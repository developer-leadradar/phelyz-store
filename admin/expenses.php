<?php
$pageTitle = "Expenses & Profit";
require_once 'includes/header.php';

$db      = getDB();
$success = '';
$error   = '';

$ready = true;
try { $db->fetchOne("SELECT 1 FROM expenses LIMIT 1"); }
catch (Exception $e) { $ready = false; }

$categories = ['Stock purchase','Shipping & freight','Customs & duty','Packaging',
               'Transport & delivery','Marketing','Loan repayment','Fees & charges','Other'];

// ── Add an expense ──────────────────────────────────────────────────────────
if ($ready && ($_POST['form'] ?? '') === 'expense') {
    $amount = (float)($_POST['amount'] ?? 0);
    $desc   = trim($_POST['description'] ?? '');
    if ($desc === '' || $amount <= 0) {
        $error = 'Give the expense a description and an amount.';
    } else {
        try {
            $db->insert('expenses', [
                'spent_on'    => $_POST['spent_on'] ?: date('Y-m-d'),
                'category'    => in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Other',
                'description' => $desc,
                'amount'      => $amount,
                'notes'       => trim($_POST['notes'] ?? '') ?: null,
            ]);
            $success = 'Expense recorded.';
        } catch (Exception $e) { $error = 'Could not save that expense.'; }
    }
}

// ── Add a purchase batch ────────────────────────────────────────────────────
if ($ready && ($_POST['form'] ?? '') === 'batch') {
    $ref = trim($_POST['reference'] ?? '');
    if ($ref === '') {
        $error = 'Give the shipment a reference.';
    } else {
        try {
            $db->insert('purchase_batches', [
                'reference'     => $ref,
                'supplier'      => trim($_POST['supplier'] ?? '') ?: null,
                'ordered_on'    => $_POST['ordered_on'] ?: date('Y-m-d'),
                'arrived_on'    => $_POST['arrived_on'] ?: null,
                'shipping_cost' => (float)($_POST['shipping_cost'] ?? 0),
                'other_cost'    => (float)($_POST['other_cost'] ?? 0),
                'allocation'    => ($_POST['allocation'] ?? 'value') === 'quantity' ? 'quantity' : 'value',
                'notes'         => trim($_POST['notes'] ?? '') ?: null,
            ]);
            $success = 'Shipment added. Now add what was in it.';
        } catch (Exception $e) { $error = 'Could not save that shipment.'; }
    }
}

// ── Add an item to a batch ──────────────────────────────────────────────────
if ($ready && ($_POST['form'] ?? '') === 'batch_item') {
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $name    = trim($_POST['item_name'] ?? '');
    $qty     = max(1, (int)($_POST['quantity'] ?? 1));
    $unit    = (float)($_POST['unit_cost'] ?? 0);
    if (!$batchId || $name === '' || $unit <= 0) {
        $error = 'An item needs a name, a quantity and a unit cost.';
    } else {
        try {
            $db->insert('purchase_batch_items', [
                'batch_id'       => $batchId,
                'product_id'     => ($_POST['product_id'] ?? '') !== '' ? (int)$_POST['product_id'] : null,
                'item_name'      => $name,
                'quantity'       => $qty,
                'unit_cost'      => $unit,
                'expected_price' => ($_POST['expected_price'] ?? '') !== '' ? (float)$_POST['expected_price'] : null,
            ]);
            $db->query(
                "UPDATE purchase_batches SET goods_cost =
                    (SELECT COALESCE(SUM(quantity * unit_cost),0) FROM purchase_batch_items WHERE batch_id = ?)
                 WHERE id = ?", [$batchId, $batchId]
            );
            $success = 'Item added.';
        } catch (Exception $e) { $error = 'Could not add that item.'; }
    }
}

// ── Push landed cost onto the linked products ───────────────────────────────
if ($ready && isset($_GET['apply_costs'])) {
    $batchId = (int)$_GET['apply_costs'];
    try {
        $batch = $db->fetchOne("SELECT * FROM purchase_batches WHERE id = ?", [$batchId]);
        $items = $db->fetchAll("SELECT * FROM purchase_batch_items WHERE batch_id = ?", [$batchId]);
        $spread = (float)$batch['shipping_cost'] + (float)$batch['other_cost'];

        $totalValue = 0.0; $totalQty = 0;
        foreach ($items as $i) { $totalValue += $i['quantity'] * $i['unit_cost']; $totalQty += $i['quantity']; }

        $applied = 0;
        foreach ($items as $i) {
            if (empty($i['product_id'])) continue;
            // Freight is shared out by what each line is worth, or by how many
            // pieces it holds, whichever the shipment was set to.
            $share = 0.0;
            if ($batch['allocation'] === 'quantity' && $totalQty > 0) {
                $share = $spread * ($i['quantity'] / $totalQty);
            } elseif ($totalValue > 0) {
                $share = $spread * (($i['quantity'] * $i['unit_cost']) / $totalValue);
            }
            $landed = (float)$i['unit_cost'] + ($i['quantity'] > 0 ? $share / $i['quantity'] : 0);
            $db->update('products', ['cost_price' => round($landed, 2)], 'id = ?', [$i['product_id']]);
            $applied++;
        }
        $success = $applied . ' product' . ($applied === 1 ? '' : 's') . ' updated with their true landed cost.';
    } catch (Exception $e) { $error = 'Could not apply those costs.'; }
}

// ── Figures ─────────────────────────────────────────────────────────────────
$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$totExpenses = 0; $byCategory = []; $expenses = []; $batches = []; $products = [];
$revenue = 0; $cogs = 0; $orderCount = 0;

if ($ready) {
    try {
        $totExpenses = (float)($db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) AS t FROM expenses WHERE spent_on BETWEEN ? AND ?", [$from,$to])['t'] ?? 0);
        $byCategory = $db->fetchAll(
            "SELECT category, SUM(amount) AS total FROM expenses WHERE spent_on BETWEEN ? AND ?
             GROUP BY category ORDER BY total DESC", [$from,$to]);
        $expenses = $db->fetchAll(
            "SELECT * FROM expenses WHERE spent_on BETWEEN ? AND ? ORDER BY spent_on DESC, id DESC LIMIT 40", [$from,$to]);
        $batches = $db->fetchAll(
            "SELECT b.*, (SELECT COUNT(*) FROM purchase_batch_items i WHERE i.batch_id=b.id) AS lines,
                    (SELECT COALESCE(SUM(i.quantity),0) FROM purchase_batch_items i WHERE i.batch_id=b.id) AS pieces
             FROM purchase_batches b ORDER BY b.ordered_on DESC LIMIT 20");
        $products = $db->fetchAll("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name ASC");

        // Sales side, so the page can show profit and not just spend.
        $rev = $db->fetchOne(
            "SELECT COALESCE(SUM(o.total),0) AS revenue, COUNT(*) AS orders
             FROM orders o WHERE o.status <> 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?", [$from,$to]);
        $revenue    = (float)($rev['revenue'] ?? 0);
        $orderCount = (int)($rev['orders'] ?? 0);

        $cogs = (float)($db->fetchOne(
            "SELECT COALESCE(SUM(oi.quantity * p.cost_price),0) AS c
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN products p ON p.id = oi.product_id
             WHERE o.status <> 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?", [$from,$to])['c'] ?? 0);
    } catch (Exception $e) {}
}

$grossProfit = $revenue - $cogs;
$netProfit   = $grossProfit - $totExpenses;
$margin      = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
$missingCost = 0;
if ($ready) {
    try { $missingCost = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM products WHERE is_active=1 AND cost_price <= 0")['c'] ?? 0); }
    catch (Exception $e) {}
}
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-page-title">Expenses &amp; Profit</h1>
    <p style="font-size:13px;color:var(--stone-mid);margin:4px 0 0;">
      What you spend, what each piece really cost to land, and what is left over.
    </p>
  </div>
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="form-input" style="width:auto;font-size:13px;padding:8px 10px;">
    <input type="date" name="to"   value="<?php echo htmlspecialchars($to); ?>"   class="form-input" style="width:auto;font-size:13px;padding:8px 10px;">
    <button class="btn btn-outline" style="font-size:13px;">Apply</button>
  </form>
</div>

<?php if (!$ready): ?>
  <div class="alert alert-error" style="margin-bottom:18px;">
    The expense tables are missing. Run <strong>migrations/add_automation_and_expenses.sql</strong> once, then reload.
  </div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:18px;"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error" style="margin-bottom:18px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<!-- KPI row -->
<div class="traffic-kpis">
  <?php
  $kpis = [
    ['Revenue',       formatPrice($revenue),     '#0EA5E9'],
    ['Cost of goods', formatPrice($cogs),        '#D97706'],
    ['Gross profit',  formatPrice($grossProfit), $grossProfit >= 0 ? '#10B981' : '#EF4444'],
    ['After expenses',formatPrice($netProfit),   $netProfit   >= 0 ? '#10B981' : '#EF4444'],
  ];
  foreach ($kpis as [$label,$value,$colour]): ?>
    <div class="traffic-kpi">
      <div class="traffic-kpi-icon" style="background:<?php echo $colour; ?>1F;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="<?php echo $colour; ?>" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div style="min-width:0;">
        <div class="traffic-kpi-num" style="font-size:17px;"><?php echo $value; ?></div>
        <div class="traffic-kpi-lbl"><?php echo $label; ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<p style="font-size:12.5px;color:var(--stone-mid);margin:-6px 0 20px;">
  <?php echo number_format($orderCount); ?> order<?php echo $orderCount === 1 ? '' : 's'; ?>
  &middot; margin <?php echo number_format($margin, 1); ?>%
  &middot; expenses <?php echo formatPrice($totExpenses); ?>
  <?php if ($missingCost > 0): ?>
    <br><span style="color:#B91C1C;"><?php echo $missingCost; ?> product<?php echo $missingCost === 1 ? ' has' : 's have'; ?> no cost price set, so profit is overstated until you add one.</span>
  <?php endif; ?>
</p>

<div class="exp-grid">

  <!-- ── Record an expense ───────────────────────────────── -->
  <form method="POST" class="card" style="padding:20px;">
    <input type="hidden" name="form" value="expense">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 12px;">Record an expense</h2>

    <div class="exp-row-2">
      <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" name="spent_on" class="form-input" value="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category" class="form-input form-select">
          <?php foreach ($categories as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">What was it for</label>
      <input type="text" name="description" class="form-input" required maxlength="255" placeholder="Transport to Mount Zion (delivery)">
    </div>
    <div class="form-group">
      <label class="form-label">Amount</label>
      <input type="number" name="amount" class="form-input" step="0.01" min="0" required placeholder="0.00">
    </div>
    <button class="btn btn-gold btn-full">Add expense</button>

    <?php if ($byCategory): ?>
      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--cream-dark);">
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--stone-mid);margin-bottom:10px;">Where it went</div>
        <?php foreach ($byCategory as $c):
          $pct = $totExpenses > 0 ? ($c['total'] / $totExpenses) * 100 : 0; ?>
          <div style="margin-bottom:9px;">
            <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:3px;">
              <span style="color:var(--black);font-weight:600;"><?php echo htmlspecialchars($c['category'] ?? ''); ?></span>
              <span style="color:var(--stone-mid);"><?php echo formatPrice($c['total']); ?></span>
            </div>
            <div style="height:6px;background:var(--cream-dark);border-radius:99px;overflow:hidden;">
              <div style="height:100%;width:<?php echo round($pct); ?>%;background:var(--gold);"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </form>

  <!-- ── Shipments ───────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

    <form method="POST" class="card" style="padding:20px;">
      <input type="hidden" name="form" value="batch">
      <h2 style="font-size:15px;font-weight:700;margin:0 0 4px;">New shipment</h2>
      <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 12px;">
        A supplier order and everything it cost to get here. Add the items afterwards and the page works out what each piece really cost.
      </p>
      <div class="exp-row-2">
        <div class="form-group">
          <label class="form-label">Reference</label>
          <input type="text" name="reference" class="form-input" required maxlength="80" placeholder="1688 order, June">
        </div>
        <div class="form-group">
          <label class="form-label">Supplier</label>
          <input type="text" name="supplier" class="form-input" maxlength="120" placeholder="1688">
        </div>
      </div>
      <div class="exp-row-2">
        <div class="form-group">
          <label class="form-label">Ordered</label>
          <input type="date" name="ordered_on" class="form-input" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Arrived</label>
          <input type="date" name="arrived_on" class="form-input">
        </div>
      </div>
      <div class="exp-row-2">
        <div class="form-group">
          <label class="form-label">Freight, customs, packing</label>
          <input type="number" name="shipping_cost" class="form-input" step="0.01" min="0" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Spread that cost by</label>
          <select name="allocation" class="form-input form-select">
            <option value="value">Item value</option>
            <option value="quantity">Number of pieces</option>
          </select>
        </div>
      </div>
      <button class="btn btn-gold btn-full">Add shipment</button>
    </form>

    <?php if ($batches): ?>
    <div class="card" style="padding:20px;">
      <h2 style="font-size:15px;font-weight:700;margin:0 0 12px;">Shipments</h2>
      <div style="display:flex;flex-direction:column;gap:11px;">
        <?php foreach ($batches as $b):
          $landedTotal = (float)$b['goods_cost'] + (float)$b['shipping_cost'] + (float)$b['other_cost'];
          $perPiece    = (int)$b['pieces'] > 0 ? $landedTotal / (int)$b['pieces'] : 0; ?>
          <div style="border:1px solid var(--cream-dark);border-radius:10px;padding:12px 14px;">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
              <div style="min-width:0;flex:1;">
                <div style="font-weight:700;font-size:13px;color:var(--black);overflow-wrap:anywhere;"><?php echo htmlspecialchars($b['reference'] ?? ''); ?></div>
                <div style="font-size:11.5px;color:var(--stone-mid);margin-top:3px;">
                  <?php echo htmlspecialchars($b['supplier'] ?: 'Supplier not set'); ?>
                  &middot; <?php echo date('j M Y', strtotime($b['ordered_on'])); ?>
                  &middot; <?php echo (int)$b['lines']; ?> line<?php echo (int)$b['lines'] === 1 ? '' : 's'; ?>,
                  <?php echo (int)$b['pieces']; ?> piece<?php echo (int)$b['pieces'] === 1 ? '' : 's'; ?>
                </div>
              </div>
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-weight:700;font-size:13px;color:var(--black);"><?php echo formatPrice($landedTotal); ?></div>
                <div style="font-size:11.5px;color:var(--stone-mid);"><?php echo formatPrice($perPiece); ?> a piece</div>
              </div>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:9px;padding-top:9px;border-top:1px solid var(--cream-dark);font-size:12px;">
              <a href="?batch=<?php echo (int)$b['id']; ?>" style="color:var(--gold);font-weight:600;">Add items</a>
              <?php if ((int)$b['lines'] > 0): ?>
                <a href="?apply_costs=<?php echo (int)$b['id']; ?>"
                   onclick="return confirm('Write the landed cost of each item onto its linked product?')"
                   style="color:var(--black);font-weight:600;">Apply costs to products</a>
              <?php endif; ?>
            </div>

            <?php if ((int)($_GET['batch'] ?? 0) === (int)$b['id']): ?>
              <form method="POST" style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--cream-dark);">
                <input type="hidden" name="form" value="batch_item">
                <input type="hidden" name="batch_id" value="<?php echo (int)$b['id']; ?>">
                <div class="exp-row-2">
                  <div class="form-group"><label class="form-label">Item</label>
                    <input type="text" name="item_name" class="form-input" required placeholder="Titanium necklace"></div>
                  <div class="form-group"><label class="form-label">Link to product</label>
                    <select name="product_id" class="form-input form-select">
                      <option value="">Not listed yet</option>
                      <?php foreach ($products as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name'] ?? ''); ?></option>
                      <?php endforeach; ?>
                    </select></div>
                </div>
                <div class="exp-row-3">
                  <div class="form-group"><label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-input" min="1" value="1" required></div>
                  <div class="form-group"><label class="form-label">Cost each</label>
                    <input type="number" name="unit_cost" class="form-input" step="0.01" min="0" required></div>
                  <div class="form-group"><label class="form-label">Sell for</label>
                    <input type="number" name="expected_price" class="form-input" step="0.01" min="0"></div>
                </div>
                <button class="btn btn-outline btn-full" style="font-size:13px;">Add item</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($expenses): ?>
<div class="card" style="padding:20px;margin-top:20px;">
  <h2 style="font-size:15px;font-weight:700;margin:0 0 12px;">Recent expenses</h2>
  <div style="overflow-x:auto;">
    <table class="data-table" style="min-width:520px;">
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th style="text-align:right;">Amount</th></tr></thead>
      <tbody>
      <?php foreach ($expenses as $e): ?>
        <tr>
          <td style="white-space:nowrap;font-size:13px;"><?php echo date('j M Y', strtotime($e['spent_on'])); ?></td>
          <td style="font-size:13px;color:var(--stone-mid);"><?php echo htmlspecialchars($e['category'] ?? ''); ?></td>
          <td style="font-size:13px;overflow-wrap:anywhere;"><?php echo htmlspecialchars($e['description'] ?? ''); ?></td>
          <td style="text-align:right;font-weight:600;white-space:nowrap;"><?php echo formatPrice($e['amount']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<style>
.exp-grid { display:grid; grid-template-columns:0.9fr 1.1fr; gap:20px; align-items:start; }
.exp-grid > * { min-width:0; }
.exp-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.exp-row-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
@media (max-width:1024px){ .exp-grid { grid-template-columns:1fr; } }
@media (max-width:560px){ .exp-row-2, .exp-row-3 { grid-template-columns:1fr; } }
</style>

<?php require_once 'includes/footer.php'; ?>
