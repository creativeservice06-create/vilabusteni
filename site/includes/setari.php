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
    'email_contact'  => 'contact@horoscoptravel.ro',

    // Mailurile: totul pleacă de pe contact@horoscoptravel.ro, prin serverul ei de mail.
    // Parola NU e aici – se pune în config.php pe server: 'smtp_parola' => '...'
    'smtp_server'    => 'mail.horoscoptravel.ro',
    'smtp_port'      => 465,                         // SSL
    'smtp_user'      => 'contact@horoscoptravel.ro', // de pe ea pleacă mailurile

    // Unde ajung notificările despre rezervări (mai multe adrese: separate prin virgulă)
    'email_proprietar' => 'contact@horoscoptravel.ro',
    // Copie ascunsă a fiecărui mail trimis clienților, ca istoric (gol = fără copie)
    'email_arhiva'     => 'contact@horoscoptravel.ro',
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
    'avans_procent'  => 30,     // avansul care confirmă rezervarea (100 = se plătește tot)
    'termen_plata_ore' => 48,   // cât timp stau zilele rezervate provizoriu, așteptând plata
    'serie_proforma' => 'VR',   // proformele se numerotează VR-0001, VR-0002 ...

    // Parola panoului de administrare NU e aici: se pune în config.php pe server ('admin_parola').

    // Tarife și reguli de rezervare
    'pret_noapte'    => 1500,   // lei / noapte, pentru toată vila
    'minim_nopti'    => 3,
    'max_persoane'   => 6,      // DE VERIFICAT cu proprietarul
    'ora_check_in'   => '15:00',
    'ora_check_out'  => '11:00',
];
