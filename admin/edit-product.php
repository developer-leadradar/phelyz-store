<?php
$pageTitle = "Edit Product";
require_once 'includes/header.php';

$db = getDB();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    redirect('products.php');
}

$product = getProductById($productId);

if (!$product) {
    redirect('products.php');
}

$categories = getAllCategories(false);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $sku = sanitize($_POST['sku']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock_quantity'];
    $categoryId = (int)$_POST['category_id'];

    if (empty($name) || empty($sku) || $price <= 0 || !$categoryId) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if SKU exists for other products
        $existingSku = $db->fetchOne("SELECT id FROM products WHERE sku = ? AND id != ?", [$sku, $productId]);
        if ($existingSku) {
            $error = 'SKU already exists for another product';
        } else {
            // Handle image upload
            $imagePath = $product['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploaded = uploadImage($_FILES['image'], 'products');
                if ($uploaded) {
                    $imagePath = $uploaded;
                }
            }

            // Prepare update data
            $updateData = [
                'name' => $name,
                'slug' => generateSlug($name),
                'description' => sanitize($_POST['description']),
                'category_id' => $categoryId,
                'material' => sanitize($_POST['material']) ?: null,
                'metal_purity' => sanitize($_POST['metal_purity']) ?: null,
                'stone_type' => sanitize($_POST['stone_type']) ?: 'None',
                'stone_weight' => (float)($_POST['stone_weight'] ?: 0),
                'brand' => sanitize($_POST['brand']) ?: null,
                'price' => $price,
                'compare_price' => (float)($_POST['compare_price'] ?: 0),
                'stock_quantity' => $stock,
                'sku' => $sku,
                'image' => $imagePath,
                'weight' => (float)($_POST['weight'] ?: 0),
                'dimensions' => sanitize($_POST['dimensions']) ?: null,
                'gender' => sanitize($_POST['gender']) ?: 'Unisex',
                'style' => sanitize($_POST['style']) ?: null,
                'occasion' => sanitize($_POST['occasion']) ?: null,
                'stock_status' => in_array($_POST['stock_status'] ?? '', ['available','express','out_of_stock']) ? $_POST['stock_status'] : 'available',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0
            ];

            $updated = $db->update('products', $updateData, 'id = ?', [$productId]);

            if ($updated !== false) {
                $success = 'Product updated successfully!';
                $product = getProductById($productId); // Refresh data
            } else {
                $error = 'Failed to update product';
            }
        }
    }
}
?>

<?php if ($error): ?>
<div class="alert alert-error" style="margin-bottom:24px;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:24px;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?php echo $success; ?>
</div>
<?php endif; ?>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Catalog</div>
        <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0;">Edit Product</h2>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $productId; ?>" target="_blank" class="btn btn-outline" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
            View in Store
        </a>
        <a href="products.php" class="btn btn-outline" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Back to Products
        </a>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="edit-product-form">

    <!-- ── Section 1: Basic Information ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Basic Information</h3>
        </div>

        <!-- Name + SKU -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_name">Product Name <span style="color:#EF4444;">*</span></label>
                <input type="text" id="edit_name" name="name" required class="form-input"
                       value="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_sku">SKU <span style="color:#EF4444;">*</span></label>
                <input type="text" id="edit_sku" name="sku" required class="form-input"
                       value="<?php echo htmlspecialchars($product['sku']); ?>">
            </div>
        </div>

        <!-- Description -->
        <div class="form-group" style="margin-top:20px;">
            <label class="form-label" for="edit_desc">Description <span style="color:#EF4444;">*</span></label>
            <textarea id="edit_desc" name="description" rows="5" required
                      class="form-input" style="resize:vertical;min-height:120px;"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <!-- Category -->
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="edit_cat">Category <span style="color:#EF4444;">*</span></label>
            <select id="edit_cat" name="category_id" required class="form-input form-select">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Checkboxes -->
        <div style="display:flex;gap:32px;margin-top:20px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="is_featured"
                       <?php echo $product['is_featured'] ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Featured Product</span>
                <span style="font-size:12px;color:var(--stone-mid);">(Show on homepage)</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="is_active"
                       <?php echo $product['is_active'] ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Active</span>
                <span style="font-size:12px;color:var(--stone-mid);">(Visible in store)</span>
            </label>
        </div>
    </div>

    <!-- ── Section 2: Pricing & Inventory ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Pricing &amp; Inventory</h3>
        </div>

        <!-- Price + Compare Price -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_price">Price (₦) <span style="color:#EF4444;">*</span></label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;font-weight:600;color:var(--stone-mid);">₦</span>
                    <input type="number" id="edit_price" name="price" required step="0.01" min="0"
                           class="form-input" style="padding-left:30px;"
                           value="<?php echo $product['price']; ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_compare">Compare Price (₦)</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;font-weight:600;color:var(--stone-mid);">₦</span>
                    <input type="number" id="edit_compare" name="compare_price" step="0.01" min="0"
                           class="form-input" style="padding-left:30px;"
                           value="<?php echo $product['compare_price']; ?>">
                </div>
                <p class="form-hint">Original price before discount (optional)</p>
            </div>
        </div>

        <!-- Stock Quantity -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_stock">Stock Quantity <span style="color:#EF4444;">*</span></label>
                <input type="number" id="edit_stock" name="stock_quantity" required min="0"
                       class="form-input"
                       value="<?php echo $product['stock_quantity']; ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_low_stock">Low Stock Threshold</label>
                <input type="number" id="edit_low_stock" name="low_stock_threshold" min="0" placeholder="5"
                       class="form-input"
                       value="<?php echo isset($product['low_stock_threshold']) ? $product['low_stock_threshold'] : ''; ?>">
                <p class="form-hint">Alert when stock falls below this</p>
            </div>
        </div>

        <!-- Stock Status -->
        <div style="margin-top:20px;">
            <label class="form-label">Stock Status <span style="color:#EF4444;">*</span></label>
            <p class="form-hint" style="margin-bottom:12px;">Controls what customers see on the product page and whether they can purchase.</p>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;" class="form-row-3col">
                <?php
                $statusOptions = [
                    'available'    => ['label'=>'Available','sub'=>'In stock, ships promptly','dot'=>'#22C55E'],
                    'express'      => ['label'=>'Express','sub'=>'Pre-order, longer shipping','dot'=>'#F59E0B'],
                    'out_of_stock' => ['label'=>'Out of Stock','sub'=>'Not available to buy','dot'=>'#EF4444'],
                ];
                $selectedStatus = $product['stock_status'] ?? 'available';
                foreach ($statusOptions as $val => $opt): ?>
                <label style="display:flex;align-items:flex-start;gap:10px;padding:14px;border:1.5px solid <?php echo $selectedStatus===$val?'var(--gold)':'var(--cream-dark)'; ?>;border-radius:10px;cursor:pointer;transition:border-color 0.15s;" class="status-label">
                    <input type="radio" name="stock_status" value="<?php echo $val; ?>"
                           <?php echo $selectedStatus===$val?'checked':''; ?>
                           onchange="highlightStatus()"
                           style="accent-color:var(--gold);margin-top:2px;flex-shrink:0;">
                    <div>
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $opt['dot']; ?>;flex-shrink:0;"></span>
                            <span style="font-size:13px;font-weight:700;color:var(--black);"><?php echo $opt['label']; ?></span>
                        </div>
                        <span style="font-size:11px;color:var(--stone-mid);"><?php echo $opt['sub']; ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Section 3: Product Details ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Product Details</h3>
        </div>

        <!-- Material + Metal Purity -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_material">Material</label>
                <select id="edit_material" name="material" class="form-input form-select">
                    <option value="">Select Material</option>
                    <?php foreach (['Gold','Platinum','Silver','Rose Gold','White Gold','Titanium','Stainless Steel'] as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo $product['material'] == $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_purity">Metal Purity</label>
                <select id="edit_purity" name="metal_purity" class="form-input form-select">
                    <option value="">Select Purity</option>
                    <?php
                    $purities = ['10K' => '10K','14K' => '14K','18K' => '18K','22K' => '22K','24K' => '24K','950' => '950 Platinum','925' => '925 Silver','N/A' => 'N/A'];
                    foreach ($purities as $pVal => $pLabel): ?>
                        <option value="<?php echo $pVal; ?>" <?php echo $product['metal_purity'] == $pVal ? 'selected' : ''; ?>><?php echo $pLabel; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Stone Type + Stone Weight -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_stone">Stone Type</label>
                <select id="edit_stone" name="stone_type" class="form-input form-select">
                    <?php foreach (['None','Diamond','Ruby','Emerald','Sapphire','Pearl','Topaz','Amethyst'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $product['stone_type'] == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_stone_w">Stone Weight (Carats)</label>
                <input type="number" id="edit_stone_w" name="stone_weight" step="0.01" min="0"
                       class="form-input"
                       value="<?php echo $product['stone_weight']; ?>">
            </div>
        </div>

        <!-- Brand + Weight -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_brand">Brand</label>
                <input type="text" id="edit_brand" name="brand" class="form-input"
                       value="<?php echo htmlspecialchars($product['brand']); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_weight">Weight (g)</label>
                <input type="number" id="edit_weight" name="weight" step="0.01" min="0"
                       class="form-input"
                       value="<?php echo $product['weight']; ?>">
            </div>
        </div>

        <!-- Gender + Style -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_gender">Gender</label>
                <select id="edit_gender" name="gender" class="form-input form-select">
                    <?php foreach (['Unisex','Men','Women'] as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo $product['gender'] == $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="edit_style">Style</label>
                <input type="text" id="edit_style" name="style" placeholder="e.g., Classic, Modern, Vintage"
                       class="form-input"
                       value="<?php echo htmlspecialchars($product['style']); ?>">
            </div>
        </div>

        <!-- Occasion -->
        <div class="form-group" style="margin-top:20px;margin-bottom:0;">
            <label class="form-label" for="edit_occasion">Occasion</label>
            <input type="text" id="edit_occasion" name="occasion" placeholder="e.g., Engagement, Wedding, Anniversary"
                   class="form-input"
                   value="<?php echo htmlspecialchars($product['occasion']); ?>">
        </div>
    </div>

    <!-- ── Section 4: Product Image ── -->
    <div class="card" style="padding:28px;margin-bottom:28px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Product Image</h3>
        </div>

        <!-- Current image -->
        <?php if (!empty($product['image'])): ?>
        <div style="margin-bottom:20px;">
            <p class="form-label" style="margin-bottom:10px;">Current Image</p>
            <div style="display:inline-block;position:relative;">
                <img src="../<?php echo htmlspecialchars($product['image']); ?>"
                     alt="Current product image"
                     style="width:140px;height:140px;object-fit:cover;border-radius:10px;border:1px solid var(--cream-dark);display:block;">
                <div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);background:var(--black);color:white;font-size:10px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:3px 8px;border-radius:4px;white-space:nowrap;">Current</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upload new image -->
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Upload New Image <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(optional — leave empty to keep current)</span></label>

            <div id="drop-zone"
                 onclick="document.getElementById('edit_product_image').click()"
                 style="border:2px dashed var(--cream-dark);border-radius:12px;padding:32px 24px;text-align:center;cursor:pointer;transition:border-color 200ms,background 200ms;background:var(--cream);"
                 ondragover="event.preventDefault();this.style.borderColor='var(--gold)';this.style.background='rgba(202,138,4,0.04)';"
                 ondragleave="this.style.borderColor='var(--cream-dark)';this.style.background='var(--cream)';"
                 ondrop="handleDrop(event)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     style="width:36px;height:36px;color:var(--stone-mid);margin:0 auto 10px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--black);margin:0 0 4px;">Drag &amp; drop new image here</p>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">or click to browse &mdash; PNG, JPG, WebP &middot; Max 5MB</p>
            </div>
            <input type="file" id="edit_product_image" name="image" accept="image/*"
                   style="display:none;" onchange="previewImage(this)">

            <!-- New image preview -->
            <div id="image-preview-container" style="margin-top:16px;display:none;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--stone-mid);margin-bottom:8px;">New Image Preview</p>
                <div style="position:relative;display:inline-block;">
                    <img id="image-preview" src="" alt="New preview"
                         style="width:140px;height:140px;object-fit:cover;border-radius:10px;border:2px solid var(--gold);display:block;">
                    <button type="button" onclick="clearImage()"
                            style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;background:#EF4444;border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:13px;height:13px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Form Actions ── -->
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-gold">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            Save Changes
        </button>
        <a href="products.php" class="btn btn-outline">Cancel</a>
    </div>

</form>

<style>
@media (max-width: 640px) {
    .form-row-2col { grid-template-columns: 1fr !important; }
    .form-row-3col { grid-template-columns: 1fr !important; }
}
</style>

<script>
function highlightStatus() {
    document.querySelectorAll('.status-label').forEach(function(label) {
        var radio = label.querySelector('input[type="radio"]');
        label.style.borderColor = radio.checked ? 'var(--gold)' : 'var(--cream-dark)';
    });
}

function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const container = document.getElementById('image-preview-container');
    const dropZone = document.getElementById('drop-zone');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
            dropZone.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('edit_product_image').value = '';
    document.getElementById('image-preview-container').style.display = 'none';
    document.getElementById('drop-zone').style.display = 'block';
}

function handleDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('drop-zone');
    dz.style.borderColor = 'var(--cream-dark)';
    dz.style.background = 'var(--cream)';
    const files = e.dataTransfer.files;
    if (files && files[0]) {
        const input = document.getElementById('edit_product_image');
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        input.files = dt.files;
        previewImage(input);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
