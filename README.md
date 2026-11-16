# vilabusteni.ro

Site de prezentare și rezervări pentru Vila Bușteni.
PHP simplu, fără framework. Merge pe orice găzduire cPanel.

## Structura

```
site/                  ← tot ce e aici ajunge în public_html pe server
  index.php            pagina principală
  includes/
    header.php         meniul și partea de sus (comune tuturor paginilor)
    footer.php         subsolul
  assets/
    css/style.css      aspectul (culorile sunt sus, în :root)
    js/main.js         scripturi
    images/            pozele
  config.example.php   model pentru config.php (date bază de date)
.github/workflows/
  deploy.yml           deploy automat pe cPanel
```

Regula simplă: **o pagină = un fișier `.php` în `site/`**.
Meniul se modifică într-un singur loc: `includes/header.php`.

## Deploy automat

La fiecare modificare pe ramura `main`, GitHub urcă automat folderul `site/`
în `public_html` prin FTP. Progresul se vede în tab-ul **Actions**.

Configurare (o singură dată), în GitHub → Settings → Secrets and variables → Actions:

| Secret         | Ce pui                                  |
|----------------|-----------------------------------------|
| `FTP_SERVER`   | `ftp78.claus.ro`                        |
| `FTP_USERNAME` | `deploy@vilabusteni.ro` (cont FTP dedicat) |
| `FTP_PASSWORD` | parola contului FTP dedicat             |

Contul FTP dedicat se creează în cPanel → FTP Accounts, cu directorul `public_html`.

## config.php

Pe server, în `public_html`, copiază `config.example.php` ca `config.php`
și completează datele bazei de date. Fișierul nu e în GitHub și deploy-ul nu îl atinge.
