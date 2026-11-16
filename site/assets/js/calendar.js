// CALENDARUL DE REZERVARE
// Arată 2 luni. Primul click = sosirea, al doilea click = plecarea.
// Nopțile ocupate vin de la /api/disponibilitate.php.
(function () {
  var cal = document.getElementById('calendar');
  if (!cal) return;

  var MINIM = parseInt(cal.dataset.minim, 10) || 1;
  var PRET = parseInt(cal.dataset.pret, 10) || 0;
  var LUNI = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie',
              'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
  var ZILE = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'];

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
  function frumos(d) { return d.getDate() + ' ' + LUNI[d.getMonth()].toLowerCase() + ' ' + d.getFullYear(); }
  function lei(n) { return n.toLocaleString('ro-RO') + ' lei'; }

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
      '<button type="button" class="cal-prev" aria-label="Luna anterioară">‹</button>' +
      '<button type="button" class="cal-next" aria-label="Luna următoare">›</button></div>' +
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

  function actualizeazaSumar() {
    if (!sosire) {
      sumar.innerHTML = 'Alege data sosirii, apoi data plecării.';
      sumar.className = 'sumar';
    } else if (!plecare) {
      sumar.innerHTML = 'Sosire: <strong>' + frumos(sosire) + '</strong>. Acum alege data plecării (minimum ' + MINIM + ' nopți).';
      sumar.className = 'sumar';
    } else {
      var n = nopti(sosire, plecare);
      if (n < MINIM) {
        sumar.innerHTML = 'Ai ales ' + n + (n === 1 ? ' noapte' : ' nopți') + '. Perioada minimă este de <strong>' + MINIM + ' nopți</strong>.';
        sumar.className = 'sumar sumar-atentie';
      } else {
        sumar.innerHTML = '<strong>' + frumos(sosire) + ' – ' + frumos(plecare) + '</strong><br>' +
          n + ' nopți × ' + lei(PRET) + ' = <strong class="sumar-total">' + lei(n * PRET) + '</strong>';
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
