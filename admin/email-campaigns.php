<?php
$pageTitle = "Email Campaigns";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/email-campaigns.php';

$db      = getDB();
$success = '';
$error   = '';

$templates = campaignTemplates();
$audiences = campaignAllAudiences();

// Arriving from the coupon page with a group already chosen.
$presetAudience = isset($_GET['audience']) && isset($audiences[$_GET['audience']]) ? $_GET['audience'] : '';

// Arriving from the festive calendar: pre-fill the send date with that
// occasion, a little ahead of the day itself so it lands in good time.
$presetSeason = '';
$presetWhen   = '';
$seasonDraft  = null;
if (!empty($_GET['season'])) {
    require_once __DIR__ . '/../includes/automations.php';
    $seasons = campaignSeasons();
    $key     = $_GET['season'];
    if (isset($seasons[$key])) {
        $presetSeason = $key;
        $seasonDraft  = $seasons[$key];
        if (!empty($seasons[$key]['when'])) {
            $ts = strtotime(date('Y') . '-' . $seasons[$key]['when']);
            if ($ts < time()) $ts = strtotime((date('Y') + 1) . '-' . $seasons[$key]['when']);
            $presetWhen = date('Y-m-d\T09:00', strtotime('-3 days', $ts));
        }
    }
}

/** Value for a compose field: what was posted, else the festive draft, else blank. */
function composeValue($postKey, $draftKey, $seasonDraft) {
    if (isset($_POST[$postKey])) return $_POST[$postKey];
    return $seasonDraft[$draftKey] ?? '';
}

// ── Send one batch (called by the progress loop on this page) ───────────────
if (isset($_GET['batch'])) {
    header('Content-Type: application/json');
    echo json_encode(campaignSendBatch((int)$_GET['batch']));
    exit;
}

// ── Delete a campaign ───────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    try {
        $db->query("DELETE FROM email_campaigns WHERE id = ?", [(int)$_GET['delete']]);
        $success = 'Campaign deleted.';
    } catch (Exception $e) {
        $error = 'Could not delete that campaign.';
    }
}

// ── Form actions ────────────────────────────────────────────────────────────
$startCampaignId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $subject  = trim($_POST['subject'] ?? '');
    $heading  = trim($_POST['heading'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $ctaText  = trim($_POST['cta_text'] ?? '');
    $ctaUrl   = trim($_POST['cta_url'] ?? '');
    $audience = $_POST['audience'] ?? 'all';

    if ($subject === '' || $body === '') {
        $error = 'A subject and a message are both required.';
    } elseif (!isset($audiences[$audience])) {
        $error = 'Please choose who should receive this.';
    } elseif ($action === 'test') {
        // Test send goes to the logged-in admin only.
        $testTo = $_SESSION['user_email'] ?? '';
        if ($testTo === '') {
            $error = 'No admin email on this account to send a test to.';
        } else {
            $fake = ['subject'=>$subject,'heading'=>$heading,'body'=>$body,'cta_text'=>$ctaText,'cta_url'=>$ctaUrl];
            $html = campaignRenderEmail($fake, ($_SESSION['user_name'] ?? 'there'), $testTo);
            emailContext(['category'=>'admin','source_type'=>'campaign_test','audience'=>$audience]);
            if (sendEmail($testTo, '[TEST] ' . $subject, $html)) {
                $success = 'Test email sent to ' . htmlspecialchars($testTo) . '. Check it looks right before sending to everyone.';
            } else {
                $error = 'Could not send the test email. Check the mail settings.';
            }
        }
    } elseif ($action === 'schedule') {
        // Saved as a draft with a date on it. The cron job picks it up when the
        // time comes, so a Christmas email does not need somebody at a laptop
        // on Christmas morning.
        $when = trim($_POST['scheduled_at'] ?? '');
        if ($when === '') {
            $error = 'Pick the date and time it should go out.';
        } elseif (strtotime($when) === false || strtotime($when) < time()) {
            $error = 'That send time is in the past.';
        } else {
            try {
                $db->insert('email_campaigns', [
                    'subject'      => $subject,
                    'heading'      => $heading,
                    'body'         => $body,
                    'cta_text'     => $ctaText,
                    'cta_url'      => $ctaUrl,
                    'audience'     => $audience,
                    'status'       => 'draft',
                    'scheduled_at' => date('Y-m-d H:i:s', strtotime($when)),
                    'season_key'   => trim($_POST['season_key'] ?? '') ?: null,
                ]);
                $success = 'Scheduled for ' . date('j M Y, g:ia', strtotime($when))
                         . '. Leave it with us, the cron job sends it.';
            } catch (Exception $e) {
                $error = 'Could not schedule that. Run migrations/add_automation_and_expenses.sql first.';
            }
        }
    } elseif ($action === 'send') {
        $recipients = campaignRecipientsFor($audience);
        if (!$recipients) {
            $error = 'Nobody matches that audience right now, so there is nothing to send.';
        } else {
            try {
                $campaignId = $db->insert('email_campaigns', [
                    'subject'          => $subject,
                    'heading'          => $heading,
                    'body'             => $body,
                    'cta_text'         => $ctaText,
                    'cta_url'          => $ctaUrl,
                    'audience'         => $audience,
                    'status'           => 'sending',
                    'total_recipients' => count($recipients),
                ]);
                foreach ($recipients as $r) {
                    try {
                        $db->insert('email_campaign_recipients', [
                            'campaign_id' => $campaignId,
                            'user_id'     => $r['id'] ?? null,
                            'email'       => $r['email'],
                            'first_name'  => $r['first_name'] ?? '',
                        ]);
                    } catch (Exception $e) {
                        // Duplicate address in the same campaign, skip it.
                    }
                }
                $startCampaignId = (int)$campaignId;
                $success = 'Sending to ' . count($recipients) . ' ' . (count($recipients) === 1 ? 'person' : 'people') . '. Keep this page open until it finishes.';
            } catch (Exception $e) {
                $error = 'Could not start the campaign. Run migrations/add_email_campaigns.sql first.';
            }
        }
    }
}

// ── Data for the page ───────────────────────────────────────────────────────
$counts = [];
foreach ($audiences as $key => $a) { $counts[$key] = campaignAudienceCount($key); }

try {
    $history = $db->fetchAll("SELECT * FROM email_campaigns ORDER BY created_at DESC LIMIT 25");
} catch (Exception $e) {
    $history = [];
    if (!$error) $error = 'The campaign tables are missing. Run migrations/add_email_campaigns.sql once, then reload.';
}

try {
    $optOutRow = $db->fetchOne("SELECT COUNT(*) AS c FROM email_unsubscribes");
    $optOuts   = (int)($optOutRow['c'] ?? 0);
} catch (Exception $e) { $optOuts = 0; }

// People captured by the welcome popup. They gave a WhatsApp number too, which
// is worth having in front of you: for a shop this size a message often lands
// better than an email.
$leads = [];
$leadCount = 0;
try {
    $leads = $db->fetchAll(
        "SELECT l.*,
                EXISTS (SELECT 1 FROM users u
                        WHERE u.email = l.email
                          AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)) AS has_ordered
         FROM leads l ORDER BY l.created_at DESC LIMIT 100"
    );
    $leadCount = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM leads")['c'] ?? 0);
} catch (Exception $e) { $leads = []; }
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-page-title">Email Campaigns</h1>
    <p style="font-size:13px;color:var(--stone-mid);margin:4px 0 0;">
      Write once, send to your customers. Pick a ready-made message or start from scratch.
    </p>
  </div>
  <a href="email-log.php" target="_blank" rel="noopener" class="btn btn-outline" style="font-size:13px;gap:6px;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
    Email log
  </a>
</div>

<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:18px;"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error" style="margin-bottom:18px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Live progress while a campaign sends -->
<div id="send-progress" style="display:none;margin-bottom:20px;" class="card">
  <div style="padding:18px 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;flex-wrap:wrap;">
      <strong style="font-size:14px;">Sending your campaign</strong>
      <span id="progress-label" style="font-size:13px;color:var(--stone-mid);">Starting...</span>
    </div>
    <div style="height:10px;background:var(--cream-dark);border-radius:99px;overflow:hidden;">
      <div id="progress-bar" style="height:100%;width:0;background:linear-gradient(90deg,#CA8A04,#D97706);transition:width 0.3s;"></div>
    </div>
    <p style="font-size:12px;color:var(--stone-mid);margin:10px 0 0;">
      Please keep this page open. Emails go out in small batches so your host does not block them.
    </p>
  </div>
</div>

<div class="campaign-grid">

  <!-- ── Compose ─────────────────────────────────────────── -->
  <form method="POST" class="card" id="campaign-form" style="padding:22px;">
    <input type="hidden" name="action" id="form-action" value="send">

    <h2 style="font-size:15px;font-weight:700;margin:0 0 4px;">1. Choose a message</h2>
    <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 14px;">Pick a starting point, then edit the wording however you like.</p>

    <div class="template-picker">
      <?php foreach ($templates as $key => $t): ?>
        <button type="button" class="template-chip<?php echo $key === 'blank' ? ' is-blank' : ''; ?>"
                data-key="<?php echo $key; ?>"
                data-subject="<?php echo htmlspecialchars($t['subject'] ?? ''); ?>"
                data-heading="<?php echo htmlspecialchars($t['heading'] ?? ''); ?>"
                data-body="<?php echo htmlspecialchars($t['body'] ?? ''); ?>"
                data-cta="<?php echo htmlspecialchars($t['cta'] ?? ''); ?>"
                data-url="<?php echo htmlspecialchars($t['url'] ?? ''); ?>">
          <?php echo htmlspecialchars($t['name'] ?? ''); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <h2 style="font-size:15px;font-weight:700;margin:22px 0 12px;">2. Write it</h2>

    <div class="form-group">
      <label class="form-label">Subject line</label>
      <input type="text" name="subject" id="f-subject" class="form-input" maxlength="255" required
             placeholder="Just in: new pieces at Phelyz Store"
             value="<?php echo htmlspecialchars(composeValue('subject', 'subject', $seasonDraft)); ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Headline <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(shown at the top of the email, optional)</span></label>
      <input type="text" name="heading" id="f-heading" class="form-input" maxlength="255"
             placeholder="Fresh from the workshop"
             value="<?php echo htmlspecialchars(composeValue('heading', 'heading', $seasonDraft)); ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Message</label>
      <textarea name="body" id="f-body" class="form-input" rows="9" required
                placeholder="Hello {name}, ..."><?php echo htmlspecialchars(composeValue('body', 'body', $seasonDraft)); ?></textarea>
      <p style="font-size:12px;color:var(--stone-mid);margin:6px 0 0;">
        Type <code style="background:var(--cream-dark);padding:1px 5px;border-radius:4px;">{name}</code> anywhere and it becomes the customer's first name. Leave a blank line between paragraphs.
      </p>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Button text <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(optional)</span></label>
        <input type="text" name="cta_text" id="f-cta" class="form-input" maxlength="100"
               placeholder="Shop new arrivals"
               value="<?php echo htmlspecialchars(composeValue('cta_text', 'cta', $seasonDraft)); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Button link</label>
        <input type="url" name="cta_url" id="f-url" class="form-input" maxlength="500"
               placeholder="<?php echo SITE_URL; ?>/shop.php"
               value="<?php echo htmlspecialchars(composeValue('cta_url', 'url', $seasonDraft)); ?>">
      </div>
    </div>

    <h2 style="font-size:15px;font-weight:700;margin:22px 0 12px;">3. Who gets it</h2>

    <div class="audience-list">
      <?php $selAud = $_POST['audience'] ?? ($presetAudience ?: 'all'); ?>
      <?php foreach ($audiences as $key => $a): ?>
        <label class="audience-option<?php echo $selAud === $key ? ' selected' : ''; ?>">
          <input type="radio" name="audience" value="<?php echo $key; ?>" <?php echo $selAud === $key ? 'checked' : ''; ?>>
          <span style="flex:1;min-width:0;">
            <span style="display:block;font-weight:700;font-size:13.5px;color:var(--black);"><?php echo htmlspecialchars($a['label'] ?? ''); ?></span>
            <span style="display:block;font-size:12px;color:var(--stone-mid);"><?php echo htmlspecialchars($a['desc'] ?? ''); ?></span>
          </span>
          <span class="audience-count"><?php echo (int)$counts[$key]; ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <?php if ($optOuts > 0): ?>
      <p style="font-size:12px;color:var(--stone-mid);margin:10px 0 0;">
        <?php echo $optOuts; ?> <?php echo $optOuts === 1 ? 'person has' : 'people have'; ?> unsubscribed and <?php echo $optOuts === 1 ? 'is' : 'are'; ?> left out automatically.
      </p>
    <?php endif; ?>

    <h2 style="font-size:15px;font-weight:700;margin:22px 0 10px;">4. When</h2>
    <div class="form-group">
      <label class="form-label">Send later <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(leave blank to send now)</span></label>
      <input type="datetime-local" name="scheduled_at" id="f-when" class="form-input"
             value="<?php echo htmlspecialchars($_POST['scheduled_at'] ?? $presetWhen); ?>">
      <input type="hidden" name="season_key" value="<?php echo htmlspecialchars($presetSeason); ?>">
      <p style="font-size:12px;color:var(--stone-mid);margin:6px 0 0;">
        Useful for festive campaigns. Needs the cron job switched on, see Automations.
      </p>
    </div>

    <div class="campaign-actions">
      <button type="button" class="btn btn-outline" onclick="submitCampaign('test')">Send test to myself</button>
      <button type="button" class="btn btn-outline" onclick="submitCampaign('schedule')">Schedule</button>
      <button type="button" class="btn btn-gold" onclick="confirmSend()">Send now</button>
    </div>
  </form>

  <!-- ── Preview + history ───────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

    <div class="card" style="padding:20px;">
      <h2 style="font-size:15px;font-weight:700;margin:0 0 12px;">Live preview</h2>
      <div style="background:#F5F5F4;border-radius:12px;padding:16px;">
        <div style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.06);">
          <div style="padding:18px 18px 12px;text-align:center;">
            <img src="<?php echo SITE_URL; ?>/assets/images/phelyz-logo-email.png" alt="Phelyz Store"
                 style="width:130px;max-width:60%;height:auto;">
          </div>
          <div style="height:3px;background:#CA8A04;"></div>
          <div style="padding:18px;">
            <h3 id="p-heading" style="font-family:Georgia,serif;font-size:19px;font-weight:400;color:#1C1917;margin:0 0 10px;"></h3>
            <div id="p-body" style="font-size:13.5px;color:#44403C;line-height:1.65;"></div>
            <div id="p-cta-wrap" style="text-align:center;margin:18px 0 4px;display:none;">
              <span id="p-cta" style="display:inline-block;background:#CA8A04;color:#fff;padding:11px 26px;border-radius:8px;font-size:13px;font-weight:700;"></span>
            </div>
          </div>
          <div style="background:#FAFAF9;border-top:1px solid #E7E5E4;padding:12px;text-align:center;font-size:10.5px;color:#A8A29E;">
            Phelyz Store &middot; <?php echo htmlspecialchars(SITE_ADDRESS); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Welcome popup signups ─────────────────────────── -->
    <div class="card" style="padding:20px;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
        <h2 style="font-size:15px;font-weight:700;margin:0;">Popup signups</h2>
        <?php if ($leadCount > 0): ?>
          <a href="?audience=leads" class="btn btn-outline" style="font-size:12px;padding:5px 11px;">Email all <?php echo $leadCount; ?></a>
        <?php endif; ?>
      </div>
      <p style="font-size:12.5px;color:var(--stone-mid);margin:0 0 12px;">
        People who gave their details for the welcome code. Their WhatsApp number is here too.
      </p>

      <?php if (!$leads): ?>
        <p style="font-size:13px;color:var(--stone-mid);margin:0;">
          Nobody has used the popup yet. It shows to first-time visitors once they scroll down the shop.
        </p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="data-table" style="min-width:460px;">
            <thead><tr><th>Email</th><th>WhatsApp</th><th>Joined</th><th>Bought?</th></tr></thead>
            <tbody>
            <?php foreach ($leads as $l): ?>
              <tr>
                <td style="font-size:12.5px;overflow-wrap:anywhere;"><?php echo htmlspecialchars($l['email'] ?? ''); ?></td>
                <td style="font-size:12.5px;white-space:nowrap;">
                  <?php if (!empty($l['whatsapp'])):
                    $wa = preg_replace('/\D/', '', $l['whatsapp']);
                    if (strpos($wa, '0') === 0) $wa = '234' . substr($wa, 1); ?>
                    <a href="https://wa.me/<?php echo htmlspecialchars($wa); ?>" target="_blank" rel="noopener"
                       style="color:#25D366;font-weight:600;"><?php echo htmlspecialchars($l['whatsapp'] ?? ''); ?></a>
                  <?php else: ?>
                    <span style="color:var(--stone-mid);">-</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:12.5px;color:var(--stone-mid);white-space:nowrap;"><?php echo date('j M Y', strtotime($l['created_at'])); ?></td>
                <td style="font-size:12px;white-space:nowrap;">
                  <?php if (!empty($l['has_ordered'])): ?>
                    <span style="color:#15803D;font-weight:700;">Yes</span>
                  <?php else: ?>
                    <span style="color:var(--stone-mid);">Not yet</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($leadCount > count($leads)): ?>
          <p style="font-size:12px;color:var(--stone-mid);margin:10px 0 0;">Showing the newest 100 of <?php echo $leadCount; ?>.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="card" style="padding:20px;">
      <h2 style="font-size:15px;font-weight:700;margin:0 0 12px;">Recent campaigns</h2>
      <?php if (!$history): ?>
        <p style="font-size:13px;color:var(--stone-mid);margin:0;">Nothing sent yet. Your first campaign will show up here.</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($history as $c): ?>
            <div style="border:1px solid var(--cream-dark);border-radius:10px;padding:12px 14px;">
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                <div style="min-width:0;flex:1;">
                  <div style="font-weight:700;font-size:13px;color:var(--black);overflow-wrap:anywhere;"><?php echo htmlspecialchars($c['subject'] ?? ''); ?></div>
                  <div style="font-size:11.5px;color:var(--stone-mid);margin-top:3px;">
                    <?php echo date('j M Y, g:ia', strtotime($c['created_at'])); ?>
                    &middot; <?php echo htmlspecialchars($audiences[$c['audience']]['label'] ?? $c['audience']); ?>
                  </div>
                </div>
                <?php
                  $badge = ['sent'=>'#10B981','sending'=>'#D97706','draft'=>'#78716C','cancelled'=>'#EF4444'][$c['status']] ?? '#78716C';
                ?>
                <span style="flex-shrink:0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#fff;background:<?php echo $badge; ?>;padding:3px 9px;border-radius:99px;">
                  <?php echo htmlspecialchars($c['status'] ?? ''); ?>
                </span>
              </div>
              <div style="font-size:12px;color:var(--stone-mid);margin-top:8px;display:flex;gap:14px;flex-wrap:wrap;">
                <span><strong style="color:var(--black);"><?php echo (int)$c['sent_count']; ?></strong> sent</span>
                <?php if ((int)$c['failed_count'] > 0): ?>
                  <span style="color:#EF4444;"><strong><?php echo (int)$c['failed_count']; ?></strong> failed</span>
                <?php endif; ?>
                <span>of <?php echo (int)$c['total_recipients']; ?></span>
                <?php if ($c['status'] === 'sending'): ?>
                  <a href="#" onclick="resumeCampaign(<?php echo (int)$c['id']; ?>, <?php echo (int)$c['total_recipients']; ?>);return false;" style="color:var(--gold);font-weight:600;">Resume</a>
                <?php endif; ?>
                <a href="?delete=<?php echo (int)$c['id']; ?>" onclick="return confirm('Delete this campaign record?')" style="color:#EF4444;">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<style>
.campaign-grid { display:grid; grid-template-columns: 1.15fr 0.85fr; gap:20px; align-items:start; }
.campaign-grid > * { min-width:0; }

.template-picker { display:flex; flex-wrap:wrap; gap:8px; }
.template-chip {
  border:1.5px solid var(--cream-dark); background:#fff; color:var(--black);
  border-radius:99px; padding:7px 14px; font-size:12.5px; font-weight:600;
  font-family:inherit; cursor:pointer; transition:all 0.15s;
}
.template-chip:hover { border-color:var(--gold); color:var(--gold); }
.template-chip.active { background:var(--black); border-color:var(--black); color:#fff; }
.template-chip.is-blank { border-style:dashed; }

.form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

.audience-list { display:flex; flex-direction:column; gap:8px; }
.audience-option {
  display:flex; align-items:center; gap:11px;
  border:1.5px solid var(--cream-dark); border-radius:10px;
  padding:11px 13px; cursor:pointer; transition:border-color 0.15s, background 0.15s;
}
.audience-option:hover { border-color:var(--gold); }
.audience-option.selected { border-color:var(--gold); background:rgba(202,138,4,0.05); }
.audience-option input { accent-color:var(--gold); flex-shrink:0; }
.audience-count {
  flex-shrink:0; background:var(--cream-dark); color:var(--black);
  border-radius:99px; padding:3px 10px; font-size:12px; font-weight:700;
}

.campaign-actions { display:flex; gap:10px; margin-top:20px; flex-wrap:wrap; }
.campaign-actions .btn { flex:1; min-width:150px; justify-content:center; }

@media (max-width: 1024px) {
  .campaign-grid { grid-template-columns:1fr; }
}
@media (max-width: 560px) {
  .form-row-2 { grid-template-columns:1fr; }
  .campaign-actions .btn { flex:1 1 100%; }
  .template-chip { font-size:12px; padding:7px 12px; }
}
</style>

<script>
(function(){
  var subject = document.getElementById('f-subject');
  var heading = document.getElementById('f-heading');
  var body    = document.getElementById('f-body');
  var cta     = document.getElementById('f-cta');
  var url     = document.getElementById('f-url');

  // Template chips fill the form in
  document.querySelectorAll('.template-chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      document.querySelectorAll('.template-chip').forEach(function(c){ c.classList.remove('active'); });
      chip.classList.add('active');
      subject.value = chip.dataset.subject;
      heading.value = chip.dataset.heading;
      body.value    = chip.dataset.body;
      cta.value     = chip.dataset.cta;
      url.value     = chip.dataset.url;
      renderPreview();
    });
  });

  // Audience highlight
  document.querySelectorAll('.audience-option input').forEach(function(input){
    input.addEventListener('change', function(){
      document.querySelectorAll('.audience-option').forEach(function(o){ o.classList.remove('selected'); });
      input.closest('.audience-option').classList.add('selected');
    });
  });

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function renderPreview(){
    var name = 'Amaka';
    document.getElementById('p-heading').textContent = (heading.value || '').replace(/\{name\}/g, name);
    document.getElementById('p-heading').style.display = heading.value ? 'block' : 'none';

    var text = (body.value || '').replace(/\{name\}/g, name).replace(/\{store\}/g, <?php echo json_encode(SITE_NAME); ?>);
    var html = text.split(/\n\s*\n/).filter(function(p){ return p.trim() !== ''; })
                   .map(function(p){ return '<p style="margin:0 0 12px;">' + escapeHtml(p).replace(/\n/g,'<br>') + '</p>'; })
                   .join('');
    document.getElementById('p-body').innerHTML = html;

    var hasCta = cta.value.trim() !== '' && url.value.trim() !== '';
    document.getElementById('p-cta-wrap').style.display = hasCta ? 'block' : 'none';
    document.getElementById('p-cta').textContent = cta.value;
  }

  [subject, heading, body, cta, url].forEach(function(el){
    el.addEventListener('input', renderPreview);
  });
  renderPreview();

  window.submitCampaign = function(action){
    document.getElementById('form-action').value = action;
    document.getElementById('campaign-form').submit();
  };

  window.confirmSend = function(){
    var picked = document.querySelector('.audience-option input:checked');
    var count  = picked ? picked.closest('.audience-option').querySelector('.audience-count').textContent : '0';
    if (parseInt(count, 10) === 0) { alert('Nobody matches that audience yet.'); return; }
    if (confirm('Send this email to ' + count + ' ' + (count === '1' ? 'person' : 'people') + '?\n\nThis cannot be undone.')) {
      submitCampaign('send');
    }
  };

  // ── Batch sender ──────────────────────────────────────────
  var box   = document.getElementById('send-progress');
  var bar   = document.getElementById('progress-bar');
  var label = document.getElementById('progress-label');

  function runBatches(id, total){
    box.style.display = 'block';
    box.scrollIntoView({behavior:'smooth', block:'center'});

    function step(){
      fetch('email-campaigns.php?batch=' + id, {credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(d){
          var done = (d.totalSent || 0);
          var pct  = total ? Math.round(done / total * 100) : 100;
          bar.style.width = pct + '%';
          label.textContent = done + ' of ' + total + ' sent';

          if (!d.done) {
            // A short pause keeps us well inside the host's sending limits.
            setTimeout(step, 1200);
          } else {
            label.textContent = 'Finished. ' + done + ' of ' + total + ' sent.';
            bar.style.width = '100%';
            setTimeout(function(){ window.location.href = 'email-campaigns.php'; }, 2200);
          }
        })
        .catch(function(){
          label.textContent = 'Connection interrupted. Reload the page and press Resume.';
        });
    }
    step();
  }

  window.resumeCampaign = function(id, total){
    runBatches(id, total || 0);
    label.textContent = 'Resuming...';
  };

  <?php if ($startCampaignId): ?>
  runBatches(<?php echo $startCampaignId; ?>, <?php echo (int)count(campaignRecipientsFor($_POST['audience'] ?? 'all')); ?>);
  <?php endif; ?>
})();
</script>

<?php require_once 'includes/footer.php'; ?>
