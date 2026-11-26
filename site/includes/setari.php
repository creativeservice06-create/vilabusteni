<?php
// ============================================================
//  SETĂRILE SITE-ULUI – tot ce se schimbă des e AICI, într-un singur loc.
//  (Parolele bazei de date NU sunt aici, ci în config.php de pe server.)
// ============================================================
return [
    // Identitate
    'nume_vila'      => 'Vila Rândunica',
    'zona'           => 'Poiana Țapului, Bușteni',
    'adresa'         => 'Strada Șoimului nr. 4, Bușteni, Prahova',
    'harta_link'     => 'https://www.google.com/maps/search/?api=1&query=Strada+%C8%98oimului+4%2C+Bu%C8%99teni%2C+Prahova%2C+Rom%C3%A2nia',

    // Contact
    'telefon'        => '0728 986 882',
    'telefon_int'    => '+40 728 986 882',   // afișat pe versiunile în alte limbi
    'telefon_link'   => '+40728986882',
    'whatsapp'       => '40728986882',
    'email_contact'  => 'rezervari@vilabusteni.ro',

    // Mailurile: totul pleacă de pe rezervari@vilabusteni.ro, prin serverul de mail al domeniului.
    // Parola NU e aici – se pune în config.php pe server: 'smtp_parola' => '...'
    'smtp_server'    => 'mail.vilabusteni.ro',
    'smtp_port'      => 465,                         // SSL
    'smtp_user'      => 'rezervari@vilabusteni.ro', // de pe ea pleacă mailurile

    // Unde ajung notificările despre rezervări (mai multe adrese: separate prin virgulă)
    'email_proprietar' => 'rezervari@vilabusteni.ro, contact@horoscoptravel.ro',
    // Copie ascunsă a fiecărui mail trimis clienților, ca istoric (gol = fără copie)
    'email_arhiva'     => '',
    // Alte notificări pentru proprietar (true = se trimit, false = nu). Rezervarea nouă se trimite mereu.
    'notifica_dovada'        => true,   // clientul a trimis dovada de plată (cu fișierul atașat)
    'notifica_anulare_auto'  => true,   // o rezervare s-a anulat automat (avans neplătit în 48h)
    // Pixelul Meta (Facebook Ads). Se activează doar după acceptul vizitatorului în banner.
    'meta_pixel'             => '1125358963706140',
    // Doar dacă serverul de mail nu răspunde: mailul pleacă de pe adresa site-ului
    'email_expeditor_rezerva' => 'rezervari@vilabusteni.ro',

    // Firma (apare în subsol și în paginile legale)
    'firma_nume'     => 'Horoscop Travel S.R.L.',
    'firma_marca'    => 'Enjoy Travel',
    'firma_cui'      => '49488410',
    'firma_reg_com'  => 'J40/1696/2024',
    'firma_sediu'    => 'București, Sector 4, Str. Concordiei nr. 7, camera 4',

    // Rețele sociale – un link gol ("") nu apare pe site
    'social' => [
        'facebook'    => 'https://www.facebook.com/profile.php?id=61596796554096',
        'instagram'   => '',   // DE COMPLETAT
        'tiktok'      => 'https://www.tiktok.com/@horoscop.travel',
        'youtube'     => 'https://www.youtube.com/@horoscoptravel',
        'tripadvisor' => '',   // DE COMPLETAT
    ],

    // Plata prin transfer bancar (apare pe proformă și în mailuri)
    'iban'           => '',     // DE COMPLETAT, ex: 'RO49 AAAA 1B31 0075 9384 0000'
    'banca'          => '',     // DE COMPLETAT, ex: 'Banca Transilvania'
    'avans_procent'  => 50,     // avansul care confirmă rezervarea (clientul alege: avansul sau integral)
    'termen_plata_ore' => 48,   // plata prin ordin de plată: cât timp stau zilele rezervate, așteptând banii
    'termen_card_minute' => 60, // plata cu cardul: dacă nu plătește în acest timp, zilele se eliberează (fără mail)
    'serie_proforma' => 'VR',   // proformele se numerotează VR-0001, VR-0002 ...

    // Plata cu cardul prin Netopia. Cheile NU sunt aici, ci în config.php pe server
    // ('netopia_api_key', 'netopia_pos_signature', 'netopia_cheie_publica', 'netopia_mediu').
    // Butonul „Plătește cu cardul” apare singur când cheile sunt completate.
    'plata_card'       => true,    // false = ascunde plata cu cardul chiar dacă există chei

    // Facturi fiscale automate prin FGO (după fiecare plată). Cheia API NU e aici: 'fgo_cheie_api' în config.php.
    // Facturarea pornește doar când sunt completate cheia, seria și cota de TVA.
    'fgo_serie'        => '',      // DE COMPLETAT: seria de facturi definită în FGO (Setări → Serii documente)
    'fgo_cota_tva'     => null,    // DE COMPLETAT cu contabilul: 0 dacă firma NU e plătitoare de TVA, 0.11 dacă este (cazare = 11%)
    'fgo_tip_factura'  => 'F',     // F = factură
    'fgo_um'           => 'buc',   // unitatea de măsură de pe factură
    'fgo_la_transfer'  => true,    // true = emite factura și când confirmi în panou o plată prin transfer

    // Parola panoului de administrare NU e aici: se pune în config.php pe server ('admin_parola').

    // Tarife și reguli de rezervare
    'pret_noapte'    => 1500,   // lei / noapte, pentru toată vila
    'minim_nopti'    => 3,
    'max_persoane'   => 6,      // DE VERIFICAT cu proprietarul
    'ora_check_in'   => '15:00',
    'ora_check_out'  => '11:00',
];
