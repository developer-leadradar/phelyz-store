<?php
$pageTitle = "Email Campaigns";
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/email-campaigns.php';

$db      = getDB();
$success = '';
$error   = '';

$templates = campaignTemplates();
$audiences = campaignAudiences();

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
            if (sendEmail($testTo, '[TEST] ' . $subject, $html)) {
                $success = 'Test email sent to ' . htmlspecialchars($testTo) . '. Check it looks right before sending to everyone.';
            } else {
                $error = 'Could not send the test email. Check the mail settings.';
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
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-page-title">Email Campaigns</h1>
    <p style="font-size:13px;color:var(--stone-mid);margin:4px 0 0;">
      Write once, send to your customers. Pick a ready-made message or start from scratch.
    </p>
  </div>
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
                data-subject="<?php echo htmlspecialchars($t['subject']); ?>"
                data-heading="<?php echo htmlspecialchars($t['heading']); ?>"
                data-body="<?php echo htmlspecialchars($t['body']); ?>"
                data-cta="<?php echo htmlspecialchars($t['cta']); ?>"
                data-url="<?php echo htmlspecialchars($t['url']); ?>">
          <?php echo htmlspecialchars($t['name']); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <h2 style="font-size:15px;font-weight:700;margin:22px 0 12px;">2. Write it</h2>

    <div class="form-group">
      <label class="form-label">Subject line</label>
      <input type="text" name="subject" id="f-subject" class="form-input" maxlength="255" required
             placeholder="Just in: new pieces at Phelyz Store"
             value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Headline <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(shown at the top of the email, optional)</span></label>
      <input type="text" name="heading" id="f-heading" class="form-input" maxlength="255"
             placeholder="Fresh from the workshop"
             value="<?php echo htmlspecialchars($_POST['heading'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Message</label>
      <textarea name="body" id="f-body" class="form-input" rows="9" required
                placeholder="Hello {name}, ..."><?php echo htmlspecialchars($_POST['body'] ?? ''); ?></textarea>
      <p style="font-size:12px;color:var(--stone-mid);margin:6px 0 0;">
        Type <code style="background:var(--cream-dark);padding:1px 5px;border-radius:4px;">{name}</code> anywhere and it becomes the customer's first name. Leave a blank line between paragraphs.
      </p>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Button text <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--stone-mid);">(optional)</span></label>
        <input type="text" name="cta_text" id="f-cta" class="form-input" maxlength="100"
               placeholder="Shop new arrivals"
               value="<?php echo htmlspecialchars($_POST['cta_text'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Button link</label>
        <input type="url" name="cta_url" id="f-url" class="form-input" maxlength="500"
               placeholder="<?php echo SITE_URL; ?>/shop.php"
               value="<?php echo htmlspecialchars($_POST['cta_url'] ?? ''); ?>">
      </div>
    </div>

    <h2 style="font-size:15px;font-weight:700;margin:22px 0 12px;">3. Who gets it</h2>

    <div class="audience-list">
      <?php $selAud = $_POST['audience'] ?? 'all'; ?>
      <?php foreach ($audiences as $key => $a): ?>
        <label class="audience-option<?php echo $selAud === $key ? ' selected' : ''; ?>">
          <input type="radio" name="audience" value="<?php echo $key; ?>" <?php echo $selAud === $key ? 'checked' : ''; ?>>
          <span style="flex:1;min-width:0;">
            <span style="display:block;font-weight:700;font-size:13.5px;color:var(--black);"><?php echo htmlspecialchars($a['label']); ?></span>
            <span style="display:block;font-size:12px;color:var(--stone-mid);"><?php echo htmlspecialchars($a['desc']); ?></span>
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

    <div class="campaign-actions">
      <button type="button" class="btn btn-outline" onclick="submitCampaign('test')">Send test to myself</button>
      <button type="button" class="btn btn-gold" onclick="confirmSend()">Send campaign</button>
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
                  <div style="font-weight:700;font-size:13px;color:var(--black);overflow-wrap:anywhere;"><?php echo htmlspecialchars($c['subject']); ?></div>
                  <div style="font-size:11.5px;color:var(--stone-mid);margin-top:3px;">
                    <?php echo date('j M Y, g:ia', strtotime($c['created_at'])); ?>
                    &middot; <?php echo htmlspecialchars($audiences[$c['audience']]['label'] ?? $c['audience']); ?>
                  </div>
                </div>
                <?php
                  $badge = ['sent'=>'#10B981','sending'=>'#D97706','draft'=>'#78716C','cancelled'=>'#EF4444'][$c['status']] ?? '#78716C';
                ?>
                <span style="flex-shrink:0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#fff;background:<?php echo $badge; ?>;padding:3px 9px;border-radius:99px;">
                  <?php echo htmlspecialchars($c['status']); ?>
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
