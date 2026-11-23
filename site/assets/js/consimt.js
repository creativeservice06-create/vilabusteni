// Bannerul de cookie-uri și pixelul Meta.
// Pixelul se încarcă doar după „Accept”. Decizia se ține minte în browser (localStorage).
(function () {
  var CHEIE = 'vr_consimt';
  var pixel = window.VR_PIXEL || '';
  var banner = document.getElementById('consimt');
  var fbPornit = false;

  function citeste() { try { return localStorage.getItem(CHEIE); } catch (e) { return null; } }
  function salveaza(v) { try { localStorage.setItem(CHEIE, v); } catch (e) {} }

  // Codul standard al pixelului Meta, încărcat abia după consimțământ
  function porneșteFb() {
    if (fbPornit || !pixel) return;
    fbPornit = true;
    !function (f, b, e, v, n, t, s) {
      if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
      if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
      t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    window.fbq('init', pixel);
    window.fbq('track', 'PageView');
  }

  function ascunde() { if (banner) banner.hidden = true; }

  var stare = citeste();
  if (banner && !stare) banner.hidden = false;
  if (stare === 'da') porneșteFb();

  if (banner) {
    banner.querySelectorAll('[data-consimt]').forEach(function (buton) {
      buton.addEventListener('click', function () {
        var v = buton.getAttribute('data-consimt');
        salveaza(v);
        ascunde();
        if (v === 'da') porneșteFb();
      });
    });
  }

  // Trimiterea formularului de rezervare = „Inițiere checkout” (doar dacă pixelul e pornit)
  var formular = document.querySelector('.form-rezervare');
  if (formular) {
    formular.addEventListener('submit', function () {
      if (fbPornit && window.fbq) window.fbq('track', 'InitiateCheckout');
    });
  }
})();
