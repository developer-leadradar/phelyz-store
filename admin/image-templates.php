<?php
$pageTitle = "Image Templates";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/image-studio.php';

$db = getDB();
$success = '';
$error   = '';

$categories = function_exists('getAllCategories') ? getAllCategories(false) : [];

// ── Delete ──────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try { $db->query("DELETE FROM image_templates WHERE id = ?", [$id]); $success = 'Template deleted.'; }
    catch (Exception $e) { $error = 'Could not delete template.'; }
}

// ── Set default ─────────────────────────────────────────────────────────────
if (isset($_GET['set_default'])) {
    $id = (int)$_GET['set_default'];
    try {
        $db->query("UPDATE image_templates SET is_default = 0");
        $db->update('image_templates', ['is_default' => 1], 'id = ?', [$id]);
        $success = 'Default template updated.';
    } catch (Exception $e) { $error = 'Could not set default.'; }
}

// ── Create ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name        = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $isDefault   = isset($_POST['is_default']) ? 1 : 0;

    if (empty($name) || !isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        $error = 'Name and an image file are required.';
    } else {
        $uploaded = uploadImage($_FILES['image'], 'templates');
        if (!$uploaded) {
            $error = 'Could not upload template image. Use PNG, JPG, or WebP under 5MB.';
        } else {
            try {
                if ($isDefault) $db->query("UPDATE image_templates SET is_default = 0");
                $db->insert('image_templates', [
                    'name'        => $name,
                    'image_path'  => $uploaded,
                    'description' => $description ?: null,
                    'category_id' => $categoryId,
                    'is_default'  => $isDefault,
                ]);
                $success = 'Template added.';
            } catch (Exception $e) {
                $error = 'Database error — make sure the image_templates table exists. Run migrations/add_image_studio.sql.';
            }
        }
    }
}

$templates = getImageTemplates();
?>

<?php if ($error): ?>
<div class="alert alert-error" style="margin-bottom:24px;">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
  <?php echo $error; ?>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:24px;">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  <?php echo $success; ?>
</div>
<?php endif; ?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Image Studio</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Template Backgrounds</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;max-width:620px;">Upload your brand's backdrop images once and reuse them across every product shoot. Mark one as default, and optionally pin templates to specific categories.</p>
  </div>
</div>

<!-- New template card -->
<div class="card" style="padding:24px;margin-bottom:24px;">
  <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 16px;">Add a new template</h3>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="form-row-2col">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Name *</label>
        <input type="text" name="name" required placeholder="e.g., Marble Slab" class="form-input">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Pin to category</label>
        <select name="category_id" class="form-input form-select">
          <option value="">— Any category —</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Description (optional)</label>
      <input type="text" name="description" placeholder="Soft marble texture, neutral grey tones" class="form-input">
    </div>
    <div class="form-group">
      <label class="form-label">Template image *</label>
      <input type="file" name="image" accept="image/*" required class="form-input">
      <p class="form-hint">PNG, JPG, or WebP · 800×800px or larger · max 5MB.</p>
    </div>
    <div class="form-group">
      <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--black);">
        <input type="checkbox" name="is_default" value="1" style="accent-color:var(--gold);">
        <span>Set as default template</span>
      </label>
    </div>
    <button type="submit" class="btn btn-gold">+ Add Template</button>
  </form>
</div>

<!-- Existing templates -->
<div class="card" style="padding:24px;">
  <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 16px;">Your templates (<?php echo count($templates); ?>)</h3>
  <?php if (empty($templates)): ?>
    <p style="color:var(--stone-mid);font-size:14px;margin:0;">No templates yet. Add one above to get started.</p>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
      <?php foreach ($templates as $t):
        $catName = '';
        if (!empty($t['category_id'])) {
          foreach ($categories as $c) if ((int)$c['id'] === (int)$t['category_id']) $catName = $c['name'];
        }
      ?>
      <div style="border:1px solid var(--cream-dark);border-radius:12px;overflow:hidden;background:white;">
        <div style="position:relative;">
          <img src="<?php echo htmlspecialchars($t['image_path']); ?>" alt=""
               style="width:100%;height:160px;object-fit:cover;display:block;"
               onerror="this.src='https://placehold.co/220x160/F5F5F4/78716C?text=Template'">
          <?php if ((int)$t['is_default'] === 1): ?>
          <span style="position:absolute;top:8px;left:8px;background:var(--gold);color:white;font-size:10px;font-weight:700;padding:4px 10px;border-radius:99px;text-transform:uppercase;letter-spacing:0.06em;">Default</span>
          <?php endif; ?>
        </div>
        <div style="padding:12px 14px;">
          <div style="font-size:14px;font-weight:700;color:var(--black);margin-bottom:2px;"><?php echo htmlspecialchars($t['name']); ?></div>
          <?php if ($catName): ?>
            <div style="font-size:11px;color:var(--stone-mid);margin-bottom:4px;"><?php echo htmlspecialchars($catName); ?></div>
          <?php endif; ?>
          <?php if (!empty($t['description'])): ?>
            <div style="font-size:12px;color:var(--stone-mid);line-height:1.4;"><?php echo htmlspecialchars($t['description']); ?></div>
          <?php endif; ?>
          <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;">
            <?php if ((int)$t['is_default'] !== 1): ?>
            <a href="?set_default=<?php echo (int)$t['id']; ?>" class="btn btn-outline btn-sm" style="font-size:11px;padding:6px 10px;">Set default</a>
            <?php endif; ?>
            <a href="?delete=<?php echo (int)$t['id']; ?>"
               onclick="return confirm('Delete this template?');"
               style="color:#EF4444;font-size:12px;font-weight:600;padding:6px 4px;text-decoration:none;">Delete</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
