<?php
// Partea de sus, comună tuturor paginilor (meniu + <head>).
// Fiecare pagină setează $titlu înainte să includă acest fișier.
$titlu = $titlu ?? 'Vila Bușteni';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titlu) ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="antet">
  <a href="/" class="logo">Vila Bușteni</a>
  <nav>
    <a href="/">Acasă</a>
    <a href="/#camere">Camere</a>
    <a href="/#contact">Contact</a>
  </nav>
</header>

<main>
