<?php
// ============================================================
//  MAILURILE CĂTRE CLIENȚI ȘI PROPRIETAR
//  Format HTML cu sigla vilei (arată bine și pe tema deschisă, și pe cea întunecată)
//  + variantă text simplu pentru aplicațiile care nu afișează HTML.
//  Textele sunt în lang/*.php, la cheia 'email'.
//
//  Tipuri de mail către client:
//    'cerere'     – imediat după rezervare: perioada e ținută 48h, proforma + cum plătește avansul
//    'confirmare' – după ce proprietarul apasă „Plată primită” în panou
//    'anulare'    – când proprietarul anulează (neplată sau perioadă indisponibilă)
//    'expirare'   – automat, când avansul nu a fost plătit în termen (vezi anuleaza_expirate)
// ============================================================

const SITE_URL = 'https://vilabusteni.ro';

/** Trimite unul dintre mailurile de mai sus clientului, în limba în care a rezervat. */
function trimite_mail_client(array $r, $tip, array $setari)
{
    if (($r['email'] ?? '') === '') return false;
    $limba = $r['limba'] ?? 'ro';
    $T = texte_limba($limba);
    $E = $T['mailuri'];
    $v = valori_mail($r, $setari, $limba, $T);

    $sectiuni = [];
    if ($tip === 'cerere') {
        $subiect = strtr($E['cerere_subiect'], $v);
        $titlu   = $E['cerere_titlu'];
        $intro   = strtr($E['cerere_intro'], $v);
        $status  = ['asteptare', $E['status_asteptare']];
        $sectiuni[] = sectiune_detalii($r, $v, $E, true);
        $sectiuni[] = sectiune_plata($v, $E, $setari);
        $butoane = [[$E['buton_proforma'], link_proforma($r), 'plin']];
    } elseif ($tip === 'confirmare') {
        $subiect = strtr($E['conf_subiect'], $v);
        $titlu   = $E['conf_titlu'];
        $intro   = strtr($E['conf_intro'], $v);
        $status  = ['confirmata', $E['status_confirmata']];
        $sectiuni[] = sectiune_detalii($r, $v, $E, false);
        $sectiuni[] = sectiune_sosire($v, $E, $setari);
        $butoane = [[$E['buton_harta'], $setari['harta_link'], 'plin'], [$E['buton_proforma'], link_proforma($r), 'contur']];
    } elseif ($tip === 'expirare') { // anulată automat: avansul nu a venit la timp
        $subiect = strtr($E['exp_subiect'], $v);
        $titlu   = $E['exp_titlu'];
        $intro   = strtr($E['exp_intro'], $v);
        $status  = ['anulata', $E['status_anulata']];
        $butoane = [[$E['buton_alte_date'], SITE_URL . ($limba === 'ro' ? '' : '/' . $limba) . '/rezervare.php', 'plin']];
    } else { // anulare
        $subiect = strtr($E['anul_subiect'], $v);
        $titlu   = $E['anul_titlu'];
        $intro   = strtr($E['anul_intro'], $v);
        $status  = ['anulata', $E['status_anulata']];
        $butoane = [[$E['buton_alte_date'], SITE_URL . ($limba === 'ro' ? '' : '/' . $limba) . '/rezervare.php', 'plin']];
    }

    $politica = trim($T['politica_anulare'] ?? '');
    if ($politica !== '' && !in_array($tip, ['anulare', 'expirare'], true)) {
        $sectiuni[] = ['titlu' => $E['anulare_titlu'], 'html' => '<p style="margin:0">' . nl2br(e($politica)) . '</p>'];
    }
    if ($tip === 'confirmare') {
        $sectiuni[] = ['titlu' => '', 'html' => '<p style="margin:0">' . e($E['factura_nota']) . '</p>'];
    }

    $html = sablon_mail([
        'limba' => $limba, 'preheader' => $intro, 'titlu' => $titlu, 'intro' => $intro, 'status' => $status,
        'sectiuni' => $sectiuni, 'butoane' => $butoane, 'E' => $E, 'T' => $T, 'v' => $v, 'setari' => $setari,
    ]);
    return trimite_mail_html($r['email'], $subiect, $html, $setari, $setari['email_contact']);
}

/** Notificarea proprietarului la o cerere nouă (în română). */
function trimite_mail_proprietar(array $r, array $setari)
{
    $T = texte_limba('ro');
    $E = $T['mailuri'];
    $v = valori_mail($r, $setari, 'ro', $T);
    $client = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'
        . rand_tabel('Client', e($r['nume']))
        . rand_tabel('Telefon', '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $r['telefon'])) . '" style="color:#164a28">' . e($r['telefon']) . '</a>')
        . rand_tabel('Email', '<a href="mailto:' . e($r['email']) . '" style="color:#164a28">' . e($r['email']) . '</a>')
        . rand_tabel('Limba', e(LIMBI[$r['limba']] ?? $r['limba']))
        . (trim($r['mesaj']) !== '' ? rand_tabel('Mesaj', nl2br(e($r['mesaj']))) : '')
        . '</table>';
    $html = sablon_mail([
        'limba' => 'ro', 'preheader' => "Rezervare nouă {$v['{perioada}']} – {$r['nume']}",
        'titlu' => 'Rezervare nouă #' . $r['id'],
        'intro' => 'Clientul are timp până la ' . $v['{termen}'] . ' să plătească avansul de ' . $v['{avans}'] . '. Când vezi banii în cont, apasă „Plată primită” în panou.',
        'status' => ['asteptare', $E['status_asteptare']],
        'sectiuni' => [sectiune_detalii($r, $v, $E, true), ['titlu' => 'Client', 'html' => $client]],
        'butoane' => [['Deschide panoul', SITE_URL . '/admin/', 'plin']],
        'E' => $E, 'T' => $T, 'v' => $v, 'setari' => $setari,
    ]);
    return trimite_mail_html($setari['email_proprietar'], "Rezervare nouă #{$r['id']} – {$v['{perioada}']} – {$r['nume']}", $html, $setari, $r['email']);
}

// ------------------------------------------------------------
//  Valorile care se pun în texte ({nume}, {perioada} ...)
// ------------------------------------------------------------
function valori_mail(array $r, array $setari, $limba, array $T)
{
    $sep = ['en' => ',', 'ru' => ' '][$limba] ?? '.';
    $bani = function ($s) use ($sep, $T) { return number_format((int) $s, 0, ',', $sep) . ' ' . $T['moneda']; };
    $avans = (int) ($r['avans'] ?? 0);
    $termen = !empty($r['termen_plata']) ? date('d.m.Y, H:i', strtotime($r['termen_plata'])) : '';
    return [
        '{nume}'     => $r['nume'],
        '{id}'       => $r['id'],
        '{nr}'       => numar_proforma($r['id'], $setari),
        '{vila}'     => $setari['nume_vila'],
        '{perioada}' => date('d.m.Y', strtotime($r['sosire'])) . ' – ' . date('d.m.Y', strtotime($r['plecare'])),
        '{nopti}'    => $r['nopti'],
        '{persoane}' => $r['persoane'],
        '{total}'    => $bani($r['total']),
        '{avans}'    => $bani($avans),
        '{rest}'     => $bani($r['total'] - $avans),
        '{procent}'  => (int) $setari['avans_procent'],
        '{termen}'   => $termen,
        '{ore}'      => (int) $setari['termen_plata_ore'],
        '{in}'       => $setari['ora_check_in'],
        '{out}'      => $setari['ora_check_out'],
        '{telefon}'  => $limba === 'ro' ? $setari['telefon'] : $setari['telefon_int'],
    ];
}

// ------------------------------------------------------------
//  Secțiunile mailului
// ------------------------------------------------------------
function rand_tabel($eticheta, $valoare_html, $tare = false)
{
    $stil = $tare ? 'font-weight:700;font-size:17px;color:#164a28' : 'color:#17221f';
    return '<tr>'
        . '<td class="m-muted m-row" style="padding:9px 0;border-bottom:1px solid #e3eaea;color:#5d6967;font-size:14px;vertical-align:top;width:42%">' . e($eticheta) . '</td>'
        . '<td class="m-text m-row" style="padding:9px 0;border-bottom:1px solid #e3eaea;font-size:15px;text-align:right;' . $stil . '">' . $valoare_html . '</td>'
        . '</tr>';
}

function sectiune_detalii(array $r, array $v, array $E, $cu_avans)
{
    $h = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'
        . rand_tabel($E['lbl_nr'], e($v['{nr}']))
        . rand_tabel($E['lbl_perioada'], e($v['{perioada}']))
        . rand_tabel($E['lbl_nopti'], e($v['{nopti}']))
        . rand_tabel($E['lbl_persoane'], e($v['{persoane}']))
        . rand_tabel($E['lbl_total'], e($v['{total}']), !$cu_avans);
    if ($cu_avans) {
        $h .= rand_tabel(strtr($E['lbl_avans'], $v), e($v['{avans}']), true);
        if ($v['{procent}'] < 100) $h .= rand_tabel($E['lbl_rest'], e($v['{rest}']));
        $h .= rand_tabel($E['lbl_termen'], e($v['{termen}']));
    } else {
        $h .= rand_tabel($E['lbl_platit'], e($v['{avans}']));
        if ($v['{procent}'] < 100) $h .= rand_tabel($E['lbl_rest'], e($v['{rest}']));
    }
    return ['titlu' => $E['detalii_titlu'], 'html' => $h . '</table>'];
}

function sectiune_plata(array $v, array $E, array $setari)
{
    if ($setari['iban'] === '') {
        return ['titlu' => $E['plata_titlu'], 'html' => '<p style="margin:0">' . e(strtr($E['plata_fara_iban'], $v)) . '</p>'];
    }
    $h = '<p style="margin:0 0 12px">' . e(strtr($E['plata_text'], $v)) . '</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="m-box" style="border-collapse:separate;background:#eef4f0;border-radius:8px">'
        . '<tr><td style="padding:14px 16px">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'
        . rand_tabel($E['lbl_suma'], e($v['{avans}']), true)
        . rand_tabel($E['lbl_titular'], e($setari['firma_nume']))
        . rand_tabel($E['lbl_iban'], '<span style="font-family:Menlo,Consolas,monospace;letter-spacing:.5px">' . e($setari['iban']) . '</span>')
        . rand_tabel($E['lbl_banca'], e($setari['banca']))
        . rand_tabel($E['lbl_referinta'], e($v['{nr}']))
        . '</table></td></tr></table>';
    return ['titlu' => $E['plata_titlu'], 'html' => $h];
}

function sectiune_sosire(array $v, array $E, array $setari)
{
    $h = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'
        . rand_tabel($E['lbl_adresa'], e($setari['adresa']))
        . rand_tabel($E['lbl_check'], e(strtr($E['check_in_out'], $v)))
        . rand_tabel($E['lbl_telefon'], '<a href="tel:' . e($setari['telefon_link']) . '" style="color:#164a28">' . e($v['{telefon}']) . '</a>')
        . '</table>';
    return ['titlu' => $E['sosire_titlu'], 'html' => $h];
}

// ------------------------------------------------------------
//  Șablonul HTML (tabele + stiluri în linie: singurul format sigur în toate aplicațiile de mail)
// ------------------------------------------------------------
function sablon_mail(array $o)
{
    $E = $o['E']; $s = $o['setari']; $v = $o['v'];
    $culori_status = [
        'asteptare'  => ['#fbefd5', '#7a5208'],
        'confirmata' => ['#dcebe3', '#164a28'],
        'anulata'    => ['#f6e1dc', '#7d3426'],
    ];
    [$fundal_status, $text_status] = $culori_status[$o['status'][0]];

    $sectiuni = '';
    foreach ($o['sectiuni'] as $sec) {
        $sectiuni .= '<tr><td class="m-card" style="padding:0 32px 26px;background:#ffffff">'
            . ($sec['titlu'] !== '' ? '<h2 class="m-title" style="margin:0 0 10px;font-family:Georgia,\'Times New Roman\',serif;font-weight:400;font-size:20px;color:#164a28">' . e($sec['titlu']) . '</h2>' : '')
            . '<div class="m-text" style="font-size:15px;line-height:1.55;color:#17221f">' . $sec['html'] . '</div>'
            . '</td></tr>';
    }

    $butoane = '';
    foreach ($o['butoane'] as [$text, $link, $fel]) {
        $stil = $fel === 'plin'
            ? 'background:#7d3426;color:#ffffff;border:2px solid #7d3426'
            : 'background:transparent;color:#164a28;border:2px solid #164a28';
        $butoane .= '<a href="' . e($link) . '" class="m-btn-' . $fel . '" style="display:inline-block;margin:0 8px 10px 0;padding:13px 22px;border-radius:6px;font-weight:700;font-size:15px;text-decoration:none;' . $stil . '">' . e($text) . '</a>';
    }

    $sociale = [];
    foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $k => $n) {
        if (!empty($s['social'][$k])) $sociale[] = '<a href="' . e($s['social'][$k]) . '" style="color:#c3d3c8;text-decoration:underline">' . $n . '</a>';
    }

    return '<!DOCTYPE html>
<html lang="' . e($o['limba']) . '" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>' . e($o['titlu']) . '</title>
<style>
  :root { color-scheme: light dark; supported-color-schemes: light dark; }
  body { margin:0; padding:0; }
  a { color:#164a28; }
  @media (max-width:620px) { .m-card { padding-left:20px !important; padding-right:20px !important; } .m-h1 { font-size:26px !important; } }
  /* Tema întunecată (Apple Mail, iOS, Outlook nou, Thunderbird) */
  @media (prefers-color-scheme: dark) {
    .m-body { background:#0c1611 !important; }
    .m-card { background:#14231b !important; }
    .m-text, .m-text td, .m-h1 { color:#e7efe9 !important; }
    .m-title { color:#9fd0ae !important; }
    .m-muted, td.m-muted { color:#9fb3a8 !important; }
    .m-box { background:#1d3326 !important; }
    .m-btn-contur { color:#9fd0ae !important; border-color:#9fd0ae !important; }
    .m-text a, .m-muted a { color:#9fd0ae !important; }
    .m-row { border-color:#2b4034 !important; }
    .m-sep { border-color:#2b4034 !important; }
  }
  /* Outlook.com / Office 365 pe temă întunecată */
  [data-ogsc] .m-card { background:#14231b !important; }
  [data-ogsc] .m-text, [data-ogsc] .m-h1 { color:#e7efe9 !important; }
  [data-ogsc] .m-title, [data-ogsc] .m-muted a, [data-ogsc] .m-text a { color:#9fd0ae !important; }
</style>
</head>
<body class="m-body" style="margin:0;padding:0;background:#eef3f4">
<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . e($o['preheader']) . '</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="m-body" style="background:#eef3f4">
<tr><td align="center" style="padding:24px 12px">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;border-collapse:separate;border-radius:12px;overflow:hidden;font-family:-apple-system,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif">

    <!-- Antet: sigla pe verde (imagine, deci la fel pe orice temă) -->
    <tr><td style="background:#164a28;padding:0;line-height:0">
      <a href="' . SITE_URL . '"><img src="' . SITE_URL . '/assets/images/sigle/antet-mail.png" width="600" alt="' . e($s['nume_vila']) . ' – Poiana Țapului, Bușteni" style="display:block;width:100%;max-width:600px;height:auto;border:0;color:#ffffff;font-size:22px;line-height:1.3;background:#164a28"></a>
    </td></tr>

    <!-- Titlu + status -->
    <tr><td class="m-card" style="padding:30px 32px 8px;background:#ffffff">
      <span style="display:inline-block;padding:5px 12px;border-radius:999px;background:' . $fundal_status . ';color:' . $text_status . ';font-size:13px;font-weight:700">' . e($o['status'][1]) . '</span>
      <h1 class="m-h1" style="margin:14px 0 10px;font-family:Georgia,\'Times New Roman\',serif;font-weight:400;font-size:30px;line-height:1.15;color:#17221f">' . e($o['titlu']) . '</h1>
      <p class="m-text" style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#17221f">' . e($o['intro']) . '</p>
    </td></tr>

    ' . $sectiuni . '

    <!-- Butoane -->
    <tr><td class="m-card" style="padding:0 32px 26px;background:#ffffff">' . $butoane . '</td></tr>

    <!-- Contact -->
    <tr><td class="m-card m-sep" style="padding:20px 32px 28px;background:#ffffff;border-top:1px solid #e3eaea">
      <p class="m-text" style="margin:0 0 4px;font-size:15px;color:#17221f">' . e($E['semnatura']) . '<br><strong>' . e($s['nume_vila']) . '</strong></p>
      <p class="m-muted" style="margin:8px 0 0;font-size:14px;line-height:1.6;color:#5d6967">
        <a href="tel:' . e($s['telefon_link']) . '" style="color:#164a28">' . e($v['{telefon}']) . '</a>
        &nbsp;·&nbsp; <a href="https://wa.me/' . e($s['whatsapp']) . '" style="color:#164a28">WhatsApp</a>
        &nbsp;·&nbsp; <a href="mailto:' . e($s['email_contact']) . '" style="color:#164a28">' . e($s['email_contact']) . '</a><br>
        ' . e($s['adresa']) . '
      </p>
    </td></tr>

    <!-- Subsol cu firma -->
    <tr><td style="background:#0f2f1b;padding:20px 32px;color:#c3d3c8;font-size:12px;line-height:1.6">
      ' . e(vsprintf($o['T']['firma'], [$s['firma_nume'], $s['firma_cui'], $s['firma_reg_com'], $s['firma_sediu']])) . '<br>
      <a href="' . SITE_URL . '" style="color:#ffffff;text-decoration:underline">vilabusteni.ro</a>'
      . ($sociale ? ' &nbsp;·&nbsp; ' . implode(' &nbsp;·&nbsp; ', $sociale) : '') . '
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>';
}

// ------------------------------------------------------------
//  Trimiterea: HTML + text simplu (multipart/alternative)
// ------------------------------------------------------------
function html_in_text($html)
{
    $html = preg_replace('#<(style|title)[^>]*>.*?</\1>#si', '', $html);
    $html = preg_replace('#<div style="display:none[^>]*>.*?</div>#si', '', $html);
    $html = preg_replace_callback('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#si', function ($m) {
        $text = trim(strip_tags($m[2]));
        return $text === '' ? '' : $text . ' (' . $m[1] . ')';   // linkurile fără text (sigla) dispar
    }, $html);
    $html = preg_replace('#</(tr|p|h1|h2|div)>|<br\s*/?>#i', "\n", $html);
    $html = preg_replace('#</td>#i', '  ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text);
    $text = preg_replace("/\n\s*\n+/", "\n\n", $text);
    return trim(implode("\n", array_map('trim', explode("\n", $text))));
}

function trimite_mail_html($catre, $subiect, $html, array $setari, $raspuns_la)
{
    $granita = 'vr' . bin2hex(random_bytes(8));
    $antete = [
        'From: ' . mb_encode_mimeheader($setari['nume_vila'], 'UTF-8') . ' <' . $setari['email_expeditor'] . '>',
        'Reply-To: ' . $raspuns_la,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $granita . '"',
    ];
    $corp = "--$granita\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode(html_in_text($html)))
          . "--$granita\r\n"
          . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($html))
          . "--$granita--\r\n";
    $ok = @mail($catre, mb_encode_mimeheader($subiect, 'UTF-8', 'B'), $corp, implode("\r\n", $antete), '-f' . $setari['email_expeditor']);
    if (!$ok) error_log("Mailul către $catre nu a putut fi trimis.");
    return $ok;
}
