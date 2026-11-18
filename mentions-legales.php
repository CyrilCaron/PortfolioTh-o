<?php
/*
 * Mentions légales, et modèle pour toute nouvelle page du site.
 * Pour créer une page : copier ce fichier, changer les 5 variables ci-dessous et le contenu de <main>.
 * L'en-tête, le fond, la transition photo et le script sont ajoutés automatiquement par les partials.
 */
require __DIR__ . '/partials/data.php';
$pageTitle = 'Mentions légales · ' . $site['nom'];
$pageDesc = 'Mentions légales du site TF Production.';
$bodyClass = 'page-simple';
$heroImg = 'p5';
$current = '';
include __DIR__ . '/partials/head.php';
?>

<div class="bg" aria-hidden="true">
  <img src="<?= img($heroImg) ?>" srcset="<?= srcset($heroImg) ?>" sizes="100vw" alt="" class="on" fetchpriority="high">
  <div class="bg-shade"></div>
</div>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="pf">
  <section class="pf-head page-text">
    <a class="link pf-back" href="index.php"><span aria-hidden="true">←</span> Retour à l'accueil</a>
    <p class="eyebrow">Informations</p>
    <h1 class="title">Mentions <em>légales</em></h1>
    <h2>Éditeur du site</h2>
    <p>TF Production, Théo Fasquel, [statut et numéro SIRET à compléter], Bruay-la-Buissière (62700).<br>Contact : <?= e($site['email']) ?></p>
    <h2>Hébergement</h2>
    <p>[Nom, adresse et téléphone de l'hébergeur à compléter]</p>
    <h2>Propriété intellectuelle</h2>
    <p>Les photos et vidéos présentées sur ce site appartiennent à TF Production ou à ses clients. Toute reproduction sans accord écrit est interdite.</p>
    <h2>Données personnelles</h2>
    <p>Les informations envoyées via le formulaire de contact servent uniquement à répondre à votre demande. Elles ne sont ni revendues ni transmises. Vous pouvez demander leur suppression à tout moment par e-mail.</p>
  </section>

  <footer class="foot pf-foot">
    <span>© <?= date('Y') ?> TF Production</span>
    <a href="portfolio.php">Portfolio</a>
    <a href="index.php#contact">Contact</a>
  </footer>
</main>

<?php include __DIR__ . '/partials/foot.php'; ?>
