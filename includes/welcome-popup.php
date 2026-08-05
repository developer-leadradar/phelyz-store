<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * First-visit welcome offer.
 *
 * Appears once the visitor has scrolled far enough to show real interest,
 * never straight away: an offer thrown at somebody the second they land reads
 * as an interruption and gets closed on reflex. Hidden entirely from people
 * who already have an account, who have already claimed, or who dismissed it.
 */

// Signed-in customers already belong to us; do not sell them a welcome offer.
if (isLoggedIn()) return;

$welcomeCode = 'WELCOME10';
?>
<div id="welcome-pop" class="wp-overlay" role="dialog" aria-modal="true" aria-labelledby="wp-title" hidden>
  <div class="wp-card">
    <button type="button" class="wp-close" aria-label="Close" onclick="welcomeDismiss()">&times;</button>

    <div class="wp-body" id="wp-form-stage">
      <div class="wp-eyebrow">A gift to start with</div>
      <h2 id="wp-title" class="wp-title">10% off your first piece</h2>
      <p class="wp-sub">
        Leave your details and we will send your code straight over, plus first word on new arrivals.
      </p>

      <form onsubmit="welcomeClaim(event)" novalidate>
        <div class="wp-field">
          <label for="wp-email">Email address</label>
          <input type="email" id="wp-email" required autocomplete="email" placeholder="you@example.com">
        </div>
        <div class="wp-field">
          <label for="wp-wa">WhatsApp number</label>
          <input type="tel" id="wp-wa" required autocomplete="tel" placeholder="0801 234 5678">
        </div>
        <p id="wp-error" class="wp-error" hidden></p>
        <button type="submit" class="wp-btn" id="wp-submit">Send me the code</button>
      </form>

      <button type="button" class="wp-skip" onclick="welcomeDismiss()">No thanks, I will pay full price</button>
    </div>

    <div class="wp-body wp-done" id="wp-done-stage" hidden>
      <div class="wp-tick">&#10003;</div>
      <h2 class="wp-title">Here is your code</h2>
      <p class="wp-sub">Use it at checkout. We have emailed it to you as well.</p>
      <div class="wp-code" id="wp-code"><?php echo htmlspecialchars($welcomeCode); ?></div>
      <button type="button" class="wp-btn" onclick="welcomeDismiss(); window.location.href='<?php echo SITE_URL; ?>/shop.php';">
        Start shopping
      </button>
    </div>
  </div>
</div>

<style>
.wp-overlay{position:fixed;inset:0;z-index:9998;background:rgba(28,25,23,0.55);backdrop-filter:blur(3px);
  display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity .25s;}
.wp-overlay.is-open{opacity:1;}
.wp-card{position:relative;width:100%;max-width:420px;background:#fff;border-radius:18px;overflow:hidden;
  box-shadow:0 24px 60px rgba(0,0,0,.32);transform:translateY(14px);transition:transform .25s;}
.wp-overlay.is-open .wp-card{transform:translateY(0);}
.wp-card::before{content:'';display:block;height:4px;background:linear-gradient(90deg,var(--gold),#D97706,var(--gold));}
.wp-close{position:absolute;top:10px;right:12px;background:none;border:none;font-size:26px;line-height:1;
  color:var(--stone-mid);cursor:pointer;padding:4px 8px;border-radius:8px;}
.wp-close:hover{color:var(--black);}
.wp-body{padding:32px 28px 26px;text-align:center;}
.wp-eyebrow{font-size:10.5px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);margin-bottom:8px;}
.wp-title{font-family:'Cormorant',Georgia,serif;font-size:27px;font-weight:700;color:var(--black);margin:0 0 8px;line-height:1.15;}
.wp-sub{font-size:13.5px;color:var(--stone-mid);line-height:1.6;margin:0 0 20px;}
.wp-field{text-align:left;margin-bottom:12px;}
.wp-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--stone);margin-bottom:6px;}
.wp-field input{width:100%;padding:12px 14px;font-family:inherit;font-size:14.5px;color:var(--black);
  background:var(--cream);border:1.5px solid var(--cream-dark);border-radius:10px;box-sizing:border-box;}
.wp-field input:focus{outline:none;background:#fff;border-color:var(--gold);box-shadow:0 0 0 3px rgba(202,138,4,.13);}
.wp-error{color:#B91C1C;font-size:12.5px;margin:0 0 10px;text-align:left;}
.wp-btn{width:100%;padding:14px;font-family:inherit;font-size:13px;font-weight:700;letter-spacing:.08em;
  text-transform:uppercase;color:#fff;background:var(--black);border:none;border-radius:10px;cursor:pointer;transition:background .2s;}
.wp-btn:hover{background:linear-gradient(135deg,var(--gold),#D97706);}
.wp-btn[disabled]{opacity:.6;cursor:default;}
.wp-skip{display:block;width:100%;margin-top:12px;background:none;border:none;font-family:inherit;
  font-size:12px;color:var(--stone-mid);cursor:pointer;text-decoration:underline;}
.wp-tick{width:52px;height:52px;margin:0 auto 14px;border-radius:50%;background:#DCFCE7;color:#15803D;
  font-size:26px;display:flex;align-items:center;justify-content:center;}
.wp-code{font-family:'Cormorant',Georgia,serif;font-size:28px;font-weight:700;letter-spacing:.12em;color:var(--black);
  background:var(--cream);border:2px dashed var(--gold);border-radius:12px;padding:14px;margin:0 0 20px;}
@media(max-width:420px){.wp-body{padding:26px 20px 22px;}.wp-title{font-size:23px;}}
</style>

<script>
(function () {
  var KEY = 'phelyz_welcome';
  var pop = document.getElementById('welcome-pop');
  if (!pop) return;

  // Already seen, claimed or dismissed: never ask again.
  try { if (localStorage.getItem(KEY)) return; } catch (e) { return; }

  var shown = false;
  function maybeShow() {
    if (shown) return;
    var scrolled = window.scrollY + window.innerHeight;
    var reach    = document.body.scrollHeight * 0.32;
    // Short pages would otherwise never qualify, so time acts as a backstop.
    if (scrolled < reach) return;
    open();
  }

  function open() {
    shown = true;
    pop.hidden = false;
    requestAnimationFrame(function () { pop.classList.add('is-open'); });
    window.removeEventListener('scroll', maybeShow);
  }

  window.welcomeDismiss = function () {
    try { localStorage.setItem(KEY, 'closed'); } catch (e) {}
    pop.classList.remove('is-open');
    setTimeout(function () { pop.hidden = true; }, 250);
  };

  window.welcomeClaim = function (ev) {
    ev.preventDefault();
    var email = document.getElementById('wp-email').value.trim();
    var wa    = document.getElementById('wp-wa').value.trim();
    var err   = document.getElementById('wp-error');
    var btn   = document.getElementById('wp-submit');

    function fail(msg) { err.textContent = msg; err.hidden = false; }
    err.hidden = true;

    if (!/^[^@\s]+@[^@\s]+\.[^@\s]{2,}$/.test(email)) { fail('That email does not look right.'); return; }
    if (wa.replace(/\D/g, '').length < 10) { fail('Please enter a full WhatsApp number.'); return; }

    btn.disabled = true; btn.textContent = 'Sending...';

    fetch('<?php echo SITE_URL; ?>/api/claim-welcome.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email, whatsapp: wa })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        btn.disabled = false; btn.textContent = 'Send me the code';
        if (!d.success) { fail(d.message || 'Something went wrong. Try again.'); return; }
        try { localStorage.setItem(KEY, 'claimed'); } catch (e) {}
        document.getElementById('wp-code').textContent = d.code;
        document.getElementById('wp-form-stage').hidden = true;
        document.getElementById('wp-done-stage').hidden = false;
      })
      .catch(function () {
        btn.disabled = false; btn.textContent = 'Send me the code';
        fail('Network problem. Please try again.');
      });
  };

  window.addEventListener('scroll', maybeShow, { passive: true });
  setTimeout(maybeShow, 25000);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !pop.hidden) welcomeDismiss();
  });
})();
</script>
