/**
 * Product image picker for the admin add/edit forms.
 *
 * Two things this has to get right that the old inline version did not.
 *
 * 1. The file input is the thing that actually gets uploaded, so it has to
 *    agree with what is on screen at all times. Previously the preview was
 *    drawn from the input but removing a picture only touched the preview, so
 *    a removed photo was still submitted - and still ended up as the primary
 *    image. A plain array is the single source of truth here, and the input is
 *    rebuilt from it after every change.
 *
 * 2. Previews are drawn synchronously. The old code read every file with a
 *    FileReader and appended each thumbnail in a callback, so a slow read from
 *    a selection you had already cleared would arrive late and reinsert itself,
 *    complete with the "Primary" badge. Object URLs need no callback at all.
 *
 * It also shrinks photos before they are sent. A phone camera file is 3-5MB and
 * shared hosting often refuses anything over 2MB, so the upload failed through
 * no fault of the shop owner. Resizing in the browser means the full-size photo
 * can be picked exactly as it comes off the camera and still arrive well inside
 * any server limit - and it uploads in a fraction of the time on mobile data.
 */
(function () {
  'use strict';

  var MAX_EDGE   = 1600;        // matches optimiseImageFile() on the server
  var QUALITY    = 0.85;
  var LEAVE_ALONE = 400 * 1024; // already small: don't re-encode and lose quality

  /**
   * Downscale one image. Returns the original file untouched if it is already
   * small, if the browser cannot do the work, or if the result comes out no
   * smaller than what we started with.
   */
  async function shrink(file) {
    if (!/^image\//.test(file.type)) return file;
    if (file.type === 'image/gif') return file;          // may be animated
    if (file.size <= LEAVE_ALONE)  return file;

    try {
      var bitmap;
      if (window.createImageBitmap) {
        // from-image applies the camera's rotation tag, so portrait photos are
        // not stored on their side.
        try {
          bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch (e) {
          bitmap = await createImageBitmap(file);
        }
      } else {
        bitmap = await loadViaImgTag(file);
      }

      var w = bitmap.width, h = bitmap.height;
      var scale = Math.min(1, MAX_EDGE / Math.max(w, h));
      if (scale === 1 && file.size <= 2 * 1024 * 1024) {
        if (bitmap.close) bitmap.close();
        return file;
      }

      var canvas = document.createElement('canvas');
      canvas.width  = Math.round(w * scale);
      canvas.height = Math.round(h * scale);
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#FFFFFF';                  // flatten transparency to white
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      if (bitmap.close) bitmap.close();

      var blob = await new Promise(function (res) {
        canvas.toBlob(res, 'image/jpeg', QUALITY);
      });
      if (!blob || blob.size >= file.size) return file;

      return new File([blob], swapExtension(file.name), {
        type: 'image/jpeg',
        lastModified: Date.now()
      });
    } catch (e) {
      console.warn('Could not resize ' + file.name + ', sending it as it is.', e);
      return file;
    }
  }

  function loadViaImgTag(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload  = function () { URL.revokeObjectURL(url); resolve(img); };
      img.onerror = function (e) { URL.revokeObjectURL(url); reject(e); };
      img.src = url;
    });
  }

  function swapExtension(name) {
    return String(name).replace(/\.[^.]+$/, '') + '.jpg';
  }

  function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  }

  function escapeAttr(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  /**
   * Wire up one picker.
   *
   * opts: { input, dropZone, container, grid, label, primaryBadge }
   * primaryBadge is false on the edit form, where a new photo joins the gallery
   * rather than replacing the main picture.
   */
  function createPicker(opts) {
    var input     = document.getElementById(opts.input);
    var dropZone  = document.getElementById(opts.dropZone);
    var container = document.getElementById(opts.container);
    var grid      = document.getElementById(opts.grid);
    var label     = document.getElementById(opts.label);
    if (!input || !grid || !dropZone || !container) return null;

    var chosen = [];   // the only thing that decides what gets uploaded
    var urls   = [];   // object URLs to release when a preview goes away

    function syncInput() {
      var dt = new DataTransfer();
      chosen.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
    }

    function releaseUrls() {
      urls.forEach(URL.revokeObjectURL);
      urls = [];
    }

    function render() {
      releaseUrls();
      grid.innerHTML = '';

      if (!chosen.length) {
        container.style.display = 'none';
        dropZone.style.display  = 'block';
        return;
      }

      if (label) {
        label.textContent = chosen.length === 1
          ? (opts.primaryBadge === false ? '1 Image To Add' : 'Selected Image')
          : 'Selected Images (' + chosen.length +
            (opts.primaryBadge === false ? ')' : ', first is primary)');
      }

      chosen.forEach(function (file, idx) {
        var url = URL.createObjectURL(file);
        urls.push(url);

        var card = document.createElement('div');
        card.style.cssText = 'position:relative;border:1px solid var(--cream-dark);' +
                             'border-radius:10px;overflow:hidden;background:white;';
        card.innerHTML =
          '<div style="position:relative;">' +
            '<img src="' + url + '" alt="" style="width:100%;height:140px;object-fit:cover;display:block;">' +
            (idx === 0 && opts.primaryBadge !== false
              ? '<span style="position:absolute;top:6px;left:6px;background:var(--gold);color:white;' +
                'font-size:10px;font-weight:700;padding:3px 8px;border-radius:99px;letter-spacing:0.04em;' +
                'text-transform:uppercase;">Primary</span>'
              : '') +
            '<button type="button" data-remove="' + idx + '" title="Remove this photo" ' +
              'style="position:absolute;top:6px;right:6px;width:26px;height:26px;border-radius:50%;' +
              'border:none;background:rgba(0,0,0,0.62);color:white;font-size:15px;line-height:1;' +
              'cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>' +
          '</div>' +
          '<div style="padding:8px 10px;">' +
            '<div style="font-size:12px;font-weight:600;color:var(--black);white-space:nowrap;' +
              'overflow:hidden;text-overflow:ellipsis;" title="' + escapeAttr(file.name) + '">' +
              escapeAttr(file.name) + '</div>' +
            '<div style="font-size:11px;color:var(--stone-mid);">' + formatSize(file.size) + '</div>' +
          '</div>';
        grid.appendChild(card);
      });

      container.style.display = 'block';
      dropZone.style.display  = 'none';
    }

    function setBusy(on, note) {
      var el = document.getElementById(opts.container + '-busy');
      if (!el) return;
      el.style.display = on ? 'block' : 'none';
      if (on && note) el.textContent = note;
    }

    async function add(fileList) {
      var incoming = Array.prototype.slice.call(fileList || []);
      if (!incoming.length) return;

      setBusy(true, 'Preparing ' + incoming.length + ' photo' + (incoming.length > 1 ? 's' : '') + '…');
      for (var i = 0; i < incoming.length; i++) {
        if (!/^image\//.test(incoming[i].type)) continue;
        chosen.push(await shrink(incoming[i]));
      }
      setBusy(false);

      syncInput();
      render();
    }

    grid.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove]');
      if (!btn) return;
      e.preventDefault();
      chosen.splice(parseInt(btn.getAttribute('data-remove'), 10), 1);
      syncInput();     // the removed photo leaves the upload, not just the screen
      render();
    });

    input.addEventListener('change', function () {
      // Nothing chosen (the picker was cancelled) must not wipe the gallery.
      if (input.files && input.files.length) add(input.files);
    });

    dropZone.addEventListener('dragover', function (e) {
      e.preventDefault();
      dropZone.style.borderColor = 'var(--gold)';
      dropZone.style.background  = 'rgba(202,138,4,0.04)';
    });
    dropZone.addEventListener('dragleave', function () {
      dropZone.style.borderColor = 'var(--cream-dark)';
      dropZone.style.background  = 'var(--cream)';
    });
    dropZone.addEventListener('drop', function (e) {
      e.preventDefault();
      dropZone.style.borderColor = 'var(--cream-dark)';
      dropZone.style.background  = 'var(--cream)';
      if (e.dataTransfer && e.dataTransfer.files) add(e.dataTransfer.files);
    });

    return {
      clear: function () { chosen = []; syncInput(); render(); },
      count: function () { return chosen.length; }
    };
  }

  window.phelyzImagePicker = createPicker;
})();
