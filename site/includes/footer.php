</main>

<footer class="subsol" id="contact">
  <svg class="creasta creasta-subsol" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
    <polyline points="<?= e(CREASTA) ?>" />
  </svg>
  <div class="subsol-interior">

    <!-- Siglele: Vila Rândunica + Enjoy Travel -->
    <div class="subsol-sigle">
      <img class="sigla-vila" src="/assets/images/sigle/vila-randunica-alb.png" alt="<?= e(t('sigla_alt')) ?>" width="300" height="267" loading="lazy">
      <div class="marca">
        <img src="/assets/images/sigle/enjoy-travel.png" alt="Enjoy Travel" width="260" height="94" loading="lazy">
        <p><?= e(t('marca', $setari['firma_marca'])) ?></p>
      </div>
    </div>

    <!-- Toate datele de contact -->
    <div class="subsol-contact">
      <h2><?= e(t('contact')) ?></h2>
      <ul class="contact-lista">
        <li><span><?= e(t('telefon')) ?></span><a href="tel:<?= e($setari['telefon_link']) ?>"><?= e(telefon()) ?></a></li>
        <li><span><?= e(t('whatsapp')) ?></span><a href="https://wa.me/<?= e($setari['whatsapp']) ?>" target="_blank" rel="noopener"><?= e(telefon()) ?></a></li>
        <li><span><?= e(t('email')) ?></span><a href="mailto:<?= e($setari['email_contact']) ?>"><?= e($setari['email_contact']) ?></a></li>
        <li><span><?= e(t('adresa')) ?></span><a href="<?= e($setari['harta_link']) ?>" target="_blank" rel="noopener"><?= e($setari['adresa']) ?></a></li>
      </ul>
      <?= iconite_sociale($setari) ?>

    </div>

    <nav class="subsol-linkuri" aria-label="<?= e(t('info_legale')) ?>">
      <a href="<?= e(url('/rezervare.php')) ?>"><?= e(t('rezervare')) ?></a>
      <a href="<?= e(url('/termeni.php')) ?>"><?= e(t('termeni')) ?></a>
      <a href="<?= e(url('/confidentialitate.php')) ?>"><?= e(t('confidentialitate')) ?></a>
      <a href="<?= e(url('/cookies.php')) ?>"><?= e(t('cookies')) ?></a>
      <a href="<?= e(url('/retragere.php')) ?>"><?= e(t('retragere_link')) ?></a>
      <a href="https://anpc.ro/" target="_blank" rel="noopener">ANPC</a>
      <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener"><?= e(t('anpc_sal')) ?></a>
    </nav>
  </div>
  <!-- Siglele obligatorii: NETOPIA (plata cu cardul) și pictograma ANPC SAL (Ordinul ANPC 449/2022) -->
  <div class="subsol-insigne">
    <?php if (card_activ($setari)): ?>
      <a href="https://netopia-payments.com" target="_blank" rel="noopener" class="insigna-netopia" title="<?= e(t('plati_acceptate')) ?>">
        <img src="/assets/images/plati/netopia-alb.svg" alt="NETOPIA Payments" width="133" height="50" loading="lazy">
      </a>
      <p class="plati-acceptate"><?= e(t('plati_acceptate')) ?></p>
    <?php endif; ?>
    <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener" class="insigna-anpc">
      <img src="/assets/images/plati/anpc-sal.png" alt="ANPC – Soluționarea alternativă a litigiilor" width="250" height="61" loading="lazy">
    </a>
  </div>
  <p class="subsol-firma">
    © <?= date('Y') ?> <?= e(t('firma', $setari['firma_nume'], $setari['firma_cui'], $setari['firma_reg_com'], $setari['firma_sediu'])) ?>
  </p>
</footer>

<!-- Bara de jos pe telefon: cele trei acțiuni importante, mereu la îndemână -->
<nav class="bara-mobil" aria-label="<?= e(t('contact_rapid')) ?>">
  <a href="tel:<?= e($setari['telefon_link']) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>
    <?= e(t('suna')) ?>
  </a>
  <a href="https://wa.me/<?= e($setari['whatsapp']) ?>" target="_blank" rel="noopener">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.3-.8a11.6 11.6 0 0 1-4.4-3.9c-.4-.5-1.1-1.6-1.1-3s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.2 1.4 2.5 1.5.3.2.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.3.1.2.1.8-.2 1.5z"/></svg>
    WhatsApp
  </a>
  <?php if ($pagina !== 'rezervare'): ?>
  <a href="<?= e(url('/rezervare.php')) ?>" class="bara-rezerva"><?= e(t('meniu_rezerva')) ?></a>
  <?php endif; ?>
</nav>

<script src="/assets/js/main.js?v=4"></script>
<?php if (!empty($scripturi)) foreach ($scripturi as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
