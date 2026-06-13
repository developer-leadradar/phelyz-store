<?php
$pageTitle = "Payment Methods";
require_once 'includes/header.php';

$db = getDB();
$success = '';
$error   = '';

$nigeriaStates = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT (Abuja)','Gombe',
    'Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos',
    'Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto',
    'Taraba','Yobe','Zamfara'
];

// Ensure all states exist (in case Shipping Rates page hasn't been visited yet)
foreach ($nigeriaStates as $state) {
    $exists = $db->fetchOne("SELECT id FROM shipping_rates WHERE state = ?", [$state]);
    if (!$exists) {
        $db->insert('shipping_rates', ['state' => $state, 'rate' => 4000.00]);
    }
}

// POST: persist per-state toggles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codStates  = isset($_POST['cod_states'])  ? array_map('strval', (array)$_POST['cod_states'])  : [];
    $bankStates = isset($_POST['bank_states']) ? array_map('strval', (array)$_POST['bank_states']) : [];

    foreach ($nigeriaStates as $state) {
        $db->update('shipping_rates', [
            'cod_enabled'  => in_array($state, $codStates,  true) ? 1 : 0,
            'bank_enabled' => in_array($state, $bankStates, true) ? 1 : 0,
        ], 'state = ?', [$state]);
    }
    $success = 'Payment-method toggles updated for all ' . count($nigeriaStates) . ' states.';
}

// Load current state of each toggle
$rows = $db->fetchAll("SELECT state, cod_enabled, bank_enabled FROM shipping_rates ORDER BY state ASC");
$stateMap = [];
foreach ($rows as $r) {
    $stateMap[$r['state']] = [
        'cod'  => (int)($r['cod_enabled']  ?? 1),
        'bank' => (int)($r['bank_enabled'] ?? 1),
    ];
}
?>

<?php if ($error): ?>
<div class="alert alert-error" style="margin-bottom:24px;">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
  <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:24px;">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  <?php echo $success; ?>
</div>
<?php endif; ?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Settings</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Payment Methods</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;max-width:620px;">Choose which payment methods are available in each state. Customers in a state see only the methods enabled here, narrowed further by any product-level overrides.</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <button type="button" onclick="bulkSet('cod', true)"  class="btn btn-outline btn-sm">All COD on</button>
    <button type="button" onclick="bulkSet('cod', false)" class="btn btn-outline btn-sm">All COD off</button>
    <button type="button" onclick="bulkSet('bank', true)" class="btn btn-outline btn-sm">All Bank on</button>
    <button type="button" onclick="bulkSet('bank', false)" class="btn btn-outline btn-sm">All Bank off</button>
  </div>
</div>

<!-- Legend / how it works -->
<div class="card" style="padding:18px 22px;margin-bottom:24px;background:rgba(202,138,4,0.05);border:1px solid rgba(202,138,4,0.2);">
  <div style="display:flex;align-items:flex-start;gap:12px;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--gold)" style="width:22px;height:22px;flex-shrink:0;margin-top:1px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <div style="font-size:13px;color:var(--stone);line-height:1.55;">
      <strong style="color:var(--black);">How it works:</strong>
      A method is offered at checkout only when (1) it's enabled for the customer's state below, AND (2) every item in their cart allows it (per-product override on the product page). When COD is the chosen method, the customer pays only the <em>shipping fee</em> via bank transfer to confirm the order, then pays the product price in cash on delivery.
    </div>
  </div>
</div>

<form method="POST" id="pm-form">
  <!-- Search -->
  <div class="card" style="padding:16px 20px;margin-bottom:16px;">
    <input type="search" id="pm-search" placeholder="Search state…" oninput="filterStates(this.value)"
           class="form-input" style="margin:0;">
  </div>

  <!-- Grid of state cards -->
  <div class="card" style="padding:20px;">
    <div id="pm-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
      <?php foreach ($nigeriaStates as $state):
        $cod  = $stateMap[$state]['cod']  ?? 1;
        $bank = $stateMap[$state]['bank'] ?? 1;
      ?>
        <div class="pm-state-card" data-state="<?php echo htmlspecialchars(strtolower($state)); ?>"
             style="border:1px solid var(--cream-dark);border-radius:10px;padding:14px 16px;background:white;">
          <div style="font-size:14px;font-weight:700;color:var(--black);margin-bottom:10px;">
            <?php echo htmlspecialchars($state); ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label style="display:flex;align-items:center;justify-content:space-between;gap:10px;cursor:pointer;font-size:13px;color:var(--stone);">
              <span style="display:flex;align-items:center;gap:8px;">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#CA8A04;"></span>
                Cash on Delivery
              </span>
              <input type="checkbox" name="cod_states[]" value="<?php echo htmlspecialchars($state); ?>"
                     class="pm-cod" <?php echo $cod ? 'checked' : ''; ?>
                     style="accent-color:var(--gold);width:18px;height:18px;cursor:pointer;">
            </label>
            <label style="display:flex;align-items:center;justify-content:space-between;gap:10px;cursor:pointer;font-size:13px;color:var(--stone);">
              <span style="display:flex;align-items:center;gap:8px;">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3B82F6;"></span>
                Bank Transfer
              </span>
              <input type="checkbox" name="bank_states[]" value="<?php echo htmlspecialchars($state); ?>"
                     class="pm-bank" <?php echo $bank ? 'checked' : ''; ?>
                     style="accent-color:var(--gold);width:18px;height:18px;cursor:pointer;">
            </label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;">
    <button type="submit" class="btn btn-gold">Save Payment Methods</button>
  </div>
</form>

<script>
function filterStates(q) {
    q = (q || '').trim().toLowerCase();
    document.querySelectorAll('.pm-state-card').forEach(function(card) {
        var state = card.getAttribute('data-state') || '';
        card.style.display = !q || state.indexOf(q) !== -1 ? '' : 'none';
    });
}
function bulkSet(method, on) {
    var sel = method === 'cod' ? '.pm-cod' : '.pm-bank';
    document.querySelectorAll(sel).forEach(function(cb) {
        // Only act on visible (filtered) cards
        if (cb.closest('.pm-state-card').style.display !== 'none') cb.checked = !!on;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
