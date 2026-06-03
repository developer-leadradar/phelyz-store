<?php
$pageTitle       = "About Us";
$pageDescription = "Learn about Phelyz Store — premium diamonds and fine jewelry crafted to perfection.";
require_once 'includes/header.php';
?>

<!-- Hero -->
<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;text-align:center;">
    <div class="breadcrumb" style="justify-content:center;color:rgba(255,255,255,0.5);">
      <a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,0.5);">Home</a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <span style="color:rgba(255,255,255,0.8);">About Us</span>
    </div>
    <h1 class="page-hero-title">Our Story</h1>
    <p class="page-hero-sub">Crafting timeless elegance since 2024</p>
  </div>
</div>

<!-- Story Section -->
<section style="padding:80px 0;background:var(--white);">
  <div class="container">
    <div class="story-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;">
      <div>
        <p class="section-eyebrow">Who We Are</p>
        <h2 class="section-title" style="margin-bottom:20px;">More Than Jewelry —<br>We Craft <em style="color:var(--gold);font-style:italic;">Memories</em></h2>
        <p style="font-size:15px;color:var(--stone-mid);line-height:1.80;margin-bottom:16px;">Phelyz Store was founded with a singular vision: to make certified, high-quality fine jewelry accessible to every Nigerian who wants to celebrate life's most precious moments in style.</p>
        <p style="font-size:15px;color:var(--stone-mid);line-height:1.80;margin-bottom:16px;">Every ring, necklace, bracelet, and earring in our collection is carefully selected and certified authentic. We partner with the world's most trusted diamond suppliers and goldsmith houses to bring you pieces that last a lifetime — and beyond.</p>
        <p style="font-size:15px;color:var(--stone-mid);line-height:1.80;">From engagement rings to anniversary gifts, from everyday wear to bridal collections, Phelyz Store is your partner in life's most meaningful milestones.</p>
      </div>
      <div style="border-radius:24px;overflow:hidden;box-shadow:var(--shadow-lg);aspect-ratio:4/3;">
        <img src="https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=700&h=520&fit=crop&q=80" alt="Jewelry craftsmanship" style="width:100%;height:100%;object-fit:cover;">
      </div>
    </div>
  </div>
</section>

<!-- Values -->
<section style="padding:80px 0;background:var(--cream);">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px;">
      <p class="section-eyebrow">What We Stand For</p>
      <h2 class="section-title">Our Core Values</h2>
      <div class="section-divider" style="margin:12px auto 0;"></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;">
      <?php
      $values = [
        ['Authenticity','Every piece carries a certificate of authenticity. We never compromise on quality or origin.','M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
        ['Craftsmanship','Each jewel is inspected by our experts before it reaches you. Beauty lives in the details.','M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z'],
        ['Transparency','Our pricing is honest, our sourcing is ethical, and our service is straightforward. Always.','M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6'],
        ['Customer First','Your satisfaction is our priority. From pre-purchase advice to after-sale support — we are here.','M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
      ];
      foreach ($values as [$title,$desc,$icon]): ?>
        <div style="background:white;border:1px solid var(--cream-dark);border-radius:16px;padding:28px;transition:all 0.2s;"
             onmouseover="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='none';this.style.transform='none'">
          <div style="width:48px;height:48px;border-radius:14px;background:rgba(202,138,4,0.10);display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--gold)" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          </div>
          <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin-bottom:10px;"><?php echo $title; ?></h3>
          <p style="font-size:13px;color:var(--stone-mid);line-height:1.70;"><?php echo $desc; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Stats -->
<section style="background:linear-gradient(135deg,var(--black),var(--stone));padding:64px 0;">
  <div class="container">
    <div class="stats-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;text-align:center;">
      <?php
      $stats=[['500+','Unique Pieces'],['2,400+','Happy Customers'],['100%','Certified Authentic'],['30-Day','Return Policy']];
      foreach ($stats as $i=>[$n,$l]): ?>
        <div style="padding:24px;<?php echo $i>0?'border-left:1px solid rgba(255,255,255,0.10);':''; ?>">
          <div style="font-family:'Cormorant',serif;font-size:40px;font-weight:700;color:var(--gold);line-height:1;margin-bottom:8px;"><?php echo $n; ?></div>
          <div style="font-size:13px;color:rgba(255,255,255,0.55);font-weight:600;letter-spacing:0.06em;text-transform:uppercase;"><?php echo $l; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="padding:80px 0;background:var(--white);text-align:center;">
  <div class="container" style="max-width:640px;">
    <p class="section-eyebrow">Ready to Find Your Perfect Piece?</p>
    <h2 class="section-title" style="margin-bottom:16px;">Explore Our Collection</h2>
    <p style="font-size:15px;color:var(--stone-mid);margin-bottom:32px;">Thousands of certified pieces waiting to be part of your story.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="shop.php" class="btn btn-gold btn-lg">Shop Now</a>
      <a href="contact.php" class="btn btn-outline btn-lg">Get in Touch</a>
    </div>
  </div>
</section>

<style>
@media(max-width:1024px){ .story-grid{grid-template-columns:1fr !important; gap:28px !important;} }
@media(max-width:768px){
  .stats-row{grid-template-columns:repeat(2,1fr) !important;}
  .stats-row > div{border-left:none !important; padding:20px 16px !important;}
  .stats-row > div:nth-child(1), .stats-row > div:nth-child(2){ border-bottom:1px solid rgba(255,255,255,0.10); }
}
@media(max-width:480px){ .stats-row{grid-template-columns:1fr !important;} }
</style>

<?php require_once 'includes/footer.php'; ?>
