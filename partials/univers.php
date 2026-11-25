<?php
/*
 * Déclinaisons du site par univers : une page par métier ou par public (/mariage, /video…).
 * Chaque univers a son titre Google, son texte, ses prestations, ses offres et ses projets.
 * Pour en ajouter un : copier un bloc, copier mariage.php sous le nouveau nom, et ajouter l'identifiant dans vercel.json.
 *   projets : ['type' => 'photo'|'video'] et/ou ['categories' => [...]] (catégories saisies dans l'admin)
 *   video   : (facultatif) vidéo de fond dans assets/video/<nom>.mp4 et <nom>-m.mp4
 *   offres  : [nom, prix, détails] ; 'formules' reprend les formules de l'accueil
 */
$univers = [
    'photo' => [
        'menu'    => 'Photographe',
        'titre'   => 'Photographe à Bruay-la-Buissière',
        'h1'      => 'Des photos qui <em>vendent</em> votre activité',
        'google'  => 'Photographe à Bruay-la-Buissière et 40 km autour : produits, équipe, locaux, événements. Photos retouchées, prix affichés.',
        'lead'    => 'Produits, équipe, locaux, événements : des images retouchées, prêtes pour votre site et vos réseaux. Je me déplace à Bruay-la-Buissière, Béthune, Lens, Arras et alentours.',
        'fond'    => 'p8',
        'formule' => 'Je ne sais pas encore',
        'points'  => [['Entreprise', 'Équipe, locaux, savoir-faire : montrer qui vous êtes.'], ['Produits', 'Packshots et mises en situation pour votre boutique en ligne.'], ['Événements', 'Inaugurations, salons, soirées : les moments qui comptent.'], ['Automobile', 'Voitures de course, séances privées, concessions.']],
        'offres'  => 'formules',
        'projets' => ['type' => 'photo'],
    ],
    'video' => [
        'menu'    => 'Vidéaste',
        'titre'   => 'Vidéaste à Bruay-la-Buissière',
        'h1'      => 'Des vidéos qui <em>captent</em> l\'attention',
        'google'  => 'Vidéaste à Bruay-la-Buissière : film d\'entreprise, vidéo pour particuliers, reels Instagram et TikTok. Tournage, montage et sous-titres.',
        'lead'    => 'Film d\'entreprise, souvenir de famille ou reels pour vos réseaux : je m\'occupe du tournage, du montage et des sous-titres.',
        'fond'    => 'video-lotus', // image affichée pendant le chargement de la vidéo
        'video'   => 'lotus',       // vidéo plein écran en haut de page : assets/video/lotus.mp4 (+ lotus-m.mp4 pour mobile)
        'video_titre' => 'Pack shot · Montres Lotus',
        'formule' => 'Visibilité',
        'points'  => [['Entreprise', 'Présentation, recrutement, coulisses : votre activité en 1 à 3 minutes.'], ['Particuliers', 'Anniversaire, fête, voyage : un film souvenir à garder.'], ['Réseaux sociaux', 'Reels verticaux pour Instagram, TikTok et Shorts, montés et sous-titrés.'], ['Événements', 'La captation complète, avec un résumé prêt à partager.']],
        'offres'  => [
            ['Reels', 'dès 250 €', ['3 reels verticaux', '20 photos en bonus', 'Environ 3 h sur place']],
            ['Film entreprise', 'Sur devis', ['1 à 3 minutes', 'Tournage et montage', 'Musique et sous-titres']],
            ['Film souvenir', 'Sur devis', ['Pour les particuliers', 'Version longue et version courte', 'Livré en ligne']],
        ],
        'projets' => ['type' => 'video'],
    ],
    'reseaux-sociaux' => [
        'menu'    => 'Social media',
        'titre'   => 'Gestion de réseaux sociaux à Bruay-la-Buissière',
        'h1'      => 'Vos réseaux, <em>gérés</em> chaque mois',
        'google'  => 'Community manager à Bruay-la-Buissière : création de contenu photo et vidéo, publications et suivi de vos réseaux sociaux. 100 € par mois et par réseau.',
        'lead'    => 'Je crée les photos et les vidéos, je publie et je suis les résultats. Vous vous occupez de votre métier.',
        'fond'    => 'p2',
        'formule' => 'Réseaux sociaux',
        'points'  => [['Calendrier', 'Un planning de publications validé avec vous chaque mois.'], ['Contenu', 'Photos et reels tournés chez vous, à votre image.'], ['Publication', 'Textes, hashtags, horaires : je m\'occupe de tout.'], ['Suivi', 'Un point chaque mois sur ce qui marche.']],
        'offres'  => 'abonnement',
        'projets' => ['type' => 'video'],
    ],
    'entreprise' => [
        'menu'    => 'Entreprise',
        'titre'   => 'Photo et vidéo pour entreprises à Bruay-la-Buissière',
        'h1'      => 'Votre entreprise, <em>mise en valeur</em>',
        'google'  => 'Photographe et vidéaste pour entreprises à Bruay-la-Buissière, Béthune, Lens et Arras : photos, reels, films et réseaux sociaux. Prix affichés.',
        'lead'    => 'Commerces, artisans, PME, associations : je réalise vos photos, vos vidéos et vos contenus pour les réseaux, avec des prix clairs dès le départ.',
        'fond'    => 'p8',
        'formule' => 'Visibilité',
        'points'  => [['Image de marque', 'Portraits d\'équipe, locaux, savoir-faire.'], ['Produits', 'Photos pour votre site et votre boutique en ligne.'], ['Vidéo', 'Film de présentation, recrutement, reels.'], ['Réseaux sociaux', 'Contenu et publications chaque mois.']],
        'offres'  => 'formules',
        'projets' => [],
    ],
    'mariage' => [
        'menu'    => 'Mariage',
        'titre'   => 'Photographe et vidéaste de mariage dans le Pas-de-Calais',
        'h1'      => 'Votre mariage, <em>raconté</em> en images',
        'google'  => 'Photographe et vidéaste de mariage à Bruay-la-Buissière, Béthune, Lens et Arras. Reportage photo, film de mariage, prix clairs.',
        'lead'    => 'Des préparatifs à la soirée, je photographie et je filme votre journée avec discrétion, pour que vous la reviviez longtemps.',
        'fond'    => 'p7',
        'formule' => 'Je ne sais pas encore',
        'points'  => [['Préparatifs', 'Les derniers détails, l\'émotion avant la cérémonie.'], ['Cérémonie', 'Mairie, église ou laïque : chaque moment important.'], ['Couple', 'Une séance photo à deux, à votre rythme.'], ['Soirée', 'Les discours, la première danse, la fête.']],
        'offres'  => [
            ['Cérémonie', 'Sur devis', ['Photos de la cérémonie', 'Photos de groupe', 'Galerie en ligne']],
            ['Journée', 'Sur devis', ['Des préparatifs au dîner', 'Séance couple', 'Galerie en ligne']],
            ['Photo + film', 'Sur devis', ['Reportage photo complet', 'Film de 5 à 10 minutes', 'Teaser pour les réseaux']],
        ],
        'projets' => ['categories' => ['Mariage']],
    ],
    'portrait' => [
        'menu'    => 'Portrait',
        'titre'   => 'Photographe portrait à Bruay-la-Buissière',
        'h1'      => 'Des portraits qui vous <em>ressemblent</em>',
        'google'  => 'Séance photo portrait à Bruay-la-Buissière : portrait professionnel, famille, couple, grossesse. En extérieur ou chez vous.',
        'lead'    => 'Portrait professionnel, en famille, en couple : une séance détendue, en extérieur ou chez vous, et des photos que vous aurez envie de montrer.',
        'fond'    => 'p6',
        'formule' => 'Je ne sais pas encore',
        'points'  => [['Professionnel', 'Pour LinkedIn, votre site ou votre équipe.'], ['Famille', 'Parents, enfants, grands-parents réunis.'], ['Couple', 'Une séance à deux, avant ou après un grand jour.'], ['Grossesse', 'Garder une trace de ce moment.']],
        'offres'  => [
            ['Portrait solo', 'Sur devis', ['30 min à 1 h', '10 photos retouchées', 'Galerie en ligne']],
            ['Famille', 'Sur devis', ['1 h en extérieur', '20 photos retouchées', 'Galerie en ligne']],
            ['Équipe', 'Sur devis', ['Portraits de toute l\'équipe', 'Fond uni ou sur place', 'Livraison sous 5 jours']],
        ],
        'projets' => ['categories' => ['Portrait']],
    ],
];
function lien_univers(string $id): string { return $id . '.php'; } // mariage.php, video.php… (petits fichiers qui chargent univers.php)
