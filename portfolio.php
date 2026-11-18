<?php
/*
 * Page Portfolio TF Production : toutes les réalisations, séparées en Photos / Vidéos, avec filtres par catégorie.
 * Chaque carte ouvre la page du projet (projet.php).
 * Les contenus viennent de partials/data.php (même source que l'accueil).
 */
require __DIR__ . '/partials/data.php';
$categories = array_values(array_unique(array_column($projets, 1)));
$pageTitle = 'Portfolio · ' . $site['nom'];
$pageDesc = 'Toutes les réalisations photo et vidéo de TF Production.';
$bodyClass = 'page-portfolio';
$heroImg = 'p4';
$current = 'realisations';
include __DIR__ . '/partials/head.php';
?>

<div class="bg" aria-hidden="true">
  <img src="<?= img('p4') ?>" srcset="<?= srcset('p4') ?>" sizes="100vw" alt="" data-bg="p4" class="on" fetchpriority="high">
  <div class="bg-shade"></div>
</div>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="pf">
  <section class="pf-head">
    <a class="link pf-back" href="index.php#realisations"><span aria-hidden="true">←</span> Retour à l'accueil</a>
    <p class="eyebrow">Portfolio</p>
    <h1 class="title">Toutes les <em>réalisations</em></h1>
    <p class="lead">Automobile, événements, spectacles et portraits : une sélection de shootings photo et vidéo réalisés pour des entreprises et des associations de la région.</p>
    <div class="pf-tools">
      <div class="seg" role="tablist" aria-label="Type de réalisation">
        <button type="button" role="tab" class="on" aria-selected="true" data-type="photo">Photos <small><?= count(array_filter($projets, fn($p) => $p[7] === 'photo')) ?></small></button>
        <button type="button" role="tab" aria-selected="false" data-type="video">Vidéos <small><?= count(array_filter($projets, fn($p) => $p[7] === 'video')) ?></small></button>
        <span class="seg-pill" aria-hidden="true"></span>
      </div>
      <ul class="filters" role="list">
        <li><button type="button" class="on" data-filter="*">Tout</button></li>
<?php foreach ($categories as $c): ?>
        <li><button type="button" data-filter="<?= e($c) ?>"><?= e($c) ?> <small></small></button></li>
<?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="pf-grid" aria-label="Réalisations">
<?php foreach ($projets as $i => [$t, $c, $p, $y, $f, , $slug, $type]): ?>
    <a class="pf-item<?= $type === 'video' ? ' is-video' : '' ?>" href="<?= lien_projet($slug) ?>" data-cat="<?= e($c) ?>" data-type="<?= $type ?>">
      <img src="<?= img($p, 'm') ?>" srcset="<?= img($p, 's') ?> 520w, <?= img($p, 'm') ?> 1000w" sizes="(max-width:760px) 92vw, (max-width:1100px) 46vw, 31vw" alt="<?= e($t) ?>" loading="lazy" decoding="async">
<?php if ($type === 'video'): ?>
      <span class="play" aria-hidden="true"></span>
<?php endif; ?>
      <span class="pf-cap"><strong><?= e($t) ?></strong><span><?= e(implode(' · ', array_filter([$c, $y]))) ?></span><em>Voir le projet →</em></span>
    </a>
<?php endforeach; ?>
    <p class="pf-empty" hidden>Aucun projet ici pour l'instant, ils arrivent bientôt.</p>
  </section>

  <section class="pf-cta">
    <p class="eyebrow">Un projet en tête ?</p>
    <h2 class="title">Votre entreprise <em>mérite</em> ces images</h2>
    <a class="btn" href="index.php#contact">Demander un devis <span aria-hidden="true">→</span></a>
  </section>

  <footer class="foot pf-foot">
    <span>© <?= date('Y') ?> TF Production</span>
    <span>Bruay-la-Buissière · Hauts-de-France</span>
    <a href="mentions-legales.php">Mentions légales</a>
  </footer>
</main>


<?php include __DIR__ . '/partials/foot.php'; ?>
