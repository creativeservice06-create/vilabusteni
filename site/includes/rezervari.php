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
        $erori[] = 'Sesiunea a expirat. Te rugăm să reîncerci.';
    }
    if (!empty($post['site_web'])) {
        $erori[] = 'Cererea nu a putut fi trimisă.'; // robot
    }

    // Perioada
    $sosire  = DateTime::createFromFormat('!Y-m-d', $d['sosire']);
    $plecare = DateTime::createFromFormat('!Y-m-d', $d['plecare']);
    $azi     = new DateTime('today');
    $nopti   = 0;

    if (!$sosire || !$plecare) {
        $erori[] = 'Alege în calendar data sosirii și data plecării.';
    } else {
        $nopti = (int) $sosire->diff($plecare)->format('%r%a');
        if ($sosire < $azi) {
            $erori[] = 'Data sosirii nu poate fi în trecut.';
        }
        if ($nopti < $setari['minim_nopti']) {
            $erori[] = "Perioada minimă este de {$setari['minim_nopti']} nopți.";
        }
        if ($nopti > 60) {
            $erori[] = 'Pentru sejururi mai lungi de 60 de nopți te rugăm să ne suni.';
        }
    }

    // Date personale
    if (mb_strlen($d['nume']) < 3) {
        $erori[] = 'Completează numele.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $erori[] = 'Adresa de email nu este validă.';
    }
    if (strlen(preg_replace('/\D/', '', $d['telefon'])) < 9) {
        $erori[] = 'Numărul de telefon nu este valid.';
    }
    $persoane = (int) $d['persoane'];
    if ($persoane < 1 || $persoane > $setari['max_persoane']) {
        $erori[] = 'Număr de persoane invalid.';
    }
    if (empty($post['accept'])) {
        $erori[] = 'Trebuie să accepți Termenii și condițiile.';
    }

    if ($erori) {
        return ['erori' => $erori, 'nopti' => $nopti, 'total' => 0];
    }

    $pdo = db();
    if (!$pdo) {
        return ['erori' => ['Rezervările online sunt momentan indisponibile. Te rugăm să ne suni la ' . $setari['telefon'] . '.'], 'nopti' => $nopti, 'total' => 0];
    }

    try {
    // Perioada se suprapune cu o rezervare confirmată?
    $st = $pdo->prepare("SELECT COUNT(*) FROM rezervari
                         WHERE status = 'confirmata' AND sosire < :plecare AND plecare > :sosire");
    $st->execute([':sosire' => $d['sosire'], ':plecare' => $d['plecare']]);
    if ($st->fetchColumn() > 0) {
        return ['erori' => ['Perioada aleasă nu mai este disponibilă. Te rugăm să alegi alte date.'], 'nopti' => $nopti, 'total' => 0];
    }

    $total = $nopti * $setari['pret_noapte'];

    $st = $pdo->prepare("INSERT INTO rezervari (sosire, plecare, nopti, persoane, nume, email, telefon, mesaj, total, status, ip)
                         VALUES (:sosire, :plecare, :nopti, :persoane, :nume, :email, :telefon, :mesaj, :total, 'in_asteptare', :ip)");
    $st->execute([
        ':sosire' => $d['sosire'], ':plecare' => $d['plecare'], ':nopti' => $nopti,
        ':persoane' => $persoane, ':nume' => $d['nume'], ':email' => $d['email'],
        ':telefon' => $d['telefon'], ':mesaj' => $d['mesaj'], ':total' => $total,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    $id = $pdo->lastInsertId();
    } catch (PDOException $ex) {
        error_log('Eroare salvare rezervare: ' . $ex->getMessage());
        return ['erori' => ['A apărut o eroare tehnică. Te rugăm să ne suni la ' . $setari['telefon'] . '.'], 'nopti' => $nopti, 'total' => 0];
    }

    trimite_mailuri($id, $d, $nopti, $total, $persoane, $setari);
    unset($_SESSION['token']); // un token = o singură trimitere

    return ['erori' => [], 'nopti' => $nopti, 'total' => $total];
}

/** Trimite mailul către proprietar și confirmarea de primire către client. */
function trimite_mailuri($id, array $d, $nopti, $total, $persoane, array $setari)
{
    $perioada = date('d.m.Y', strtotime($d['sosire'])) . ' – ' . date('d.m.Y', strtotime($d['plecare']));

    // 1. Către proprietar
    $text = "Cerere nouă de rezervare #$id\n\n"
          . "Perioada: $perioada ($nopti nopți)\n"
          . "Persoane: $persoane\n"
          . "Total: " . lei($total) . "\n\n"
          . "Nume: {$d['nume']}\nEmail: {$d['email']}\nTelefon: {$d['telefon']}\n\n"
          . "Mesaj:\n{$d['mesaj']}\n";
    trimite_mail($setari['email_proprietar'], "Rezervare nouă #$id – $perioada", $text, $setari, $d['email']);

    // 2. Către client
    $text = "Bună ziua, {$d['nume']},\n\n"
          . "Am primit cererea ta de rezervare la {$setari['nume_vila']}.\n\n"
          . "Perioada: $perioada ($nopti nopți)\n"
          . "Persoane: $persoane\n"
          . "Total estimat: " . lei($total) . "\n"
          . "Check-in de la {$setari['ora_check_in']}, check-out până la {$setari['ora_check_out']}.\n\n"
          . "Rezervarea devine fermă după confirmarea noastră. Te contactăm în cel mai scurt timp.\n\n"
          . "Adresa: {$setari['adresa']}\n"
          . "Telefon: {$setari['telefon']}\n\n"
          . "Cu drag,\n{$setari['nume_vila']}\n";
    trimite_mail($d['email'], "Am primit cererea ta – {$setari['nume_vila']}", $text, $setari, $setari['email_contact']);
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
