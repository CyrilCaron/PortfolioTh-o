<?php
/*
 * Point d'entrée unique sur Vercel (runtime communautaire vercel-php).
 * vercel.json envoie chaque page ici avec ?__page=<nom> ; on exécute la page PHP correspondante
 * à la racine du site, comme sur un hébergement classique.
 */
$pages = ['index', 'portfolio', 'projet', 'mentions-legales', 'carte-cadeau', 'univers', 'admin'];
$page = (string) ($_GET['__page'] ?? 'index');
unset($_GET['__page'], $_REQUEST['__page']);
if (!in_array($page, $pages, true)) {
    http_response_code(404);
    $page = 'index';
}
chdir(__DIR__ . '/..');
require __DIR__ . '/../' . $page . '.php';
