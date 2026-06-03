<?php
$pageTitle = "Categories";
require_once 'includes/header.php';

$db = getDB();
$error = '';
$success = '';

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $slug = generateSlug($name);
    $description = sanitize($_POST['description']);
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        $categoryData = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'display_order' => (int)$_POST['display_order']
        ];
        
        $inserted = $db->insert('categories', $categoryData);
        
        if ($inserted) {
            $success = 'Category added successfully';
        } else {
            $error = 'Failed to add category';
        }
    }
}

// Handle update category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $catId = (int)$_POST['category_id'];
    $name = sanitize($_POST['name']);
    $slug = generateSlug($name);
    $description = sanitize($_POST['description']);
    
    $updateData = [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'display_order' => (int)$_POST['display_order']
    ];
    
    $updated = $db->update('categories', $updateData, 'id = ?', [$catId]);
    
    if ($updated !== false) {
        $success = 'Category updated successfully';
    } else {
        $error = 'Failed to update category';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $catId = (int)$_GET['delete'];
    
    // Check if category has products
    $hasProducts = $db->fetchOne(
        "SELECT COUNT(*) as total FROM products WHERE category_id = ?",
        [$catId]
    )['total'];
    
    if ($hasProducts > 0) {
        $error = 'Cannot delete category with products. Please delete or move products first.';
    } else {
        $deleted = $db->delete('categories', 'id = ?', [$catId]);
        if ($deleted) {
            $success = 'Category deleted successfully';
        }
    }
}

// Get all categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY display_order ASC, name ASC");
?>

<?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:20px;"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <h2 style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0;">Category Management</h2>
    <button onclick="showAddForm()" class="btn btn-gold">+ Add Category</button>
</div>

<!-- Add Category Form -->
<div id="addCategoryForm" class="card" style="display:none;padding:24px;margin-bottom:20px;">
    <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 20px;">Add New Category</h3>
    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Category Name <span style="color:#EF4444;">*</span></label>
                <input type="text" name="name" required class="form-input" placeholder="e.g., Rings">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" value="0" class="form-input">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" rows="2" class="form-input" style="resize:vertical;"></textarea>
        </div>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:20px;">
            <input type="checkbox" name="is_active" checked style="width:15px;height:15px;accent-color:var(--gold);">
            <span style="font-size:13px;font-weight:600;color:var(--black);">Active</span>
        </label>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" name="add_category" class="btn btn-gold">Add Category</button>
            <button type="button" onclick="hideAddForm()" class="btn btn-outline">Cancel</button>
        </div>
    </form>
</div>

<!-- Categories Table -->
<div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <?php
                    $productCount = $db->fetchOne(
                        "SELECT COUNT(*) as total FROM products WHERE category_id = ?",
                        [$category['id']]
                    )['total'] ?? 0;
                    ?>
                    <tr id="category-<?php echo $category['id']; ?>">
                        <td style="color:var(--stone-mid);"><?php echo $category['display_order']; ?></td>
                        <td style="font-weight:700;color:var(--black);"><?php echo htmlspecialchars($category['name']); ?></td>
                        <td style="font-size:12px;color:var(--stone-mid);font-family:monospace;"><?php echo htmlspecialchars($category['slug']); ?></td>
                        <td style="font-size:12px;color:var(--stone-mid);"><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 50)); ?><?php echo strlen($category['description'] ?? '') > 50 ? '…' : ''; ?></td>
                        <td style="font-size:13px;"><?php echo $productCount; ?></td>
                        <td>
                            <?php if ($category['is_active']): ?>
                                <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;background:#F0FDF4;color:#166534;">Active</span>
                            <?php else: ?>
                                <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;background:#FEF2F2;color:#991B1B;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button onclick="editCategory(<?php echo $category['id']; ?>)" class="btn btn-outline btn-sm">Edit</button>
                                <a href="?delete=<?php echo $category['id']; ?>"
                                   class="btn btn-sm" style="color:#EF4444;border:1.5px solid #FECACA;"
                                   onclick="return confirm('Delete this category? This cannot be undone.')">Delete</a>
                            </div>
                        </td>
                    </tr>

                    <!-- Inline Edit Form -->
                    <tr id="edit-form-<?php echo $category['id']; ?>" style="display:none;">
                        <td colspan="7" style="padding:16px 20px;background:var(--cream);">
                            <form method="POST" style="display:flex;flex-direction:column;gap:14px;">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-row-2col">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Category Name <span style="color:#EF4444;">*</span></label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required class="form-input">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" name="display_order" value="<?php echo $category['display_order']; ?>" class="form-input">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" rows="2" class="form-input" style="resize:vertical;"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                </div>
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                    <input type="checkbox" name="is_active" <?php echo $category['is_active'] ? 'checked' : ''; ?> style="width:15px;height:15px;accent-color:var(--gold);">
                                    <span style="font-size:13px;font-weight:600;color:var(--black);">Active</span>
                                </label>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                    <button type="submit" name="update_category" class="btn btn-gold btn-sm">Update</button>
                                    <button type="button" onclick="cancelEdit(<?php echo $category['id']; ?>)" class="btn btn-outline btn-sm">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media (max-width: 640px) {
    .form-row-2col { grid-template-columns: 1fr !important; }
}
</style>

<script>
function showAddForm() {
    document.getElementById('addCategoryForm').style.display = 'block';
    document.getElementById('addCategoryForm').scrollIntoView({behavior:'smooth',block:'start'});
}
function hideAddForm() {
    document.getElementById('addCategoryForm').style.display = 'none';
}
function editCategory(id) {
    document.getElementById('category-' + id).style.display = 'none';
    document.getElementById('edit-form-' + id).style.display = 'table-row';
}
function cancelEdit(id) {
    document.getElementById('category-' + id).style.display = 'table-row';
    document.getElementById('edit-form-' + id).style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>