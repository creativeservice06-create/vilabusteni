<?php
// TEXTELE SITE-ULUI ÎN ROMÂNĂ
// Fiecare limbă are același fișier, cu aceleași chei (stânga) și textul tradus (dreapta).
// %s și %d se înlocuiesc automat (prețuri, numere, date) – lasă-le pe loc când modifici.
return [
    // Generale
    'moneda'          => 'lei',
    'meta_titlu'      => 'Vila Rândunica – cazare în Poiana Țapului, Bușteni',
    'meta_descriere'  => 'Vila Rândunica din Poiana Țapului, Bușteni: casă din 1925 cu terasă spre Bucegi. 3 camere cu baie proprie, mansardă. Se închiriază integral.',
    'sari'            => 'Sari la conținut',
    'acasa_aria'      => 'prima pagină',
    'limba'           => 'Limba',

    // Meniu
    'meniu'           => 'Meniu',
    'meniu_povestea'  => 'Povestea',
    'meniu_casa'      => 'Casa',
    'meniu_galerie'   => 'Galerie',
    'meniu_zona'      => 'Zona',
    'meniu_contact'   => 'Contact',
    'meniu_rezerva'   => 'Rezervă',

    // Sus
    'hero_alt'        => 'Munții Bucegi văzuți din curtea vilei',
    'hero_motto'      => 'Din 1925, cu fața spre Bucegi.',
    'buton_date'      => 'Vezi datele libere',
    'buton_suna'      => 'Sună: %s',

    // Pe scurt
    'pe_scurt'        => 'Pe scurt',
    'scurt_1_titlu'   => 'Toată vila',
    'scurt_1_text'    => 'doar pentru voi',
    'scurt_2_titlu'   => '3 camere',
    'scurt_2_text'    => 'fiecare cu baia ei, plus mansardă',
    'scurt_3_text'    => 'pe noapte, minimum %d nopți',

    // Povestea
    'povestea_titlu'  => 'Casa care te face să mai rămâi o clipă',
    'povestea_intro'  => 'Pe terasă, lumina prinde mai întâi crestele Bucegilor. Mai jos, Poiana Țapului rămâne încă în umbră. Te sprijini de balustrada de lemn și, pentru o vreme, uiți să te uiți la ceas.',
    'cronologie'      => [
        ['1925', 'Vila e ridicată între Bușteni și Sinaia. Castelul Peleș, reședința de vară a regelui Carol I, face parte din viața Familiei Regale și găzduiește vizite oficiale.'],
        ['Mai târziu', 'Casa devine locuință de protocol. Sub arcade îți poți închipui pași pe treptele de piatră și conversații mutate pe terasă, care țineau mult după apus.'],
        ['Azi', 'Își poartă și acum numele din acte. Când cobori treptele spre curte, întorci încă o dată capul spre munți. Poate de aceea i se potrivește atât de bine numele de Rândunica.'],
    ],

    // Casa
    'casa_titlu'      => 'Casa, cameră cu cameră',
    'casa_text'       => 'Se închiriază integral: trei camere cu baie proprie, mansardă, living, sufragerie, bucătărie, terasă și curte.',
    'spatii'          => [   // în ordinea pozelor din index.php
        ['Terasa',       'De aici se vede toată creasta, de la primele raze până după apus.'],
        ['Livingul',     'Șemineu, fotolii adânci și scara de lemn spre mansardă.'],
        ['Sufrageria',   'Masa rotundă, bufetul vechi și ferestre spre curte.'],
        ['Dormitoarele', 'Trei camere, fiecare cu baia ei.'],
        ['Bucătăria',    'Aragaz pe gaz, cuptor, cuptor cu microunde și tot ce trebuie pentru gătit.'],
    ],
    'dotari'          => [   // în ordinea iconițelor din index.php
        'Garaj',
        'Stație de încărcare pentru mașini electrice',
        'Șemineu în living',
        'Bucătărie complet utilată',
        'Încălzire centrală pe gaz',
        'Terasă cu vedere spre Bucegi',
    ],

    // Preț
    'pret_titlu'      => '%s pe noapte, pentru toată vila',
    'pret_text'       => 'Minimum %d nopți, adică %s pentru un weekend prelungit. Check-in de la %s, check-out până la %s.',
    'buton_alege'     => 'Alege datele',
    'buton_intreaba'  => 'Întreabă pe WhatsApp',

    // Galerie
    'galerie_titlu'   => 'Galerie',
    'galerie_indiciu' => 'Glisează pentru mai multe poze',
    'galerie'         => [   // în ordinea pozelor din index.php
        'Bucegii văzuți din curte',
        'Fațada cu arcade și treptele de piatră',
        'Terasa',
        'Livingul cu șemineu',
        'Sufrageria',
        'Dormitor',
        'Dormitor',
        'Bucătăria',
        'Creasta, într-o zi senină',
    ],
    'poza_marita'     => 'Poză mărită',
    'inchide'         => 'Închide',
    'poza_anterioara' => 'Poza anterioară',
    'poza_urmatoare'  => 'Poza următoare',
    'din'             => 'din',

    // Unde e
    'locul_alt'       => 'Vila Rândunica văzută din curte',
    'locul_titlu'     => 'În Poiana Țapului, pe Valea Prahovei',
    'locul_text'      => 'Între Bușteni și Sinaia, aproape de Castelul Peleș, de telecabina spre Babele și de traseele din Bucegi.',
    'buton_harta'     => 'Deschide în Google Maps',

    // Ce vezi în zonă
    'zona_titlu'      => 'Ce vezi în zonă',
    'zona_text'       => 'Locuri pe care le poți vizita într-o zi, plecând de la vilă.',
    'vezi_pe_harta'   => 'Vezi pe hartă',
    'obiective'       => [   // [nume, descriere] – în ordinea din index.php
        ['Cascada Urlătoarea', 'Una dintre cele mai cunoscute cascade de pe Valea Prahovei, la capătul unui traseu ușor prin pădure care pornește chiar din Poiana Țapului.'],
        ['Castelul Cantacuzino', 'Castel în stil neoromânesc din Bușteni, ridicat la începutul secolului XX de prințul Gheorghe Grigore Cantacuzino, cu o terasă spre Bucegi.'],
        ['Mănăstirea Caraiman', 'Mănăstire ortodoxă la poalele muntelui Caraiman, într-un loc liniștit, la marginea pădurii.'],
        ['Telecabina Bușteni', 'Urcă din Bușteni direct pe Platoul Bucegi, la Babele, fără drumeție.'],
        ['Platoul Bucegi', 'Babele și Sfinxul, stâncile modelate de vânt, și Crucea Eroilor de pe Caraiman, cea pe care o vezi și de pe terasa vilei.'],
        ['Vârful Omu și Valea Cerbului', 'Cel mai înalt vârf din Bucegi, la 2.505 m. Valea Cerbului, unul dintre traseele clasice spre el, pornește din Bușteni.'],
    ],

    // Subsol
    'sigla_alt'       => 'Vila Rândunica, Poiana Țapului, Bușteni',
    'marca'           => 'Vila Rândunica este o marcă %s.',
    'contact'         => 'Contact',
    'telefon'         => 'Telefon',
    'whatsapp'        => 'WhatsApp',
    'email'           => 'Email',
    'adresa'          => 'Adresa',
    'info_legale'     => 'Informații legale',
    'rezervare'       => 'Rezervare',
    'termeni'         => 'Termeni și condiții',
    'confidentialitate' => 'Politica de confidențialitate',
    'cookies'         => 'Politica de cookie-uri',
    'anpc_sal'        => 'ANPC – soluționarea alternativă a litigiilor',
    'firma'           => '%s, CUI %s, Nr. Reg. Com. %s, sediul: %s',
    'contact_rapid'   => 'Contact rapid',
    'suna'            => 'Sună',
    'legal_doar_ro'   => '',

    // Rezervare
    'rez_titlu'       => 'Rezervare',
    'rez_h1'          => 'Alege datele',
    'rez_intro'       => 'Toată vila costă %s pe noapte, minimum %d nopți. Atinge ziua sosirii, apoi ziua plecării. Zilele tăiate sunt ocupate.',
    'f_nume'          => 'Nume și prenume',
    'f_email'         => 'Email',
    'f_telefon'       => 'Telefon',
    'f_persoane'      => 'Număr de persoane',
    'f_mesaj'         => 'Mesaj (opțional)',
    'f_accept'        => 'Am citit și accept <a href="%s" target="_blank">Termenii și condițiile</a> și <a href="%s" target="_blank">Politica de confidențialitate</a>.',
    'f_trimite'       => 'Trimite cererea de rezervare',
    'f_nota'          => 'Rezervarea devine fermă după confirmarea noastră. Plata online cu cardul va fi disponibilă în curând.',
    'rez_multumim'    => 'Mulțumim! Am primit cererea ta.',
    'rez_perioada'    => 'Perioada: <strong>%s – %s</strong> (%d nopți), total <strong>%s</strong>.',
    'rez_contactam'   => 'Te contactăm în cel mai scurt timp pentru confirmare. Ți-am trimis și un email la <strong>%s</strong>.',
    'rez_intrebari'   => 'Pentru orice întrebare: %s.',
    'rez_inapoi'      => 'Înapoi la prima pagină',

    // Erori formular
    'err_sesiune'     => 'Sesiunea a expirat. Te rugăm să reîncerci.',
    'err_robot'       => 'Cererea nu a putut fi trimisă.',
    'err_date'        => 'Alege în calendar data sosirii și data plecării.',
    'err_trecut'      => 'Data sosirii nu poate fi în trecut.',
    'err_minim'       => 'Perioada minimă este de %d nopți.',
    'err_lung'        => 'Pentru sejururi mai lungi de 60 de nopți te rugăm să ne suni.',
    'err_nume'        => 'Completează numele.',
    'err_email'       => 'Adresa de email nu este validă.',
    'err_telefon'     => 'Numărul de telefon nu este valid.',
    'err_persoane'    => 'Număr de persoane invalid.',
    'err_accept'      => 'Trebuie să accepți Termenii și condițiile.',
    'err_indisponibil'=> 'Rezervările online sunt momentan indisponibile. Te rugăm să ne suni la %s.',
    'err_ocupat'      => 'Perioada aleasă nu mai este disponibilă. Te rugăm să alegi alte date.',
    'err_tehnic'      => 'A apărut o eroare tehnică. Te rugăm să ne suni la %s.',

    // Calendar (în {acolade} se pun automat valorile)
    'calendar'        => [
        'luni'     => ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
        'luni_mici'=> ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'],
        'zile'     => ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'],
        'prev'     => 'Luna anterioară',
        'next'     => 'Luna următoare',
        'start'    => 'Alege data sosirii, apoi data plecării.',
        'sosire'   => 'Sosire: <strong>{data}</strong>. Acum alege data plecării (minimum {minim} nopți).',
        'putin'    => 'Ai ales {n} {nopti}. Perioada minimă este de <strong>{minim} nopți</strong>.',
        'noapte'   => 'noapte',
        'nopti'    => 'nopți',
        'total'    => '{n} nopți × {pret} = ',
        'locale'   => 'ro-RO',
    ],

    // Emailul către client
    'mail_subiect'    => 'Am primit cererea ta – %s',
    'mail_corp'       => "Bună ziua, {nume},\n\n"
                       . "Am primit cererea ta de rezervare la {vila}.\n\n"
                       . "Perioada: {perioada} ({nopti} nopți)\n"
                       . "Persoane: {persoane}\n"
                       . "Total estimat: {total}\n"
                       . "Check-in de la {check_in}, check-out până la {check_out}.\n\n"
                       . "Rezervarea devine fermă după confirmarea noastră. Te contactăm în cel mai scurt timp.\n\n"
                       . "Adresa: {adresa}\n"
                       . "Telefon: {telefon}\n\n"
                       . "Cu drag,\n{vila}\n",

    // Mailurile trimise din panoul de administrare (în {acolade} se pun automat valorile)
    'mail_conf_subiect' => "Rezervare confirmată – %s",
    'mail_conf_corp' => "Bună ziua, {nume},\n\nRezervarea ta #{id} la {vila} este confirmată. Te așteptăm!\n\nPerioada: {perioada} ({nopti} nopți)\nPersoane: {persoane}\nTotal: {total}\nCheck-in de la {check_in}, check-out până la {check_out}.\n\nAdresa: {adresa}\nHartă: {harta}\nTelefon: {telefon}\n{plata}{anulare}\nCu drag,\n{vila}\n",
    'mail_plata_avans' => "\nPLATA\nAvans: {avans} ({procent}% din total), de plătit în {zile} zile prin transfer bancar.\nRestul de {rest} se achită la sosire.\n{banca}",
    'mail_plata_tot' => "\nPLATA\nSuma de {total} se plătește în {zile} zile prin transfer bancar.\n{banca}",
    'mail_banca' => "Titular: {titular}\nIBAN: {iban}\nBanca: {nume_banca}\nLa detalii plată scrie: Rezervare #{id}\n",
    'mail_anulare_titlu' => "POLITICA DE ANULARE",
    'politica_anulare' => "",   // DE COMPLETAT: textul politicii de anulare (gol = nu apare în mail)
    'mail_anul_subiect' => "Perioada nu mai este disponibilă – %s",
    'mail_anul_corp' => "Bună ziua, {nume},\n\nNe pare rău, dar perioada {perioada} nu mai este disponibilă la {vila}, așa că nu putem confirma cererea ta #{id}.\n\nPoți alege alte date aici: {link}\nSau sună-ne la {telefon} și găsim împreună o variantă.\n\nCu drag,\n{vila}\n",
];
