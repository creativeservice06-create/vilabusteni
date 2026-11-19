<?php
// ============================================================
//  PLATA CU CARDUL PRIN NETOPIA (API v2)
//
//  Cum merge:
//   1. Clientul apasă „Plătește cu cardul” → plata-card.php cere la Netopia o pagină de plată
//      (start_plata_card) și îl trimite acolo. Datele cardului NU trec prin site.
//   2. După plată, Netopia anunță site-ul în fundal (api/netopia-ipn.php). Mesajul e semnat
//      de Netopia; îl verificăm cu cheia lor publică (verifica_ipn) înainte să-l credem.
//   3. Dacă plata a reușit: rezervarea se confirmă, se emite factura (FGO) și pleacă mailurile.
//   4. Clientul revine pe plata-card.php?cod=...&intors=1 și vede rezultatul.
//
//  Cheile se pun DOAR în config.php pe server:
//   'netopia_mediu'         => 'sandbox' sau 'live'
//   'netopia_api_key'       => '...'   (Netopia admin → Profil → Securitate)
//   'netopia_pos_signature' => 'XXXX-XXXX-XXXX-XXXX-XXXX'   (Puncte de vânzare → Setări tehnice)
//   'netopia_cheie_publica' => conținutul cheii publice Netopia (-----BEGIN ...) sau calea spre fișierul .cer/.pem
// ============================================================
require_once __DIR__ . '/tari.php';

const NETOPIA_URL = [
    'sandbox' => 'https://secure.sandbox.netopia-payments.com',
    'live'    => 'https://secure.mobilpay.ro/pay',
];
// Codurile de status trimise de Netopia
const NETOPIA_PLATIT    = [3, 5];          // plătit / confirmat
const NETOPIA_RESPINS   = [11, 12, 13];    // eroare / refuzat / suspect de fraudă
const NETOPIA_ANULAT    = [4];
const NETOPIA_RAMBURSAT = [8];

/** Cheile Netopia din config.php, sau null dacă plata cu cardul nu e configurată. */
function config_netopia()
{
    $c = config_site();
    $cheie = trim((string) ($c['netopia_api_key'] ?? ''));
    $pos   = trim((string) ($c['netopia_pos_signature'] ?? ''));
    if ($cheie === '' || $pos === '') return null;
    $mediu = ($c['netopia_mediu'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
    return [
        'mediu'   => $mediu,
        'cheie'   => $cheie,
        'pos'     => $pos,
        'publica' => (string) ($c['netopia_cheie_publica'] ?? ''),
        'url'     => rtrim($c['netopia_url_test'] ?? NETOPIA_URL[$mediu], '/'),
    ];
}

/** Plata cu cardul e pornită? (chei completate + 'plata_card' => true în setări) */
function card_activ(array $setari)
{
    return !empty($setari['plata_card']) && config_netopia() !== null;
}

/** Rezervarea mai poate fi plătită cu cardul acum? */
function poate_plati_card(array $r, array $setari)
{
    if (!card_activ($setari) || ($r['status'] ?? '') !== 'in_asteptare' || (int) ($r['avans'] ?? 0) <= 0) return false;
    $termen_ok = !empty($r['termen_plata']) && strtotime($r['termen_plata']) > time();
    return $termen_ok || !empty($r['dovada_la']);
}

/** Linkul paginii de plată cu cardul pentru o rezervare, în limba clientului. */
function link_plata_card(array $r)
{
    $limba = $r['limba'] ?? 'ro';
    return SITE_URL . ($limba === 'ro' ? '' : '/' . $limba) . '/plata-card.php?cod=' . $r['cod'];
}

/** Cerere JSON simplă (cURL). Întoarce [cod HTTP, răspuns decodat sau null, text brut, eroare]. */
function cerere_json($metoda, $url, array $antete, $corp = null, $timp = 25)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $metoda,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timp,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $antete,
                                              $corp !== null ? ['Content-Type: application/json'] : []),
    ]);
    if ($corp !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($corp) ? $corp : json_encode($corp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $text = curl_exec($ch);
    $eroare = $text === false ? curl_error($ch) : '';
    $cod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = is_string($text) ? json_decode($text, true) : null;
    return [$cod, is_array($json) ? $json : null, (string) $text, $eroare];
}

/**
 * Cere la Netopia o pagină de plată pentru suma de plătit a rezervării.
 * Întoarce ['url' => adresa paginii de plată] sau ['eroare' => motivul (pentru jurnal)].
 */
function start_plata_card(PDO $pdo, array $r, array $setari)
{
    $n = config_netopia();
    if (!$n) return ['eroare' => 'Netopia nu este configurată'];

    $suma    = (int) $r['avans'];
    $comanda = numar_proforma($r['id'], $setari) . '-' . date('ymdHis') . '-' . bin2hex(random_bytes(2));
    $acum    = date('Y-m-d H:i:s');
    $pdo->prepare("INSERT INTO plati_card (rezervare_id, comanda, suma, status, creat_la) VALUES (?, ?, ?, 'pornita', ?)")
        ->execute([$r['id'], $comanda, $suma, $acum]);
    $id_plata = (int) $pdo->lastInsertId();

    // Numele: prenume + nume (Netopia le cere separat)
    $parti = preg_split('/\s+/', trim($r['nume']));
    $nume_familie = count($parti) > 1 ? array_pop($parti) : $parti[0];
    $prenume = count($parti) ? implode(' ', $parti) : $nume_familie;

    $tara = $r['fact_tara'] ?: 'RO';
    $judet = $tara === 'RO' ? (JUDETE[$r['fact_judet']] ?? '') : '';
    $limba = in_array($r['limba'], ['ro', 'en', 'de', 'it', 'ru'], true) ? $r['limba'] : 'ro';
    $baza = SITE_URL . ($r['limba'] === 'ro' ? '' : '/' . $r['limba']);

    $cerere = [
        'config' => [
            'emailTemplate' => '',
            'emailSubject'  => '',
            'notifyUrl'     => SITE_URL . '/api/netopia-ipn.php',
            'redirectUrl'   => $baza . '/plata-card.php?cod=' . $r['cod'] . '&intors=1',
            'cancelUrl'     => $baza . '/plata-card.php?cod=' . $r['cod'] . '&intors=1',
            'language'      => $limba,
        ],
        'payment' => [
            'options' => ['installments' => 0, 'bonus' => 0],
            'data'    => array_filter([
                'BROWSER_USER_AGENT' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
                'IP_ADDRESS'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'BROWSER_LANGUAGE'   => substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 8),
            ]),
        ],
        'order' => [
            'posSignature' => $n['pos'],
            'dateTime'     => date('c'),
            'description'  => mb_substr(numar_proforma($r['id'], $setari) . ' – ' . $setari['nume_vila'] . ', '
                              . date('d.m.Y', strtotime($r['sosire'])) . ' – ' . date('d.m.Y', strtotime($r['plecare'])), 0, 200),
            'orderID'      => $comanda,
            'amount'       => $suma,
            'currency'     => 'RON',
            'billing'      => [
                'email'       => $r['email'],
                'phone'       => $r['telefon'],
                'firstName'   => $prenume,
                'lastName'    => $nume_familie,
                'city'        => (string) ($r['fact_localitate'] ?: ''),
                'country'     => TARI[$tara][0] ?? 642,
                'countryName' => TARI[$tara][2] ?? 'Romania',
                'state'       => $judet !== '' ? $judet : (string) ($r['fact_localitate'] ?: ''),
                'postalCode'  => '',
                'details'     => (string) ($r['fact_adresa'] ?: ''),
            ],
            'products' => [[
                'name'     => 'Cazare ' . $setari['nume_vila'] . ' (' . (int) $r['nopti'] . ' nopți)',
                'code'     => numar_proforma($r['id'], $setari),
                'category' => 'Cazare',
                'price'    => $suma,
                'vat'      => 0,
            ]],
        ],
    ];

    [$cod, $json, $text, $eroare_retea] = cerere_json('POST', $n['url'] . '/payment/card/start', ['Authorization: ' . $n['cheie']], $cerere);

    $plata = $json['payment'] ?? [];
    $url = $plata['paymentURL'] ?? ($json['customerAction']['url'] ?? '');
    $ntp = (string) ($plata['ntpID'] ?? '');
    if ($url !== '' && $cod >= 200 && $cod < 300) {
        $pdo->prepare("UPDATE plati_card SET ntp_id = ?, actualizat_la = ? WHERE id = ?")->execute([$ntp ?: null, $acum, $id_plata]);
        return ['url' => $url];
    }

    $motiv = $eroare_retea ?: ('HTTP ' . $cod . ': ' . ($json['error']['message'] ?? mb_substr($text, 0, 200)));
    $pdo->prepare("UPDATE plati_card SET status = 'eroare', mesaj = ?, actualizat_la = ? WHERE id = ?")
        ->execute([mb_substr($motiv, 0, 255), $acum, $id_plata]);
    error_log('Netopia start: ' . $motiv);
    return ['eroare' => $motiv];
}

// ------------------------------------------------------------
//  Verificarea mesajului de la Netopia (JWT semnat RS512)
// ------------------------------------------------------------
function base64url_decode($s)
{
    $s = strtr($s, '-_', '+/');
    return base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4), true);
}

/** Cheia publică Netopia (din text sau din fișier). */
function cheie_publica_netopia(array $n)
{
    $k = trim($n['publica']);
    if ($k === '') return null;
    if (strpos($k, '-----BEGIN') === false && is_file($k)) {
        $k = (string) file_get_contents($k);
    }
    $cheie = @openssl_pkey_get_public($k);
    return $cheie ?: null;
}

/**
 * Verifică semnătura unui mesaj IPN. $token = antetul Verification-token, $corp = corpul brut.
 * Întoarce null dacă e valid, altfel motivul respingerii.
 */
function verifica_ipn($token, $corp)
{
    $n = config_netopia();
    if (!$n) return 'Netopia nu este configurată';
    $cheie = cheie_publica_netopia($n);
    if (!$cheie) return 'lipsește sau e greșită cheia publică Netopia (netopia_cheie_publica din config.php)';

    $parti = explode('.', trim((string) $token));
    if (count($parti) !== 3) return 'antetul Verification-token lipsește sau nu e un JWT';
    [$h64, $p64, $s64] = $parti;
    $antet = json_decode((string) base64url_decode($h64), true);
    $date  = json_decode((string) base64url_decode($p64), true);
    $semn  = base64url_decode($s64);
    if (!is_array($antet) || !is_array($date) || $semn === false) return 'JWT ilizibil';
    if (($antet['alg'] ?? '') !== 'RS512') return 'algoritm neașteptat: ' . ($antet['alg'] ?? '?');
    if (openssl_verify($h64 . '.' . $p64, $semn, $cheie, OPENSSL_ALGO_SHA512) !== 1) return 'semnătură invalidă';

    if (($date['iss'] ?? '') !== 'NETOPIA Payments') return 'emitent greșit';
    $aud = (array) ($date['aud'] ?? []);
    if (!in_array($n['pos'], $aud, true)) return 'mesajul e pentru alt punct de vânzare';
    $marja = 300;
    if (isset($date['exp']) && time() > (int) $date['exp'] + $marja) return 'token expirat';
    if (isset($date['nbf']) && time() + $marja < (int) $date['nbf']) return 'token încă nevalid';
    if (!hash_equals((string) ($date['sub'] ?? ''), base64_encode(hash('sha512', $corp, true)))) return 'conținutul mesajului nu corespunde semnăturii';
    return null;
}

/**
 * Procesează un mesaj IPN deja verificat.
 * Întoarce [răspuns pentru Netopia, funcție de rulat după răspuns (mailuri, factură) sau null].
 */
function proceseaza_ipn(PDO $pdo, array $mesaj, array $setari)
{
    $comanda = (string) ($mesaj['order']['orderID'] ?? '');
    $p = $mesaj['payment'] ?? [];
    $status = (int) ($p['status'] ?? 0);

    $st = $pdo->prepare("SELECT * FROM plati_card WHERE comanda = ?");
    $st->execute([$comanda]);
    $plata = $st->fetch();
    if (!$plata) {
        error_log("Netopia IPN: comanda necunoscută $comanda");
        return [['errorType' => 2, 'errorCode' => 404, 'errorMessage' => 'unknown order'], null];
    }

    $acum = date('Y-m-d H:i:s');
    $card = trim(($p['instrument']['panMasked'] ?? '') . ' ' . ($p['instrument']['issuer'] ?? ''));
    $nou = in_array($status, NETOPIA_PLATIT, true) ? 'platita'
         : (in_array($status, NETOPIA_RESPINS, true) ? 'respinsa'
         : (in_array($status, NETOPIA_ANULAT, true) ? 'anulata'
         : (in_array($status, NETOPIA_RAMBURSAT, true) ? 'rambursata' : 'in_curs')));
    $mesaj_text = mb_substr((string) ($p['message'] ?? ''), 0, 255);
    $ok = ['errorType' => 0, 'errorCode' => null, 'errorMessage' => ''];

    // O plată deja înregistrată ca plătită nu se mai procesează a doua oară (Netopia poate retrimite mesajul)
    if (in_array($plata['status'], ['platita', 'rambursata'], true) && $nou !== 'rambursata') {
        return [$ok, null];
    }

    // Suma: Netopia trimite suma în lei; dacă diferă de ce am cerut, nu confirmăm automat
    $suma_primita = isset($p['amount']) ? (float) $p['amount'] : (float) $plata['suma'];
    $suma_ok = abs($suma_primita - (int) $plata['suma']) < 0.01;

    // „AND status <> ...” face ca o plată să fie procesată o singură dată, chiar dacă două mesaje vin deodată
    $upd = $pdo->prepare("UPDATE plati_card SET status = ?, cod_netopia = ?, mesaj = ?, card = COALESCE(?, card), ntp_id = COALESCE(?, ntp_id), actualizat_la = ?
                          WHERE id = ? AND status <> ?");
    $upd->execute([$nou, $status, $mesaj_text, $card ?: null, ($p['ntpID'] ?? null) ?: null, $acum, $plata['id'],
                   $nou === 'rambursata' ? 'rambursata' : 'platita']);
    $schimbat = $upd->rowCount() === 1;

    if (!$schimbat || !in_array($nou, ['platita', 'rambursata'], true)) {
        return [$ok, null];
    }

    $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ?");
    $st->execute([$plata['rezervare_id']]);
    $r = $st->fetch();
    if (!$r) return [$ok, null];
    $plata['status'] = $nou;
    $plata['card'] = $card;

    if ($nou === 'rambursata') {
        return [$ok, function () use ($r, $plata, $setari) {
            trimite_mail_card_proprietar($r, $plata, $setari, 'rambursare');
        }];
    }

    if (!$suma_ok) {
        return [$ok, function () use ($r, $plata, $setari, $suma_primita) {
            trimite_mail_card_proprietar($r, $plata, $setari, 'suma', $suma_primita);
        }];
    }

    // Plata a reușit: confirmăm rezervarea (dacă zilele sunt încă libere)
    $situatie = 'confirmata';
    if ($r['status'] === 'confirmata') {
        $situatie = 'deja_confirmata';   // a plătit și prin transfer, de exemplu
    } elseif (perioada_ocupata($pdo, $r['sosire'], $r['plecare'], $r['id'])) {
        $situatie = 'ocupata';           // a plătit după expirarea termenului, iar zilele au fost luate între timp
    } else {
        $pdo->prepare("UPDATE rezervari SET status = 'confirmata', platit_la = ?, modificat_la = ?, plata_metoda = 'card', nota = ? WHERE id = ?")
            ->execute([$acum, $acum, 'Plătită cu cardul (Netopia ' . ($p['ntpID'] ?? '') . ')', $r['id']]);
    }

    return [$ok, function () use ($pdo, $r, $plata, $setari, $situatie) {
        $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ?");
        $st->execute([$r['id']]);
        $r = $st->fetch();
        if ($situatie === 'confirmata') {
            emite_factura_plata($pdo, $r, (int) $plata['suma'], $setari);   // nu face nimic dacă FGO nu e configurat
            trimite_mail_client($r, 'confirmare', $setari);
        }
        if ($situatie !== 'confirmata' || ($setari['notifica_plata_card'] ?? true)) {
            trimite_mail_card_proprietar($r, $plata, $setari, $situatie);
        }
    }];
}

/** Mail către proprietar despre o plată cu cardul (sau o situație care cere atenție). */
function trimite_mail_card_proprietar(array $r, array $plata, array $setari, $situatie, $suma_primita = null)
{
    $T = texte_limba('ro');
    $E = $T['mailuri'];
    $v = valori_mail($r, $setari, 'ro', $T);
    $suma = number_format((int) $plata['suma'], 0, ',', '.') . ' lei';
    $texte = [
        'confirmata'      => ['confirmata', 'Plătită cu cardul', "Plata cu cardul de $suma pentru {$v['{nr}']} a reușit. Rezervarea s-a confirmat automat și clientul a primit mail de confirmare."],
        'deja_confirmata' => ['asteptare', 'Plată în plus', "Am primit $suma cu cardul pentru {$v['{nr}']}, dar rezervarea era deja confirmată (poate a plătit și prin transfer). Verifică și, dacă e cazul, rambursează diferența din contul Netopia."],
        'ocupata'         => ['anulata', 'Atenție: perioadă ocupată', "{$r['nume']} a plătit $suma cu cardul pentru {$v['{nr}']}, dar între timp perioada a fost ocupată de altă rezervare, așa că NU am confirmat-o. Sună clientul și rambursează plata din contul Netopia sau propune alte date."],
        'suma'            => ['asteptare', 'Sumă diferită', "Netopia a raportat o plată de " . ($suma_primita !== null ? number_format($suma_primita, 2, ',', '.') : '?') . " lei pentru {$v['{nr}']}, dar ceream $suma. Nu am confirmat automat: verifică tranzacția în contul Netopia, apoi confirmă din panou."],
        'rambursare'      => ['anulata', 'Plată rambursată', "Plata cu cardul de $suma pentru {$v['{nr}']} a fost rambursată în Netopia. Rezervarea NU s-a schimbat automat: anuleaz-o din panou dacă e cazul."],
    ];
    [$status, $eticheta, $intro] = $texte[$situatie];
    $html = sablon_mail([
        'limba' => 'ro', 'preheader' => $intro,
        'titlu' => $eticheta . ' #' . $r['id'],
        'intro' => $intro . ($plata['card'] ? ' Card: ' . $plata['card'] . '.' : '') . ' Comanda Netopia: ' . $plata['comanda'] . '.',
        'status' => [$status, $eticheta],
        'sectiuni' => [sectiune_detalii($r, $v, $E, $r['status'] !== 'confirmata')],
        'butoane' => [['Deschide panoul', SITE_URL . '/admin/?vezi=' . ($r['status'] === 'confirmata' ? 'confirmate' : 'noi'), 'plin']],
        'E' => $E, 'T' => $T, 'v' => $v, 'setari' => $setari,
    ]);
    return trimite_mail_html($setari['email_proprietar'], "$eticheta #{$r['id']} – {$v['{perioada}']} – {$r['nume']}", $html, $setari, $r['email']);
}
