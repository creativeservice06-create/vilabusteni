<?php
// ============================================================
//  VERIFICAREA AUTOMATĂ A TERMENELOR DE PLATĂ
//  Rulează discret, după ce pagina a fost deja trimisă vizitatorului,
//  cel mult o dată la 5 minute (oricine intră pe site o declanșează).
//  Anulează cererile neplătite în termen și trimite mailurile (vezi anuleaza_expirate).
//  Opțional, aceeași verificare poate rula și din cron: cron/expirate.php
// ============================================================

function porneste_verificarea_automata()
{
    $fisier = sys_get_temp_dir() . '/vilabusteni-verificare-' . md5(__DIR__);
    if (is_file($fisier) && time() - filemtime($fisier) < 300) {
        return; // s-a verificat în ultimele 5 minute
    }
    @touch($fisier);

    register_shutdown_function(function () {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request(); // vizitatorul primește pagina, verificarea continuă în fundal
        }
        try {
            global $setari;
            require_once __DIR__ . '/db.php';
            require_once __DIR__ . '/rezervari.php';
            if ($pdo = db()) {
                anuleaza_expirate($pdo, $setari);
            }
        } catch (Throwable $ex) {
            error_log('Verificarea automată a eșuat: ' . $ex->getMessage());
        }
    });
}
