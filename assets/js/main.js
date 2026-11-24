// TF Production, maquette v6 (accueil, Portfolio, pages simples)
(function () {
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  var root = document.documentElement;
  function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
  function idle(fn) { (window.requestIdleCallback || function (f) { setTimeout(f, 200); })(fn); }

  /* ---------- Transition photos entre pages ----------
     S'applique à tous les liens vers une autre page du site (fichier .php ou dossier).
     Exclus : liens externes, nouvel onglet, téléchargements, ancres de la même page, et data-no-tx. */
  var tx = document.getElementById('tx');
  var arriving = root.classList.contains('tx-arrive');
  try { sessionStorage.removeItem('tf-tx'); } catch (e) {}
  function loadTx() { if (tx) tx.querySelectorAll('img[data-src]').forEach(function (i) { if (!i.getAttribute('src')) i.src = i.dataset.src; }); }

  if (arriving && tx) {
    // on arrive par la transition : les photos couvrent l'écran le temps que le fond soit prêt, puis repartent
    tx.classList.add('cover');
    var first = document.querySelector('.bg img.on');
    var ready = first && first.decode ? first.decode().catch(function () {}) : Promise.resolve();
    Promise.race([ready, wait(900)]).then(function () { return wait(200); }).then(function () {
      root.classList.remove('tx-arrive');
      tx.classList.remove('cover'); tx.classList.add('out');
      setTimeout(function () { tx.classList.remove('out'); }, 1500);
    });
  } else {
    root.classList.remove('tx-arrive');
  }

  function samePage(url) {
    var clean = function (p) { return p.replace(/index\.php$/, ''); };
    return clean(url.pathname) === clean(location.pathname) && url.search === location.search;
  }
  document.addEventListener('click', function (ev) {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    var a = ev.target.closest && ev.target.closest('a[href]');
    if (!a || (a.target && a.target !== '_self') || a.hasAttribute('download') || a.hasAttribute('data-no-tx')) return;
    var url = new URL(a.href, location.href);
    if (url.origin !== location.origin || !/(\.php|\/|\/[a-z0-9-]+)$/.test(url.pathname) || samePage(url)) return;
    if (!tx || reduce) return;
    ev.preventDefault();
    if (tx.classList.contains('in')) return;
    loadTx();
    try { sessionStorage.setItem('tf-tx', '1'); } catch (e) {}
    tx.classList.add('in');
    setTimeout(function () { location.href = url.href; }, 1250);
  });
  // retour arrière du navigateur : on retire le voile resté affiché
  addEventListener('pageshow', function (ev) { if (ev.persisted && tx) { tx.className = 'tx'; root.classList.remove('tx-arrive'); } });

  /* ---------- Fond plein écran ----------
     Seule la photo de la première section est chargée avec la page ; les autres arrivent
     une fois la page prête, ou au moment où on en a besoin. */
  var bgs = {};
  document.querySelectorAll('.bg img[data-bg]').forEach(function (img) { bgs[img.dataset.bg] = img; });
  var on = document.querySelector('.bg img.on');
  var current = on && on.dataset.bg ? on.dataset.bg : null, sectionBg = current;
  function ensure(img) {
    if (img.dataset.srcset) { img.srcset = img.dataset.srcset; img.removeAttribute('data-srcset'); }
  }
  function setBg(id) {
    var img = bgs[id];
    if (!img || id === current) return;
    current = id;
    ensure(img);
    function show() {
      if (current !== id) return;
      Object.keys(bgs).forEach(function (k) { bgs[k].classList.toggle('on', k === id); });
    }
    if (img.complete && img.naturalWidth) show(); else img.addEventListener('load', show, { once: true });
  }
  addEventListener('load', function () {
    idle(function () {
      // d'abord les fonds des sections, dans l'ordre, puis les vignettes de transition, puis le reste
      document.querySelectorAll('.panel[data-bg]').forEach(function (s) { if (bgs[s.dataset.bg]) ensure(bgs[s.dataset.bg]); });
      setTimeout(function () {
        loadTx();
        idle(function () { Object.keys(bgs).forEach(function (k) { ensure(bgs[k]); }); });
      }, 600);
    });
  });

  var panels = [].slice.call(document.querySelectorAll('.panel'));
  if (panels.length) initHome();
  if (document.querySelector('.pf-grid')) initPortfolio();
  if (document.querySelector('.pj')) initProjet();
  initCommon();

  function initHome() {
    // Section active : fond, menu, pagination, apparition du contenu
    var navLinks = document.querySelectorAll('.nav a, .pager a');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        // une section est « active » quand elle occupe près de la moitié de l'écran (même si elle est plus haute que lui)
        if (!en.isIntersecting) return;
        // le contenu apparaît dès qu'un bon morceau de la section est visible
        if (en.intersectionRatio >= 0.15 || en.intersectionRect.height > innerHeight * 0.2) en.target.classList.add('in');
        if (en.intersectionRatio < 0.45 && en.intersectionRect.height < innerHeight * 0.45) return;
        panels.forEach(function (p) { p.classList.toggle('cur', p === en.target); });
        sectionBg = en.target.dataset.bg;
        setBg(sectionBg);
        navLinks.forEach(function (a) { a.classList.toggle('on', a.getAttribute('href') === '#' + en.target.id); });
      });
    }, { threshold: [0, 0.15, 0.3, 0.45, 0.6] });
    panels.forEach(function (s) { io.observe(s); });
    panels[0].classList.add('in', 'cur');

    /* --- Défilement section par section (ordinateur) ---
       - la molette et le pavé tactile sont cumulés : un geste = une section, quelle que soit l'inertie ;
       - verrou tant que l'animation tourne ET tant que le geste continue (inertie du pavé tactile) ;
       - animation pilotée en JS (le défilement doux du navigateur ne se cumule plus avec le nôtre) ;
       - si l'on fait défiler soi-même (barre de défilement, clic molette…), on se recale sur la section la plus proche ;
       - une section plus haute que l'écran se parcourt normalement avant de passer à la suivante. */
    var desktop = matchMedia('(min-width: 1101px) and (min-height: 640px) and (hover: hover)');
    var anim = null, hist = [], lastWheel = 0, cooldown = 0, snapTimer = null;

    function indexNow() {
      var mid = innerHeight / 2, idx = 0;
      panels.forEach(function (p, i) { if (p.getBoundingClientRect().top <= mid) idx = i; });
      return idx;
    }
    function scrollToY(y) {
      if (anim) cancelAnimationFrame(anim.raf);
      var from = scrollY, dist = y - from, dur = reduce ? 0 : 850, t0 = performance.now();
      anim = { raf: 0 };
      (function frame(now) {
        var t = dur ? Math.min(1, (now - t0) / dur) : 1;
        var e = t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; // easeInOutCubic
        window.scrollTo(0, from + dist * e);
        if (t < 1) anim.raf = requestAnimationFrame(frame);
        else anim = null;
      })(t0);
    }
    function goTo(i) {
      i = Math.max(0, Math.min(panels.length - 1, i));
      cooldown = performance.now() + (reduce ? 0 : 850) + 300; // animation + petite pause
      scrollToY(panels[i].offsetTop);
    }
    function canLeave(dir) {
      var r = panels[indexNow()].getBoundingClientRect();
      if (dir > 0) return r.bottom <= innerHeight + 4;
      return r.top >= -4;
    }

    // Molette / pavé tactile : un nouveau geste déclenche une section, l'inertie qui suit est ignorée.
    // Un nouveau geste = une pause de plus de 280 ms, ou des valeurs qui remontent (l'inertie, elle, décroît).
    addEventListener('wheel', function (ev) {
      if (!desktop.matches || ev.ctrlKey) return;
      if (ev.target.closest && ev.target.closest('textarea, .lb')) return;
      var now = performance.now(), dt = now - lastWheel, d = Math.abs(ev.deltaY);
      lastWheel = now;
      hist.push(d); if (hist.length > 12) hist.shift();
      var dir = ev.deltaY > 0 ? 1 : -1;
      if (!anim && now >= cooldown && !canLeave(dir)) return; // contenu restant : défilement normal
      ev.preventDefault();
      if (anim || now < cooldown || d < 1) return;
      var recent = avg(hist.slice(-3)), older = avg(hist.slice(0, -3));
      var newGesture = dt > 280 || (hist.length >= 6 && recent > older * 1.15);
      if (!newGesture) return;
      goTo(indexNow() + dir);
    }, { passive: false });
    function avg(a) { if (!a.length) return 0; var s = 0; a.forEach(function (x) { s += x; }); return s / a.length; }

    addEventListener('keydown', function (ev) {
      if (!desktop.matches || /input|textarea|select|button/i.test(ev.target.tagName)) return;
      var dir = { ArrowDown: 1, PageDown: 1, ' ': 1, ArrowUp: -1, PageUp: -1 }[ev.key];
      if (!dir) return;
      if (anim || performance.now() < cooldown) { ev.preventDefault(); return; }
      if (!canLeave(dir)) return;
      ev.preventDefault();
      goTo(indexNow() + dir);
    });

    // Défilement manuel (barre, glisser) : recalage doux sur la section la plus proche
    addEventListener('scroll', function () {
      if (!desktop.matches || anim) return;
      clearTimeout(snapTimer);
      snapTimer = setTimeout(function () {
        if (anim || performance.now() - lastWheel < 300) return;
        var best = 0, d = Infinity;
        panels.forEach(function (p, i) { var x = Math.abs(p.offsetTop - scrollY); if (x < d) { d = x; best = i; } });
        var p = panels[best];
        if (d > 2 && d < innerHeight * 0.5 && p.offsetHeight <= innerHeight + 4) goTo(best);
      }, 220);
    }, { passive: true });

    // Liens d'ancre internes : même animation
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        var i = panels.indexOf(document.querySelector(a.getAttribute('href')));
        if (i < 0) return;
        ev.preventDefault();
        goTo(i);
        history.replaceState(null, '', a.getAttribute('href'));
      });
    });
    // arrivée depuis une autre page avec une ancre (#contact…)
    if (location.hash) {
      var t = document.querySelector(location.hash);
      if (t && panels.indexOf(t) > -1) setTimeout(function () { window.scrollTo(0, t.offsetTop); }, 0);
    }

    // Survol d'un service ou d'un projet : sa photo passe en plein écran
    if (matchMedia('(hover: hover)').matches) document.querySelectorAll('[data-hover-bg]').forEach(function (el) {
      el.addEventListener('mouseenter', function () { setBg(el.dataset.hoverBg); });
      el.addEventListener('mouseleave', function () { setBg(sectionBg); });
    });

    // Index des réalisations : aperçu synchronisé
    var ix = document.querySelectorAll('.index a'), fImgs = document.querySelectorAll('.frame-img img'), fCaps = document.querySelectorAll('.frame-cap p');
    ix.forEach(function (a) {
      a.addEventListener('mouseenter', function () {
        var n = +a.dataset.pv;
        ix.forEach(function (x, j) { x.classList.toggle('on', j === n); });
        fImgs.forEach(function (x, j) { x.classList.toggle('on', j === n); });
        fCaps.forEach(function (x, j) { x.classList.toggle('on', j === n); });
      });
    });

    // Carte « Derniers projets »
    var imgs = document.querySelectorAll('.latest-img img'), txts = document.querySelectorAll('.latest-txt'), count = document.querySelector('.latest-count'), k = 0;
    if (imgs.length > 1) setInterval(function () {
      imgs[k].classList.remove('on'); txts[k].classList.remove('on');
      k = (k + 1) % imgs.length;
      imgs[k].classList.add('on'); txts[k].classList.add('on');
      count.textContent = (k + 1) + ' / ' + imgs.length;
      var ll = document.querySelector('.latest-link'); if (ll && txts[k].dataset.href) ll.href = txts[k].dataset.href;
    }, 6000);

    // Avis
    var quotes = document.querySelectorAll('.quote'), dots = document.querySelectorAll('.quote-nav button'), q = 0, timer;
    function showQuote(n) {
      quotes[q].classList.remove('on'); dots[q].classList.remove('on');
      q = n; quotes[q].classList.add('on'); dots[q].classList.add('on');
    }
    function auto() { clearInterval(timer); timer = setInterval(function () { showQuote((q + 1) % quotes.length); }, 7000); }
    dots.forEach(function (d, n) { d.addEventListener('click', function () { showQuote(n); auto(); }); });
    if (quotes.length > 1) auto();

    // « Choisir une formule » : présélectionne la formule dans le formulaire
    document.querySelectorAll('[data-formule]').forEach(function (a) {
      a.addEventListener('click', function () {
        var r = document.querySelector('.chip input[value="' + a.dataset.formule + '"]');
        if (r) r.checked = true;
      });
    });
  }

  function initPortfolio() {
    var items = [].slice.call(document.querySelectorAll('.pf-item'));
    var tabs = document.querySelectorAll('.seg button'), seg = document.querySelector('.seg');
    var filters = document.querySelectorAll('.filters button'), empty = document.querySelector('.pf-empty');
    var hasPhoto = items.some(function (el) { return el.dataset.type === 'photo'; });
    var type = location.hash === '#videos' || !hasPhoto && items.length ? 'video' : 'photo', cat = '*';

    // apparition en cascade des cartes visibles
    function reveal(delay) {
      var n = 0;
      items.forEach(function (el) {
        el.classList.remove('in');
        if (el.classList.contains('out')) return;
        setTimeout(function () { el.classList.add('in'); }, delay + n++ * 70);
      });
    }
    function apply(first) {
      tabs.forEach(function (t) { var on = t.dataset.type === type; t.classList.toggle('on', on); t.setAttribute('aria-selected', on); });
      seg.classList.toggle('is-video', type === 'video');
      // compte par catégorie dans l'onglet actif ; une catégorie vide est masquée
      filters.forEach(function (f) {
        var c = f.dataset.filter;
        if (c === '*') return;
        var k = items.filter(function (el) { return el.dataset.type === type && el.dataset.cat === c; }).length;
        f.querySelector('small').textContent = k;
        f.parentNode.hidden = !k;
        if (!k && cat === c) cat = '*';
      });
      filters.forEach(function (f) { f.classList.toggle('on', f.dataset.filter === cat); });
      var shown = 0;
      items.forEach(function (el) {
        var ok = el.dataset.type === type && (cat === '*' || el.dataset.cat === cat);
        el.classList.toggle('out', !ok); if (ok) shown++;
      });
      empty.hidden = shown > 0;
      reveal(first ? (arriving ? 900 : 100) : 0);
    }
    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        type = t.dataset.type; cat = '*';
        history.replaceState(null, '', type === 'video' ? '#videos' : location.pathname);
        apply();
      });
    });
    filters.forEach(function (f) { f.addEventListener('click', function () { cat = f.dataset.filter; apply(); }); });
    apply(true);
  }

  function initProjet() {
    // le fond s'assombrit une fois passé le haut de page, pour la lecture
    var dim = document.querySelector('.bg-dim'), ticking = false;
    addEventListener('scroll', function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () { ticking = false; dim.style.opacity = Math.min(1, scrollY / (innerHeight * 0.7)); });
    }, { passive: true });

    // vidéos : la lecture ne se charge qu'au clic
    document.querySelectorAll('.pj-vframe[data-src]').forEach(function (f) {
      f.querySelector('.pj-play').addEventListener('click', function () {
        var v;
        if (f.dataset.kind === 'iframe') {
          v = document.createElement('iframe');
          v.allow = 'autoplay; fullscreen; picture-in-picture'; v.allowFullscreen = true;
        } else {
          v = document.createElement('video'); v.controls = true; v.autoplay = true; v.playsInline = true;
        }
        v.src = f.dataset.src;
        f.innerHTML = ''; f.appendChild(v); f.classList.add('playing');
      });
    });

    // visionneuse photo
    var links = [].slice.call(document.querySelectorAll('a[data-lb]'));
    var lb = document.getElementById('lb'); if (!lb || !links.length) return;
    var lbImg = lb.querySelector('img'), lbCap = lb.querySelector('.lb-cap'), cur = 0;
    function show(i) { cur = (i + links.length) % links.length; lbImg.src = links[cur].href; lbImg.alt = links[cur].dataset.cap || ''; lbCap.textContent = links[cur].dataset.cap || ''; }
    links.forEach(function (a, i) { a.addEventListener('click', function (ev) { ev.preventDefault(); show(i); lb.showModal(); }); });
    lb.querySelector('.lb-x').addEventListener('click', function () { lb.close(); });
    lb.querySelector('.lb-prev').addEventListener('click', function () { show(cur - 1); });
    lb.querySelector('.lb-next').addEventListener('click', function () { show(cur + 1); });
    lb.addEventListener('click', function (ev) { if (ev.target === lb) lb.close(); });
    // balayage sur téléphone : gauche/droite pour changer de photo, vers le bas pour fermer
    var tx0 = 0, ty0 = 0;
    lb.addEventListener('touchstart', function (ev) { tx0 = ev.touches[0].clientX; ty0 = ev.touches[0].clientY; }, { passive: true });
    lb.addEventListener('touchend', function (ev) {
      var dx = ev.changedTouches[0].clientX - tx0, dy = ev.changedTouches[0].clientY - ty0;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(cur + (dx < 0 ? 1 : -1));
      else if (dy > 90) lb.close();
    }, { passive: true });
    addEventListener('keydown', function (ev) {
      if (!lb.open) return;
      if (ev.key === 'ArrowLeft') show(cur - 1);
      if (ev.key === 'ArrowRight') show(cur + 1);
    });
  }

  function initCommon() {
    // Menu mobile plein écran
    var burger = document.querySelector('.burger'), menu = document.getElementById('menu-mobile');
    if (burger && menu) {
      var setMenu = function (open) {
        burger.setAttribute('aria-expanded', open);
        burger.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        root.classList.toggle('menu-open', open);
        if (open) { menu.hidden = false; requestAnimationFrame(function () { menu.classList.add('on'); }); }
        else { menu.classList.remove('on'); setTimeout(function () { if (!menu.classList.contains('on')) menu.hidden = true; }, 450); }
      };
      burger.addEventListener('click', function () { setMenu(!menu.classList.contains('on')); });
      menu.addEventListener('click', function (ev) { if (ev.target.closest('a')) setMenu(false); });
      addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && menu.classList.contains('on')) setMenu(false); });
      matchMedia('(min-width: 761px)').addEventListener('change', function (m) { if (m.matches) setMenu(false); });
    }

    // apparition au défilement des éléments .reveal
    var rv = document.querySelectorAll('.reveal');
    if (rv.length) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
      }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
      setTimeout(function () { rv.forEach(function (el) { io.observe(el); }); }, arriving ? 900 : 0);
    }
    // Copier l'e-mail
    document.querySelectorAll('[data-copy]').forEach(function (b) {
      b.addEventListener('click', function () {
        if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.copy);
        var act = b.querySelector('.d-act') || b, old = act.textContent;
        act.textContent = 'Copié'; setTimeout(function () { act.textContent = old; }, 1800);
      });
    });
  }
})();
