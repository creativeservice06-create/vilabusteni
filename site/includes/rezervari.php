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
                         WHERE status IN ('confirmata', 'blocat') AND sosire < :plecare AND plecare > :sosire");
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
          . "Mesaj:\n{$d['mesaj']}\n\n"
          . "Confirmă sau anulează din panou: https://vilabusteni.ro/admin/\n";
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

// ============================================================
//  FUNCȚII PENTRU PANOUL DE ADMINISTRARE
// ============================================================

/** Rezervările se suprapun cu altele confirmate / blocate? (fără rezervarea $exceptie) */
function perioada_ocupata(PDO $pdo, $sosire, $plecare, $exceptie = 0)
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM rezervari
                         WHERE status IN ('confirmata', 'blocat') AND id <> :id
                           AND sosire < :plecare AND plecare > :sosire");
    $st->execute([':id' => (int) $exceptie, ':sosire' => $sosire, ':plecare' => $plecare]);
    return $st->fetchColumn() > 0;
}

/**
 * Confirmă sau anulează o rezervare și trimite clientului mailul potrivit, în limba lui.
 * Întoarce un mesaj pentru panou.
 */
function schimba_status(PDO $pdo, $id, $status_nou, array $setari)
{
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ?");
    $st->execute([(int) $id]);
    $r = $st->fetch();
    if (!$r) {
        return ['eroare', "Rezervarea #$id nu există."];
    }
    if ($r['status'] === $status_nou) {
        return ['info', "Rezervarea #$id are deja acest status."];
    }

    if ($status_nou === 'confirmata' && perioada_ocupata($pdo, $r['sosire'], $r['plecare'], $r['id'])) {
        return ['eroare', "Nu pot confirma #$id: perioada se suprapune cu o rezervare confirmată sau cu zile blocate."];
    }

    $acum = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()';
    $pdo->prepare("UPDATE rezervari SET status = ?, modificat_la = $acum WHERE id = ?")->execute([$status_nou, $r['id']]);

    if ($r['status'] === 'blocat' || $r['email'] === '') {
        return ['ok', $status_nou === 'anulata' ? 'Zilele au fost deblocate.' : 'Status schimbat.'];
    }

    $tip = $status_nou === 'confirmata' ? 'confirmare' : 'anulare';
    $trimis = trimite_mail_status($r, $tip, $setari);
    $text = $status_nou === 'confirmata' ? "Rezervarea #{$r['id']} a fost confirmată." : "Rezervarea #{$r['id']} a fost anulată.";
    return $trimis
        ? ['ok', "$text Clientul a primit mail ({$r['email']})."]
        : ['eroare', "$text ATENȚIE: mailul către client nu a putut fi trimis – anunță-l telefonic ({$r['telefon']})."];
}

/** Mailul de confirmare / anulare către client, în limba în care a rezervat. */
function trimite_mail_status(array $r, $tip, array $setari)
{
    $limba = $r['limba'] ?? 'ro';
    $T = texte_limba($limba);
    $sep = ['en' => ',', 'ru' => ' '][$limba] ?? '.';
    $bani = function ($suma) use ($T, $sep) { return number_format((int) $suma, 0, ',', $sep) . ' ' . $T['moneda']; };
    $tel = $limba === 'ro' ? $setari['telefon'] : $setari['telefon_int'];
    $perioada = date('d.m.Y', strtotime($r['sosire'])) . ' – ' . date('d.m.Y', strtotime($r['plecare']));
    $prefix = $limba === 'ro' ? '' : '/' . $limba;

    if ($tip === 'anulare') {
        $text = strtr($T['mail_anul_corp'], [
            '{nume}' => $r['nume'], '{vila}' => $setari['nume_vila'], '{perioada}' => $perioada, '{id}' => $r['id'],
            '{link}' => 'https://vilabusteni.ro' . $prefix . '/rezervare.php', '{telefon}' => $tel,
        ]);
        return trimite_mail($r['email'], sprintf($T['mail_anul_subiect'], $setari['nume_vila']), $text, $setari, $setari['email_contact']);
    }

    // Plata: doar dacă IBAN-ul e completat în setari.php
    $plata = '';
    if ($setari['iban'] !== '') {
        $banca = strtr($T['mail_banca'], [
            '{titular}' => $setari['firma_nume'], '{iban}' => $setari['iban'],
            '{nume_banca}' => $setari['banca'], '{id}' => $r['id'],
        ]);
        $procent = (int) $setari['avans_procent'];
        if ($procent > 0 && $procent < 100) {
            $avans = (int) round($r['total'] * $procent / 100);
            $plata = strtr($T['mail_plata_avans'], [
                '{avans}' => $bani($avans), '{procent}' => $procent, '{zile}' => $setari['avans_zile'],
                '{rest}' => $bani($r['total'] - $avans), '{banca}' => $banca,
            ]);
        } else {
            $plata = strtr($T['mail_plata_tot'], ['{total}' => $bani($r['total']), '{zile}' => $setari['avans_zile'], '{banca}' => $banca]);
        }
    }

    // Politica de anulare: doar dacă e completată în lang/*.php
    $anulare = trim($T['politica_anulare'] ?? '') !== '' ? "\n" . $T['mail_anulare_titlu'] . "\n" . trim($T['politica_anulare']) . "\n" : '';

    $text = strtr($T['mail_conf_corp'], [
        '{nume}' => $r['nume'], '{id}' => $r['id'], '{vila}' => $setari['nume_vila'], '{perioada}' => $perioada,
        '{nopti}' => $r['nopti'], '{persoane}' => $r['persoane'], '{total}' => $bani($r['total']),
        '{check_in}' => $setari['ora_check_in'], '{check_out}' => $setari['ora_check_out'],
        '{adresa}' => $setari['adresa'], '{harta}' => $setari['harta_link'], '{telefon}' => $tel,
        '{plata}' => $plata, '{anulare}' => $anulare,
    ]);
    return trimite_mail($r['email'], sprintf($T['mail_conf_subiect'], $setari['nume_vila']), $text, $setari, $setari['email_contact']);
}

/** Blochează manual o perioadă (ex. închiriată prin telefon sau pe alte site-uri). */
function blocheaza_perioada(PDO $pdo, $sosire, $plecare, $nota)
{
    $a = DateTime::createFromFormat('!Y-m-d', $sosire);
    $b = DateTime::createFromFormat('!Y-m-d', $plecare);
    if (!$a || !$b || $b <= $a) {
        return ['eroare', 'Alege o dată de început și una de sfârșit (sfârșitul după început).'];
    }
    if (perioada_ocupata($pdo, $sosire, $plecare)) {
        return ['eroare', 'Perioada se suprapune cu o rezervare confirmată sau cu zile deja blocate.'];
    }
    $nopti = (int) $a->diff($b)->format('%a');
    $pdo->prepare("INSERT INTO rezervari (sosire, plecare, nopti, persoane, nume, email, telefon, mesaj, total, status, nota)
                   VALUES (?, ?, ?, 0, 'Blocat manual', '', '', '', 0, 'blocat', ?)")
        ->execute([$sosire, $plecare, $nopti, mb_substr(trim($nota), 0, 255)]);
    return ['ok', "Am blocat $nopti " . ($nopti === 1 ? 'noapte' : 'nopți') . ': ' . $a->format('d.m.Y') . ' – ' . $b->format('d.m.Y') . '.'];
}
