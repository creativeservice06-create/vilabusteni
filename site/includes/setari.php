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

    // Unde ajung notificările despre rezervări noi
    'email_proprietar' => 'contact@horoscoptravel.ro',
    // Adresa de pe care pleacă mailurile automate (trebuie creată în cPanel → Email Accounts)
    'email_expeditor'  => 'rezervari@vilabusteni.ro',

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

    // Tarife și reguli de rezervare
    'pret_noapte'    => 1500,   // lei / noapte, pentru toată vila
    'minim_nopti'    => 3,
    'max_persoane'   => 6,      // DE VERIFICAT cu proprietarul
    'ora_check_in'   => '15:00',
    'ora_check_out'  => '11:00',
];
