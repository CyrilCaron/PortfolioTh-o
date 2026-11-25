<?php
/*
 * Pour tester en local sans Apache : php -S localhost:8000 router.php
 * Gère les adresses courtes /mariage, /video… comme le .htaccess et vercel.json.
 */
$chemin = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('~^/(photo|video|reseaux-sociaux|entreprise|mariage|portrait)/?$~', $chemin, $m)) {
    $_GET['u'] = $m[1];
    require __DIR__ . '/univers.php';
    return true;
}
return false; // le reste (pages .php, images…) est servi normalement
