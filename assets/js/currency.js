/**
 * Phelyz Store - automatic NGN ⇄ USD display currency.
 *
 * - Visitor country comes from Vercel's x-vercel-ip-country header,
 *   exposed by PHP as window.PHELYZ_COUNTRY. Nigeria → NGN, everyone else → USD.
 * - Live rate from open.er-api.com (free, no API key, CORS-enabled),
 *   cached in localStorage for 6 hours.
 * - Conversion is DISPLAY-ONLY: it rewrites ₦ amounts in the page text.
 *   Orders are still charged in Naira.
 * - A floating pill (bottom-left) lets the user switch manually; the manual
 *   choice is remembered and wins over geo-detection.
 */
(function () {
  'use strict';

  var RATE_URL   = 'https://open.er-api.com/v6/latest/NGN';
  var CACHE_KEY  = 'phelyz_ngn_usd_rate';
  var PREF_KEY   = 'phelyz_currency_pref';
  var CACHE_TTL  = 6 * 60 * 60 * 1000; // 6 hours
  var NAIRA_RE   = /₦\s?([\d,]+(?:\.\d+)?)/g;

  var state = {
    currency: 'NGN',     // current display currency
    rate: null,          // USD per 1 NGN
    converted: [],       // [{node, original}] so we can restore
    observer: null,
  };

  // ── Currency preference ───────────────────────────────────────────────────
  function preferredCurrency() {
    var saved = null;
    try { saved = localStorage.getItem(PREF_KEY); } catch (e) {}
    if (saved === 'NGN' || saved === 'USD') return saved;
    var country = (window.PHELYZ_COUNTRY || '').toUpperCase();
    if (country && country !== 'NG') return 'USD';
    return 'NGN';
  }

  // ── Rate fetch with cache ─────────────────────────────────────────────────
  function getRate() {
    return new Promise(function (resolve, reject) {
      try {
        var cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
        if (cached && cached.rate > 0 && (Date.now() - cached.ts) < CACHE_TTL) {
          return resolve(cached.rate);
        }
      } catch (e) {}

      fetch(RATE_URL)
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var rate = d && d.rates && d.rates.USD;
          if (!rate || rate <= 0) return reject(new Error('No USD rate'));
          try { localStorage.setItem(CACHE_KEY, JSON.stringify({ rate: rate, ts: Date.now() })); } catch (e) {}
          resolve(rate);
        })
        .catch(reject);
    });
  }

  // ── DOM conversion ────────────────────────────────────────────────────────
  var SKIP_TAGS = { SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, TEXTAREA: 1, INPUT: 1, SELECT: 1, OPTION: 1 };

  function eachTextNode(root, fn) {
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: function (node) {
        var p = node.parentNode;
        if (!p || SKIP_TAGS[p.nodeName]) return NodeFilter.FILTER_REJECT;
        return NAIRA_RE.test(node.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
      },
    });
    var nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(fn);
  }

  function formatUsd(n) {
    return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function convertNode(node) {
    var original = node.nodeValue;
    NAIRA_RE.lastIndex = 0;
    var replaced = original.replace(NAIRA_RE, function (_, num) {
      var ngn = parseFloat(num.replace(/,/g, ''));
      if (isNaN(ngn)) return _;
      return formatUsd(ngn * state.rate);
    });
    if (replaced !== original) {
      state.converted.push({ node: node, original: original });
      node.nodeValue = replaced;
    }
  }

  function convertAll(root) {
    eachTextNode(root || document.body, convertNode);
  }

  function restoreAll() {
    state.converted.forEach(function (entry) {
      // Node may have been replaced by AJAX - only restore if still attached
      if (entry.node && entry.node.parentNode) entry.node.nodeValue = entry.original;
    });
    state.converted = [];
  }

  // ── Watch for AJAX price updates (cart totals, shipping, etc.) ────────────
  function startObserver() {
    if (state.observer) return;
    state.observer = new MutationObserver(function (mutations) {
      if (state.currency !== 'USD' || !state.rate) return;
      mutations.forEach(function (m) {
        if (m.type === 'characterData') {
          NAIRA_RE.lastIndex = 0;
          if (NAIRA_RE.test(m.target.nodeValue)) convertNode(m.target);
        }
        m.addedNodes && m.addedNodes.forEach(function (n) {
          if (n.nodeType === 1) convertAll(n);
          else if (n.nodeType === 3) {
            NAIRA_RE.lastIndex = 0;
            if (NAIRA_RE.test(n.nodeValue)) convertNode(n);
          }
        });
      });
    });
    state.observer.observe(document.body, { childList: true, subtree: true, characterData: true });
  }

  // ── Apply / switch ────────────────────────────────────────────────────────
  function applyCurrency(cur) {
    if (cur === 'USD') {
      if (!state.rate) return; // rate not loaded, stay in NGN
      restoreAll();            // idempotent
      state.currency = 'USD';
      convertAll();
      startObserver();
    } else {
      state.currency = 'NGN';
      restoreAll();
    }
    updatePill();
  }

  window.phelyzSetCurrency = function (cur) {
    try { localStorage.setItem(PREF_KEY, cur); } catch (e) {}
    if (cur === 'USD' && !state.rate) {
      getRate().then(function (rate) {
        state.rate = rate;
        applyCurrency('USD');
      }).catch(function () {
        if (typeof showToast === 'function') showToast('Exchange rate unavailable right now', 'error');
      });
      return;
    }
    applyCurrency(cur);
  };

  // ── Floating toggle pill ──────────────────────────────────────────────────
  function buildPill() {
    if (document.getElementById('currency-pill')) return;
    var pill = document.createElement('div');
    pill.id = 'currency-pill';
    pill.title = 'Display currency - orders are charged in Naira (₦)';
    pill.style.cssText =
      'position:fixed;bottom:24px;left:24px;z-index:98;display:flex;align-items:center;' +
      'background:rgba(28,25,23,0.92);border-radius:99px;padding:4px;gap:2px;' +
      'box-shadow:0 4px 16px rgba(0,0,0,0.25);backdrop-filter:blur(4px);';
    pill.innerHTML =
      '<button data-cur="NGN" style="border:none;border-radius:99px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">₦ NGN</button>' +
      '<button data-cur="USD" style="border:none;border-radius:99px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">$ USD</button>';
    pill.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () { window.phelyzSetCurrency(b.getAttribute('data-cur')); });
    });
    document.body.appendChild(pill);
    updatePill();
  }

  function updatePill() {
    var pill = document.getElementById('currency-pill');
    if (!pill) return;
    pill.querySelectorAll('button').forEach(function (b) {
      var active = b.getAttribute('data-cur') === state.currency;
      b.style.background = active ? 'var(--gold, #CA8A04)' : 'transparent';
      b.style.color = active ? 'white' : 'rgba(255,255,255,0.65)';
    });
  }

  // ── Boot ──────────────────────────────────────────────────────────────────
  function init() {
    buildPill();
    var wanted = preferredCurrency();
    if (wanted === 'USD') {
      getRate().then(function (rate) {
        state.rate = rate;
        applyCurrency('USD');
      }).catch(function () { /* rate unavailable - stay NGN */ });
    } else {
      // Pre-warm the rate in the background so a manual toggle is instant
      getRate().then(function (rate) { state.rate = rate; }).catch(function () {});
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
