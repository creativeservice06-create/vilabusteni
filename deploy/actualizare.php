<?php
// ============================================================
//  ACTUALIZARE AUTOMATĂ DIN GITHUB
//  Rulează pe server prin cron job (la câteva minute).
//  Dacă pe GitHub (ramura main) e o versiune nouă, descarcă folderul "site/"
//  și îl copiază în public_html. Fișierele șterse din GitHub se șterg și de pe server.
//  config.php din public_html NU este atins niciodată.
//
//  Se pune pe server în /home/USER/deploy/ (în afara public_html).
//  Tokenul GitHub (doar pentru repository privat) se pune în deploy/token.txt.
// ============================================================

$REPO    = 'creativeservice06-create/vilabusteni';
$RAMURA  = 'main';
$SURSA   = 'site/';                                  // folderul din repository care se publică
$DEST    = dirname(__DIR__) . '/public_html';        // unde se publică
$PASTRAT = ['config.php', 'cgi-bin', '.well-known']; // nu se ating niciodată
$STARE   = __DIR__ . '/stare.json';
$JURNAL  = __DIR__ . '/jurnal.log';

// -------------------------------------------------------------

function jurnal($mesaj)
{
    global $JURNAL;
    $linie = date('Y-m-d H:i:s') . '  ' . $mesaj . "\n";
    file_put_contents($JURNAL, $linie, FILE_APPEND);
    if (PHP_SAPI === 'cli') echo $linie;
}

function cerere($url, $fisier_destinatie = null)
{
    $token = @trim((string) file_get_contents(__DIR__ . '/token.txt'));
    $antete = ['User-Agent: vilabusteni-deploy', 'Accept: application/vnd.github+json'];
    if ($token !== '') $antete[] = 'Authorization: Bearer ' . $token;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $antete,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_RETURNTRANSFER => $fisier_destinatie === null,
    ]);
    if ($fisier_destinatie !== null) {
        $fp = fopen($fisier_destinatie, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
    }
    $rezultat = curl_exec($ch);
    $cod = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $eroare = curl_error($ch);
    curl_close($ch);
    if (isset($fp)) fclose($fp);

    if ($rezultat === false || $cod >= 400) {
        throw new RuntimeException("Cererea către GitHub a eșuat ($cod) $eroare");
    }
    return $rezultat;
}

function e_pastrat($cale)
{
    global $PASTRAT;
    $primul = explode('/', $cale)[0];
    return in_array($cale, $PASTRAT, true) || in_array($primul, $PASTRAT, true);
}

// Un singur proces o dată
$lacat = fopen(__DIR__ . '/.lacat', 'c');
if (!flock($lacat, LOCK_EX | LOCK_NB)) exit;

try {
    $stare = is_file($STARE) ? json_decode(file_get_contents($STARE), true) : ['sha' => '', 'fisiere' => []];

    // 1. Care e ultima versiune pe GitHub?
    $commit = json_decode(cerere("https://api.github.com/repos/$REPO/commits/$RAMURA"), true);
    $sha = $commit['sha'] ?? '';
    if ($sha === '') throw new RuntimeException('Nu am putut citi ultima versiune.');
    if ($sha === $stare['sha'] && !in_array('--fortat', $argv ?? [], true)) exit; // nimic nou

    // 2. Descarcă arhiva acelei versiuni
    $zip_cale = sys_get_temp_dir() . '/vilabusteni-' . $sha . '.zip';
    cerere("https://api.github.com/repos/$REPO/zipball/$sha", $zip_cale);

    $zip = new ZipArchive();
    if ($zip->open($zip_cale) !== true) throw new RuntimeException('Arhiva descărcată nu e validă.');

    // 3. Copiază fișierele din site/ în public_html
    $noi = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $nume = $zip->getNameIndex($i);                  // ex: "owner-repo-abc123/site/index.php"
        $parti = explode('/', $nume, 2);
        if (!isset($parti[1]) || strpos($parti[1], $SURSA) !== 0) continue;
        $relativ = substr($parti[1], strlen($SURSA));
        if ($relativ === '' || substr($relativ, -1) === '/') continue; // foldere
        if (strpos($relativ, '..') !== false || e_pastrat($relativ)) continue;

        $tinta = $DEST . '/' . $relativ;
        if (!is_dir(dirname($tinta))) mkdir(dirname($tinta), 0755, true);
        $continut = $zip->getFromIndex($i);
        if (!is_file($tinta) || md5_file($tinta) !== md5($continut)) {
            file_put_contents($tinta . '.nou', $continut);
            rename($tinta . '.nou', $tinta);
        }
        $noi[] = $relativ;
    }
    $zip->close();
    unlink($zip_cale);

    if (count($noi) === 0) throw new RuntimeException('Arhiva nu conține fișiere în ' . $SURSA);

    // 4. Șterge de pe server fișierele care au fost șterse din GitHub
    $sterse = 0;
    foreach (array_diff($stare['fisiere'], $noi) as $vechi) {
        if (!e_pastrat($vechi) && is_file($DEST . '/' . $vechi)) {
            unlink($DEST . '/' . $vechi);
            $sterse++;
        }
    }

    file_put_contents($STARE, json_encode(['sha' => $sha, 'fisiere' => $noi, 'data' => date('c')], JSON_PRETTY_PRINT));
    jurnal('Actualizat la ' . substr($sha, 0, 7) . ' – ' . count($noi) . " fișiere, $sterse șterse. " .
           trim(strtok($commit['commit']['message'] ?? '', "\n")));
} catch (Throwable $ex) {
    jurnal('EROARE: ' . $ex->getMessage());
}
