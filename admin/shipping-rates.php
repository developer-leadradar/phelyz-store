<?php
$pageTitle = "Shipping Rates";
require_once 'includes/header.php';

$db = getDB();
$success = '';
$error   = '';

$nigeriaSStates = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT (Abuja)','Gombe',
    'Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos',
    'Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto',
    'Taraba','Yobe','Zamfara'
];

// Ensure all states exist in DB (safe to call repeatedly)
foreach ($nigeriaSStates as $state) {
    $exists = $db->fetchOne("SELECT id FROM shipping_rates WHERE state = ?", [$state]);
    if (!$exists) {
        $db->insert('shipping_rates', ['state' => $state, 'rate' => 4000.00]);
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rates = $_POST['rates'] ?? [];
    $updated = 0;
    foreach ($rates as $state => $rate) {
        $state = sanitize($state);
        $rate  = max(0, (float)$rate);
        $db->update('shipping_rates', ['rate' => $rate], 'state = ?', [$state]);
        $updated++;
    }
    if ($updated > 0) {
        $success = 'Shipping rates updated successfully — ' . $updated . ' states saved.';
    } else {
        $error = 'No rates were updated.';
    }
}

// Load all rates into a keyed array
$rows = $db->fetchAll("SELECT state, rate FROM shipping_rates ORDER BY state ASC");
$rateMap = [];
foreach ($rows as $r) {
    $rateMap[$r['state']] = $r['rate'];
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

<!-- Page header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Settings</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Shipping Rates</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;">Set the delivery cost for each state in Nigeria. These rates are shown to customers before checkout.</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <!-- Quick-set preset buttons -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button type="button" onclick="setAll(2500)" class="btn btn-outline btn-sm">All ₦2,500</button>
      <button type="button" onclick="setAll(3500)" class="btn btn-outline btn-sm">All ₦3,500</button>
      <button type="button" onclick="setAll(4000)" class="btn btn-outline btn-sm">All ₦4,000</button>
    </div>
  </div>
</div>

<!-- Info banner -->
<div style="background:rgba(202,138,4,0.06);border:1px solid rgba(202,138,4,0.25);border-radius:10px;padding:14px 18px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px;">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--gold)" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;">
    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
  </svg>
  <p style="font-size:13px;color:var(--stone);margin:0;line-height:1.6;">
    Free shipping still applies when an order exceeds the threshold set in <a href="settings.php" style="color:var(--gold);font-weight:600;">Store Settings</a>.
    Customers see the rate for their selected state on the product page, cart, and at checkout.
  </p>
</div>

<form method="POST" id="rates-form">
  <div class="card" style="padding:28px;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);">
      <div style="width:4px;height:28px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
      <div>
        <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin:0 0 2px;">All States &amp; FCT</h3>
        <p style="font-size:12px;color:var(--stone-mid);margin:0;">36 states + Federal Capital Territory — <?php echo count($nigeriaSStates); ?> entries</p>
      </div>
    </div>

    <!-- Search filter -->
    <div style="margin-bottom:20px;position:relative;max-width:320px;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
           style="width:15px;height:15px;position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--stone-mid);">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
      </svg>
      <input type="text" id="state-search" placeholder="Filter states…"
             oninput="filterStates(this.value)"
             style="width:100%;padding:9px 12px 9px 36px;border:1.5px solid var(--cream-dark);border-radius:8px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box;"
             onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--cream-dark)'">
    </div>

    <!-- States grid -->
    <div id="states-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
      <?php foreach ($nigeriaSStates as $state):
        $rate = $rateMap[$state] ?? 4000;
        // Colour-code by bracket
        if ($rate <= 2500) $dot = '#22C55E';
        elseif ($rate <= 3000) $dot = '#84CC16';
        elseif ($rate <= 3500) $dot = '#F59E0B';
        elseif ($rate <= 4000) $dot = '#F97316';
        else $dot = '#EF4444';
      ?>
      <div class="state-row" data-state="<?php echo strtolower($state); ?>"
           style="background:var(--cream);border:1px solid var(--cream-dark);border-radius:10px;padding:12px 14px;display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;gap:7px;">
          <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $dot; ?>;flex-shrink:0;"></span>
          <span style="font-size:12px;font-weight:700;color:var(--black);"><?php echo htmlspecialchars($state); ?></span>
        </div>
        <div style="position:relative;">
          <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13px;font-weight:600;color:var(--stone-mid);">₦</span>
          <input type="number" name="rates[<?php echo htmlspecialchars($state); ?>]"
                 value="<?php echo (int)$rate; ?>"
                 min="0" step="100"
                 class="rate-input"
                 oninput="updateDot(this)"
                 style="width:100%;padding:8px 10px 8px 26px;border:1.5px solid var(--cream-dark);border-radius:7px;font-size:13px;font-weight:600;font-family:inherit;outline:none;box-sizing:border-box;"
                 onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--cream-dark)'">
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div id="no-match" style="display:none;text-align:center;padding:32px 0;color:var(--stone-mid);font-size:13px;">
      No states match your search.
    </div>
  </div>

  <!-- Save button -->
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <button type="submit" class="btn btn-gold" style="gap:8px;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:16px;height:16px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
      </svg>
      Save All Rates
    </button>
    <span style="font-size:12px;color:var(--stone-mid);">Changes apply immediately to all new sessions.</span>
  </div>
</form>

<style>
@media (max-width: 900px) {
  #states-grid { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 540px) {
  #states-grid { grid-template-columns: 1fr !important; }
}
</style>

<script>
function filterStates(q) {
  q = q.toLowerCase().trim();
  var rows = document.querySelectorAll('.state-row');
  var visible = 0;
  rows.forEach(function(row) {
    var match = row.dataset.state.includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  document.getElementById('no-match').style.display = visible === 0 ? 'block' : 'none';
}

function setAll(value) {
  document.querySelectorAll('.rate-input').forEach(function(input) {
    if (input.closest('.state-row').style.display !== 'none') {
      input.value = value;
      updateDot(input);
    }
  });
}

function updateDot(input) {
  var val = parseFloat(input.value) || 0;
  var dot = input.closest('.state-row').querySelector('span[style*="border-radius:50%"]');
  if (!dot) return;
  if (val <= 2500)      dot.style.background = '#22C55E';
  else if (val <= 3000) dot.style.background = '#84CC16';
  else if (val <= 3500) dot.style.background = '#F59E0B';
  else if (val <= 4000) dot.style.background = '#F97316';
  else                  dot.style.background = '#EF4444';
}
</script>

<?php require_once 'includes/footer.php'; ?>
