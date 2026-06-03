<?php
$pageTitle = "Add Product";
require_once 'includes/header.php';

$db = getDB();
$categories = getAllCategories(false);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    $name = sanitize($_POST['name']);
    $sku = sanitize($_POST['sku']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock_quantity'];
    $categoryId = (int)$_POST['category_id'];

    if (empty($name) || empty($sku) || $price <= 0 || !$categoryId) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if SKU exists
        $existingSku = $db->fetchOne("SELECT id FROM products WHERE sku = ?", [$sku]);
        if ($existingSku) {
            $error = 'SKU already exists';
        } else {
            // Handle image upload
            $imagePath = 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=800'; // Default
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploaded = uploadImage($_FILES['image'], 'products');
                if ($uploaded) {
                    $imagePath = $uploaded;
                }
            }

            // Prepare product data
            $productData = [
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
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'rating' => 4.5,
                'review_count' => 0
            ];

            $productId = $db->insert('products', $productData);

            if ($productId) {
                redirect('products.php?success=1');
            } else {
                $error = 'Failed to add product';
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

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Catalog</div>
        <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0;">Add New Product</h2>
    </div>
    <a href="products.php" class="btn btn-outline" style="gap:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back to Products
    </a>
</div>

<form method="POST" enctype="multipart/form-data" id="product-form">

    <!-- ── Section 1: Basic Information ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Basic Information</h3>
        </div>

        <!-- Name + SKU -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_name">Product Name <span style="color:#EF4444;">*</span></label>
                <input type="text" id="add_name" name="name" required placeholder="e.g., Diamond Engagement Ring"
                       class="form-input"
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_sku">SKU <span style="color:#EF4444;">*</span></label>
                <input type="text" id="add_sku" name="sku" required placeholder="e.g., RING-001"
                       class="form-input"
                       value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>">
            </div>
        </div>

        <!-- Description -->
        <div class="form-group" style="margin-top:20px;">
            <label class="form-label" for="add_desc">Description <span style="color:#EF4444;">*</span></label>
            <textarea id="add_desc" name="description" rows="5" required placeholder="Enter detailed product description..."
                      class="form-input" style="resize:vertical;min-height:120px;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <!-- Category -->
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="add_cat">Category <span style="color:#EF4444;">*</span></label>
            <select id="add_cat" name="category_id" required class="form-input form-select">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"
                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Checkboxes: Featured + Active -->
        <div style="display:flex;gap:32px;margin-top:20px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="is_featured"
                       <?php echo (isset($_POST['is_featured'])) ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Featured Product</span>
                <span style="font-size:12px;color:var(--stone-mid);">(Show on homepage)</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="is_active" checked
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
                <label class="form-label" for="add_price">Price (₦) <span style="color:#EF4444;">*</span></label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;font-weight:600;color:var(--stone-mid);">₦</span>
                    <input type="number" id="add_price" name="price" required step="0.01" min="0" placeholder="0.00"
                           class="form-input" style="padding-left:30px;"
                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_compare">Compare Price (₦)</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;font-weight:600;color:var(--stone-mid);">₦</span>
                    <input type="number" id="add_compare" name="compare_price" step="0.01" min="0" placeholder="0.00"
                           class="form-input" style="padding-left:30px;"
                           value="<?php echo isset($_POST['compare_price']) ? $_POST['compare_price'] : ''; ?>">
                </div>
                <p class="form-hint">Original price before discount (optional)</p>
            </div>
        </div>

        <!-- Stock + Low Stock Threshold -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_stock">Stock Quantity <span style="color:#EF4444;">*</span></label>
                <input type="number" id="add_stock" name="stock_quantity" required min="0"
                       class="form-input"
                       value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : '0'; ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_low_stock">Low Stock Threshold</label>
                <input type="number" id="add_low_stock" name="low_stock_threshold" min="0" placeholder="5"
                       class="form-input"
                       value="<?php echo isset($_POST['low_stock_threshold']) ? $_POST['low_stock_threshold'] : ''; ?>">
                <p class="form-hint">Alert when stock falls below this</p>
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
                <label class="form-label" for="add_material">Material</label>
                <select id="add_material" name="material" class="form-input form-select">
                    <option value="">Select Material</option>
                    <?php foreach (['Gold','Platinum','Silver','Rose Gold','White Gold','Titanium','Stainless Steel'] as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo (isset($_POST['material']) && $_POST['material'] === $m) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_purity">Metal Purity</label>
                <select id="add_purity" name="metal_purity" class="form-input form-select">
                    <option value="">Select Purity</option>
                    <?php foreach (['10K','14K','18K','22K','24K','950','925','N/A'] as $p):
                        $label = $p === '950' ? '950 Platinum' : ($p === '925' ? '925 Silver' : $p); ?>
                        <option value="<?php echo $p; ?>" <?php echo (isset($_POST['metal_purity']) && $_POST['metal_purity'] === $p) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Stone Type + Stone Weight -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_stone">Stone Type</label>
                <select id="add_stone" name="stone_type" class="form-input form-select">
                    <?php foreach (['None','Diamond','Ruby','Emerald','Sapphire','Pearl','Topaz','Amethyst'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo (isset($_POST['stone_type']) && $_POST['stone_type'] === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_stone_w">Stone Weight (Carats)</label>
                <input type="number" id="add_stone_w" name="stone_weight" step="0.01" min="0" placeholder="0.00"
                       class="form-input"
                       value="<?php echo isset($_POST['stone_weight']) ? $_POST['stone_weight'] : '0'; ?>">
            </div>
        </div>

        <!-- Brand + Weight -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_brand">Brand</label>
                <input type="text" id="add_brand" name="brand" placeholder="e.g., Phelyz Collection"
                       class="form-input"
                       value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_weight">Weight (g)</label>
                <input type="number" id="add_weight" name="weight" step="0.01" min="0" placeholder="0.00"
                       class="form-input"
                       value="<?php echo isset($_POST['weight']) ? $_POST['weight'] : ''; ?>">
            </div>
        </div>

        <!-- Gender + Style -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_gender">Gender</label>
                <select id="add_gender" name="gender" class="form-input form-select">
                    <?php foreach (['Unisex','Men','Women'] as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo (isset($_POST['gender']) && $_POST['gender'] === $g) ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="add_style">Style</label>
                <input type="text" id="add_style" name="style" placeholder="e.g., Classic, Modern, Vintage"
                       class="form-input"
                       value="<?php echo isset($_POST['style']) ? htmlspecialchars($_POST['style']) : ''; ?>">
            </div>
        </div>

        <!-- Occasion -->
        <div class="form-group" style="margin-top:20px;margin-bottom:0;">
            <label class="form-label" for="add_occasion">Occasion</label>
            <input type="text" id="add_occasion" name="occasion" placeholder="e.g., Engagement, Wedding, Anniversary"
                   class="form-input"
                   value="<?php echo isset($_POST['occasion']) ? htmlspecialchars($_POST['occasion']) : ''; ?>">
        </div>
    </div>

    <!-- ── Section 4: Product Image ── -->
    <div class="card" style="padding:28px;margin-bottom:28px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0;">Product Image</h3>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Upload Image</label>

            <!-- Drop zone -->
            <div id="drop-zone"
                 onclick="document.getElementById('product_image').click()"
                 style="border:2px dashed var(--cream-dark);border-radius:12px;padding:40px 24px;text-align:center;cursor:pointer;transition:border-color 200ms,background 200ms;background:var(--cream);"
                 ondragover="event.preventDefault();this.style.borderColor='var(--gold)';this.style.background='rgba(202,138,4,0.04)';"
                 ondragleave="this.style.borderColor='var(--cream-dark)';this.style.background='var(--cream)';"
                 ondrop="handleDrop(event)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     style="width:40px;height:40px;color:var(--stone-mid);margin:0 auto 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                <p style="font-size:14px;font-weight:600;color:var(--black);margin:0 0 4px;">Drag &amp; drop image here</p>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">or click to browse &mdash; PNG, JPG, WebP &middot; Max 5MB &middot; 800&times;800px recommended</p>
            </div>
            <input type="file" id="product_image" name="image" accept="image/*"
                   style="display:none;" onchange="previewImage(this)">

            <!-- Preview -->
            <div id="image-preview-container" style="margin-top:16px;display:none;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--stone-mid);margin-bottom:8px;">Preview</p>
                <div style="position:relative;display:inline-block;">
                    <img id="image-preview" src="" alt="Preview"
                         style="width:160px;height:160px;object-fit:cover;border-radius:10px;border:1px solid var(--cream-dark);display:block;">
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Product
        </button>
        <a href="products.php" class="btn btn-outline">Cancel</a>
    </div>

</form>

<style>
@media (max-width: 640px) {
    .form-row-2col { grid-template-columns: 1fr !important; }
}
</style>

<script>
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
    document.getElementById('product_image').value = '';
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
        const input = document.getElementById('product_image');
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        input.files = dt.files;
        previewImage(input);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
