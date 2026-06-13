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
            // Handle delete-existing-gallery-image requests (sent via hidden field)
            $deleteIds = isset($_POST['delete_image_ids']) ? array_filter(array_map('intval', explode(',', $_POST['delete_image_ids']))) : [];
            if (!empty($deleteIds)) {
                try {
                    $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                    $params = array_merge($deleteIds, [$productId]);
                    $db->query("DELETE FROM product_images WHERE id IN ($placeholders) AND product_id = ?", $params);
                } catch (Exception $e) { /* table may not exist */ }
            }

            // Handle image upload(s) — single legacy `image` field + multi `images[]`
            $imagePath = $product['image'];
            $extraImages = [];

            $uploadedFiles = [];
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $count = count($_FILES['images']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['images']['error'][$i] === 0 && $_FILES['images']['size'][$i] > 0) {
                        $uploadedFiles[] = [
                            'name'     => $_FILES['images']['name'][$i],
                            'type'     => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error'    => $_FILES['images']['error'][$i],
                            'size'     => $_FILES['images']['size'][$i],
                        ];
                    }
                }
            }
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0 && $_FILES['image']['size'] > 0) {
                array_unshift($uploadedFiles, $_FILES['image']);
            }

            $replacePrimary = !empty($_POST['replace_primary']) && !empty($uploadedFiles);
            foreach ($uploadedFiles as $idx => $f) {
                $uploaded = uploadImage($f, 'products');
                if ($uploaded) {
                    if ($idx === 0 && $replacePrimary) {
                        $imagePath = $uploaded;
                    } else {
                        $extraImages[] = $uploaded;
                    }
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
                'colors' => trim($_POST['colors'] ?? '') ?: null,
                'cod_enabled'  => isset($_POST['pm_cod_override'])  ? (isset($_POST['cod_enabled'])  ? 1 : 0) : null,
                'bank_enabled' => isset($_POST['pm_bank_override']) ? (isset($_POST['bank_enabled']) ? 1 : 0) : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0
            ];

            $updated = $db->update('products', $updateData, 'id = ?', [$productId]);

            if ($updated !== false) {
                // Save new gallery images (if any)
                if (!empty($extraImages)) {
                    try {
                        // Continue numbering from existing
                        $maxSort = $db->fetchOne(
                            "SELECT COALESCE(MAX(sort_order), 0) AS s FROM product_images WHERE product_id = ?",
                            [$productId]
                        );
                        $sort = (int)($maxSort['s'] ?? 0) + 1;
                        foreach ($extraImages as $extraPath) {
                            $db->insert('product_images', [
                                'product_id' => $productId,
                                'image_path' => $extraPath,
                                'sort_order' => $sort++,
                                'is_primary' => 0,
                            ]);
                        }
                    } catch (Exception $e) {
                        error_log('product_images insert failed: ' . $e->getMessage());
                    }
                }
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

        <!-- Colors -->
        <div class="form-group" style="margin-top:20px;margin-bottom:0;">
            <label class="form-label">Available Colors <span style="color:var(--stone-mid);font-weight:400;">(optional)</span></label>
            <p class="form-hint" style="margin-bottom:10px;">Add the colour variants this product comes in. Customers pick one before adding to cart.</p>
            <div id="colors-list" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;min-height:8px;"></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="text" id="color-name-input" placeholder="Colour name (e.g., Rose Gold)"
                       class="form-input" style="flex:1;min-width:180px;">
                <input type="color" id="color-hex-input" value="#CA8A04"
                       style="width:48px;height:42px;border:1.5px solid var(--cream-dark);border-radius:8px;padding:2px;cursor:pointer;background:white;" title="Pick a hex">
                <button type="button" onclick="addColorChip()" class="btn btn-outline" style="padding:10px 18px;font-size:13px;">+ Add</button>
            </div>
            <input type="hidden" name="colors" id="colors-hidden" value="<?php echo htmlspecialchars($product['colors'] ?? ''); ?>">
        </div>

        <!-- Payment Methods Override -->
        <?php
        $pmCod  = $product['cod_enabled']  ?? null;
        $pmBank = $product['bank_enabled'] ?? null;
        $pmOverride = ($pmCod !== null || $pmBank !== null);
        ?>
        <div class="form-group" style="margin-top:20px;margin-bottom:0;">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--black);font-weight:600;">
                <input type="checkbox" name="pm_cod_override" id="pm-override-toggle" <?php echo $pmOverride ? 'checked' : ''; ?>
                       onchange="document.getElementById('pm-override-box').style.display = this.checked ? 'block' : 'none';"
                       style="accent-color:var(--gold);">
                <span>Override default payment methods for this product</span>
            </label>
            <p class="form-hint" style="margin-top:4px;">Leave unchecked to use the per-state defaults set in Settings → Payment Methods.</p>
            <div id="pm-override-box" style="display:<?php echo $pmOverride ? 'block' : 'none'; ?>;margin-top:12px;padding:14px 16px;background:var(--cream);border-radius:8px;border:1px solid var(--cream-dark);">
                <input type="hidden" name="pm_bank_override" value="1">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--stone-mid);margin:0 0 10px;">Allowed for this product</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;color:var(--black);">
                        <input type="checkbox" name="cod_enabled" value="1" <?php echo ($pmCod === null || (int)$pmCod === 1) ? 'checked' : ''; ?>
                               style="accent-color:var(--gold);width:18px;height:18px;">
                        <span><strong>Cash on Delivery</strong> — pay shipping now, balance on arrival</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;color:var(--black);">
                        <input type="checkbox" name="bank_enabled" value="1" <?php echo ($pmBank === null || (int)$pmBank === 1) ? 'checked' : ''; ?>
                               style="accent-color:var(--gold);width:18px;height:18px;">
                        <span><strong>Bank Transfer</strong> — pay full amount upfront</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Section 4: Product Images ── -->
    <div class="card" style="padding:28px;margin-bottom:28px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Product Images</h3>
        </div>

        <!-- Existing gallery -->
        <?php
        $existingExtras = function_exists('getProductImages') ? getProductImages($productId) : [];
        ?>
        <?php if (!empty($product['image']) || !empty($existingExtras)): ?>
        <div style="margin-bottom:24px;">
            <p class="form-label" style="margin-bottom:10px;">Current Gallery <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">— click ✕ on any to remove</span></p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                <?php if (!empty($product['image'])): ?>
                <div style="position:relative;border:1px solid var(--cream-dark);border-radius:10px;overflow:hidden;background:white;">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Primary"
                         style="width:100%;height:120px;object-fit:cover;display:block;"
                         onerror="this.src='https://placehold.co/120x120/F5F5F4/78716C?text=J'">
                    <span style="position:absolute;top:6px;left:6px;background:var(--gold);color:white;font-size:10px;font-weight:700;padding:3px 8px;border-radius:99px;letter-spacing:0.04em;text-transform:uppercase;">Primary</span>
                </div>
                <?php endif; ?>
                <?php foreach ($existingExtras as $img): ?>
                <div id="existing-img-<?php echo (int)$img['id']; ?>"
                     style="position:relative;border:1px solid var(--cream-dark);border-radius:10px;overflow:hidden;background:white;">
                    <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery"
                         style="width:100%;height:120px;object-fit:cover;display:block;"
                         onerror="this.src='https://placehold.co/120x120/F5F5F4/78716C?text=J'">
                    <button type="button" onclick="markImageForDelete(<?php echo (int)$img['id']; ?>)"
                            style="position:absolute;top:6px;right:6px;width:24px;height:24px;background:#EF4444;border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;font-size:14px;line-height:1;" title="Remove from gallery">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="delete_image_ids" id="delete-image-ids" value="">
        </div>
        <?php endif; ?>

        <!-- Replace primary toggle -->
        <div class="form-group" style="margin-bottom:16px;">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--black);">
                <input type="checkbox" name="replace_primary" value="1" style="accent-color:var(--gold);">
                <span>Replace primary image with the first new upload</span>
            </label>
            <p class="form-hint" style="margin-top:4px;">If unchecked, all new uploads are added to the gallery.</p>
        </div>

        <!-- Upload new image(s) -->
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Add Images <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(optional — select multiple)</span></label>

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
                <p style="font-size:14px;font-weight:600;color:var(--black);margin:0 0 4px;">Drag &amp; drop image(s) here</p>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">or click to browse &mdash; PNG, JPG, WebP &middot; Max 5MB each</p>
            </div>
            <input type="file" id="edit_product_image" name="images[]" accept="image/*" multiple
                   style="display:none;" onchange="previewImages(this)">

            <div id="image-preview-container" style="margin-top:16px;display:none;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--stone-mid);margin-bottom:8px;">
                    <span id="image-count-label">New Image</span>
                </p>
                <div id="image-preview-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;"></div>
                <button type="button" onclick="clearImage()" style="margin-top:12px;background:none;border:none;color:#EF4444;font-size:13px;font-weight:600;cursor:pointer;padding:0;">
                    Remove all
                </button>
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

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function previewImages(input) {
    const container = document.getElementById('image-preview-container');
    const grid      = document.getElementById('image-preview-grid');
    const dropZone  = document.getElementById('drop-zone');
    const label     = document.getElementById('image-count-label');

    if (!input.files || !input.files.length) return;
    grid.innerHTML = '';
    const total = input.files.length;
    label.textContent = total === 1
        ? 'New Image'
        : 'New Images (' + total + ')';

    Array.from(input.files).forEach(function(file, idx) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const card = document.createElement('div');
            card.style.cssText = 'position:relative;border:1px solid var(--cream-dark);border-radius:10px;overflow:hidden;background:white;';
            card.innerHTML =
                '<div style="position:relative;">' +
                    '<img src="' + e.target.result + '" alt="Preview ' + (idx+1) + '" style="width:100%;height:140px;object-fit:cover;display:block;">' +
                '</div>' +
                '<div style="padding:8px 10px;">' +
                    '<div style="font-size:12px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + escapeHtmlAttr(file.name) + '">' + escapeHtmlAttr(file.name) + '</div>' +
                    '<div style="font-size:11px;color:var(--stone-mid);">' + formatFileSize(file.size) + '</div>' +
                '</div>';
            grid.appendChild(card);
        };
        reader.readAsDataURL(file);
    });
    container.style.display = 'block';
    dropZone.style.display = 'none';
}

function escapeHtmlAttr(s) {
    return String(s).replace(/[&<>"']/g, function(c){
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
}

function previewImage(input) { previewImages(input); }

function clearImage() {
    document.getElementById('edit_product_image').value = '';
    document.getElementById('image-preview-container').style.display = 'none';
    document.getElementById('image-preview-grid').innerHTML = '';
    document.getElementById('drop-zone').style.display = 'block';
}

/* Mark an existing gallery image for deletion */
var deletedImageIds = [];
function markImageForDelete(id) {
    if (deletedImageIds.indexOf(id) !== -1) return;
    deletedImageIds.push(id);
    document.getElementById('delete-image-ids').value = deletedImageIds.join(',');
    var el = document.getElementById('existing-img-' + id);
    if (el) el.style.display = 'none';
}

/* ── Colors tag input ─────────────────────────── */
var productColors = [];

function syncColorsHidden() {
    document.getElementById('colors-hidden').value =
        productColors.map(c => c.name + (c.hex ? '|' + c.hex : '')).join(',');
}

function renderColorChips() {
    var list = document.getElementById('colors-list');
    if (!productColors.length) { list.innerHTML = ''; syncColorsHidden(); return; }
    list.innerHTML = productColors.map(function(c, i) {
        var hex = c.hex || '#E5E7EB';
        return '<span style="display:inline-flex;align-items:center;gap:8px;padding:6px 10px 6px 8px;border:1px solid var(--cream-dark);border-radius:99px;background:white;font-size:13px;">' +
               '<span style="width:18px;height:18px;border-radius:50%;background:' + hex + ';border:1px solid rgba(0,0,0,0.08);flex-shrink:0;"></span>' +
               '<span style="font-weight:600;color:var(--black);">' + escapeHtml(c.name) + '</span>' +
               '<button type="button" onclick="removeColorChip(' + i + ')" aria-label="Remove" style="background:none;border:none;color:var(--stone-mid);cursor:pointer;padding:0 2px;font-size:16px;line-height:1;">&times;</button>' +
               '</span>';
    }).join('');
    syncColorsHidden();
}

function addColorChip() {
    var nameEl = document.getElementById('color-name-input');
    var hexEl  = document.getElementById('color-hex-input');
    var name = (nameEl.value || '').trim();
    var hex  = (hexEl.value  || '').trim();
    if (!name) { nameEl.focus(); return; }
    if (productColors.some(c => c.name.toLowerCase() === name.toLowerCase())) {
        nameEl.value = '';
        return;
    }
    productColors.push({ name: name, hex: hex });
    nameEl.value = '';
    renderColorChips();
    nameEl.focus();
}

function removeColorChip(i) {
    productColors.splice(i, 1);
    renderColorChips();
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c){
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
}

(function() {
    var existing = document.getElementById('colors-hidden').value;
    if (!existing) return;
    existing.split(',').forEach(function(chunk) {
        chunk = chunk.trim();
        if (!chunk) return;
        var parts = chunk.split('|');
        var name = (parts[0] || '').trim();
        var hex  = ((parts[1] || '').trim()).match(/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/) ? parts[1].trim() : '';
        if (name) productColors.push({ name: name, hex: hex });
    });
    renderColorChips();
})();

document.getElementById('color-name-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); addColorChip(); }
});

function handleDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('drop-zone');
    dz.style.borderColor = 'var(--cream-dark)';
    dz.style.background = 'var(--cream)';
    const files = e.dataTransfer.files;
    if (files && files.length) {
        const input = document.getElementById('edit_product_image');
        const dt = new DataTransfer();
        Array.from(files).forEach(function(f) { dt.items.add(f); });
        input.files = dt.files;
        previewImages(input);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
