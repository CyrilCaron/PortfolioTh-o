<?php
/*
 * Transition entre pages : les photos envahissent l'écran puis s'en vont.
 * Page de départ : un clic sur un lien interne ajoute la classe "in" (les photos arrivent), puis on change de page.
 * Page d'arrivée : le voile commence couvert ("cover") puis passe en "out" (les photos repartent).
 * Les vignettes sont petites (520 px, WebP) et ne se chargent qu'une fois la page prête, sauf à l'arrivée.
 */
$txImgs = ['p1', 'p8', 'p2', 'p6', 'p7', 'p3', 'p4', 'p5', 'p9'];
mt_srand(11);
$cols = 5; $rows = 4; $n = 0;
?>
<div class="tx" id="tx" aria-hidden="true">
<?php for ($r = 0; $r < $rows; $r++): for ($c = 0; $c < $cols; $c++):
    $x = ($c + 0.5) / $cols * 100 + mt_rand(-30, 30) / 10;   // centre en vw
    $y = ($r + 0.5) / $rows * 100 + mt_rand(-30, 30) / 10;   // centre en vh
    $dx = $x - 50; $dy = $y - 50; $len = max(1, sqrt($dx * $dx + $dy * $dy));
    $fx = round($dx / $len * 90, 1); $fy = round($dy / $len * 90, 1);            // arrivée depuis l'extérieur
    $ox = round(-$dy / $len * 30 + $dx / $len * 80, 1); $oy = round($dx / $len * 30 + $dy / $len * 80, 1); // départ en tourbillon
    $d = round((1 - $len / 72) * 0.32, 3); if ($d < 0) $d = 0;                // le centre arrive en dernier
    $rot = mt_rand(-12, 12); $fr = mt_rand(-40, 40);
    $p = $txImgs[$n++ % count($txImgs)]; ?>
  <span class="tx-c" style="--x:<?= round($x, 1) ?>vw;--y:<?= round($y, 1) ?>vh;--fx:<?= $fx ?>vw;--fy:<?= $fy ?>vh;--ox:<?= $ox ?>vw;--oy:<?= $oy ?>vh;--r:<?= $rot ?>deg;--fr:<?= $fr ?>deg;--d:<?= $d ?>s;--d2:<?= round(0.32 - $d, 3) ?>s"><img data-src="<?= img($p, 's') ?>" alt="" decoding="async"></span>
<?php endfor; endfor; ?>
  <span class="tx-logo"><?php $logoSize = 64; $logoT = ''; $logoF = ''; include __DIR__ . '/logo.php'; ?></span>
</div>
<script>/* à l'arrivée, les vignettes (déjà en cache) s'affichent tout de suite */if(document.documentElement.classList.contains('tx-arrive'))document.querySelectorAll('#tx img').forEach(function(i){i.src=i.dataset.src})</script>
