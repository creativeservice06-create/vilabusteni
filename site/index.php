<?php
// PAGINA PRINCIPALĂ
// Textele sunt în lang/ro.php (și în celelalte limbi). Telefonul, prețul etc. vin din includes/setari.php.
// Aici se schimbă doar ordinea secțiunilor și pozele.
require __DIR__ . '/includes/init.php';
$titlu  = t('meta_titlu');
$pagina = 'acasa';
require __DIR__ . '/includes/header.php';

// Pozele spațiilor (textele lor: 'spatii' în lang/*.php, în aceeași ordine)
$poze_spatii = ['terasa-vedere.jpg', 'living.jpg', 'sufragerie.jpg', 'dormitor-1.jpg', 'bucatarie.jpg'];

// Iconițele dotărilor (textele: 'dotari' în lang/*.php, în aceeași ordine)
$iconite_dotari = [
    '<path d="M3 21V9l9-6 9 6v12h-6v-7H9v7z"/>',
    '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
    '<path d="M12 22c4 0 7-3 7-7 0-4-3-6-4-10-1 3-3 4-3 4S11 5 9 2C9 7 5 10 5 15c0 4 3 7 7 7z"/>',
    '<path d="M4 20h16M6 20V9h12v11M9 9V5h6v4"/>',
    '<path d="M5 20h14M7 20V4h10v16M10 8h4M10 12h4M10 16h4"/>',
    '<path d="m2 18 6-9 4 5 3-3 7 7z"/>',
];

// Pozele din galerie (descrierile: 'galerie' în lang/*.php, în aceeași ordine)
$poze_galerie = ['munti-panorama.jpg', 'fatada.jpg', 'terasa-2.jpg', 'living.jpg', 'sufragerie.jpg',
                 'dormitor-2.jpg', 'dormitor-1.jpg', 'bucatarie.jpg', 'munti-bucegi.jpg'];

// Ce se caută pe Google Maps pentru fiecare obiectiv (textele: 'obiective' în lang/*.php)
$harta_obiective = ['Cascada Urlătoarea Bușteni', 'Castelul Cantacuzino Bușteni', 'Mănăstirea Caraiman Bușteni',
                    'Telecabina Bușteni', 'Babele Bucegi', 'Vârful Omu Bucegi'];
?>

<!-- SUS: poza mare, cu creasta desenată peste marginea de jos -->
<section class="hero">
  <img class="hero-poza" src="/assets/images/munti-panorama.jpg" alt="<?= e(t('hero_alt')) ?>" fetchpriority="high">
  <div class="hero-text">
    <h1><?= e($setari['nume_vila']) ?></h1>
    <p class="hero-motto"><?= e(t('hero_motto')) ?></p>
    <div class="hero-butoane">
      <a href="<?= e(url('/rezervare.php')) ?>" class="buton buton-plin"><?= e(t('buton_date')) ?></a>
      <a href="tel:<?= e($setari['telefon_link']) ?>" class="buton buton-sticla"><?= e(t('buton_suna', telefon())) ?></a>
    </div>
  </div>
  <svg class="creasta creasta-hero" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
    <polygon points="<?= e(CREASTA) ?> 1200,120 0,120" />
    <polyline points="<?= e(CREASTA) ?>" pathLength="1" />
  </svg>
</section>

<!-- Pe scurt -->
<section class="pe-scurt" aria-label="<?= e(t('pe_scurt')) ?>">
  <ul>
    <li><strong><?= e(t('scurt_1_titlu')) ?></strong> <?= e(t('scurt_1_text')) ?></li>
    <li><strong><?= e(t('scurt_2_titlu')) ?></strong> <?= e(t('scurt_2_text')) ?></li>
    <li><strong><?= e(lei($setari['pret_noapte'])) ?></strong> <?= e(t('scurt_3_text', $setari['minim_nopti'])) ?></li>
  </ul>
</section>

<!-- POVESTEA -->
<section id="povestea" class="povestea">
  <div class="povestea-an" aria-hidden="true">1925</div>
  <div class="povestea-text">
    <h2><?= e(t('povestea_titlu')) ?></h2>
    <p class="introducere"><?= e(t('povestea_intro')) ?></p>
    <ol class="cronologie">
      <?php foreach (t('cronologie') as [$cand, $text]): ?>
        <li>
          <span class="cronologie-cand"><?= e($cand) ?></span>
          <p><?= e($text) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- CASA -->
<section id="casa" class="casa">
  <div class="casa-cap">
    <h2><?= e(t('casa_titlu')) ?></h2>
    <p><?= e(t('casa_text')) ?></p>
  </div>

  <div class="spatii">
    <?php foreach (t('spatii') as $i => [$nume, $text]): ?>
      <figure class="spatiu spatiu-<?= $i + 1 ?>">
        <img src="/assets/images/<?= $i === 0 ? '' : 'mici/' ?><?= e($poze_spatii[$i]) ?>" alt="<?= e($nume) ?>" loading="lazy">
        <figcaption><strong><?= e($nume) ?></strong> <?= e($text) ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>

  <ul class="dotari">
    <?php foreach (t('dotari') as $i => $text): ?>
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><?= $iconite_dotari[$i] ?></svg><?= e($text) ?></li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- PREȚ -->
<section class="pret">
  <svg class="creasta creasta-pret" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
    <polygon points="<?= e(CREASTA) ?> 1200,0 0,0" />
  </svg>
  <div class="pret-interior">
    <h2><?= e(t('pret_titlu', lei($setari['pret_noapte']))) ?></h2>
    <p><?= e(t('pret_text', $setari['minim_nopti'], lei($setari['pret_noapte'] * $setari['minim_nopti']), $setari['ora_check_in'], $setari['ora_check_out'])) ?></p>
    <div class="pret-butoane">
      <a href="<?= e(url('/rezervare.php')) ?>" class="buton buton-plin"><?= e(t('buton_alege')) ?></a>
      <a href="https://wa.me/<?= e($setari['whatsapp']) ?>" class="buton buton-sticla" target="_blank" rel="noopener"><?= e(t('buton_intreaba')) ?></a>
    </div>
  </div>
</section>

<!-- GALERIE: pe telefon se derulează cu degetul, pe calculator e grilă -->
<section id="galerie" class="galerie-sectiune">
  <div class="galerie-cap">
    <h2><?= e(t('galerie_titlu')) ?></h2>
    <p class="galerie-indiciu"><?= e(t('galerie_indiciu')) ?></p>
  </div>
  <div class="galerie" tabindex="0" aria-label="<?= e(t('galerie_titlu')) ?>">
    <?php foreach (t('galerie') as $i => $text): ?>
      <a href="/assets/images/<?= e($poze_galerie[$i]) ?>" class="galerie-poza" data-text="<?= e($text) ?>">
        <img src="/assets/images/mici/<?= e($poze_galerie[$i]) ?>" alt="<?= e($text) ?>" loading="lazy">
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- UNDE E -->
<section id="locul" class="locul">
  <img class="locul-poza" src="/assets/images/mici/fatada.jpg" alt="<?= e(t('locul_alt')) ?>" loading="lazy">
  <div class="locul-text">
    <h2><?= e(t('locul_titlu')) ?></h2>
    <p><?= e(t('locul_text')) ?></p>
    <address><?= e($setari['adresa']) ?></address>
    <div class="locul-butoane">
      <a href="<?= e($setari['harta_link']) ?>" class="buton buton-contur" target="_blank" rel="noopener"><?= e(t('buton_harta')) ?></a>
      <a href="tel:<?= e($setari['telefon_link']) ?>" class="buton buton-contur"><?= e(t('buton_suna', telefon())) ?></a>
    </div>
  </div>
</section>

<!-- OBIECTIVE DIN ZONĂ -->
<section id="zona" class="zona">
  <div class="zona-cap">
    <h2><?= e(t('zona_titlu')) ?></h2>
    <p><?= e(t('zona_text')) ?></p>
  </div>
  <ul class="obiective">
    <?php foreach (t('obiective') as $i => [$nume, $text]): ?>
      <li class="obiectiv">
        <h3><?= e($nume) ?></h3>
        <p><?= e($text) ?></p>
        <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(urlencode($harta_obiective[$i])) ?>" target="_blank" rel="noopener"><?= e(t('vezi_pe_harta')) ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- Fereastra pentru poze mari -->
<div class="lightbox" hidden role="dialog" aria-modal="true" aria-label="<?= e(t('poza_marita')) ?>" data-din="<?= e(t('din')) ?>">
  <button class="lightbox-inchide" aria-label="<?= e(t('inchide')) ?>">×</button>
  <button class="lightbox-prev" aria-label="<?= e(t('poza_anterioara')) ?>">‹</button>
  <img src="" alt="">
  <button class="lightbox-next" aria-label="<?= e(t('poza_urmatoare')) ?>">›</button>
  <p class="lightbox-text"></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
