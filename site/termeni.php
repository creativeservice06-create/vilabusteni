<?php
// TERMENI ȘI CONDIȚII – MODEL. Textele marcate cu galben trebuie completate/verificate
// de proprietar (ideal și de un jurist) înainte de lansare.
require __DIR__ . '/includes/init.php';
$titlu = t('termeni') . ' – ' . $setari['nume_vila'];
require __DIR__ . '/includes/header.php';
$f = $setari;
?>
<section class="interior legal" lang="ro">
  <h1><?= e(t('termeni')) ?></h1>
  <?php if (t('legal_doar_ro')): ?><p class="nota-limba" lang="<?= e($LIMBA) ?>"><?= e(t('legal_doar_ro')) ?></p><?php endif; ?>
  <p class="mic">Ultima actualizare: <span class="de-completat">[data]</span></p>

  <h2>1. Cine suntem</h2>
  <p>Site-ul vilabusteni.ro este administrat de <strong><?= e($f['firma_nume']) ?></strong>, CUI <?= e($f['firma_cui']) ?>,
     <?= e($f['firma_reg_com']) ?>, cu sediul în <?= e($f['firma_sediu']) ?>
     („noi”). Contact: <?= e($f['telefon']) ?>, <?= e($f['email_contact']) ?>.</p>
  <p><strong>Rolul nostru:</strong> <?= e($f['firma_nume']) ?> este prestatorul direct al serviciului de cazare (comerciant):
     vindem serviciul în nume propriu, încasăm plata și emitem factura fiscală. Nu acționăm ca intermediar sau marketplace.
     <?= e($f['nume_vila']) ?> este o marcă <?= e($f['firma_marca']) ?>.</p>

  <h2>2. Obiectul</h2>
  <p>Prin acest site oferim servicii de cazare în <?= e($f['nume_vila']) ?>, <?= e($f['adresa']) ?>. Vila se închiriază integral.</p>

  <h2>3. Rezervarea</h2>
  <p>Cererea trimisă prin formular nu reprezintă o rezervare fermă. Rezervarea devine fermă după confirmarea noastră
     <span class="de-completat">[și după plata avansului / a sumei totale – de stabilit]</span>.</p>
  <p>Perioada minimă de ședere este de <?= (int) $f['minim_nopti'] ?> nopți. Check-in de la <?= e($f['ora_check_in']) ?>,
     check-out până la <?= e($f['ora_check_out']) ?>. Capacitatea maximă este de <?= (int) $f['max_persoane'] ?> persoane.</p>

  <h2>4. Prețuri și plată</h2>
  <p>Prețurile afișate sunt în lei și sunt pentru întreaga vilă, pe noapte <span class="de-completat">[includ / nu includ TVA și taxa locală de ședere]</span>.</p>
  <p>Pentru confirmarea rezervării se plătește, în <?= (int) $f['termen_plata_ore'] ?> de ore de la trimiterea cererii, cel puțin
     <?= (int) $f['avans_procent'] ?>% din valoarea sejurului (sau o sumă mai mare, ori integral). Restul se plătește
     la sosire. Dacă plata nu este primită în termen, cererea se anulează automat.</p>
  <p>Plata se poate face:</p>
  <ul>
    <li><strong>online, cu cardul</strong> (Visa, Mastercard), pe pagina securizată a procesatorului de plăți
      <strong>NETOPIA Payments</strong> (NETOPIA Financial Services S.A.). Datele cardului se introduc doar pe pagina NETOPIA
      și nu ajung la noi. Plata se face în lei (RON); dacă ai un card în altă monedă, conversia o face banca ta.
      Rezervarea se confirmă automat după reușita plății;</li>
    <li><strong>prin transfer bancar</strong>, în contul din factura proformă; rezervarea se confirmă după ce primim banii sau dovada plății.</li>
  </ul>
  <p>Factura fiscală se emite după încasare, pe numele și adresa (sau firma) completate în formularul de rezervare, și se trimite pe email.</p>

  <h2 id="livrare">5. Prestarea serviciului („livrarea”)</h2>
  <p>Nu livrăm produse fizice. Serviciul cumpărat este cazarea în <?= e($f['nume_vila']) ?>, <?= e($f['adresa']) ?>, în perioada rezervată.
     După confirmarea plății primești pe email confirmarea rezervării, cu adresa, orele de check-in (de la <?= e($f['ora_check_in']) ?>)
     și check-out (până la <?= e($f['ora_check_out']) ?>) și datele de contact. Accesul în vilă se face la sosire, pe baza rezervării confirmate.
     Nu există costuri de livrare.</p>

  <h2 id="anulare">6. Anulare și rambursare</h2>
  <p>Rezervarea se poate anula oricând înainte de sosire, din pagina
     <a href="<?= e(url('/retragere.php')) ?>">Retrageți-vă din contract aici</a>, pe email sau telefonic.</p>
  <ul>
    <li><strong>Avansul de <?= (int) $f['avans_procent'] ?>%</strong> din valoarea sejurului <strong>nu se returnează</strong> la anularea făcută de client, indiferent de data anulării.</li>
    <li>Orice sumă plătită <strong>peste acest avans</strong> (de exemplu, dacă ai plătit integral) se returnează integral.</li>
    <li>Dacă nu te prezinți la sosire fără să anulezi, se aplică aceleași reguli.</li>
    <li>Dacă <strong>noi</strong> anulăm rezervarea (de exemplu, vila nu mai este disponibilă), returnăm integral toate sumele plătite.</li>
    <li>Rambursarea se face în cel mult <strong>14 zile</strong> de la anulare, prin aceeași metodă de plată.</li>
  </ul>
  <p>Sumele plătite cu cardul se rambursează pe același card, prin NETOPIA Payments; cele plătite prin transfer, în contul din care au fost trimise.</p>
  <p>Conform art. 16 lit. l) din OUG 34/2014, dreptul de retragere de 14 zile nu se aplică serviciilor de cazare
     prestate la o dată sau într-o perioadă anume; se aplică politica de anulare de mai sus.</p>
  <p>Poți trimite oricând online declarația de retragere / anulare din pagina
     <a href="<?= e(url('/retragere.php')) ?>">Retrageți-vă din contract aici</a> (link și în subsolul fiecărei pagini).
     Primești imediat pe email confirmarea de primire, cu data și ora. O poți trimite și pe email, la <?= e($f['email_contact']) ?>.</p>

  <h2>7. Regulile casei</h2>
  <p class="de-completat">[Fumat, animale de companie, petreceri, liniște, garanție/depozit, răspunderea pentru daune etc.]</p>

  <h2>8. Reclamații și litigii</h2>
  <p>Reclamațiile se pot trimite la <?= e($f['email_contact']) ?>. Consumatorii se pot adresa și
     <a href="https://anpc.ro/" target="_blank" rel="noopener">ANPC</a> sau procedurii de
     <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">soluționare alternativă a litigiilor (SAL)</a>.</p>

  <h2>9. Date personale</h2>
  <p>Prelucrăm datele tale conform <a href="<?= e(url('/confidentialitate.php')) ?>">Politicii de confidențialitate</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
