<?php
$pageTitle = "Coupons";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/coupons.php';

$db      = getDB();
$success = '';
$error   = '';

$tablesReady = true;
try { $db->fetchOne("SELECT 1 FROM coupons LIMIT 1"); }
catch (Exception $e) { $tablesReady = false; }

// ── Toggle active ───────────────────────────────────────────────────────────
if ($tablesReady && isset($_GET['toggle'])) {
    try {
        $id = (int)$_GET['toggle'];
        $db->query("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?", [$id]);
        $success = 'Coupon updated.';
    } catch (Exception $e) { $error = 'Could not update that coupon.'; }
}

// ── Delete ──────────────────────────────────────────────────────────────────
if ($tablesReady && isset($_GET['delete'])) {
    try {
        $db->query("DELETE FROM coupons WHERE id = ?", [(int)$_GET['delete']]);
        $success = 'Coupon deleted.';
    } catch (Exception $e) { $error = 'Could not delete that coupon.'; }
}

// ── Save (create or edit) ───────────────────────────────────────────────────
$editing = null;
if ($tablesReady && isset($_GET['edit'])) {
    $editing = $db->fetchOne("SELECT * FROM coupons WHERE id = ?", [(int)$_GET['edit']]);
}

if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['code'] ?? ''));

    $data = [
        'code'                  => $code,
        'description'           => trim($_POST['description'] ?? ''),
        'type'                  => in_array($_POST['type'] ?? '', ['percent','fixed','free_shipping'], true) ? $_POST['type'] : 'percent',
        'value'                 => (float)($_POST['value'] ?? 0),
        'max_discount'          => ($_POST['max_discount'] ?? '') !== '' ? (float)$_POST['max_discount'] : null,
        'min_spend'             => (float)($_POST['min_spend'] ?? 0),
        'starts_at'             => ($_POST['starts_at']  ?? '') !== '' ? str_replace('T', ' ', $_POST['starts_at'])  . ':00' : null,
        'expires_at'            => ($_POST['expires_at'] ?? '') !== '' ? str_replace('T', ' ', $_POST['expires_at']) . ':00' : null,
        'max_uses'              => ($_POST['max_uses'] ?? '') !== '' ? (int)$_POST['max_uses'] : null,
        'max_uses_per_customer' => max(0, (int)($_POST['max_uses_per_customer'] ?? 1)),
        'first_order_only'      => !empty($_POST['first_order_only']) ? 1 : 0,
        'birthday_only'         => !empty($_POST['birthday_only']) ? 1 : 0,
        'birthday_window_days'  => max(0, (int)($_POST['birthday_window_days'] ?? 7)),
        'category_id'           => ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null,
        'exclude_express'       => !empty($_POST['exclude_express']) ? 1 : 0,
        'source'                => trim($_POST['source'] ?? '') ?: null,
        'is_active'             => !empty($_POST['is_active']) ? 1 : 0,
    ];

    if ($code === '') {
        $error = 'Give the coupon a code.';
    } elseif ($data['type'] === 'percent' && ($data['value'] <= 0 || $data['value'] > 100)) {
        $error = 'A percentage discount must be between 1 and 100.';
    } elseif ($data['type'] === 'fixed' && $data['value'] <= 0) {
        $error = 'Enter how much money comes off.';
    } else {
        try {
            $clash = $db->fetchOne("SELECT id FROM coupons WHERE UPPER(code) = ? AND id <> ?", [$code, $id]);
            if ($clash) {
                $error = 'That code already exists. Pick another.';
            } elseif ($id) {
                $db->update('coupons', $data, 'id = ?', [$id]);
                $success = 'Coupon ' . htmlspecialchars($code) . ' saved.';
                $editing = null;
            } else {
                $db->insert('coupons', $data);
                $success = 'Coupon ' . htmlspecialchars($code) . ' created.';
            }
        } catch (Exception $e) {
            $error = 'Could not save that coupon.';
        }
    }
}

// ── Who used a given coupon ─────────────────────────────────────────────────
$viewing    = null;
$redeemers  = [];
if ($tablesReady && isset($_GET['used'])) {
    $viewing = $db->fetchOne("SELECT * FROM coupons WHERE id = ?", [(int)$_GET['used']]);
    if ($viewing) {
        try {
            $redeemers = $db->fetchAll(
                "SELECT r.created_at, r.discount, r.email, r.order_id,
                        u.first_name, u.last_name,
                        o.order_number, o.total, o.status
                 FROM coupon_redemptions r
                 LEFT JOIN users  u ON u.id = r.user_id
                 LEFT JOIN orders o ON o.id = r.order_id
                 WHERE r.coupon_id = ?
                 ORDER BY r.created_at DESC",
                [(int)$viewing['id']]
            );
        } catch (Exception $e) { $redeemers = []; }
    }
}

// ── Data ────────────────────────────────────────────────────────────────────
$coupons    = [];
$categories = [];
if ($tablesReady) {
    try {
        $coupons = $db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*)          FROM coupon_redemptions r WHERE r.coupon_id = c.id) AS redemptions,
                    (SELECT COALESCE(SUM(r.discount),0) FROM coupon_redemptions r WHERE r.coupon_id = c.id) AS given_away,
                    (SELECT COALESCE(SUM(o.total),0)    FROM coupon_redemptions r
                        JOIN orders o ON o.id = r.order_id
                        WHERE r.coupon_id = c.id AND o.status <> 'cancelled') AS revenue
             FROM coupons c ORDER BY c.is_active DESC, c.created_at DESC"
        );
    } catch (Exception $e) { $coupons = []; }
}
try { $categories = getAllCategories(); } catch (Exception $e) { $categories = []; }

$f = function ($key, $default = '') use ($editing) {
    return htmlspecialchars((string)($editing[$key] ?? $default));
};
$chk = function ($key, $default = 0) use ($editing) {
    $v = $editing[$key] ?? $default;
    return !empty($v) ? 'checked' : '';
};
$dtLocal = function ($v) { return $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; };
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-page-title">Coupons</h1>
    <p style="font-size:13px;color:var(--stone-mid);margin:4px 0 0;">
      Discount codes, free shipping, first-order and birthday offers, and codes you can hand to influencers.
    </p>
  </div>
</div>

<?php if (!$tablesReady): ?>
  <div class="alert alert-error" style="margin-bottom:18px;">
    The coupon tables are missing. Run <strong>migrations/add_coupons.sql</strong> once in phpMyAdmin, then reload this page.
  </div>
<?php endif; ?>

<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:18px;"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error" style="margin-bottom:18px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if ($viewing): ?>
<!-- ── Redemption list for one coupon ───────────────────── -->
<div class="card" style="padding:22px;margin-bottom:20px;">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
    <div style="min-width:0;">
      <h2 style="font-size:16px;font-weight:700;margin:0 0 4px;">
        Who used <code style="background:var(--black);color:#fff;padding:2px 9px;border-radius:6px;font-size:13px;letter-spacing:0.06em;"><?php echo htmlspecialchars($viewing['code']); ?></code>
      </h2>
      <p style="font-size:12.5px;color:var(--stone-mid);margin:0;">
        <?php
        $people = [];
        foreach ($redeemers as $r) { if (!empty($r['email'])) $people[strtolower($r['email'])] = true; }
        $peopleCount = count($people);
        echo count($redeemers) . ' ' . (count($redeemers) === 1 ? 'use' : 'uses') . ' by ' . $peopleCount . ' ' . ($peopleCount === 1 ? 'person' : 'people');
        ?>
      </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <?php if ($peopleCount > 0): ?>
        <a href="email-campaigns.php?audience=coupon_<?php echo (int)$viewing['id']; ?>" class="btn btn-gold" style="font-size:13px;">
          Email these <?php echo $peopleCount; ?> <?php echo $peopleCount === 1 ? 'buyer' : 'buyers'; ?>
        </a>
      <?php endif; ?>
      <a href="coupons.php" class="btn btn-outline" style="font-size:13px;">Close</a>
    </div>
  </div>

  <?php if (!$redeemers): ?>
    <p style="font-size:13px;color:var(--stone-mid);margin:12px 0 0;">Nobody has used this code yet.</p>
  <?php else: ?>
    <div style="overflow-x:auto;margin-top:14px;">
      <table class="data-table" style="min-width:560px;">
        <thead>
          <tr>
            <th>Customer</th><th>Email</th><th>Used</th><th>Order</th><th style="text-align:right;">Saved</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($redeemers as $r): ?>
          <tr>
            <td style="font-weight:600;color:var(--black);">
              <?php
              $nm = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
              echo $nm !== '' ? htmlspecialchars($nm) : '<span style="color:var(--stone-mid);font-weight:400;">Guest</span>';
              ?>
            </td>
            <td style="font-size:13px;overflow-wrap:anywhere;"><?php echo htmlspecialchars($r['email'] ?? '-'); ?></td>
            <td style="font-size:13px;color:var(--stone-mid);white-space:nowrap;"><?php echo date('j M Y', strtotime($r['created_at'])); ?></td>
            <td style="font-size:13px;">
              <?php if (!empty($r['order_number'])): ?>
                <a href="order-details.php?id=<?php echo (int)$r['order_id']; ?>" style="color:var(--gold);font-weight:600;"><?php echo htmlspecialchars($r['order_number']); ?></a>
                <span style="color:var(--stone-mid);">(<?php echo formatPrice($r['total']); ?>)</span>
              <?php else: ?>
                <span style="color:var(--stone-mid);">-</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:600;color:#15803D;white-space:nowrap;"><?php echo formatPrice($r['discount']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="font-size:12px;color:var(--stone-mid);margin:12px 0 0;">
      These are proven buyers. Sending this group a stronger offer a couple of weeks later is the quickest way to turn a first order into a second.
    </p>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="coupon-grid">

  <!-- ── Form ────────────────────────────────────────────── -->
  <form method="POST" class="card" style="padding:22px;">
    <input type="hidden" name="id" value="<?php echo (int)($editing['id'] ?? 0); ?>">

    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;">
      <h2 style="font-size:15px;font-weight:700;margin:0;"><?php echo $editing ? 'Edit coupon' : 'New coupon'; ?></h2>
      <?php if ($editing): ?>
        <a href="coupons.php" style="font-size:12.5px;color:var(--stone-mid);">Cancel</a>
      <?php endif; ?>
    </div>

    <?php if (!$editing): ?>
      <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 14px;">Start from a ready-made setup, then adjust it.</p>
      <div class="preset-row">
        <button type="button" class="preset-chip" data-preset="welcome">First order</button>
        <button type="button" class="preset-chip" data-preset="shipping">Free shipping</button>
        <button type="button" class="preset-chip" data-preset="birthday">Birthday</button>
        <button type="button" class="preset-chip" data-preset="influencer">Influencer</button>
        <button type="button" class="preset-chip" data-preset="winback">Win-back</button>
      </div>
    <?php endif; ?>

    <div class="form-row-2" style="margin-top:16px;">
      <div class="form-group">
        <label class="form-label">Code</label>
        <input type="text" name="code" id="c-code" class="form-input" required maxlength="50"
               placeholder="WELCOME10" style="text-transform:uppercase;"
               value="<?php echo $f('code'); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Label <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(only you see this)</span></label>
        <input type="text" name="description" id="c-desc" class="form-input" maxlength="255"
               placeholder="First order discount" value="<?php echo $f('description'); ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">What it gives</label>
      <div class="type-row">
        <?php
        $types = [
          'percent'       => ['Percent off', '10% off the bag'],
          'fixed'         => ['Money off',   'A set amount off'],
          'free_shipping' => ['Free shipping','Delivery on us'],
        ];
        $curType = $editing['type'] ?? 'percent';
        foreach ($types as $val => [$lbl, $sub]): ?>
          <label class="type-option<?php echo $curType === $val ? ' selected' : ''; ?>">
            <input type="radio" name="type" value="<?php echo $val; ?>" <?php echo $curType === $val ? 'checked' : ''; ?>>
            <span>
              <strong style="display:block;font-size:13px;color:var(--black);"><?php echo $lbl; ?></strong>
              <span style="font-size:11.5px;color:var(--stone-mid);"><?php echo $sub; ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-row-2" id="value-row">
      <div class="form-group">
        <label class="form-label"><span id="value-label">Percentage off</span></label>
        <input type="number" name="value" id="c-value" class="form-input" step="0.01" min="0"
               placeholder="10" value="<?php echo $f('value'); ?>">
      </div>
      <div class="form-group" id="cap-group">
        <label class="form-label">Most it can take off <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(optional)</span></label>
        <input type="number" name="max_discount" id="c-cap" class="form-input" step="0.01" min="0"
               placeholder="e.g. 20000" value="<?php echo $f('max_discount'); ?>">
      </div>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Minimum spend</label>
        <input type="number" name="min_spend" id="c-min" class="form-input" step="0.01" min="0"
               placeholder="0" value="<?php echo $f('min_spend'); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Only this category</label>
        <select name="category_id" class="form-input form-select">
          <option value="">All products</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)($editing['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Starts</label>
        <input type="datetime-local" name="starts_at" class="form-input" value="<?php echo $dtLocal($editing['starts_at'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Expires</label>
        <input type="datetime-local" name="expires_at" id="c-expires" class="form-input" value="<?php echo $dtLocal($editing['expires_at'] ?? ''); ?>">
      </div>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Total uses <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(blank = unlimited)</span></label>
        <input type="number" name="max_uses" id="c-maxuses" class="form-input" min="1"
               placeholder="Unlimited" value="<?php echo $f('max_uses'); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Uses per customer</label>
        <input type="number" name="max_uses_per_customer" id="c-percust" class="form-input" min="0"
               value="<?php echo htmlspecialchars((string)($editing['max_uses_per_customer'] ?? 1)); ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Influencer or channel <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(optional, for your reports)</span></label>
      <input type="text" name="source" id="c-source" class="form-input" maxlength="60"
             placeholder="e.g. Instagram: Ada" value="<?php echo $f('source'); ?>">
    </div>

    <div class="switch-list">
      <label class="switch-row">
        <input type="checkbox" name="first_order_only" id="c-first" value="1" <?php echo $chk('first_order_only'); ?>>
        <span><strong>First order only</strong><br><span style="font-size:11.5px;color:var(--stone-mid);">Customer must be signed in and have never ordered</span></span>
      </label>
      <label class="switch-row">
        <input type="checkbox" name="birthday_only" id="c-bday" value="1" <?php echo $chk('birthday_only'); ?>>
        <span><strong>Birthday only</strong><br><span style="font-size:11.5px;color:var(--stone-mid);">Works within a few days of their birthday</span></span>
      </label>
      <div class="form-group" id="bday-window" style="margin:0 0 0 30px;display:<?php echo !empty($editing['birthday_only']) ? 'block' : 'none'; ?>;">
        <label class="form-label">Days either side of the birthday</label>
        <input type="number" name="birthday_window_days" class="form-input" min="0" style="max-width:120px;"
               value="<?php echo htmlspecialchars((string)($editing['birthday_window_days'] ?? 7)); ?>">
      </div>
      <label class="switch-row">
        <input type="checkbox" name="exclude_express" value="1" <?php echo $editing ? $chk('exclude_express') : 'checked'; ?>>
        <span><strong>Skip Express pieces</strong><br><span style="font-size:11.5px;color:var(--stone-mid);">Made-to-order items are not discounted (recommended)</span></span>
      </label>
      <label class="switch-row">
        <input type="checkbox" name="is_active" value="1" <?php echo $editing ? $chk('is_active') : 'checked'; ?>>
        <span><strong>Active</strong><br><span style="font-size:11.5px;color:var(--stone-mid);">Customers can use it right away</span></span>
      </label>
    </div>

    <button type="submit" class="btn btn-gold btn-full" style="margin-top:18px;">
      <?php echo $editing ? 'Save changes' : 'Create coupon'; ?>
    </button>
  </form>

  <!-- ── List ────────────────────────────────────────────── -->
  <div class="card" style="padding:20px;min-width:0;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 14px;">Your coupons</h2>

    <?php if (!$coupons): ?>
      <p style="font-size:13px;color:var(--stone-mid);margin:0;">No coupons yet. Create your first one on the left.</p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($coupons as $c):
          $expired = !empty($c['expires_at']) && strtotime($c['expires_at']) < time();
          $spent   = !empty($c['max_uses']) && (int)$c['redemptions'] >= (int)$c['max_uses'];
          if     (!$c['is_active']) { $stLabel = 'Off';     $stColor = '#78716C'; }
          elseif ($expired)         { $stLabel = 'Expired'; $stColor = '#EF4444'; }
          elseif ($spent)           { $stLabel = 'Used up'; $stColor = '#D97706'; }
          else                      { $stLabel = 'Live';    $stColor = '#10B981'; }

          if     ($c['type'] === 'percent')       $gives = rtrim(rtrim(number_format((float)$c['value'], 2), '0'), '.') . '% off';
          elseif ($c['type'] === 'fixed')         $gives = formatPrice($c['value']) . ' off';
          else                                    $gives = 'Free shipping';
        ?>
          <div style="border:1px solid var(--cream-dark);border-radius:10px;padding:13px 14px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
              <div style="min-width:0;flex:1;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                  <code style="background:var(--black);color:#fff;padding:3px 9px;border-radius:6px;font-size:12.5px;font-weight:700;letter-spacing:0.06em;"><?php echo htmlspecialchars($c['code']); ?></code>
                  <span style="font-size:12.5px;font-weight:700;color:var(--gold);"><?php echo $gives; ?></span>
                </div>
                <?php if ($c['description']): ?>
                  <div style="font-size:12px;color:var(--stone-mid);margin-top:5px;overflow-wrap:anywhere;"><?php echo htmlspecialchars($c['description']); ?></div>
                <?php endif; ?>
                <div style="font-size:11.5px;color:var(--stone-mid);margin-top:5px;">
                  <?php
                  $bits = [];
                  if ((float)$c['min_spend'] > 0)  $bits[] = 'min ' . formatPrice($c['min_spend']);
                  if (!empty($c['first_order_only'])) $bits[] = 'first order';
                  if (!empty($c['birthday_only']))    $bits[] = 'birthday';
                  if (!empty($c['expires_at']))       $bits[] = 'ends ' . date('j M Y', strtotime($c['expires_at']));
                  if (!empty($c['max_uses']))         $bits[] = (int)$c['max_uses'] . ' total';
                  if (!empty($c['source']))           $bits[] = htmlspecialchars($c['source']);
                  echo $bits ? implode(' &middot; ', $bits) : 'No restrictions';
                  ?>
                </div>
              </div>
              <span style="flex-shrink:0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#fff;background:<?php echo $stColor; ?>;padding:3px 9px;border-radius:99px;"><?php echo $stLabel; ?></span>
            </div>

            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--stone-mid);margin-top:10px;padding-top:10px;border-top:1px solid var(--cream-dark);">
              <span><strong style="color:var(--black);"><?php echo (int)$c['redemptions']; ?></strong> used</span>
              <span><strong style="color:var(--black);"><?php echo formatPrice($c['given_away']); ?></strong> given</span>
              <span><strong style="color:#10B981;"><?php echo formatPrice($c['revenue']); ?></strong> earned</span>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:9px;font-size:12px;">
              <a href="?edit=<?php echo (int)$c['id']; ?>" style="color:var(--gold);font-weight:600;">Edit</a>
              <?php if ((int)$c['redemptions'] > 0): ?>
                <a href="?used=<?php echo (int)$c['id']; ?>" style="color:var(--black);font-weight:600;">See who used it</a>
              <?php endif; ?>
              <a href="?toggle=<?php echo (int)$c['id']; ?>" style="color:var(--stone-mid);font-weight:600;"><?php echo $c['is_active'] ? 'Turn off' : 'Turn on'; ?></a>
              <a href="?delete=<?php echo (int)$c['id']; ?>" onclick="return confirm('Delete <?php echo htmlspecialchars($c['code']); ?>? Past orders keep their discount.')" style="color:#EF4444;">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
.coupon-grid { display:grid; grid-template-columns: 1.05fr 0.95fr; gap:20px; align-items:start; }
.coupon-grid > * { min-width:0; }

.form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

.preset-row { display:flex; flex-wrap:wrap; gap:8px; }
.preset-chip {
  border:1.5px solid var(--cream-dark); background:#fff; color:var(--black);
  border-radius:99px; padding:7px 14px; font-size:12.5px; font-weight:600;
  font-family:inherit; cursor:pointer; transition:all 0.15s;
}
.preset-chip:hover { border-color:var(--gold); color:var(--gold); }

.type-row { display:grid; grid-template-columns:repeat(3,1fr); gap:9px; }
.type-option {
  display:flex; align-items:flex-start; gap:8px; cursor:pointer;
  border:1.5px solid var(--cream-dark); border-radius:10px; padding:11px;
  transition:border-color 0.15s, background 0.15s;
}
.type-option:hover { border-color:var(--gold); }
.type-option.selected { border-color:var(--gold); background:rgba(202,138,4,0.05); }
.type-option input { accent-color:var(--gold); margin-top:2px; flex-shrink:0; }

.switch-list { display:flex; flex-direction:column; gap:11px; margin-top:6px; }
.switch-row {
  display:flex; align-items:flex-start; gap:10px; cursor:pointer;
  font-size:13px; color:var(--black);
}
.switch-row input { accent-color:var(--gold); margin-top:3px; flex-shrink:0; width:16px; height:16px; }

@media (max-width: 1024px) {
  .coupon-grid { grid-template-columns:1fr; }
}
@media (max-width: 560px) {
  .form-row-2 { grid-template-columns:1fr; }
  .type-row { grid-template-columns:1fr; }
  .preset-chip { font-size:12px; padding:7px 12px; }
}
</style>

<script>
(function(){
  // Type switch changes what the value field means
  function syncType(){
    var picked = document.querySelector('input[name="type"]:checked');
    if (!picked) return;
    document.querySelectorAll('.type-option').forEach(function(o){ o.classList.remove('selected'); });
    picked.closest('.type-option').classList.add('selected');

    var t      = picked.value;
    var row    = document.getElementById('value-row');
    var label  = document.getElementById('value-label');
    var cap    = document.getElementById('cap-group');

    row.style.display = (t === 'free_shipping') ? 'none' : 'grid';
    cap.style.display = (t === 'percent') ? 'block' : 'none';
    if (label) label.textContent = (t === 'percent') ? 'Percentage off' : 'Amount off (naira)';
  }
  document.querySelectorAll('input[name="type"]').forEach(function(r){
    r.addEventListener('change', syncType);
  });
  syncType();

  // Birthday window only matters for birthday codes
  var bday = document.getElementById('c-bday');
  if (bday) {
    bday.addEventListener('change', function(){
      document.getElementById('bday-window').style.display = bday.checked ? 'block' : 'none';
    });
  }

  // Ready-made setups
  var presets = {
    welcome:    {code:'WELCOME10', desc:'First order discount',   type:'percent', value:10, min:0,     first:true,  bday:false, percust:1, maxuses:'',    source:'',              days:30},
    shipping:   {code:'FREESHIP',  desc:'Free delivery offer',    type:'free_shipping', value:0, min:0, first:false, bday:false, percust:1, maxuses:'',   source:'',              days:14},
    birthday:   {code:'BIRTHDAY15',desc:'Birthday treat',         type:'percent', value:15, min:0,     first:false, bday:true,  percust:1, maxuses:'',    source:'',              days:0},
    influencer: {code:'ADA10',     desc:'Influencer code',        type:'percent', value:10, min:0,     first:false, bday:false, percust:1, maxuses:'',    source:'Instagram: Ada',days:60},
    winback:    {code:'MISSYOU15', desc:'Win-back offer',         type:'percent', value:15, min:50000, first:false, bday:false, percust:1, maxuses:'',    source:'',              days:21}
  };

  document.querySelectorAll('.preset-chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      var p = presets[chip.dataset.preset];
      if (!p) return;
      document.getElementById('c-code').value    = p.code;
      document.getElementById('c-desc').value    = p.desc;
      document.getElementById('c-value').value   = p.value || '';
      document.getElementById('c-min').value     = p.min || '';
      document.getElementById('c-percust').value = p.percust;
      document.getElementById('c-maxuses').value = p.maxuses;
      document.getElementById('c-source').value  = p.source;
      document.getElementById('c-first').checked = p.first;
      document.getElementById('c-bday').checked  = p.bday;
      document.getElementById('bday-window').style.display = p.bday ? 'block' : 'none';

      var radio = document.querySelector('input[name="type"][value="' + p.type + '"]');
      if (radio) { radio.checked = true; syncType(); }

      // Always give a code an end date so it cannot circulate forever.
      var exp = document.getElementById('c-expires');
      if (exp && p.days > 0) {
        var d = new Date(Date.now() + p.days * 86400000);
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        exp.value = d.toISOString().slice(0,16);
      }
    });
  });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
