<?php
/*
 * Outils de la page Admin : connexion, lecture/écriture des projets, traitement des photos.
 * Les projets sont des fichiers JSON dans contenu/projets/, les photos dans assets/img/<dossier>/.
 */
declare(strict_types=1);

const ADMIN_CONFIG = __DIR__ . '/../contenu/admin-config.php';
const PROJETS_DIR  = __DIR__ . '/../contenu/projets';
const IMG_DIR      = __DIR__ . '/../assets/img';
const TAILLES      = ['' => [1800, 76], '-m' => [1000, 74], '-s' => [520, 70]]; // suffixe => [largeur max, qualité]
const TYPES_BLOCS  = ['texte', 'photo', 'duo', 'grille', 'chiffres', 'citation', 'video', 'reels'];

/* ---------- Session et sécurité ---------- */
function admin_session(): void {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('tf_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => $https]);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf(): string { return $_SESSION['csrf']; }
function csrf_ok(): bool {
    $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    return is_string($t) && hash_equals($_SESSION['csrf'], $t);
}
function connecte(): bool { return !empty($_SESSION['admin']); }
function config(): ?array { return is_file(ADMIN_CONFIG) ? (include ADMIN_CONFIG) : null; }
function enregistrer_mot_de_passe(string $mdp): bool {
    $code = "<?php\n// Généré par admin.php. Pour réinitialiser le mot de passe : supprimer ce fichier puis ouvrir admin.php.\nreturn " . var_export(['hash' => password_hash($mdp, PASSWORD_DEFAULT)], true) . ";\n";
    return file_put_contents(ADMIN_CONFIG, $code, LOCK_EX) !== false;
}
function json_out(array $data, int $code = 200): void { // ne revient jamais (exit)
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- Projets ---------- */
function slugify(string $s): string {
    $s = strtolower(trim((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s)));
    $s = trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    return substr($s, 0, 60) ?: 'projet';
}
function slug_valide(string $s): bool { return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $s); }
function chemin_projet(string $slug): string { return PROJETS_DIR . '/' . $slug . '.json'; }

/** @return array<string, array> projets triés par ordre */
function lire_projets(): array {
    $out = [];
    foreach (glob(PROJETS_DIR . '/*.json') ?: [] as $f) {
        $j = json_decode((string) file_get_contents($f), true);
        if (is_array($j) && !empty($j['slug'])) $out[$j['slug']] = $j;
    }
    uasort($out, fn($a, $b) => ($a['ordre'] ?? 99) <=> ($b['ordre'] ?? 99));
    return $out;
}
function ecrire_projet(array $p): bool {
    if (!is_dir(PROJETS_DIR)) mkdir(PROJETS_DIR, 0755, true);
    return file_put_contents(chemin_projet($p['slug']), json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}
function renumeroter(array $slugs): void {
    $tous = lire_projets();
    foreach (array_values($slugs) as $i => $s) {
        if (isset($tous[$s])) { $tous[$s]['ordre'] = $i + 1; ecrire_projet($tous[$s]); }
    }
}

/* ---------- Nettoyage d'un projet envoyé par l'éditeur ---------- */
function txt($v, int $max = 2000): string { return mb_substr(trim(is_string($v) ? $v : ''), 0, $max); }
function photo_ok($v, array $bib): string { return is_string($v) && in_array($v, $bib, true) ? $v : ''; }
function url_ok($v): string {
    $v = txt($v, 500);
    return $v === '' || preg_match('~^https?://~i', $v) || preg_match('~^assets/~', $v) ? $v : '';
}

function nettoyer_projet(array $in, array $existant, array $bib): array {
    $p = [
        'ordre'      => (int) ($existant['ordre'] ?? 999),
        'slug'       => $existant['slug'] ?? '',
        'dossier'    => $existant['dossier'] ?? ($existant['slug'] ?? ''),
        'titre'      => txt($in['titre'] ?? '', 120) ?: 'Sans titre',
        'type'       => ($in['type'] ?? '') === 'video' ? 'video' : 'photo',
        'categorie'  => txt($in['categorie'] ?? '', 60) ?: 'Divers',
        'annee'      => txt($in['annee'] ?? '', 10),
        'client'     => txt($in['client'] ?? '', 120),
        'lieu'       => txt($in['lieu'] ?? '', 120),
        'livrables'  => txt($in['livrables'] ?? '', 120),
        'une'        => !empty($in['une']),
        'publie'     => !empty($in['publie']),
        'couverture' => photo_ok($in['couverture'] ?? '', $bib) ?: ($bib[0] ?? ''),
        'resume'     => txt($in['resume'] ?? '', 300),
        'blocs'      => [],
    ];
    foreach (is_array($in['blocs'] ?? null) ? $in['blocs'] : [] as $b) {
        $t = $b['type'] ?? '';
        if (!in_array($t, TYPES_BLOCS, true)) continue;
        $photos = fn($l) => array_values(array_filter(array_map(fn($x) => photo_ok($x, $bib), is_array($l) ? $l : [])));
        $n = match ($t) {
            'texte'    => ['type' => $t, 'titre' => txt($b['titre'] ?? '', 120), 'texte' => txt($b['texte'] ?? '', 5000)],
            'photo'    => ['type' => $t, 'src' => photo_ok($b['src'] ?? '', $bib), 'taille' => in_array($b['taille'] ?? '', ['plein', 'large', 'moyen'], true) ? $b['taille'] : 'large', 'legende' => txt($b['legende'] ?? '', 200)],
            'duo'      => ['type' => $t, 'src' => array_slice($photos($b['src'] ?? []), 0, 2), 'legende' => txt($b['legende'] ?? '', 200)],
            'grille'   => ['type' => $t, 'src' => $photos($b['src'] ?? [])],
            'chiffres' => ['type' => $t, 'items' => array_values(array_filter(array_map(fn($r) => [txt($r[0] ?? '', 20), txt($r[1] ?? '', 80)], is_array($b['items'] ?? null) ? array_slice($b['items'], 0, 6) : []), fn($r) => $r[0] !== ''))],
            'citation' => ['type' => $t, 'texte' => txt($b['texte'] ?? '', 600), 'auteur' => txt($b['auteur'] ?? '', 120)],
            'video'    => ['type' => $t, 'url' => url_ok($b['url'] ?? ''), 'poster' => photo_ok($b['poster'] ?? '', $bib), 'legende' => txt($b['legende'] ?? '', 200)],
            'reels'    => ['type' => $t, 'items' => array_values(array_map(fn($r) => ['url' => url_ok($r['url'] ?? ''), 'poster' => photo_ok($r['poster'] ?? '', $bib)], is_array($b['items'] ?? null) ? array_slice($b['items'], 0, 6) : []))],
        };
        // on n'enregistre pas un bloc vide
        $vide = match ($t) {
            'texte' => $n['texte'] === '' && $n['titre'] === '', 'photo' => $n['src'] === '', 'duo' => count($n['src']) < 2,
            'grille' => !$n['src'], 'chiffres' => !$n['items'], 'citation' => $n['texte'] === '', 'video' => $n['url'] === '' && $n['poster'] === '',
            'reels' => !$n['items'],
        };
        if (!$vide) $p['blocs'][] = $n;
    }
    return $p;
}

/* ---------- Photos ---------- */
/** Photos d'un dossier projet : ['dossier/001', ...] (sans les tailles -m / -s) */
function bibliotheque(string $dossier): array {
    if ($dossier === '' || !slug_valide($dossier)) return [];
    $out = [];
    foreach (glob(IMG_DIR . '/' . $dossier . '/*.webp') ?: [] as $f) {
        $n = basename($f, '.webp');
        if (preg_match('/^\d{3,}$/', $n)) $out[] = $dossier . '/' . $n;
    }
    sort($out, SORT_NATURAL);
    return $out;
}
/** Infos pour l'éditeur : id, vignette, orientation */
function bibliotheque_infos(string $dossier): array {
    return array_map(function ($id) {
        $s = IMG_DIR . '/' . $id . '-s.webp';
        [$w, $h] = is_file($s) ? (getimagesize($s) ?: [1, 1]) : [1, 1];
        return ['id' => $id, 'thumb' => 'assets/img/' . $id . '-s.webp', 'w' => $w, 'h' => $h];
    }, bibliotheque($dossier));
}

/** Convertit une photo envoyée en WebP 3 tailles. Renvoie l'identifiant ou un message d'erreur. */
function importer_photo(string $tmp, string $dossier): array {
    if (!slug_valide($dossier)) return ['err' => 'Dossier invalide.'];
    $info = @getimagesize($tmp);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return ['err' => 'Format non pris en charge (JPG, PNG ou WebP).'];
    if ($info[0] * $info[1] > 60_000_000) return ['err' => 'Photo trop grande (plus de 60 mégapixels).'];
    @ini_set('memory_limit', '768M');
    $src = @imagecreatefromstring((string) file_get_contents($tmp));
    if (!$src) return ['err' => 'Impossible de lire cette photo.'];
    // photos de téléphone ou d'appareil : on applique la rotation EXIF
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($tmp)['Orientation'] ?? 1);
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot) { $r = imagerotate($src, $rot, 0); if ($r) { imagedestroy($src); $src = $r; } }
    }
    $dir = IMG_DIR . '/' . $dossier;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return ['err' => 'Impossible de créer le dossier photo.'];
    // numéro suivant : 001, 002…
    $max = 0;
    foreach (bibliotheque($dossier) as $id) $max = max($max, (int) basename($id));
    $num = sprintf('%03d', $max + 1);
    $w = imagesx($src); $h = imagesy($src);
    foreach (TAILLES as $suf => [$lim, $q]) {
        // largeur max pour un paysage, hauteur max (x 1,25) pour un portrait
        $k = min($lim / $w, $lim * 1.25 / $h, 1);
        $nw = max(1, (int) round($w * $k)); $nh = max(1, (int) round($h * $k));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagewebp($dst, $dir . '/' . $num . $suf . '.webp', $q);
        imagedestroy($dst);
    }
    imagedestroy($src);
    return ['id' => $dossier . '/' . $num];
}
function supprimer_photo(string $id): bool {
    if (!preg_match('~^([a-z0-9-]+)/(\d{3,})$~', $id)) return false;
    foreach (array_keys(TAILLES) as $suf) @unlink(IMG_DIR . '/' . $id . $suf . '.webp');
    return true;
}
