<?php
// Contenus partagés par l'accueil et la page Portfolio. Modifier ici textes, images et prix.
declare(strict_types=1);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
// Images en WebP, 3 tailles : '' = 1800 px, 'm' = 1000 px, 's' = 520 px
function img(string $id, string $size = ''): string { return 'assets/img/' . $id . ($size ? '-' . $size : '') . '.webp'; }
function srcset(string $id): string { return img($id, 's') . ' 520w, ' . img($id, 'm') . ' 1000w, ' . img($id) . ' 1800w'; }

$site = [
    'nom'       => 'TF Production',
    'roles'     => ['Photographe', 'Vidéaste', 'Social media'],
    'accroche'  => 'Photos, vidéos et réseaux sociaux pour les entreprises de Bruay-la-Buissière et 40 km autour.',
    'email'     => 'contact@tf-production.fr',
    'instagram' => 'https://www.instagram.com/',
    'malt'      => 'https://www.malt.fr/',
    'agenda'    => '#', // lien Calendly ou Google Agenda pour « Réserver un appel »
];

// Carte cadeau : coller ici le lien Stripe (Payment Link « le client choisit le montant »).
// Tant qu'il est vide, le bouton mène au formulaire de contact.
$cadeau = [
    'stripe'   => '',
    'min'      => '30 €',
    'validite' => '1 an',
    'montants' => [['50 €', 'Un coup de pouce pour une séance'], ['150 €', 'La formule Essentiel'], ['250 €', 'La formule Visibilité'], ['Libre', 'Le montant de votre choix']],
];
// Période de Noël : mise en avant de la carte cadeau sur l'accueil (du 1er novembre au 24 décembre)
$md = (int) date('md');
$noel = $md >= 1101 && $md <= 1224;


// Services : chaque colonne change la photo de fond au survol [titre, texte, image]
$services = [
    ['Photo',           'Produits, équipe, locaux : des images retouchées, prêtes à publier.', 'p8'],
    ['Reels & vidéo',   'Formats verticaux pour Instagram, TikTok et Shorts, montés et sous-titrés.', 'p1'],
    ['Réseaux sociaux', 'Je gère vos comptes : calendrier, publications et suivi chaque mois.', 'p2'],
    ['Événements',      'Inaugurations, salons, spectacles : la captation complète, photo et vidéo.', 'p7'],
];

// Formules [nom, prix, détails, mise en avant]
$formules = [
    ['Essentiel',  '150 €', ['15 photos retouchées', '1 h à 1 h 30 sur place', 'Livraison sous 5 jours'], false],
    ['Visibilité', '250 €', ['20 photos retouchées', '3 reels verticaux', 'Environ 3 h sur place'], true],
    ['Signature',  '350 €', ['20 photos retouchées', '8 reels verticaux', 'Environ 4 h sur place'], false],
];
$abonnement = ['100 € / mois', 'par réseau social', '200 € de mise en place, une seule fois'];

// Projets [titre, catégorie, image, année, format, à la une sur l'accueil]
// Projets : un fichier JSON par projet dans contenu/projets/ (c'est ce que la future page Admin écrira).
// $fiches = projets complets (pour projet.php) ; $projets = version courte pour l'accueil et le portfolio :
// [titre, catégorie, couverture, année, format, à la une, slug, type photo|video]
$fiches = []; $brouillons = [];
foreach (glob(__DIR__ . '/../contenu/projets/*.json') as $f) {
    $j = json_decode((string) file_get_contents($f), true);
    if (!is_array($j) || empty($j['slug'])) continue;
    if (($j['publie'] ?? true) === false) { $brouillons[$j['slug']] = $j; continue; } // brouillon : visible seulement par l'admin
    $fiches[$j['slug']] = $j;
}
uasort($fiches, fn($x, $y) => ($x['ordre'] ?? 99) <=> ($y['ordre'] ?? 99));
$projets = [];
foreach ($fiches as $j) {
    $projets[] = [$j['titre'], $j['categorie'], $j['couverture'], $j['annee'], $j['type'] === 'video' ? 'Vidéo' : 'Photo', !empty($j['une']), $j['slug'], $j['type']];
}
// « 1 projet », « 3 projets »
function n_projets(int $n): string { return $n . ' projet' . ($n > 1 ? 's' : ''); }
function lien_projet(string $slug): string { return 'projet.php?p=' . rawurlencode($slug); }

// Carte « Derniers projets » de l'accueil : les 3 premiers projets [titre, résumé, couverture, slug]
$derniers = array_map(fn($j) => [$j['titre'], $j['resume'] ?? '', $j['couverture'], $j['slug']], array_slice(array_values($fiches), 0, 3));
$categories = array_values(array_unique(array_column($projets, 1)));

// Avis (à remplacer par les vrais avis Malt) [citation, nom, entreprise]
$avis = [
    ['Théo a compris tout de suite ce qu\'on voulait montrer. Nos publications n\'ont jamais aussi bien marché.', 'Prénom Nom', 'Gérant, commerce local'],
    ['Réactif, précis et de bon conseil. Les reels étaient prêts en deux jours.', 'Prénom Nom', 'Responsable communication'],
    ['Un vrai regard de photographe, et un tarif clair dès le départ.', 'Prénom Nom', 'Artisan'],
];

// Formulaire de devis : maquette, aucun e-mail n'est envoyé (brancher mail() ou PHPMailer ici plus tard).
$envoye = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $envoye = trim((string)($_POST['email'] ?? '')) !== '';
}

// Sections plein écran : identifiant => image de fond
$fonds = ['accueil' => 'p1', 'services' => 'p8', 'offres' => 'p2', 'realisations' => 'p4', 'avis' => 'p6', 'contact' => 'p5'];
