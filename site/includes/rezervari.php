<?php
// LOGICA REZERVĂRILOR: verificare date, salvare în baza de date, trimitere mailuri.
require_once __DIR__ . '/mailuri.php';

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
                         WHERE " . conditie_ocupat() . " AND sosire < :plecare AND plecare > :sosire");
    $st->execute([':sosire' => $d['sosire'], ':plecare' => $d['plecare']]);
    if ($st->fetchColumn() > 0) {
        return ['erori' => [t('err_ocupat')], 'nopti' => $nopti, 'total' => 0];
    }

    $total = $nopti * $setari['pret_noapte'];

    $cod     = bin2hex(random_bytes(16));   // pentru linkul secret al proformei
    $avans   = avans_pentru($total, $setari);
    $termen  = date('Y-m-d H:i:s', time() + $setari['termen_plata_ore'] * 3600);
    $st = $pdo->prepare("INSERT INTO rezervari (sosire, plecare, nopti, persoane, nume, email, telefon, mesaj, total, status, ip, limba, cod, avans, termen_plata)
                         VALUES (:sosire, :plecare, :nopti, :persoane, :nume, :email, :telefon, :mesaj, :total, 'in_asteptare', :ip, :limba, :cod, :avans, :termen)");
    $st->execute([
        ':sosire' => $d['sosire'], ':plecare' => $d['plecare'], ':nopti' => $nopti,
        ':persoane' => $persoane, ':nume' => $d['nume'], ':email' => $d['email'],
        ':telefon' => $d['telefon'], ':mesaj' => $d['mesaj'], ':total' => $total,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':limba' => $GLOBALS['LIMBA'],
        ':cod' => $cod, ':avans' => $avans, ':termen' => $termen,
    ]);
    $id = $pdo->lastInsertId();
    } catch (PDOException $ex) {
        error_log('Eroare salvare rezervare: ' . $ex->getMessage());
        return ['erori' => [t('err_tehnic', telefon())], 'nopti' => $nopti, 'total' => 0];
    }

    // mailurile: proprietarului (notificare) și clientului (cererea + proforma)
    $r = $pdo->query('SELECT * FROM rezervari WHERE id = ' . (int) $id)->fetch();
    trimite_mail_client($r, 'cerere', $setari);
    trimite_mail_proprietar($r, $setari);
    unset($_SESSION['token']); // un token = o singură trimitere

    return ['erori' => [], 'nopti' => $nopti, 'total' => $total, 'avans' => $avans, 'termen' => $termen, 'cod' => $cod];
}

/** Avansul pentru o sumă (procentul din setari.php, rotunjit la leu). */
function avans_pentru($total, array $setari)
{
    $procent = max(0, min(100, (int) $setari['avans_procent']));
    return $procent >= 100 ? (int) $total : (int) round($total * $procent / 100);
}

/** Numărul proformei: VR-0007 */
function numar_proforma($id, array $setari)
{
    return $setari['serie_proforma'] . '-' . str_pad((string) (int) $id, 4, '0', STR_PAD_LEFT);
}

/** Linkul secret spre proforma unei rezervări, în limba clientului. */
function link_proforma(array $r)
{
    $limba = $r['limba'] ?? 'ro';
    return 'https://vilabusteni.ro' . ($limba === 'ro' ? '' : '/' . $limba) . '/proforma.php?cod=' . $r['cod'];
}

// ============================================================
//  FUNCȚII PENTRU PANOUL DE ADMINISTRARE
// ============================================================

/** Rezervările se suprapun cu altele confirmate / blocate? (fără rezervarea $exceptie) */
function perioada_ocupata(PDO $pdo, $sosire, $plecare, $exceptie = 0)
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM rezervari
                         WHERE " . conditie_ocupat() . " AND id <> :id
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
    if ($status_nou === 'confirmata') {
        $pdo->prepare("UPDATE rezervari SET platit_la = $acum WHERE id = ?")->execute([$r['id']]);
    }
    $trimis = trimite_mail_client($r, $tip, $setari);
    $text = $status_nou === 'confirmata' ? "Plata pentru #{$r['id']} a fost înregistrată și rezervarea e confirmată." : "Rezervarea #{$r['id']} a fost anulată.";
    return $trimis
        ? ['ok', "$text Clientul a primit mail ({$r['email']})."]
        : ['eroare', "$text ATENȚIE: mailul către client nu a putut fi trimis – anunță-l telefonic ({$r['telefon']})."];
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
