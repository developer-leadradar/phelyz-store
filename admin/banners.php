<?php
$pageTitle = "Promo Banners";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/banners.php';

$db = getDB();
$success = '';
$error   = '';
$presets = bannerPresets();

// ── Delete ──────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    try { $db->query("DELETE FROM promo_banners WHERE id = ?", [(int)$_GET['delete']]); $success = 'Banner deleted.'; }
    catch (Exception $e) { $error = 'Could not delete banner.'; }
}

// ── Toggle active ───────────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    try {
        $id  = (int)$_GET['toggle'];
        $cur = $db->fetchOne("SELECT is_active FROM promo_banners WHERE id = ?", [$id]);
        if ($cur) {
            $db->update('promo_banners', ['is_active' => $cur['is_active'] ? 0 : 1], 'id = ?', [$id]);
            $success = 'Banner ' . ($cur['is_active'] ? 'switched off.' : 'switched on.');
        }
    } catch (Exception $e) { $error = 'Could not update banner.'; }
}

// ── Create / update ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $ctaText  = sanitize($_POST['cta_text'] ?? '');
    $ctaUrl   = sanitize($_POST['cta_url'] ?? '');
    $preset   = isset($presets[$_POST['preset'] ?? '']) ? $_POST['preset'] : 'gold';
    $emoji    = trim($_POST['emoji'] ?? '');
    $starts   = trim($_POST['starts_at'] ?? '') ?: null;
    $ends     = trim($_POST['ends_at'] ?? '') ?: null;
    $active   = isset($_POST['is_active']) ? 1 : 0;
    $sort     = (int)($_POST['sort_order'] ?? 0);

    if ($title === '') {
        $error = 'A headline is required.';
    } else {
        $bgImage = $_POST['existing_bg'] ?? null;
        if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === 0 && $_FILES['bg_image']['size'] > 0) {
            $up = uploadImage($_FILES['bg_image'], 'banners');
            if ($up) $bgImage = $up;
        }
        if (!empty($_POST['remove_bg'])) $bgImage = null;

        $data = [
            'title' => $title, 'subtitle' => $subtitle ?: null,
            'cta_text' => $ctaText ?: null, 'cta_url' => $ctaUrl ?: null,
            'preset' => $preset, 'emoji' => $emoji ?: null,
            'bg_image' => $bgImage ?: null,
            'starts_at' => $starts, 'ends_at' => $ends,
            'is_active' => $active, 'sort_order' => $sort,
        ];
        try {
            if ($id > 0) { $db->update('promo_banners', $data, 'id = ?', [$id]); $success = 'Banner updated.'; }
            else         { $db->insert('promo_banners', $data);                  $success = 'Banner created.'; }
        } catch (Exception $e) {
            $error = 'Database error — run migrations/add_analytics_banners_tracking.sql first.';
        }
    }
}

$banners = getAllBanners();
$editing = null;
if (isset($_GET['edit'])) {
    foreach ($banners as $b) if ((int)$b['id'] === (int)$_GET['edit']) { $editing = $b; break; }
}

// Values for the form (editing or sensible defaults)
$fPreset   = $editing['preset']    ?? 'gold';
$fTitle    = $editing['title']     ?? '';
$fSubtitle = $editing['subtitle']  ?? '';
$fCta      = $editing['cta_text']  ?? '';
$fCtaUrl   = $editing['cta_url']   ?? 'shop.php';
$fEmoji    = $editing['emoji']     ?? '';
$fStarts   = $editing['starts_at'] ?? '';
$fEnds     = $editing['ends_at']   ?? '';
$fSort     = $editing['sort_order'] ?? 0;
$fActive   = $editing ? (int)$editing['is_active'] : 1;
?>

<?php if ($error): ?>
<div class="alert alert-error" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px;"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Marketing</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Promo Banners</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;max-width:660px;">
      Pick a festive look, type your message, choose the dates it should run — the banner switches itself on and off automatically.
      No design work needed. Multiple live banners rotate as a slider on the homepage.
    </p>
  </div>
</div>

<!-- ── Builder ── -->
<form method="POST" enctype="multipart/form-data" id="banner-form">
  <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>"><?php endif; ?>
  <input type="hidden" name="existing_bg" value="<?php echo htmlspecialchars($editing['bg_image'] ?? ''); ?>">

  <div class="card" style="padding:26px;margin-bottom:24px;">
    <h3 style="font-family:'Cormorant',serif;font-size:19px;font-weight:700;color:var(--black);margin:0 0 4px;">
      <?php echo $editing ? 'Edit banner' : 'Create a banner'; ?>
    </h3>
    <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 20px;">Step 1 — choose the occasion. The colours and wording fill in for you.</p>

    <!-- Preset picker -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(158px,1fr));gap:10px;margin-bottom:24px;">
      <?php foreach ($presets as $key => $p): ?>
        <button type="button" class="preset-btn" data-preset="<?php echo $key; ?>"
                data-copy='<?php echo htmlspecialchars(json_encode($p["copy"]), ENT_QUOTES); ?>'
                data-emoji="<?php echo htmlspecialchars($p['emoji']); ?>"
                onclick="pickPreset('<?php echo $key; ?>')"
                style="border:2px solid <?php echo $fPreset === $key ? 'var(--gold)' : 'transparent'; ?>;border-radius:10px;padding:0;cursor:pointer;overflow:hidden;background:none;text-align:left;">
          <div style="background:<?php echo $p['grad']; ?>;height:52px;display:flex;align-items:center;justify-content:center;font-size:20px;">
            <?php echo $p['emoji']; ?>
          </div>
          <div style="padding:7px 9px;background:white;font-size:11.5px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?php echo htmlspecialchars($p['label']); ?>
          </div>
        </button>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="preset" id="preset-input" value="<?php echo htmlspecialchars($fPreset); ?>">

    <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 14px;padding-top:6px;border-top:1px solid var(--cream-dark);">Step 2 — your message.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="form-row-2col">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Headline *</label>
        <input type="text" name="title" id="f-title" required maxlength="160" class="form-input"
               value="<?php echo htmlspecialchars($fTitle); ?>" oninput="renderPreview()">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Emoji <span style="color:var(--stone-mid);font-weight:400;">(optional)</span></label>
        <input type="text" name="emoji" id="f-emoji" maxlength="8" class="form-input" style="max-width:100px;"
               value="<?php echo htmlspecialchars($fEmoji); ?>" oninput="renderPreview()">
      </div>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">Sub-line</label>
      <input type="text" name="subtitle" id="f-subtitle" maxlength="255" class="form-input"
             value="<?php echo htmlspecialchars($fSubtitle); ?>" oninput="renderPreview()">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:16px;" class="form-row-2col">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Button text</label>
        <input type="text" name="cta_text" id="f-cta" maxlength="60" class="form-input"
               value="<?php echo htmlspecialchars($fCta); ?>" oninput="renderPreview()">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Button link</label>
        <input type="text" name="cta_url" class="form-input" placeholder="shop.php or shop.php?category=1"
               value="<?php echo htmlspecialchars($fCtaUrl); ?>">
      </div>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">Background photo <span style="color:var(--stone-mid);font-weight:400;">(optional — the preset colours are used if you skip this)</span></label>
      <input type="file" name="bg_image" accept="image/*" class="form-input">
      <?php if (!empty($editing['bg_image'])): ?>
        <label style="display:inline-flex;align-items:center;gap:7px;margin-top:8px;font-size:12.5px;cursor:pointer;">
          <input type="checkbox" name="remove_bg" value="1" style="accent-color:var(--gold);"> Remove current photo
        </label>
      <?php endif; ?>
    </div>

    <p style="font-size:12.5px;color:var(--stone-mid);margin:22px 0 14px;padding-top:14px;border-top:1px solid var(--cream-dark);">Step 3 — when should it run?</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:18px;align-items:end;">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Start date</label>
        <input type="date" name="starts_at" class="form-input" value="<?php echo htmlspecialchars($fStarts); ?>">
        <p class="form-hint">Blank = start immediately</p>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">End date</label>
        <input type="date" name="ends_at" class="form-input" value="<?php echo htmlspecialchars($fEnds); ?>">
        <p class="form-hint">Blank = run until switched off</p>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Order</label>
        <input type="number" name="sort_order" class="form-input" value="<?php echo (int)$fSort; ?>" min="0">
        <p class="form-hint">Lower shows first</p>
      </div>
      <div class="form-group" style="margin:0;">
        <label style="display:flex;align-items:center;gap:8px;padding:11px 13px;border:1.5px solid var(--cream-dark);border-radius:8px;cursor:pointer;font-size:13px;">
          <input type="checkbox" name="is_active" value="1" <?php echo $fActive ? 'checked' : ''; ?> style="accent-color:var(--gold);"> Switched on
        </label>
      </div>
    </div>
  </div>

  <!-- Live preview -->
  <div style="margin-bottom:10px;font-size:11px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);">Live preview — exactly what customers will see</div>
  <div id="banner-preview" style="border-radius:14px;overflow:hidden;margin-bottom:24px;"></div>

  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:34px;">
    <button type="submit" class="btn btn-gold"><?php echo $editing ? 'Save Changes' : 'Create Banner'; ?></button>
    <?php if ($editing): ?><a href="banners.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </div>
</form>

<!-- ── Existing banners ── -->
<div class="card" style="padding:24px;">
  <h3 style="font-family:'Cormorant',serif;font-size:19px;font-weight:700;color:var(--black);margin:0 0 16px;">
    Your banners (<?php echo count($banners); ?>)
  </h3>
  <?php if (empty($banners)): ?>
    <p style="font-size:13.5px;color:var(--stone-mid);margin:0;">No banners yet. Create one above — it appears on the homepage as soon as it's switched on and within its date range.</p>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php foreach ($banners as $b):
        $st   = bannerSlideStyle($b);
        $live = bannerIsLive($b); ?>
        <div style="display:flex;align-items:center;gap:16px;border:1px solid var(--cream-dark);border-radius:11px;padding:12px;flex-wrap:wrap;">
          <div style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['text']; ?>;border-radius:8px;padding:12px 16px;min-width:210px;flex:1;">
            <div style="font-size:13.5px;font-weight:700;">
              <?php echo $b['emoji'] ? htmlspecialchars($b['emoji']).' ' : ''; ?><?php echo htmlspecialchars($b['title']); ?>
            </div>
            <?php if ($b['subtitle']): ?>
              <div style="font-size:11.5px;opacity:0.85;margin-top:3px;"><?php echo htmlspecialchars($b['subtitle']); ?></div>
            <?php endif; ?>
          </div>
          <div style="font-size:12px;color:var(--stone-mid);min-width:150px;">
            <div><strong style="color:var(--black);"><?php echo htmlspecialchars($presets[$b['preset']]['label'] ?? $b['preset']); ?></strong></div>
            <div style="margin-top:3px;">
              <?php
                $from = $b['starts_at'] ? date('j M Y', strtotime($b['starts_at'])) : 'now';
                $to   = $b['ends_at']   ? date('j M Y', strtotime($b['ends_at']))   : 'no end';
                echo htmlspecialchars("$from → $to");
              ?>
            </div>
          </div>
          <span class="status-badge <?php echo $live ? 'status-delivered' : 'status-cancelled'; ?>" style="font-size:11px;">
            <?php echo $live ? 'Live now' : (empty($b['is_active']) ? 'Off' : 'Scheduled'); ?>
          </span>
          <div style="display:flex;gap:12px;align-items:center;margin-left:auto;">
            <a href="?edit=<?php echo (int)$b['id']; ?>" style="font-size:12.5px;font-weight:600;color:var(--gold);text-decoration:none;">Edit</a>
            <a href="?toggle=<?php echo (int)$b['id']; ?>" style="font-size:12.5px;font-weight:600;color:var(--stone);text-decoration:none;"><?php echo $b['is_active'] ? 'Switch off' : 'Switch on'; ?></a>
            <a href="?delete=<?php echo (int)$b['id']; ?>" onclick="return confirm('Delete this banner?');" style="font-size:12.5px;font-weight:600;color:#EF4444;text-decoration:none;">Delete</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
const PRESETS = <?php echo json_encode(array_map(function($p){
    return ['grad'=>$p['grad'],'text'=>$p['text'],'accent'=>$p['accent'],'onAcc'=>$p['onAcc'],'emoji'=>$p['emoji'],'copy'=>$p['copy']];
}, $presets)); ?>;
const EXISTING_BG = <?php echo json_encode($editing['bg_image'] ?? ''); ?>;
let currentPreset = <?php echo json_encode($fPreset); ?>;

function pickPreset(key) {
  currentPreset = key;
  document.getElementById('preset-input').value = key;
  document.querySelectorAll('.preset-btn').forEach(b => {
    b.style.borderColor = b.dataset.preset === key ? 'var(--gold)' : 'transparent';
  });
  // Fill empty fields with the preset's suggested wording so there's always
  // something sensible in place — never overwrite what the owner already typed.
  const p = PRESETS[key];
  const t = document.getElementById('f-title');
  const s = document.getElementById('f-subtitle');
  const c = document.getElementById('f-cta');
  const e = document.getElementById('f-emoji');
  if (!t.value.trim()) t.value = p.copy[0];
  if (!s.value.trim()) s.value = p.copy[1];
  if (!c.value.trim()) c.value = p.copy[2];
  if (!e.value.trim()) e.value = p.emoji;
  renderPreview();
}

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderPreview() {
  const p = PRESETS[currentPreset] || PRESETS.gold;
  const title = document.getElementById('f-title').value || 'Your headline here';
  const sub   = document.getElementById('f-subtitle').value;
  const cta   = document.getElementById('f-cta').value;
  const emoji = document.getElementById('f-emoji').value;
  const bg = EXISTING_BG
    ? `linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)), url('${EXISTING_BG}') center/cover no-repeat`
    : p.grad;

  document.getElementById('banner-preview').innerHTML = `
    <div style="background:${bg};color:${p.text};padding:38px 34px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;">
      <div style="min-width:220px;">
        <div style="font-family:'Cormorant',serif;font-size:30px;font-weight:700;line-height:1.15;">
          ${emoji ? esc(emoji) + ' ' : ''}${esc(title)}
        </div>
        ${sub ? `<div style="font-size:14px;opacity:0.88;margin-top:8px;max-width:520px;line-height:1.5;">${esc(sub)}</div>` : ''}
      </div>
      ${cta ? `<span style="background:${p.accent};color:${p.onAcc};padding:13px 26px;border-radius:999px;font-size:13px;font-weight:700;white-space:nowrap;">${esc(cta)}</span>` : ''}
    </div>`;
}
renderPreview();
</script>

<?php require_once 'includes/footer.php'; ?>
