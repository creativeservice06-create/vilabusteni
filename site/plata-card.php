<?php
// ============================================================
//  PLATA CU CARDUL – plata-card.php?cod=...
//  - fără parametri: arată suma și butonul „Plătește cu cardul” (îl trimite pe pagina securizată Netopia)
//  - cu &intors=1: clientul s-a întors de la Netopia; arătăm rezultatul (sau așteptăm confirmarea)
//  Textele sunt în lang/*.php, la cheia 'card'.
// ============================================================
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/rezervari.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
$C = t('card');
$cod = preg_replace('/[^a-f0-9]/', '', $_GET['cod'] ?? '');
$pdo = db();
$r = null;
if (strlen($cod) === 32 && $pdo) {
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE cod = ?");
    $st->execute([$cod]);
    $r = $st->fetch() ?: null;
}

$eroare = null;
// Pornirea plății: cerem pagina de plată la Netopia și trimitem clientul acolo
if ($r && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['token'] ?? '') !== ($_SESSION['token'] ?? null)) {
        $eroare = $C['err_sesiune'];
    } elseif (($_POST['actiune'] ?? '') === 'transfer') {
        // se răzgândește: plătește prin ordin de plată → primește proforma pe email
        if (treci_la_transfer($pdo, $r, $setari)) {
            header('Location: ' . url('/proforma.php') . '?cod=' . $r['cod'] . '&nou=1', true, 303);
            exit;
        }
        $eroare = $C['indisponibil'];
    } elseif (!poate_plati_card($r, $setari)) {
        $eroare = $C['indisponibil'];
    } else {
        $rez = start_plata_card($pdo, $r, $setari);
        if (isset($rez['url'])) {
            header('Location: ' . $rez['url'], true, 303);
            exit;
        }
        $eroare = t_card_tel($C['err_start']);
    }
}

/** Textul cu %s înlocuit de telefonul vilei. */
function t_card_tel($text)
{
    return sprintf($text, telefon());
}

// Ultima încercare de plată (pentru pagina de întoarcere)
$plata = null;
if ($r) {
    $st = $pdo->prepare("SELECT * FROM plati_card WHERE rezervare_id = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$r['id']]);
    $plata = $st->fetch() ?: null;
}
$intors = isset($_GET['intors']);
if (isset($_GET['eroare']) && !$eroare) {
    $eroare = t_card_tel($C['err_start']);   // pornirea plății din formular n-a mers
}
$runda  = min(30, max(0, (int) ($_GET['n'] ?? 0)));

// Ce arătăm
if (!$r) {
    $stare = 'negasit';
} elseif ($r['status'] === 'confirmata') {
    $stare = ($plata && $plata['status'] === 'platita') ? 'platit' : 'deja';
} elseif ($intors && $plata && in_array($plata['status'], ['pornita', 'in_curs'], true)) {
    $stare = $runda < 20 ? 'verificam' : 'intarzie';
} elseif ($intors && $plata && in_array($plata['status'], ['respinsa', 'anulata', 'eroare'], true)) {
    $stare = 'respins';
} elseif ($intors && $plata && $plata['status'] === 'platita') {
    $stare = 'verificam_rez';   // plata a reușit, dar rezervarea așteaptă o verificare (ex. perioadă ocupată)
} elseif (poate_plati_card($r, $setari)) {
    $stare = 'plateste';
} else {
    $stare = 'indisponibil';
}

if ($r) {
    $v = valori_mail($r, $setari, $LIMBA, $GLOBALS['TEXTE']);
}
$titlu = $C['titlu'] . ' – ' . $setari['nume_vila'];
$pagina = 'rezervare';
$baza = url('/plata-card.php') . '?cod=' . $cod;
if ($stare === 'verificam') {
    header('Refresh: 4; url=' . $baza . '&intors=1&n=' . ($runda + 1));
}
require __DIR__ . '/includes/header.php';
$link_telefon = '<a href="tel:' . e($setari['telefon_link']) . '">' . e(telefon()) . '</a>';
?>

<section class="interior plata-card">
  <h1><?= e($C['h1_' . $stare] ?? $C['titlu']) ?></h1>

  <?php if ($eroare): ?>
    <div class="mesaj-eroare" role="alert"><p><?= e($eroare) ?></p></div>
  <?php endif; ?>

  <?php if ($stare === 'negasit'): ?>
    <p><?= e($C['negasit']) ?></p>

  <?php else: ?>
    <?php if (in_array($stare, ['verificam', 'intarzie', 'verificam_rez', 'platit', 'deja', 'respins', 'indisponibil'], true)): ?>
      <div class="<?= in_array($stare, ['platit', 'deja'], true) ? 'mesaj-succes' : ($stare === 'respins' ? 'mesaj-eroare' : 'info-avans') ?>" role="status">
        <?php if ($stare === 'verificam'): ?><span class="rotita" aria-hidden="true"></span><?php endif; ?>
        <div><p><?= vsprintf($C['text_' . $stare], [e($r['email']), $link_telefon]) ?></p></div>
      </div>
    <?php endif; ?>

    <!-- Rezumatul plății -->
    <dl class="plata-rezumat">
      <div><dt><?= e($C['rezervare']) ?></dt><dd><?= e($v['{nr}']) ?></dd></div>
      <div><dt><?= e($C['perioada']) ?></dt><dd><?= e($v['{perioada}']) ?> · <?= (int) $r['nopti'] ?> <?= e(t('calendar')['nopti']) ?></dd></div>
      <div><dt><?= e($C['total']) ?></dt><dd><?= e(lei($r['total'])) ?></dd></div>
      <div class="plata-suma"><dt><?= e(in_array($stare, ['platit', 'deja'], true) ? $C['platit'] : ($v['{integral}'] ? $C['suma_integral'] : strtr($C['suma_avans'], $v))) ?></dt><dd><?= e(lei($r['avans'])) ?></dd></div>
    </dl>

    <?php if (in_array($stare, ['plateste', 'respins'], true) && poate_plati_card($r, $setari)): ?>
      <form method="post" action="<?= e($baza) ?>" class="plata-formular">
        <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
        <button class="buton buton-plin buton-lat"><?= e(strtr($stare === 'respins' ? $C['buton_reincearca'] : $C['buton'], ['{suma}' => lei($r['avans'])])) ?></button>
      </form>
      <p class="plata-sigur">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/></svg>
        <span><?= e($C['sigur']) ?></span>
      </p>
      <form method="post" action="<?= e($baza) ?>" class="plata-alternativa">
        <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
        <input type="hidden" name="actiune" value="transfer">
        <span class="mic"><?= e($C['alternativa']) ?></span>
        <button class="buton buton-contur buton-mic"><?= e($C['buton_transfer']) ?></button>
      </form>
    <?php elseif (in_array($stare, ['platit', 'deja'], true)): ?>
      <p><a href="<?= e(url('/proforma.php')) ?>?cod=<?= e($cod) ?>" class="buton buton-contur"><?= e($C['link_proforma']) ?></a></p>
    <?php endif; ?>

    <?php if ($stare === 'verificam'): ?>
      <p class="mic"><a href="<?= e($baza) ?>&amp;intors=1&amp;n=<?= $runda + 1 ?>"><?= e($C['reincarca']) ?></a></p>
    <?php endif; ?>
  <?php endif; ?>

  <p><a href="<?= e(url('/')) ?>"><?= e(t('rez_inapoi')) ?></a></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
