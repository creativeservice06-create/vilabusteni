<?php
// ============================================================
//  PANOUL DE ADMINISTRARE – vilabusteni.ro/admin/
//  Parola se setează în config.php (pe server): 'admin_parola' => '...'
//  De aici: vezi cererile, le confirmi sau anulezi (clientul primește mail
//  automat, în limba lui) și blochezi zile închiriate pe alte căi.
// ============================================================
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/rezervari.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$config = is_file(__DIR__ . '/../config.php') ? require __DIR__ . '/../config.php' : [];
$parola_setata = (string) ($config['admin_parola'] ?? '');
$mesaj = null;

// ---------- Ieșire ----------
if (isset($_GET['iesire'])) {
    unset($_SESSION['admin']);
    header('Location: ./');
    exit;
}

// ---------- Autentificare ----------
if (empty($_SESSION['admin']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parola'])) {
    $blocat_pana = $_SESSION['admin_blocat'] ?? 0;
    if (time() < $blocat_pana) {
        $mesaj = ['eroare', 'Prea multe încercări greșite. Mai încearcă peste câteva minute.'];
    } elseif ($parola_setata !== '' && hash_equals($parola_setata, (string) $_POST['parola'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['admin_incercari'] = 0;
        header('Location: ./');
        exit;
    } else {
        sleep(2); // încetinește ghicirea parolei
        $_SESSION['admin_incercari'] = ($_SESSION['admin_incercari'] ?? 0) + 1;
        if ($_SESSION['admin_incercari'] >= 5) {
            $_SESSION['admin_blocat'] = time() + 15 * 60;
            $_SESSION['admin_incercari'] = 0;
        }
        $mesaj = ['eroare', 'Parolă greșită.'];
    }
}

$logat = !empty($_SESSION['admin']);
$pdo = $logat ? db() : null;

// ---------- Acțiuni (confirmă / anulează / blochează) ----------
if ($logat && $pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune'])) {
    if (($_POST['token'] ?? '') !== ($_SESSION['token'] ?? null)) {
        $mesaj = ['eroare', 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.'];
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        switch ($_POST['actiune']) {
            case 'confirma':    $mesaj = schimba_status($pdo, $id, 'confirmata', $setari); break;
            case 'anuleaza':    $mesaj = schimba_status($pdo, $id, 'anulata', $setari); break;
            case 'deblocheaza': $mesaj = schimba_status($pdo, $id, 'anulata', $setari); break;
            case 'blocheaza':   $mesaj = blocheaza_perioada($pdo, $_POST['de_la'] ?? '', $_POST['pana_la'] ?? '', $_POST['nota'] ?? ''); break;
        }
    }
    // după acțiune revenim la pagină (fără retrimiterea formularului la refresh)
    $_SESSION['admin_mesaj'] = $mesaj;
    header('Location: ./?vezi=' . urlencode($_GET['vezi'] ?? 'noi'));
    exit;
}
if (isset($_SESSION['admin_mesaj'])) {
    $mesaj = $_SESSION['admin_mesaj'];
    unset($_SESSION['admin_mesaj']);
}

// ---------- Datele pentru listă ----------
$file = [
    'noi'        => ['Cereri noi',   "status = 'in_asteptare' AND plecare >= :azi", 'sosire ASC'],
    'confirmate' => ['Confirmate',   "status = 'confirmata' AND plecare >= :azi",  'sosire ASC'],
    'blocate'    => ['Zile blocate', "status = 'blocat' AND plecare >= :azi",      'sosire ASC'],
    'istoric'    => ['Istoric',      "(status = 'anulata' OR plecare < :azi)",     'sosire DESC'],
];
$vezi = isset($file[$_GET['vezi'] ?? '']) ? $_GET['vezi'] : 'noi';
$numar = [];
$lista = [];
if ($pdo) {
    $azi = date('Y-m-d');
    foreach ($file as $cheie => [$_, $conditie, $__]) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM rezervari WHERE $conditie");
        $st->execute([':azi' => $azi]);
        $numar[$cheie] = (int) $st->fetchColumn();
    }
    [$_, $conditie, $ordine] = $file[$vezi];
    $st = $pdo->prepare("SELECT * FROM rezervari WHERE $conditie ORDER BY $ordine LIMIT 200");
    $st->execute([':azi' => $azi]);
    $lista = $st->fetchAll();
}

$statusuri = ['in_asteptare' => 'În așteptare', 'confirmata' => 'Confirmată', 'anulata' => 'Anulată', 'blocat' => 'Blocat'];
function data_ro($d) { return date('d.m.Y', strtotime($d)); }
function zi_sapt($d) { return ['Du', 'Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ'][(int) date('w', strtotime($d))]; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Administrare – <?= e($setari['nume_vila']) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/sigle/randunica-verde.png">
  <link rel="stylesheet" href="/assets/css/style.css?v=8">
  <link rel="stylesheet" href="/assets/css/admin.css?v=1">
</head>
<body class="admin">

<header class="admin-antet">
  <a href="./" class="admin-logo">
    <img src="/assets/images/sigle/randunica-verde.png" alt="" width="36" height="33">
    <span>Rezervări <small><?= e($setari['nume_vila']) ?></small></span>
  </a>
  <?php if ($logat): ?>
    <nav><a href="/" target="_blank">Vezi site-ul</a><a href="?iesire=1">Ieși</a></nav>
  <?php endif; ?>
</header>

<main class="admin-continut">

<?php if ($mesaj): ?>
  <div class="admin-mesaj admin-mesaj-<?= e($mesaj[0]) ?>" role="status"><?= e($mesaj[1]) ?></div>
<?php endif; ?>

<?php if (!$logat): ?>

  <!-- ===== Autentificare ===== -->
  <section class="admin-login">
    <h1>Intră în panou</h1>
    <?php if ($parola_setata === ''): ?>
      <p class="admin-mesaj admin-mesaj-eroare">Panoul nu are încă parolă. În cPanel → File Manager → public_html → config.php adaugă linia:<br>
        <code>'admin_parola' => 'parola-ta-lunga',</code></p>
    <?php else: ?>
      <form method="post">
        <label>Parola
          <input type="password" name="parola" required autofocus autocomplete="current-password">
        </label>
        <button class="buton buton-plin buton-lat">Intră</button>
      </form>
    <?php endif; ?>
  </section>

<?php elseif (!$pdo): ?>

  <p class="admin-mesaj admin-mesaj-eroare">Baza de date nu răspunde (<?= e($GLOBALS['db_eroare'] ?? 'motiv necunoscut') ?>). Verifică config.php.</p>

<?php else: ?>

  <!-- ===== Filele ===== -->
  <nav class="admin-file">
    <?php foreach ($file as $cheie => [$nume]): ?>
      <a href="?vezi=<?= e($cheie) ?>" <?= $cheie === $vezi ? 'aria-current="page"' : '' ?>>
        <?= e($nume) ?> <span><?= $numar[$cheie] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($vezi === 'blocate'): ?>
    <!-- ===== Blochează zile ===== -->
    <form method="post" class="admin-blocheaza">
      <h2>Blochează zile</h2>
      <p class="mic">Pentru perioade închiriate prin telefon, pe Booking sau zile în care vila nu e disponibilă. Apar ocupate în calendarul de pe site.</p>
      <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
      <input type="hidden" name="actiune" value="blocheaza">
      <div class="admin-blocheaza-campuri">
        <label>Prima noapte <input type="date" name="de_la" required min="<?= date('Y-m-d') ?>"></label>
        <label>Ziua plecării <input type="date" name="pana_la" required min="<?= date('Y-m-d') ?>"></label>
        <label class="admin-nota">Notă (opțional) <input type="text" name="nota" maxlength="255" placeholder="ex: Booking.com – familia Ionescu"></label>
      </div>
      <button class="buton buton-plin">Blochează perioada</button>
    </form>
  <?php endif; ?>

  <!-- ===== Lista ===== -->
  <?php if (!$lista): ?>
    <p class="admin-gol"><?= $vezi === 'noi' ? 'Nicio cerere nouă. Când cineva rezervă pe site, apare aici și primești mail.' : 'Nimic aici deocamdată.' ?></p>
  <?php endif; ?>

  <div class="admin-lista">
  <?php foreach ($lista as $r): ?>
    <article class="admin-card status-<?= e($r['status']) ?>">
      <header>
        <span class="admin-status"><?= e($statusuri[$r['status']] ?? $r['status']) ?></span>
        <span class="admin-id">#<?= (int) $r['id'] ?><?php if ($r['status'] !== 'blocat'): ?> · <?= e(strtoupper($r['limba'] ?? 'ro')) ?><?php endif; ?></span>
      </header>

      <p class="admin-perioada">
        <?= zi_sapt($r['sosire']) ?> <?= data_ro($r['sosire']) ?> → <?= zi_sapt($r['plecare']) ?> <?= data_ro($r['plecare']) ?>
        <small><?= (int) $r['nopti'] ?> <?= (int) $r['nopti'] === 1 ? 'noapte' : 'nopți' ?></small>
      </p>

      <?php if ($r['status'] === 'blocat'): ?>
        <p><?= e($r['nota'] ?: 'Fără notă') ?></p>
      <?php else: ?>
        <dl>
          <dt>Client</dt><dd><?= e($r['nume']) ?>, <?= (int) $r['persoane'] ?> pers.</dd>
          <dt>Total</dt><dd><strong><?= number_format((int) $r['total'], 0, ',', '.') ?> lei</strong></dd>
          <dt>Telefon</dt><dd>
            <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $r['telefon'])) ?>"><?= e($r['telefon']) ?></a>
            · <a href="https://wa.me/<?= e(ltrim(preg_replace('/\D/', '', preg_replace('/^0/', '40', $r['telefon'])), '0')) ?>" target="_blank" rel="noopener">WhatsApp</a>
          </dd>
          <dt>Email</dt><dd><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></dd>
          <?php if (trim($r['mesaj'] ?? '') !== ''): ?><dt>Mesaj</dt><dd><?= nl2br(e($r['mesaj'])) ?></dd><?php endif; ?>
          <dt>Primită</dt><dd><?= e(date('d.m.Y H:i', strtotime($r['creat_la']))) ?></dd>
        </dl>
      <?php endif; ?>

      <?php if (strtotime($r['plecare']) >= strtotime('today') && $r['status'] !== 'anulata'): ?>
        <div class="admin-butoane">
          <?php if ($r['status'] === 'in_asteptare'): ?>
            <form method="post" onsubmit="return confirm('Confirmi rezervarea #<?= (int) $r['id'] ?>? Clientul primește mail de confirmare.')">
              <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
              <input type="hidden" name="actiune" value="confirma"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="buton buton-plin">Confirmă</button>
            </form>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['in_asteptare', 'confirmata'], true)): ?>
            <form method="post" onsubmit="return confirm('Anulezi rezervarea #<?= (int) $r['id'] ?>? Clientul primește mail că perioada nu e disponibilă.')">
              <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
              <input type="hidden" name="actiune" value="anuleaza"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="buton buton-contur">Anulează</button>
            </form>
          <?php endif; ?>
          <?php if ($r['status'] === 'blocat'): ?>
            <form method="post" onsubmit="return confirm('Deblochezi aceste zile? Vor apărea din nou libere pe site.')">
              <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
              <input type="hidden" name="actiune" value="deblocheaza"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="buton buton-contur">Deblochează</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>

<?php endif; ?>
</main>
</body>
</html>
