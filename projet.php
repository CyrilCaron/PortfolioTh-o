<?php
/*
 * Page d'un projet : projet.php?p=<slug>. Le contenu vient de contenu/projets/<slug>.json,
 * mis en page bloc par bloc (voir partials/blocs.php pour la liste des blocs).
 */
require __DIR__ . '/partials/data.php';
require __DIR__ . '/partials/blocs.php';

$slug = (string) ($_GET['p'] ?? '');
// un brouillon n'est visible que si l'on est connecté à l'admin (aperçu)
if (!isset($fiches[$slug]) && isset($brouillons[$slug]) && isset($_COOKIE['tf_admin'])) {
    session_name('tf_admin'); session_start(['read_and_close' => true]);
    if (!empty($_SESSION['admin'])) $pj = $brouillons[$slug];
}
if (!isset($fiches[$slug]) && !isset($pj)) { header('Location: portfolio.php'); exit; }
$pj = $pj ?? $fiches[$slug];
$apercu = !isset($fiches[$slug]);
$slugs = array_keys($fiches);
$pos = array_search($slug, $slugs, true);
$suivant = $slugs ? $fiches[$slugs[$pos === false ? 0 : ($pos + 1) % count($slugs)]] : null;
$isVideo = $pj['type'] === 'video';

$pageTitle = $pj['titre'] . ' · ' . $site['nom'];
$pageDesc = $pj['resume'] ?? '';
$bodyClass = 'page-projet';
$heroImg = $pj['couverture'];
$current = 'realisations';
include __DIR__ . '/partials/head.php';
?>

<div class="bg" aria-hidden="true">
  <img src="<?= img($heroImg) ?>" srcset="<?= srcset($heroImg) ?>" sizes="100vw" alt="" class="on" fetchpriority="high">
  <div class="bg-shade"></div>
  <div class="bg-dim"></div>
</div>

<?php include __DIR__ . '/partials/header.php'; ?>

<?php if ($apercu): ?><p class="pj-draft">Brouillon : cette page n'est visible que par vous.</p><?php endif; ?>
<main class="pj">
  <section class="pj-hero">
    <a class="link pf-back" href="portfolio.php<?= $isVideo ? '#videos' : '' ?>"><span aria-hidden="true">←</span> Toutes les réalisations</a>
    <p class="eyebrow"><?= $isVideo ? 'Projet vidéo' : 'Projet photo' ?> · <?= e($pj['categorie']) ?></p>
    <h1 class="pj-title"><?= e($pj['titre']) ?></h1>
    <p class="pj-lead"><?= e($pj['resume'] ?? '') ?></p>
    <dl class="pj-meta">
<?php foreach (['Client' => 'client', 'Lieu' => 'lieu', 'Année' => 'annee', 'Livré' => 'livrables'] as $l => $k): if (!empty($pj[$k])): ?>
      <div><dt><?= $l ?></dt><dd><?= e($pj[$k]) ?></dd></div>
<?php endif; endforeach; ?>
    </dl>
    <a class="scroll-hint" href="#contenu" data-no-tx aria-label="Voir le projet">↓</a>
  </section>

  <div class="pj-body" id="contenu">
<?php foreach ($pj['blocs'] ?? [] as $b) { bloc($b, $pj['titre']); echo "\n"; } ?>
  </div>

<?php if ($suivant && $suivant['slug'] !== $pj['slug']): ?>
  <a class="pj-next reveal" href="<?= lien_projet($suivant['slug']) ?>">
    <img src="<?= img($suivant['couverture'], 'm') ?>" srcset="<?= srcset($suivant['couverture']) ?>" sizes="100vw" alt="" loading="lazy" decoding="async">
    <span class="pj-next-txt"><span class="eyebrow">Projet suivant</span><strong><?= e($suivant['titre']) ?></strong><span><?= e($suivant['categorie']) ?> · <?= $suivant['type'] === 'video' ? 'Vidéo' : 'Photo' ?></span></span>
    <span class="pj-next-go" aria-hidden="true">→</span>
  </a>
<?php endif; ?>

  <section class="pf-cta reveal">
    <p class="eyebrow">Un projet du même genre ?</p>
    <h2 class="title">Parlons de <em>votre projet</em></h2>
    <a class="btn" href="index.php#contact">Demander un devis <span aria-hidden="true">→</span></a>
  </section>

  <footer class="foot pf-foot">
    <span>© <?= date('Y') ?> TF Production</span>
    <a href="portfolio.php">Portfolio</a>
    <a href="mentions-legales.php">Mentions légales</a>
  </footer>
</main>

<!-- Visionneuse -->
<dialog class="lb" id="lb" aria-label="Visionneuse">
  <img alt="">
  <p class="lb-cap"></p>
  <button type="button" class="lb-x" aria-label="Fermer">✕</button>
  <button type="button" class="lb-prev" aria-label="Photo précédente">←</button>
  <button type="button" class="lb-next" aria-label="Photo suivante">→</button>
</dialog>

<?php include __DIR__ . '/partials/foot.php'; ?>
