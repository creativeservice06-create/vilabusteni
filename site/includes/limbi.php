<?php
// ============================================================
//  LIMBILE SITE-ULUI
//  Textele fiecărei limbi stau în folderul lang/ (ro.php, en.php ...).
//  Adresele: vilabusteni.ro/ (română), vilabusteni.ro/en/, /de/, /it/, /ru/
//  (.htaccess transformă /en/rezervare.php în rezervare.php?lang=en)
//
//  Pentru a schimba un text: îl cauți în lang/ro.php (și în celelalte limbi).
//  Pentru o limbă nouă: copiezi lang/en.php, îl traduci și o adaugi în LIMBI.
// ============================================================

const LIMBI = [
    'ro' => 'Română',
    'en' => 'English',
    'de' => 'Deutsch',
    'it' => 'Italiano',
    'ru' => 'Русский',
];

$LIMBA = (isset($_GET['lang']) && isset(LIMBI[$_GET['lang']])) ? $_GET['lang'] : 'ro';
$TEXTE = require __DIR__ . '/../lang/' . $LIMBA . '.php';
if ($LIMBA !== 'ro') {
    // ce lipsește dintr-o traducere se afișează în română
    $TEXTE += require __DIR__ . '/../lang/ro.php';
}

/** Textul pentru o cheie, în limba curentă. Cu parametri: t('cheie', 3, 'ceva') – ca sprintf. */
function t($cheie, ...$valori)
{
    global $TEXTE;
    $text = $TEXTE[$cheie] ?? $cheie;
    return $valori ? vsprintf($text, $valori) : $text;
}

/** Adresa unei pagini în limba curentă: url('/rezervare.php') → /en/rezervare.php */
function url($cale = '/', $limba = null)
{
    global $LIMBA;
    $limba = $limba ?? $LIMBA;
    return $limba === 'ro' ? $cale : '/' . $limba . $cale;
}

/** Pagina curentă (fără prefixul de limbă), pentru selectorul de limbă. */
function pagina_curenta()
{
    $cale = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $cale = preg_replace('#^/(' . implode('|', array_keys(LIMBI)) . ')(?=/|$)#', '', $cale);
    return $cale === '' ? '/' : $cale;
}
