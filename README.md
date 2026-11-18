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

## Cum funcționează rezervarea (versiunea 1)

1. Clientul alege perioada în calendar (minim 3 nopți) și completează datele.
2. Cererea se salvează în baza de date cu statusul `in_asteptare`.
3. Proprietarul primește un email; clientul primește confirmarea de primire.
4. Proprietarul confirmă telefonic/pe email. Zilele cu status `confirmata` apar ocupate în calendar.

Urmează: panou de administrare, plata online cu cardul, banner cookie + Google Ads / Meta Pixel, versiunea în engleză.

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
