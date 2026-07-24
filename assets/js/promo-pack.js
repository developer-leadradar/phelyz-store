/**
 * Phelyz Promo Pack — builds 1080×1920 WhatsApp-status images in the browser.
 * No server round-trip, no cost. Each selected product becomes:
 *   - a status-sized JPEG (product photo + gradient + name + price + CTA + brand)
 *   - a ready caption with the product link.
 */
(function () {
  'use strict';

  var W = 1080, H = 1920;
  var selected = new Set();

  var THEMES = {
    dark:  { bg1: '#1C1917', bg2: '#0C0A09', text: '#FFFFFF', accent: '#CA8A04', sub: 'rgba(255,255,255,0.72)' },
    cream: { bg1: '#F5F1EA', bg2: '#E7E0D4', text: '#1C1917', accent: '#B45309', sub: 'rgba(28,25,23,0.62)' },
    gold:  { bg1: '#7A5B12', bg2: '#3A2A06', text: '#FFFFFF', accent: '#FDE68A', sub: 'rgba(255,255,255,0.8)' },
  };

  // ── Selection ───────────────────────────────────────────────────────────
  window.togglePromo = function (id) {
    if (selected.has(id)) selected.delete(id); else selected.add(id);
    reflect(id);
    updateBar();
  };

  function reflect(id) {
    var el = document.querySelector('.promo-item[data-id="' + id + '"]');
    if (!el) return;
    var on = selected.has(id);
    el.style.borderColor = on ? 'var(--gold)' : 'var(--cream-dark)';
    var chk = el.querySelector('.promo-check');
    if (chk) {
      chk.style.display = on ? 'flex' : 'none';
      chk.style.background = on ? 'var(--gold)' : 'white';
      chk.style.borderColor = on ? 'var(--gold)' : 'var(--cream-dark)';
    }
  }

  window.promoSelectAllVisible = function () {
    document.querySelectorAll('.promo-item').forEach(function (el) {
      if (el.style.display === 'none') return;
      selected.add(parseInt(el.getAttribute('data-id')));
      reflect(parseInt(el.getAttribute('data-id')));
    });
    updateBar();
  };

  window.promoClear = function () {
    selected.forEach(function (id) { selected.delete(id); reflect(id); });
    updateBar();
  };

  function updateBar() {
    document.getElementById('promo-count').textContent = selected.size + ' selected';
    var btn = document.getElementById('promo-generate-btn');
    btn.disabled = selected.size === 0;
    btn.style.opacity = selected.size === 0 ? '0.5' : '1';
  }

  // ── Filters ─────────────────────────────────────────────────────────────
  window.filterPromo = function (q) {
    q = (q || '').trim().toLowerCase();
    document.querySelectorAll('.promo-item').forEach(function (el) {
      var name = el.getAttribute('data-name') || '';
      el.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
    });
  };
  window.filterPromoCat = function (cat) {
    document.querySelectorAll('.promo-item').forEach(function (el) {
      el.style.display = !cat || el.getAttribute('data-cat') === cat ? '' : 'none';
    });
  };

  // ── Image loading ───────────────────────────────────────────────────────
  function loadImg(src) {
    return new Promise(function (resolve) {
      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function () { resolve(img); };
      img.onerror = function () { resolve(null); }; // continue even if a photo fails CORS
      img.src = src;
    });
  }

  function drawCover(ctx, img, x, y, w, h) {
    var ir = img.width / img.height, cr = w / h, dw, dh, dx, dy;
    if (ir > cr) { dh = h; dw = h * ir; dx = x - (dw - w) / 2; dy = y; }
    else { dw = w; dh = w / ir; dx = x; dy = y - (dh - h) / 2; }
    ctx.drawImage(img, dx, dy, dw, dh);
  }

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function wrapText(ctx, text, maxWidth) {
    var words = text.split(' '), lines = [], line = '';
    for (var i = 0; i < words.length; i++) {
      var test = line ? line + ' ' + words[i] : words[i];
      if (ctx.measureText(test).width > maxWidth && line) { lines.push(line); line = words[i]; }
      else line = test;
    }
    if (line) lines.push(line);
    return lines;
  }

  // ── Render one status image ─────────────────────────────────────────────
  async function renderStatus(product, theme, opts) {
    var t = THEMES[theme] || THEMES.dark;
    var canvas = document.createElement('canvas');
    canvas.width = W; canvas.height = H;
    var ctx = canvas.getContext('2d');

    // Background gradient
    var g = ctx.createLinearGradient(0, 0, 0, H);
    g.addColorStop(0, t.bg1); g.addColorStop(1, t.bg2);
    ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);

    // Product photo in a rounded card (upper ~62%)
    var pad = 70, cardY = 250, cardH = 980, cardW = W - pad * 2;
    var img = await loadImg(product.image);
    ctx.save();
    roundRect(ctx, pad, cardY, cardW, cardH, 40);
    ctx.clip();
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(pad, cardY, cardW, cardH);
    if (img) drawCover(ctx, img, pad, cardY, cardW, cardH);
    ctx.restore();

    // Card border
    ctx.strokeStyle = t.accent; ctx.lineWidth = 4;
    roundRect(ctx, pad, cardY, cardW, cardH, 40); ctx.stroke();

    // Headline (top)
    ctx.textAlign = 'center';
    ctx.fillStyle = t.accent;
    ctx.font = '700 54px Montserrat, system-ui, sans-serif';
    ctx.fillText((opts.headline || '').toUpperCase(), W / 2, 160);

    // Product name
    ctx.fillStyle = t.text;
    ctx.font = '700 72px "Cormorant", Georgia, serif';
    var nameLines = wrapText(ctx, product.name, W - pad * 2);
    var ny = cardY + cardH + 130;
    nameLines.slice(0, 2).forEach(function (ln, i) { ctx.fillText(ln, W / 2, ny + i * 84); });

    // Category
    if (product.category) {
      ctx.fillStyle = t.sub;
      ctx.font = '600 34px Montserrat, sans-serif';
      ctx.fillText(product.category.toUpperCase(), W / 2, ny + nameLines.slice(0, 2).length * 84 + 30);
    }

    // Price pill
    if (opts.showPrice) {
      var priceY = ny + nameLines.slice(0, 2).length * 84 + 120;
      ctx.font = '800 64px Montserrat, sans-serif';
      var pw = ctx.measureText(product.price).width + 100;
      ctx.fillStyle = t.accent;
      roundRect(ctx, (W - pw) / 2, priceY - 62, pw, 96, 48); ctx.fill();
      ctx.fillStyle = theme === 'gold' ? '#3A2A06' : (theme === 'cream' ? '#FFFFFF' : '#1C1917');
      ctx.fillText(product.price, W / 2, priceY);
      if (product.compare) {
        ctx.fillStyle = t.sub;
        ctx.font = '600 38px Montserrat, sans-serif';
        ctx.save();
        var cx = W / 2, cwid = ctx.measureText(product.compare).width;
        ctx.fillText(product.compare, cx, priceY + 62);
        ctx.strokeStyle = t.sub; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(cx - cwid / 2, priceY + 50); ctx.lineTo(cx + cwid / 2, priceY + 50); ctx.stroke();
        ctx.restore();
      }
    }

    // CTA (bottom)
    ctx.fillStyle = t.text;
    ctx.font = '600 42px Montserrat, sans-serif';
    ctx.fillText(opts.cta || '', W / 2, H - 190);

    // Brand strip
    ctx.fillStyle = t.accent;
    ctx.font = '800 46px "Cormorant", serif';
    ctx.fillText((window.PROMO_STORE.name || 'PHELYZ').toUpperCase(), W / 2, H - 100);
    ctx.fillStyle = t.sub;
    ctx.font = '500 30px Montserrat, sans-serif';
    var host = (window.PROMO_STORE.url || '').replace(/^https?:\/\//, '');
    ctx.fillText(host, W / 2, H - 55);

    return canvas.toDataURL('image/jpeg', 0.92);
  }

  function buildCaption(product, opts) {
    var s = window.PROMO_STORE;
    var lines = [];
    lines.push((opts.headline || '') + ' — ' + product.name);
    if (opts.showPrice) lines.push('Price: ' + product.price + (product.compare ? ' (was ' + product.compare + ')' : ''));
    lines.push('');
    lines.push(opts.cta || 'Order now:');
    lines.push(product.link);
    if (s.wa) lines.push('WhatsApp: https://wa.me/' + s.wa);
    return lines.join('\n');
  }

  // ── Generate ────────────────────────────────────────────────────────────
  var generated = []; // {name, dataUrl, caption}

  window.promoGenerate = async function () {
    if (!selected.size) return;
    var btn = document.getElementById('promo-generate-btn');
    btn.disabled = true; btn.textContent = 'Generating…';

    var theme = document.getElementById('promo-theme').value;
    var opts = {
      headline:  document.getElementById('promo-headline').value,
      cta:       document.getElementById('promo-cta').value,
      showPrice: document.getElementById('promo-show-price').checked,
    };

    generated = [];
    var results = document.getElementById('promo-results');
    results.innerHTML = '';
    document.getElementById('promo-output').style.display = 'block';

    var list = window.PROMO_PRODUCTS.filter(function (p) { return selected.has(p.id); });

    for (var i = 0; i < list.length; i++) {
      var p = list[i];
      var dataUrl = await renderStatus(p, theme, opts);
      var caption = buildCaption(p, opts);
      var safeName = p.name.replace(/[^a-z0-9]+/gi, '-').toLowerCase().slice(0, 40);
      generated.push({ name: 'phelyz-status-' + safeName + '.jpg', dataUrl: dataUrl, caption: caption });

      var card = document.createElement('div');
      card.style.cssText = 'border:1px solid var(--cream-dark);border-radius:10px;overflow:hidden;background:white;';
      card.innerHTML =
        '<img src="' + dataUrl + '" style="width:100%;display:block;aspect-ratio:9/16;object-fit:cover;">' +
        '<div style="padding:10px;">' +
          '<button data-idx="' + i + '" class="promo-dl btn btn-gold btn-sm" style="width:100%;font-size:12px;margin-bottom:6px;">⬇ Download</button>' +
          '<textarea readonly style="width:100%;height:90px;font-size:11px;border:1px solid var(--cream-dark);border-radius:6px;padding:6px;resize:none;font-family:inherit;">' + caption + '</textarea>' +
          '<button data-cap="' + i + '" class="promo-copy" style="width:100%;margin-top:6px;background:none;border:none;color:var(--gold);font-size:12px;font-weight:600;cursor:pointer;">📋 Copy caption</button>' +
        '</div>';
      results.appendChild(card);
    }

    // Wire per-card buttons
    results.querySelectorAll('.promo-dl').forEach(function (b) {
      b.addEventListener('click', function () { downloadOne(generated[parseInt(b.getAttribute('data-idx'))]); });
    });
    results.querySelectorAll('.promo-copy').forEach(function (b) {
      b.addEventListener('click', function () {
        navigator.clipboard.writeText(generated[parseInt(b.getAttribute('data-cap'))].caption)
          .then(function () { showToast('Caption copied', 'success'); });
      });
    });

    btn.disabled = false; btn.textContent = 'Generate Promo Pack';
  };

  function downloadOne(item) {
    var a = document.createElement('a');
    a.href = item.dataUrl; a.download = item.name;
    document.body.appendChild(a); a.click(); a.remove();
  }

  window.promoDownloadAll = function () {
    // Trigger sequential downloads (browsers throttle, so space them out)
    generated.forEach(function (item, i) {
      setTimeout(function () { downloadOne(item); }, i * 400);
    });
    showToast('Downloading ' + generated.length + ' images…', 'success');
  };

  window.promoCopyCaptions = function () {
    var all = generated.map(function (g, i) { return '── ' + (i + 1) + ' ──\n' + g.caption; }).join('\n\n');
    navigator.clipboard.writeText(all).then(function () { showToast('All captions copied', 'success'); });
  };
})();
