<?php
// ============================================================
//  FACTURI FISCALE AUTOMATE PRIN FGO (API v2)
//
//  După fiecare plată primită (card sau transfer confirmat în panou) se emite în FGO
//  o factură pe numele clientului, pentru suma încasată:
//    'integral' – a plătit tot sejurul dintr-o dată
//    'avans'    – a plătit doar o parte
//    'rest'     – diferența, încasată la sosire (o emiți din panou: „Rest încasat – emite factura”)
//  FGO o trimite apoi automat în e-Factura (SPV), după setările din contul FGO.
//
//  Ce trebuie completat:
//   - config.php (pe server):  'fgo_cheie_api' => 'fgo_api_v2....'
//       (FGO → cheie API pentru firma Horoscop Travel, cu dreptul „facturi:emitere”)
//   - setari.php:  'fgo_serie' și 'fgo_cota_tva'
//   - în FGO → Setări eCommerce → API: adresa https://vilabusteni.ro
// ============================================================
require_once __DIR__ . '/tari.php';

const FGO_URL = 'https://api.fgo.ro/v2';

/** Datele de acces FGO, sau null dacă facturarea automată nu e configurată. */
function config_fgo(array $setari)
{
    $c = config_site();
    $cheie = trim((string) ($c['fgo_cheie_api'] ?? ''));
    if ($cheie === '' || trim((string) ($setari['fgo_serie'] ?? '')) === '' || !isset($setari['fgo_cota_tva'])) {
        return null;
    }
    return [
        'cheie' => $cheie,
        'cui'   => preg_replace('/\D/', '', $setari['firma_cui']),
        'url'   => rtrim($c['fgo_url_test'] ?? FGO_URL, '/'),
    ];
}

function fgo_activ(array $setari)
{
    return config_fgo($setari) !== null;
}

/** Facturile unei rezervări (cele reușite și cele cu eroare). */
function facturi_rezervare(PDO $pdo, $id)
{
    $st = $pdo->prepare("SELECT * FROM facturi WHERE rezervare_id = ? ORDER BY id");
    $st->execute([(int) $id]);
    return $st->fetchAll();
}

/** Cât s-a facturat deja (doar facturile emise cu succes). */
function suma_facturata(PDO $pdo, $id)
{
    $st = $pdo->prepare("SELECT COALESCE(SUM(suma), 0) FROM facturi WHERE rezervare_id = ? AND numar IS NOT NULL");
    $st->execute([(int) $id]);
    return (int) $st->fetchColumn();
}

/** Prima factură reușită a rezervării, cu link (pentru mailul de confirmare). */
function factura_cu_link(PDO $pdo, $id)
{
    $st = $pdo->prepare("SELECT * FROM facturi WHERE rezervare_id = ? AND numar IS NOT NULL ORDER BY id DESC LIMIT 1");
    $st->execute([(int) $id]);
    return $st->fetch() ?: null;
}

/**
 * Emite factura pentru o plată tocmai primită (avans sau integral, după caz).
 * Nu face nimic dacă FGO nu e configurat. Întoarce [true/false/null, mesaj].
 */
function emite_factura_plata(PDO $pdo, array $r, $suma, array $setari)
{
    if (!fgo_activ($setari) || (int) $suma <= 0) return [null, ''];
    $facturat = suma_facturata($pdo, $r['id']);
    if ($facturat > 0) {
        $tip = 'rest';
    } else {
        $tip = (int) $suma >= (int) $r['total'] ? 'integral' : 'avans';
    }
    return emite_factura($pdo, $r, $tip, (int) $suma, $setari);
}

/** Textul de pe factură, în română. */
function descriere_factura(array $r, $tip, array $setari)
{
    $perioada = date('d.m.Y', strtotime($r['sosire'])) . ' – ' . date('d.m.Y', strtotime($r['plecare']));
    $nopti = (int) $r['nopti'] . ((int) $r['nopti'] === 1 ? ' noapte' : ' nopți');
    $baza = 'cazare ' . $setari['nume_vila'] . ' (închiriere integrală), ' . $perioada . ', ' . $nopti
          . ', rezervarea ' . numar_proforma($r['id'], $setari);
    return [
        'integral' => 'Servicii de ' . $baza,
        'avans'    => 'Avans pentru ' . $baza,
        'rest'     => 'Diferență (după avans) pentru ' . $baza,
    ][$tip];
}

/**
 * Emite în FGO factura de tipul $tip pentru suma $suma.
 * Dacă există deja una reușită de același tip, nu mai emite alta.
 * Întoarce [true/false, mesaj pentru panou].
 */
function emite_factura(PDO $pdo, array $r, $tip, $suma, array $setari)
{
    $c = config_fgo($setari);
    if (!$c) return [false, 'Facturarea FGO nu este configurată (cheia API, seria sau cota TVA lipsesc).'];

    $st = $pdo->prepare("SELECT * FROM facturi WHERE rezervare_id = ? AND tip = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$r['id'], $tip]);
    $existenta = $st->fetch();
    if ($existenta && $existenta['numar'] !== null) {
        return [true, "Factura {$existenta['serie']} {$existenta['numar']} era deja emisă."];
    }
    $acum = date('Y-m-d H:i:s');
    if ($existenta) {
        $id_factura = (int) $existenta['id'];
        $pdo->prepare("UPDATE facturi SET suma = ?, eroare = NULL, creat_la = ? WHERE id = ?")->execute([$suma, $acum, $id_factura]);
    } else {
        $pdo->prepare("INSERT INTO facturi (rezervare_id, tip, suma, creat_la) VALUES (?, ?, ?, ?)")->execute([$r['id'], $tip, $suma, $acum]);
        $id_factura = (int) $pdo->lastInsertId();
    }

    // Clientul: firmă (PJ) sau persoană fizică (PF)
    $pj = ($r['fact_tip'] ?? 'PF') === 'PJ' && trim((string) $r['fact_cui']) !== '';
    $tara = $r['fact_tara'] ?: 'RO';
    $client = array_filter([
        'tip'        => $pj ? 'PJ' : 'PF',
        'denumire'   => $pj ? $r['fact_firma'] : $r['nume'],
        'cod_unic'   => $pj ? strtoupper(preg_replace('/\s+/', '', $r['fact_cui'])) : null,
        'email'      => $r['email'],
        'telefon'    => $r['telefon'],
        'tara'       => TARI[$tara][1] ?? 'Romania',
        'cod_judet'  => $tara === 'RO' ? ($r['fact_judet'] ?: null) : null,
        'localitate' => $r['fact_localitate'] ?: null,
        'adresa'     => $r['fact_adresa'] ?: null,
    ], function ($x) { return $x !== null && $x !== ''; });

    $cerere = [
        'serie'               => $setari['fgo_serie'],
        'valuta'              => 'RON',
        'tip_factura'         => $setari['fgo_tip_factura'] ?: 'F',
        'id_extern'           => 'rez' . (int) $r['id'] . '-' . $tip,
        'verificare_duplicat' => true,
        'client'              => $client,
        'articole'            => [[
            'denumire'   => descriere_factura($r, $tip, $setari),
            'um'         => $setari['fgo_um'] ?: 'buc',
            'nr_produse' => 1,
            'pret_total' => (int) $suma,   // prețul include TVA (dacă firma e plătitoare)
            'cota_tva'   => (float) $setari['fgo_cota_tva'],
        ]],
    ];

    $antete = [
        'Authorization: Bearer ' . $c['cheie'],
        'Fgo-Cod-Unic: ' . $c['cui'],
        'Fgo-Url-Platforma: ' . SITE_URL,
    ];
    [$cod, $json, $text, $eroare_retea] = cerere_json('POST', $c['url'] . '/facturi', $antete, $cerere, 30);

    $serie = $numar = null;
    if ($cod >= 200 && $cod < 300 && !empty($json['data']['numar'])) {
        $serie = (string) ($json['data']['serie'] ?? $setari['fgo_serie']);
        $numar = (string) $json['data']['numar'];
    } elseif ($cod === 400 && ($json['motiv']['cod'] ?? '') === 'factura_duplicata') {
        // a mai fost emisă (ex. un răspuns pierdut): o folosim pe aceea
        $serie = (string) ($json['motiv']['args']['serie'] ?? $setari['fgo_serie']);
        $numar = (string) ($json['motiv']['args']['numar'] ?? '');
        if ($numar === '') $serie = null;
    }

    if ($numar === null || $serie === null) {
        $motiv = $eroare_retea ?: fgo_motiv_eroare($cod, $json, $text);
        $pdo->prepare("UPDATE facturi SET eroare = ? WHERE id = ?")->execute([mb_substr($motiv, 0, 500), $id_factura]);
        error_log("FGO: factura pentru rezervarea #{$r['id']} nu a fost emisă: $motiv");
        return [false, "Factura nu a putut fi emisă în FGO: $motiv"];
    }

    $link = fgo_link($c, $serie, $numar);
    $pdo->prepare("UPDATE facturi SET serie = ?, numar = ?, link = ?, eroare = NULL WHERE id = ?")
        ->execute([$serie, $numar, $link, $id_factura]);
    return [true, "Factura $serie $numar a fost emisă în FGO."];
}

/** Linkul de descărcare (PDF) al unei facturi; null dacă FGO nu îl dă. */
function fgo_link(array $c, $serie, $numar)
{
    $antete = ['Authorization: Bearer ' . $c['cheie'], 'Fgo-Cod-Unic: ' . $c['cui']];
    [$cod, $json] = cerere_json('POST', $c['url'] . '/facturi/' . rawurlencode($serie) . '/' . rawurlencode($numar) . '/genereaza-link-de-descarcare', $antete, new stdClass(), 20);
    if ($cod < 200 || $cod >= 300 || !is_array($json)) return null;
    $gasit = null;
    array_walk_recursive($json, function ($val) use (&$gasit) {
        if ($gasit === null && is_string($val) && preg_match('#^https?://#', $val)) $gasit = $val;
    });
    return $gasit ? mb_substr($gasit, 0, 500) : null;
}

/** Explicația unei erori FGO, pe scurt, pentru panou. */
function fgo_motiv_eroare($cod, $json, $text)
{
    if (!is_array($json)) return "HTTP $cod" . ($text !== '' ? ': ' . mb_substr(strip_tags($text), 0, 150) : '');
    $motiv = $json['detail'] ?? $json['title'] ?? '';
    if (!empty($json['motiv']['mesaj'])) $motiv .= ' (' . $json['motiv']['mesaj'] . ')';
    foreach ($json['validation_errors'] ?? [] as $v) {
        $camp = $v['pointer'] ?? $v['name'] ?? '';
        foreach ($v['issues'] ?? [] as $i) {
            $motiv .= ' · ' . trim($camp . ' ' . ($i['message'] ?? $i['code'] ?? ''));
        }
    }
    $sfaturi = [401 => ' Verifică fgo_cheie_api din config.php.', 403 => ' Cheia nu are dreptul de emitere, CUI-ul nu corespunde sau abonamentul FGO nu permite.'];
    return trim("HTTP $cod: $motiv") . ($sfaturi[$cod] ?? '');
}
