<?php
$pageTitle = "FAQ";
require_once 'includes/header.php';

$faqs = [
  "Shipping & Delivery" => [
    "How much is shipping?" => "Shipping is FREE on all orders over ₦50,000. A flat-rate fee applies to orders below that threshold.",
    "How long does delivery take?" => "Delivery within Akwa Ibom typically takes 1–2 business days. Nationwide delivery is 3–5 business days depending on your state.",
    "Do you ship internationally?" => "Currently we ship within Nigeria. Contact us on WhatsApp to discuss international orders.",
    "Can I track my order?" => "Yes. Once your parcel is dispatched you'll get a tracking ID, and you can follow its progress live on our <a href='track.php' style='color:var(--gold);font-weight:600;'>Track Order</a> page.",
  ],
  "Products & Quality" => [
    "Are all your jewels certified?" => "Absolutely. Every piece comes with a certificate of authenticity from our trusted suppliers.",
    "What materials do you use?" => "We carry 9K, 14K, and 18K Gold in Yellow, White, and Rose Gold varieties, along with Sterling Silver. All stones are certified genuine.",
    "Can I request a custom piece?" => "Yes! Contact us via WhatsApp or our contact form with your design idea and we'll put you in touch with our craftspeople.",
    "Do you have a physical store?" => "We are based in Uyo, Akwa Ibom State. WhatsApp us to book a viewing appointment.",
  ],
  "Orders & Payment" => [
    "What payment methods do you accept?" => "We accept card payments (debit/credit, bank transfer and USSD via Paystack), direct Bank Transfer, and Cash on Delivery where available for your state.",
    "Can I modify or cancel my order?" => "Orders can be modified or cancelled within 2 hours of placing, provided the parcel has not been dispatched. Contact us immediately via WhatsApp.",
    "Is my payment information secure?" => "Yes. All card transactions are processed by Paystack over an encrypted connection and we never store your card details.",
  ],
  "Returns & Refunds" => [
    "Do you accept returns?" => "Returns are accepted only in limited circumstances and strictly subject to our Returns &amp; Refunds Policy. Please read the full conditions on our <a href='terms.php#returns' style='color:var(--gold);font-weight:600;'>Terms &amp; Conditions</a> page before requesting one.",
    "What if my item is damaged or incorrect?" => "Contact us on WhatsApp within 24 hours of delivery with clear photos and your order number. Verified damaged, defective or incorrect items are replaced at no cost to you.",
    "How long do refunds take?" => "Where a refund is approved, it is processed within 5–7 business days after the returned item has been received and inspected.",
    "Do you accept returns on custom or personalised pieces?" => "No. Custom, engraved, resized and personalised pieces, along with earrings and other pierced jewellery, are final sale for hygiene and craftsmanship reasons.",
  ],
];
?>

<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;text-align:center;">
    <div class="breadcrumb" style="justify-content:center;color:rgba(255,255,255,0.5);"><a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,0.5);">Home</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><span style="color:rgba(255,255,255,0.8);">FAQ</span></div>
    <h1 class="page-hero-title">Frequently Asked Questions</h1>
    <p class="page-hero-sub">Everything you need to know about shopping with Phelyz</p>
  </div>
</div>

<div class="container" style="max-width:800px;padding-top:64px;padding-bottom:80px;">

  <?php foreach ($faqs as $section => $questions): ?>
    <div style="margin-bottom:40px;">
      <h2 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--cream-dark);"><?php echo $section; ?></h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($questions as $q => $a): ?>
          <details style="background:white;border:1px solid var(--cream-dark);border-radius:10px;overflow:hidden;" onmouseover="this.style.borderColor='rgba(202,138,4,0.3)'" onmouseout="this.style.borderColor='var(--cream-dark)'">
            <summary style="padding:16px 20px;cursor:pointer;font-size:14px;font-weight:600;color:var(--black);list-style:none;display:flex;align-items:center;justify-content:space-between;">
              <?php echo htmlspecialchars($q); ?>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="var(--gold)" width="16" height="16" style="flex-shrink:0;margin-left:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </summary>
            <div style="padding:0 20px 16px;font-size:14px;color:var(--stone-mid);line-height:1.70;"><?php echo htmlspecialchars($a); ?></div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Still have questions? -->
  <div style="background:linear-gradient(135deg,var(--black),var(--stone));border-radius:20px;padding:40px;text-align:center;margin-top:48px;">
    <h3 style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;color:white;margin-bottom:10px;">Still have questions?</h3>
    <p style="font-size:14px;color:rgba(255,255,255,0.60);margin-bottom:24px;">Our team is ready to help — usually within a few minutes on WhatsApp.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="https://wa.me/<?php echo preg_replace('/\D/','',SITE_WHATSAPP); ?>?text=Hi%20Phelyz%20Store!" target="_blank" class="btn btn-gold">Chat on WhatsApp</a>
      <a href="contact.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.30);">Send a Message</a>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
