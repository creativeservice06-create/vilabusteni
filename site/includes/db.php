<?php
// Conexiunea la baza de date. Datele de acces vin din config.php (doar pe server).
// Întoarce null dacă baza de date nu e încă configurată.

// Motivul ultimei erori de conectare (fără date sensibile), pentru diagnostic.
$GLOBALS['db_eroare'] = null;

function db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $fisier = __DIR__ . '/../config.php';
    if (!file_exists($fisier)) {
        $GLOBALS['db_eroare'] = 'config.php lipsește din public_html';
        return null;
    }
    $c = require $fisier;

    $dsn = $c['db_dsn'] ?? "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $c['db_user'] ?? null, $c['db_pass'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Creează/actualizează automat tabelele (vezi includes/migrari.php)
        require_once __DIR__ . '/migrari.php';
        aplica_migrari($pdo);
    } catch (PDOException $ex) {
        error_log('Eroare conectare DB: ' . $ex->getMessage());
        $coduri = [1045 => 'acces refuzat (user sau parolă greșită)', 1044 => 'userul nu are drepturi pe bază',
                   1049 => 'baza de date nu există (verifică numele)', 2002 => 'serverul MySQL nu răspunde'];
        $cod = $ex->errorInfo[1] ?? (int) $ex->getCode();
        $GLOBALS['db_eroare'] = 'cod ' . $cod . ': ' . ($coduri[$cod] ?? 'altă eroare');
        $pdo = null;
        return null;
    }
    return $pdo;
}

/**
 * Condiția SQL pentru o rezervare care ține zilele ocupate:
 * confirmată, zile blocate manual, sau cerere care așteaptă plata și nu i-a expirat termenul.
 */
function conditie_ocupat()
{
    $acum = date('Y-m-d H:i:s');
    return "(status IN ('confirmata', 'blocat') OR (status = 'in_asteptare' AND termen_plata > '$acum'))";
}

/**
 * Nopțile ocupate (confirmate, blocate manual, cereri care așteaptă plata), ca listă de date "YYYY-MM-DD".
 * O noapte = data sosirii, până în ziua dinaintea plecării.
 */
function nopti_ocupate()
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    try {
        $rez = $pdo->query("SELECT sosire, plecare FROM rezervari
                        WHERE " . conditie_ocupat() . " AND plecare >= " . ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "date('now')" : 'CURDATE()'))
               ->fetchAll();
    } catch (PDOException $ex) {
        error_log('Eroare citire rezervări: ' . $ex->getMessage());
        return [];
    }

    $nopti = [];
    foreach ($rez as $r) {
        $zi = new DateTime($r['sosire']);
        $final = new DateTime($r['plecare']);
        while ($zi < $final) {
            $nopti[] = $zi->format('Y-m-d');
            $zi->modify('+1 day');
        }
    }
    return array_values(array_unique($nopti));
}
