<?php
// PAGINA PRINCIPALĂ
// Textele se modifică direct aici. Telefonul, prețul etc. vin din includes/setari.php.
require __DIR__ . '/includes/init.php';
$titlu = 'Vila Rândunica – cazare în Poiana Țapului, Bușteni';
require __DIR__ . '/includes/header.php';

// Pozele din galerie: [fișier, descriere]. Pozele stau în assets/images (mari) și assets/images/mici.
$galerie = [
    ['terasa-vedere.jpg',    'Bucegii, de pe terasa vilei'],
    ['fatada.jpg',           'Fațada și curtea'],
    ['living.jpg',           'Livingul cu șemineu'],
    ['sufragerie.jpg',       'Sufrageria'],
    ['dormitor-1.jpg',       'Dormitor'],
    ['dormitor-2.jpg',       'Dormitor'],
    ['bucatarie.jpg',        'Bucătăria'],
    ['terasa-2.jpg',         'Terasa'],
    ['munti-bucegi.jpg',     'Vârfurile Bucegilor'],
];
?>

<!-- 1. SUS -->
<section class="hero" style="background-image:url('/assets/images/munti-panorama.jpg')">
  <div class="hero-text">
    <p class="supratitlu"><?= e(mb_strtoupper($setari['zona'])) ?></p>
    <h1>Vila Rândunica</h1>
    <p class="hero-motto">Din 1925, cu fața spre Bucegi.</p>
    <div class="hero-butoane">
      <a href="/rezervare.php" class="buton">Verifică disponibilitatea</a>
      <a href="tel:<?= e($setari['telefon_link']) ?>" class="buton buton-contur">Sună-ne</a>
    </div>
  </div>
</section>

<!-- 2. POVESTEA -->
<section id="poveste" class="sectiune poveste">
  <p class="supratitlu">01 / Povestea casei</p>
  <h2>Casa care te face să mai rămâi o clipă</h2>
  <div class="poveste-text">
    <p>Pe terasă, lumina prinde mai întâi crestele Bucegilor. Mai jos, Poiana Țapului rămâne încă în umbră. Te sprijini de balustrada de lemn și, pentru o vreme, uiți să te uiți la ceas.</p>
    <p>Vila Rândunica a fost ridicată în 1925, între Bușteni și Sinaia. Pe atunci, Castelul Peleș, construit ca reședință de vară a regelui Carol I, făcea parte din viața Familiei Regale a României și găzduia vizite oficiale.</p>
    <p>Vila a fost folosită mai târziu ca locuință de protocol. Dacă te oprești sub arcade, îți poți închipui pași pe treptele de piatră și o conversație care, într-o seară de vară, s-a mutat din casă pe terasă și a mai ținut mult după apus.</p>
    <p>Casa își poartă și azi numele din acte. Iar când cobori treptele spre curte, te trezești că mai întorci o dată capul spre munți și te gândești când ai putea reveni. Poate că de aceea i se potrivește atât de bine numele de Rândunica.</p>
  </div>
</section>

<!-- 3. CAZARE -->
<section id="cazare" class="sectiune cazare">
  <p class="supratitlu">02 / Cazare</p>
  <h2>Toată vila, doar pentru voi</h2>

  <div class="cazare-grila">
    <div>
      <ul class="cifre">
        <li><strong>3</strong> camere</li>
        <li><strong>3</strong> băi individuale</li>
        <li><strong>1</strong> mansardă</li>
      </ul>
      <ul class="facilitati">
        <li>Bucătărie complet utilată</li>
        <li>Living cu șemineu</li>
        <li>Terasă cu vedere spre Bucegi</li>
        <li>Garaj</li>
        <li>Stație de încărcare pentru mașini electrice</li>
        <li>Încălzire centrală pe gaz</li>
      </ul>
    </div>

    <div class="card-pret">
      <p class="supratitlu">Tarif pe noapte</p>
      <p class="pret"><?= lei($setari['pret_noapte']) ?></p>
      <p>Minimum <?= (int) $setari['minim_nopti'] ?> nopți · <?= lei($setari['pret_noapte'] * $setari['minim_nopti']) ?> pentru <?= (int) $setari['minim_nopti'] ?> nopți</p>
      <p class="mic">Check-in de la <?= e($setari['ora_check_in']) ?> · Check-out până la <?= e($setari['ora_check_out']) ?></p>
      <a href="/rezervare.php" class="buton buton-lat">Alege perioada</a>
      <p class="mic contact-rapid">
        sau <a href="tel:<?= e($setari['telefon_link']) ?>"><?= e($setari['telefon']) ?></a> ·
        <a href="https://wa.me/<?= e($setari['whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp</a>
      </p>
    </div>
  </div>
</section>

<!-- 4. GALERIE -->
<section id="galerie" class="sectiune">
  <p class="supratitlu">03 / Galerie</p>
  <h2>Vila, pe dinăuntru și pe dinafară</h2>
  <div class="galerie">
    <?php foreach ($galerie as [$fisier, $text]): ?>
      <a href="/assets/images/<?= e($fisier) ?>" class="galerie-poza" data-text="<?= e($text) ?>">
        <img src="/assets/images/mici/<?= e($fisier) ?>" alt="<?= e($text) ?>" loading="lazy">
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- 5. LOCUL -->
<section id="locul" class="sectiune locul">
  <p class="supratitlu">04 / Valea Prahovei</p>
  <h2>În Poiana Țapului</h2>
  <p>Vila se află în Poiana Țapului, pe Valea Prahovei, între Bușteni și Sinaia – aproape de Castelul Peleș, de telecabina spre Babele și de traseele din Bucegi.</p>
  <p><strong><?= e($setari['adresa']) ?></strong></p>
  <p>
    <a href="<?= e($setari['harta_link']) ?>" target="_blank" rel="noopener" class="buton buton-contur-inchis">Deschide în Google Maps ↗</a>
  </p>
</section>

<!-- Fereastra pentru poze mari (galerie) -->
<div class="lightbox" hidden>
  <button class="lightbox-inchide" aria-label="Închide">×</button>
  <button class="lightbox-prev" aria-label="Poza anterioară">‹</button>
  <img src="" alt="">
  <button class="lightbox-next" aria-label="Poza următoare">›</button>
  <p class="lightbox-text"></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
