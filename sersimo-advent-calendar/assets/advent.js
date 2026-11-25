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
    dlg.setAttribute('aria-label', 'Calendarul de Advent Sersimo');
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
    dlg.addEventListener('close', cleanup);
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
    return opened.indexOf(T) < 0 ? 'Ușa <b>' + T + '</b> e aprinsă — deschide-o!' : 'Ne vedem mâine la ușa ' + (T + 1 > N ? 'de Crăciun' : T + 1) + '.';
  }

  function showGrid() {
    stopTimer();
    view.className = 'ssadv-view';
    view.innerHTML =
      '<div class="ssadv-h"><small>Christmas Deluxe by Sersimo</small><h2>Calendarul de Advent</h2></div>' +
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
    b.classList.add('is-open');
    setTimeout(function () { showCoupon(i, first); }, first && !reduce ? 650 : 0);
  }

  function showCoupon(i, burst) {
    var c = days[i], shape = c.forma === 'cadou' ? 'cadou' : 'glob';
    view.className = 'ssadv-view ssadv-cv';
    view.innerHTML =
      '<button type="button" class="ssadv-back">&larr; Calendar</button>' +
      '<p class="ssadv-day">Ziua ' + i + ' · ' + esc(c.eticheta || 'surpriza de azi') + '</p>' +
      '<div class="ssadv-hang"><div class="ssadv-shape ssadv-' + shape + '" style="--c:' + esc(c.culoare || '#c31c72') + '">' +
        '<div class="ssadv-st">' +
          (c.emoji ? '<span class="ssadv-em" aria-hidden="true">' + esc(c.emoji) + '</span>' : '') +
          '<span class="ssadv-big">' + esc(c.mare) + '</span>' +
          (c.mic ? '<span class="ssadv-sub">' + esc(c.mic) + '</span>' : '') +
        '</div></div></div>' +
      (c.text ? '<p class="ssadv-txt">' + esc(c.text) + '</p>' : '') +
      (c.cod ? '<div class="ssadv-code"><code>' + esc(c.cod) + '</code><button type="button" class="ssadv-copy">Copiază</button></div>' : '') +
      (c.link ? '<a class="ssadv-cta" href="' + esc(c.link) + '">' + esc(c.buton || 'Vezi oferta') + ' &rarr;</a>' : '') +
      '<p class="ssadv-exp">Valabil doar azi · expiră în <b class="ssadv-cd">--:--:--</b></p>';

    view.querySelector('.ssadv-back').onclick = showGrid;
    var cp = view.querySelector('.ssadv-copy');
    if (cp) cp.onclick = function () { copy(c.cod, cp); };
    var a = view.querySelector('.ssadv-cta');
    if (a) a.onclick = function () { track('click_oferta', { usa: i, link: c.link }); };

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
  function cleanup() { stopTimer(); d.documentElement.classList.remove('ssadv-lock'); }

  function open(src) {
    loadDays(function () {
      T = C.debugDay == null && serverDay != null ? serverDay : C.getDay();
      if (!dlg) build();
      opened = C.ls('ssadv_open') || [];
      showGrid();
      if (!dlg.open) {
        d.documentElement.classList.add('ssadv-lock');
        dlg.showModal();
        track('deschis', { sursa: src || 'buton' });
      }
    });
  }
  function close() { if (dlg && dlg.open) dlg.close(); }

  w.SSADV = { open: open, close: close };
})(window, document);
