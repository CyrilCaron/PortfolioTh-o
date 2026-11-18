<?php
/*
 * Rendu des blocs de contenu d'un projet. Chaque bloc du JSON a un "type" :
 *   texte    {titre?, texte}                    paragraphe(s), titre à gauche
 *   photo    {src, taille: plein|large|moyen, legende?}
 *   duo      {src: [a, b], legende?}            deux photos côte à côte, décalées
 *   grille   {src: [...]}                       mosaïque
 *   chiffres {items: [[valeur, libellé], ...]}
 *   citation {texte, auteur?}
 *   video    {url, poster, legende?}            YouTube, Vimeo ou fichier .mp4 (vide = vidéo à venir)
 *   reels    {items: [{url, poster}, ...]}      vidéos verticales 9:16
 * La future page Admin proposera ces mêmes blocs, à ajouter et réordonner.
 */

// Adresse à intégrer pour une vidéo : [type, adresse]
function video_embed(string $url): array {
    if ($url === '') return ['', ''];
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|shorts/|embed/))([\w-]{11})~', $url, $m)) return ['iframe', 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0'];
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) return ['iframe', 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1'];
    return ['file', $url];
}

function photo_lb(string $id, string $titre, string $sizes, string $extra = ''): string {
    return '<a class="pj-img' . $extra . '" href="' . img($id) . '" data-lb data-cap="' . e($titre) . '">'
        . '<img src="' . img($id, 'm') . '" srcset="' . srcset($id) . '" sizes="' . $sizes . '" alt="' . e($titre) . '" loading="lazy" decoding="async"></a>';
}

function video_frame(array $v, string $titre, string $class): string {
    [$kind, $src] = video_embed($v['url'] ?? '');
    $poster = $v['poster'] ?? '';
    return '<div class="' . $class . '"' . ($kind ? ' data-kind="' . $kind . '" data-src="' . e($src) . '"' : '') . '>'
        . ($poster ? '<img src="' . img($poster, 'm') . '" srcset="' . srcset($poster) . '" sizes="(max-width:760px) 100vw, 60vw" alt="" loading="lazy" decoding="async">' : '')
        . '<button type="button" class="pj-play"' . ($kind ? '' : ' disabled') . ' aria-label="Lire la vidéo"><span></span></button>'
        . ($kind ? '' : '<span class="pj-soon">Vidéo à venir</span>')
        . '</div>';
}

function bloc(array $b, string $titre): void {
    $leg = !empty($b['legende']) ? '<figcaption>' . e($b['legende']) . '</figcaption>' : '';
    switch ($b['type'] ?? '') {
        case 'texte':
            echo '<div class="pj-text reveal">' . (!empty($b['titre']) ? '<h2>' . e($b['titre']) . '</h2>' : '<span></span>') . '<div>';
            foreach (preg_split("/\n\s*\n/", trim($b['texte'] ?? '')) as $para) echo '<p>' . nl2br(e($para)) . '</p>';
            echo '</div></div>';
            break;
        case 'photo':
            $t = in_array($b['taille'] ?? '', ['plein', 'large', 'moyen'], true) ? $b['taille'] : 'large';
            echo '<figure class="pj-photo t-' . $t . ' reveal">' . photo_lb($b['src'], $b['legende'] ?? $titre, $t === 'moyen' ? '(max-width:760px) 100vw, 760px' : '100vw') . $leg . '</figure>';
            break;
        case 'duo':
            echo '<figure class="pj-duo reveal">';
            foreach (array_slice($b['src'] ?? [], 0, 2) as $s) echo photo_lb($s, $titre, '(max-width:760px) 100vw, 50vw');
            echo $leg . '</figure>';
            break;
        case 'grille':
            $n = count($b['src'] ?? []);
            // jusqu'à 4 photos : une rangée ; au-delà : mosaïque en colonnes
            echo '<div class="pj-grid reveal ' . ($n <= 4 ? 'is-row" style="--n:' . max(1, $n) . '"' : 'is-mosaic"') . '>';
            foreach ($b['src'] ?? [] as $s) echo photo_lb($s, $titre, '(max-width:760px) 100vw, 33vw');
            echo '</div>';
            break;
        case 'chiffres':
            echo '<dl class="pj-stats reveal">';
            foreach ($b['items'] ?? [] as [$v, $l]) echo '<div><dt>' . e($v) . '</dt><dd>' . e($l) . '</dd></div>';
            echo '</dl>';
            break;
        case 'citation':
            echo '<blockquote class="pj-quote reveal"><p>' . e($b['texte'] ?? '') . '</p>' . (!empty($b['auteur']) ? '<cite>' . e($b['auteur']) . '</cite>' : '') . '</blockquote>';
            break;
        case 'video':
            echo '<figure class="pj-video reveal">' . video_frame($b, $titre, 'pj-vframe') . $leg . '</figure>';
            break;
        case 'reels':
            echo '<div class="pj-reels reveal">';
            foreach ($b['items'] ?? [] as $r) echo video_frame($r, $titre, 'pj-vframe is-reel');
            echo '</div>';
            break;
    }
}
