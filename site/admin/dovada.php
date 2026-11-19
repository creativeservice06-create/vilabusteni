<?php
// Afișează dovada de plată a unei rezervări – doar pentru cine e logat în panou.
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/rezervari.php';

header('X-Robots-Tag: noindex, nofollow');
if (empty($_SESSION['admin'])) {
    header('Location: ./');
    exit;
}
$pdo = db();
$st = $pdo ? $pdo->prepare("SELECT dovada_fisier FROM rezervari WHERE id = ?") : null;
$fisier = null;
if ($st) {
    $st->execute([(int) ($_GET['id'] ?? 0)]);
    $fisier = $st->fetchColumn();
}
$cale = $fisier ? folder_dovezi() . '/' . basename($fisier) : '';
if (!$fisier || !is_file($cale)) {
    http_response_code(404);
    exit('Dovada nu a fost găsită.');
}
$tipuri = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
header('Content-Type: ' . ($tipuri[pathinfo($cale, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . basename($cale) . '"');
header('Content-Length: ' . filesize($cale));
header('Cache-Control: private, no-store');
readfile($cale);
