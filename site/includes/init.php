<?php
// Se include la începutul fiecărei pagini.
// Încarcă setările și câteva funcții mici folosite peste tot.

$setari = require __DIR__ . '/setari.php';

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
