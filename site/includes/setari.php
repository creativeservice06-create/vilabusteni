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
    'telefon'        => '0738 744 166',
    'telefon_link'   => '+40738744166',
    'whatsapp'       => '40738744166',
    'email_contact'  => 'contact@horoscoptravel.ro',

    // Unde ajung notificările despre rezervări noi
    'email_proprietar' => 'contact@horoscoptravel.ro',
    // Adresa de pe care pleacă mailurile automate (trebuie creată în cPanel → Email Accounts)
    'email_expeditor'  => 'rezervari@vilabusteni.ro',

    // Firma (apare în subsol și în paginile legale) – DE COMPLETAT
    'firma_nume'     => 'Horoscop Travel',
    'firma_cui'      => '[CUI de completat]',
    'firma_reg_com'  => '[Nr. Reg. Com. de completat]',
    'firma_sediu'    => '[Sediul social de completat]',

    // Tarife și reguli de rezervare
    'pret_noapte'    => 1500,   // lei / noapte, pentru toată vila
    'minim_nopti'    => 3,
    'max_persoane'   => 6,      // DE VERIFICAT cu proprietarul
    'ora_check_in'   => '15:00',
    'ora_check_out'  => '11:00',
];
