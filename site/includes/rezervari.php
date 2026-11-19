<?php
// LOGICA REZERVĂRILOR: verificare date, salvare în baza de date, trimitere mailuri.
require_once __DIR__ . '/mailuri.php';
require_once __DIR__ . '/netopia.php';   // plata cu cardul
require_once __DIR__ . '/fgo.php';       // facturile fiscale

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

    // Datele pentru factură
    $tara = isset(TARI[$d['fact_tara'] ?? '']) ? $d['fact_tara'] : '';
    $judet = $tara === 'RO' && isset(JUDETE[$d['fact_judet'] ?? '']) ? $d['fact_judet'] : null;
    $pj = !empty($d['fact_pj']);
    $cui = strtoupper(preg_replace('/[\s.\-]/', '', (string) ($d['fact_cui'] ?? '')));
    if ($tara === '') {
        $erori[] = t('err_fact_tara');
    } elseif ($tara === 'RO' && !$judet) {
        $erori[] = t('err_fact_judet');
    }
    if (mb_strlen($d['fact_localitate'] ?? '') < 2) {
        $erori[] = t('err_fact_localitate');
    }
    if (mb_strlen($d['fact_adresa'] ?? '') < 4) {
        $erori[] = t('err_fact_adresa');
    }
    if ($pj && (mb_strlen($d['fact_firma'] ?? '') < 2 || !preg_match('/^[A-Z]{0,2}[0-9A-Z]{2,15}$/', $cui))) {
        $erori[] = t('err_fact_firma');
    }

    // Cât plătește acum: avansul (procentul din setări) sau toată suma
    $total    = max(0, $nopti) * $setari['pret_noapte'];
    $optiune  = ($post['plata'] ?? '') === 'integral' ? 'integral' : 'avans';
    $de_plata = $optiune === 'integral' ? $total : avans_pentru($total, $setari);

    // Cum plătește: cu cardul (pe loc, prin Netopia) sau prin ordin de plată (proformă, 48h)
    $metoda = ($post['metoda'] ?? '') === 'card' && card_activ($setari) ? 'card' : 'transfer';
    if (($post['metoda'] ?? '') === '') {
        $erori[] = t('err_metoda');
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

    $cod     = bin2hex(random_bytes(16));   // pentru linkul secret al proformei
    $avans   = $de_plata;                    // suma de plătit acum, aleasă de client
    $termen  = date('Y-m-d H:i:s', time() + ($metoda === 'card' ? (int) ($setari['termen_card_minute'] ?? 60) * 60 : $setari['termen_plata_ore'] * 3600));
    $st = $pdo->prepare("INSERT INTO rezervari (sosire, plecare, nopti, persoane, nume, email, telefon, mesaj, total, status, ip, limba, cod, avans, termen_plata, optiune_plata, plata_metoda,
                                                fact_tip, fact_tara, fact_judet, fact_localitate, fact_adresa, fact_firma, fact_cui, fact_reg_com)
                         VALUES (:sosire, :plecare, :nopti, :persoane, :nume, :email, :telefon, :mesaj, :total, 'in_asteptare', :ip, :limba, :cod, :avans, :termen, :optiune, :metoda,
                                 :fact_tip, :fact_tara, :fact_judet, :fact_localitate, :fact_adresa, :fact_firma, :fact_cui, :fact_reg_com)");
    $st->execute([
        ':sosire' => $d['sosire'], ':plecare' => $d['plecare'], ':nopti' => $nopti,
        ':persoane' => $persoane, ':nume' => $d['nume'], ':email' => $d['email'],
        ':telefon' => $d['telefon'], ':mesaj' => $d['mesaj'], ':total' => $total,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':limba' => $GLOBALS['LIMBA'],
        ':cod' => $cod, ':avans' => $avans, ':termen' => $termen, ':optiune' => $optiune, ':metoda' => $metoda,
        ':fact_tip' => $pj ? 'PJ' : 'PF', ':fact_tara' => $tara, ':fact_judet' => $judet,
        ':fact_localitate' => mb_substr($d['fact_localitate'], 0, 100), ':fact_adresa' => mb_substr($d['fact_adresa'], 0, 255),
        ':fact_firma' => $pj ? mb_substr($d['fact_firma'], 0, 160) : null, ':fact_cui' => $pj ? $cui : null,
        ':fact_reg_com' => $pj ? (mb_substr(trim($d['fact_reg_com'] ?? ''), 0, 40) ?: null) : null,
    ]);
    $id = $pdo->lastInsertId();
    } catch (PDOException $ex) {
        error_log('Eroare salvare rezervare: ' . $ex->getMessage());
        return ['erori' => [t('err_tehnic', telefon())], 'nopti' => $nopti, 'total' => 0];
    }

    // Mailurile:
    //  - ordin de plată: acum – clientul primește proforma (48h), proprietarul notificarea
    //  - card: abia după plată (confirmarea, vezi includes/netopia.php); fără plată nu pleacă nimic
    $r = $pdo->query('SELECT * FROM rezervari WHERE id = ' . (int) $id)->fetch();
    if ($metoda === 'transfer') {
        trimite_mail_client($r, 'cerere', $setari);
        trimite_mail_proprietar($r, $setari);
    }
    unset($_SESSION['token']); // un token = o singură trimitere

    return ['erori' => [], 'nopti' => $nopti, 'total' => $total, 'avans' => $avans, 'termen' => $termen, 'cod' => $cod, 'metoda' => $metoda, 'r' => $r];
}

/**
 * Clientul a ales cardul, dar se răzgândește și plătește prin ordin de plată:
 * primește proforma (cu termen de 48h), proprietarul primește notificarea.
 */
function treci_la_transfer(PDO $pdo, array $r, array $setari)
{
    if ($r['status'] !== 'in_asteptare') return false;
    $termen = date('Y-m-d H:i:s', time() + $setari['termen_plata_ore'] * 3600);
    $pdo->prepare("UPDATE rezervari SET plata_metoda = 'transfer', termen_plata = ? WHERE id = ? AND status = 'in_asteptare'")->execute([$termen, $r['id']]);
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ?");
    $st->execute([$r['id']]);
    $r = $st->fetch();
    trimite_mail_client($r, 'cerere', $setari);
    trimite_mail_proprietar($r, $setari);
    return true;
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
    $text_factura = '';
    if ($status_nou === 'confirmata') {
        $pdo->prepare("UPDATE rezervari SET platit_la = $acum, plata_metoda = COALESCE(plata_metoda, 'transfer') WHERE id = ?")->execute([$r['id']]);
        // factura fiscală pentru plata prin transfer (dacă FGO e configurat)
        if (!empty($setari['fgo_la_transfer']) && fgo_activ($setari)) {
            [$ok_f, $msg_f] = emite_factura_plata($pdo, $r, (int) $r['avans'], $setari);
            $text_factura = ' ' . rtrim($msg_f, '. ') . '.';
        }
    }
    $trimis = trimite_mail_client($r, $tip, $setari);
    $text = ($status_nou === 'confirmata' ? "Plata pentru #{$r['id']} a fost înregistrată și rezervarea e confirmată." : "Rezervarea #{$r['id']} a fost anulată.") . $text_factura;
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

/**
 * Anulează automat cererile al căror termen de plată a expirat:
 * status „anulata”, mail către client (în limba lui) și notificare către proprietar.
 * Se apelează automat (vezi includes/automat.php) și poate fi rulată și din cron (cron/expirate.php).
 * Întoarce câte rezervări a anulat.
 */
function anuleaza_expirate(PDO $pdo, array $setari)
{
    $acum = date('Y-m-d H:i:s');
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE status = 'in_asteptare' AND termen_plata IS NOT NULL AND termen_plata < ?
                           AND dovada_la IS NULL");   // cu dovadă de plată trimisă: decide proprietarul
    $st->execute([$acum]);
    $anulate = 0;
    foreach ($st->fetchAll() as $r) {
        // „AND status = 'in_asteptare'” ne apără dacă proprietarul a confirmat-o chiar în aceeași clipă
        $upd = $pdo->prepare("UPDATE rezervari SET status = 'anulata', modificat_la = ?, nota = ? WHERE id = ? AND status = 'in_asteptare'");
        $card = ($r['plata_metoda'] ?? '') === 'card';
        $upd->execute([$acum, $card ? 'Anulată automat: plata cu cardul nu a fost finalizată' : 'Anulată automat: avansul nu a fost plătit în termen', $r['id']]);
        if ($upd->rowCount() !== 1) continue;
        $anulate++;
        if ($card) continue;   // a ales cardul și n-a plătit: n-a primit niciun mail, nu-i trimitem nici acum
        trimite_mail_client($r, 'expirare', $setari);
        if ($setari['notifica_anulare_auto'] ?? true) trimite_mail_anulare_automata($r, $setari);
    }
    return $anulate;
}

/** Îl anunță pe proprietar că o cerere s-a anulat automat (cu posibilitatea de reactivare). */
function trimite_mail_anulare_automata(array $r, array $setari)
{
    $T = texte_limba('ro');
    $E = $T['mailuri'];
    $v = valori_mail($r, $setari, 'ro', $T);
    $html = sablon_mail([
        'limba' => 'ro', 'preheader' => "Anulată automat: {$r['nume']}, {$v['{perioada}']}",
        'titlu' => 'Rezervare anulată automat #' . $r['id'],
        'intro' => "{$r['nume']} nu a plătit avansul de {$v['{avans}']} până la {$v['{termen}']}, așa că rezervarea {$v['{nr}']} s-a anulat automat, zilele s-au eliberat pe site și clientul a primit mail. Dacă totuși a plătit, o poți reactiva din panou (Istoric → Plată primită – confirmă), cât timp perioada e liberă.",
        'status' => ['anulata', 'Anulată automat'],
        'sectiuni' => [sectiune_detalii($r, $v, $E, true)],
        'butoane' => [['Deschide panoul', SITE_URL . '/admin/?vezi=istoric', 'plin']],
        'E' => $E, 'T' => $T, 'v' => $v, 'setari' => $setari,
    ]);
    return trimite_mail_html($setari['email_proprietar'], "Anulată automat #{$r['id']} – {$v['{perioada}']} – {$r['nume']}", $html, $setari, $r['email']);
}

// ============================================================
//  DOVADA DE PLATĂ
//  Fișierele se păstrează ÎN AFARA public_html (nu pot fi deschise din browser),
//  în folderul dovezi-plata de lângă public_html. Se văd doar din panou.
// ============================================================
function folder_dovezi()
{
    $folder = dirname(__DIR__, 2) . '/dovezi-plata';
    if (!is_dir($folder)) {
        @mkdir($folder, 0750, true);
        @file_put_contents($folder . '/.htaccess', "Require all denied\n");
    }
    return $folder;
}

/**
 * Salvează dovada de plată încărcată de client pentru rezervarea $r.
 * Întoarce [true, ''] sau [false, cheia mesajului de eroare].
 */
function salveaza_dovada(PDO $pdo, array $r, array $fisier, array $setari)
{
    if (($fisier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_INI_SIZE || ($fisier['size'] ?? 0) > 8 * 1024 * 1024) {
        return [false, 'dov_err_mare'];
    }
    if (($fisier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($fisier['tmp_name'])) {
        return [false, 'dov_err'];
    }
    $tipuri = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fisier['tmp_name']);
    if (!isset($tipuri[$mime])) {
        return [false, 'dov_err_tip'];
    }
    $nume = numar_proforma($r['id'], $setari) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $tipuri[$mime];
    $cale = folder_dovezi() . '/' . $nume;
    if (!move_uploaded_file($fisier['tmp_name'], $cale)) {
        return [false, 'dov_err'];
    }
    @chmod($cale, 0640);
    $acum = date('Y-m-d H:i:s');
    $pdo->prepare("UPDATE rezervari SET dovada_fisier = ?, dovada_la = ? WHERE id = ?")->execute([$nume, $acum, $r['id']]);
    $r['dovada_fisier'] = $nume;
    $r['dovada_la'] = $acum;
    if ($setari['notifica_dovada'] ?? true) trimite_mail_dovada($r, $cale, $mime, $setari);
    return [true, ''];
}

/** Îl anunță pe proprietar că a sosit o dovadă de plată (cu fișierul atașat). */
function trimite_mail_dovada(array $r, $cale, $mime, array $setari)
{
    $T = texte_limba('ro');
    $E = $T['mailuri'];
    $v = valori_mail($r, $setari, 'ro', $T);
    $html = sablon_mail([
        'limba' => 'ro', 'preheader' => "Dovadă de plată: {$r['nume']}, {$v['{avans}']}",
        'titlu' => 'Dovadă de plată primită #' . $r['id'],
        'intro' => "{$r['nume']} a trimis dovada de plată pentru {$v['{nr}']} ({$v['{perioada}']}), suma de {$v['{avans}']}. Fișierul e atașat. Verifică intrarea banilor în cont și apasă „Plată primită – confirmă” în panou. Rezervarea nu se mai anulează automat cât timp o verifici.",
        'status' => ['asteptare', 'Dovadă de plată trimisă'],
        'sectiuni' => [sectiune_detalii($r, $v, $E, true)],
        'butoane' => [['Deschide panoul', SITE_URL . '/admin/', 'plin']],
        'E' => $E, 'T' => $T, 'v' => $v, 'setari' => $setari,
    ]);
    return trimite_mail_html($setari['email_proprietar'], "Dovadă de plată #{$r['id']} – {$r['nume']} – {$v['{avans}']}", $html, $setari, $r['email'],
                             [['cale' => $cale, 'nume' => basename($cale), 'tip' => $mime]]);
}

// ============================================================
//  FACTURI DIN PANOU (FGO)
// ============================================================

/** Emite factura pentru restul încasat la sosire și o trimite clientului. */
function factureaza_rest(PDO $pdo, $id, array $setari)
{
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ?");
    $st->execute([(int) $id]);
    $r = $st->fetch();
    if (!$r || $r['status'] !== 'confirmata') return ['eroare', "Rezervarea #$id nu este confirmată."];
    $rest = (int) $r['total'] - suma_facturata($pdo, $r['id']);
    if ($rest <= 0) return ['info', "Pentru #$id s-a facturat deja toată suma."];
    [$ok, $mesaj] = emite_factura($pdo, $r, 'rest', $rest, $setari);
    if (!$ok) return ['eroare', $mesaj];
    $trimis = trimite_mail_client($r, 'factura', $setari);
    return ['ok', $mesaj . ($trimis ? " Clientul a primit factura pe mail ({$r['email']})." : ' ATENȚIE: mailul cu factura nu a putut fi trimis.')];
}

/** Reîncearcă o factură care a dat eroare (după ce ai corectat cauza). */
function reincearca_factura(PDO $pdo, $id_factura, array $setari)
{
    $st = $pdo->prepare("SELECT f.*, r.status AS status_rez FROM facturi f JOIN rezervari r ON r.id = f.rezervare_id WHERE f.id = ?");
    $st->execute([(int) $id_factura]);
    $f = $st->fetch();
    if (!$f) return ['eroare', 'Factura nu există.'];
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ?");
    $st->execute([$f['rezervare_id']]);
    $r = $st->fetch();
    [$ok, $mesaj] = emite_factura($pdo, $r, $f['tip'], (int) $f['suma'], $setari);
    if (!$ok) return ['eroare', $mesaj];
    $trimis = $r['status'] === 'confirmata' && trimite_mail_client($r, 'factura', $setari);
    return ['ok', $mesaj . ($trimis ? " Clientul a primit factura pe mail ({$r['email']})." : '')];
}
