// Formularul de rezervare – datele pentru factură:
// județul apare doar pentru România, câmpurile de firmă doar dacă e bifat „pe firmă”.
(function () {
  var tara = document.getElementById('fact-tara');
  var pj = document.getElementById('fact-pj');
  if (!tara || !pj) return;
  var campJudet = document.querySelector('.camp-judet');
  var judet = campJudet.querySelector('select');
  var localitate = document.querySelector('input[name="fact_localitate"]');
  var firma = document.querySelector('.campuri-firma');

  function actualizeaza() {
    var ro = tara.value === 'RO';
    campJudet.hidden = !ro;
    judet.required = ro;
    // București: la localitate se scrie sectorul (cerință e-Factura)
    localitate.placeholder = ro && judet.value === 'B' ? localitate.getAttribute('data-bucuresti') : '';
    firma.hidden = !pj.checked;
    firma.querySelectorAll('input[name="fact_firma"], input[name="fact_cui"]').forEach(function (c) {
      c.required = pj.checked;
    });
  }
  tara.addEventListener('change', actualizeaza);
  judet.addEventListener('change', actualizeaza);
  pj.addEventListener('change', actualizeaza);
  actualizeaza();
})();
