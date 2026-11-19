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
    'plata' => 'avans', 'metoda' => '',
    // date pentru factura fiscală
    'fact_tara' => $LIMBA === 'ro' ? 'RO' : '', 'fact_judet' => '', 'fact_localitate' => '', 'fact_adresa' => '',
    'fact_pj' => '', 'fact_firma' => '', 'fact_cui' => '', 'fact_reg_com' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($date as $camp => $_) {
        $date[$camp] = trim($_POST[$camp] ?? '');
    }
    $rezultat = salveaza_rezervare($date, $_POST, $setari);
    $erori = $rezultat['erori'];
    $trimis = empty($erori);

    // A ales cardul: îl trimitem direct pe pagina de plată Netopia
    if ($trimis && $rezultat['metoda'] === 'card') {
        $start = start_plata_card(db(), $rezultat['r'], $setari);
        header('Location: ' . ($start['url'] ?? (url('/plata-card.php') . '?cod=' . $rezultat['cod'] . '&eroare=1')), true, 303);
        exit;
    }
}

$titlu = t('rez_titlu') . ' – ' . $setari['nume_vila'];
$scripturi = ['/assets/js/calendar.js?v=7', '/assets/js/facturare.js?v=1'];
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
    <p class="succes-butoane">
      <a href="<?= e(url('/proforma.php')) ?>?cod=<?= e($rezultat['cod']) ?>" class="buton buton-plin" target="_blank"><?= e(t('rez_proforma')) ?></a>
      <a href="<?= e(url('/proforma.php')) ?>?cod=<?= e($rezultat['cod']) ?>#dovada" class="buton buton-contur" target="_blank"><?= e(t('mailuri')['buton_dovada']) ?></a>
    </p>
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

  <?php if (trim(t('politica_anulare')) !== ''): ?>
    <p class="politica-anulare"><strong><?= e(t('mailuri')['anulare_titlu']) ?>:</strong> <?= e(t('politica_anulare')) ?>
      <a href="<?= e(url('/termeni.php')) ?>#anulare"><?= e(t('termeni')) ?></a></p>
  <?php endif; ?>

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
        <!-- Datele pentru factura fiscală -->
        <fieldset class="facturare" id="facturare">
          <legend><?= e(t('fact_titlu')) ?></legend>
          <label><?= e(t('fact_tara')) ?>
            <select name="fact_tara" id="fact-tara" autocomplete="country" required>
              <?php if ($date['fact_tara'] === ''): ?><option value="" selected disabled><?= e(t('fact_alege')) ?></option><?php endif; ?>
              <?php foreach (lista_tari($LIMBA) as $cod_t => $nume_t): ?>
                <option value="<?= e($cod_t) ?>" <?= $date['fact_tara'] === $cod_t ? 'selected' : '' ?>><?= e($nume_t) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="camp-judet" <?= $date['fact_tara'] !== 'RO' ? 'hidden' : '' ?>><?= e(t('fact_judet')) ?>
            <select name="fact_judet" autocomplete="address-level1">
              <option value=""><?= e(t('fact_alege')) ?></option>
              <?php foreach (JUDETE as $cod_j => $nume_j): ?>
                <option value="<?= e($cod_j) ?>" <?= $date['fact_judet'] === $cod_j ? 'selected' : '' ?>><?= e($nume_j) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label><?= e(t('fact_localitate')) ?>
            <input type="text" name="fact_localitate" required maxlength="100" value="<?= e($date['fact_localitate']) ?>" autocomplete="address-level2" data-bucuresti="<?= e(t('fact_sector')) ?>">
          </label>
          <label class="camp-lat"><?= e(t('fact_adresa')) ?>
            <input type="text" name="fact_adresa" required maxlength="255" value="<?= e($date['fact_adresa']) ?>" autocomplete="street-address" placeholder="<?= e(t('fact_adresa_ex')) ?>">
          </label>
          <label class="bifa camp-lat">
            <input type="checkbox" name="fact_pj" value="1" id="fact-pj" <?= $date['fact_pj'] ? 'checked' : '' ?>>
            <span><?= e(t('fact_pj')) ?></span>
          </label>
          <div class="campuri-firma camp-lat" <?= $date['fact_pj'] ? '' : 'hidden' ?>>
            <label class="camp-lat"><?= e(t('fact_firma')) ?>
              <input type="text" name="fact_firma" maxlength="160" value="<?= e($date['fact_firma']) ?>" autocomplete="organization">
            </label>
            <label><?= e(t('fact_cui')) ?>
              <input type="text" name="fact_cui" maxlength="20" value="<?= e($date['fact_cui']) ?>" placeholder="RO12345678">
            </label>
            <label><?= e(t('fact_reg_com')) ?>
              <input type="text" name="fact_reg_com" maxlength="40" value="<?= e($date['fact_reg_com']) ?>">
            </label>
          </div>
        </fieldset>

        <!-- Cât plătește acum: avansul sau toată suma -->
        <fieldset class="optiuni-plata" id="optiuni-plata" data-procent="<?= (int) $setari['avans_procent'] ?>">
          <legend><?= e(t('plata_titlu')) ?></legend>
          <label class="optiune">
            <input type="radio" name="plata" value="avans" <?= $date['plata'] !== 'integral' ? 'checked' : '' ?>>
            <span class="optiune-text"><?= e(strtr(t('opt_avans'), ['{procent}' => $setari['avans_procent']])) ?></span>
            <span class="optiune-suma" data-suma="avans"><?= e(t('opt_alege')) ?></span>
          </label>
          <label class="optiune">
            <input type="radio" name="plata" value="integral" <?= $date['plata'] === 'integral' ? 'checked' : '' ?>>
            <span class="optiune-text"><?= e(t('opt_integral')) ?></span>
            <span class="optiune-suma" data-suma="integral"><?= e(t('opt_alege')) ?></span>
          </label>
        </fieldset>

        <!-- Cum plătește: cu cardul (pe loc) sau prin ordin de plată (proformă) -->
        <fieldset class="optiuni-plata metode-plata" id="metode-plata">
          <legend><?= e(t('metoda_titlu')) ?></legend>
          <?php if (card_activ($setari)): ?>
          <label class="optiune">
            <input type="radio" name="metoda" value="card" <?= $date['metoda'] === 'card' ? 'checked' : '' ?> data-buton="<?= e(t('f_trimite_card')) ?>">
            <span class="optiune-text"><strong><?= e(t('metoda_card')) ?></strong><small><?= e(t('metoda_card_text')) ?></small></span>
          </label>
          <?php endif; ?>
          <label class="optiune">
            <input type="radio" name="metoda" value="transfer" <?= $date['metoda'] === 'transfer' || !card_activ($setari) ? 'checked' : '' ?> data-buton="<?= e(t('f_trimite_transfer')) ?>">
            <span class="optiune-text"><strong><?= e(t('metoda_transfer')) ?></strong><small><?= e(strtr(t('metoda_transfer_text'), ['{ore}' => $setari['termen_plata_ore']])) ?></small></span>
          </label>
        </fieldset>

        <label class="bifa">
          <input type="checkbox" name="accept" value="1" required>
          <span><?= t('f_accept', e(url('/termeni.php')), e(url('/confidentialitate.php'))) ?></span>
        </label>
        <button type="submit" class="buton buton-plin buton-lat" id="buton-trimite"><?= e($date['metoda'] === 'card' ? t('f_trimite_card') : ($date['metoda'] === 'transfer' ? t('f_trimite_transfer') : t('f_trimite'))) ?></button>
        <p class="mic"><?= e(strtr(t('f_nota'), ['{ore}' => $setari['termen_plata_ore'], '{procent}' => $setari['avans_procent']])) ?></p>
      </div>
    </div>
  </form>

<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
