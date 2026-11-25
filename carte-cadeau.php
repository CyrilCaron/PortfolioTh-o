<?php
/*
 * Carte cadeau : le client choisit le montant, la carte vaut pour n'importe quelle prestation.
 * Paiement par lien Stripe ($cadeau['stripe'] dans partials/data.php). Mise en avant Noël via $noel.
 */
require __DIR__ . '/partials/data.php';
$pageTitle = 'Carte cadeau photo et vidéo · ' . $site['nom'];
$pageDesc = 'Offrez une carte cadeau TF Production : vous choisissez le montant, la personne choisit sa prestation (photo, vidéo, portrait, mariage, réseaux sociaux).';
$bodyClass = 'page-simple page-cadeau';
$heroImg = 'p7';
$current = 'cadeau';
$lienAchat = $cadeau['stripe'] ?: 'index.php?formule=Carte+cadeau#contact';
include __DIR__ . '/partials/head.php';
?>

<div class="bg" aria-hidden="true">
  <img src="<?= img($heroImg) ?>" srcset="<?= srcset($heroImg) ?>" sizes="100vw" alt="" class="on" fetchpriority="high">
  <div class="bg-shade"></div>
</div>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="pf">
  <section class="gc-hero">
    <div class="pf-head gc-intro">
      <a class="link pf-back" href="index.php"><span aria-hidden="true">←</span> Retour à l'accueil</a>
      <p class="eyebrow"><?= $noel ? 'Idée cadeau de Noël' : 'Idée cadeau' ?></p>
      <h1 class="title">Offrez un <em>souvenir</em>, pas un objet</h1>
      <p class="lead">Vous choisissez le montant. La personne qui la reçoit choisit sa prestation : portrait, photos de famille, mariage, vidéo, reels pour son entreprise… La carte vaut pour <strong>n'importe quelle prestation TF Production</strong>.</p>
      <div class="gc-actions">
        <a class="btn" href="<?= e($lienAchat) ?>"<?= $cadeau['stripe'] ? ' data-no-tx rel="noopener"' : '' ?>>Offrir une carte cadeau <span aria-hidden="true">→</span></a>
        <span class="gc-note">Paiement sécurisé · dès <?= e($cadeau['min']) ?></span>
      </div>
    </div>

    <!-- La carte, en verre -->
    <div class="gc-card" aria-hidden="true">
      <div class="gc-card-in">
        <span class="gc-logo"><?php $logoSize = 46; $logoT = ''; $logoF = ''; include __DIR__ . '/partials/logo.php'; ?></span>
        <p class="gc-k">Carte cadeau</p>
        <p class="gc-v">Montant <em>libre</em></p>
        <p class="gc-s">Photo · Vidéo · Réseaux sociaux</p>
        <p class="gc-b"><span>TF Production</span><span>Valable <?= e($cadeau['validite']) ?></span></p>
        <?php if ($noel): ?><span class="gc-ribbon">Noël <?= date('Y') ?></span><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="gc-amounts" aria-label="Exemples de montants">
<?php foreach ($cadeau['montants'] as [$m, $l]): ?>
    <a class="offer gc-amount" href="<?= e($lienAchat) ?>"<?= $cadeau['stripe'] ? ' data-no-tx rel="noopener"' : '' ?>>
      <p class="price"><?= e($m) ?></p>
      <p><?= e($l) ?></p>
    </a>
<?php endforeach; ?>
  </section>

  <section class="pf-head gc-steps">
    <p class="eyebrow">Comment ça marche</p>
    <ol>
      <li><strong>Vous choisissez le montant</strong><span>À partir de <?= e($cadeau['min']) ?>, sans plafond.</span></li>
      <li><strong>Vous payez en ligne</strong><span>Paiement sécurisé par carte bancaire.</span></li>
      <li><strong>Vous recevez la carte par e-mail</strong><span>Sous 24 h, prête à imprimer ou à transférer.</span></li>
      <li><strong>La personne choisit sa prestation</strong><span>Elle me contacte, on fixe la date ensemble.</span></li>
    </ol>
  </section>

  <section class="pf-head page-text gc-faq">
    <p class="eyebrow">Questions fréquentes</p>
    <h2>Sur quelles prestations est-elle valable ?</h2>
    <p>Toutes : séance photo, portrait, famille, mariage, événement, vidéo, reels, gestion des réseaux sociaux. C'est la personne qui reçoit la carte qui choisit.</p>
    <h2>Et si la prestation coûte plus que la carte ?</h2>
    <p>La carte est déduite du prix, la personne règle simplement la différence. Si elle coûte moins, le reste peut servir pour une prochaine prestation.</p>
    <h2>Combien de temps est-elle valable ?</h2>
    <p><?= e(ucfirst($cadeau['validite'])) ?> à partir de la date d'achat.</p>
    <h2>Je peux ajouter un message ?</h2>
    <p>Oui, indiquez-le au moment du paiement : il sera écrit sur la carte.</p>
  </section>

  <section class="pf-cta">
    <p class="eyebrow"><?= $noel ? 'Encore une idée pour le sapin ?' : 'Un cadeau qui se garde' ?></p>
    <h2 class="title">Un beau moment, <em>en images</em></h2>
    <a class="btn" href="<?= e($lienAchat) ?>"<?= $cadeau['stripe'] ? ' data-no-tx rel="noopener"' : '' ?>>Offrir une carte cadeau <span aria-hidden="true">→</span></a>
  </section>

  <footer class="foot pf-foot">
    <span>© <?= date('Y') ?> TF Production</span>
    <a href="portfolio.php">Portfolio</a>
    <a href="index.php#contact">Contact</a>
    <a href="mentions-legales.php">Mentions légales</a>
  </footer>
</main>

<?php include __DIR__ . '/partials/foot.php'; ?>
