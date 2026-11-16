<?php
// Partea de sus, comună tuturor paginilor (<head> + meniu).
// Fiecare pagină setează $titlu (și opțional $descriere) înainte de include.
$titlu     = $titlu ?? $setari['nume_vila'];
$descriere = $descriere ?? 'Vila Rândunica din Poiana Țapului, Bușteni – casă din 1925 cu vedere spre Bucegi. 3 camere, 3 băi, mansardă. Rezervă online.';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titlu) ?></title>
  <meta name="description" content="<?= e($descriere) ?>">
  <meta property="og:title" content="<?= e($titlu) ?>">
  <meta property="og:description" content="<?= e($descriere) ?>">
  <meta property="og:image" content="/assets/images/terasa-vedere.jpg">
  <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>

<a href="#continut" class="sari">Sari la conținut</a>

<header class="antet">
  <a href="/" class="logo">
    <span class="logo-nume"><?= e($setari['nume_vila']) ?></span>
    <span class="logo-zona"><?= e($setari['zona']) ?></span>
  </a>
  <button class="meniu-buton" aria-label="Deschide meniul" aria-expanded="false">☰</button>
  <nav class="meniu">
    <a href="/#poveste">Povestea</a>
    <a href="/#cazare">Cazare</a>
    <a href="/#galerie">Galerie</a>
    <a href="/#locul">Locul</a>
    <a href="/rezervare.php" class="buton buton-mic">Rezervă</a>
  </nav>
</header>

<main id="continut">
