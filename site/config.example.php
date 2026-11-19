<?php
// MODEL de configurare. Pe server se copiază ca "config.php" (în public_html) și se completează.
// config.php NU se pune în GitHub și nu este suprascris la deploy.
// Numele bazei și al userului au forma: userCpanel_nume (ex: vilabust_rezervari).
return [
    'db_host' => 'localhost',
    'db_name' => 'vilabust_rezervari',
    'db_user' => 'vilabust_site',
    'db_pass' => 'PAROLA_USERULUI_BAZEI_DE_DATE',

    // Parola căsuței contact@horoscoptravel.ro (pentru trimiterea mailurilor)
    'smtp_parola' => 'PAROLA_CASUTEI_DE_MAIL',

    // Parola panoului de administrare (vilabusteni.ro/admin/) – lungă, doar a ta
    'admin_parola' => 'ALEGE_O_PAROLA_LUNGA',
];
