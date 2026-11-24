<?php
/*
 * En-tête commun. $current = 'accueil' sur la page d'accueil (liens d'ancre animés),
 * sinon le nom de la page ('realisations' pour le portfolio, '' pour une page sans entrée de menu).
 */
$onHome = ($current ?? '') === 'accueil';
$base = $onHome ? '' : 'index.php';
$menu = ['accueil' => 'Accueil', 'services' => 'Services', 'offres' => 'Offres', 'realisations' => 'Réalisations', 'avis' => 'Avis', 'contact' => 'Contact'];
?>
<header class="top">
  <a class="brand" href="<?= $onHome ? '#accueil' : 'index.php' ?>" aria-label="<?= e($site['nom']) ?>, accueil">
    <span class="brand-logo"><?php $logoSize = 30; $logoT = ''; $logoF = ''; include __DIR__ . '/logo.php'; ?></span>
    <span class="brand-name">TF Production</span>
  </a>
  <nav class="nav" aria-label="Menu principal">
<?php foreach ($menu as $id => $label):
    // hors accueil, « Réalisations » mène à la page Portfolio
    $href = !$onHome && $id === 'realisations' ? 'portfolio.php' : ($id === 'accueil' && !$onHome ? 'index.php' : $base . '#' . $id);
    $on = $onHome ? $id === 'accueil' : $id === ($current ?? ''); ?>
    <a href="<?= $href ?>"<?= $on ? ' class="on"' . (!$onHome ? ' aria-current="page"' : '') : '' ?>><?= $label ?></a>
<?php endforeach; ?>
  </nav>
<?php if (!empty($noel) && ($current ?? '') !== 'cadeau'): ?>
  <a class="gift-top" href="carte-cadeau.php"><span aria-hidden="true">✦</span> Carte cadeau</a>
<?php endif; ?>
  <a class="btn btn-sm" href="<?= $base ?>#contact">Devis <span aria-hidden="true">↘</span></a>
  <button type="button" class="burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="menu-mobile"><span></span><span></span></button>
</header>

<!-- Menu mobile plein écran -->
<div class="mmenu" id="menu-mobile" hidden>
  <nav aria-label="Menu mobile">
<?php $k = 0; foreach ($menu as $id => $label):
    $href = !$onHome && $id === 'realisations' ? 'portfolio.php' : ($id === 'accueil' && !$onHome ? 'index.php' : $base . '#' . $id); ?>
    <a href="<?= $href ?>" style="--i:<?= $k++ ?>"><small><?= sprintf('%02d', $k) ?></small><?= $label ?></a>
<?php endforeach; ?>
    <a href="portfolio.php" style="--i:<?= $k++ ?>"><small>+</small>Tout le portfolio</a>
    <a href="carte-cadeau.php" style="--i:<?= $k++ ?>"><small>✦</small>Carte cadeau</a>
  </nav>
  <div class="mmenu-foot">
    <a class="btn" href="<?= $base ?>#contact">Demander un devis <span aria-hidden="true">↘</span></a>
    <p><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a><a href="<?= e($site['instagram']) ?>">Instagram</a></p>
  </div>
</div>
