<?php
$pageTitle = "Email Automations";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/automations.php';

$db      = getDB();
$success = '';
$error   = '';

$ready = automationEnsureRows();
if (!$ready) {
    $error = 'The automation tables are missing. Run migrations/add_automation_and_expenses.sql once, then reload.';
}

// ── Toggle on/off ───────────────────────────────────────────────────────────
if ($ready && isset($_GET['toggle'])) {
    try {
        $db->query("UPDATE email_automations SET is_active = 1 - is_active WHERE automation_key = ?", [$_GET['toggle']]);
        $success = 'Automation updated.';
    } catch (Exception $e) { $error = 'Could not update that automation.'; }
}

// ── Save copy / timing ──────────────────────────────────────────────────────
if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['automation_key'])) {
    try {
        $db->update('email_automations', [
            'subject'     => trim($_POST['subject'] ?? ''),
            'heading'     => trim($_POST['heading'] ?? ''),
            'body'        => trim($_POST['body'] ?? ''),
            'cta_text'    => trim($_POST['cta_text'] ?? ''),
            'cta_url'     => trim($_POST['cta_url'] ?? ''),
            'delay_hours' => max(0, (int)($_POST['delay_hours'] ?? 24)),
            'coupon_code' => trim($_POST['coupon_code'] ?? '') ?: null,
        ], 'automation_key = ?', [$_POST['automation_key']]);
        $success = 'Saved.';
    } catch (Exception $e) { $error = 'Could not save that.'; }
}

$editing = null;
if ($ready && isset($_GET['edit'])) {
    $editing = $db->fetchOne("SELECT * FROM email_automations WHERE automation_key = ?", [$_GET['edit']]);
}

$automations = [];
if ($ready) {
    try { $automations = $db->fetchAll("SELECT * FROM email_automations ORDER BY id ASC"); }
    catch (Exception $e) { $automations = []; }
}

// Upcoming seasonal moments, so the admin can see what is worth writing next.
$seasons  = campaignSeasons();
$upcoming = [];
foreach ($seasons as $key => $s) {
    if (empty($s['when'])) { $upcoming[] = ['key'=>$key,'label'=>$s['label'],'date'=>null,'days'=>null]; continue; }
    $thisYear = date('Y') . '-' . $s['when'];
    $ts = strtotime($thisYear);
    if ($ts < strtotime('today')) $ts = strtotime((date('Y') + 1) . '-' . $s['when']);
    $upcoming[] = ['key'=>$key,'label'=>$s['label'],'date'=>$ts,
                   'days'=>(int)floor(($ts - strtotime('today')) / 86400)];
}
usort($upcoming, function ($a, $b) {
    if ($a['days'] === null) return 1;
    if ($b['days'] === null) return -1;
    return $a['days'] <=> $b['days'];
});

$cronToken = getenv('CRON_TOKEN') ?: '';
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-page-title">Email Automations</h1>
    <p style="font-size:13px;color:var(--stone-mid);margin:4px 0 0;">
      Emails that send themselves when a customer does something, plus your festive calendar.
    </p>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:18px;"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error" style="margin-bottom:18px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if ($editing): ?>
<!-- ── Edit one automation ─────────────────────────────── -->
<form method="POST" class="card" style="padding:22px;margin-bottom:20px;">
  <input type="hidden" name="automation_key" value="<?php echo htmlspecialchars($editing['automation_key']); ?>">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;">
    <h2 style="font-size:15px;font-weight:700;margin:0;"><?php echo htmlspecialchars($editing['label']); ?></h2>
    <a href="automations.php" style="font-size:12.5px;color:var(--stone-mid);">Cancel</a>
  </div>

  <div class="form-group">
    <label class="form-label">Subject line</label>
    <input type="text" name="subject" class="form-input" required maxlength="255" value="<?php echo htmlspecialchars($editing['subject']); ?>">
  </div>
  <div class="form-group">
    <label class="form-label">Headline</label>
    <input type="text" name="heading" class="form-input" maxlength="255" value="<?php echo htmlspecialchars($editing['heading']); ?>">
  </div>
  <div class="form-group">
    <label class="form-label">Message</label>
    <textarea name="body" class="form-input" rows="8" required><?php echo htmlspecialchars($editing['body']); ?></textarea>
    <p style="font-size:12px;color:var(--stone-mid);margin:6px 0 0;">
      <code style="background:var(--cream-dark);padding:1px 5px;border-radius:4px;">{name}</code> becomes their first name.
    </p>
  </div>
  <div class="auto-row-3">
    <div class="form-group">
      <label class="form-label">Button text</label>
      <input type="text" name="cta_text" class="form-input" maxlength="100" value="<?php echo htmlspecialchars($editing['cta_text']); ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Button link</label>
      <input type="url" name="cta_url" class="form-input" maxlength="500" value="<?php echo htmlspecialchars($editing['cta_url']); ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Include coupon</label>
      <input type="text" name="coupon_code" class="form-input" maxlength="50" placeholder="Optional" value="<?php echo htmlspecialchars($editing['coupon_code'] ?? ''); ?>">
    </div>
  </div>
  <div class="form-group">
    <label class="form-label">Send this many hours after the trigger</label>
    <input type="number" name="delay_hours" class="form-input" min="0" style="max-width:160px;" value="<?php echo (int)$editing['delay_hours']; ?>">
  </div>

  <button type="submit" class="btn btn-gold">Save changes</button>
</form>
<?php endif; ?>

<div class="auto-grid">

  <!-- ── The automations ─────────────────────────────────── -->
  <div class="card" style="padding:20px;min-width:0;">
    <h2 style="font-size:15px;font-weight:700;margin:0 0 4px;">Triggered emails</h2>
    <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 14px;">
      Each one is off until you switch it on. Nothing sends between 9pm and 8am, and nobody gets more than one automated email a day.
    </p>

    <?php if (!$automations): ?>
      <p style="font-size:13px;color:var(--stone-mid);margin:0;">Run the migration to set these up.</p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:11px;">
        <?php foreach ($automations as $a): ?>
          <div style="border:1px solid var(--cream-dark);border-radius:10px;padding:13px 14px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
              <div style="min-width:0;flex:1;">
                <div style="font-weight:700;font-size:13.5px;color:var(--black);"><?php echo htmlspecialchars($a['label']); ?></div>
                <div style="font-size:12px;color:var(--stone-mid);margin-top:3px;"><?php echo htmlspecialchars($a['description']); ?></div>
              </div>
              <span style="flex-shrink:0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#fff;
                           background:<?php echo $a['is_active'] ? '#10B981' : '#A8A29E'; ?>;padding:3px 9px;border-radius:99px;">
                <?php echo $a['is_active'] ? 'On' : 'Off'; ?>
              </span>
            </div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--stone-mid);margin-top:9px;padding-top:9px;border-top:1px solid var(--cream-dark);">
              <span>after <strong style="color:var(--black);"><?php echo (int)$a['delay_hours']; ?>h</strong></span>
              <span><strong style="color:var(--black);"><?php echo (int)$a['sent_count']; ?></strong> sent</span>
              <?php if (!empty($a['coupon_code'])): ?><span>code <strong style="color:var(--gold);"><?php echo htmlspecialchars($a['coupon_code']); ?></strong></span><?php endif; ?>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:9px;font-size:12px;">
              <a href="?edit=<?php echo urlencode($a['automation_key']); ?>" style="color:var(--gold);font-weight:600;">Edit wording</a>
              <a href="?toggle=<?php echo urlencode($a['automation_key']); ?>" style="color:var(--stone-mid);font-weight:600;">
                <?php echo $a['is_active'] ? 'Turn off' : 'Turn on'; ?>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Festive calendar + cron setup ───────────────────── -->
  <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

    <div class="card" style="padding:20px;">
      <h2 style="font-size:15px;font-weight:700;margin:0 0 4px;">Festive calendar</h2>
      <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 14px;">
        Write the campaign now, set the date, and it sends itself on the day.
      </p>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($upcoming as $u): ?>
          <div style="display:flex;align-items:center;gap:10px;border:1px solid var(--cream-dark);border-radius:9px;padding:10px 12px;">
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:13px;color:var(--black);"><?php echo htmlspecialchars($u['label']); ?></div>
              <div style="font-size:11.5px;color:var(--stone-mid);">
                <?php
                if ($u['date'] === null) echo 'Date moves each year, set it yourself';
                elseif ($u['days'] === 0) echo 'Today';
                else echo date('j M Y', $u['date']) . ' &middot; in ' . $u['days'] . ' day' . ($u['days'] === 1 ? '' : 's');
                ?>
              </div>
            </div>
            <a href="email-campaigns.php?season=<?php echo urlencode($u['key']); ?>"
               class="btn btn-outline" style="flex-shrink:0;font-size:12px;padding:6px 12px;">Write it</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php
    // Has the scheduler actually run? The cron script leaves a timestamp, so
    // this can report the truth instead of assuming.
    $stampFile = __DIR__ . '/../data/cron-last-run.txt';
    $lastRun   = is_file($stampFile) ? @strtotime(trim(@file_get_contents($stampFile))) : null;
    $mins      = $lastRun ? floor((time() - $lastRun) / 60) : null;
    $healthy   = $lastRun && $mins <= 90;
    ?>
    <div class="card" style="padding:20px;">
      <div style="display:flex;align-items:center;gap:9px;margin-bottom:10px;">
        <span style="width:9px;height:9px;border-radius:50%;flex-shrink:0;
                     background:<?php echo $healthy ? '#10B981' : ($lastRun ? '#D97706' : '#A8A29E'); ?>;"></span>
        <h2 style="font-size:15px;font-weight:700;margin:0;">Scheduler</h2>
      </div>

      <?php if ($healthy): ?>
        <p style="font-size:12.5px;color:var(--stone-mid);line-height:1.65;margin:0;">
          Running normally. Last checked
          <strong style="color:var(--black);"><?php echo $mins <= 1 ? 'a moment ago' : $mins . ' minutes ago'; ?></strong>,
          and it looks again every 30 minutes.
          <br><br>
          Whatever you switch on above starts going out from the next check. Nothing sends between
          9pm and 8am, and no customer gets more than one automated email a day.
        </p>
      <?php elseif ($lastRun): ?>
        <p style="font-size:12.5px;color:#92400E;line-height:1.65;margin:0;">
          The schedule last ran <strong><?php echo $mins; ?> minutes ago</strong>, which is longer than expected.
          It should run every 30 minutes. Worth checking <strong>Cron Jobs</strong> in cPanel is still enabled.
        </p>
      <?php else: ?>
        <p style="font-size:12.5px;color:var(--stone-mid);line-height:1.65;margin:0 0 10px;">
          The cron job is installed and set to run every 30 minutes, but it has not reported in yet.
          Give it half an hour and this will turn green. If it stays grey, check <strong>Cron Jobs</strong> in cPanel
          still lists this command:
        </p>
        <code style="display:block;background:var(--black);color:#E7E5E4;padding:11px 13px;border-radius:9px;
                     font-size:11px;overflow-x:auto;white-space:pre;line-height:1.6;">/usr/local/bin/php -q /home/cimedgec/repositories/phelyz-store/cron/run-automations.php</code>
      <?php endif; ?>
    </div>

  </div>
</div>

<style>
.auto-grid { display:grid; grid-template-columns:1.05fr 0.95fr; gap:20px; align-items:start; }
.auto-grid > * { min-width:0; }
.auto-row-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
@media (max-width:1024px){ .auto-grid { grid-template-columns:1fr; } }
@media (max-width:640px){ .auto-row-3 { grid-template-columns:1fr; } }
</style>

<?php require_once 'includes/footer.php'; ?>
