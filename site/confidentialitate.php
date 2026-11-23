<?php
// POLITICA DE CONFIDENȚIALITATE (GDPR) – MODEL de verificat înainte de lansare.
require __DIR__ . '/includes/init.php';
$titlu = t('confidentialitate') . ' – ' . $setari['nume_vila'];
require __DIR__ . '/includes/header.php';
$f = $setari;
?>
<section class="interior legal" lang="ro">
  <h1><?= e(t('confidentialitate')) ?></h1>
  <?php if (t('legal_doar_ro')): ?><p class="nota-limba" lang="<?= e($LIMBA) ?>"><?= e(t('legal_doar_ro')) ?></p><?php endif; ?>
  <p class="mic">Ultima actualizare: <span class="de-completat">[data]</span></p>

  <h2>1. Operatorul datelor</h2>
  <p><strong><?= e($f['firma_nume']) ?></strong>, CUI <?= e($f['firma_cui']) ?>, sediul în <?= e($f['firma_sediu']) ?>.
     Contact pentru protecția datelor: <?= e($f['email_contact']) ?>.</p>

  <h2>2. Ce date colectăm</h2>
  <p>Prin formularul de rezervare: nume, email, telefon, perioada sejurului, numărul de persoane, mesajul tău, adresa IP de la care s-a trimis cererea
     și datele pentru factură (țara, județul, localitatea, adresa și, dacă ceri factură pe firmă, denumirea, CUI-ul și nr. de înregistrare).
     La plata cu cardul nu primim datele cardului: le introduci doar pe pagina securizată NETOPIA Payments; noi primim doar rezultatul plății și ultimele cifre ale cardului.</p>

  <h2>3. De ce și pe ce bază</h2>
  <ul>
    <li>Pentru a răspunde cererii și a încheia/executa contractul de cazare (art. 6 alin. 1 lit. b GDPR).</li>
    <li>Pentru obligații legale – evidența turiștilor, facturare (art. 6 alin. 1 lit. c GDPR).</li>
    <li>Pentru prevenirea abuzurilor asupra formularului – adresa IP (interes legitim, art. 6 alin. 1 lit. f GDPR).</li>
  </ul>

  <h2>4. Cât timp păstrăm datele</h2>
  <p class="de-completat">[ex.: cererile nefinalizate – 12 luni; datele din rezervări și facturi – conform termenelor legale de arhivare.]</p>

  <h2>5. Cui le transmitem</h2>
  <p>Furnizorului de găzduire web (Claus Web SRL); procesatorului de plăți NETOPIA Payments (NETOPIA Financial Services S.A.), pentru plățile cu cardul,
     care prelucrează datele plății ca operator independent; programului de facturare FGO, prin care emitem factura fiscală, transmisă apoi în sistemul RO e-Factura al ANAF; Meta Platforms Ireland Ltd. (Facebook), prin pixelul de măsurare, doar dacă ai acceptat cookie-urile de marketing.
     <span class="de-completat">[Alți destinatari, dacă e cazul – de ex. contabilul firmei.]</span> Nu vindem datele tale.</p>

  <h2>6. Drepturile tale</h2>
  <p>Ai dreptul de acces, rectificare, ștergere, restricționare, portabilitate și opoziție, precum și dreptul de a depune plângere la
     <a href="https://www.dataprotection.ro/" target="_blank" rel="noopener">ANSPDCP</a>. Pentru exercitarea lor scrie-ne la <?= e($f['email_contact']) ?>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
