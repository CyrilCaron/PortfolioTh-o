<?php
/*
 * Début commun à toutes les pages. Variables à définir avant l'include :
 *   $pageTitle, $pageDesc, $bodyClass (facultatif), $heroImg (facultatif : photo de fond à charger en priorité)
 */
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<script>/* transition : voile couvert dès le premier rendu si l'on arrive d'une autre page du site */try{if(sessionStorage.getItem('tf-tx'))document.documentElement.classList.add('tx-arrive')}catch(e){}</script>
<?php if (!empty($heroImg)): ?>
<link rel="preload" as="image" href="<?= img($heroImg) ?>" imagesrcset="<?= srcset($heroImg) ?>" imagesizes="100vw" fetchpriority="high">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;1,6..96,400;1,6..96,500&family=Manrope:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;1,6..96,400;1,6..96,500&family=Manrope:wght@400;500;600;700&display=swap"></noscript>
<link rel="stylesheet" href="assets/css/style.css?v=11">
<script src="assets/js/main.js?v=11" defer></script>
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
