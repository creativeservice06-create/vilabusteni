<?php
// Se include la începutul fiecărei pagini.
// Încarcă setările și câteva funcții mici folosite peste tot.

$setari = require __DIR__ . '/setari.php';

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

/** Formatează un preț: 4500 → "4.500 lei" */
function lei($suma)
{
    return number_format((int) $suma, 0, ',', '.') . ' lei';
}

/** Token anti-falsificare pentru formulare. */
function token_formular()
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['token'];
}
