<?php
// Se include la începutul fiecărei pagini.
// Încarcă setările și câteva funcții mici folosite peste tot.

date_default_timezone_set('Europe/Bucharest');
$setari = require __DIR__ . '/setari.php';
require_once __DIR__ . '/limbi.php';
require_once __DIR__ . '/automat.php';
if (PHP_SAPI !== 'cli') {
    porneste_verificarea_automata();   // anulează automat cererile neplătite în termen
}

// Linia crestei Bucegilor (Caraiman – Coștila), desenată ca o singură linie.
// Apare sus pe prima pagină și ca separator. Puncte "x,y" pe o suprafață de 1200×120.
const CREASTA = '0,112 70,96 130,62 178,24 214,38 252,33 300,66 348,54 392,84 436,76 478,50 528,14 568,30 610,24 650,62 700,86 744,72 790,36 830,20 862,30 912,26 962,56 1018,82 1070,78 1126,100 1200,110';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Afișează text în siguranță în HTML (evită caractere periculoase). */
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/** Formatează un preț în limba curentă: 4500 → "4.500 lei" / "4,500 lei" / "4 500 лей" */
function lei($suma)
{
    global $LIMBA;
    $separator = ['en' => ',', 'ru' => ' '][$LIMBA] ?? '.';
    return number_format((int) $suma, 0, ',', $separator) . ' ' . t('moneda');
}

/** Telefonul afișat: format românesc pe site-ul în română, cu +40 în celelalte limbi. */
function telefon()
{
    global $setari, $LIMBA;
    return $LIMBA === 'ro' ? $setari['telefon'] : $setari['telefon_int'];
}

/** Token anti-falsificare pentru formulare. */
function token_formular()
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['token'];
}

/**
 * Iconițele rețelelor sociale (doar cele care au link în setari.php).
 * Desenele sunt simplificate, în culoarea textului din jur.
 */
function iconite_sociale(array $setari)
{
    $iconite = [
        'facebook'    => ['Facebook',    '<path fill="currentColor" d="M13.5 21v-7.6h2.6l.4-3h-3V8.5c0-.9.3-1.5 1.5-1.5h1.6V4.3c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.8v3h2.6V21z"/>'],
        'instagram'   => ['Instagram',   '<g fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/></g><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/>'],
        'tiktok'      => ['TikTok',      '<path fill="currentColor" d="M16.2 3c.4 2.3 1.8 3.8 4.2 4v3.1c-1.5 0-2.9-.4-4.1-1.2v6a5.9 5.9 0 1 1-5.9-5.9h.6v3.2a2.7 2.7 0 1 0 2.1 2.7V3z"/>'],
        'youtube'     => ['YouTube',     '<path fill="currentColor" d="M21.6 7.6a2.6 2.6 0 0 0-1.8-1.8C18.2 5.4 12 5.4 12 5.4s-6.2 0-7.8.4a2.6 2.6 0 0 0-1.8 1.8A27 27 0 0 0 2 12a27 27 0 0 0 .4 4.4 2.6 2.6 0 0 0 1.8 1.8c1.6.4 7.8.4 7.8.4s6.2 0 7.8-.4a2.6 2.6 0 0 0 1.8-1.8A27 27 0 0 0 22 12a27 27 0 0 0-.4-4.4zM10 15V9l5.2 3z"/>'],
        'tripadvisor' => ['Tripadvisor', '<g fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="7" cy="13" r="3.6"/><circle cx="17" cy="13" r="3.6"/><path d="M3 8.6c2.5-1.7 5.6-2.6 9-2.6s6.5.9 9 2.6M12 6.6v3.2M10 17.2l2 2.3 2-2.3"/></g><circle cx="7" cy="13" r="1.2" fill="currentColor"/><circle cx="17" cy="13" r="1.2" fill="currentColor"/>'],
    ];
    $html = '';
    foreach ($iconite as $cheie => [$nume, $desen]) {
        $link = $setari['social'][$cheie] ?? '';
        if ($link === '') continue;
        $html .= '<a href="' . e($link) . '" target="_blank" rel="noopener" aria-label="' . e($nume) . '" title="' . e($nume) . '">'
               . '<svg viewBox="0 0 24 24" aria-hidden="true">' . $desen . '</svg></a>';
    }
    return $html ? '<div class="sociale">' . $html . '</div>' : '';
}
