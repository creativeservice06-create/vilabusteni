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
  <p>Dacă vom folosi instrumente de măsurare sau publicitate (de ex. Google Ads, Meta/Facebook), acestea vor fi
     activate doar după ce îți dai acordul în bannerul de cookie-uri, iar această pagină va fi actualizată.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
