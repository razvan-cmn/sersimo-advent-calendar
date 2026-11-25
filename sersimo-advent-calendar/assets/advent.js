/* Sersimo Advent — calendarul propriu-zis. Se încarcă doar când e nevoie (vezi advent-loader.js). */
(function (w, d) {
  'use strict';
  var C = w.SSADV_CFG, N = 24, Y = C.year || 2026;
  var dlg, box, grid, msg, view, days = null, serverDay = null, T, timer, msgTimer;
  var opened = C.ls('ssadv_open') || [];
  var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function track(ev, p) {
    try { (w.dataLayer = w.dataLayer || []).push(Object.assign({ event: 'advent_' + ev, advent_zi: T }, p || {})); } catch (e) {}
  }
  function el(tag, cls, html) {
    var e = d.createElement(tag);
    if (cls) e.className = cls;
    if (html != null) e.innerHTML = html;
    return e;
  }

  function loadDays(cb) {
    if (days) return cb();
    if (C.days) { days = C.days; return cb(); }
    var h = { Accept: 'application/json' };
    if (C.nonce) h['X-WP-Nonce'] = C.nonce;
    fetch(C.data, { credentials: 'same-origin', headers: h })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        // Răspunsul pluginului: { azi: N, zile: {...} }. Ziua serverului are prioritate față de ceasul telefonului.
        if (j && j.zile) { days = j.zile; if (typeof j.azi === 'number') serverDay = j.azi; }
        else days = j || {};
        cb();
      })
      .catch(function () { days = {}; cb(); });
  }

  function build() {
    dlg = el('dialog', 'ssadv-dlg');
    dlg.setAttribute('aria-label', 'Advent Calendar Sersimo');
    box = el('div', 'ssadv-box');
    var lights = el('div', 'ssadv-lights');
    var cols = ['#ff4d6d', '#ffd166', '#06d6a0', '#4cc9f0', '#f72585'];
    for (var i = 0; i < 15; i++) {
      var s = el('span');
      s.style.background = cols[i % cols.length];
      s.style.color = cols[i % cols.length];
      s.style.top = (Math.sin(Math.PI * i / 14) * 9).toFixed(1) + 'px';
      s.style.animationDelay = (i % 5) * 0.37 + 's';
      lights.appendChild(s);
    }
    var x = el('button', 'ssadv-x', '&times;');
    x.type = 'button';
    x.setAttribute('aria-label', 'Închide');
    x.onclick = close;
    view = el('div', 'ssadv-view');
    box.appendChild(lights);
    box.appendChild(x);
    box.appendChild(view);
    dlg.appendChild(box);
    dlg.addEventListener('click', function (e) { if (e.target === dlg) close(); });
    // Curățăm la orice fel de închidere (X, fundal, Esc). Nu ne bazăm doar pe evenimentul „close”,
    // care în unele browsere nu ajunge mereu.
    dlg.addEventListener('close', cleanup);
    dlg.addEventListener('cancel', function () { setTimeout(cleanup, 0); });
    dlg.addEventListener('keydown', function (e) { if (e.key === 'Escape') setTimeout(function () { if (!dlg.open) cleanup(); }, 50); });
    d.body.appendChild(dlg);
  }

  function status(text, temp) {
    clearTimeout(msgTimer);
    msg.innerHTML = text;
    if (temp) msgTimer = setTimeout(function () { msg.innerHTML = defaultMsg(); }, 2600);
  }
  function defaultMsg() {
    if (T === 0) {
      var p = C.now(), left = Math.max(1, 31 - p.day);
      return 'Prima ușă se deschide pe <b>1 decembrie</b>' + (p.month === 11 ? ' — mai sunt ' + left + (left === 1 ? ' zi' : ' zile') : '') + '.';
    }
    if (T > N) return 'Calendarul s-a încheiat. Crăciun fericit! 🎄';
    if (opened.indexOf(T) < 0) return 'Deschide ușa de astăzi!';
    return T < N ? 'Ne vedem mâine la ușa ' + (T + 1) + '.' : 'Crăciun fericit! 🎄';
  }

  function showGrid() {
    stopTimer();
    view.className = 'ssadv-view';
    view.innerHTML =
      '<div class="ssadv-h"><small>Christmas Deluxe by Sersimo</small><h2>Advent Calendar</h2></div>' +
      '<div class="ssadv-grid" role="list"></div><p class="ssadv-msg" aria-live="polite"></p>';
    grid = view.querySelector('.ssadv-grid');
    msg = view.querySelector('.ssadv-msg');
    for (var i = 1; i <= N; i++) grid.appendChild(door(i));
    status(defaultMsg());
  }

  function door(i) {
    var info = days[i] || {}, isOpen = opened.indexOf(i) >= 0;
    var cls = i > T ? 'is-locked' : i === T ? 'is-today' : isOpen ? 'is-past' : 'is-missed';
    var b = el('button', 'ssadv-door ' + cls + (isOpen && i <= T ? ' is-open' : ''));
    b.type = 'button';
    b.setAttribute('role', 'listitem');
    b.setAttribute('aria-label', 'Ziua ' + i + (i === T ? ', azi' : i > T ? ', încuiată' : ''));
    b.innerHTML =
      '<span class="ssadv-in" aria-hidden="true">' + (i <= T ? esc(info.emoji || '🎁') : '') + '</span>' +
      '<span class="ssadv-leaf" aria-hidden="true">' + (i === T ? '<i class="ssadv-tag">azi</i>' : '') + '<b>' + i + '</b></span>';
    b.onclick = function () { pick(i, b); };
    return b;
  }

  function shake(b) {
    b.classList.remove('ssadv-shake');
    void b.offsetWidth;
    b.classList.add('ssadv-shake');
  }

  function pick(i, b) {
    if (i > T) {
      shake(b);
      status('🔒 Ușa ' + i + ' se deschide pe <b>' + i + ' decembrie</b>.', 1);
      track('usa_incuiata', { usa: i });
      return;
    }
    if (i < T) {
      status('Oferta din ziua ' + i + ' a expirat. Încearcă ușa de azi!', 1);
      track('usa_expirata', { usa: i });
      return;
    }
    if (!days[i] || !days[i].mare) {
      shake(b);
      status('Surpriza de azi apare în curând. Revino puțin mai târziu! ✨', 1);
      return;
    }
    var first = opened.indexOf(i) < 0;
    if (first) {
      opened.push(i);
      C.ls('ssadv_open', opened);
      if (C.fab) C.fab.removeAttribute('data-new');
    }
    track('usa_deschisa', { usa: i, prima_data: first });
    remember(i);
    b.classList.add('is-open');
    setTimeout(function () { showCoupon(i, first); }, first && !reduce ? 650 : 0);
  }

  // Butonul flotant ține minte cuponul de azi: după X, un click duce direct la el.
  function remember(i) {
    var c = days[i] || {}, v = { k: Y + '-12-' + i, mare: c.mare || '', nume: c.mic || (c.produs && c.produs.nume) || '' };
    C.ls('ssadv_cupon', v);
    if (C.setFab) C.setFab(v);
  }

  function product(c) {
    var p = c.produs || {}, name = c.mic || p.nume || '', link = c.link || p.link || '';
    if (!name && !p.img) return '';
    var now = c.pret_cupon || p.pret || '', old = c.pret_cupon ? p.pret : p.pret_vechi;
    var inner =
      (p.img ? '<img src="' + esc(p.img) + '" alt="" width="76" height="76" decoding="async">' : '') +
      '<span class="ssadv-pi"><span class="ssadv-pn">' + esc(name) + '</span>' +
      (now ? '<span class="ssadv-pp">' + (old && old !== now ? '<del>' + esc(old) + '</del> ' : '') + '<b>' + esc(now) + '</b>' +
        (c.pret_cupon ? ' <small>cu codul</small>' : '') + '</span>' : '') +
      '</span>';
    var cls = 'ssadv-prod' + (p.img ? '' : ' is-text');
    return link ? '<a class="' + cls + '" href="' + esc(link) + '">' + inner + '</a>' : '<div class="' + cls + '">' + inner + '</div>';
  }

  function showCoupon(i, burst) {
    var c = days[i], shape = c.forma === 'cadou' ? 'cadou' : 'glob', link = c.link || (c.produs && c.produs.link) || '';
    view.className = 'ssadv-view ssadv-cv';
    view.innerHTML =
      '<button type="button" class="ssadv-back">&larr; Calendar</button>' +
      '<p class="ssadv-day">Ziua ' + i + ' · ' + esc(c.eticheta || 'surpriza de azi') + '</p>' +
      '<div class="ssadv-hang"><div class="ssadv-shape ssadv-' + shape + '" style="--c:' + esc(c.culoare || '#c8102e') + '">' +
        '<div class="ssadv-st">' +
          (c.emoji ? '<span class="ssadv-em" aria-hidden="true">' + esc(c.emoji) + '</span>' : '') +
          '<span class="ssadv-big">' + esc(c.mare) + '</span>' +
        '</div></div></div>' +
      product(c) +
      (c.text ? '<p class="ssadv-txt">' + esc(c.text) + '</p>' : '') +
      (c.cod ? '<div class="ssadv-code"><code>' + esc(c.cod) + '</code><button type="button" class="ssadv-copy">Copiază</button></div>' : '') +
      (link ? '<a class="ssadv-cta" href="' + esc(link) + '">' + esc(c.buton || 'Vezi oferta') + ' &rarr;</a>' : '') +
      '<p class="ssadv-exp">Valabil doar azi · expiră în <b class="ssadv-cd">--:--:--</b></p>';

    view.querySelector('.ssadv-back').onclick = showGrid;
    var cp = view.querySelector('.ssadv-copy');
    if (cp) cp.onclick = function () { copy(c.cod, cp); };
    [].forEach.call(view.querySelectorAll('.ssadv-cta,.ssadv-prod[href]'), function (a) {
      a.onclick = function () { track('click_oferta', { usa: i, link: link, din: a.className.indexOf('prod') > 0 ? 'produs' : 'buton' }); };
    });

    var cd = view.querySelector('.ssadv-cd');
    function tick() {
      var p = C.now(), s = 86400 - (p.hour * 3600 + p.minute * 60 + p.second);
      cd.textContent = [s / 3600 | 0, (s % 3600) / 60 | 0, s % 60].map(function (n) { return (n < 10 ? '0' : '') + n; }).join(':');
    }
    tick();
    stopTimer();
    timer = setInterval(tick, 1000);
    if (burst && !reduce) confetti();
    var back = view.querySelector('.ssadv-back');
    if (back) back.focus({ preventScroll: true });
  }

  function copy(text, btn) {
    function ok() {
      btn.textContent = 'Copiat ✓';
      btn.classList.add('is-ok');
      track('cod_copiat', { usa: T });
      setTimeout(function () { btn.textContent = 'Copiază'; btn.classList.remove('is-ok'); }, 2200);
    }
    if (navigator.clipboard && w.isSecureContext) {
      navigator.clipboard.writeText(text).then(ok, fallback);
    } else fallback();
    function fallback() {
      var t = el('textarea');
      t.value = text;
      t.setAttribute('readonly', '');
      t.style.cssText = 'position:fixed;opacity:0';
      box.appendChild(t);
      t.select();
      try { d.execCommand('copy'); ok(); } catch (e) {}
      t.remove();
    }
  }

  function confetti() {
    var em = ['❄️', '✨', '🎄', '⭐', '❄️', '✨'];
    for (var k = 0; k < 16; k++) {
      var p = el('span', 'ssadv-p', em[k % em.length]);
      var a = (k / 16) * Math.PI * 2, r = 110 + Math.random() * 70;
      p.style.setProperty('--x', Math.cos(a) * r + 'px');
      p.style.setProperty('--y', Math.sin(a) * r - 30 + 'px');
      p.style.animationDelay = Math.random() * 0.12 + 's';
      box.appendChild(p);
      setTimeout(p.remove.bind(p), 1400);
    }
  }

  function stopTimer() { clearInterval(timer); timer = null; }
  var live = false;
  function cleanup() {
    if (!live) return;
    live = false;
    stopTimer();
    d.documentElement.classList.remove('ssadv-lock');
    // După X: butonul pulsează scurt, ca omul să vadă unde a rămas cuponul.
    if (C.fab && opened.indexOf(T) >= 0 && !reduce) {
      C.fab.classList.remove('ssadv-ping');
      void C.fab.offsetWidth;
      C.fab.classList.add('ssadv-ping');
    }
  }

  function open(src) {
    loadDays(function () {
      T = C.debugDay == null && serverDay != null ? serverDay : C.getDay();
      if (!dlg) build();
      opened = C.ls('ssadv_open') || [];
      if (src === 'cupon' && opened.indexOf(T) >= 0 && days[T] && days[T].mare) { remember(T); showCoupon(T, false); }
      else showGrid();
      if (!dlg.open) {
        d.documentElement.classList.add('ssadv-lock');
        dlg.showModal();
        live = true;
        track('deschis', { sursa: src || 'buton' });
      }
    });
  }
  function close() {
    if (dlg && dlg.open) dlg.close();
    cleanup();
  }

  w.SSADV = { open: open, close: close };
})(window, document);
