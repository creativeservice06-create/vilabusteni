<?php
// MODEL de configurare. Pe server se copiază ca "config.php" (în public_html) și se completează.
// config.php NU se pune în GitHub și nu este suprascris la deploy.
// Numele bazei și al userului au forma: userCpanel_nume (ex: vilabust_rezervari).
return [
    'db_host' => 'localhost',
    'db_name' => 'vilabust_rezervari',
    'db_user' => 'vilabust_site',
    'db_pass' => 'PAROLA_USERULUI_BAZEI_DE_DATE',

    // Parola căsuței rezervari@vilabusteni.ro (pentru trimiterea mailurilor)
    'smtp_parola' => 'PAROLA_CASUTEI_DE_MAIL',

    // Parola panoului de administrare (vilabusteni.ro/admin/) – lungă, doar a ta
    'admin_parola' => 'ALEGE_O_PAROLA_LUNGA',

    // ---------- Plata cu cardul – NETOPIA (opțional; fără chei, butonul de card nu apare) ----------
    // 'sandbox' = test (carduri de test, fără bani reali); 'live' = plăți reale.
    // ATENȚIE: cheile de test și cele live sunt diferite – la trecerea pe live le schimbi pe toate.
    'netopia_mediu'         => 'sandbox',
    'netopia_api_key'       => '',   // Netopia admin → Profil (sus-dreapta) → Securitate → generează API Key
    'netopia_pos_signature' => '',   // Netopia admin → Puncte de vânzare → punctul vilabusteni.ro → Setări tehnice (XXXX-XXXX-XXXX-XXXX-XXXX)
    // Cheia publică Netopia, pentru verificarea confirmărilor de plată: tot textul, cu tot cu -----BEGIN și -----END,
    // sau calea completă spre fișierul descărcat din Netopia (ex. '/home/vilabust/netopia/public.cer').
    'netopia_cheie_publica' => '',

    // ---------- Facturi fiscale automate – FGO (opțional) ----------
    // FGO → cheie API pentru Horoscop Travel, cu dreptul „facturi:emitere”. Începe cu fgo_api_v2...
    // Mai trebuie completate în includes/setari.php: 'fgo_serie' și 'fgo_cota_tva'.
    'fgo_cheie_api' => '',
];
