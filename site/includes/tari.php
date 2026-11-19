<?php
// ȚĂRILE ȘI JUDEȚELE din formularul de rezervare (pentru factura fiscală și plata cu cardul).
// Cod de 2 litere => [cod numeric ISO (cerut de Netopia), numele în română (trimis la FGO), numele în engleză].
// Numele în celelalte limbi se iau automat din PHP (extensia intl), dacă există.

const TARI = [
    'RO' => [642, 'Romania', 'Romania'],
    'MD' => [498, 'Republica Moldova', 'Moldova'],
    'DE' => [276, 'Germania', 'Germany'],
    'AT' => [40,  'Austria', 'Austria'],
    'CH' => [756, 'Elvetia', 'Switzerland'],
    'IT' => [380, 'Italia', 'Italy'],
    'FR' => [250, 'Franta', 'France'],
    'ES' => [724, 'Spania', 'Spain'],
    'PT' => [620, 'Portugalia', 'Portugal'],
    'GB' => [826, 'Marea Britanie', 'United Kingdom'],
    'IE' => [372, 'Irlanda', 'Ireland'],
    'NL' => [528, 'Olanda', 'Netherlands'],
    'BE' => [56,  'Belgia', 'Belgium'],
    'LU' => [442, 'Luxemburg', 'Luxembourg'],
    'DK' => [208, 'Danemarca', 'Denmark'],
    'SE' => [752, 'Suedia', 'Sweden'],
    'NO' => [578, 'Norvegia', 'Norway'],
    'FI' => [246, 'Finlanda', 'Finland'],
    'PL' => [616, 'Polonia', 'Poland'],
    'CZ' => [203, 'Cehia', 'Czechia'],
    'SK' => [703, 'Slovacia', 'Slovakia'],
    'HU' => [348, 'Ungaria', 'Hungary'],
    'BG' => [100, 'Bulgaria', 'Bulgaria'],
    'RS' => [688, 'Serbia', 'Serbia'],
    'GR' => [300, 'Grecia', 'Greece'],
    'HR' => [191, 'Croatia', 'Croatia'],
    'SI' => [705, 'Slovenia', 'Slovenia'],
    'CY' => [196, 'Cipru', 'Cyprus'],
    'MT' => [470, 'Malta', 'Malta'],
    'EE' => [233, 'Estonia', 'Estonia'],
    'LV' => [428, 'Letonia', 'Latvia'],
    'LT' => [440, 'Lituania', 'Lithuania'],
    'UA' => [804, 'Ucraina', 'Ukraine'],
    'RU' => [643, 'Rusia', 'Russia'],
    'TR' => [792, 'Turcia', 'Turkey'],
    'IL' => [376, 'Israel', 'Israel'],
    'AE' => [784, 'Emiratele Arabe Unite', 'United Arab Emirates'],
    'US' => [840, 'Statele Unite ale Americii', 'United States'],
    'CA' => [124, 'Canada', 'Canada'],
    'AU' => [36,  'Australia', 'Australia'],
    'JP' => [392, 'Japonia', 'Japan'],
    'CN' => [156, 'China', 'China'],
    'IN' => [356, 'India', 'India'],
];

// Codurile județelor (aceleași ca la ANAF / e-Factura și FGO)
const JUDETE = [
    'AB' => 'Alba', 'AR' => 'Arad', 'AG' => 'Argeș', 'BC' => 'Bacău', 'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud',
    'BT' => 'Botoșani', 'BR' => 'Brăila', 'BV' => 'Brașov', 'B' => 'București', 'BZ' => 'Buzău', 'CL' => 'Călărași',
    'CS' => 'Caraș-Severin', 'CJ' => 'Cluj', 'CT' => 'Constanța', 'CV' => 'Covasna', 'DB' => 'Dâmbovița', 'DJ' => 'Dolj',
    'GL' => 'Galați', 'GR' => 'Giurgiu', 'GJ' => 'Gorj', 'HR' => 'Harghita', 'HD' => 'Hunedoara', 'IL' => 'Ialomița',
    'IS' => 'Iași', 'IF' => 'Ilfov', 'MM' => 'Maramureș', 'MH' => 'Mehedinți', 'MS' => 'Mureș', 'NT' => 'Neamț',
    'OT' => 'Olt', 'PH' => 'Prahova', 'SM' => 'Satu Mare', 'SJ' => 'Sălaj', 'SB' => 'Sibiu', 'SV' => 'Suceava',
    'TR' => 'Teleorman', 'TM' => 'Timiș', 'TL' => 'Tulcea', 'VL' => 'Vâlcea', 'VS' => 'Vaslui', 'VN' => 'Vrancea',
];

/** Numele țării în limba paginii. */
function nume_tara($cod, $limba)
{
    if (!isset(TARI[$cod])) return $cod;
    if ($limba === 'ro') return nume_tara_ro($cod);
    if (class_exists('Locale')) {
        $n = Locale::getDisplayRegion('-' . $cod, $limba);
        if ($n !== '' && $n !== $cod) return $n;
    }
    return TARI[$cod][2];
}

/** Numele în română, cu diacritice (pentru afișare). */
function nume_tara_ro($cod)
{
    $diacritice = ['Romania' => 'România', 'Elvetia' => 'Elveția', 'Franta' => 'Franța', 'Croatia' => 'Croația'];
    $n = TARI[$cod][1] ?? $cod;
    return $diacritice[$n] ?? $n;
}

/** Lista țărilor pentru <select>: România prima, apoi alfabetic în limba paginii. */
function lista_tari($limba)
{
    $lista = [];
    foreach (TARI as $cod => $_) {
        $lista[$cod] = nume_tara($cod, $limba);
    }
    $ro = ['RO' => $lista['RO']];
    unset($lista['RO']);
    if (class_exists('Collator')) {
        (new Collator($limba))->asort($lista);
    } else {
        asort($lista);
    }
    return $ro + $lista;
}

/** Adresa de facturare pe un rând: „Str. X nr. 1, Bușteni, Prahova, România” */
function adresa_facturare(array $r, $limba = 'ro')
{
    $tara = $r['fact_tara'] ?: 'RO';
    $parti = [$r['fact_adresa'] ?? '', $r['fact_localitate'] ?? ''];
    if ($tara === 'RO' && !empty($r['fact_judet'])) {
        $parti[] = $r['fact_judet'] === 'B' ? '' : (JUDETE[$r['fact_judet']] ?? '');
    }
    $parti[] = nume_tara($tara, $limba);
    return implode(', ', array_filter(array_map('trim', $parti)));
}
