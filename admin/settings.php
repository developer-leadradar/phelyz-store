<?php
$pageTitle = "Settings";
require_once 'includes/header.php';

$success = '';
$error   = '';
$settingsFile = __DIR__ . '/../data/settings.json';

// Load saved settings (fall back to config defaults)
$defaults = [
    'store_name'              => SITE_NAME,
    'store_email'             => SITE_EMAIL,
    'store_phone'             => SITE_PHONE,
    'currency'                => 'NGN',
    'store_address'           => 'Victoria Island, Lagos, Nigeria',
    'smtp_host'               => SMTP_HOST,
    'smtp_port'               => SMTP_PORT,
    'smtp_encryption'         => SMTP_ENCRYPTION,
    'smtp_username'           => SMTP_USERNAME,
    'smtp_password'           => '',
    'from_email'              => SMTP_FROM_EMAIL,
    'from_name'               => SMTP_FROM_NAME,
    'send_order_emails'       => 1,
    'send_admin_notifications'=> 1,
    'free_shipping_threshold' => 50000,
    'shipping_fee'            => 2500,
    'enable_shipping'         => 1,
    'enable_local_pickup'     => 1,
    'tax_rate'                => 5,
    'enable_tax'              => 1,
    'gemini_api_key'          => '',
    'image_studio_provider'   => 'gemini',
];

$saved = [];
if (file_exists($settingsFile)) {
    $saved = json_decode(file_get_contents($settingsFile), true) ?: [];
}
$s = array_merge($defaults, $saved);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = [
        'store_name'              => sanitize($_POST['store_name'] ?? ''),
        'store_email'             => sanitize($_POST['store_email'] ?? ''),
        'store_phone'             => sanitize($_POST['store_phone'] ?? ''),
        'currency'                => sanitize($_POST['currency'] ?? 'NGN'),
        'store_address'           => sanitize($_POST['store_address'] ?? ''),
        'smtp_host'               => sanitize($_POST['smtp_host'] ?? ''),
        'smtp_port'               => (int)($_POST['smtp_port'] ?? 587),
        'smtp_encryption'         => sanitize($_POST['smtp_encryption'] ?? 'tls'),
        'smtp_username'           => sanitize($_POST['smtp_username'] ?? ''),
        'smtp_password'           => !empty($_POST['smtp_password']) ? $_POST['smtp_password'] : ($s['smtp_password'] ?? ''),
        'from_email'              => sanitize($_POST['from_email'] ?? ''),
        'from_name'               => sanitize($_POST['from_name'] ?? ''),
        'send_order_emails'       => isset($_POST['send_order_emails']) ? 1 : 0,
        'send_admin_notifications'=> isset($_POST['send_admin_notifications']) ? 1 : 0,
        'free_shipping_threshold' => (float)($_POST['free_shipping_threshold'] ?? 50000),
        'shipping_fee'            => (float)($_POST['shipping_fee'] ?? 2500),
        'enable_shipping'         => isset($_POST['enable_shipping']) ? 1 : 0,
        'enable_local_pickup'     => isset($_POST['enable_local_pickup']) ? 1 : 0,
        'tax_rate'                => (float)($_POST['tax_rate'] ?? 5),
        'enable_tax'              => isset($_POST['enable_tax']) ? 1 : 0,
        // Keep the existing key if the user submits empty (treats the input
        // like a password field — paste once, never see it again).
        'gemini_api_key'          => !empty($_POST['gemini_api_key']) ? trim($_POST['gemini_api_key']) : ($s['gemini_api_key'] ?? ''),
        'image_studio_provider'   => sanitize($_POST['image_studio_provider'] ?? 'gemini'),
    ];

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) { mkdir($dataDir, 0755, true); }

    if (file_put_contents($settingsFile, json_encode($new, JSON_PRETTY_PRINT))) {
        $s = $new;
        $success = 'Settings saved successfully.';
    } else {
        $error = 'Could not write settings file. Check folder permissions on /data/.';
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
        <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Configuration</div>
        <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0;">Store Settings</h2>
    </div>
</div>

<form method="POST" id="settings-form">

    <!-- ── Section 1: General Settings ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">General Settings</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Store identity and contact information</p>
            </div>
        </div>

        <!-- Store Name -->
        <div class="form-group">
            <label class="form-label" for="s_store_name">Store Name</label>
            <input type="text" id="s_store_name" name="store_name" class="form-input"
                   value="<?php echo htmlspecialchars($s['store_name']); ?>">
        </div>

        <!-- Store Email + Phone -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_email">Store Email</label>
                <input type="email" id="s_email" name="store_email" class="form-input"
                       value="<?php echo htmlspecialchars($s['store_email']); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_phone">Store Phone</label>
                <input type="tel" id="s_phone" name="store_phone" class="form-input"
                       value="<?php echo htmlspecialchars($s['store_phone']); ?>">
            </div>
        </div>

        <!-- Currency -->
        <div class="form-group" style="margin-top:20px;">
            <label class="form-label" for="s_currency">Currency</label>
            <select id="s_currency" name="currency" class="form-input form-select" style="max-width:320px;">
                <option value="NGN" <?php echo $s['currency']==='NGN'?'selected':''; ?>>Nigerian Naira (₦)</option>
                <option value="USD">US Dollar ($)</option>
                <option value="EUR">Euro (€)</option>
                <option value="GBP">British Pound (£)</option>
            </select>
        </div>

        <!-- Store Address -->
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="s_address">Store Address</label>
            <textarea id="s_address" name="store_address" rows="3"
                      class="form-input" style="resize:vertical;"><?php echo htmlspecialchars($s['store_address']); ?></textarea>
        </div>

        <!-- Section Save -->
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save General Settings
            </button>
        </div>
    </div>

    <!-- ── Section 2: Email / SMTP ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Email / SMTP</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Outbound email server configuration</p>
            </div>
        </div>

        <!-- SMTP Host -->
        <div class="form-group">
            <label class="form-label" for="s_smtp_host">SMTP Host</label>
            <input type="text" id="s_smtp_host" name="smtp_host" class="form-input"
                   value="<?php echo htmlspecialchars($s['smtp_host']); ?>" placeholder="smtp.example.com">
        </div>

        <!-- SMTP Port + Encryption -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_smtp_port">SMTP Port</label>
                <input type="number" id="s_smtp_port" name="smtp_port" class="form-input"
                       value="<?php echo (int)$s['smtp_port']; ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_smtp_enc">Encryption</label>
                <select id="s_smtp_enc" name="smtp_encryption" class="form-input form-select">
                    <option value="tls" <?php echo $s['smtp_encryption']==='tls'?'selected':''; ?>>TLS</option>
                    <option value="ssl" <?php echo $s['smtp_encryption']==='ssl'?'selected':''; ?>>SSL</option>
                    <option value="none" <?php echo $s['smtp_encryption']==='none'?'selected':''; ?>>None</option>
                </select>
            </div>
        </div>

        <!-- SMTP Username + Password -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_smtp_user">SMTP Username</label>
                <input type="text" id="s_smtp_user" name="smtp_username" class="form-input"
                       value="<?php echo htmlspecialchars($s['smtp_username']); ?>" placeholder="your@email.com">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_smtp_pass">SMTP Password</label>
                <div style="position:relative;">
                    <input type="password" id="s_smtp_pass" name="smtp_password" class="form-input"
                           placeholder="••••••••" style="padding-right:44px;">
                    <button type="button" onclick="togglePass('s_smtp_pass',this)"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--stone-mid);cursor:pointer;display:flex;align-items:center;">
                        <svg id="s_smtp_pass_eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:17px;height:17px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- From Email + From Name -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_from_email">From Email</label>
                <input type="email" id="s_from_email" name="from_email" class="form-input"
                       value="<?php echo htmlspecialchars($s['from_email']); ?>" placeholder="noreply@phelyz.com">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_from_name">From Name</label>
                <input type="text" id="s_from_name" name="from_name" class="form-input"
                       value="<?php echo htmlspecialchars($s['from_name']); ?>" placeholder="Phelyz Store">
            </div>
        </div>

        <!-- Email notification checkboxes -->
        <div style="margin-top:20px;display:flex;flex-direction:column;gap:12px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="send_order_emails" <?php echo $s['send_order_emails'] ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Send Order Confirmation Emails</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="send_admin_notifications" <?php echo $s['send_admin_notifications'] ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Send Admin Notifications for New Orders</span>
            </label>
        </div>

        <!-- Section Save -->
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Email Settings
            </button>
        </div>
    </div>

    <!-- ── Section 3: Shipping ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Shipping</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Delivery rates and free shipping threshold</p>
            </div>
        </div>

        <!-- Free shipping threshold + Default shipping cost -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_free_ship">Free Shipping Threshold (₦)</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;font-weight:600;color:var(--stone-mid);">₦</span>
                    <input type="number" id="s_free_ship" name="free_shipping_threshold" step="0.01" min="0"
                           class="form-input" style="padding-left:30px;" value="<?php echo (float)$s['free_shipping_threshold']; ?>">
                </div>
                <p class="form-hint">Orders above this amount get free shipping</p>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_ship_fee">Standard Shipping Fee (₦)</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;font-weight:600;color:var(--stone-mid);">₦</span>
                    <input type="number" id="s_ship_fee" name="shipping_fee" step="0.01" min="0"
                           class="form-input" style="padding-left:30px;" value="<?php echo (float)$s['shipping_fee']; ?>">
                </div>
            </div>
        </div>

        <!-- Shipping checkboxes -->
        <div style="margin-top:20px;display:flex;flex-direction:column;gap:12px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="enable_shipping" <?php echo $s['enable_shipping'] ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Enable Shipping</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="enable_local_pickup" <?php echo $s['enable_local_pickup'] ? 'checked' : ''; ?>
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Enable Local Pickup</span>
            </label>
        </div>

        <!-- Section Save -->
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Shipping Settings
            </button>
        </div>
    </div>

    <!-- ── Section 4: Tax Settings ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Tax Settings</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">VAT and tax calculation rules</p>
            </div>
        </div>

        <div class="form-group" style="max-width:220px;">
            <label class="form-label" for="s_tax_rate">Tax Rate (%)</label>
            <input type="number" id="s_tax_rate" name="tax_rate" step="0.01" min="0" max="100"
                   class="form-input" value="5">
            <p class="form-hint">Default: 5% VAT</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="enable_tax" checked
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Enable Tax Calculation</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="prices_include_tax"
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Prices Include Tax</span>
            </label>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Tax Settings
            </button>
        </div>
    </div>

    <!-- ── Section 5: Payment Settings ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Payment Settings</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Payment gateways and methods</p>
            </div>
        </div>

        <!-- COD + Bank Transfer -->
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="enable_cod" checked
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Enable Cash on Delivery</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="enable_bank_transfer" checked
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Enable Bank Transfer</span>
            </label>
        </div>

        <!-- Bank Account Details -->
        <div class="form-group">
            <label class="form-label" for="s_bank_details">Bank Account Details (for Bank Transfer)</label>
            <textarea id="s_bank_details" name="bank_details" rows="4"
                      class="form-input" style="resize:vertical;"
                      placeholder="Bank Name: &#10;Account Name: &#10;Account Number: "></textarea>
        </div>

        <!-- PayPal -->
        <div style="background:var(--cream);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="enable_paypal"
                           style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                    <span style="font-size:13px;font-weight:700;color:var(--black);">Enable PayPal</span>
                </label>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_paypal_id">PayPal Client ID</label>
                <input type="text" id="s_paypal_id" name="paypal_client_id" class="form-input"
                       placeholder="Your PayPal Client ID">
            </div>
        </div>

        <!-- Stripe -->
        <div style="background:var(--cream);border-radius:10px;padding:16px;">
            <div style="margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="enable_stripe"
                           style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                    <span style="font-size:13px;font-weight:700;color:var(--black);">Enable Stripe</span>
                </label>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-row-2col">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="s_stripe_pub">Stripe Publishable Key</label>
                    <input type="text" id="s_stripe_pub" name="stripe_publishable_key" class="form-input"
                           placeholder="pk_live_...">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="s_stripe_sec">Stripe Secret Key</label>
                    <div style="position:relative;">
                        <input type="password" id="s_stripe_sec" name="stripe_secret_key" class="form-input"
                               placeholder="sk_live_..." style="padding-right:44px;">
                        <button type="button" onclick="togglePass('s_stripe_sec',this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--stone-mid);cursor:pointer;display:flex;align-items:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:17px;height:17px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Payment Settings
            </button>
        </div>
    </div>

    <!-- ── Section 6: Order Settings ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Order Settings</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Checkout and order numbering preferences</p>
            </div>
        </div>

        <div class="form-group" style="max-width:220px;">
            <label class="form-label" for="s_order_prefix">Order Number Prefix</label>
            <input type="text" id="s_order_prefix" name="order_prefix" class="form-input"
                   value="ORD-" placeholder="e.g., ORD-">
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="allow_guest_checkout" checked
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Allow Guest Checkout</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="require_login"
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Require Login to Purchase</span>
            </label>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Order Settings
            </button>
        </div>
    </div>

    <!-- ── Section 7: Inventory Settings ── -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Inventory Settings</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Stock thresholds and backorder rules</p>
            </div>
        </div>

        <div class="form-group" style="max-width:220px;">
            <label class="form-label" for="s_low_stock">Low Stock Threshold</label>
            <input type="number" id="s_low_stock" name="low_stock_threshold" min="0"
                   class="form-input" value="5">
            <p class="form-hint">Show warning when stock falls below this number</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="allow_backorders"
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Allow Backorders</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="hide_out_of_stock"
                       style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:13px;font-weight:600;color:var(--black);">Hide Out of Stock Products</span>
            </label>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Inventory Settings
            </button>
        </div>
    </div>

    <!-- ── Section 7b: Image Studio ── -->
    <div class="card" id="image-studio" style="padding:28px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Image Studio (AI provider)</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">API key for generating AI "model wearing the jewellery" shots</p>
            </div>
        </div>

        <div class="form-group" style="max-width:240px;">
            <label class="form-label" for="s_studio_provider">AI Provider</label>
            <select id="s_studio_provider" name="image_studio_provider" class="form-input form-select">
                <option value="gemini" <?php echo ($s['image_studio_provider'] ?? 'gemini') === 'gemini' ? 'selected' : ''; ?>>Google Gemini 2.5 Flash Image (Nano Banana)</option>
            </select>
            <p class="form-hint">More providers can be plugged in later (OpenAI, Replicate, etc.).</p>
        </div>

        <div class="form-group">
            <label class="form-label" for="s_gemini_key">Gemini API Key</label>
            <input type="password" id="s_gemini_key" name="gemini_api_key" autocomplete="off"
                   placeholder="<?php echo !empty($s['gemini_api_key']) ? '••••••••••••• (saved — leave empty to keep)' : 'Paste your Gemini API key here'; ?>"
                   class="form-input">
            <p class="form-hint">
                Get a free key at <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" style="color:var(--gold);font-weight:600;">aistudio.google.com</a>.
                Stored locally in <code>/data/settings.json</code> on this server. On Vercel, set <code>GEMINI_API_KEY</code> as an environment variable instead — env vars override what's saved here.
            </p>
        </div>

        <div style="margin-top:16px;padding:14px 16px;border-radius:8px;background:rgba(202,138,4,0.06);border:1px solid rgba(202,138,4,0.2);font-size:12.5px;color:var(--stone);line-height:1.55;">
            <strong style="color:var(--black);">Free tier:</strong> Gemini's free tier allows roughly 10 image generations per minute and ~100 per day — plenty for a jewellery shop's daily uploads. You only need a paid plan if you're regenerating your entire catalogue at once.
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Image Studio Settings
            </button>
        </div>
    </div>

    <!-- ── Section 8: Social Media ── -->
    <div class="card" style="padding:28px;margin-bottom:28px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
            <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
            <div>
                <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">Social Media Links</h3>
                <p style="font-size:12px;color:var(--stone-mid);margin:0;">Store social profiles for the footer</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="form-row-2col">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_fb">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;color:#1877F2;">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook URL
                    </span>
                </label>
                <input type="url" id="s_fb" name="facebook_url" class="form-input"
                       placeholder="https://facebook.com/yourpage">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_ig">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;color:#E4405F;">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                        Instagram URL
                    </span>
                </label>
                <input type="url" id="s_ig" name="instagram_url" class="form-input"
                       placeholder="https://instagram.com/yourpage">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_tw">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;color:#1DA1F2;">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                        Twitter URL
                    </span>
                </label>
                <input type="url" id="s_tw" name="twitter_url" class="form-input"
                       placeholder="https://twitter.com/yourpage">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="s_pin">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px;color:#BD081C;">
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
                        </svg>
                        Pinterest URL
                    </span>
                </label>
                <input type="url" id="s_pin" name="pinterest_url" class="form-input"
                       placeholder="https://pinterest.com/yourpage">
            </div>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
            <button type="submit" class="btn btn-gold btn-sm" style="gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Save Social Links
            </button>
        </div>
    </div>

    <!-- ── Global Save ── -->
    <div style="display:flex;align-items:center;gap:12px;padding:20px 0;border-top:2px solid var(--cream-dark);">
        <button type="submit" class="btn btn-gold" style="gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            Save All Settings
        </button>
        <button type="reset" class="btn btn-outline">Reset</button>
    </div>

</form>

<style>
@media (max-width: 640px) {
    .form-row-2col { grid-template-columns: 1fr !important; }
}
</style>

<script>
function togglePass(fieldId, btn) {
    var field = document.getElementById(fieldId);
    var isPass = field.type === 'password';
    field.type = isPass ? 'text' : 'password';
    var svg = btn.querySelector('svg');
    if (isPass) {
        svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>';
    } else {
        svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
