<?php
/*
 * Maquette d'accueil TF Production (v6) : verre liquide, défilement section par section, page Portfolio avec transition.
 * Inspirée de la structure du portfolio de Maxime Richard : sections plein écran, fond photo qui change
 * selon la section, grand titre étiré, carte « derniers projets », contact en 3 cartes.
 * Adaptée aux besoins de Théo : offres et prix visibles, demande de devis partout, avis Malt, cible locale.
 * Pour changer un texte, une image ou un prix, modifier les tableaux ci-dessous.
 */
require __DIR__ . '/partials/data.php';
$pageTitle = $site['nom'] . ' · Photographe et vidéaste à Bruay-la-Buissière';
$pageDesc = $site['accroche'];
$heroImg = $fonds['accueil'];
$current = 'accueil';
include __DIR__ . '/partials/head.php';
?>

<!-- Fond fixe : une photo par section, en fondu. Seule la première est chargée tout de suite. -->
<div class="bg" aria-hidden="true">
<?php foreach (array_unique(array_merge(array_values($fonds), array_column($projets, 2), array_column($services, 2))) as $p): ?>
<?php if ($p === $heroImg): ?>
  <img src="<?= img($p) ?>" srcset="<?= srcset($p) ?>" sizes="100vw" alt="" data-bg="<?= $p ?>" class="on" fetchpriority="high">
<?php else: ?>
  <img data-srcset="<?= srcset($p) ?>" sizes="100vw" alt="" data-bg="<?= $p ?>" decoding="async">
<?php endif; ?>
<?php endforeach; ?>
  <div class="bg-shade"></div>
</div>

<?php include __DIR__ . '/partials/header.php'; ?>

<nav class="pager" aria-label="Sections">
<?php foreach (array_keys($fonds) as $i => $id): ?>
  <a href="#<?= $id ?>"<?= $i === 0 ? ' class="on"' : '' ?> aria-label="Section <?= $i + 1 ?>"><span><?= sprintf('%02d', $i + 1) ?></span></a>
<?php endforeach; ?>
</nav>

<main>

  <!-- 1. Accueil -->
  <section id="accueil" class="panel hero" data-bg="<?= $fonds['accueil'] ?>">
    <div class="hero-center">
      <span class="hero-logo"><?php $logoSize = 76; $logoT = 'tf-draw'; $logoF = 'tf-draw-f'; include __DIR__ . '/partials/logo.php'; ?></span>
      <h1 class="hero-title"><span>TF</span> <em>Production</em></h1>
      <p class="hero-roles"><?php foreach ($site['roles'] as $r): ?><span><?= e($r) ?></span><?php endforeach; ?></p>
    </div>
    <div class="hero-foot">
      <div class="hero-pitch">
        <p><?= e($site['accroche']) ?></p>
        <a class="btn" href="#contact">Demander un devis <span aria-hidden="true">↘</span></a>
      </div>
<?php if ($derniers): ?>
      <aside class="latest" aria-label="Derniers projets">
        <div class="latest-img">
<?php foreach ($derniers as $i => [$t, $d, $p]): ?>
          <img src="<?= img($p, 's') ?>" alt="<?= e($t) ?>" decoding="async"<?= $i === 0 ? ' class="on"' : '' ?>>
<?php endforeach; ?>
        </div>
        <div class="latest-body">
          <p class="latest-top"><span><?= count($derniers) > 1 ? 'Derniers projets' : 'Dernier projet' ?></span><span class="latest-count"><?= count($derniers) > 1 ? '1 / ' . count($derniers) : '' ?></span></p>
<?php foreach ($derniers as $i => [$t, $d, $p, $slug]): ?>
          <div data-href="<?= lien_projet($slug) ?>" class="latest-txt<?= $i === 0 ? ' on' : '' ?>">
            <h2><?= e($t) ?></h2>
            <p><?= e($d) ?></p>
          </div>
<?php endforeach; ?>
          <a class="link latest-link" href="<?= lien_projet($derniers[0][3]) ?>">Voir le projet <span aria-hidden="true">↘</span></a>
          <?php if (count($derniers) > 1): ?><span class="latest-bar"><span></span></span><?php endif; ?>
        </div>
      </aside>
<?php endif; ?>
    </div>
    <a class="scroll-hint" href="#services" aria-label="Défiler vers les services">↓</a>
  </section>

  <!-- 2. Services + confiance -->
  <section id="services" class="panel" data-bg="<?= $fonds['services'] ?>">
    <div class="panel-head">
      <p class="eyebrow">Services</p>
      <h2 class="title">Des images qui <em>travaillent</em> pour vous</h2>
      <p class="lead">Du shooting à la publication, je m'occupe de tout pour que votre entreprise ait de belles images à montrer, chaque mois.</p>
      <a class="btn" href="#contact">Me contacter <span aria-hidden="true">↘</span></a>
    </div>
    <div class="trust">
      <p class="eyebrow">Ils me font confiance</p>
      <div class="trust-logos"><span>Logo partenaire</span><span>Logo partenaire</span><span>Logo partenaire</span><span>Logo partenaire</span><span>Logo partenaire</span></div>
    </div>
    <div class="cols">
<?php foreach ($services as [$t, $d, $p]): ?>
      <article class="col" data-hover-bg="<?= $p ?>">
        <h3><?= e($t) ?></h3>
        <p><?= e($d) ?></p>
      </article>
<?php endforeach; ?>
    </div>
    <p class="swipe-hint" aria-hidden="true">Faites glisser <span>→</span></p>
  </section>

  <!-- 3. Offres (ajout pour Théo : prix affichés) -->
  <section id="offres" class="panel" data-bg="<?= $fonds['offres'] ?>">
    <div class="panel-head">
      <p class="eyebrow">Offres</p>
      <h2 class="title">Des prix <em>clairs</em>, sans surprise</h2>
      <p class="lead">Une prestation ponctuelle ou un suivi chaque mois. Vous savez ce que vous payez avant de commencer.</p>
    </div>
    <div class="offers">
<?php foreach ($formules as [$nom, $prix, $details, $vedette]): ?>
      <article class="offer<?= $vedette ? ' offer-star' : '' ?>">
        <?php if ($vedette): ?><span class="badge">Le plus choisi</span><?php endif; ?>
        <h3><?= e($nom) ?></h3>
        <p class="price"><?= e($prix) ?></p>
        <ul><?php foreach ($details as $d): ?><li><?= e($d) ?></li><?php endforeach; ?></ul>
        <a class="link" href="#contact" data-formule="<?= e($nom) ?>">Choisir <?= e($nom) ?> <span aria-hidden="true">↘</span></a>
      </article>
<?php endforeach; ?>
      <article class="offer offer-sub">
        <h3>Réseaux sociaux</h3>
        <p class="price"><?= e($abonnement[0]) ?></p>
        <ul><li><?= e($abonnement[1]) ?></li><li><?= e($abonnement[2]) ?></li><li>Sans engagement</li></ul>
        <a class="link" href="#contact" data-formule="Réseaux sociaux">En parler <span aria-hidden="true">↘</span></a>
      </article>
    </div>
    <p class="swipe-hint" aria-hidden="true">Faites glisser <span>→</span></p>
  </section>

  <!-- 4. Réalisations : index des projets à la une + grand aperçu, chaque projet ouvre sa page -->
<?php $une = array_values(array_filter($projets, fn($p) => $p[5])) ?: array_slice($projets, 0, 5); // à défaut, les 5 premiers ?>
  <section id="realisations" class="panel works" data-bg="<?= $fonds['realisations'] ?>">
    <div class="works-index">
      <p class="eyebrow">Portfolio · à la une</p>
      <h2 class="title">Mes <em>réalisations</em></h2>
      <ol class="index">
<?php foreach ($une as $i => [$t, $c, $p, $y, $f, , $slug]): ?>
        <li><a href="<?= lien_projet($slug) ?>" data-pv="<?= $i ?>" data-hover-bg="<?= $p ?>"<?= $i === 0 ? ' class="on"' : '' ?>>
          <span class="ix-n"><?= sprintf('%02d', $i + 1) ?></span>
          <span class="ix-t"><?= e($t) ?></span>
          <span class="ix-c"><?= e($c) ?></span>
        </a></li>
<?php endforeach; ?>
      </ol>
      <a class="link ix-all" href="portfolio.php">Tout le portfolio, <?= n_projets(count($projets)) ?> <span aria-hidden="true">→</span></a>
    </div>
<?php if ($une): ?>
    <div class="works-frame">
      <div class="frame-img">
<?php foreach ($une as $i => [$t, $c, $p, $y, $f]): ?>
        <img src="<?= img($p, 'm') ?>" srcset="<?= img($p, 'm') ?> 1000w, <?= img($p) ?> 1800w" sizes="(max-width:1100px) 100vw, 50vw" alt="<?= e($t) ?>" loading="lazy" decoding="async"<?= $i === 0 ? ' class="on"' : '' ?>>
<?php endforeach; ?>
      </div>
      <div class="frame-cap">
<?php foreach ($une as $i => [$t, $c, $p, $y, $f]): ?>
        <p class="<?= $i === 0 ? 'on' : '' ?>"><strong><?= e($t) ?></strong><span><?= e(implode(' · ', array_filter([$f, $y]))) ?></span></p>
<?php endforeach; ?>
      </div>
      <a class="btn frame-cta" href="portfolio.php"><?= count($projets) > 1 ? 'Voir les ' . n_projets(count($projets)) : 'Voir le portfolio' ?> <span aria-hidden="true">→</span></a>
    </div>
<?php endif; ?>
  </section>

  <!-- 5. Avis -->
  <section id="avis" class="panel" data-bg="<?= $fonds['avis'] ?>">
    <div class="panel-head">
      <p class="eyebrow">Avis clients · 5/5 sur Malt</p>
      <h2 class="title">Ils en <em>parlent</em> mieux que moi</h2>
      <div class="quotes">
<?php foreach ($avis as $i => [$q, $n, $r]): ?>
        <figure class="quote<?= $i === 0 ? ' on' : '' ?>">
          <blockquote>« <?= e($q) ?> »</blockquote>
          <figcaption><?= e($n) ?> · <?= e($r) ?></figcaption>
        </figure>
<?php endforeach; ?>
      </div>
      <div class="quote-nav">
<?php foreach ($avis as $i => $a): ?>
        <button type="button" aria-label="Avis <?= $i + 1 ?>"<?= $i === 0 ? ' class="on"' : '' ?>></button>
<?php endforeach; ?>
        <a class="link" href="<?= e($site['malt']) ?>">Tous les avis sur Malt <span aria-hidden="true">↘</span></a>
      </div>
    </div>
  </section>

  <!-- 6. Contact : formulaire visible + zone d'intervention -->
  <section id="contact" class="panel contact" data-bg="<?= $fonds['contact'] ?>">
    <div class="contact-grid">
      <div class="contact-form">
        <p class="eyebrow">Contact · devis gratuit</p>
        <h2 class="title">Parlons de <em>votre projet</em></h2>
<?php if ($envoye): ?>
        <p class="ok">Merci, votre demande est bien partie. Je vous réponds sous 24 h.</p>
<?php else: ?>
        <form method="post" action="#contact">
          <div class="row">
            <label>Nom<input name="nom" autocomplete="name" placeholder="Votre nom" required></label>
            <label>E-mail<input name="email" type="email" autocomplete="email" placeholder="vous@entreprise.fr" required></label>
          </div>
          <fieldset>
            <legend>Ce qui vous intéresse</legend>
            <div class="chips">
<?php foreach (array_merge(array_column($formules, 0), ['Réseaux sociaux', 'Je ne sais pas encore']) as $i => $nom): ?>
              <label class="chip"><input type="radio" name="formule" value="<?= e($nom) ?>"<?= $nom === 'Je ne sais pas encore' ? ' checked' : '' ?>><span><?= e($nom) ?></span></label>
<?php endforeach; ?>
            </div>
          </fieldset>
          <label>Votre projet<textarea name="message" rows="3" placeholder="Votre activité, ce que vous voulez montrer, une date…"></textarea></label>
          <div class="send">
            <button type="submit" class="btn">Envoyer ma demande <span aria-hidden="true">→</span></button>
            <span>Réponse sous 24 h · sans engagement</span>
          </div>
        </form>
<?php endif; ?>
      </div>
      <aside class="contact-side">
        <div class="zone">
          <div class="radar" aria-hidden="true"><span></span><span></span><span></span><i></i></div>
          <div>
            <p class="eyebrow">Zone d'intervention</p>
            <p class="zone-t">Bruay-la-Buissière <em>+ 40 km</em></p>
            <p class="zone-s">Béthune, Lens, Arras, Saint-Pol et alentours. Au-delà, sur devis.</p>
          </div>
        </div>
        <div class="direct">
          <button type="button" class="direct-row copy" data-copy="<?= e($site['email']) ?>"><span class="d-ico" aria-hidden="true">✉</span><span class="d-txt"><strong>E-mail</strong><?= e($site['email']) ?></span><span class="d-act">Copier</span></button>
          <a class="direct-row" href="<?= e($site['agenda']) ?>"><span class="d-ico" aria-hidden="true">☏</span><span class="d-txt"><strong>Appel de 15 min</strong>Choisir un créneau</span><span class="d-act">→</span></a>
          <a class="direct-row" href="<?= e($site['instagram']) ?>"><span class="d-ico" aria-hidden="true">◎</span><span class="d-txt"><strong>Instagram</strong>Les derniers shootings</span><span class="d-act">→</span></a>
        </div>
      </aside>
    </div>
    <footer class="foot">
      <span>© <?= date('Y') ?> TF Production</span>
      <a href="portfolio.php">Portfolio</a>
      <a href="mentions-legales.php">Mentions légales</a>
    </footer>
  </section>

</main>
<?php include __DIR__ . '/partials/foot.php'; ?>
