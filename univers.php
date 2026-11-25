<?php
/*
 * Page d'un univers (/mariage, /video…) : même site, contenu ciblé pour Google et pour le visiteur.
 * Les textes et offres sont dans partials/univers.php.
 */
require __DIR__ . '/partials/data.php';
require __DIR__ . '/partials/univers.php';
$uid = (string) ($_GET['u'] ?? '');
if (!isset($univers[$uid])) { http_response_code(404); $uid = 'photo'; }
$u = $univers[$uid];

// Offres : propres à l'univers, ou reprises de l'accueil
$offres = match ($u['offres']) {
    'formules'   => array_map(fn($f) => [$f[0], $f[1], $f[2], $f[3]], $formules),
    'abonnement' => [['Réseaux sociaux', $abonnement[0], [$abonnement[1], $abonnement[2], 'Sans engagement'], true]],
    default      => array_map(fn($o) => [$o[0], $o[1], $o[2], false], $u['offres']),
};
// Projets de l'univers
$filtre = $u['projets'];
$liste = array_values(array_filter($projets, fn($p) =>
    (!isset($filtre['type']) || $p[7] === $filtre['type']) &&
    (!isset($filtre['categories']) || in_array($p[1], $filtre['categories'], true))));
$devis = 'index.php?formule=' . rawurlencode($u['formule']) . '#contact';

$pageTitle = $u['titre'] . ' · ' . $site['nom'];
$pageDesc = $u['google'];
$bodyClass = 'page-simple page-univers';
$heroImg = $u['fond'];
$current = 'univers';
include __DIR__ . '/partials/head.php';
?>

<div class="bg" aria-hidden="true">
  <img src="<?= img($heroImg) ?>" srcset="<?= srcset($heroImg) ?>" sizes="100vw" alt="" class="on" fetchpriority="high">
<?php if (!empty($u['video'])): // vidéo muette en boucle ; l'image reste dessous pendant le chargement ?>
  <video class="bg-video" autoplay muted loop playsinline preload="auto" poster="<?= img($heroImg, 'm') ?>">
    <source src="assets/video/<?= e($u['video']) ?>-m.mp4" type="video/mp4" media="(max-width:760px)">
    <source src="assets/video/<?= e($u['video']) ?>.mp4" type="video/mp4">
  </video>
<?php endif; ?>
  <div class="bg-shade"></div>
</div>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="pf">
  <section class="pf-head un-head">
    <a class="link pf-back" href="index.php"><span aria-hidden="true">←</span> TF Production</a>
    <p class="eyebrow"><?= e($u['menu']) ?> · Bruay-la-Buissière + 40 km</p>
    <h1 class="title"><?= $u['h1'] /* contient <em>, texte saisi dans le code */ ?></h1>
    <p class="lead"><?= e($u['lead']) ?></p>
    <div class="gc-actions">
      <a class="btn" href="<?= e($devis) ?>">Demander un devis <span aria-hidden="true">↘</span></a>
<?php if ($liste): ?>
      <a class="link" href="#projets">Voir les réalisations <span aria-hidden="true">↓</span></a>
<?php endif; ?>
    </div>
  </section>

  <section class="cols un-cols" aria-label="Prestations">
<?php foreach ($u['points'] as [$t, $d]): ?>
    <article class="col"><h3><?= e($t) ?></h3><p><?= e($d) ?></p></article>
<?php endforeach; ?>
  </section>

  <section class="un-offres">
    <p class="eyebrow">Offres</p>
    <div class="offers un-offers" style="--n:<?= count($offres) + 1 ?>">
<?php foreach ($offres as [$nom, $prix, $details, $vedette]): ?>
      <article class="offer<?= $vedette ? ' offer-star' : '' ?>">
        <h3><?= e($nom) ?></h3>
        <p class="price"><?= e($prix) ?></p>
        <ul><?php foreach ($details as $d): ?><li><?= e($d) ?></li><?php endforeach; ?></ul>
        <a class="link" href="<?= e($devis) ?>">Demander un devis <span aria-hidden="true">↘</span></a>
      </article>
<?php endforeach; ?>
      <article class="offer offer-gift">
        <?php if ($noel): ?><span class="badge">Idée cadeau de Noël</span><?php endif; ?>
        <h3>Carte cadeau</h3>
        <p class="price">Libre</p>
        <ul><li>Vous choisissez le montant</li><li>Valable sur toutes les prestations</li></ul>
        <a class="link" href="carte-cadeau.php">Offrir <span aria-hidden="true">→</span></a>
      </article>
    </div>
  </section>

<?php if ($liste): ?>
  <section id="projets" class="un-projets">
    <p class="eyebrow">Réalisations</p>
    <div class="un-grid">
<?php foreach ($liste as [$t, $c, $p, $y, $f, , $slug, $type]): ?>
      <a class="pf-item in<?= $type === 'video' ? ' is-video' : '' ?>" href="<?= lien_projet($slug) ?>">
        <img src="<?= img($p, 'm') ?>" srcset="<?= img($p, 's') ?> 520w, <?= img($p, 'm') ?> 1000w" sizes="(max-width:760px) 92vw, (max-width:1100px) 46vw, 31vw" alt="<?= e($t) ?>" loading="lazy" decoding="async">
        <?php if ($type === 'video'): ?><span class="play" aria-hidden="true"></span><?php endif; ?>
        <span class="pf-cap"><strong><?= e($t) ?></strong><span><?= e(implode(' · ', array_filter([$c, $y]))) ?></span><em>Voir le projet →</em></span>
      </a>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

  <section class="pf-cta">
    <p class="eyebrow">Un projet en tête ?</p>
    <h2 class="title">Parlons-en, <em>sans engagement</em></h2>
    <a class="btn" href="<?= e($devis) ?>">Demander un devis <span aria-hidden="true">→</span></a>
  </section>

  <nav class="un-autres" aria-label="Les autres univers">
    <span>Aussi :</span>
<?php foreach ($univers as $k => $v): if ($k === $uid) continue; ?>
    <a href="<?= lien_univers($k) ?>"><?= e($v['menu']) ?></a>
<?php endforeach; ?>
    <a href="carte-cadeau.php">Carte cadeau</a>
  </nav>

  <footer class="foot pf-foot">
    <span>© <?= date('Y') ?> TF Production</span>
    <a href="portfolio.php">Portfolio</a>
    <a href="index.php#contact">Contact</a>
    <a href="mentions-legales.php">Mentions légales</a>
  </footer>
</main>

<?php include __DIR__ . '/partials/foot.php'; ?>
