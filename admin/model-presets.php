<?php
$pageTitle = "Model Presets";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/image-studio.php';

$db = getDB();
$success = '';
$error   = '';

$skinTones = ['light brown', 'medium brown', 'deep brown', 'rich ebony', 'warm tan'];
$genders   = ['female', 'male', 'unisex'];
$ageRanges = ['18-24', '25-32', '30-40', '40+'];
$poseStyles= ['elegant', 'editorial', 'candid', 'commercial', 'high-fashion'];
$lights    = ['soft studio', 'warm natural', 'cool studio', 'dramatic side-lit', 'high-key bright'];

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try { $db->query("DELETE FROM model_presets WHERE id = ?", [$id]); $success = 'Preset deleted.'; }
    catch (Exception $e) { $error = 'Could not delete preset.'; }
}

// Set default
if (isset($_GET['set_default'])) {
    $id = (int)$_GET['set_default'];
    try {
        $db->query("UPDATE model_presets SET is_default = 0");
        $db->update('model_presets', ['is_default' => 1], 'id = ?', [$id]);
        $success = 'Default preset updated.';
    } catch (Exception $e) { $error = 'Could not set default.'; }
}

// Create / update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name         = sanitize($_POST['name'] ?? '');
    $skin         = sanitize($_POST['skin_tone'] ?? 'medium brown');
    $gender       = sanitize($_POST['gender'] ?? 'female');
    $ageRange     = sanitize($_POST['age_range'] ?? '25-35');
    $pose         = sanitize($_POST['pose_style'] ?? 'elegant');
    $light        = sanitize($_POST['lighting_mood'] ?? 'soft studio');
    $extra        = sanitize($_POST['extra_prompt'] ?? '');
    $isDefault    = isset($_POST['is_default']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Preset name is required.';
    } else {
        try {
            if ($isDefault) $db->query("UPDATE model_presets SET is_default = 0");
            $data = [
                'name'          => $name,
                'skin_tone'     => $skin,
                'gender'        => $gender,
                'age_range'     => $ageRange,
                'pose_style'    => $pose,
                'lighting_mood' => $light,
                'extra_prompt'  => $extra ?: null,
                'is_default'    => $isDefault,
            ];
            if ($id > 0) {
                $db->update('model_presets', $data, 'id = ?', [$id]);
                $success = 'Preset updated.';
            } else {
                $db->insert('model_presets', $data);
                $success = 'Preset added.';
            }
        } catch (Exception $e) {
            $error = 'Database error — make sure the model_presets table exists. Run migrations/add_image_studio.sql.';
        }
    }
}

$presets = getModelPresets();
$editing = null;
if (isset($_GET['edit'])) {
    foreach ($presets as $p) if ((int)$p['id'] === (int)$_GET['edit']) { $editing = $p; break; }
}
?>

<?php if ($error): ?>
<div class="alert alert-error" style="margin-bottom:24px;"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:24px;"><?php echo $success; ?></div>
<?php endif; ?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Image Studio</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Model Presets</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;max-width:640px;">Save reusable "personas" so every model-shot generation follows your brand's chosen look. The default preset is used automatically when you click "Generate model shot" on a product photo.</p>
  </div>
</div>

<!-- Add / edit form -->
<div class="card" style="padding:24px;margin-bottom:24px;">
  <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 16px;">
    <?php echo $editing ? 'Edit preset' : 'Add a new preset'; ?>
  </h3>
  <form method="POST">
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
    <?php endif; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-row-2col">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Preset name *</label>
        <input type="text" name="name" required placeholder="e.g., Amaka — Editorial"
               value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" class="form-input">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-input form-select">
          <?php foreach ($genders as $g): ?>
          <option value="<?php echo $g; ?>" <?php echo ($editing['gender'] ?? 'female') === $g ? 'selected' : ''; ?>><?php echo ucfirst($g); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;" class="form-row-2col">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Skin tone</label>
        <select name="skin_tone" class="form-input form-select">
          <?php foreach ($skinTones as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo ($editing['skin_tone'] ?? 'medium brown') === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Age range</label>
        <select name="age_range" class="form-input form-select">
          <?php foreach ($ageRanges as $a): ?>
          <option value="<?php echo $a; ?>" <?php echo ($editing['age_range'] ?? '25-32') === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;" class="form-row-2col">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Pose style</label>
        <select name="pose_style" class="form-input form-select">
          <?php foreach ($poseStyles as $p): ?>
          <option value="<?php echo $p; ?>" <?php echo ($editing['pose_style'] ?? 'elegant') === $p ? 'selected' : ''; ?>><?php echo ucfirst($p); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Lighting mood</label>
        <select name="lighting_mood" class="form-input form-select">
          <?php foreach ($lights as $l): ?>
          <option value="<?php echo $l; ?>" <?php echo ($editing['lighting_mood'] ?? 'soft studio') === $l ? 'selected' : ''; ?>><?php echo ucfirst($l); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">Extra prompt details (optional)</label>
      <textarea name="extra_prompt" rows="2" placeholder="e.g., graceful hands and neckline, subtle smile, luxury jewellery model"
                class="form-input" style="resize:vertical;"><?php echo htmlspecialchars($editing['extra_prompt'] ?? ''); ?></textarea>
    </div>

    <div class="form-group" style="margin-top:8px;">
      <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--black);">
        <input type="checkbox" name="is_default" value="1" <?php echo ((int)($editing['is_default'] ?? 0) === 1) ? 'checked' : ''; ?> style="accent-color:var(--gold);">
        <span>Set as default preset</span>
      </label>
    </div>

    <div style="display:flex;gap:10px;margin-top:8px;">
      <button type="submit" class="btn btn-gold"><?php echo $editing ? 'Save Changes' : '+ Add Preset'; ?></button>
      <?php if ($editing): ?>
        <a href="model-presets.php" class="btn btn-outline">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- List -->
<div class="card" style="padding:24px;">
  <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 16px;">Your presets (<?php echo count($presets); ?>)</h3>
  <?php if (empty($presets)): ?>
    <p style="color:var(--stone-mid);font-size:14px;margin:0;">No presets yet. Add one above.</p>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
      <?php foreach ($presets as $p): ?>
      <div style="border:1px solid var(--cream-dark);border-radius:10px;padding:14px 16px;background:white;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <div style="font-size:14px;font-weight:700;color:var(--black);"><?php echo htmlspecialchars($p['name']); ?></div>
          <?php if ((int)$p['is_default'] === 1): ?>
          <span style="background:var(--gold);color:white;font-size:9px;font-weight:700;padding:3px 8px;border-radius:99px;text-transform:uppercase;letter-spacing:0.06em;">Default</span>
          <?php endif; ?>
        </div>
        <div style="font-size:12px;color:var(--stone-mid);line-height:1.6;">
          <?php echo htmlspecialchars(ucfirst($p['skin_tone'])); ?> · <?php echo htmlspecialchars($p['gender']); ?> · <?php echo htmlspecialchars($p['age_range']); ?><br>
          <?php echo htmlspecialchars(ucfirst($p['pose_style'])); ?> · <?php echo htmlspecialchars(ucfirst($p['lighting_mood'])); ?>
        </div>
        <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;">
          <a href="?edit=<?php echo (int)$p['id']; ?>" style="color:var(--gold);font-size:12px;font-weight:600;text-decoration:none;">Edit</a>
          <?php if ((int)$p['is_default'] !== 1): ?>
          <a href="?set_default=<?php echo (int)$p['id']; ?>" style="color:var(--stone);font-size:12px;font-weight:600;text-decoration:none;">Set default</a>
          <?php endif; ?>
          <a href="?delete=<?php echo (int)$p['id']; ?>"
             onclick="return confirm('Delete this preset?');"
             style="color:#EF4444;font-size:12px;font-weight:600;text-decoration:none;">Delete</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
