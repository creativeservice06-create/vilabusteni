<?php
// Rulare manuală sau din cron (opțional – site-ul face oricum verificarea singur):
//   /usr/local/bin/php /home/vilabust/public_html/cron/expirate.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/rezervari.php';
$pdo = db();
if (!$pdo) { fwrite(STDERR, "Baza de date nu răspunde.\n"); exit(1); }
echo 'Rezervări anulate automat: ' . anuleaza_expirate($pdo, $setari) . "\n";
