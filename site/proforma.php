<?php
// PROFORMA unei rezervări – se deschide din linkul secret din email (proforma.php?cod=...).
// Clientul o poate descărca ca PDF sau tipări (butonul folosește „Salvează ca PDF” din browser).
// Textele sunt în lang/*.php, la cheia 'proforma'.
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/rezervari.php';

header('X-Robots-Tag: noindex, nofollow');
$P = t('proforma');
$cod = preg_replace('/[^a-f0-9]/', '', $_GET['cod'] ?? '');
$r = null;
if (strlen($cod) === 32 && ($pdo = db())) {
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE cod = ?");
    $st->execute([$cod]);
    $r = $st->fetch() ?: null;
}
// Încărcarea dovezii de plată
$mesaj_dovada = null;
if ($r && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dovada'])) {
    if (($_POST['token'] ?? '') !== ($_SESSION['token'] ?? null)) {
        $mesaj_dovada = ['eroare', $P['dov_err']];
    } elseif ($r['status'] === 'confirmata') {
        $mesaj_dovada = ['ok', $P['dov_ok']];
    } else {
        [$ok, $cheie] = salveaza_dovada($pdo, $r, $_FILES['dovada'], $setari);
        $mesaj_dovada = $ok ? ['ok', $P['dov_ok']] : ['eroare', $P[$cheie]];
        if ($ok) {
            $st->execute([$cod]);
            $r = $st->fetch();
        }
    }
}

if ($r) {
    $v = valori_mail($r, $setari, $LIMBA, $GLOBALS['TEXTE']);
    $pret_noapte = $r['nopti'] > 0 ? (int) round($r['total'] / $r['nopti']) : 0;
}
?>
<!DOCTYPE html>
<html lang="<?= e($LIMBA) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($P['titlu']) ?><?= $r ? ' ' . e($v['{nr}']) : '' ?> – <?= e($setari['nume_vila']) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/sigle/randunica-verde.png">
  <link rel="stylesheet" href="/assets/css/style.css?v=14">
  <link rel="stylesheet" href="/assets/css/proforma.css?v=3">
</head>
<body class="proforma-pagina">

<?php if (!$r): ?>
  <main class="document"><p><?= e($P['negasit']) ?></p></main>
<?php else: ?>

  <div class="bara-print">
    <?php if (poate_plati_card($r, $setari)): ?>
      <a href="<?= e(url('/plata-card.php')) ?>?cod=<?= e($r['cod']) ?>" class="buton buton-plin"><?= e(strtr($P['card_buton'], ['{suma}' => lei($r['avans'])])) ?></a>
    <?php endif; ?>
    <?php if ($r['status'] !== 'confirmata'): ?>
      <a href="#dovada" class="buton buton-contur"><?= e($P['dov_buton_sus']) ?></a>
    <?php endif; ?>
    <button type="button" class="buton <?= poate_plati_card($r, $setari) ? 'buton-contur' : 'buton-plin' ?>" onclick="window.print()"><?= e($P['print']) ?></button>
  </div>

  <main class="document">
    <?php if ($r['status'] === 'confirmata'): ?><div class="stampila stampila-ok"><?= e($P['status_platit']) ?></div><?php endif; ?>
    <?php if ($r['status'] === 'anulata'): ?><div class="stampila stampila-anulat"><?= e($P['status_anulat']) ?></div><?php endif; ?>

    <header class="doc-cap">
      <img src="/assets/images/sigle/vila-randunica.png" alt="<?= e($setari['nume_vila']) ?>" class="doc-sigla">
      <div class="doc-titlu">
        <h1><?= e($P['titlu']) ?></h1>
        <table>
          <tr><th><?= e($P['nr']) ?></th><td><strong><?= e($v['{nr}']) ?></strong></td></tr>
          <tr><th><?= e($P['data']) ?></th><td><?= e(date('d.m.Y', strtotime($r['creat_la']))) ?></td></tr>
          <?php if ($r['status'] === 'in_asteptare' && $v['{termen}']): ?>
          <tr><th><?= e($P['valabila']) ?></th><td><?= e($v['{termen}']) ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </header>

    <section class="doc-parti">
      <div>
        <h2><?= e($P['furnizor']) ?></h2>
        <p><strong><?= e($setari['firma_nume']) ?></strong><br>
          <?= e($P['cui']) ?>: <?= e($setari['firma_cui']) ?><br>
          <?= e($P['reg']) ?>: <?= e($setari['firma_reg_com']) ?><br>
          <?= e($P['sediu']) ?>: <?= e($setari['firma_sediu']) ?><br>
          <?php if ($setari['iban'] !== ''): ?>
            <?= e($P['iban']) ?>: <span class="iban"><?= e($setari['iban']) ?></span><br>
            <?= e($P['banca']) ?>: <?= e($setari['banca']) ?><br>
          <?php endif; ?>
          <?= e($P['tel']) ?>: <?= e(telefon()) ?><br>
          <?= e($P['email']) ?>: <?= e($setari['email_contact']) ?>
        </p>
      </div>
      <div>
        <h2><?= e($P['client']) ?></h2>
        <p><?php if (($r['fact_tip'] ?? 'PF') === 'PJ' && !empty($r['fact_firma'])): ?>
            <strong><?= e($r['fact_firma']) ?></strong><br>
            <?= e($P['cui']) ?>: <?= e($r['fact_cui']) ?><?php if (!empty($r['fact_reg_com'])): ?> · <?= e($P['reg']) ?>: <?= e($r['fact_reg_com']) ?><?php endif; ?><br>
            <?= e($P['persoana']) ?>: <?= e($r['nume']) ?><br>
          <?php else: ?>
            <strong><?= e($r['nume']) ?></strong><br>
          <?php endif; ?>
          <?php if (!empty($r['fact_adresa'])): ?>
            <?= e(adresa_facturare($r, $LIMBA)) ?><br>
          <?php endif; ?>
          <?= e($P['tel']) ?>: <?= e($r['telefon']) ?><br>
          <?= e($P['email']) ?>: <?= e($r['email']) ?>
        </p>
      </div>
    </section>

    <table class="doc-tabel">
      <thead>
        <tr><th>#</th><th><?= e($P['descriere']) ?></th><th class="nr"><?= e($P['cant']) ?></th><th class="nr"><?= e($P['pret']) ?></th><th class="nr"><?= e($P['valoare']) ?></th></tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td><?= e(strtr($P['serviciu'], $v)) ?></td>
          <td class="nr"><?= (int) $r['nopti'] ?> <?= e($P['um']) ?></td>
          <td class="nr"><?= e(lei($pret_noapte)) ?></td>
          <td class="nr"><?= e($v['{total}']) ?></td>
        </tr>
      </tbody>
    </table>

    <table class="doc-totaluri">
      <tr><th><?= e($P['total']) ?></th><td><strong><?= e($v['{total}']) ?></strong></td></tr>
      <tr class="de-plata"><th><?= e($v['{integral}'] ? $P['integral'] : strtr($P['avans'], $v)) ?></th><td><strong><?= e($v['{avans}']) ?></strong></td></tr>
      <?php if (!$v['{integral}']): ?>
      <tr><th><?= e($P['rest']) ?></th><td><?= e($v['{rest}']) ?></td></tr>
      <?php endif; ?>
    </table>

    <p class="doc-plata"><?= e(strtr(card_activ($setari) ? $P['plata_card'] : $P['plata'], $v)) ?></p>
    <p class="doc-mentiune"><?= e($P['mentiune']) ?></p>

    <footer class="doc-subsol">
      <?= e($setari['nume_vila']) ?> · <?= e($setari['adresa']) ?> · vilabusteni.ro
    </footer>
  </main>

  <?php if ($r['status'] !== 'confirmata'): ?>
  <!-- Trimiterea dovezii de plată (nu apare la tipărire) -->
  <section class="dovada" id="dovada">
    <h2><?= e($P['dov_titlu']) ?></h2>
    <p><?= e($P['dov_text']) ?></p>
    <?php if ($mesaj_dovada): ?>
      <p class="dovada-mesaj dovada-<?= e($mesaj_dovada[0]) ?>" role="status"><?= e($mesaj_dovada[1]) ?></p>
    <?php elseif (!empty($r['dovada_la'])): ?>
      <p class="dovada-mesaj dovada-ok"><?= e(strtr($P['dov_deja'], ['{data}' => date('d.m.Y, H:i', strtotime($r['dovada_la']))])) ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="#dovada">
      <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
      <label class="dovada-fisier">
        <span><?= e($P['dov_fisier']) ?></span>
        <input type="file" name="dovada" accept="image/jpeg,image/png,image/webp,application/pdf" required>
      </label>
      <button class="buton buton-plin"><?= e($P['dov_buton']) ?></button>
    </form>
  </section>
  <?php endif; ?>

<?php endif; ?>
</body>
</html>
