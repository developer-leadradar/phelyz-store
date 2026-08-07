<?php
// ── Raw message body, for the preview frame ─────────────────────────────────
//
// This has to answer before a single byte of the admin page is sent. It used
// to sit below the header include, which meant the frame received the entire
// admin page (sidebar, menu button, page title) wrapped around the email, and
// the header() calls below arrived too late to take effect.
//
// Bootstrapped by hand rather than through includes/header.php, because that
// file starts printing HTML immediately.
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (!empty($_GET['view']) && isset($_GET['raw'])) {
    $row = null;
    try {
        $row = getDB()->fetchOne("SELECT body_html FROM email_log WHERE token = ?", [$_GET['view']]);
    } catch (Exception $e) {}

    header('Content-Type: text/html; charset=UTF-8');
    // The stored HTML is ours, but it is still content being replayed: let it
    // load images and inline styles, nothing else.
    header("Content-Security-Policy: default-src 'none'; img-src * data:; style-src 'unsafe-inline'");
    header('X-Frame-Options: SAMEORIGIN');

    echo ($row && $row['body_html'])
        ? $row['body_html']
        : '<p style="font-family:sans-serif;color:#888;padding:20px;">No copy was stored for this message.</p>';
    exit;
}

$pageTitle = "Email Log";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/email-campaigns.php';

$db    = getDB();
$ready = true;
try { $db->fetchOne("SELECT 1 FROM email_log LIMIT 1"); }
catch (Exception $e) { $ready = false; }

// ── One message, in full ────────────────────────────────────────────────────
$viewing = null;
if ($ready && !empty($_GET['view'])) {
    $viewing = $db->fetchOne("SELECT * FROM email_log WHERE token = ?", [$_GET['view']]);
}

// ── Filters ─────────────────────────────────────────────────────────────────
$q        = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$status   = $_GET['status'] ?? '';
$days     = isset($_GET['days']) ? max(0, (int)$_GET['days']) : 30;
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 50;

$where = []; $params = [];
if ($q !== '') {
    $where[]  = '(to_email LIKE ? OR subject LIKE ? OR to_name LIKE ? OR token = ?)';
    $like     = '%' . $q . '%';
    array_push($params, $like, $like, $like, $q);
}
if (in_array($category, ['transactional','campaign','automation','admin','other'], true)) {
    $where[] = 'category = ?'; $params[] = $category;
}
if (in_array($status, ['sent','failed'], true)) { $where[] = 'status = ?'; $params[] = $status; }
if ($status === 'opened')   { $where[] = 'opened_at IS NOT NULL'; }
if ($status === 'unopened') { $where[] = 'opened_at IS NULL AND status = "sent"'; }
if ($days > 0) { $where[] = 'created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'; $params[] = $days; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = []; $total = 0; $stats = ['sent'=>0,'failed'=>0,'opened'=>0,'people'=>0];
if ($ready) {
    try {
        $total = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM email_log $whereSql", $params)['c'] ?? 0);
        $rows  = $db->fetchAll(
            "SELECT * FROM email_log $whereSql ORDER BY created_at DESC, id DESC
             LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);

        // Headline figures use the same window as the list, not all time, so
        // the numbers always describe what is on screen.
        $s = $db->fetchOne(
            "SELECT SUM(status='sent') AS sent, SUM(status='failed') AS failed,
                    SUM(opened_at IS NOT NULL) AS opened, COUNT(DISTINCT to_email) AS people
             FROM email_log $whereSql", $params);
        $stats = ['sent'=>(int)($s['sent']??0), 'failed'=>(int)($s['failed']??0),
                  'opened'=>(int)($s['opened']??0), 'people'=>(int)($s['people']??0)];
    } catch (Exception $e) { $rows = []; }
}
$totalPages = max(1, (int)ceil($total / $perPage));
$openRate   = $stats['sent'] > 0 ? ($stats['opened'] / $stats['sent']) * 100 : 0;

// Everything ever sent to the person being viewed, so one click shows their
// whole history with the shop.
$thread = [];
if ($viewing) {
    try {
        $thread = $db->fetchAll(
            "SELECT token, subject, category, status, opened_at, created_at
             FROM email_log WHERE to_email = ? ORDER BY created_at DESC LIMIT 40",
            [$viewing['to_email']]);
    } catch (Exception $e) {}
}

function catBadge($c) {
    $map = ['transactional'=>['Transactional','#0EA5E9'], 'campaign'=>['Campaign','#8B5CF6'],
            'automation'=>['Automated','#10B981'], 'admin'=>['Admin','#78716C'], 'other'=>['Other','#A8A29E']];
    [$label, $colour] = $map[$c] ?? ['Other', '#A8A29E'];
    return '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'
         . 'color:#fff;background:' . $colour . ';padding:2px 8px;border-radius:99px;white-space:nowrap;">'
         . $label . '</span>';
}
function prettySource($s) {
    return $s ? ucwords(str_replace('_', ' ', $s)) : '-';
}
function qs(array $over = []) {
    return htmlspecialchars(http_build_query(array_merge($_GET, $over)));
}
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-page-title">Email Log</h1>
    <p style="font-size:13px;color:var(--stone-mid);margin:4px 0 0;">
      Every message the store has ever sent, why it was sent, and whether it was opened.
    </p>
  </div>
  <a href="email-campaigns.php" class="btn btn-outline" style="font-size:13px;">Back to campaigns</a>
</div>

<?php if (!$ready): ?>
  <div class="alert alert-error" style="margin-bottom:18px;">
    The log table is missing. Run <strong>migrations/add_email_log.sql</strong> once, then reload.
  </div>
<?php endif; ?>

<?php if ($viewing): ?>
<!-- ══ One message ═══════════════════════════════════════ -->
<div class="log-detail">
  <div class="card" style="padding:0;overflow:hidden;min-width:0;">
    <div style="padding:18px 20px;border-bottom:1px solid var(--cream-dark);">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <div style="min-width:0;flex:1;">
          <div style="font-family:'Cormorant',serif;font-size:19px;font-weight:700;color:var(--black);overflow-wrap:anywhere;">
            <?php echo htmlspecialchars($viewing['subject'] ?? ''); ?>
          </div>
          <div style="font-size:12.5px;color:var(--stone-mid);margin-top:5px;overflow-wrap:anywhere;">
            To <strong style="color:var(--black);"><?php echo htmlspecialchars($viewing['to_email'] ?? ''); ?></strong>
            &middot; <?php echo date('j M Y, g:ia', strtotime($viewing['created_at'])); ?>
          </div>
        </div>
        <a href="email-log.php" style="font-size:12.5px;color:var(--stone-mid);flex-shrink:0;">Close</a>
      </div>

      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:11px;align-items:center;">
        <?php echo catBadge($viewing['category']); ?>
        <span style="font-size:11.5px;color:var(--stone-mid);"><?php echo htmlspecialchars(prettySource($viewing['source_type'])); ?></span>
        <?php if ($viewing['status'] === 'failed'): ?>
          <span style="font-size:10px;font-weight:700;text-transform:uppercase;color:#fff;background:#EF4444;padding:2px 8px;border-radius:99px;">Failed</span>
        <?php elseif ($viewing['opened_at']): ?>
          <span style="font-size:10px;font-weight:700;text-transform:uppercase;color:#fff;background:#15803D;padding:2px 8px;border-radius:99px;">
            Opened <?php echo (int)$viewing['open_count']; ?>x
          </span>
        <?php else: ?>
          <span style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--stone-mid);border:1px solid var(--cream-dark);padding:2px 8px;border-radius:99px;">Not opened</span>
        <?php endif; ?>
        <?php if (!$viewing['was_subscribed']): ?>
          <span style="font-size:10px;font-weight:700;text-transform:uppercase;color:#92400E;background:#FEF3C7;padding:2px 8px;border-radius:99px;">Unsubscribed</span>
        <?php endif; ?>
      </div>

      <?php if ($viewing['error']): ?>
        <div style="margin-top:11px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 12px;font-size:12.5px;color:#B91C1C;overflow-wrap:anywhere;">
          <?php echo htmlspecialchars($viewing['error'] ?? ''); ?>
        </div>
      <?php endif; ?>

      <div style="font-size:11.5px;color:var(--stone-mid);margin-top:11px;">
        Reference <code style="background:var(--cream-dark);padding:2px 7px;border-radius:5px;"><?php echo htmlspecialchars($viewing['token'] ?? ''); ?></code>
        <?php if ($viewing['transport']): ?> &middot; sent via <?php echo htmlspecialchars($viewing['transport'] ?? ''); ?><?php endif; ?>
        <?php if ($viewing['opened_at']): ?> &middot; first opened <?php echo date('j M, g:ia', strtotime($viewing['opened_at'])); ?><?php endif; ?>
      </div>
    </div>

    <!-- The message exactly as it was received -->
    <iframe src="?view=<?php echo urlencode($viewing['token'] ?? ''); ?>&raw=1"
            title="Message body" style="width:100%;height:620px;border:0;background:#F5F5F4;display:block;"></iframe>
  </div>

  <div class="card" style="padding:20px;min-width:0;">
    <h2 style="font-size:14px;font-weight:700;margin:0 0 4px;">Everything sent to this address</h2>
    <p style="font-size:12px;color:var(--stone-mid);margin:0 0 12px;overflow-wrap:anywhere;"><?php echo htmlspecialchars($viewing['to_email'] ?? ''); ?></p>
    <div style="display:flex;flex-direction:column;gap:7px;">
      <?php foreach ($thread as $t): $isThis = $t['token'] === $viewing['token']; ?>
        <a href="?view=<?php echo urlencode($t['token'] ?? ''); ?>"
           style="display:block;text-decoration:none;border:1px solid <?php echo $isThis ? 'var(--gold)' : 'var(--cream-dark)'; ?>;
                  background:<?php echo $isThis ? 'rgba(202,138,4,.05)' : '#fff'; ?>;border-radius:9px;padding:9px 11px;">
          <div style="font-size:12.5px;font-weight:600;color:var(--black);overflow-wrap:anywhere;"><?php echo htmlspecialchars($t['subject'] ?? ''); ?></div>
          <div style="font-size:11px;color:var(--stone-mid);margin-top:3px;">
            <?php echo date('j M Y', strtotime($t['created_at'])); ?>
            &middot; <?php echo htmlspecialchars(ucfirst($t['category'] ?? '')); ?>
            <?php if ($t['status'] === 'failed'): ?>
              &middot; <span style="color:#B91C1C;font-weight:700;">failed</span>
            <?php elseif ($t['opened_at']): ?>
              &middot; <span style="color:#15803D;font-weight:700;">opened</span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══ The master list ═══════════════════════════════════ -->

<div class="traffic-kpis">
  <?php foreach ([
    ['Delivered',   number_format($stats['sent'] ?? 0),   '#10B981'],
    ['Failed',      number_format($stats['failed'] ?? 0), $stats['failed'] > 0 ? '#EF4444' : '#A8A29E'],
    ['Opened',      number_format($stats['opened'] ?? 0) . ' (' . number_format($openRate, 0) . '%)', '#0EA5E9'],
    ['People',      number_format($stats['people'] ?? 0), '#CA8A04'],
  ] as [$label, $value, $colour]): ?>
    <div class="traffic-kpi">
      <div class="traffic-kpi-icon" style="background:<?php echo $colour; ?>1F;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="<?php echo $colour; ?>" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
      </div>
      <div style="min-width:0;">
        <div class="traffic-kpi-num" style="font-size:17px;"><?php echo $value; ?></div>
        <div class="traffic-kpi-lbl"><?php echo $label; ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<p style="font-size:12px;color:var(--stone-mid);margin:-6px 0 16px;">
  Opens are a floor, not a measurement: many mail apps block the tracking image, so a message can be
  read without ever registering here.
</p>

<form method="GET" class="card log-filters">
  <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" class="form-input"
         placeholder="Search email, subject, name or reference">
  <select name="category" class="form-input form-select">
    <option value="">Every kind</option>
    <?php foreach (['transactional'=>'Transactional','campaign'=>'Campaigns','automation'=>'Automated','admin'=>'Admin'] as $k=>$v): ?>
      <option value="<?php echo $k; ?>" <?php echo $category===$k?'selected':''; ?>><?php echo $v; ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" class="form-input form-select">
    <option value="">Any outcome</option>
    <?php foreach (['sent'=>'Delivered','failed'=>'Failed','opened'=>'Opened','unopened'=>'Not opened'] as $k=>$v): ?>
      <option value="<?php echo $k; ?>" <?php echo $status===$k?'selected':''; ?>><?php echo $v; ?></option>
    <?php endforeach; ?>
  </select>
  <select name="days" class="form-input form-select">
    <?php foreach ([7=>'Last 7 days',30=>'Last 30 days',90=>'Last 90 days',365=>'Last year',0=>'All time'] as $k=>$v): ?>
      <option value="<?php echo $k; ?>" <?php echo $days===$k?'selected':''; ?>><?php echo $v; ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-gold" style="font-size:13px;">Filter</button>
  <a href="email-log.php" class="btn btn-outline" style="font-size:13px;">Clear</a>
</form>

<div class="card" style="padding:0;overflow:hidden;">
  <?php if (!$rows): ?>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;padding:24px;">
      <?php echo $ready ? 'No messages match that. Every email the store sends from now on appears here.' : 'Run the migration to start recording.'; ?>
    </p>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table" style="min-width:760px;">
        <thead>
          <tr><th>When</th><th>To</th><th>Subject</th><th>Kind</th><th>Why</th><th>Outcome</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td style="white-space:nowrap;font-size:12.5px;color:var(--stone-mid);">
              <?php echo date('j M', strtotime($r['created_at'])); ?><br>
              <span style="font-size:11px;"><?php echo date('g:ia', strtotime($r['created_at'])); ?></span>
            </td>
            <td style="font-size:12.5px;overflow-wrap:anywhere;max-width:190px;">
              <?php if ($r['to_name']): ?>
                <div style="font-weight:600;color:var(--black);"><?php echo htmlspecialchars($r['to_name'] ?? ''); ?></div>
              <?php endif; ?>
              <?php echo htmlspecialchars($r['to_email'] ?? ''); ?>
              <?php if (!$r['was_subscribed']): ?>
                <div style="font-size:10.5px;color:#92400E;font-weight:700;">unsubscribed</div>
              <?php endif; ?>
            </td>
            <td style="font-size:12.5px;overflow-wrap:anywhere;max-width:250px;"><?php echo htmlspecialchars($r['subject'] ?? ''); ?></td>
            <td><?php echo catBadge($r['category']); ?></td>
            <td style="font-size:12px;color:var(--stone-mid);white-space:nowrap;">
              <?php echo htmlspecialchars(prettySource($r['source_type'])); ?>
              <?php if ($r['audience']): ?><br><span style="font-size:10.5px;"><?php echo htmlspecialchars($r['audience'] ?? ''); ?></span><?php endif; ?>
            </td>
            <td style="white-space:nowrap;font-size:12px;">
              <?php if ($r['status'] === 'failed'): ?>
                <span style="color:#B91C1C;font-weight:700;">Failed</span>
              <?php elseif ($r['opened_at']): ?>
                <span style="color:#15803D;font-weight:700;">Opened</span>
                <?php if ((int)$r['open_count'] > 1): ?><span style="color:var(--stone-mid);"> x<?php echo (int)$r['open_count']; ?></span><?php endif; ?>
              <?php else: ?>
                <span style="color:var(--stone-mid);">Delivered</span>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap;"><a href="?view=<?php echo urlencode($r['token'] ?? ''); ?>" style="color:var(--gold);font-weight:600;font-size:12.5px;">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 18px;border-top:1px solid var(--cream-dark);flex-wrap:wrap;">
        <span style="font-size:12.5px;color:var(--stone-mid);">
          <?php echo number_format($total); ?> messages &middot; page <?php echo $page; ?> of <?php echo $totalPages; ?>
        </span>
        <div style="display:flex;gap:8px;">
          <?php if ($page > 1): ?><a href="?<?php echo qs(['page'=>$page-1]); ?>" class="btn btn-outline" style="font-size:12px;padding:6px 12px;">Previous</a><?php endif; ?>
          <?php if ($page < $totalPages): ?><a href="?<?php echo qs(['page'=>$page+1]); ?>" class="btn btn-outline" style="font-size:12px;padding:6px 12px;">Next</a><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<style>
.log-filters { display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto auto; gap:9px; padding:14px; margin-bottom:16px; align-items:center; }
.log-filters .form-input { font-size:13px; padding:9px 11px; }
.log-detail { display:grid; grid-template-columns:1.55fr .95fr; gap:18px; align-items:start; }
.log-detail > * { min-width:0; }
@media (max-width:1100px){ .log-detail { grid-template-columns:1fr; } }
@media (max-width:860px){ .log-filters { grid-template-columns:1fr 1fr; } .log-filters .btn { width:100%; } }
@media (max-width:520px){ .log-filters { grid-template-columns:1fr; } }
</style>

<?php require_once 'includes/footer.php'; ?>
