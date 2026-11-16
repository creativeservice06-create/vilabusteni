// Scripturile generale ale site-ului: meniul pe mobil și galeria de poze.

// Meniul pe telefon
(function () {
  var buton = document.querySelector('.meniu-buton');
  var meniu = document.querySelector('.meniu');
  if (!buton || !meniu) return;
  buton.addEventListener('click', function () {
    var deschis = meniu.classList.toggle('deschis');
    buton.setAttribute('aria-expanded', deschis);
  });
  meniu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { meniu.classList.remove('deschis'); });
  });
})();

// Galeria: click pe o poză o deschide mare; săgeți și Esc din tastatură
(function () {
  var poze = Array.prototype.slice.call(document.querySelectorAll('.galerie-poza'));
  var box = document.querySelector('.lightbox');
  if (!poze.length || !box) return;
  var img = box.querySelector('img');
  var textEl = box.querySelector('.lightbox-text');
  var curent = 0;

  function arata(i) {
    curent = (i + poze.length) % poze.length;
    img.src = poze[curent].href;
    img.alt = poze[curent].dataset.text;
    textEl.textContent = poze[curent].dataset.text;
    box.hidden = false;
    document.body.style.overflow = 'hidden';
  }
  function inchide() { box.hidden = true; document.body.style.overflow = ''; }

  poze.forEach(function (p, i) {
    p.addEventListener('click', function (ev) { ev.preventDefault(); arata(i); });
  });
  box.querySelector('.lightbox-inchide').onclick = inchide;
  box.querySelector('.lightbox-prev').onclick = function () { arata(curent - 1); };
  box.querySelector('.lightbox-next').onclick = function () { arata(curent + 1); };
  box.addEventListener('click', function (ev) { if (ev.target === box) inchide(); });
  document.addEventListener('keydown', function (ev) {
    if (box.hidden) return;
    if (ev.key === 'Escape') inchide();
    if (ev.key === 'ArrowLeft') arata(curent - 1);
    if (ev.key === 'ArrowRight') arata(curent + 1);
  });
})();
