<?php
// PAGINA DE REZERVARE
// Clientul alege perioada în calendar, completează datele și trimite cererea.
// Cererea se salvează în baza de date (status "in_asteptare") și se trimit două mailuri:
// unul către proprietar, unul de confirmare a primirii către client.
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/rezervari.php';

$erori = [];
$trimis = false;
$date = [
    'sosire' => '', 'plecare' => '', 'persoane' => 2,
    'nume' => '', 'email' => '', 'telefon' => '', 'mesaj' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($date as $camp => $_) {
        $date[$camp] = trim($_POST[$camp] ?? '');
    }
    $rezultat = salveaza_rezervare($date, $_POST, $setari);
    $erori = $rezultat['erori'];
    $trimis = empty($erori);
}

$titlu = 'Rezervare – ' . $setari['nume_vila'];
$scripturi = ['/assets/js/calendar.js?v=2'];
require __DIR__ . '/includes/header.php';
?>

<section class="sectiune rezervare">
  <p class="supratitlu">Rezervare</p>

<?php if ($trimis): ?>

  <h1>Mulțumim! Am primit cererea ta.</h1>
  <div class="mesaj-succes">
    <p>Perioada: <strong><?= e(date('d.m.Y', strtotime($date['sosire']))) ?> – <?= e(date('d.m.Y', strtotime($date['plecare']))) ?></strong>
       (<?= (int) $rezultat['nopti'] ?> nopți), total <strong><?= lei($rezultat['total']) ?></strong>.</p>
    <p>Te contactăm în cel mai scurt timp pentru confirmare. Ți-am trimis și un email la <strong><?= e($date['email']) ?></strong>.</p>
    <p>Pentru orice întrebare: <a href="tel:<?= e($setari['telefon_link']) ?>"><?= e($setari['telefon']) ?></a>.</p>
  </div>
  <p><a href="/" class="buton buton-contur-inchis">Înapoi la prima pagină</a></p>

<?php else: ?>

  <h1>Alege perioada</h1>
  <p class="intro">Toată vila: <?= lei($setari['pret_noapte']) ?> / noapte · minimum <?= (int) $setari['minim_nopti'] ?> nopți.
     Zilele tăiate sunt deja ocupate.</p>

  <?php if ($erori): ?>
    <div class="mesaj-eroare">
      <?php foreach ($erori as $er): ?><p><?= e($er) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="form-rezervare" novalidate>
    <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
    <input type="hidden" name="sosire" id="sosire" value="<?= e($date['sosire']) ?>">
    <input type="hidden" name="plecare" id="plecare" value="<?= e($date['plecare']) ?>">
    <!-- câmp capcană pentru roboți: oamenii nu îl văd -->
    <input type="text" name="site_web" class="capcana" tabindex="-1" autocomplete="off">

    <div class="rezervare-grila">
      <div>
        <div id="calendar" class="calendar"
             data-minim="<?= (int) $setari['minim_nopti'] ?>"
             data-pret="<?= (int) $setari['pret_noapte'] ?>"></div>
        <div id="sumar" class="sumar">Alege data sosirii, apoi data plecării.</div>
      </div>

      <div class="campuri">
        <label>Nume și prenume
          <input type="text" name="nume" required maxlength="120" value="<?= e($date['nume']) ?>" autocomplete="name">
        </label>
        <label>Email
          <input type="email" name="email" required maxlength="160" value="<?= e($date['email']) ?>" autocomplete="email">
        </label>
        <label>Telefon
          <input type="tel" name="telefon" required maxlength="40" value="<?= e($date['telefon']) ?>" autocomplete="tel">
        </label>
        <label>Număr de persoane
          <select name="persoane">
            <?php for ($i = 1; $i <= $setari['max_persoane']; $i++): ?>
              <option value="<?= $i ?>" <?= (int) $date['persoane'] === $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </label>
        <label>Mesaj (opțional)
          <textarea name="mesaj" rows="3" maxlength="1000"><?= e($date['mesaj']) ?></textarea>
        </label>
        <label class="bifa">
          <input type="checkbox" name="accept" value="1" required>
          <span>Am citit și accept <a href="/termeni.php" target="_blank">Termenii și condițiile</a>
            și <a href="/confidentialitate.php" target="_blank">Politica de confidențialitate</a>.</span>
        </label>
        <button type="submit" class="buton buton-lat" id="buton-trimite">Trimite cererea de rezervare</button>
        <p class="mic">Rezervarea devine fermă după confirmarea noastră. Plata online cu cardul va fi disponibilă în curând.</p>
      </div>
    </div>
  </form>

<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
