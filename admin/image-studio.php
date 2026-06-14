<?php
$pageTitle = "Image Studio";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/image-studio.php';

$templates = getImageTemplates();
$presets   = getModelPresets();
$provider  = imageStudioGetProvider();
$apiKeyConfigured = $provider->isConfigured();
$genCountToday    = imageStudioGenCountToday();

// Build a quick template -> URL lookup for JS
$templatesJs = array_map(function($t) {
    return [
        'id'          => (int)$t['id'],
        'name'        => $t['name'],
        'image_path'  => $t['image_path'],
        'category_id' => $t['category_id'] !== null ? (int)$t['category_id'] : null,
        'is_default'  => (int)$t['is_default'],
    ];
}, $templates);
$presetsJs = array_map(function($p) {
    return [
        'id'   => (int)$p['id'],
        'name' => $p['name'],
        'is_default' => (int)$p['is_default'],
    ];
}, $presets);
?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Image Studio</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Batch Editor</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;max-width:640px;">Drop in up to 10 product photos. The studio removes the original background, drops each piece onto your chosen template, compresses to WebP, and lets you assign the result to a product. Optionally generate a "model wearing the jewellery" shot with AI.</p>
  </div>
  <div style="font-size:11px;color:var(--stone-mid);text-align:right;">
    <?php if ($apiKeyConfigured): ?>
      <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
        <span style="width:8px;height:8px;border-radius:50%;background:#22C55E;"></span>
        AI provider connected
      </div>
      <div style="margin-top:4px;">Model shots generated today: <strong><?php echo $genCountToday; ?></strong></div>
    <?php else: ?>
      <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
        <span style="width:8px;height:8px;border-radius:50%;background:#F59E0B;"></span>
        AI not configured
      </div>
      <div style="margin-top:4px;"><a href="settings.php#image-studio" style="color:var(--gold);font-weight:600;">Add Gemini key →</a></div>
    <?php endif; ?>
  </div>
</div>

<!-- Empty-state warnings -->
<?php if (empty($templates)): ?>
<div class="alert alert-info" style="margin-bottom:16px;">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  No template backgrounds yet. <a href="image-templates.php" style="color:var(--gold);font-weight:600;">Add one first →</a>
</div>
<?php endif; ?>

<!-- Options bar -->
<div class="card" style="padding:20px;margin-bottom:16px;">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;align-items:end;">
    <div class="form-group" style="margin:0;">
      <label class="form-label">Template background</label>
      <select id="opt-template" class="form-input form-select">
        <option value="">— Pick a template —</option>
        <?php foreach ($templates as $t): ?>
        <option value="<?php echo (int)$t['id']; ?>" <?php echo $t['is_default'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($t['name']); ?><?php echo $t['is_default'] ? ' (default)' : ''; ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Model preset (for AI shots)</label>
      <select id="opt-preset" class="form-input form-select">
        <?php foreach ($presets as $p): ?>
        <option value="<?php echo (int)$p['id']; ?>" <?php echo $p['is_default'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($p['name']); ?><?php echo $p['is_default'] ? ' (default)' : ''; ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Output size</label>
      <select id="opt-size" class="form-input form-select">
        <option value="1000">1000 × 1000 (recommended)</option>
        <option value="800">800 × 800</option>
        <option value="1200">1200 × 1200</option>
        <option value="1500">1500 × 1500 (high-res)</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Watermark</label>
      <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--cream-dark);border-radius:8px;cursor:pointer;font-size:13px;">
        <input type="checkbox" id="opt-watermark" style="accent-color:var(--gold);">
        Add Phelyz wordmark
      </label>
    </div>
  </div>
</div>

<!-- Drop zone -->
<div id="studio-dropzone"
     style="border:2.5px dashed var(--cream-dark);border-radius:14px;padding:48px 24px;text-align:center;cursor:pointer;background:var(--cream);transition:border-color 200ms,background 200ms;margin-bottom:20px;"
     ondragover="event.preventDefault();this.style.borderColor='var(--gold)';this.style.background='rgba(202,138,4,0.04)';"
     ondragleave="this.style.borderColor='var(--cream-dark)';this.style.background='var(--cream)';"
     ondrop="studioHandleDrop(event)"
     onclick="document.getElementById('studio-input').click()">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="currentColor"
       style="width:56px;height:56px;color:var(--stone-mid);margin:0 auto 12px;">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
  </svg>
  <p style="font-size:16px;font-weight:700;color:var(--black);margin:0 0 4px;">Drop up to 10 product photos here</p>
  <p style="font-size:12px;color:var(--stone-mid);margin:0;">or click to browse · PNG / JPG / WebP · works best on a plain or busy background — the studio removes it for you</p>
  <input type="file" id="studio-input" accept="image/*" multiple style="display:none;" onchange="studioHandleFiles(this.files)">
</div>

<!-- Processing queue -->
<div id="studio-queue" style="display:none;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
    <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Processing queue</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button type="button" onclick="studioProcessAll()" id="btn-process-all" class="btn btn-gold btn-sm">Process all</button>
      <button type="button" onclick="studioReset()" class="btn btn-outline btn-sm">Clear queue</button>
    </div>
  </div>
  <div id="studio-items" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;"></div>
</div>

<!-- Hidden raw input for drag-drop fallback -->
<canvas id="studio-canvas" style="display:none;"></canvas>

<!-- Templates + presets data for JS -->
<script>
window.STUDIO_TEMPLATES = <?php echo json_encode($templatesJs); ?>;
window.STUDIO_PRESETS   = <?php echo json_encode($presetsJs); ?>;
window.STUDIO_PROVIDER_READY = <?php echo $apiKeyConfigured ? 'true' : 'false'; ?>;
window.STUDIO_BASE_URL  = '<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>';
</script>

<!-- @imgly/background-removal — runs locally in browser, no API key -->
<script type="module">
import { removeBackground } from "https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.5.5/+esm";
window.studioRemoveBackground = removeBackground;
window.dispatchEvent(new Event('studio-bg-ready'));
</script>

<script src="<?php echo (defined('SITE_URL') ? SITE_URL : ''); ?>/assets/js/image-studio.js?v=1"></script>

<?php require_once 'includes/footer.php'; ?>
