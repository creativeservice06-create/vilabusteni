<?php
// Partea de sus, comună tuturor paginilor (<head> + meniu + selectorul de limbă).
// Fiecare pagină setează înainte de include:
//   $titlu      – titlul din tab-ul browserului
//   $descriere  – (opțional) textul pentru Google
//   $pagina     – (opțional) "acasa" face meniul transparent peste poza de sus
$titlu     = $titlu ?? $setari['nume_vila'];
$descriere = $descriere ?? t('meta_descriere');
$pagina    = $pagina ?? '';
$aici      = pagina_curenta();
$domeniu   = 'https://vilabusteni.ro';
?>
<!DOCTYPE html>
<html lang="<?= e($LIMBA) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#164a28">
  <title><?= e($titlu) ?></title>
  <meta name="description" content="<?= e($descriere) ?>">
  <meta property="og:title" content="<?= e($titlu) ?>">
  <meta property="og:description" content="<?= e($descriere) ?>">
  <!-- Previzualizarea când linkul e trimis pe WhatsApp, Facebook etc.: sigla vilei -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e($setari['nume_vila']) ?>">
  <meta property="og:url" content="<?= e($domeniu . url($aici)) ?>">
  <meta property="og:locale" content="<?= e(['ro' => 'ro_RO', 'en' => 'en_GB', 'de' => 'de_DE', 'it' => 'it_IT', 'ru' => 'ru_RU'][$LIMBA]) ?>">
  <meta property="og:image" content="<?= e($domeniu) ?>/assets/images/sigle/previzualizare.jpg">
  <meta property="og:image:secure_url" content="<?= e($domeniu) ?>/assets/images/sigle/previzualizare.jpg">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="600">
  <meta property="og:image:height" content="600">
  <meta property="og:image:alt" content="<?= e(t('sigla_alt')) ?>">
  <meta name="twitter:card" content="summary">
  <link rel="apple-touch-icon" href="/assets/images/sigle/previzualizare.jpg">
  <?php foreach (LIMBI as $cod => $_): ?>
  <link rel="alternate" hreflang="<?= e($cod) ?>" href="<?= e($domeniu . url($aici, $cod)) ?>">
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= e($domeniu . $aici) ?>">
  <link rel="icon" type="image/png" href="/assets/images/sigle/randunica-verde.png">
  <link rel="preload" href="/assets/fonts/young-serif.woff" as="font" type="font/woff" crossorigin>
  <link rel="preload" href="/assets/fonts/instrument-sans.woff" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="/assets/css/style.css?v=7">
</head>
<body class="pagina-<?= e($pagina ?: 'interioara') ?>">

<a href="#continut" class="sari"><?= e(t('sari')) ?></a>

<header class="antet" id="antet">
  <a href="<?= e(url('/')) ?>" class="logo" aria-label="<?= e($setari['nume_vila']) ?> – <?= e(t('acasa_aria')) ?>">
    <img class="logo-pasare logo-pasare-verde" src="/assets/images/sigle/randunica-verde.png" alt="" width="46" height="42">
    <img class="logo-pasare logo-pasare-alba" src="/assets/images/sigle/randunica-alb.png" alt="" width="46" height="42">
    <span class="logo-text">
      <span class="logo-nume"><?= e($setari['nume_vila']) ?></span>
      <span class="logo-zona"><?= e($setari['zona']) ?></span>
    </span>
  </a>

  <div class="antet-dreapta">
    <nav class="meniu" id="meniu">
      <a href="<?= e(url('/')) ?>#povestea"><?= e(t('meniu_povestea')) ?></a>
      <a href="<?= e(url('/')) ?>#casa"><?= e(t('meniu_casa')) ?></a>
      <a href="<?= e(url('/')) ?>#galerie"><?= e(t('meniu_galerie')) ?></a>
      <a href="<?= e(url('/')) ?>#zona"><?= e(t('meniu_zona')) ?></a>
      <a href="#contact"><?= e(t('meniu_contact')) ?></a>
      <a href="<?= e(url('/rezervare.php')) ?>" class="meniu-rezerva"><?= e(t('meniu_rezerva')) ?></a>
    </nav>

    <!-- Selectorul de limbă: un click pe codul limbii deschide lista -->
    <details class="limbi">
      <summary aria-label="<?= e(t('limba')) ?>: <?= e(LIMBI[$LIMBA]) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/></svg>
        <span><?= e(strtoupper($LIMBA)) ?></span>
      </summary>
      <ul>
        <?php foreach (LIMBI as $cod => $nume): ?>
          <li><a href="<?= e(url($aici, $cod)) ?>" hreflang="<?= e($cod) ?>" lang="<?= e($cod) ?>"<?= $cod === $LIMBA ? ' aria-current="true"' : '' ?>><?= e($nume) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </details>

    <button class="meniu-buton" aria-label="<?= e(t('meniu')) ?>" aria-expanded="false" aria-controls="meniu">
      <span></span><span></span>
    </button>
  </div>
</header>

<main id="continut">
