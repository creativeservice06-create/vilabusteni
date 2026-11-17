</main>

<footer class="subsol">
  <svg class="creasta creasta-subsol" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
    <polyline points="<?= e(CREASTA) ?>" />
  </svg>
  <div class="subsol-interior">
    <div class="subsol-vila">
      <p class="subsol-nume"><?= e($setari['nume_vila']) ?></p>
      <p><?= e($setari['adresa']) ?></p>
      <p>
        <a href="tel:<?= e($setari['telefon_link']) ?>"><?= e($setari['telefon']) ?></a><br>
        <a href="mailto:<?= e($setari['email_contact']) ?>"><?= e($setari['email_contact']) ?></a>
      </p>
    </div>
    <nav class="subsol-linkuri" aria-label="Informații legale">
      <a href="/termeni.php">Termeni și condiții</a>
      <a href="/confidentialitate.php">Politica de confidențialitate</a>
      <a href="/cookies.php">Politica de cookie-uri</a>
      <a href="https://anpc.ro/" target="_blank" rel="noopener">ANPC</a>
      <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">ANPC – soluționarea alternativă a litigiilor</a>
    </nav>
  </div>
  <p class="subsol-firma">
    © <?= date('Y') ?> <?= e($setari['firma_nume']) ?>, CUI <?= e($setari['firma_cui']) ?>, <?= e($setari['firma_reg_com']) ?>
  </p>
</footer>

<!-- Bara de jos pe telefon: cele trei acțiuni importante, mereu la îndemână -->
<nav class="bara-mobil" aria-label="Contact rapid">
  <a href="tel:<?= e($setari['telefon_link']) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>
    Sună
  </a>
  <a href="https://wa.me/<?= e($setari['whatsapp']) ?>" target="_blank" rel="noopener">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.3-.8a11.6 11.6 0 0 1-4.4-3.9c-.4-.5-1.1-1.6-1.1-3s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.2 1.4 2.5 1.5.3.2.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.3.1.2.1.8-.2 1.5z"/></svg>
    WhatsApp
  </a>
  <?php if ($pagina !== 'rezervare'): ?>
  <a href="/rezervare.php" class="bara-rezerva">Rezervă</a>
  <?php endif; ?>
</nav>

<script src="/assets/js/main.js?v=3"></script>
<?php if (!empty($scripturi)) foreach ($scripturi as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
