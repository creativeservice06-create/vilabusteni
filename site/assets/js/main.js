// Scripturile generale ale site-ului: meniul, meniul transparent de pe prima pagină și galeria.

// Meniul pe telefon (butonul cu două linii)
(function () {
  var buton = document.querySelector('.meniu-buton');
  var meniu = document.getElementById('meniu');
  if (!buton || !meniu) return;

  function seteaza(deschis) {
    meniu.classList.toggle('deschis', deschis);
    document.body.classList.toggle('meniu-deschis', deschis);
    buton.setAttribute('aria-expanded', deschis);
  }
  buton.addEventListener('click', function () { seteaza(!meniu.classList.contains('deschis')); });
  meniu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { seteaza(false); });
  });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') seteaza(false); });
})();

// Pe prima pagină: meniul e transparent peste poză și devine alb când derulezi
(function () {
  var antet = document.getElementById('antet');
  if (!antet || !document.body.classList.contains('pagina-acasa')) return;
  function verifica() { antet.classList.toggle('scrolat', window.scrollY > 40); }
  window.addEventListener('scroll', verifica, { passive: true });
  verifica();
})();

// Galeria: click pe o poză o deschide mare; săgeți, Esc și glisare cu degetul
(function () {
  var poze = Array.prototype.slice.call(document.querySelectorAll('.galerie-poza'));
  var box = document.querySelector('.lightbox');
  if (!poze.length || !box) return;
  var img = box.querySelector('img');
  var textEl = box.querySelector('.lightbox-text');
  var curent = 0, startX = null;

  function arata(i) {
    curent = (i + poze.length) % poze.length;
    img.src = poze[curent].href;
    img.alt = poze[curent].dataset.text;
    textEl.textContent = poze[curent].dataset.text + '  (' + (curent + 1) + ' din ' + poze.length + ')';
    box.hidden = false;
    document.body.style.overflow = 'hidden';
  }
  function inchide() { box.hidden = true; document.body.style.overflow = ''; poze[curent].focus(); }

  poze.forEach(function (p, i) {
    p.addEventListener('click', function (ev) { ev.preventDefault(); arata(i); box.querySelector('.lightbox-inchide').focus(); });
  });
  box.querySelector('.lightbox-inchide').onclick = inchide;
  box.querySelector('.lightbox-prev').onclick = function () { arata(curent - 1); };
  box.querySelector('.lightbox-next').onclick = function () { arata(curent + 1); };
  box.addEventListener('click', function (ev) { if (ev.target === box) inchide(); });
  box.addEventListener('touchstart', function (ev) { startX = ev.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', function (ev) {
    if (startX === null) return;
    var dx = ev.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 50) arata(curent + (dx < 0 ? 1 : -1));
    startX = null;
  });
  document.addEventListener('keydown', function (ev) {
    if (box.hidden) return;
    if (ev.key === 'Escape') inchide();
    if (ev.key === 'ArrowLeft') arata(curent - 1);
    if (ev.key === 'ArrowRight') arata(curent + 1);
  });
})();
