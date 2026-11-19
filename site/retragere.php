<?php
// ============================================================
//  RETRAGEREA DIN CONTRACT ONLINE (art. 11¹ din OUG 34/2014)
//  Linkul „Retrageți-vă din contract aici” e în subsolul fiecărei pagini.
//  Clientul completează numele, rezervarea și emailul, apasă „Confirmați retragerea”
//  și primește imediat pe email confirmarea de primire, cu conținutul declarației și data/ora.
//  Proprietarul primește și el mail. Rezervarea NU se anulează automat: o anulezi din panou,
//  conform politicii de anulare. Declarațiile se păstrează în tabelul „retrageri”.
//  Textele sunt în lang/*.php, la cheia 'retragere'.
// ============================================================
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/rezervari.php';

header('X-Robots-Tag: noindex');
$R = t('retragere');
$date = ['nume' => '', 'email' => '', 'rezervare' => preg_replace('/[^A-Za-z0-9-]/', '', $_GET['nr'] ?? ''), 'mesaj' => ''];
$erori = [];
$trimis = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($date as $camp => $_) {
        $date[$camp] = trim((string) ($_POST[$camp] ?? ''));
    }
    if (($_POST['token'] ?? '') !== ($_SESSION['token'] ?? null)) $erori[] = t('err_sesiune');
    if (!empty($_POST['site_web'])) $erori[] = t('err_robot');
    if (mb_strlen($date['nume']) < 3) $erori[] = t('err_nume');
    if (!filter_var($date['email'], FILTER_VALIDATE_EMAIL)) $erori[] = t('err_email');
    if (mb_strlen($date['rezervare']) < 3) $erori[] = $R['err_rezervare'];

    $pdo = db();
    if (!$erori && !$pdo) $erori[] = t('err_indisponibil', telefon());

    if (!$erori) {
        $acum = date('Y-m-d H:i:s');
        // Căutăm rezervarea după număr (VR-0007 sau 7) și email, ca proprietarul s-o găsească ușor
        $rez = null;
        if (preg_match('/(\d+)\s*$/', $date['rezervare'], $m)) {
            $st = $pdo->prepare("SELECT * FROM rezervari WHERE id = ? AND LOWER(email) = LOWER(?)");
            $st->execute([(int) $m[1], $date['email']]);
            $rez = $st->fetch() ?: null;
        }
        $pdo->prepare("INSERT INTO retrageri (nume, email, rezervare, rezervare_id, mesaj, ip, limba, creat_la) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([mb_substr($date['nume'], 0, 120), mb_substr($date['email'], 0, 160), mb_substr($date['rezervare'], 0, 40),
                       $rez['id'] ?? null, mb_substr($date['mesaj'], 0, 2000), $_SERVER['REMOTE_ADDR'] ?? '', $LIMBA, $acum]);
        $nr_inreg = (int) $pdo->lastInsertId();
        $trimis = ['data' => date('d.m.Y, H:i', strtotime($acum)), 'nr' => $nr_inreg];
        trimite_mailuri_retragere($date, $rez, $trimis, $setari, $LIMBA);
        unset($_SESSION['token']);
    }
}

/** Confirmarea de primire către client (suport durabil = email) + notificarea proprietarului. */
function trimite_mailuri_retragere(array $d, $rez, array $trimis, array $setari, $limba)
{
    $T = texte_limba($limba);
    $E = $T['mailuri'];
    $R = $T['retragere'];
    $v = ['{telefon}' => $limba === 'ro' ? $setari['telefon'] : $setari['telefon_int']];
    $rand = function ($e, $val) { return rand_tabel($e, nl2br(e($val))); };
    $continut = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'
        . $rand($R['nume'], $d['nume'])
        . $rand($R['rezervare'], $d['rezervare'])
        . $rand($R['email'], $d['email'])
        . ($d['mesaj'] !== '' ? $rand($R['mesaj'], $d['mesaj']) : '')
        . $rand($R['data_ora'], $trimis['data'])
        . $rand($R['nr_inregistrare'], 'R-' . $trimis['nr'])
        . '</table>';
    $declaratie = ['titlu' => $R['mail_continut'], 'html' => '<p style="margin:0 0 10px">' . e($R['declaratie']) . '</p>' . $continut];

    $html = sablon_mail([
        'limba' => $limba, 'preheader' => $R['mail_intro'], 'titlu' => $R['mail_titlu'],
        'intro' => strtr($R['mail_intro'], ['{data}' => $trimis['data']]),
        'status' => ['asteptare', $R['mail_status']],
        'sectiuni' => [$declaratie], 'butoane' => [],
        'E' => $E, 'T' => $T, 'v' => $v, 'setari' => $setari,
    ]);
    trimite_mail_html($d['email'], strtr($R['mail_subiect'], ['{rezervare}' => $d['rezervare']]), $html, $setari, $setari['email_contact']);

    // proprietarului, în română
    $To = texte_limba('ro');
    $Ro = $To['retragere'];
    $html = sablon_mail([
        'limba' => 'ro', 'preheader' => "Declarație de retragere: {$d['nume']}, {$d['rezervare']}",
        'titlu' => 'Declarație de retragere din contract',
        'intro' => $rez
            ? "{$d['nume']} a trimis online declarația de retragere pentru rezervarea #{$rez['id']} ({$rez['status']}). Clientul a primit automat confirmarea de primire. Rezervarea NU s-a anulat automat: verifică politica de anulare, anuleaz-o din panou și, dacă e cazul, rambursează suma (cardul – din contul Netopia)."
            : "{$d['nume']} a trimis online o declarație de retragere, dar numărul „{$d['rezervare']}” nu corespunde unei rezervări cu emailul {$d['email']}. Contactează clientul. A primit automat confirmarea de primire.",
        'status' => ['asteptare', 'De verificat'],
        'sectiuni' => [['titlu' => 'Declarația', 'html' => str_replace([$R['nume'], $R['rezervare'], $R['email'], $R['mesaj'], $R['data_ora'], $R['nr_inregistrare']],
                                                                       [$Ro['nume'], $Ro['rezervare'], $Ro['email'], $Ro['mesaj'], $Ro['data_ora'], $Ro['nr_inregistrare']], $continut)]],
        'butoane' => [['Deschide panoul', SITE_URL . '/admin/', 'plin']],
        'E' => $To['mailuri'], 'T' => $To, 'v' => ['{telefon}' => $setari['telefon']], 'setari' => $setari,
    ]);
    trimite_mail_html($setari['email_proprietar'], "Retragere din contract – {$d['rezervare']} – {$d['nume']}", $html, $setari, $d['email']);
}

$titlu = $R['titlu'] . ' – ' . $setari['nume_vila'];
require __DIR__ . '/includes/header.php';
?>

<section class="interior retragere">
  <h1><?= e($R['h1']) ?></h1>

<?php if ($trimis): ?>
  <div class="mesaj-succes" role="status">
    <p><strong><?= e($R['ok_titlu']) ?></strong></p>
    <p><?= e(strtr($R['ok_text'], ['{data}' => $trimis['data'], '{email}' => $date['email'], '{nr}' => 'R-' . $trimis['nr']])) ?></p>
  </div>
  <p><a href="<?= e(url('/')) ?>"><?= e(t('rez_inapoi')) ?></a></p>

<?php else: ?>
  <p class="intro"><?= e($R['intro']) ?></p>
  <p class="mic"><?= e($R['nota_cazare']) ?> <a href="<?= e(url('/termeni.php')) ?>#anulare"><?= e(t('termeni')) ?></a>.</p>

  <?php if ($erori): ?>
    <div class="mesaj-eroare" role="alert"><?php foreach ($erori as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div>
  <?php endif; ?>

  <form method="post" class="campuri form-retragere" novalidate>
    <input type="hidden" name="token" value="<?= e(token_formular()) ?>">
    <input type="text" name="site_web" class="capcana" tabindex="-1" autocomplete="off" aria-hidden="true">
    <label><?= e($R['nume']) ?>
      <input type="text" name="nume" required maxlength="120" value="<?= e($date['nume']) ?>" autocomplete="name">
    </label>
    <label><?= e($R['rezervare']) ?>
      <input type="text" name="rezervare" required maxlength="40" value="<?= e($date['rezervare']) ?>" placeholder="VR-0007">
      <span class="mic"><?= e($R['rezervare_ajutor']) ?></span>
    </label>
    <label><?= e($R['email']) ?>
      <input type="email" name="email" required maxlength="160" value="<?= e($date['email']) ?>" autocomplete="email">
      <span class="mic"><?= e($R['email_ajutor']) ?></span>
    </label>
    <label><?= e($R['mesaj']) ?>
      <textarea name="mesaj" rows="3" maxlength="2000"><?= e($date['mesaj']) ?></textarea>
    </label>
    <button type="submit" class="buton buton-plin buton-lat"><?= e($R['buton']) ?></button>
  </form>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
