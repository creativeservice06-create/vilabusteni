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
            case 'test_mail':
                $GLOBALS['ultima_eroare_mail'] = null;
                $html = sablon_mail([
                    'limba' => 'ro', 'preheader' => 'Mail de test', 'titlu' => 'Mail de test',
                    'intro' => 'Dacă citești asta, mailurile site-ului ajung corect. Trimis ' . date('d.m.Y H:i') . '.',
                    'status' => ['confirmata', 'Test'], 'sectiuni' => [], 'butoane' => [['Deschide panoul', SITE_URL . '/admin/', 'plin']],
                    'E' => texte_limba('ro')['mailuri'], 'T' => texte_limba('ro'), 'v' => ['{telefon}' => $setari['telefon']], 'setari' => $setari,
                ]);
                $ok = trimite_mail_html($setari['email_proprietar'], 'Mail de test – ' . $setari['nume_vila'], $html, $setari, $setari['email_contact']);
                $prin = $GLOBALS['mail_trimis_prin'] ?? '';
                if ($ok && $prin === 'smtp') {
                    $mesaj = ['ok', "Mail de test trimis de pe {$setari['smtp_user']} către {$setari['email_proprietar']}. Verifică Inbox-ul (și Spam)."];
                } elseif ($ok) {
                    $motiv = config_smtp($setari) ? ('serverul de mail a răspuns: ' . ($GLOBALS['ultima_eroare_mail'] ?? 'eroare necunoscută')) : "lipsește 'smtp_parola' din config.php";
                    $mesaj = ['eroare', "Mailul a plecat doar prin varianta de rezervă ({$setari['email_expeditor_rezerva']}), pentru că $motiv."];
                } else {
                    $mesaj = ['eroare', 'Mailul de test nu a putut fi trimis deloc. ' . ($GLOBALS['ultima_eroare_mail'] ?? '')];
                }
                break;
            case 'blocheaza':   $mesaj = blocheaza_perioada($pdo, $_POST['de_la'] ?? '', $_POST['pana_la'] ?? '', $_POST['nota'] ?? ''); break;
            case 'factura_rest':       $mesaj = factureaza_rest($pdo, $id, $setari); break;
            case 'factura_reincearca': $mesaj = reincearca_factura($pdo, (int) ($_POST['factura'] ?? 0), $setari); break;
            case 'factura_emite':      // factură pentru o rezervare confirmată fără factură (ex. confirmată înainte de activarea FGO)
                $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ? AND status = 'confirmata'");
                $st->execute([$id]);
                $rz = $st->fetch();
                if (!$rz) { $mesaj = ['eroare', "Rezervarea #$id nu este confirmată."]; break; }
                [$ok_f, $msg_f] = emite_factura_plata($pdo, $rz, (int) $rz['avans'], $setari);
                $mesaj = [$ok_f ? 'ok' : 'eroare', $msg_f];
                if ($ok_f && trimite_mail_client($rz, 'factura', $setari)) $mesaj[1] .= " Clientul a primit factura pe mail ({$rz['email']}).";
                break;
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
    'noi'        => ['Așteaptă plata',   "status = 'in_asteptare' AND plecare >= :azi", 'sosire ASC'],
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

    // plățile cu cardul și facturile rezervărilor din listă
    $plati = $facturi = [];
    $iduri = array_map('intval', array_column($lista, 'id'));
    if ($iduri) {
        $in = implode(',', $iduri);
        foreach ($pdo->query("SELECT * FROM plati_card WHERE rezervare_id IN ($in) ORDER BY id DESC")->fetchAll() as $p) $plati[$p['rezervare_id']][] = $p;
        foreach ($pdo->query("SELECT * FROM facturi WHERE rezervare_id IN ($in) ORDER BY id")->fetchAll() as $f) $facturi[$f['rezervare_id']][] = $f;
    }
}
$fgo_activ = fgo_activ($setari);
$netopia = config_netopia();
$stari_card = ['pornita' => 'deschisă, neplătită', 'in_curs' => 'în curs', 'platita' => 'plătită', 'respinsa' => 'refuzată', 'anulata' => 'anulată', 'eroare' => 'eroare la pornire', 'rambursata' => 'rambursată'];
$tipuri_factura = ['avans' => 'avans', 'integral' => 'integral', 'rest' => 'rest'];

$statusuri = ['in_asteptare' => 'Așteaptă plata', 'confirmata' => 'Confirmată', 'anulata' => 'Anulată', 'blocat' => 'Blocat'];
// ---------- Date afișate pe înțelesul tuturor ----------
const ZILE_RO = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
const LUNI_RO = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
/** "Luni" */
function zi_lunga($d) { return ZILE_RO[(int) date('w', strtotime($d))]; }
/** "5 ianuarie 2027" */
function data_lunga($d) { $t = strtotime($d); return date('j', $t) . ' ' . LUNI_RO[date('n', $t) - 1] . ' ' . date('Y', $t); }
/** "joi, 19 noiembrie 2026, ora 10:03" */
function data_ora($d) { $t = strtotime($d); return mb_strtolower(zi_lunga($d)) . ', ' . data_lunga($d) . ', ora ' . date('H:i', $t); }
/** Durată ușor de citit: "1 zi și 4 ore", "5 ore și 20 min", "40 min" */
function durata($secunde)
{
    $secunde = abs((int) $secunde);
    $zile = intdiv($secunde, 86400); $ore = intdiv($secunde % 86400, 3600); $min = intdiv($secunde % 3600, 60);
    if ($zile > 0) return $zile . ($zile === 1 ? ' zi' : ' zile') . ($ore ? ' și ' . $ore . ($ore === 1 ? ' oră' : ' ore') : '');
    if ($ore > 0)  return $ore . ($ore === 1 ? ' oră' : ' ore') . ($min ? ' și ' . $min . ' min' : '');
    return max(1, $min) . ' min';
}
function lei_admin($s) { return number_format((int) $s, 0, ',', '.') . ' lei'; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Administrare – <?= e($setari['nume_vila']) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/sigle/randunica-verde.png">
  <link rel="stylesheet" href="/assets/css/style.css?v=11">
  <link rel="stylesheet" href="/assets/css/admin.css?v=5">
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
    <p class="admin-gol"><?= $vezi === 'noi' ? 'Nicio rezervare care așteaptă plata. Când cineva rezervă pe site, apare aici și primești mail.' : 'Nimic aici deocamdată.' ?></p>
  <?php endif; ?>

  <div class="admin-lista">
  <?php foreach ($lista as $r): ?>
    <?php
      $in_asteptare = $r['status'] === 'in_asteptare';
      $expirat      = $in_asteptare && !empty($r['termen_plata']) && strtotime($r['termen_plata']) < time() && empty($r['dovada_la']);
      $de_plata     = (int) ($r['avans'] ?? 0);
      $integral     = $de_plata >= (int) $r['total'] && (int) $r['total'] > 0;
      $procent_ales = (int) $r['total'] > 0 ? (int) round($de_plata * 100 / $r['total']) : 0;
      $pana_la_sosire = (int) round((strtotime($r['sosire']) - strtotime('today')) / 86400);
    ?>
    <article class="admin-card status-<?= e($r['status']) ?><?= $expirat ? ' expirat' : '' ?>">
      <header>
        <span class="admin-status"><?= e($expirat ? 'Termen de plată expirat' : ($statusuri[$r['status']] ?? $r['status'])) ?></span>
        <span class="admin-id">#<?= (int) $r['id'] ?><?php if ($r['status'] !== 'blocat'): ?> · <?= e(LIMBI[$r['limba'] ?? 'ro'] ?? 'Română') ?><?php endif; ?></span>
      </header>

      <?php if (!empty($r['dovada_la']) && $r['status'] !== 'blocat'): ?>
        <p class="admin-alerta alerta-albastru">
          Dovada de plată a fost trimisă <?= e(data_ora($r['dovada_la'])) ?>.
          <a href="dovada.php?id=<?= (int) $r['id'] ?>" target="_blank"><strong>Vezi dovada</strong></a>
          <?php if ($in_asteptare): ?><br>Verifică dacă banii au intrat în cont, apoi apasă <strong>Plată primită – confirmă</strong>. Nu se anulează automat.<?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if ($in_asteptare && !empty($r['termen_plata']) && empty($r['dovada_la'])): ?>
        <!-- Cât timp mai are clientul să plătească -->
        <p class="admin-alerta<?= $expirat ? ' alerta-rosu' : '' ?>">
          <?php if ($expirat): ?>
            Termenul a expirat acum <?= e(durata(time() - strtotime($r['termen_plata']))) ?>. Zilele s-au eliberat pe site. Dacă nu a plătit, apasă <strong>Anulează</strong>.
          <?php else: ?>
            Mai are <strong><?= e(durata(strtotime($r['termen_plata']) - time())) ?></strong> să plătească avansul – până <?= e(data_ora($r['termen_plata'])) ?>.
          <?php endif; ?>
        </p>
      <?php elseif ($r['status'] === 'anulata'): ?>
        <p class="admin-alerta alerta-rosu">Anulată<?= !empty($r['modificat_la']) ? ' ' . e(data_ora($r['modificat_la'])) : '' ?><?= !empty($r['nota']) ? ' – ' . e(mb_strtolower(mb_substr($r['nota'], 0, 1)) . mb_substr($r['nota'], 1)) : '' ?>.</p>
      <?php elseif ($r['status'] === 'confirmata' && !empty($r['platit_la'])): ?>
        <p class="admin-alerta alerta-verde"><?= ($r['plata_metoda'] ?? '') === 'card' ? 'Plătit cu cardul' : 'Plată încasată' ?> – înregistrat <?= e(data_ora($r['platit_la'])) ?>.</p>
      <?php endif; ?>

      <!-- Sosire / plecare -->
      <div class="admin-date">
        <div>
          <span class="admin-eticheta">Sosire</span>
          <strong class="admin-zi"><?= e(zi_lunga($r['sosire'])) ?></strong>
          <span class="admin-data"><?= e(data_lunga($r['sosire'])) ?></span>
          <span class="admin-ora">de la <?= e($setari['ora_check_in']) ?></span>
        </div>
        <div class="admin-sageata" aria-hidden="true">→</div>
        <div>
          <span class="admin-eticheta">Plecare</span>
          <strong class="admin-zi"><?= e(zi_lunga($r['plecare'])) ?></strong>
          <span class="admin-data"><?= e(data_lunga($r['plecare'])) ?></span>
          <span class="admin-ora">până la <?= e($setari['ora_check_out']) ?></span>
        </div>
      </div>
      <p class="admin-nopti">
        <?= (int) $r['nopti'] ?> <?= (int) $r['nopti'] === 1 ? 'noapte' : 'nopți' ?>
        <?php if ($pana_la_sosire > 0): ?> · sosesc peste <?= $pana_la_sosire ?> <?= $pana_la_sosire === 1 ? 'zi' : 'zile' ?><?php elseif ($pana_la_sosire === 0): ?> · sosesc <strong>azi</strong><?php endif; ?>
      </p>

      <?php if ($r['status'] === 'blocat'): ?>
        <p class="admin-bloc-nota"><?= e($r['nota'] ?: 'Fără notă') ?></p>
      <?php else: ?>
        <!-- Clientul -->
        <section class="admin-sectiune">
          <h3>Client</h3>
          <p class="admin-client"><strong><?= e($r['nume']) ?></strong> · <?= (int) $r['persoane'] ?> <?= (int) $r['persoane'] === 1 ? 'persoană' : 'persoane' ?></p>
          <div class="admin-contact">
            <a class="admin-chip" href="tel:<?= e(preg_replace('/[^\d+]/', '', $r['telefon'])) ?>">Sună: <?= e($r['telefon']) ?></a>
            <a class="admin-chip" href="https://wa.me/<?= e(ltrim(preg_replace('/\D/', '', preg_replace('/^0/', '40', $r['telefon'])), '0')) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <a class="admin-chip" href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a>
          </div>
          <?php if (trim($r['mesaj'] ?? '') !== ''): ?>
            <blockquote class="admin-mesaj-client"><?= nl2br(e($r['mesaj'])) ?></blockquote>
          <?php endif; ?>
          <?php if (!empty($r['fact_adresa'])): ?>
            <p class="admin-factura-date">
              <span>Factură pe:</span>
              <?php if (($r['fact_tip'] ?? 'PF') === 'PJ'): ?><strong><?= e($r['fact_firma']) ?></strong>, CUI <?= e($r['fact_cui']) ?><?= $r['fact_reg_com'] ? ', ' . e($r['fact_reg_com']) : '' ?> · <?php else: ?>persoană fizică · <?php endif; ?>
              <?= e(adresa_facturare($r)) ?>
            </p>
          <?php endif; ?>
        </section>

        <!-- Banii -->
        <section class="admin-sectiune">
          <h3>Plată</h3>
          <div class="admin-bani">
            <div><span>Total sejur</span><strong><?= e(lei_admin($r['total'])) ?></strong></div>
            <div class="<?= $r['status'] === 'confirmata' ? 'bani-ok' : 'bani-asteptare' ?>">
              <span><?= $integral ? ($r['status'] === 'confirmata' ? 'Plătit integral' : 'Plată integrală de primit') : (($r['status'] === 'confirmata' ? 'Avans plătit' : 'Avans de primit') . ' (' . $procent_ales . '%)') ?></span>
              <strong><?= e(lei_admin($r['avans'] ?? 0)) ?></strong>
            </div>
            <div><span>Rest la sosire</span><strong><?= e(lei_admin($r['total'] - (int) ($r['avans'] ?? 0))) ?></strong></div>
          </div>
        </section>

        <?php if (!empty($plati[$r['id']])): ?>
        <section class="admin-sectiune">
          <h3>Plăți cu cardul</h3>
          <ul class="admin-mini-lista">
            <?php foreach ($plati[$r['id']] as $p): ?>
              <li class="plata-<?= e($p['status']) ?>">
                <strong><?= e(lei_admin($p['suma'])) ?></strong> · <?= e($stari_card[$p['status']] ?? $p['status']) ?>
                <span><?= e(data_ora($p['actualizat_la'] ?: $p['creat_la'])) ?><?= $p['card'] ? ' · ' . e($p['card']) : '' ?><?= $p['mesaj'] ? ' · ' . e($p['mesaj']) : '' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endif; ?>

        <?php if (!empty($facturi[$r['id']]) || ($fgo_activ && $r['status'] === 'confirmata')): ?>
        <section class="admin-sectiune">
          <h3>Facturi fiscale</h3>
          <?php if (!empty($facturi[$r['id']])): ?>
          <ul class="admin-mini-lista">
            <?php foreach ($facturi[$r['id']] as $f): ?>
              <li class="<?= $f['numar'] !== null ? 'plata-platita' : 'plata-respinsa' ?>">
                <?php if ($f['numar'] !== null): ?>
                  <strong><?= e($f['serie'] . ' ' . $f['numar']) ?></strong> · <?= e($tipuri_factura[$f['tip']] ?? $f['tip']) ?> · <?= e(lei_admin($f['suma'])) ?>
                  <?php if ($f['link']): ?> · <a href="<?= e($f['link']) ?>" target="_blank" rel="noopener">PDF</a><?php endif; ?>
                <?php else: ?>
                  <strong>Neemisă</strong> (<?= e($tipuri_factura[$f['tip']] ?? $f['tip']) ?>, <?= e(lei_admin($f['suma'])) ?>)
                  <span><?= e($f['eroare']) ?></span>
                  <form method="post" class="admin-inline">
                    <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
                    <input type="hidden" name="actiune" value="factura_reincearca"><input type="hidden" name="factura" value="<?= (int) $f['id'] ?>">
                    <button class="buton buton-contur buton-mic">Reîncearcă</button>
                  </form>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <?php if ($fgo_activ && $r['status'] === 'confirmata'): ?>
            <?php $facturat = array_sum(array_map(function ($f) { return $f['numar'] !== null ? (int) $f['suma'] : 0; }, $facturi[$r['id']] ?? [])); ?>
            <?php if (empty($facturi[$r['id']])): ?>
              <form method="post" class="admin-inline" onsubmit="return confirm('Emiți în FGO factura pentru plata de <?= e(lei_admin($r['avans'])) ?> (#<?= (int) $r['id'] ?>)? Clientul o primește pe mail.')">
                <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
                <input type="hidden" name="actiune" value="factura_emite"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="buton buton-contur buton-mic">Emite factura pentru <?= e(lei_admin($r['avans'])) ?></button>
              </form>
            <?php elseif ($facturat > 0 && $facturat < (int) $r['total']): ?>
              <form method="post" class="admin-inline" onsubmit="return confirm('Ai încasat restul de <?= e(lei_admin($r['total'] - $facturat)) ?> pentru #<?= (int) $r['id'] ?>? Se emite factura în FGO și clientul o primește pe mail.')">
                <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
                <input type="hidden" name="actiune" value="factura_rest"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="buton buton-contur buton-mic">Rest încasat – emite factura (<?= e(lei_admin($r['total'] - $facturat)) ?>)</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <p class="admin-subsol-card">
          Cerere primită <?= e(data_ora($r['creat_la'])) ?>
          <?php if (!empty($r['cod'])): ?> · <a href="<?= e(link_proforma($r)) ?>" target="_blank">Proforma <?= e(numar_proforma($r['id'], $setari)) ?></a><?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if (strtotime($r['plecare']) >= strtotime('today')): ?>
        <div class="admin-butoane">
          <?php if ($r['status'] === 'anulata' && $r['email'] !== ''): ?>
            <form method="post" onsubmit="return confirm('Clientul a plătit totuși pentru #<?= (int) $r['id'] ?>? Rezervarea se reactivează și se confirmă (dacă zilele sunt încă libere), iar clientul primește mail de confirmare.')">
              <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
              <input type="hidden" name="actiune" value="confirma"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="buton buton-contur">Reactivează – plată primită</button>
            </form>
          <?php endif; ?>
          <?php if ($r['status'] === 'in_asteptare'): ?>
            <form method="post" onsubmit="return confirm('Ai primit avansul pentru #<?= (int) $r['id'] ?>? Rezervarea se confirmă și clientul primește mail de confirmare.')">
              <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
              <input type="hidden" name="actiune" value="confirma"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="buton buton-plin">Plată primită – confirmă</button>
            </form>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['in_asteptare', 'confirmata'], true)): ?>
            <form method="post" onsubmit="return confirm('Anulezi rezervarea #<?= (int) $r['id'] ?>? Zilele se eliberează și clientul primește mail de anulare.')">
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

  <!-- Starea plăților cu cardul și a facturării -->
  <section class="admin-integrari">
    <p><strong>Plata cu cardul (Netopia):</strong>
      <?php if (!$netopia): ?>neconfigurată – lipsesc cheile din config.php.
      <?php elseif (empty($setari['plata_card'])): ?>oprită din setari.php ('plata_card' => false).
      <?php else: ?><?= $netopia['mediu'] === 'live' ? '<span class="eticheta-ok">activă (plăți reale)</span>' : '<span class="eticheta-test">mod de test (sandbox)</span> – plățile nu sunt reale' ?><?= $netopia['publica'] === '' ? ' · ATENȚIE: lipsește netopia_cheie_publica, confirmările de plată vor fi respinse' : '' ?>.
      <?php endif; ?>
    </p>
    <p><strong>Facturi automate (FGO):</strong>
      <?php if ($fgo_activ): ?><span class="eticheta-ok">active</span> – seria <?= e($setari['fgo_serie']) ?>, TVA <?= e((float) $setari['fgo_cota_tva'] * 100) ?>%.
      <?php else: ?>neconfigurate –
        <?= e(implode(', ', array_filter([
            trim((string) (config_site()['fgo_cheie_api'] ?? '')) === '' ? 'lipsește fgo_cheie_api din config.php' : '',
            trim((string) $setari['fgo_serie']) === '' ? 'lipsește fgo_serie din setari.php' : '',
            !isset($setari['fgo_cota_tva']) ? 'lipsește fgo_cota_tva din setari.php' : '',
        ]))) ?>.
      <?php endif; ?>
    </p>
  </section>

  <!-- Verificarea mailurilor -->
  <form method="post" class="admin-test-mail">
    <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
    <input type="hidden" name="actiune" value="test_mail">
    <span>Mailurile pleacă de pe <strong><?= e($setari['smtp_user']) ?></strong>.</span>
    <button class="buton buton-contur">Trimite mail de test</button>
  </form>

<?php endif; ?>
</main>
</body>
</html>
