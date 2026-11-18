<?php
// PAGINA PRINCIPALĂ
// Textele se modifică direct aici. Telefonul, prețul etc. vin din includes/setari.php.
require __DIR__ . '/includes/init.php';
$titlu  = 'Vila Rândunica – cazare în Poiana Țapului, Bușteni';
$pagina = 'acasa';
require __DIR__ . '/includes/header.php';

// Spațiile casei: [poza, nume, descriere scurtă]
$spatii = [
    ['terasa-vedere.jpg', 'Terasa',       'De aici se vede toată creasta, de la primele raze până după apus.'],
    ['living.jpg',        'Livingul',     'Șemineu, fotolii adânci și scara de lemn spre mansardă.'],
    ['sufragerie.jpg',    'Sufrageria',   'Masa rotundă, bufetul vechi și ferestre spre curte.'],
    ['dormitor-1.jpg',    'Dormitoarele', 'Trei camere, fiecare cu baia ei.'],
    ['bucatarie.jpg',     'Bucătăria',    'Aragaz pe gaz, cuptor, cuptor cu microunde și tot ce trebuie pentru gătit.'],
];

// Obiective din zonă: [nume, descriere, ce se caută pe Google Maps]
$obiective = [
    ['Cascada Urlătoarea', 'Una dintre cele mai cunoscute cascade de pe Valea Prahovei, la capătul unui traseu ușor prin pădure care pornește chiar din Poiana Țapului.', 'Cascada Urlătoarea Bușteni'],
    ['Castelul Cantacuzino', 'Castel în stil neoromânesc din Bușteni, ridicat la începutul secolului XX de prințul Gheorghe Grigore Cantacuzino, cu o terasă spre Bucegi.', 'Castelul Cantacuzino Bușteni'],
    ['Mănăstirea Caraiman', 'Mănăstire ortodoxă la poalele muntelui Caraiman, într-un loc liniștit, la marginea pădurii.', 'Mănăstirea Caraiman Bușteni'],
    ['Telecabina Bușteni', 'Urcă din Bușteni direct pe Platoul Bucegi, la Babele, fără drumeție.', 'Telecabina Bușteni'],
    ['Platoul Bucegi', 'Babele și Sfinxul, stâncile modelate de vânt, și Crucea Eroilor de pe Caraiman, cea pe care o vezi și de pe terasa vilei.', 'Babele Bucegi'],
    ['Vârful Omu și Valea Cerbului', 'Cel mai înalt vârf din Bucegi, la 2.505 m. Valea Cerbului, unul dintre traseele clasice spre el, pornește din Bușteni.', 'Vârful Omu Bucegi'],
];

// Galeria: [poza, descriere]
$galerie = [
    ['munti-panorama.jpg', 'Bucegii văzuți din curte'],
    ['fatada.jpg',         'Fațada cu arcade și treptele de piatră'],
    ['terasa-2.jpg',       'Terasa'],
    ['living.jpg',         'Livingul cu șemineu'],
    ['sufragerie.jpg',     'Sufrageria'],
    ['dormitor-2.jpg',     'Dormitor'],
    ['dormitor-1.jpg',     'Dormitor'],
    ['bucatarie.jpg',      'Bucătăria'],
    ['munti-bucegi.jpg',   'Creasta, într-o zi senină'],
];
?>

<!-- SUS: poza mare, cu creasta desenată peste marginea de jos -->
<section class="hero">
  <img class="hero-poza" src="/assets/images/munti-panorama.jpg" alt="Munții Bucegi văzuți din curtea vilei" fetchpriority="high">
  <div class="hero-text">
    <h1>Vila Rândunica</h1>
    <p class="hero-motto">Din 1925, cu fața spre Bucegi.</p>
    <div class="hero-butoane">
      <a href="/rezervare.php" class="buton buton-plin">Vezi datele libere</a>
      <a href="tel:<?= e($setari['telefon_link']) ?>" class="buton buton-sticla">Sună: <?= e($setari['telefon']) ?></a>
    </div>
  </div>
  <svg class="creasta creasta-hero" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
    <polygon points="<?= e(CREASTA) ?> 1200,120 0,120" />
    <polyline points="<?= e(CREASTA) ?>" pathLength="1" />
  </svg>
</section>

<!-- Pe scurt -->
<section class="pe-scurt" aria-label="Pe scurt">
  <ul>
    <li><strong>Toată vila</strong> doar pentru voi</li>
    <li><strong>3 camere</strong> fiecare cu baia ei, plus mansardă</li>
    <li><strong><?= lei($setari['pret_noapte']) ?></strong> pe noapte, minimum <?= (int) $setari['minim_nopti'] ?> nopți</li>
  </ul>
</section>

<!-- POVESTEA -->
<section id="povestea" class="povestea">
  <div class="povestea-an" aria-hidden="true">1925</div>
  <div class="povestea-text">
    <h2>Casa care te face să mai rămâi o clipă</h2>
    <p class="introducere">Pe terasă, lumina prinde mai întâi crestele Bucegilor. Mai jos, Poiana Țapului rămâne încă în umbră. Te sprijini de balustrada de lemn și, pentru o vreme, uiți să te uiți la ceas.</p>
    <ol class="cronologie">
      <li>
        <span class="cronologie-cand">1925</span>
        <p>Vila e ridicată între Bușteni și Sinaia. Castelul Peleș, reședința de vară a regelui Carol I, face parte din viața Familiei Regale și găzduiește vizite oficiale.</p>
      </li>
      <li>
        <span class="cronologie-cand">Mai târziu</span>
        <p>Casa devine locuință de protocol. Sub arcade îți poți închipui pași pe treptele de piatră și conversații mutate pe terasă, care țineau mult după apus.</p>
      </li>
      <li>
        <span class="cronologie-cand">Azi</span>
        <p>Își poartă și acum numele din acte. Când cobori treptele spre curte, întorci încă o dată capul spre munți. Poate de aceea i se potrivește atât de bine numele de Rândunica.</p>
      </li>
    </ol>
  </div>
</section>

<!-- CASA -->
<section id="casa" class="casa">
  <div class="casa-cap">
    <h2>Casa, cameră cu cameră</h2>
    <p>Se închiriază integral: trei camere cu baie proprie, mansardă, living, sufragerie, bucătărie, terasă și curte.</p>
  </div>

  <div class="spatii">
    <?php foreach ($spatii as $i => [$poza, $nume, $text]): ?>
      <figure class="spatiu spatiu-<?= $i + 1 ?>">
        <img src="/assets/images/<?= $i === 0 ? '' : 'mici/' ?><?= e($poza) ?>" alt="<?= e($nume) ?>" loading="lazy">
        <figcaption><strong><?= e($nume) ?></strong> <?= e($text) ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>

  <ul class="dotari">
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21V9l9-6 9 6v12h-6v-7H9v7z"/></svg>Garaj</li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>Stație de încărcare pentru mașini electrice</li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22c4 0 7-3 7-7 0-4-3-6-4-10-1 3-3 4-3 4S11 5 9 2C9 7 5 10 5 15c0 4 3 7 7 7z"/></svg>Șemineu în living</li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M6 20V9h12v11M9 9V5h6v4"/></svg>Bucătărie complet utilată</li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20h14M7 20V4h10v16M10 8h4M10 12h4M10 16h4"/></svg>Încălzire centrală pe gaz</li>
    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m2 18 6-9 4 5 3-3 7 7z"/></svg>Terasă cu vedere spre Bucegi</li>
  </ul>
</section>

<!-- PREȚ -->
<section class="pret">
  <svg class="creasta creasta-pret" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
    <polygon points="<?= e(CREASTA) ?> 1200,0 0,0" />
  </svg>
  <div class="pret-interior">
    <h2><?= lei($setari['pret_noapte']) ?> pe noapte, pentru toată vila</h2>
    <p>Minimum <?= (int) $setari['minim_nopti'] ?> nopți, adică <?= lei($setari['pret_noapte'] * $setari['minim_nopti']) ?> pentru un weekend prelungit.
       Check-in de la <?= e($setari['ora_check_in']) ?>, check-out până la <?= e($setari['ora_check_out']) ?>.</p>
    <div class="pret-butoane">
      <a href="/rezervare.php" class="buton buton-plin">Alege datele</a>
      <a href="https://wa.me/<?= e($setari['whatsapp']) ?>" class="buton buton-sticla" target="_blank" rel="noopener">Întreabă pe WhatsApp</a>
    </div>
  </div>
</section>

<!-- GALERIE: pe telefon se derulează cu degetul, pe calculator e grilă -->
<section id="galerie" class="galerie-sectiune">
  <div class="galerie-cap">
    <h2>Galerie</h2>
    <p class="galerie-indiciu">Glisează pentru mai multe poze</p>
  </div>
  <div class="galerie" tabindex="0" aria-label="Galerie foto">
    <?php foreach ($galerie as [$poza, $text]): ?>
      <a href="/assets/images/<?= e($poza) ?>" class="galerie-poza" data-text="<?= e($text) ?>">
        <img src="/assets/images/mici/<?= e($poza) ?>" alt="<?= e($text) ?>" loading="lazy">
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- UNDE E -->
<section id="locul" class="locul">
  <img class="locul-poza" src="/assets/images/mici/fatada.jpg" alt="Vila Rândunica văzută din curte" loading="lazy">
  <div class="locul-text">
    <h2>În Poiana Țapului, pe Valea Prahovei</h2>
    <p>Între Bușteni și Sinaia, aproape de Castelul Peleș, de telecabina spre Babele și de traseele din Bucegi.</p>
    <address><?= e($setari['adresa']) ?></address>
    <div class="locul-butoane">
      <a href="<?= e($setari['harta_link']) ?>" class="buton buton-contur" target="_blank" rel="noopener">Deschide în Google Maps</a>
      <a href="tel:<?= e($setari['telefon_link']) ?>" class="buton buton-contur">Sună: <?= e($setari['telefon']) ?></a>
    </div>
  </div>
</section>

<!-- OBIECTIVE DIN ZONĂ -->
<section id="zona" class="zona">
  <div class="zona-cap">
    <h2>Ce vezi în zonă</h2>
    <p>Locuri pe care le poți vizita într-o zi, plecând de la vilă.</p>
  </div>
  <ul class="obiective">
    <?php foreach ($obiective as [$nume, $text, $cauta]): ?>
      <li class="obiectiv">
        <h3><?= e($nume) ?></h3>
        <p><?= e($text) ?></p>
        <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(urlencode($cauta)) ?>" target="_blank" rel="noopener">Vezi pe hartă</a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- Fereastra pentru poze mari -->
<div class="lightbox" hidden role="dialog" aria-modal="true" aria-label="Poză mărită">
  <button class="lightbox-inchide" aria-label="Închide">×</button>
  <button class="lightbox-prev" aria-label="Poza anterioară">‹</button>
  <img src="" alt="">
  <button class="lightbox-next" aria-label="Poza următoare">›</button>
  <p class="lightbox-text"></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
