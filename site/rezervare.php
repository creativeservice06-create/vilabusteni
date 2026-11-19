<?php
// PAGINA DE REZERVARE
// Clientul alege perioada în calendar, completează datele și trimite cererea.
// Cererea se salvează în baza de date (status "in_asteptare") și se trimit două mailuri:
// unul către proprietar (în română), unul de confirmare a primirii către client (în limba lui).
// Textele sunt în lang/*.php.
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

$titlu = t('rez_titlu') . ' – ' . $setari['nume_vila'];
$scripturi = ['/assets/js/calendar.js?v=5'];
$pagina = 'rezervare';
require __DIR__ . '/includes/header.php';

$link_telefon = '<a href="tel:' . e($setari['telefon_link']) . '">' . e(telefon()) . '</a>';
?>

<section class="interior rezervare">

<?php if ($trimis): ?>

  <h1><?= e(t('rez_multumim')) ?></h1>
  <div class="mesaj-succes">
    <p><?= t('rez_perioada', e(date('d.m.Y', strtotime($date['sosire']))), e(date('d.m.Y', strtotime($date['plecare']))), (int) $rezultat['nopti'], e(lei($rezultat['total']))) ?></p>
    <p><?= t('rez_contactam', e($date['email']), e(date('d.m.Y, H:i', strtotime($rezultat['termen']))), e(lei($rezultat['avans']))) ?></p>
    <p><a href="<?= e(url('/proforma.php')) ?>?cod=<?= e($rezultat['cod']) ?>" class="buton buton-plin" target="_blank"><?= e(t('rez_proforma')) ?></a></p>
    <p><?= t('rez_intrebari', $link_telefon) ?></p>
  </div>
  <p><a href="<?= e(url('/')) ?>" class="buton buton-contur"><?= e(t('rez_inapoi')) ?></a></p>

<?php else: ?>

  <h1><?= e(t('rez_h1')) ?></h1>
  <p class="intro"><?= e(t('rez_intro', lei($setari['pret_noapte']), $setari['minim_nopti'])) ?></p>

  <!-- Regula avansului: vizibilă înainte de a alege datele -->
  <div class="info-avans">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 7v5.5l3.5 2"/></svg>
    <div>
      <strong><?= e(t('rez_avans_titlu')) ?></strong>
      <p><?= e(strtr(t('rez_avans_text'), ['{procent}' => $setari['avans_procent'], '{ore}' => $setari['termen_plata_ore']])) ?></p>
    </div>
  </div>

  <?php if ($erori): ?>
    <div class="mesaj-eroare" role="alert">
      <?php foreach ($erori as $er): ?><p><?= e($er) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="form-rezervare" novalidate>
    <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
    <input type="hidden" name="sosire" id="sosire" value="<?= e($date['sosire']) ?>">
    <input type="hidden" name="plecare" id="plecare" value="<?= e($date['plecare']) ?>">
    <!-- câmp capcană pentru roboți: oamenii nu îl văd -->
    <input type="text" name="site_web" class="capcana" tabindex="-1" autocomplete="off" aria-hidden="true">

    <div class="rezervare-grila">
      <div>
        <div id="calendar" class="calendar"
             data-minim="<?= (int) $setari['minim_nopti'] ?>"
             data-pret="<?= (int) $setari['pret_noapte'] ?>"
             data-moneda="<?= e(t('moneda')) ?>"
             data-procent="<?= (int) $setari['avans_procent'] ?>"
             data-ore="<?= (int) $setari['termen_plata_ore'] ?>"
             data-texte="<?= e(json_encode(t('calendar'), JSON_UNESCAPED_UNICODE)) ?>"></div>
        <div id="sumar" class="sumar" aria-live="polite"><?= e(t('calendar')['start']) ?></div>
      </div>

      <div class="campuri">
        <label><?= e(t('f_nume')) ?>
          <input type="text" name="nume" required maxlength="120" value="<?= e($date['nume']) ?>" autocomplete="name">
        </label>
        <label><?= e(t('f_email')) ?>
          <input type="email" name="email" required maxlength="160" value="<?= e($date['email']) ?>" autocomplete="email">
        </label>
        <label><?= e(t('f_telefon')) ?>
          <input type="tel" name="telefon" required maxlength="40" value="<?= e($date['telefon']) ?>" autocomplete="tel">
        </label>
        <label><?= e(t('f_persoane')) ?>
          <select name="persoane">
            <?php for ($i = 1; $i <= $setari['max_persoane']; $i++): ?>
              <option value="<?= $i ?>" <?= (int) $date['persoane'] === $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </label>
        <label><?= e(t('f_mesaj')) ?>
          <textarea name="mesaj" rows="3" maxlength="1000"><?= e($date['mesaj']) ?></textarea>
        </label>
        <label class="bifa">
          <input type="checkbox" name="accept" value="1" required>
          <span><?= t('f_accept', e(url('/termeni.php')), e(url('/confidentialitate.php'))) ?></span>
        </label>
        <button type="submit" class="buton buton-plin buton-lat" id="buton-trimite"><?= e(t('f_trimite')) ?></button>
        <p class="mic"><?= e(strtr(t('f_nota'), ['{ore}' => $setari['termen_plata_ore'], '{procent}' => $setari['avans_procent']])) ?></p>
      </div>
    </div>
  </form>

<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
