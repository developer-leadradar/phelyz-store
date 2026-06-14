/**
 * Phelyz Image Studio — browser-side processing pipeline.
 *
 * Per image:
 *   1. Background removal (@imgly/background-removal, runs locally on CPU)
 *   2. Composite cutout onto the chosen template background
 *   3. Optional watermark
 *   4. Encode to WebP at chosen size
 *   5. POST data-url to /api/image-studio/save-processed.php → DB row
 *
 * Then per-output the admin can:
 *   - Approve & assign to product (POST /api/image-studio/assign-product.php)
 *   - Generate model shot via Gemini (POST /api/image-studio/generate-model.php)
 *   - Reject (just removes from queue)
 */

(function() {
  'use strict';

  const MAX_FILES = 10;
  const STATE = { items: [] };

  // ── Drop / file pick ──────────────────────────────────────────────────────
  window.studioHandleDrop = function(e) {
    e.preventDefault();
    const dz = document.getElementById('studio-dropzone');
    dz.style.borderColor = 'var(--cream-dark)';
    dz.style.background = 'var(--cream)';
    studioHandleFiles(e.dataTransfer.files);
  };

  window.studioHandleFiles = function(fileList) {
    if (!fileList || !fileList.length) return;
    const files = Array.from(fileList).filter(f => f.type.startsWith('image/'));
    if (!files.length) {
      showToast('Please drop image files only', 'error');
      return;
    }
    const room = MAX_FILES - STATE.items.length;
    if (room <= 0) {
      showToast('Queue is full (max 10). Process or clear first.', 'error');
      return;
    }
    const accepted = files.slice(0, room);
    if (files.length > room) {
      showToast(`Only added the first ${room} — limit is 10 at a time.`, 'info');
    }
    accepted.forEach(addItemFromFile);
    refreshQueueVisibility();
  };

  // ── Add a new item to the queue ───────────────────────────────────────────
  function addItemFromFile(file) {
    const id = 'it_' + Math.random().toString(36).slice(2, 9);
    const item = {
      id, file, status: 'queued',
      sourceDataUrl: null,
      cutoutBlob: null,
      processedDataUrl: null,
      processedPath: null,
      outputId: null,
      jobId: null,
      modelDataUrl: null,
      modelPath: null,
      modelOutputId: null,
    };
    STATE.items.push(item);
    const reader = new FileReader();
    reader.onload = function(e) {
      item.sourceDataUrl = e.target.result;
      renderCard(item);
    };
    reader.readAsDataURL(file);
  }

  // ── Render a single item card ─────────────────────────────────────────────
  function renderCard(item) {
    let card = document.getElementById('card-' + item.id);
    if (!card) {
      card = document.createElement('div');
      card.id = 'card-' + item.id;
      card.style.cssText = 'border:1px solid var(--cream-dark);border-radius:12px;overflow:hidden;background:white;display:flex;flex-direction:column;';
      document.getElementById('studio-items').appendChild(card);
    }

    const sourceImg = item.sourceDataUrl
      ? `<img src="${item.sourceDataUrl}" alt="src" style="width:50%;height:140px;object-fit:cover;display:block;border-right:1px solid var(--cream-dark);">`
      : '<div style="width:50%;height:140px;background:var(--cream);"></div>';

    const processedImg = item.processedDataUrl
      ? `<img src="${item.processedDataUrl}" alt="out" style="width:50%;height:140px;object-fit:cover;display:block;background:white;">`
      : `<div style="width:50%;height:140px;background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--stone-mid);">${statusLabel(item)}</div>`;

    let actions = '';
    if (item.status === 'queued' || item.status === 'failed') {
      actions = `<button onclick="studioProcessItem('${item.id}')" class="btn btn-gold btn-sm" style="font-size:12px;padding:7px 12px;">Process</button>`;
    } else if (item.status === 'processing' || item.status === 'generating') {
      actions = `<button class="btn btn-outline btn-sm" disabled style="font-size:12px;padding:7px 12px;opacity:0.6;">${statusLabel(item)}…</button>`;
    } else if (item.status === 'done') {
      const genButton = window.STUDIO_PROVIDER_READY
        ? `<button onclick="studioGenerateModel('${item.id}')" class="btn btn-outline btn-sm" style="font-size:12px;padding:7px 12px;">Generate model shot</button>`
        : `<button onclick="showToast('Add your Gemini API key in Settings first', 'info')" class="btn btn-outline btn-sm" style="font-size:12px;padding:7px 12px;opacity:0.6;">Generate model shot</button>`;
      actions = `
        <button onclick="studioOpenAssign('${item.id}', 'processed')" class="btn btn-gold btn-sm" style="font-size:12px;padding:7px 12px;">Assign to product</button>
        ${genButton}
      `;
    } else if (item.status === 'assigned') {
      actions = `<span style="font-size:12px;font-weight:600;color:#22C55E;">✓ Assigned</span>`;
    }

    const modelBlock = item.modelDataUrl
      ? `
        <div style="border-top:1px solid var(--cream-dark);padding:10px 12px;background:var(--cream);">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--stone-mid);margin-bottom:6px;">AI model shot</div>
          <img src="${item.modelDataUrl}" alt="model" style="width:100%;height:140px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
          <button onclick="studioOpenAssign('${item.id}', 'model')" class="btn btn-gold btn-sm" style="width:100%;font-size:12px;padding:7px 10px;">Assign model shot to product</button>
        </div>`
      : '';

    card.innerHTML = `
      <div style="display:flex;">
        ${sourceImg}
        ${processedImg}
      </div>
      <div style="padding:10px 12px;font-size:12px;color:var(--stone-mid);border-top:1px solid var(--cream-dark);">
        <div style="font-weight:600;color:var(--black);font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(item.file.name)}</div>
        <div style="margin-top:2px;">Original → Processed</div>
      </div>
      <div style="padding:10px 12px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;border-top:1px solid var(--cream-dark);">
        ${actions}
        <button onclick="studioRemoveItem('${item.id}')" style="margin-left:auto;background:none;border:none;color:var(--stone-mid);cursor:pointer;font-size:12px;padding:4px;">Remove</button>
      </div>
      ${modelBlock}
    `;
  }

  function statusLabel(item) {
    return {
      queued:     'Queued',
      processing: 'Processing',
      generating: 'Generating AI shot',
      done:       'Processed',
      failed:     'Failed — try again',
      assigned:   'Assigned',
    }[item.status] || item.status;
  }

  // ── Process all in queue (sequentially to avoid memory spikes) ───────────
  window.studioProcessAll = async function() {
    const btn = document.getElementById('btn-process-all');
    btn.disabled = true;
    btn.style.opacity = '0.6';
    for (const item of STATE.items) {
      if (item.status === 'queued' || item.status === 'failed') {
        await studioProcessItem(item.id);
      }
    }
    btn.disabled = false;
    btn.style.opacity = '';
  };

  // ── Process one image ─────────────────────────────────────────────────────
  window.studioProcessItem = async function(itemId) {
    const item = findItem(itemId); if (!item) return;
    const templateId = parseInt(document.getElementById('opt-template').value || '0');
    if (!templateId) { showToast('Pick a template background first', 'error'); return; }
    const template = window.STUDIO_TEMPLATES.find(t => t.id === templateId);
    if (!template) { showToast('Template not found', 'error'); return; }

    if (typeof window.studioRemoveBackground !== 'function') {
      showToast('Background remover is still loading — try again in a few seconds', 'info');
      return;
    }

    item.status = 'processing'; renderCard(item);

    try {
      // 1. Background removal (returns a Blob)
      const cutoutBlob = await window.studioRemoveBackground(item.file);
      item.cutoutBlob = cutoutBlob;

      // 2. Composite onto template, watermark, resize
      const dataUrl = await composeOnTemplate(cutoutBlob, template.image_path);

      item.processedDataUrl = dataUrl;
      item.status = 'done';
      renderCard(item);

      // 3. POST to server (best-effort; UI works without DB)
      try {
        const res = await fetch('/api/image-studio/save-processed.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            input_filename: item.file.name,
            image_data_url: dataUrl,
            output_type: 'template',
            job_id: item.jobId || 0,
          }),
        });
        const j = await res.json();
        if (j.ok) {
          item.outputId = j.output_id || null;
          item.jobId    = j.job_id    || item.jobId;
          item.processedPath = j.output_path;
        }
      } catch(e) { /* offline-ish — still usable */ }
    } catch (err) {
      console.error(err);
      item.status = 'failed';
      renderCard(item);
      showToast('Could not process ' + item.file.name, 'error');
    }
  };

  // ── Compose cutout onto template ──────────────────────────────────────────
  async function composeOnTemplate(cutoutBlob, templatePath) {
    const size      = parseInt(document.getElementById('opt-size').value || '1000');
    const watermark = document.getElementById('opt-watermark').checked;

    const [cutoutImg, bgImg] = await Promise.all([
      loadImageFromBlob(cutoutBlob),
      loadImageFromUrl(templatePath),
    ]);

    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    // 1. Cover-fit the template
    drawCover(ctx, bgImg, 0, 0, size, size);

    // 2. Subtle vignette so the product sits "in" the scene, not pasted on
    const grad = ctx.createRadialGradient(size/2, size/2, size*0.25, size/2, size/2, size*0.7);
    grad.addColorStop(0, 'rgba(0,0,0,0)');
    grad.addColorStop(1, 'rgba(0,0,0,0.18)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, size, size);

    // 3. Place cutout — contain-fit to 78% of canvas
    const targetBox = size * 0.78;
    const ratio = Math.min(targetBox / cutoutImg.width, targetBox / cutoutImg.height);
    const drawW = cutoutImg.width  * ratio;
    const drawH = cutoutImg.height * ratio;
    const drawX = (size - drawW) / 2;
    const drawY = (size - drawH) / 2;

    // Soft contact shadow underneath
    ctx.save();
    ctx.shadowColor = 'rgba(0,0,0,0.30)';
    ctx.shadowBlur  = size * 0.04;
    ctx.shadowOffsetY = size * 0.015;
    ctx.drawImage(cutoutImg, drawX, drawY, drawW, drawH);
    ctx.restore();

    // 4. Optional watermark
    if (watermark) {
      ctx.save();
      ctx.font        = `600 ${Math.round(size * 0.022)}px Montserrat, system-ui, sans-serif`;
      ctx.fillStyle   = 'rgba(255,255,255,0.85)';
      ctx.strokeStyle = 'rgba(0,0,0,0.25)';
      ctx.lineWidth   = 0.6;
      ctx.textAlign   = 'right';
      ctx.textBaseline= 'bottom';
      const txt = 'PHELYZ';
      ctx.strokeText(txt, size - size*0.025, size - size*0.025);
      ctx.fillText  (txt, size - size*0.025, size - size*0.025);
      ctx.restore();
    }

    return canvas.toDataURL('image/webp', 0.92);
  }

  // ── Generate model shot via API ───────────────────────────────────────────
  window.studioGenerateModel = async function(itemId) {
    const item = findItem(itemId); if (!item) return;
    if (!item.processedPath && !item.processedDataUrl) {
      showToast('Process the image first', 'error'); return;
    }
    const presetId = parseInt(document.getElementById('opt-preset').value || '0');

    item.status = 'generating'; renderCard(item);

    try {
      // If we haven't saved to server yet, try to save now so Gemini can fetch it
      let sourcePath = item.processedPath;
      if (!sourcePath) {
        const saveRes = await fetch('/api/image-studio/save-processed.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            input_filename: item.file.name,
            image_data_url: item.processedDataUrl,
            output_type: 'template',
            job_id: item.jobId || 0,
          }),
        });
        const j = await saveRes.json();
        if (!j.ok) throw new Error(j.message || 'Could not save source');
        sourcePath = j.output_path;
        item.processedPath = sourcePath;
        item.outputId = j.output_id || null;
        item.jobId    = j.job_id    || item.jobId;
      }

      const res = await fetch('/api/image-studio/generate-model.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          source_path: sourcePath,
          preset_id:   presetId,
          job_id:      item.jobId || 0,
        }),
      });
      const j = await res.json();
      if (!j.ok) throw new Error(j.message || 'Generation failed');
      item.modelDataUrl   = j.output_path; // remote URL works as src
      item.modelPath      = j.output_path;
      item.modelOutputId  = j.output_id || null;
      item.status = 'done';
      renderCard(item);
      showToast('Model shot generated', 'success');
    } catch (err) {
      console.error(err);
      item.status = 'done'; // keep the processed image usable
      renderCard(item);
      showToast(err.message || 'Could not generate model shot', 'error');
    }
  };

  // ── Assign to product (modal) ─────────────────────────────────────────────
  window.studioOpenAssign = function(itemId, kind) {
    const item = findItem(itemId); if (!item) return;
    const outputId = kind === 'model' ? item.modelOutputId : item.outputId;
    const previewUrl = kind === 'model' ? (item.modelDataUrl || item.modelPath) : (item.processedDataUrl || item.processedPath);
    if (!outputId) {
      showToast('This image hasn\'t been saved to the server yet — try processing again.', 'error');
      return;
    }

    const modal = document.createElement('div');
    modal.id = 'studio-assign-modal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
    modal.innerHTML = `
      <div style="background:white;border-radius:14px;width:100%;max-width:520px;overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between;">
          <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Assign to product</h3>
          <button onclick="studioCloseAssign()" style="background:none;border:none;font-size:22px;line-height:1;cursor:pointer;color:var(--stone-mid);">×</button>
        </div>
        <div style="padding:20px;">
          <div style="display:flex;gap:12px;align-items:center;margin-bottom:18px;">
            <img src="${previewUrl}" alt="preview" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--cream-dark);">
            <div style="font-size:13px;color:var(--stone-mid);">
              ${kind === 'model' ? '<strong style="color:var(--black);">AI model shot</strong>' : '<strong style="color:var(--black);">Processed image</strong>'}<br>
              You can set it as the product's primary photo, or just add it to the gallery.
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Search a product</label>
            <input type="search" id="assign-search" placeholder="Type product name or SKU…" class="form-input"
                   oninput="studioSearchProducts(this.value)" autofocus>
          </div>
          <div id="assign-results" style="max-height:240px;overflow:auto;border:1px solid var(--cream-dark);border-radius:8px;background:var(--cream);"></div>
          <div style="display:flex;gap:14px;margin-top:14px;align-items:center;font-size:13px;color:var(--stone);">
            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
              <input type="radio" name="assign-role" value="gallery" checked style="accent-color:var(--gold);">
              Add to gallery
            </label>
            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
              <input type="radio" name="assign-role" value="primary" style="accent-color:var(--gold);">
              Replace primary image
            </label>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);
    modal._meta = { outputId };
    studioSearchProducts('');
  };

  window.studioCloseAssign = function() {
    const m = document.getElementById('studio-assign-modal');
    if (m) m.remove();
  };

  let searchTimer = null;
  window.studioSearchProducts = function(q) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async function() {
      try {
        const res = await fetch('/api/image-studio/search-products.php?q=' + encodeURIComponent(q || ''));
        const j = await res.json();
        const box = document.getElementById('assign-results');
        if (!box) return;
        if (!j.products || !j.products.length) {
          box.innerHTML = '<div style="padding:14px;text-align:center;font-size:12px;color:var(--stone-mid);">No products found.</div>';
          return;
        }
        box.innerHTML = j.products.map(p => `
          <div onclick="studioAssignTo(${p.id})" style="display:flex;align-items:center;gap:10px;padding:10px 12px;cursor:pointer;border-bottom:1px solid var(--cream-dark);background:white;" onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background='white'">
            <img src="${p.image || ''}" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0;background:var(--cream-dark);" onerror="this.style.background='var(--cream-dark)';this.src='';">
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(p.name)}</div>
              <div style="font-size:11px;color:var(--stone-mid);">${escapeHtml(p.sku)}</div>
            </div>
          </div>
        `).join('');
      } catch(e) { /* ignore */ }
    }, 200);
  };

  window.studioAssignTo = async function(productId) {
    const modal = document.getElementById('studio-assign-modal');
    if (!modal) return;
    const outputId = modal._meta.outputId;
    const role = (document.querySelector('input[name="assign-role"]:checked') || {}).value || 'gallery';
    try {
      const res = await fetch('/api/image-studio/assign-product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ output_id: outputId, product_id: productId, role }),
      });
      const j = await res.json();
      if (!j.ok) throw new Error(j.message || 'Assign failed');
      showToast('Image assigned to product', 'success');
      studioCloseAssign();
      // Mark the item as assigned in our local state
      const it = STATE.items.find(i => i.outputId === outputId || i.modelOutputId === outputId);
      if (it) { it.status = 'assigned'; renderCard(it); }
    } catch(err) {
      showToast(err.message || 'Could not assign', 'error');
    }
  };

  // ── Misc helpers ──────────────────────────────────────────────────────────
  window.studioRemoveItem = function(itemId) {
    const idx = STATE.items.findIndex(i => i.id === itemId);
    if (idx === -1) return;
    STATE.items.splice(idx, 1);
    const card = document.getElementById('card-' + itemId);
    if (card) card.remove();
    refreshQueueVisibility();
  };

  window.studioReset = function() {
    STATE.items = [];
    document.getElementById('studio-items').innerHTML = '';
    refreshQueueVisibility();
  };

  function refreshQueueVisibility() {
    document.getElementById('studio-queue').style.display = STATE.items.length ? 'block' : 'none';
  }

  function findItem(id) { return STATE.items.find(i => i.id === id); }

  function loadImageFromBlob(blob) {
    return new Promise(function(resolve, reject) {
      const url = URL.createObjectURL(blob);
      const img = new Image();
      img.onload  = function() { URL.revokeObjectURL(url); resolve(img); };
      img.onerror = function() { URL.revokeObjectURL(url); reject(new Error('Could not load cutout')); };
      img.src = url;
    });
  }

  function loadImageFromUrl(src) {
    return new Promise(function(resolve, reject) {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload  = function() { resolve(img); };
      img.onerror = function() { reject(new Error('Could not load template image ' + src)); };
      img.src = src;
    });
  }

  function drawCover(ctx, img, x, y, w, h) {
    const ir = img.width / img.height;
    const cr = w / h;
    let dw, dh, dx, dy;
    if (ir > cr) {
      dh = h; dw = h * ir; dx = x - (dw - w) / 2; dy = y;
    } else {
      dw = w; dh = w / ir; dx = x; dy = y - (dh - h) / 2;
    }
    ctx.drawImage(img, dx, dy, dw, dh);
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }
})();
