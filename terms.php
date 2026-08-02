<?php
$pageTitle = "Terms & Conditions";
require_once 'includes/header.php';

$lastUpdated = 'July 2026';
$waDigits    = preg_replace('/\D/', '', SITE_WHATSAPP);
?>

<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;text-align:center;">
    <div class="section-eyebrow" style="justify-content:center;">Legal</div>
    <h1 style="font-family:'Cormorant',serif;font-size:clamp(32px,4.5vw,52px);font-weight:700;color:white;letter-spacing:-0.02em;margin-bottom:10px;">
      Terms &amp; Conditions
    </h1>
    <p style="font-size:14px;color:rgba(255,255,255,0.65);">Last updated: <?php echo $lastUpdated; ?></p>
  </div>
</div>

<div class="container" style="padding-top:48px;padding-bottom:72px;">
  <div style="display:grid;grid-template-columns:230px 1fr;gap:40px;align-items:start;" class="terms-grid">

    <!-- Sticky contents -->
    <nav class="card terms-toc" style="padding:20px;position:sticky;top:calc(var(--nav-height) + 16px);">
      <div style="font-size:11px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:12px;">Contents</div>
      <?php
      $toc = [
        'agreement'  => '1. Agreement',
        'orders'     => '2. Orders &amp; Pricing',
        'payment'    => '3. Payment',
        'delivery'   => '4. Delivery',
        'returns'    => '5. Returns &amp; Refunds',
        'warranty'   => '6. Authenticity &amp; Care',
        'conduct'    => '7. Acceptable Use',
        'liability'  => '8. Liability',
        'privacy'    => '9. Privacy',
        'law'        => '10. Governing Law',
        'contact'    => '11. Contact',
      ];
      foreach ($toc as $anchor => $label): ?>
        <a href="#<?php echo $anchor; ?>" style="display:block;font-size:13px;color:var(--stone);text-decoration:none;padding:6px 0;border-bottom:1px solid var(--cream-dark);"
           onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--stone)'"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </nav>

    <!-- Body -->
    <div class="card terms-body" style="padding:36px 40px;line-height:1.75;color:var(--stone);font-size:14.5px;">

      <p style="margin-bottom:28px;">
        These Terms &amp; Conditions ("Terms") govern your access to and use of <strong><?php echo htmlspecialchars(SITE_NAME); ?></strong>
        and any purchase you make from us. By placing an order you confirm that you have read, understood and accepted these Terms in full.
        If you do not accept them, please do not place an order.
      </p>

      <h2 id="agreement">1. Agreement</h2>
      <p>
        <?php echo htmlspecialchars(SITE_NAME); ?> is a jewellery retailer operating from <?php echo htmlspecialchars(SITE_ADDRESS); ?>.
        These Terms form a binding agreement between you ("the Customer") and us. We may update these Terms at any time; the version
        published on this page at the moment you place an order is the version that applies to that order.
      </p>

      <h2 id="orders">2. Orders &amp; Pricing</h2>
      <ul>
        <li>All prices are listed in Nigerian Naira (₦) and are inclusive of applicable charges unless stated otherwise.</li>
        <li>Prices displayed in other currencies are indicative conversions provided for convenience only. <strong>All orders are charged in Naira.</strong></li>
        <li>Placing an order constitutes an offer to buy. A contract is formed only when we confirm the order and payment has been received in full.</li>
        <li>We reserve the right to refuse or cancel any order — including after confirmation — where an item is out of stock, a price or description error has occurred, payment cannot be verified, or we suspect fraud.</li>
        <li>Product photographs are representative. Natural stones and handcrafted pieces vary slightly in tone, inclusion and finish; such variation is not a defect.</li>
        <li>Pre-order and "Express" items are made or sourced to order. Estimated timelines are indicative, not guaranteed, and delays alone do not entitle you to cancel a confirmed pre-order.</li>
      </ul>

      <h2 id="payment">3. Payment</h2>
      <ul>
        <li>All payments are processed securely online by Paystack. Within Paystack's checkout you may pay by debit or credit card, bank transfer, or USSD.</li>
        <li>We do not see or store your card or banking details at any point.</li>
        <li><strong>Orders are dispatched only after payment has cleared in full.</strong> An order that is created but not paid is held as pending and may be cancelled automatically.</li>
        <li>We do not offer cash on delivery or off-platform payment. Anyone requesting payment outside our Paystack checkout is not acting for us — please report it to us immediately.</li>
      </ul>

      <h2 id="delivery">4. Delivery</h2>
      <ul>
        <li>Delivery fees are calculated by destination state and shown before checkout. Free delivery applies to orders at or above the published threshold.</li>
        <li>Delivery timelines are estimates in business days and begin from dispatch, not from order placement.</li>
        <li>You are responsible for providing a complete, accurate delivery address and a reachable phone number. We are not liable for delay, loss or misdelivery caused by incorrect or incomplete details supplied by you.</li>
        <li>Risk in the goods passes to you on delivery to the address provided or to any person present at that address who accepts the parcel.</li>
        <li>Where delivery is attempted and fails through no fault of ours, redelivery may attract an additional fee.</li>
      </ul>

      <h2 id="returns" style="scroll-margin-top:120px;">5. Returns &amp; Refunds</h2>

      <div style="background:rgba(202,138,4,0.07);border:1px solid rgba(202,138,4,0.25);border-radius:10px;padding:18px 20px;margin:18px 0 22px;">
        <strong style="color:var(--black);display:block;margin-bottom:6px;">Please read this section carefully.</strong>
        Jewellery is a high-value, easily-substituted category. Our returns policy is deliberately narrow and is applied strictly.
        Requests that do not meet <em>every</em> condition below will be declined.
      </div>

      <h3>5.1 Return window</h3>
      <p>
        A return may be requested <strong>only within seven (7) calendar days of the delivery date</strong>. The date recorded by our
        courier or delivery agent is definitive. Requests received after the seventh day are automatically out of scope and will not be considered.
        A request is only valid once it has been submitted to us in writing (WhatsApp or email) and acknowledged by us with a Return Authorisation reference.
        <strong>Items returned without a Return Authorisation reference will be refused on arrival and returned to sender at your cost.</strong>
      </p>

      <h3>5.2 Mandatory conditions</h3>
      <p>To be eligible, <strong>all</strong> of the following must be true:</p>
      <ul>
        <li>The item is entirely unworn, unused, unaltered and free of any sign of wear, scratching, sizing, body oils, perfume, make-up or odour.</li>
        <li>All original packaging, boxes, pouches, authenticity certificates, documentation and any attached security tags are present, intact and <strong>unremoved</strong>. A detached or tampered security tag voids the return.</li>
        <li>The item matches, by weight and identifying features, the specific piece dispatched to you. Substituted, swapped or replica items will be reported.</li>
        <li>Clear, unedited photographs of the item and its packaging are supplied at the time of the request.</li>
        <li>The item is not listed as non-returnable under clause 5.3.</li>
      </ul>

      <h3>5.3 Items that cannot be returned</h3>
      <p>The following are <strong>final sale</strong> and are never eligible for return or refund, except where the item is genuinely faulty or was sent in error:</p>
      <ul>
        <li>Earrings and any other pierced jewellery, for hygiene reasons.</li>
        <li>Custom, bespoke, made-to-order, engraved, resized or otherwise personalised pieces.</li>
        <li>Pre-order and "Express" items.</li>
        <li>Items purchased on sale, clearance, promotional discount, bundle or with a discount code.</li>
        <li>Gift cards and any item supplied free of charge or as part of a promotion.</li>
        <li>Items where the authenticity certificate, security tag or original packaging is missing, damaged or has been removed.</li>
      </ul>

      <h3>5.4 Inspection</h3>
      <p>
        Every returned item is inspected and, where applicable, independently verified before any refund is considered. Inspection typically
        takes up to five (5) business days from receipt. <strong>We reserve the sole and final discretion to decline a return</strong> that fails
        inspection, and to return the item to you at your cost. Where we have reasonable grounds to believe a return is fraudulent — including
        item substitution, wear concealment or repeated abuse of this policy — we will decline it, may withhold future service, and may refer the matter to the appropriate authorities.
      </p>

      <h3>5.5 Costs and refunds</h3>
      <ul>
        <li><strong>Return shipping is paid by you</strong> unless the item is confirmed faulty or was sent in error. We recommend an insured, tracked service; you bear the risk of loss or damage in transit until we receive the item.</li>
        <li>Original delivery fees are <strong>non-refundable</strong>.</li>
        <li>Approved refunds are issued to the original payment method only, within 5–7 business days of approval. Bank processing times are outside our control.</li>
        <li>Where an approved return brings an order below the free-delivery threshold, the delivery fee that was waived will be deducted from the refund.</li>
      </ul>

      <h3>5.6 Faulty, damaged or incorrect items</h3>
      <p>
        This is separate from, and more generous than, the return window above. If your item arrives damaged, defective or is not what you
        ordered, notify us on WhatsApp <strong>within 24 hours of delivery</strong> with your order number and clear photographs of the item and
        its packaging as received. Verified cases are repaired, replaced or refunded in full at our cost, including return shipping. Damage
        reported after 24 hours, or arising from wear, accident, misuse, improper storage, exposure to chemicals or third-party alteration
        (including resizing or repair not carried out by us), is not covered.
      </p>

      <h2 id="warranty">6. Authenticity &amp; Care</h2>
      <ul>
        <li>Every piece is supplied as described, with a certificate of authenticity where applicable.</li>
        <li>Any alteration, resizing, repair or cleaning carried out by a third party immediately voids all authenticity assurances and any remedy under clause 5.6.</li>
        <li>Fine jewellery requires care. Remove pieces before swimming, bathing, exercising, sleeping or handling chemicals, perfume or cleaning agents. Store separately in the pouch provided. Damage from failure to observe reasonable care is not a defect.</li>
        <li>Natural wear, tarnish and patina over time are expected characteristics and are not covered.</li>
      </ul>

      <h2 id="conduct">7. Acceptable Use</h2>
      <ul>
        <li>You agree to provide accurate information and not to impersonate any person or misrepresent your identity or payment authority.</li>
        <li>You may not use this site for any unlawful purpose, attempt to gain unauthorised access to any part of it, or interfere with its operation or security.</li>
        <li>All content on this site — including images, text, layout and branding — is our property or licensed to us and may not be copied, reproduced or used commercially without our written permission.</li>
        <li>We may suspend or close any account, and refuse service, where these Terms are breached.</li>
      </ul>

      <h2 id="liability">8. Liability</h2>
      <p>
        To the fullest extent permitted by law, our total liability arising from or in connection with any order is limited to the amount you
        actually paid for the item(s) giving rise to the claim. We are not liable for indirect, incidental or consequential loss, including loss
        of profit, opportunity or sentiment. Nothing in these Terms excludes or limits liability that cannot lawfully be excluded or limited,
        including liability for death or personal injury caused by our negligence, or for fraud. We are not liable for failure or delay caused
        by events beyond our reasonable control, including courier disruption, strike, civil unrest, natural events or network and payment-provider outages.
      </p>

      <h2 id="privacy">9. Privacy</h2>
      <p>
        We collect only the information needed to process your order and support you — your name, contact details, delivery address and order
        history. We do not sell your data. Payment details are handled entirely by our payment processor and never reach our servers. You may
        request access to, correction of, or deletion of your personal data by contacting us, subject to any records we are required to retain by law.
      </p>

      <h2 id="law">10. Governing Law</h2>
      <p>
        These Terms are governed by the laws of the Federal Republic of Nigeria. Any dispute shall first be pursued in good faith through
        negotiation between the parties; failing resolution within thirty (30) days, it shall be subject to the exclusive jurisdiction of the
        courts of Akwa Ibom State, Nigeria.
      </p>

      <h2 id="contact">11. Contact</h2>
      <p>Questions about these Terms, or making a return request:</p>
      <ul style="list-style:none;padding-left:0;">
        <li><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL); ?>" style="color:var(--gold);font-weight:600;"><?php echo htmlspecialchars(SITE_EMAIL); ?></a></li>
        <li><strong>WhatsApp / Phone:</strong> <a href="https://wa.me/<?php echo $waDigits; ?>" target="_blank" rel="noopener" style="color:var(--gold);font-weight:600;"><?php echo htmlspecialchars(SITE_PHONE); ?></a></li>
        <li><strong>Hours:</strong> <?php echo htmlspecialchars(SITE_HOURS); ?></li>
        <li><strong>Address:</strong> <?php echo htmlspecialchars(SITE_ADDRESS); ?></li>
      </ul>

    </div>
  </div>
</div>

<style>
.terms-body h2 {
  font-family: 'Cormorant', serif;
  font-size: 24px;
  font-weight: 700;
  color: var(--black);
  margin: 34px 0 12px;
  padding-top: 8px;
  scroll-margin-top: 110px;
}
.terms-body h2:first-of-type { margin-top: 0; }
.terms-body h3 {
  font-size: 15px;
  font-weight: 700;
  color: var(--black);
  margin: 22px 0 8px;
}
.terms-body p { margin-bottom: 14px; }
.terms-body ul { margin: 0 0 16px; padding-left: 20px; }
.terms-body li { margin-bottom: 9px; }
.terms-body strong { color: var(--black); }
@media (max-width: 900px) {
  .terms-grid { grid-template-columns: 1fr !important; }
  .terms-toc { position: static !important; }
  .terms-body { padding: 26px 22px !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
