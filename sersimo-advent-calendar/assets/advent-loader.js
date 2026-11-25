/* Sersimo Advent — loader (singurul cod care stă permanent în pagină).
   Pune butonul flotant și încarcă calendarul (advent.css + advent.js) abia la click,
   la #calendar-advent în URL sau la deschiderea automată (o dată pe zi). */
(function (w, d) {
  'use strict';
  var C = w.SSADV_CFG = w.SSADV_CFG || {};
  var N = 24, Y = C.year || 2026;

  // Data + ora în România, indiferent de fusul telefonului.
  C.now = function () {
    var p = {};
    new Intl.DateTimeFormat('en-GB', {
      timeZone: C.tz || 'Europe/Bucharest', year: 'numeric', month: 'numeric', day: 'numeric',
      hour: 'numeric', minute: 'numeric', second: 'numeric', hourCycle: 'h23'
    }).formatToParts(new Date()).forEach(function (x) { p[x.type] = +x.value; });
    return p;
  };
  // 0 = înainte de 1 decembrie, 1..24 = ziua, 25 = s-a terminat.
  C.getDay = function () {
    if (C.debugDay != null) return C.debugDay;
    var p = C.now();
    if (p.year < Y || (p.year === Y && p.month < 12)) return 0;
    if (p.year > Y || p.day > N) return 25;
    return p.day;
  };
  C.ls = function (k, v) {
    try {
      if (v === undefined) { var s = localStorage.getItem(k); return s ? JSON.parse(s) : null; }
      localStorage.setItem(k, JSON.stringify(v));
    } catch (e) { return null; }
  };

  var day = C.getDay();
  if (day === 25 || (day === 0 && !C.teaser)) return; // nimic de arătat

  var state = 0, queue = [];
  function load(cb) {
    if (cb) queue.push(cb);
    if (state === 2) return flush();
    if (state) return;
    state = 1;
    var left = 2, v = C.v ? '?v=' + C.v : '';
    function done() { if (--left === 0) { state = 2; flush(); } }
    var l = d.createElement('link');
    l.rel = 'stylesheet'; l.href = C.base + 'advent.css' + v; l.onload = l.onerror = done;
    var s = d.createElement('script');
    s.src = C.base + 'advent.js' + v; s.async = true; s.onload = s.onerror = done;
    d.head.appendChild(l); d.head.appendChild(s);
  }
  function flush() { var q = queue; queue = []; q.forEach(function (f) { f(); }); }
  function open(src) { load(function () { w.SSADV && w.SSADV.open(src); }); }
  C.open = open;

  // Buton flotant — stilul e aici ca să nu depindă de CSS-ul încărcat târziu.
  var css = d.createElement('style');
  css.textContent =
    '.ssadv-fab{position:fixed;left:16px;bottom:16px;z-index:99990;display:flex;align-items:center;gap:8px;' +
    'border:0;border-radius:999px;background:#c31c72;color:#fff;padding:10px 18px 10px 12px;cursor:pointer;' +
    'font:600 15px/1 Oswald,system-ui,sans-serif;letter-spacing:.04em;text-transform:uppercase;' +
    'box-shadow:0 6px 18px rgba(0,0,0,.25);-webkit-tap-highlight-color:transparent}' +
    '.ssadv-fab.is-right{left:auto;right:16px}.ssadv-fab:hover{background:#a3155f}' +
    '.ssadv-fab i{font-style:normal;font-size:22px;display:inline-block}' +
    '.ssadv-fab[data-new] i{animation:ssadv-wig 4s ease-in-out infinite}' +
    '.ssadv-fab[data-new]::after{content:"";position:absolute;top:5px;left:33px;width:10px;height:10px;' +
    'border-radius:50%;background:#ffd98a;box-shadow:0 0 0 2px #c31c72}' +
    '@keyframes ssadv-wig{0%,88%,100%{transform:none}92%{transform:rotate(-12deg)}96%{transform:rotate(12deg)}}' +
    '@media(max-width:600px){.ssadv-fab span{display:none}.ssadv-fab{padding:12px}}' +
    '@media(prefers-reduced-motion:reduce){.ssadv-fab i{animation:none!important}}';
  d.head.appendChild(css);

  var opened = C.ls('ssadv_open') || [];
  var b = d.createElement('button');
  b.type = 'button';
  b.className = 'ssadv-fab' + (C.side === 'right' ? ' is-right' : '');
  b.setAttribute('aria-label', 'Deschide Advent Calendar');
  b.innerHTML = '<i aria-hidden="true">🎄</i><span>Advent Calendar</span>';
  if (day >= 1 && day <= N && opened.indexOf(day) < 0) b.setAttribute('data-new', '');
  b.onclick = function () { open('buton'); };
  b.onpointerenter = function () { load(); }; // preîncărcare la hover
  C.fab = b;

  function start() {
    d.body.appendChild(b);
    if (location.hash === '#calendar-advent') open('link');
    w.addEventListener('hashchange', function () { if (location.hash === '#calendar-advent') open('link'); });

    // Deschidere automată: o dată pe zi, doar dacă ușa de azi nu a fost deschisă,
    // după ce vizitatorul a închis bannerul de cookies.
    var key = Y + '-12-' + day;
    if (C.auto && day >= 1 && day <= N && opened.indexOf(day) < 0 && C.ls('ssadv_auto') !== key) {
      var tries = 0;
      setTimeout(function wait() {
        var bar = d.querySelector(C.cookieBar || '#cookie-law-info-bar');
        var busy = bar && bar.offsetParent !== null;
        if (busy || d.querySelector('dialog[open]')) { if (++tries < 30) setTimeout(wait, 2000); return; }
        C.ls('ssadv_auto', key);
        open('auto');
      }, C.auto);
    }
  }
  if (d.body) start(); else d.addEventListener('DOMContentLoaded', start);
})(window, document);
