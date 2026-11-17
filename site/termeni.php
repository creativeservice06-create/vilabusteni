<?php
// TERMENI ȘI CONDIȚII – MODEL. Textele marcate cu galben trebuie completate/verificate
// de proprietar (ideal și de un jurist) înainte de lansare.
require __DIR__ . '/includes/init.php';
$titlu = 'Termeni și condiții – ' . $setari['nume_vila'];
require __DIR__ . '/includes/header.php';
$f = $setari;
?>
<section class="interior legal">
  <h1>Termeni și condiții</h1>
  <p class="mic">Ultima actualizare: <span class="de-completat">[data]</span></p>

  <h2>1. Cine suntem</h2>
  <p>Site-ul vilabusteni.ro este administrat de <strong><?= e($f['firma_nume']) ?></strong>, CUI <?= e($f['firma_cui']) ?>,
     <?= e($f['firma_reg_com']) ?>, cu sediul în <?= e($f['firma_sediu']) ?>
     („noi”). Contact: <?= e($f['telefon']) ?>, <?= e($f['email_contact']) ?>.</p>

  <h2>2. Obiectul</h2>
  <p>Prin acest site oferim servicii de cazare în <?= e($f['nume_vila']) ?>, <?= e($f['adresa']) ?>. Vila se închiriază integral.</p>

  <h2>3. Rezervarea</h2>
  <p>Cererea trimisă prin formular nu reprezintă o rezervare fermă. Rezervarea devine fermă după confirmarea noastră
     <span class="de-completat">[și după plata avansului / a sumei totale – de stabilit]</span>.</p>
  <p>Perioada minimă de ședere este de <?= (int) $f['minim_nopti'] ?> nopți. Check-in de la <?= e($f['ora_check_in']) ?>,
     check-out până la <?= e($f['ora_check_out']) ?>. Capacitatea maximă este de <?= (int) $f['max_persoane'] ?> persoane.</p>

  <h2>4. Prețuri și plată</h2>
  <p>Prețurile afișate sunt în lei și sunt pentru întreaga vilă, pe noapte <span class="de-completat">[includ / nu includ TVA și taxa locală de ședere]</span>.
     Plata se face <span class="de-completat">[online cu cardul prin procesatorul de plăți / prin transfer bancar / la sosire]</span>.</p>

  <h2>5. Anulare și rambursare</h2>
  <p class="de-completat">[Politica de anulare: de ex. anulare gratuită cu X zile înainte de sosire; între X și Y zile se reține Z%; sub Y zile nu se rambursează. Termenul de rambursare: N zile lucrătoare.]</p>
  <p>Conform art. 16 lit. l) din OUG 34/2014, dreptul de retragere de 14 zile nu se aplică serviciilor de cazare
     prestate la o dată sau într-o perioadă anume; se aplică politica de anulare de mai sus.</p>

  <h2>6. Regulile casei</h2>
  <p class="de-completat">[Fumat, animale de companie, petreceri, liniște, garanție/depozit, răspunderea pentru daune etc.]</p>

  <h2>7. Reclamații și litigii</h2>
  <p>Reclamațiile se pot trimite la <?= e($f['email_contact']) ?>. Consumatorii se pot adresa și
     <a href="https://anpc.ro/" target="_blank" rel="noopener">ANPC</a> sau procedurii de
     <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">soluționare alternativă a litigiilor (SAL)</a>.</p>

  <h2>8. Date personale</h2>
  <p>Prelucrăm datele tale conform <a href="/confidentialitate.php">Politicii de confidențialitate</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
