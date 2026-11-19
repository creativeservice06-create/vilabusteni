// CALENDARUL DE REZERVARE
// Arată 2 luni. Primul click = sosirea, al doilea click = plecarea.
// Nopțile ocupate vin de la /api/disponibilitate.php.
(function () {
  var cal = document.getElementById('calendar');
  if (!cal) return;

  var MINIM = parseInt(cal.dataset.minim, 10) || 1;
  var PRET = parseInt(cal.dataset.pret, 10) || 0;
  var MONEDA = cal.dataset.moneda || 'lei';
  var PROCENT = parseInt(cal.dataset.procent, 10) || 0;
  var ORE = parseInt(cal.dataset.ore, 10) || 48;
  // Textele vin din pagină, în limba curentă (lang/*.php → 'calendar')
  var T = JSON.parse(cal.dataset.texte || '{}');
  var LUNI = T.luni, LUNI_MICI = T.luni_mici || T.luni, ZILE = T.zile;
  function sablon(text, valori) {
    return text.replace(/\{(\w+)\}/g, function (_, k) { return valori[k] !== undefined ? valori[k] : ''; });
  }

  var campSosire = document.getElementById('sosire');
  var campPlecare = document.getElementById('plecare');
  var sumar = document.getElementById('sumar');

  var azi = new Date(); azi.setHours(0, 0, 0, 0);
  var lunaAfisata = new Date(azi.getFullYear(), azi.getMonth(), 1);
  var ocupate = {};
  var sosire = campSosire.value ? dinText(campSosire.value) : null;
  var plecare = campPlecare.value ? dinText(campPlecare.value) : null;

  // --- ajutoare pentru date ---
  function text(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  function dinText(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function plusZile(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function nopti(a, b) { return Math.round((b - a) / 86400000); }
  function frumos(d) { return d.getDate() + ' ' + LUNI_MICI[d.getMonth()] + ' ' + d.getFullYear(); }
  function lei(n) { return n.toLocaleString(T.locale || 'ro-RO') + ' ' + MONEDA; }

  // Există vreo noapte ocupată între a (inclusiv) și b (exclusiv)?
  function intervalLiber(a, b) {
    for (var d = new Date(a); d < b; d = plusZile(d, 1)) {
      if (ocupate[text(d)]) return false;
    }
    return true;
  }

  // --- desenarea calendarului ---
  function deseneaza() {
    var html = '<div class="cal-nav">' +
      '<button type="button" class="cal-prev" aria-label="' + T.prev + '">‹</button>' +
      '<button type="button" class="cal-next" aria-label="' + T.next + '">›</button></div>' +
      '<div class="cal-luni">';
    for (var i = 0; i < 2; i++) {
      html += lunaHtml(new Date(lunaAfisata.getFullYear(), lunaAfisata.getMonth() + i, 1));
    }
    cal.innerHTML = html + '</div>';

    cal.querySelector('.cal-prev').disabled = lunaAfisata <= new Date(azi.getFullYear(), azi.getMonth(), 1);
    cal.querySelector('.cal-prev').onclick = function () { lunaAfisata.setMonth(lunaAfisata.getMonth() - 1); deseneaza(); };
    cal.querySelector('.cal-next').onclick = function () { lunaAfisata.setMonth(lunaAfisata.getMonth() + 1); deseneaza(); };
    cal.querySelectorAll('.cal-zi[data-zi]').forEach(function (b) {
      b.onclick = function () { alegeZi(dinText(b.dataset.zi)); };
    });
    actualizeazaSumar();
  }

  function lunaHtml(prima) {
    var html = '<div class="cal-luna"><div class="cal-titlu">' + LUNI[prima.getMonth()] + ' ' + prima.getFullYear() + '</div>' +
      '<div class="cal-grila">';
    ZILE.forEach(function (z) { html += '<span class="cal-cap">' + z + '</span>'; });

    var gol = (prima.getDay() + 6) % 7; // luni = prima zi a săptămânii
    for (var g = 0; g < gol; g++) html += '<span></span>';

    var zileInLuna = new Date(prima.getFullYear(), prima.getMonth() + 1, 0).getDate();
    for (var z = 1; z <= zileInLuna; z++) {
      var d = new Date(prima.getFullYear(), prima.getMonth(), z);
      var t = text(d);
      var clase = ['cal-zi'];
      var trecut = d < azi;
      var ocupat = ocupate[t];
      // O zi ocupată poate fi totuși aleasă ca zi de PLECARE (plecarea e dimineața).
      var poateFiPlecare = sosire && !plecare && d > sosire && intervalLiber(sosire, d);

      if (trecut) clase.push('trecut');
      if (ocupat) clase.push('ocupat');
      if (sosire && t === text(sosire)) clase.push('ales', 'capat');
      if (plecare && t === text(plecare)) clase.push('ales', 'capat');
      if (sosire && plecare && d > sosire && d < plecare) clase.push('interval');

      var activ = !trecut && (!ocupat || poateFiPlecare);
      html += activ
        ? '<button type="button" class="' + clase.join(' ') + '" data-zi="' + t + '">' + z + '</button>'
        : '<span class="' + clase.join(' ') + '">' + z + '</span>';
    }
    return html + '</div></div>';
  }

  function alegeZi(d) {
    if (!sosire || plecare || d <= sosire) {
      // începe o selecție nouă
      if (ocupate[text(d)]) return;
      sosire = d; plecare = null;
    } else {
      if (!intervalLiber(sosire, d)) {
        sosire = d; plecare = null; // a sărit peste zile ocupate → reîncepe
      } else {
        plecare = d;
      }
    }
    campSosire.value = sosire ? text(sosire) : '';
    campPlecare.value = plecare ? text(plecare) : '';
    deseneaza();
  }

  // Anunță formularul ce total s-a ales (0 = perioadă incompletă)
  function anuntaTotal() {
    var total = (sosire && plecare && nopti(sosire, plecare) >= MINIM) ? nopti(sosire, plecare) * PRET : 0;
    actualizeazaPlata(total);
  }

  // Opțiunile de plată: sumele afișate și limitele pentru „altă sumă”
  function actualizeazaPlata(total) {
    var box = document.getElementById('optiuni-plata');
    if (!box) return;
    var minim = Math.round(total * PROCENT / 100);
    var gol = box.querySelector('[data-suma="avans"]').dataset.gol || box.querySelector('[data-suma="avans"]').textContent;
    box.querySelectorAll('[data-suma]').forEach(function (el) { if (!el.dataset.gol) el.dataset.gol = gol; });
    box.querySelector('[data-suma="avans"]').textContent = total ? lei(minim) : gol;
    box.querySelector('[data-suma="integral"]').textContent = total ? lei(total) : gol;
  }

  function actualizeazaSumar() {
    anuntaTotal();
    if (!sosire) {
      sumar.innerHTML = T.start;
      sumar.className = 'sumar';
    } else if (!plecare) {
      sumar.innerHTML = sablon(T.sosire, { data: frumos(sosire), minim: MINIM });
      sumar.className = 'sumar';
    } else {
      var n = nopti(sosire, plecare);
      if (n < MINIM) {
        sumar.innerHTML = sablon(T.putin, { n: n, nopti: n === 1 ? T.noapte : T.nopti, minim: MINIM });
        sumar.className = 'sumar sumar-atentie';
      } else {
        sumar.innerHTML = '<strong>' + frumos(sosire) + ' – ' + frumos(plecare) + '</strong><br>' +
          sablon(T.total, { n: n, pret: lei(PRET) }) + '<strong class="sumar-total">' + lei(n * PRET) + '</strong>' +
          (PROCENT > 0 && T.avans ? '<span class="sumar-avans">' + sablon(T.avans, { procent: PROCENT, avans: lei(Math.round(n * PRET * PROCENT / 100)), ore: ORE }) + '</span>' : '');
        sumar.className = 'sumar sumar-ok';
      }
    }
  }

  // Dacă formularul revine cu date alese, arată luna sosirii
  if (sosire) lunaAfisata = new Date(sosire.getFullYear(), sosire.getMonth(), 1);

  fetch('/api/disponibilitate.php')
    .then(function (r) { return r.json(); })
    .then(function (j) { (j.ocupate || []).forEach(function (z) { ocupate[z] = true; }); })
    .catch(function () { /* fără date de disponibilitate: calendarul merge oricum */ })
    .then(deseneaza);
})();

// Metoda de plată: textul butonului se schimbă după alegere („Plătește cu cardul” / „Primește proforma”)
(function () {
  var buton = document.getElementById('buton-trimite');
  var metode = document.querySelectorAll('input[name="metoda"]');
  if (!buton || !metode.length) return;
  metode.forEach(function (m) {
    m.addEventListener('change', function () { if (m.checked) buton.textContent = m.dataset.buton; });
    if (m.checked) buton.textContent = m.dataset.buton;
  });
})();
