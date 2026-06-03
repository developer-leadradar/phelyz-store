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
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Category Management</h1>
    <button onclick="showAddForm()" class="btn btn-primary">➕ Add Category</button>
</div>

<!-- Add Category Form (Hidden by default) -->
<div id="addCategoryForm" class="form-section" style="display: none;">
    <h3>Add New Category</h3>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="0">
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        
        <div class="form-group checkbox-group">
            <label>
                <input type="checkbox" name="is_active" checked>
                <span>Active</span>
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            <button type="button" onclick="hideAddForm()" class="btn btn-outline">Cancel</button>
        </div>
    </form>
</div>

<!-- Categories Table -->
<div class="table-responsive">
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
                    <td><?php echo $category['display_order']; ?></td>
                    <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                    <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                    <td><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?><?php echo strlen($category['description']) > 50 ? '...' : ''; ?></td>
                    <td><?php echo $productCount; ?> products</td>
                    <td>
                        <?php if ($category['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button onclick="editCategory(<?php echo $category['id']; ?>)" class="btn btn-sm btn-secondary">Edit</button>
                        <a href="?delete=<?php echo $category['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Delete this category?')">Delete</a>
                    </td>
                </tr>
                
                <!-- Edit Form (Hidden) -->
                <tr id="edit-form-<?php echo $category['id']; ?>" style="display: none;">
                    <td colspan="7">
                        <form method="POST" class="edit-category-form">
                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Category Name *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Display Order</label>
                                    <input type="number" name="display_order" value="<?php echo $category['display_order']; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="2"><?php echo htmlspecialchars($category['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="is_active" <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                                    <span>Active</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_category" class="btn btn-primary">Update</button>
                                <button type="button" onclick="cancelEdit(<?php echo $category['id']; ?>)" class="btn btn-outline">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function showAddForm() {
    document.getElementById('addCategoryForm').style.display = 'block';
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