<?php
$pageTitle = "Contact Us";
require_once 'includes/header.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $body = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
        if (sendEmail(SITE_EMAIL, "Contact Form: $subject", nl2br($body))) {
            $success = 'Thank you! We received your message and will respond within 24 hours.';
        } else {
            $success = 'Message received! We will get back to you soon.';
        }
    }
}
?>

<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;text-align:center;">
    <div class="breadcrumb" style="justify-content:center;color:rgba(255,255,255,0.5);">
      <a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,0.5);">Home</a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <span style="color:rgba(255,255,255,0.8);">Contact</span>
    </div>
    <h1 class="page-hero-title">Get in Touch</h1>
    <p class="page-hero-sub">We'd love to hear from you — reach out any time</p>
  </div>
</div>

<div class="container" style="padding-top:64px;padding-bottom:80px;">
  <div class="contact-grid" style="display:grid;grid-template-columns:1fr 1.5fr;gap:40px;align-items:flex-start;">

    <!-- Contact Info -->
    <div>
      <h2 style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;color:var(--black);margin-bottom:8px;">Let's Talk</h2>
      <p style="font-size:14px;color:var(--stone-mid);margin-bottom:32px;line-height:1.70;">Have questions about an order, a product, or just want advice on choosing the perfect piece? We're here to help.</p>

      <?php
      $contacts=[
        ['Location','Victoria Island, Lagos, Nigeria','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
        ['Phone',SITE_PHONE,'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z'],
        ['Email',SITE_EMAIL,'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        ['Business Hours','Mon – Sat: 9:00 AM – 6:00 PM','M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
      ];
      foreach ($contacts as [$title,$val,$icon]): ?>
        <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 0;border-bottom:1px solid var(--cream-dark);">
          <div style="width:40px;height:40px;border-radius:10px;background:rgba(202,138,4,0.10);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--gold)" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:3px;"><?php echo $title; ?></div>
            <div style="font-size:14px;font-weight:600;color:var(--black);"><?php echo htmlspecialchars($val); ?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- WhatsApp CTA -->
      <a href="https://wa.me/<?php echo preg_replace('/\D/','',SITE_WHATSAPP); ?>?text=Hi%20Phelyz%20Store,%20I%20need%20help" target="_blank" rel="noopener"
         style="display:flex;align-items:center;gap:12px;margin-top:24px;padding:16px 20px;background:#25D366;color:white;border-radius:12px;text-decoration:none;transition:background 0.2s;"
         onmouseover="this.style.background='#128C7E'" onmouseout="this.style.background='#25D366'">
        <svg viewBox="0 0 32 32" fill="white" width="24" height="24"><path d="M16 2C8.27 2 2 8.27 2 16c0 2.47.65 4.79 1.79 6.8L2 30l7.38-1.76A13.9 13.9 0 0016 30c7.73 0 14-6.27 14-14S23.73 2 16 2z"/><path d="M23.5 19.4c-.3-.15-1.77-.87-2.04-.97-.28-.1-.48-.15-.68.15s-.78.97-.96 1.17c-.17.2-.35.22-.65.07-1.77-.88-2.93-1.58-4.1-3.57-.31-.53.31-.5.89-1.65.1-.2.05-.37-.03-.52-.07-.15-.68-1.63-.93-2.24-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.52.07-.79.37-.27.3-1.03 1.01-1.03 2.46s1.06 2.85 1.2 3.05c.15.2 2.07 3.16 5.02 4.43 1.87.81 2.6.88 3.54.74.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35z" opacity="0.3"/></svg>
        <div>
          <div style="font-weight:700;font-size:14px;">Chat on WhatsApp</div>
          <div style="font-size:12px;opacity:0.85;">Fastest response — usually within minutes</div>
        </div>
      </a>
    </div>

    <!-- Contact Form -->
    <div class="card" style="padding:32px;">
      <h3 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);margin-bottom:20px;">Send a Message</h3>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php else: ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div class="form-group" style="margin:0;"><label class="form-label">Your Name *</label><input type="text" name="name" class="form-input" required value="<?php echo htmlspecialchars($_POST['name']??''); ?>"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Email Address *</label><input type="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($_POST['email']??''); ?>"></div>
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Subject</label>
            <select name="subject" class="form-input form-select">
              <option value="">Select a topic</option>
              <?php foreach(['Order Enquiry','Product Question','Shipping & Delivery','Returns & Refunds','Custom Jewelry','Other'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($_POST['subject']??'')===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:20px;"><label class="form-label">Message *</label><textarea name="message" class="form-input" style="min-height:140px;" required placeholder="How can we help you?"><?php echo htmlspecialchars($_POST['message']??''); ?></textarea></div>
          <button type="submit" class="btn btn-gold btn-full">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
            Send Message
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
@media(max-width:1024px){ .contact-grid { grid-template-columns: 1fr !important; } }
</style>

<?php require_once 'includes/footer.php'; ?>
