<?php
// LOGICA REZERVĂRILOR: verificare date, salvare în baza de date, trimitere mailuri.

/**
 * Verifică și salvează o cerere de rezervare.
 * Întoarce ['erori' => [...], 'nopti' => n, 'total' => suma].
 */
function salveaza_rezervare(array $d, array $post, array $setari)
{
    $erori = [];

    // Protecții de bază
    if (($post['token'] ?? '') !== ($_SESSION['token'] ?? null)) {
        $erori[] = t('err_sesiune');
    }
    if (!empty($post['site_web'])) {
        $erori[] = t('err_robot'); // robot
    }

    // Perioada
    $sosire  = DateTime::createFromFormat('!Y-m-d', $d['sosire']);
    $plecare = DateTime::createFromFormat('!Y-m-d', $d['plecare']);
    $azi     = new DateTime('today');
    $nopti   = 0;

    if (!$sosire || !$plecare) {
        $erori[] = t('err_date');
    } else {
        $nopti = (int) $sosire->diff($plecare)->format('%r%a');
        if ($sosire < $azi) {
            $erori[] = t('err_trecut');
        }
        if ($nopti < $setari['minim_nopti']) {
            $erori[] = t('err_minim', $setari['minim_nopti']);
        }
        if ($nopti > 60) {
            $erori[] = t('err_lung');
        }
    }

    // Date personale
    if (mb_strlen($d['nume']) < 3) {
        $erori[] = t('err_nume');
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $erori[] = t('err_email');
    }
    if (strlen(preg_replace('/\D/', '', $d['telefon'])) < 9) {
        $erori[] = t('err_telefon');
    }
    $persoane = (int) $d['persoane'];
    if ($persoane < 1 || $persoane > $setari['max_persoane']) {
        $erori[] = t('err_persoane');
    }
    if (empty($post['accept'])) {
        $erori[] = t('err_accept');
    }

    if ($erori) {
        return ['erori' => $erori, 'nopti' => $nopti, 'total' => 0];
    }

    $pdo = db();
    if (!$pdo) {
        return ['erori' => [t('err_indisponibil', telefon())], 'nopti' => $nopti, 'total' => 0];
    }

    try {
    // Perioada se suprapune cu o rezervare confirmată?
    $st = $pdo->prepare("SELECT COUNT(*) FROM rezervari
                         WHERE status = 'confirmata' AND sosire < :plecare AND plecare > :sosire");
    $st->execute([':sosire' => $d['sosire'], ':plecare' => $d['plecare']]);
    if ($st->fetchColumn() > 0) {
        return ['erori' => [t('err_ocupat')], 'nopti' => $nopti, 'total' => 0];
    }

    $total = $nopti * $setari['pret_noapte'];

    $st = $pdo->prepare("INSERT INTO rezervari (sosire, plecare, nopti, persoane, nume, email, telefon, mesaj, total, status, ip, limba)
                         VALUES (:sosire, :plecare, :nopti, :persoane, :nume, :email, :telefon, :mesaj, :total, 'in_asteptare', :ip, :limba)");
    $st->execute([
        ':sosire' => $d['sosire'], ':plecare' => $d['plecare'], ':nopti' => $nopti,
        ':persoane' => $persoane, ':nume' => $d['nume'], ':email' => $d['email'],
        ':telefon' => $d['telefon'], ':mesaj' => $d['mesaj'], ':total' => $total,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':limba' => $GLOBALS['LIMBA'],
    ]);
    $id = $pdo->lastInsertId();
    } catch (PDOException $ex) {
        error_log('Eroare salvare rezervare: ' . $ex->getMessage());
        return ['erori' => [t('err_tehnic', telefon())], 'nopti' => $nopti, 'total' => 0];
    }

    trimite_mailuri($id, $d, $nopti, $total, $persoane, $setari);
    unset($_SESSION['token']); // un token = o singură trimitere

    return ['erori' => [], 'nopti' => $nopti, 'total' => $total];
}

/** Trimite mailul către proprietar (în română) și confirmarea de primire către client (în limba lui). */
function trimite_mailuri($id, array $d, $nopti, $total, $persoane, array $setari)
{
    global $LIMBA;
    $perioada = date('d.m.Y', strtotime($d['sosire'])) . ' – ' . date('d.m.Y', strtotime($d['plecare']));

    // 1. Către proprietar – mereu în română, cu limba clientului
    $text = "Cerere nouă de rezervare #$id\n\n"
          . "Perioada: $perioada ($nopti nopți)\n"
          . "Persoane: $persoane\n"
          . "Total: " . number_format($total, 0, ',', '.') . " lei\n"
          . "Limba clientului: " . LIMBI[$LIMBA] . "\n\n"
          . "Nume: {$d['nume']}\nEmail: {$d['email']}\nTelefon: {$d['telefon']}\n\n"
          . "Mesaj:\n{$d['mesaj']}\n";
    trimite_mail($setari['email_proprietar'], "Rezervare nouă #$id – $perioada", $text, $setari, $d['email']);

    // 2. Către client – în limba în care a făcut rezervarea
    $text = strtr(t('mail_corp'), [
        '{nume}' => $d['nume'], '{vila}' => $setari['nume_vila'], '{perioada}' => $perioada,
        '{nopti}' => $nopti, '{persoane}' => $persoane, '{total}' => lei($total),
        '{check_in}' => $setari['ora_check_in'], '{check_out}' => $setari['ora_check_out'],
        '{adresa}' => $setari['adresa'], '{telefon}' => telefon(),
    ]);
    trimite_mail($d['email'], t('mail_subiect', $setari['nume_vila']), $text, $setari, $setari['email_contact']);
}

function trimite_mail($catre, $subiect, $text, array $setari, $raspuns_la)
{
    $antete = [
        'From: ' . mb_encode_mimeheader($setari['nume_vila']) . ' <' . $setari['email_expeditor'] . '>',
        'Reply-To: ' . $raspuns_la,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $ok = @mail($catre, mb_encode_mimeheader($subiect, 'UTF-8'), $text, implode("\r\n", $antete), '-f' . $setari['email_expeditor']);
    if (!$ok) {
        error_log("Mailul către $catre nu a putut fi trimis.");
    }
    return $ok;
}
