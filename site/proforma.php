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
  <link rel="stylesheet" href="/assets/css/style.css?v=8">
  <link rel="stylesheet" href="/assets/css/proforma.css?v=1">
</head>
<body class="proforma-pagina">

<?php if (!$r): ?>
  <main class="document"><p><?= e($P['negasit']) ?></p></main>
<?php else: ?>

  <div class="bara-print">
    <button type="button" class="buton buton-plin" onclick="window.print()"><?= e($P['print']) ?></button>
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
        <p><strong><?= e($r['nume']) ?></strong><br>
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
      <tr class="de-plata"><th><?= e(strtr($P['avans'], $v)) ?></th><td><strong><?= e($v['{avans}']) ?></strong></td></tr>
      <?php if ($v['{procent}'] < 100): ?>
      <tr><th><?= e($P['rest']) ?></th><td><?= e($v['{rest}']) ?></td></tr>
      <?php endif; ?>
    </table>

    <p class="doc-plata"><?= e(strtr($P['plata'], $v)) ?></p>
    <p class="doc-mentiune"><?= e($P['mentiune']) ?></p>

    <footer class="doc-subsol">
      <?= e($setari['nume_vila']) ?> · <?= e($setari['adresa']) ?> · vilabusteni.ro
    </footer>
  </main>

<?php endif; ?>
</body>
</html>
