<?php
// Partea de sus, comună tuturor paginilor (<head> + meniu).
// Fiecare pagină setează înainte de include:
//   $titlu      – titlul din tab-ul browserului
//   $descriere  – (opțional) textul pentru Google
//   $pagina     – (opțional) "acasa" face meniul transparent peste poza de sus
$titlu     = $titlu ?? $setari['nume_vila'];
$descriere = $descriere ?? 'Vila Rândunica din Poiana Țapului, Bușteni: casă din 1925 cu terasă spre Bucegi. 3 dormitoare, 3 băi, mansardă. Se închiriază integral.';
$pagina    = $pagina ?? '';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#1f3a33">
  <title><?= e($titlu) ?></title>
  <meta name="description" content="<?= e($descriere) ?>">
  <meta property="og:title" content="<?= e($titlu) ?>">
  <meta property="og:description" content="<?= e($descriere) ?>">
  <meta property="og:image" content="/assets/images/terasa-vedere.jpg">
  <link rel="icon" type="image/png" href="/assets/images/sigle/randunica-verde.png">
  <link rel="preload" href="/assets/fonts/young-serif.woff" as="font" type="font/woff" crossorigin>
  <link rel="preload" href="/assets/fonts/instrument-sans.woff" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="/assets/css/style.css?v=6">
</head>
<body class="pagina-<?= e($pagina ?: 'interioara') ?>">

<a href="#continut" class="sari">Sari la conținut</a>

<header class="antet" id="antet">
  <a href="/" class="logo" aria-label="<?= e($setari['nume_vila']) ?> – prima pagină">
    <img class="logo-pasare logo-pasare-verde" src="/assets/images/sigle/randunica-verde.png" alt="" width="46" height="42">
    <img class="logo-pasare logo-pasare-alba" src="/assets/images/sigle/randunica-alb.png" alt="" width="46" height="42">
    <span class="logo-text">
      <span class="logo-nume"><?= e($setari['nume_vila']) ?></span>
      <span class="logo-zona"><?= e($setari['zona']) ?></span>
    </span>
  </a>
  <button class="meniu-buton" aria-label="Meniu" aria-expanded="false" aria-controls="meniu">
    <span></span><span></span>
  </button>
  <nav class="meniu" id="meniu">
    <a href="/#povestea">Povestea</a>
    <a href="/#casa">Casa</a>
    <a href="/#galerie">Galerie</a>
    <a href="/#zona">Zona</a>
    <a href="#contact">Contact</a>
    <a href="/rezervare.php" class="meniu-rezerva">Rezervă</a>
  </nav>
</header>

<main id="continut">
