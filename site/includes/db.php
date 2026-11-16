<?php
// Conexiunea la baza de date. Datele de acces vin din config.php (doar pe server).
// Întoarce null dacă baza de date nu e încă configurată.

function db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $fisier = __DIR__ . '/../config.php';
    if (!file_exists($fisier)) {
        return null;
    }
    $c = require $fisier;

    $dsn = $c['db_dsn'] ?? "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $c['db_user'] ?? null, $c['db_pass'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $ex) {
        error_log('Eroare conectare DB: ' . $ex->getMessage());
        return null;
    }
    return $pdo;
}

/**
 * Nopțile ocupate (rezervări confirmate), ca listă de date "YYYY-MM-DD".
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
                        WHERE status = 'confirmata' AND plecare >= " . ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "date('now')" : 'CURDATE()'))
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
