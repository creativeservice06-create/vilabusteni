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
  <p>Prin formularul de rezervare: nume, email, telefon, perioada sejurului, numărul de persoane, mesajul tău și adresa IP de la care s-a trimis cererea.</p>

  <h2>3. De ce și pe ce bază</h2>
  <ul>
    <li>Pentru a răspunde cererii și a încheia/executa contractul de cazare (art. 6 alin. 1 lit. b GDPR).</li>
    <li>Pentru obligații legale – evidența turiștilor, facturare (art. 6 alin. 1 lit. c GDPR).</li>
    <li>Pentru prevenirea abuzurilor asupra formularului – adresa IP (interes legitim, art. 6 alin. 1 lit. f GDPR).</li>
  </ul>

  <h2>4. Cât timp păstrăm datele</h2>
  <p class="de-completat">[ex.: cererile nefinalizate – 12 luni; datele din rezervări și facturi – conform termenelor legale de arhivare.]</p>

  <h2>5. Cui le transmitem</h2>
  <p>Furnizorului de găzduire web (Claus Web SRL) și, după activarea plăților online, procesatorului de plăți.
     <span class="de-completat">[Alți destinatari, dacă e cazul.]</span> Nu vindem datele tale.</p>

  <h2>6. Drepturile tale</h2>
  <p>Ai dreptul de acces, rectificare, ștergere, restricționare, portabilitate și opoziție, precum și dreptul de a depune plângere la
     <a href="https://www.dataprotection.ro/" target="_blank" rel="noopener">ANSPDCP</a>. Pentru exercitarea lor scrie-ne la <?= e($f['email_contact']) ?>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
