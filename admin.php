<?php
/*
 * Page Admin TF Production : créer et mettre en page les projets du portfolio.
 * - Premier passage : choisir le mot de passe (enregistré, chiffré, dans contenu/admin-config.php).
 * - Liste des projets : ordre, à la une, publié ou brouillon, suppression.
 * - Éditeur : informations, photos (envoi + conversion WebP automatique), blocs de mise en page.
 */
declare(strict_types=1);

// Diagnostic : si PHP s'arrête sur une erreur, on l'affiche en clair au lieu d'une page vide
ini_set('display_errors', '0');
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo '<div style="position:fixed;inset:20px auto auto 20px;z-index:99;max-width:760px;padding:16px 20px;border-radius:12px;background:#fff;color:#900;font:14px/1.5 monospace">'
            . '<strong>Erreur PHP</strong> (PHP ' . PHP_VERSION . ')<br>' . htmlspecialchars($e['message'] . ' — ' . basename($e['file']) . ' ligne ' . $e['line']) . '</div>';
    }
});
if (PHP_VERSION_ID < 80000) {
    exit('<p style="font:16px sans-serif;padding:30px">La page Admin demande PHP 8.0 ou plus. Version du serveur : ' . PHP_VERSION . '. Changez la version PHP dans le panneau de l\'hébergeur.</p>');
}
foreach (['gd', 'mbstring', 'json', 'session'] as $ext) {
    if (!extension_loaded($ext)) exit('<p style="font:16px sans-serif;padding:30px">Extension PHP manquante sur le serveur : ' . $ext . '.</p>');
}
// Sur Vercel, les fichiers du serveur sont en lecture seule : l'admin ne peut pas encore enregistrer.
// (prochaine étape : enregistrer les projets et les photos directement dans le dépôt GitHub)
if (getenv('VERCEL')) {
    header('X-Robots-Tag: noindex, nofollow');
    exit('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Admin · TF Production</title>'
        . '<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#07081A;color:#F2F5FB;font:16px/1.6 system-ui,sans-serif;padding:24px">'
        . '<div style="max-width:460px"><p style="font:28px Georgia,serif;margin:0 0 12px">Admin bientôt disponible</p>'
        . '<p style="color:#B9C4D9;margin:0 0 20px">Sur cet hébergement, les projets s\'ajoutent pour l\'instant via le dépôt GitHub. L\'enregistrement direct depuis cette page arrive dans la prochaine version.</p>'
        . '<a href="index.php" style="color:#C2D6FF">← Retour au site</a></div></body>');
}
require __DIR__ . '/partials/admin-lib.php';
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
admin_session();
$action = (string) ($_GET['a'] ?? '');
$erreur = '';

/* ---------- Mot de passe : création, connexion, déconnexion ---------- */
if (config() === null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        $m1 = (string) ($_POST['mdp'] ?? ''); $m2 = (string) ($_POST['mdp2'] ?? '');
        if (mb_strlen($m1) < 10) $erreur = 'Choisissez au moins 10 caractères.';
        elseif ($m1 !== $m2) $erreur = 'Les deux mots de passe ne correspondent pas.';
        elseif (!enregistrer_mot_de_passe($m1)) $erreur = 'Impossible d\'écrire contenu/admin-config.php (droits du dossier).';
        else { session_regenerate_id(true); $_SESSION['admin'] = true; header('Location: admin.php'); exit; }
    }
    $ecran = 'installation';
} elseif (!connecte()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        if (password_verify((string) ($_POST['mdp'] ?? ''), config()['hash'] ?? '')) {
            session_regenerate_id(true); $_SESSION['admin'] = true; header('Location: admin.php'); exit;
        }
        sleep(1); // freine les essais en série
        $erreur = 'Mot de passe incorrect.';
    }
    $ecran = 'connexion';
} else {
    $ecran = 'liste';
}

if ($ecran !== 'installation' && $ecran !== 'connexion') {
    $projets = lire_projets();

    /* ---------- Actions en arrière-plan (appelées par admin.js) ---------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_ok()) json_out(['err' => 'Session expirée, rechargez la page.'], 403);
        $slug = (string) ($_POST['slug'] ?? '');
        $p = $projets[$slug] ?? null;
        switch ($action) {
            case 'creer':
                $titre = txt($_POST['titre'] ?? '', 120) ?: 'Nouveau projet';
                $base = slugify($titre); $s = $base; $i = 2;
                while (isset($projets[$s]) || is_dir(IMG_DIR . '/' . $s)) $s = $base . '-' . $i++;
                $nouveau = ['ordre' => 0, 'slug' => $s, 'dossier' => $s, 'titre' => $titre, 'type' => ($_POST['type'] ?? '') === 'video' ? 'video' : 'photo',
                    'categorie' => 'Divers', 'annee' => date('Y'), 'client' => '', 'lieu' => '', 'livrables' => '', 'une' => false, 'publie' => false,
                    'couverture' => '', 'resume' => '', 'blocs' => []];
                ecrire_projet($nouveau);
                renumeroter(array_merge([$s], array_keys($projets))); // le nouveau projet arrive en premier
                json_out(['ok' => true, 'slug' => $s]);
            case 'enregistrer':
                if (!$p) json_out(['err' => 'Projet introuvable.'], 404);
                $in = json_decode((string) ($_POST['projet'] ?? ''), true);
                if (!is_array($in)) json_out(['err' => 'Données illisibles.'], 400);
                $propre = nettoyer_projet($in, $p, bibliotheque($p['dossier'] ?? $p['slug']));
                if (!ecrire_projet($propre)) json_out(['err' => 'Écriture impossible (droits du dossier contenu/projets).'], 500);
                json_out(['ok' => true]);
            case 'photo':
                if (!$p) json_out(['err' => 'Projet introuvable.'], 404);
                $f = $_FILES['photo'] ?? null;
                if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
                    $code = $f['error'] ?? -1;
                    json_out(['err' => in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Photo trop lourde pour l\'hébergement (limite ' . ini_get('upload_max_filesize') . ').' : 'Envoi interrompu.'], 400);
                }
                $r = importer_photo($f['tmp_name'], $p['dossier'] ?? $p['slug']);
                if (isset($r['err'])) json_out($r, 400);
                $id = $r['id'];
                $s = IMG_DIR . '/' . $id . '-s.webp'; [$w, $hh] = getimagesize($s) ?: [1, 1];
                json_out(['ok' => true, 'photo' => ['id' => $id, 'thumb' => 'assets/img/' . $id . '-s.webp', 'w' => $w, 'h' => $hh]]);
            case 'supprimer-photo':
                if (!$p) json_out(['err' => 'Projet introuvable.'], 404);
                $id = (string) ($_POST['id'] ?? '');
                if (!in_array($id, bibliotheque($p['dossier'] ?? $p['slug']), true)) json_out(['err' => 'Photo introuvable.'], 404);
                supprimer_photo($id);
                json_out(['ok' => true]);
            case 'ordre':
                $liste = json_decode((string) ($_POST['ordre'] ?? '[]'), true);
                if (!is_array($liste)) json_out(['err' => 'Ordre illisible.'], 400);
                renumeroter(array_values(array_filter($liste, fn($x) => is_string($x) && isset($projets[$x]))));
                json_out(['ok' => true]);
            case 'basculer':
                if (!$p) json_out(['err' => 'Projet introuvable.'], 404);
                $champ = ($_POST['champ'] ?? '') === 'une' ? 'une' : 'publie';
                $p[$champ] = !($p[$champ] ?? ($champ === 'publie'));
                ecrire_projet($p);
                json_out(['ok' => true, 'valeur' => $p[$champ]]);
            case 'supprimer':
                if (!$p) json_out(['err' => 'Projet introuvable.'], 404);
                // le fichier du projet va à la corbeille (contenu/corbeille), les photos restent sur le serveur
                $corb = __DIR__ . '/contenu/corbeille';
                if (!is_dir($corb)) mkdir($corb, 0755, true);
                rename(chemin_projet($slug), $corb . '/' . $slug . '-' . date('Ymd-His') . '.json');
                json_out(['ok' => true]);
            case 'deconnexion':
                $_SESSION = []; session_destroy();
                json_out(['ok' => true]);
        }
        json_out(['err' => 'Action inconnue.'], 400);
    }

    if ($action === 'editer') {
        $slug = (string) ($_GET['p'] ?? '');
        if (!isset($projets[$slug])) { header('Location: admin.php'); exit; }
        $ecran = 'editeur';
        $projet = $projets[$slug];
        $projet['publie'] = $projet['publie'] ?? true;
        $bib = bibliotheque_infos($projet['dossier'] ?? $slug);
        $cats = array_values(array_unique(array_filter(array_column($projets, 'categorie'))));
    }
}
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin · TF Production</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;1,6..96,400&family=Manrope:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/css/admin.css?v=2">
</head>
<body class="tfa tfa-<?= $ecran ?>" data-csrf="<?= h(csrf()) ?>">

<?php if ($ecran === 'installation' || $ecran === 'connexion'): ?>
<main class="tfa-login">
  <form method="post" class="tfa-card">
    <p class="tfa-brand">TF <em>Production</em></p>
    <h1><?= $ecran === 'installation' ? 'Créer le mot de passe' : 'Espace admin' ?></h1>
<?php if ($ecran === 'installation'): ?>
    <p class="tfa-hint">Première connexion : choisissez le mot de passe de l'espace admin (10 caractères minimum).</p>
<?php endif; ?>
<?php if ($erreur): ?><p class="tfa-err"><?= h($erreur) ?></p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label>Mot de passe<input type="password" name="mdp" autocomplete="<?= $ecran === 'installation' ? 'new-password' : 'current-password' ?>" required autofocus></label>
<?php if ($ecran === 'installation'): ?>
    <label>Confirmer<input type="password" name="mdp2" autocomplete="new-password" required></label>
<?php endif; ?>
    <button class="tfa-btn primary" type="submit"><?= $ecran === 'installation' ? 'Enregistrer' : 'Se connecter' ?></button>
    <a class="tfa-back" href="index.php">← Retour au site</a>
  </form>
</main>

<?php elseif ($ecran === 'liste'): ?>
<header class="tfa-top">
  <a class="tfa-brand" href="admin.php">TF <em>Production</em> <small>Admin</small></a>
  <nav><a href="index.php" target="_blank" rel="noopener">Voir le site ↗</a><button type="button" class="tfa-link" data-deconnexion>Déconnexion</button></nav>
</header>
<main class="tfa-main">
  <div class="tfa-head">
    <div><h1>Projets</h1><p class="tfa-hint">Glissez les cartes pour changer l'ordre du portfolio. Le premier projet apparaît en haut de l'accueil.</p></div>
    <form class="tfa-new" data-creer>
      <input name="titre" placeholder="Titre du nouveau projet" required maxlength="120">
      <select name="type"><option value="photo">Photo</option><option value="video">Vidéo</option></select>
      <button class="tfa-btn primary" type="submit">+ Créer</button>
    </form>
  </div>
<?php if (!$projets): ?>
  <p class="tfa-empty">Aucun projet pour l'instant. Créez le premier ci-dessus.</p>
<?php endif; ?>
  <ol class="tfa-list" data-liste>
<?php foreach ($projets as $s => $p): $pub = $p['publie'] ?? true; ?>
    <li class="tfa-item" draggable="true" data-slug="<?= h($s) ?>">
      <span class="tfa-grip" aria-hidden="true">⋮⋮</span>
      <span class="tfa-thumb"><?php if (!empty($p['couverture'])): ?><img src="assets/img/<?= h($p['couverture']) ?>-s.webp" alt=""><?php endif; ?></span>
      <span class="tfa-info">
        <strong><?= h($p['titre']) ?></strong>
        <span><?= $p['type'] === 'video' ? 'Vidéo' : 'Photo' ?> · <?= h($p['categorie'] ?? '') ?> · <?= h($p['annee'] ?? '') ?> · <?= count($p['blocs'] ?? []) ?> blocs</span>
      </span>
      <button type="button" class="tfa-tag<?= $pub ? ' on' : '' ?>" data-basculer="publie"><?= $pub ? 'Publié' : 'Brouillon' ?></button>
      <button type="button" class="tfa-tag<?= !empty($p['une']) ? ' on' : '' ?>" data-basculer="une">À la une</button>
      <a class="tfa-btn" href="admin.php?a=editer&amp;p=<?= h($s) ?>">Modifier</a>
      <a class="tfa-btn ghost" href="projet.php?p=<?= h($s) ?>" target="_blank" rel="noopener">Voir ↗</a>
      <button type="button" class="tfa-btn ghost danger" data-supprimer>Supprimer</button>
    </li>
<?php endforeach; ?>
  </ol>
</main>

<?php else: /* éditeur */ ?>
<header class="tfa-top">
  <a class="tfa-brand" href="admin.php">← Projets</a>
  <span class="tfa-status" data-status></span>
  <nav>
    <a class="tfa-btn ghost" href="projet.php?p=<?= h($projet['slug']) ?>" target="_blank" rel="noopener" data-apercu>Aperçu ↗</a>
    <button type="button" class="tfa-btn primary" data-enregistrer>Enregistrer</button>
  </nav>
</header>
<main class="tfa-main tfa-editor">
  <section class="tfa-panel">
    <h2>Informations</h2>
    <div class="tfa-fields">
      <label class="w2">Titre<input data-f="titre" maxlength="120"></label>
      <label>Type<select data-f="type"><option value="photo">Photo</option><option value="video">Vidéo</option></select></label>
      <label>Catégorie<input data-f="categorie" list="cats" maxlength="60"><datalist id="cats"><?php foreach ($cats as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?></datalist></label>
      <label>Année<input data-f="annee" maxlength="10" inputmode="numeric"></label>
      <label>Client<input data-f="client" maxlength="120" placeholder="ex. Projet personnel"></label>
      <label>Lieu<input data-f="lieu" maxlength="120"></label>
      <label>Livré<input data-f="livrables" maxlength="120" placeholder="ex. 40 photos, 2 reels"></label>
      <label class="w3">Résumé (une phrase, affichée sous le titre et sur l'accueil)<textarea data-f="resume" rows="2" maxlength="300"></textarea></label>
      <label class="tfa-check"><input type="checkbox" data-f="publie"> Publié sur le site</label>
      <label class="tfa-check"><input type="checkbox" data-f="une"> À la une sur l'accueil</label>
    </div>
  </section>

  <section class="tfa-panel">
    <div class="tfa-panel-head">
      <h2>Photos <small data-nb></small></h2>
      <div>
        <button type="button" class="tfa-btn ghost" data-auto>Mise en page automatique</button>
        <label class="tfa-btn primary">+ Ajouter des photos<input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-upload></label>
      </div>
    </div>
    <p class="tfa-hint">Glissez vos photos ici. Elles sont converties en WebP (3 tailles) automatiquement. Cliquez sur ★ pour choisir la couverture.</p>
    <div class="tfa-drop" data-drop><div class="tfa-lib" data-lib></div><div class="tfa-progress" data-progress hidden><span></span></div></div>
  </section>

  <section class="tfa-panel">
    <h2>Mise en page</h2>
    <p class="tfa-hint">Ajoutez des blocs, glissez-les par la poignée ⋮⋮ pour changer l'ordre. Cliquez sur un emplacement photo pour choisir une image.</p>
    <div class="tfa-blocs" data-blocs></div>
    <div class="tfa-add">
      <span>Ajouter :</span>
      <button type="button" data-add="texte">Texte</button>
      <button type="button" data-add="photo">Photo</button>
      <button type="button" data-add="duo">Duo</button>
      <button type="button" data-add="grille">Grille</button>
      <button type="button" data-add="chiffres">Chiffres</button>
      <button type="button" data-add="citation">Citation</button>
      <button type="button" data-add="video">Vidéo</button>
      <button type="button" data-add="reels">Reels</button>
    </div>
  </section>
</main>

<dialog class="tfa-picker" data-picker>
  <form method="dialog" class="tfa-picker-in">
    <header><h2 data-picker-title>Choisir une photo</h2><button class="tfa-x" value="annuler" aria-label="Fermer">✕</button></header>
    <div class="tfa-lib pick" data-picker-lib></div>
    <footer><span class="tfa-hint" data-picker-hint></span><button class="tfa-btn primary" value="ok">Valider</button></footer>
  </form>
</dialog>

<script id="donnees" type="application/json"><?= json_encode(['projet' => $projet, 'bib' => $bib], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<script src="assets/js/admin.js?v=2" defer></script>
</body>
</html>
