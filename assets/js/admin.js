// Admin TF Production : liste des projets et éditeur de mise en page
(function () {
  var CSRF = document.body.dataset.csrf;
  function post(action, data, file) {
    var fd = new FormData();
    fd.append('csrf', CSRF);
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    if (file) fd.append('photo', file);
    return fetch('admin.php?a=' + action, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return { err: 'Réponse inattendue du serveur (' + r.status + ').' }; }); })
      .then(function (j) { if (j.err) throw new Error(j.err); return j; });
  }
  function el(tag, attrs, kids) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') n.textContent = attrs[k];
      else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), attrs[k]);
      else n.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) n.appendChild(c); });
    return n;
  }
  // glisser-déposer pour réordonner une liste ; onEnd est appelé après un déplacement
  function sortable(list, itemSel, handleSel, onEnd) {
    var drag = null;
    list.addEventListener('mousedown', function (ev) {
      var it = ev.target.closest(itemSel);
      if (it) it.draggable = !handleSel || !!ev.target.closest(handleSel);
    });
    list.addEventListener('dragstart', function (ev) {
      drag = ev.target.closest(itemSel); if (!drag) return;
      ev.dataTransfer.effectAllowed = 'move'; ev.dataTransfer.setData('text/plain', '');
      setTimeout(function () { drag.classList.add('dragging'); }, 0);
    });
    list.addEventListener('dragover', function (ev) {
      if (!drag) return;
      ev.preventDefault();
      var over = ev.target.closest(itemSel);
      if (!over || over === drag || over.parentNode !== list) return;
      var r = over.getBoundingClientRect();
      list.insertBefore(drag, (ev.clientY - r.top) > r.height / 2 ? over.nextSibling : over);
    });
    list.addEventListener('dragend', function () {
      if (!drag) return;
      drag.classList.remove('dragging'); drag = null; onEnd();
    });
  }

  /* ---------- Déconnexion ---------- */
  document.querySelectorAll('[data-deconnexion]').forEach(function (b) {
    b.addEventListener('click', function () { post('deconnexion').then(function () { location.href = 'admin.php'; }); });
  });

  /* ---------- Liste des projets ---------- */
  var liste = document.querySelector('[data-liste]');
  if (liste) {
    sortable(liste, '.tfa-item', null, function () {
      post('ordre', { ordre: JSON.stringify([].map.call(liste.children, function (li) { return li.dataset.slug; })) })
        .catch(function (e) { alert(e.message); });
    });
    liste.addEventListener('click', function (ev) {
      var li = ev.target.closest('.tfa-item'); if (!li) return;
      var t = ev.target.closest('[data-basculer]');
      if (t) {
        post('basculer', { slug: li.dataset.slug, champ: t.dataset.basculer }).then(function (j) {
          t.classList.toggle('on', j.valeur);
          if (t.dataset.basculer === 'publie') t.textContent = j.valeur ? 'Publié' : 'Brouillon';
        }).catch(function (e) { alert(e.message); });
      }
      if (ev.target.closest('[data-supprimer]')) {
        var nom = li.querySelector('strong').textContent;
        if (!confirm('Supprimer le projet « ' + nom + ' » ?\nIl sera retiré du site (le fichier part dans contenu/corbeille, les photos restent sur le serveur).')) return;
        post('supprimer', { slug: li.dataset.slug }).then(function () { li.remove(); }).catch(function (e) { alert(e.message); });
      }
    });
    var creer = document.querySelector('[data-creer]');
    creer.addEventListener('submit', function (ev) {
      ev.preventDefault();
      post('creer', { titre: creer.titre.value, type: creer.type.value })
        .then(function (j) { location.href = 'admin.php?a=editer&p=' + encodeURIComponent(j.slug); })
        .catch(function (e) { alert(e.message); });
    });
  }

  /* ---------- Éditeur ---------- */
  var donnees = document.getElementById('donnees');
  if (!donnees) return;
  var D = JSON.parse(donnees.textContent), P = D.projet, BIB = D.bib;
  P.blocs = P.blocs || [];
  var status = document.querySelector('[data-status]'), dirty = false;
  function changed() { dirty = true; status.textContent = 'Modifications non enregistrées'; status.className = 'tfa-status warn'; }
  function info(id) { for (var i = 0; i < BIB.length; i++) if (BIB[i].id === id) return BIB[i]; return null; }
  function thumb(id) { var i = info(id); return i ? i.thumb : 'assets/img/' + id + '-s.webp'; }

  // Champs d'informations
  document.querySelectorAll('[data-f]').forEach(function (inp) {
    var k = inp.dataset.f;
    if (inp.type === 'checkbox') inp.checked = !!P[k]; else inp.value = P[k] || '';
    inp.addEventListener('input', function () { P[k] = inp.type === 'checkbox' ? inp.checked : inp.value; changed(); });
    inp.addEventListener('change', function () { P[k] = inp.type === 'checkbox' ? inp.checked : inp.value; changed(); });
  });

  /* --- Bibliothèque de photos --- */
  var lib = document.querySelector('[data-lib]'), nb = document.querySelector('[data-nb]');
  function renderLib() {
    lib.innerHTML = '';
    nb.textContent = BIB.length ? '(' + BIB.length + ')' : '';
    if (!BIB.length) lib.appendChild(el('p', { class: 'tfa-hint', text: 'Aucune photo. Glissez-en ici ou cliquez sur « Ajouter des photos ».' }));
    BIB.forEach(function (ph) {
      var cover = P.couverture === ph.id;
      lib.appendChild(el('figure', { class: 'tfa-ph' + (cover ? ' cover' : '') }, [
        el('img', { src: ph.thumb, alt: '', loading: 'lazy' }),
        el('button', { type: 'button', class: 'tfa-star', title: 'Choisir comme couverture', text: '★', onclick: function () { P.couverture = ph.id; changed(); renderLib(); } }),
        el('button', { type: 'button', class: 'tfa-del', title: 'Supprimer la photo', text: '✕', onclick: function () { supprimerPhoto(ph.id); } }),
        cover ? el('span', { class: 'tfa-badge', text: 'Couverture' }) : null
      ]));
    });
  }
  function supprimerPhoto(id) {
    var used = JSON.stringify(P.blocs).indexOf('"' + id + '"') > -1;
    if (!confirm('Supprimer cette photo du serveur ?' + (used ? '\nElle est utilisée dans la mise en page : elle en sera retirée.' : ''))) return;
    post('supprimer-photo', { slug: P.slug, id: id }).then(function () {
      BIB = BIB.filter(function (x) { return x.id !== id; });
      if (P.couverture === id) P.couverture = BIB[0] ? BIB[0].id : '';
      // retirer la photo des blocs
      P.blocs.forEach(function (b) {
        if (b.src === id) b.src = '';
        if (Array.isArray(b.src)) b.src = b.src.filter(function (x) { return x !== id; });
        if (b.poster === id) b.poster = '';
        (b.items || []).forEach(function (r) { if (r && r.poster === id) r.poster = ''; });
      });
      renderLib(); renderBlocs();
      return enregistrer(true);
    }).catch(function (e) { alert(e.message); });
  }

  // Envoi des photos, une par une, avec barre de progression
  var progress = document.querySelector('[data-progress]'), bar = progress.querySelector('span');
  function envoyer(files) {
    files = [].filter.call(files, function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type); });
    if (!files.length) return;
    var i = 0, errs = [];
    progress.hidden = false;
    (function next() {
      bar.style.width = (i / files.length * 100) + '%';
      status.textContent = 'Envoi des photos : ' + i + ' / ' + files.length; status.className = 'tfa-status';
      if (i >= files.length) {
        progress.hidden = true;
        if (!P.couverture && BIB[0]) P.couverture = BIB[0].id;
        renderLib(); enregistrer(true);
        if (errs.length) alert(errs.join('\n'));
        return;
      }
      var f = files[i++];
      post('photo', { slug: P.slug }, f).then(function (j) { BIB.push(j.photo); renderLib(); })
        .catch(function (e) { errs.push(f.name + ' : ' + e.message); })
        .then(next);
    })();
  }
  document.querySelector('[data-upload]').addEventListener('change', function (ev) { envoyer(ev.target.files); ev.target.value = ''; });
  var drop = document.querySelector('[data-drop]');
  ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (ev) { if (ev.dataTransfer.types.indexOf('Files') > -1) { ev.preventDefault(); drop.classList.add('over'); } }); });
  ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove('over'); }); });
  drop.addEventListener('drop', function (ev) { if (ev.dataTransfer.files.length) { ev.preventDefault(); envoyer(ev.dataTransfer.files); } });

  /* --- Sélecteur de photos --- */
  var picker = document.querySelector('[data-picker]'), pLib = document.querySelector('[data-picker-lib]');
  var pTitle = document.querySelector('[data-picker-title]'), pHint = document.querySelector('[data-picker-hint]'), pick = [], pickMax = 1, pickDone = null;
  function choisir(actuel, max, cb) {
    if (!BIB.length) { alert('Ajoutez d\'abord des photos au projet.'); return; }
    pick = actuel.filter(Boolean).slice(); pickMax = max; pickDone = cb;
    pTitle.textContent = max === 1 ? 'Choisir une photo' : max === 2 ? 'Choisir 2 photos' : 'Choisir les photos';
    renderPick(); picker.showModal();
  }
  function renderPick() {
    pLib.innerHTML = '';
    pHint.textContent = pickMax === 1 ? '' : pick.length + ' sélectionnée(s), dans l\'ordre du clic';
    BIB.forEach(function (ph) {
      var n = pick.indexOf(ph.id);
      pLib.appendChild(el('button', { type: 'button', class: 'tfa-ph' + (n > -1 ? ' sel' : ''), onclick: function () {
        if (pickMax === 1) { pick = [ph.id]; picker.close('ok'); return; }
        if (n > -1) pick.splice(n, 1); else if (pick.length < pickMax) pick.push(ph.id);
        renderPick();
      } }, [el('img', { src: ph.thumb, alt: '', loading: 'lazy' }), n > -1 && pickMax > 1 ? el('span', { class: 'tfa-num', text: String(n + 1) }) : null]));
    });
  }
  picker.addEventListener('close', function () {
    if (picker.returnValue === 'ok' && pickDone) { pickDone(pick); changed(); renderBlocs(); }
    pickDone = null;
  });

  /* --- Blocs --- */
  var NOMS = { texte: 'Texte', photo: 'Photo', duo: 'Duo de photos', grille: 'Grille de photos', chiffres: 'Chiffres clés', citation: 'Citation', video: 'Vidéo', reels: 'Reels verticaux' };
  var NEUF = {
    texte: function () { return { type: 'texte', titre: '', texte: '' }; },
    photo: function () { return { type: 'photo', src: '', taille: 'large', legende: '' }; },
    duo: function () { return { type: 'duo', src: [], legende: '' }; },
    grille: function () { return { type: 'grille', src: [] }; },
    chiffres: function () { return { type: 'chiffres', items: [['', ''], ['', ''], ['', '']] }; },
    citation: function () { return { type: 'citation', texte: '', auteur: '' }; },
    video: function () { return { type: 'video', url: '', poster: '', legende: '' }; },
    reels: function () { return { type: 'reels', items: [{ url: '', poster: '' }, { url: '', poster: '' }, { url: '', poster: '' }] }; }
  };
  var blocsEl = document.querySelector('[data-blocs]');

  function champ(label, val, set, opts) {
    opts = opts || {};
    var inp = opts.rows ? el('textarea', { rows: opts.rows }) : el('input', { type: 'text' });
    if (opts.ph) inp.placeholder = opts.ph;
    inp.value = val || '';
    inp.addEventListener('input', function () { set(inp.value); changed(); });
    return el('label', { class: opts.cls || '' }, [document.createTextNode(label), inp]);
  }
  function slot(id, onPick, label) {
    return el('button', { type: 'button', class: 'tfa-slot' + (id ? ' full' : ''), title: label || 'Choisir une photo', onclick: onPick },
      [id ? el('img', { src: thumb(id), alt: '' }) : el('span', { text: label || '+ Photo' })]);
  }
  function editeur(b) {
    var box = el('div', { class: 'tfa-bloc-body' });
    switch (b.type) {
      case 'texte':
        box.appendChild(champ('Titre (facultatif)', b.titre, function (v) { b.titre = v; }));
        box.appendChild(champ('Texte (ligne vide = nouveau paragraphe)', b.texte, function (v) { b.texte = v; }, { rows: 5, cls: 'w3' }));
        break;
      case 'photo':
        box.appendChild(slot(b.src, function () { choisir([b.src], 1, function (s) { b.src = s[0] || ''; }); }));
        var sel = el('select', { onchange: function () { b.taille = sel.value; changed(); } });
        [['plein', 'Plein écran (bord à bord)'], ['large', 'Large'], ['moyen', 'Moyen, centré']].forEach(function (o) { var op = el('option', { value: o[0], text: o[1] }); if (b.taille === o[0]) op.selected = true; sel.appendChild(op); });
        box.appendChild(el('div', { class: 'tfa-col' }, [el('label', {}, [document.createTextNode('Taille'), sel]), champ('Légende (facultatif)', b.legende, function (v) { b.legende = v; })]));
        break;
      case 'duo':
        var dw = el('div', { class: 'tfa-slots' });
        [0, 1].forEach(function (k) { dw.appendChild(slot(b.src[k], function () { choisir(b.src, 2, function (s) { b.src = s; }); })); });
        box.appendChild(dw);
        box.appendChild(champ('Légende (facultatif)', b.legende, function (v) { b.legende = v; }));
        break;
      case 'grille':
        var gw = el('div', { class: 'tfa-slots many' });
        b.src.forEach(function (id) { gw.appendChild(slot(id, function () { choisir(b.src, 40, function (s) { b.src = s; }); })); });
        gw.appendChild(slot('', function () { choisir(b.src, 40, function (s) { b.src = s; }); }, '+ Photos'));
        box.appendChild(gw);
        box.appendChild(el('p', { class: 'tfa-hint', text: 'Jusqu\'à 4 photos : une rangée. Au-delà : une mosaïque.' }));
        break;
      case 'chiffres':
        var cw = el('div', { class: 'tfa-rows' });
        b.items.forEach(function (r, k) {
          cw.appendChild(el('div', { class: 'tfa-row' }, [
            champ('Valeur', r[0], function (v) { r[0] = v; }, { ph: '120' }),
            champ('Libellé', r[1], function (v) { r[1] = v; }, { ph: 'photos livrées' }),
            el('button', { type: 'button', class: 'tfa-mini', text: '✕', title: 'Retirer', onclick: function () { b.items.splice(k, 1); changed(); renderBlocs(); } })
          ]));
        });
        if (b.items.length < 6) cw.appendChild(el('button', { type: 'button', class: 'tfa-mini wide', text: '+ Chiffre', onclick: function () { b.items.push(['', '']); changed(); renderBlocs(); } }));
        box.appendChild(cw);
        break;
      case 'citation':
        box.appendChild(champ('Citation', b.texte, function (v) { b.texte = v; }, { rows: 3, cls: 'w3' }));
        box.appendChild(champ('Auteur (facultatif)', b.auteur, function (v) { b.auteur = v; }));
        break;
      case 'video':
        box.appendChild(slot(b.poster, function () { choisir([b.poster], 1, function (s) { b.poster = s[0] || ''; }); }, '+ Image d\'aperçu'));
        box.appendChild(el('div', { class: 'tfa-col' }, [
          champ('Lien YouTube ou Vimeo', b.url, function (v) { b.url = v; }, { ph: 'https://youtu.be/…' }),
          champ('Légende (facultatif)', b.legende, function (v) { b.legende = v; })
        ]));
        break;
      case 'reels':
        var rw = el('div', { class: 'tfa-reels' });
        b.items.forEach(function (r, k) {
          rw.appendChild(el('div', { class: 'tfa-reel' }, [
            slot(r.poster, function () { choisir([r.poster], 1, function (s) { r.poster = s[0] || ''; }); }, '+ Aperçu'),
            champ('Lien', r.url, function (v) { r.url = v; }, { ph: 'https://youtube.com/shorts/…' }),
            el('button', { type: 'button', class: 'tfa-mini', text: 'Retirer', onclick: function () { b.items.splice(k, 1); changed(); renderBlocs(); } })
          ]));
        });
        if (b.items.length < 6) rw.appendChild(el('button', { type: 'button', class: 'tfa-mini wide', text: '+ Reel', onclick: function () { b.items.push({ url: '', poster: '' }); changed(); renderBlocs(); } }));
        box.appendChild(rw);
        break;
    }
    return box;
  }
  function renderBlocs() {
    blocsEl.innerHTML = '';
    if (!P.blocs.length) blocsEl.appendChild(el('p', { class: 'tfa-empty', text: 'Aucun bloc. Ajoutez-en avec les boutons ci-dessous, ou utilisez la mise en page automatique.' }));
    P.blocs.forEach(function (b, i) {
      var card = el('article', { class: 'tfa-bloc', 'data-i': String(i) }, [
        el('header', {}, [
          el('span', { class: 'tfa-grip', title: 'Glisser pour déplacer', text: '⋮⋮' }),
          el('strong', { text: NOMS[b.type] }),
          el('span', { class: 'tfa-sp' }),
          el('button', { type: 'button', class: 'tfa-mini', title: 'Monter', text: '↑', onclick: function () { move(i, -1); } }),
          el('button', { type: 'button', class: 'tfa-mini', title: 'Descendre', text: '↓', onclick: function () { move(i, 1); } }),
          el('button', { type: 'button', class: 'tfa-mini', title: 'Dupliquer', text: '⧉', onclick: function () { P.blocs.splice(i + 1, 0, JSON.parse(JSON.stringify(b))); changed(); renderBlocs(); } }),
          el('button', { type: 'button', class: 'tfa-mini danger', title: 'Supprimer le bloc', text: '✕', onclick: function () { if (confirm('Supprimer ce bloc ?')) { P.blocs.splice(i, 1); changed(); renderBlocs(); } } })
        ]),
        editeur(b)
      ]);
      blocsEl.appendChild(card);
    });
  }
  function move(i, d) {
    var j = i + d; if (j < 0 || j >= P.blocs.length) return;
    var t = P.blocs[i]; P.blocs[i] = P.blocs[j]; P.blocs[j] = t; changed(); renderBlocs();
  }
  sortable(blocsEl, '.tfa-bloc', '.tfa-grip', function () {
    var old = P.blocs.slice();
    P.blocs = [].map.call(blocsEl.querySelectorAll('.tfa-bloc'), function (c) { return old[+c.dataset.i]; });
    changed(); renderBlocs();
  });
  document.querySelectorAll('[data-add]').forEach(function (b) {
    b.addEventListener('click', function () {
      P.blocs.push(NEUF[b.dataset.add]()); changed(); renderBlocs();
      blocsEl.lastChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });

  // Mise en page automatique : enchaîne les photos selon leur orientation
  document.querySelector('[data-auto]').addEventListener('click', function () {
    if (!BIB.length) { alert('Ajoutez d\'abord des photos.'); return; }
    if (P.blocs.length && !confirm('Remplacer la mise en page actuelle par une mise en page automatique ?')) return;
    var photos = BIB.filter(function (x) { return x.id !== P.couverture; });
    var blocs = [{ type: 'texte', titre: 'Le projet', texte: P.resume || '' }], runs = [];
    photos.forEach(function (ph) {
      var o = ph.w >= ph.h ? 'L' : 'P', last = runs[runs.length - 1];
      if (last && last.o === o) last.ids.push(ph.id); else runs.push({ o: o, ids: [ph.id] });
    });
    var plein = true;
    runs.forEach(function (r) {
      var ids = r.ids.slice();
      while (ids.length) {
        if (r.o === 'L') {
          if (ids.length >= 3) blocs.push({ type: 'grille', src: ids.splice(0, 3) });
          else { blocs.push({ type: 'photo', src: ids.shift(), taille: plein ? 'plein' : 'large', legende: '' }); plein = !plein; }
        } else {
          var n = ids.length === 2 ? 2 : ids.length === 1 ? 1 : Math.min(4, ids.length === 5 ? 3 : ids.length);
          var part = ids.splice(0, n);
          if (n === 1) blocs.push({ type: 'photo', src: part[0], taille: 'moyen', legende: '' });
          else if (n === 2) blocs.push({ type: 'duo', src: part, legende: '' });
          else blocs.push({ type: 'grille', src: part });
        }
      }
    });
    if (!blocs[0].texte) blocs.shift();
    P.blocs = blocs; changed(); renderBlocs();
  });

  /* --- Enregistrement --- */
  var btn = document.querySelector('[data-enregistrer]');
  function enregistrer(silencieux) {
    btn.disabled = true;
    status.textContent = 'Enregistrement…'; status.className = 'tfa-status';
    return post('enregistrer', { slug: P.slug, projet: JSON.stringify(P) }).then(function () {
      dirty = false; status.textContent = 'Enregistré ✓'; status.className = 'tfa-status ok';
    }).catch(function (e) {
      status.textContent = 'Erreur : ' + e.message; status.className = 'tfa-status warn';
      if (!silencieux) alert(e.message);
    }).then(function () { btn.disabled = false; });
  }
  btn.addEventListener('click', function () { enregistrer(); });
  addEventListener('keydown', function (ev) { if ((ev.metaKey || ev.ctrlKey) && ev.key === 's') { ev.preventDefault(); enregistrer(); } });
  document.querySelector('[data-apercu]').addEventListener('click', function () { if (dirty) enregistrer(true); });
  addEventListener('beforeunload', function (ev) { if (dirty) { ev.preventDefault(); ev.returnValue = ''; } });

  renderLib(); renderBlocs();
})();
