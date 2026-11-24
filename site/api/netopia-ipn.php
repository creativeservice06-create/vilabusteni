<?php
// ============================================================
//  NOTIFICAREA DE LA NETOPIA (IPN) – o apelează Netopia, nu clientul.
//  Verifică semnătura, actualizează plata și rezervarea, apoi răspunde imediat;
//  factura și mailurile se trimit după răspuns, ca Netopia să nu aștepte.
// ============================================================
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/rezervari.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function raspunde_ipn($cod_http, array $raspuns)
{
    http_response_code($cod_http);
    echo json_encode($raspuns);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    raspunde_ipn(405, ['errorType' => 2, 'errorCode' => 405, 'errorMessage' => 'POST only']);
    exit;
}

$corp  = (string) file_get_contents('php://input');
$token = $_SERVER['HTTP_VERIFICATION_TOKEN'] ?? '';

$motiv = verifica_ipn($token, $corp);
if ($motiv !== null) {
    error_log('Netopia IPN respins: ' . $motiv);
    raspunde_ipn(401, ['errorType' => 2, 'errorCode' => 401, 'errorMessage' => 'verification failed']);
    exit;
}

$mesaj = json_decode($corp, true);
$pdo = db();
if (!is_array($mesaj) || !$pdo) {
    // eroare temporară: Netopia va retrimite mesajul mai târziu
    raspunde_ipn(503, ['errorType' => 1, 'errorCode' => 503, 'errorMessage' => 'temporarily unavailable']);
    exit;
}

try {
    [$raspuns, $dupa] = proceseaza_ipn($pdo, $mesaj, $setari);
} catch (Throwable $ex) {
    error_log('Netopia IPN: ' . $ex->getMessage());
    raspunde_ipn(500, ['errorType' => 1, 'errorCode' => 500, 'errorMessage' => 'internal error']);
    exit;
}

raspunde_ipn(200, $raspuns);

if ($dupa) {
    // trimitem răspunsul către Netopia, apoi continuăm în fundal
    ignore_user_abort(true);
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } elseif (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
    }
    try {
        $dupa();
    } catch (Throwable $ex) {
        error_log('Netopia IPN (după răspuns): ' . $ex->getMessage());
    }
}
