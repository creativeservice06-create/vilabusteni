# vilabusteni.ro – Vila Rândunica

Site de prezentare și rezervări pentru Vila Rândunica (Poiana Țapului, Bușteni).
PHP simplu, fără framework. Merge pe orice găzduire cPanel.

## Structura

```
site/                      ← tot ce e aici ajunge în public_html pe server
  index.php                pagina principală (poveste, cazare, galerie, locație)
  rezervare.php            calendarul + formularul de rezervare
  termeni.php              Termeni și condiții        (model – de completat)
  confidentialitate.php    Politica de confidențialitate (model – de completat)
  cookies.php              Politica de cookie-uri
  api/
    disponibilitate.php    trimite calendarului zilele ocupate
  lang/                    ⭐ TEXTELE site-ului: ro.php, en.php, de.php, it.php, ru.php
  includes/
    setari.php             ⭐ telefon, email, preț, minim nopți, date firmă – AICI se schimbă
    header.php             meniul și partea de sus
    footer.php             subsolul
    init.php               funcții mici folosite peste tot
    db.php                 conexiunea la baza de date
    migrari.php            structura bazei de date – se aplică AUTOMAT
    rezervari.php          verificarea, salvarea și mailurile rezervărilor
  assets/
    css/style.css          aspectul (culorile și fonturile sunt sus, în :root)
    fonts/                 fonturile Young Serif (titluri) și Instrument Sans (text), găzduite local
    js/main.js             meniul, meniul transparent de pe prima pagină, galeria
    js/calendar.js         calendarul de rezervare
    images/                pozele mari; images/mici/ = variantele mici pentru galerie
  config.example.php       model pentru config.php (date bază de date)
.github/workflows/
  deploy.yml               deploy automat pe cPanel
```

Reguli simple:
- **un text de pe site** se schimbă în `lang/ro.php` (și în aceeași cheie din celelalte limbi);
- adresele limbilor: `/` română, `/en/`, `/de/`, `/it/`, `/ru/` (setate în `includes/limbi.php` și `.htaccess`);
- **o pagină = un fișier `.php`** în `site/`, cu litere mici;
- **prețul, telefonul, emailurile** se schimbă doar în `includes/setari.php`;
- **o poză nouă**: se pune în `images/` și (mai mică) în `images/mici/`, apoi se adaugă în lista `$galerie` din `index.php`.

## Cum funcționează rezervarea

1. Clientul alege perioada (minim 3 nopți) și cât plătește acum: avansul de 30%, o sumă aleasă (minimum 30%) sau totul.
2. Perioada e ținută **48 de ore** (`termen_plata_ore`), iar clientul primește mail cu **proforma**
   (`proforma.php?cod=...`) și detaliile de plată a **avansului de 30%** (`avans_procent`).
3. Proprietarul primește mail cu link spre panou. Când vede banii în cont → **Plată primită – confirmă**:
   clientul primește mailul de confirmare; factura fiscală se emite separat (deocamdată de contabil).
4. Dacă nu plătește în 48h, rezervarea se **anulează automat** (`includes/automat.php`, verificare la cel mult 5 minute,
   declanșată de orice vizită pe site; opțional și din cron: `cron/expirate.php`). Clientul și proprietarul primesc mail.
   Dacă clientul a plătit totuși, din panou → Istoric → **Reactivează – plată primită**.
5. Clientul poate trimite **dovada de plată** (poză/PDF) din pagina proformei. Fișierul se păstrează în `dovezi-plata/`
   (lângă public_html, nu e accesibil din browser), proprietarul îl primește atașat pe mail și îl vede în panou.
   O rezervare cu dovadă trimisă nu se mai anulează automat.

Mailurile sunt în `includes/mailuri.php` (șablon HTML cu sigla, merge pe tema deschisă și întunecată),
textele în `lang/*.php` la cheile `mailuri` și `proforma`.

## Panoul de administrare – vilabusteni.ro/admin/

Parola se pune în `config.php` pe server: `'admin_parola' => '...'`.
- **Cereri noi**: Confirmă (clientul primește mail de confirmare în limba lui) sau Anulează (mail că perioada nu e disponibilă).
- **Confirmate**: rezervările viitoare; zilele lor apar ocupate în calendarul de pe site.
- **Zile blocate**: blochezi perioade închiriate pe alte căi (telefon, Booking etc.).
- **Istoric**: rezervări trecute și anulate.

Mailul de confirmare include automat: perioada, prețul, adresa, link Google Maps, check-in/out, telefonul;
plus detaliile de plată (dacă `iban` e completat în `setari.php`) și politica de anulare (dacă `politica_anulare` e completată în `lang/*.php`).

## Mailurile

Toate mailurile pleacă de pe **contact@horoscoptravel.ro**, prin serverul ei de mail (`mail.horoscoptravel.ro`, port 465, SSL),
cu autentificare. Parola căsuței se pune doar pe server, în `config.php`: `'smtp_parola' => '...'`.
Dacă serverul de mail nu răspunde, mailul pleacă de rezervă de pe `rezervari@vilabusteni.ro`, ca niciun client să nu rămână fără mail.
Verificare: panou → jos → **Trimite mail de test**.

## Plata cu cardul (Netopia) și facturile (FGO)

**Formularul:** clientul alege avans 50% sau integral și metoda: **card** sau **ordin de plată**.
- Ordin de plată: primește imediat proforma pe email (48h), proprietarul primește „Rezervare nouă”.
- Card: e trimis direct pe Netopia; zilele sunt ținute 60 min (`termen_card_minute`); fără plată se eliberează fără niciun mail.
  Dacă se răzgândește, pe pagina de plată are „Plătesc prin ordin de plată” (primește proforma).

**Fluxul cu cardul:** clientul trimite formularul → plătește pe pagina securizată Netopia → Netopia anunță site-ul (`api/netopia-ipn.php`, mesaj semnat, verificat cu cheia publică Netopia)
→ rezervarea se confirmă automat → se emite factura în FGO → clientul primește confirmarea cu linkul facturii, proprietarul o notificare.

Fișiere: `includes/netopia.php`, `plata-card.php`, `api/netopia-ipn.php`, `includes/fgo.php`, `includes/tari.php` (țări și județe).

**Netopia** – în `config.php` pe server: `netopia_mediu` (`sandbox` / `live`), `netopia_api_key`, `netopia_pos_signature`, `netopia_cheie_publica`.
Fără chei, butonul de card nu apare nicăieri. Cheile de test și cele live sunt diferite.

**FGO** – în `config.php`: `fgo_cheie_api`; în `includes/setari.php`: `fgo_serie` și `fgo_cota_tva` (de stabilit cu contabilul).
În FGO → Setări eCommerce → API se înregistrează adresa `https://vilabusteni.ro`.
Facturile: la plată (card sau transfer confirmat în panou) se emite factura de **avans** sau **integral**; restul încasat la sosire
se facturează din panou („Rest încasat – emite factura”). O factură care dă eroare apare în panou cu butonul „Reîncearcă”.

În subsolul panoului se vede dacă plata cu cardul e în test sau live și dacă facturarea e activă.

## Instalare pe server (o singură dată)

1. cPanel → **MySQL Databases**: creezi baza `rezervari` și userul `site`, apoi le legi (All Privileges).
2. cPanel → **File Manager** → `public_html`: copiezi `config.example.php` ca `config.php` și completezi datele.
3. cPanel → **Email Accounts**: creezi `rezervari@vilabusteni.ro` (de pe ea pleacă mailurile automate).

Tabelele se creează singure la prima accesare a site-ului – nu se importă nimic manual.
Modificările viitoare ale structurii se adaugă ca pași noi în `includes/migrari.php` și se aplică tot automat.

## Deploy automat (cron job pe server)

Serverul își ia singur codul din GitHub: scriptul `deploy/actualizare.php` rulează
la 5 minute, verifică dacă pe ramura `main` e o versiune nouă și, dacă da, copiază
folderul `site/` în `public_html`. Fișierele șterse din GitHub se șterg și de pe server.
`config.php` nu este atins niciodată. Istoricul actualizărilor: `deploy/jurnal.log` pe server.

Instalare (o singură dată):
1. cPanel → File Manager → în folderul principal (lângă `public_html`) creezi folderul `deploy`
   și urci în el `deploy/actualizare.php`.
2. Doar dacă repository-ul e privat: în `deploy/` creezi `token.txt` cu un token GitHub
   (fine-grained, acces doar la acest repository, permisiunea Contents: Read-only).
3. cPanel → Cron Jobs → la 5 minute (`*/5 * * * *`), comanda:
   `/usr/local/bin/php /home/vilabust/deploy/actualizare.php >/dev/null 2>&1`

Pentru o actualizare forțată: rulezi scriptul cu parametrul `--fortat`.

Varianta prin FTP din GitHub Actions (`.github/workflows/deploy.yml`) e oprită,
pentru că serverul nu acceptă conexiuni FTP din afara României.
