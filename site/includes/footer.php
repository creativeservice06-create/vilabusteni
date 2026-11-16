</main>

<footer class="subsol">
  <div class="subsol-coloane">
    <div>
      <strong><?= e($setari['nume_vila']) ?></strong><br>
      <?= e($setari['adresa']) ?><br>
      <a href="tel:<?= e($setari['telefon_link']) ?>"><?= e($setari['telefon']) ?></a> ·
      <a href="mailto:<?= e($setari['email_contact']) ?>"><?= e($setari['email_contact']) ?></a>
    </div>
    <div>
      <a href="/termeni.php">Termeni și condiții</a><br>
      <a href="/confidentialitate.php">Politica de confidențialitate</a><br>
      <a href="/cookies.php">Politica de cookie-uri</a>
    </div>
    <div>
      <a href="https://anpc.ro/" target="_blank" rel="noopener">ANPC</a><br>
      <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">ANPC – Soluționarea alternativă a litigiilor</a>
    </div>
  </div>
  <p class="subsol-firma">
    &copy; <?= date('Y') ?> <?= e($setari['firma_nume']) ?> · CUI <?= e($setari['firma_cui']) ?> · <?= e($setari['firma_reg_com']) ?>
  </p>
</footer>

<script src="/assets/js/main.js?v=2"></script>
<?php if (!empty($scripturi)) foreach ($scripturi as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
