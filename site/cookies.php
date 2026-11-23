<?php
// POLITICA DE COOKIE-URI. Se actualizează când adăugăm Google Ads / Facebook Pixel.
require __DIR__ . '/includes/init.php';
$titlu = t('cookies') . ' – ' . $setari['nume_vila'];
require __DIR__ . '/includes/header.php';
?>
<section class="interior legal" lang="ro">
  <h1><?= e(t('cookies')) ?></h1>
  <?php if (t('legal_doar_ro')): ?><p class="nota-limba" lang="<?= e($LIMBA) ?>"><?= e(t('legal_doar_ro')) ?></p><?php endif; ?>

  <h2>Ce cookie-uri folosim acum</h2>
  <p>Momentan folosim un singur cookie strict necesar, <code>PHPSESSID</code>, care protejează formularul de rezervare
     împotriva trimiterilor false. Se șterge când închizi browserul și nu necesită consimțământ.</p>

  <h2>Cookie-uri de marketing</h2>
  <p>Folosim pixelul Meta (Facebook), care măsoară vizitele pe site și ne ajută să afișăm reclame relevante.
     Pixelul pornește doar dacă apeși „Accept” în bannerul de cookie-uri. Poți schimba decizia oricând, ștergând
     datele site-ului din browser. Cookie-urile pixelului sunt, de exemplu, <code>_fbp</code> și <code>_fbc</code>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
