</main><!-- /page-main -->

<!-- ── WhatsApp Float ────────────────────────────────── -->
<a href="https://wa.me/<?php echo preg_replace('/\D/','',$_ENV['SITE_WHATSAPP']??SITE_WHATSAPP); ?>?text=Hello%20Phelyz%20Store,%20I%20need%20help"
   class="wa-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" title="Chat with us">
  <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" width="30" height="30">
    <path d="M16 2C8.27 2 2 8.27 2 16c0 2.47.65 4.79 1.79 6.8L2 30l7.38-1.76A13.9 13.9 0 0016 30c7.73 0 14-6.27 14-14S23.73 2 16 2z" fill="#25D366"/>
    <path d="M23.5 19.4c-.3-.15-1.77-.87-2.04-.97-.28-.1-.48-.15-.68.15s-.78.97-.96 1.17c-.17.2-.35.22-.65.07-1.77-.88-2.93-1.58-4.1-3.57-.31-.53.31-.5.89-1.65.1-.2.05-.37-.03-.52-.07-.15-.68-1.63-.93-2.24-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.52.07-.79.37-.27.3-1.03 1.01-1.03 2.46s1.06 2.85 1.2 3.05c.15.2 2.07 3.16 5.02 4.43 1.87.81 2.6.88 3.54.74.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35z" fill="white"/>
  </svg>
</a>

<!-- ── Site Footer ───────────────────────────────────── -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div>
        <div class="footer-logo">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 2h11l4 6-9.5 14L2.5 8l4-6z"/></svg>
          PHELYZ
        </div>
        <p class="footer-desc">Your trusted destination for premium diamonds and fine jewelry. Crafting timeless elegance since 2024.</p>
        <div class="footer-social">
          <!-- Facebook -->
          <a href="#" class="social-btn" aria-label="Facebook">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          </a>
          <!-- Instagram -->
          <a href="#" class="social-btn" aria-label="Instagram">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg>
          </a>
          <!-- Twitter / X -->
          <a href="#" class="social-btn" aria-label="Twitter">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <!-- Pinterest -->
          <a href="#" class="social-btn" aria-label="Pinterest">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12c0 4.24 2.64 7.88 6.39 9.34-.09-.78-.17-1.97.03-2.82l1.23-5.21s-.31-.63-.31-1.56c0-1.46.85-2.55 1.9-2.55.9 0 1.33.67 1.33 1.48 0 .9-.58 2.25-.87 3.5-.25 1.04.52 1.89 1.55 1.89 1.85 0 3.28-1.96 3.28-4.78 0-2.5-1.79-4.24-4.36-4.24-2.97 0-4.71 2.22-4.71 4.53 0 .9.34 1.86.77 2.38.08.1.09.19.07.3l-.29 1.17c-.04.18-.15.22-.34.13-1.25-.58-2.03-2.42-2.03-3.9 0-3.16 2.3-6.07 6.63-6.07 3.48 0 6.19 2.48 6.19 5.8 0 3.46-2.18 6.24-5.2 6.24-1.02 0-1.97-.53-2.3-1.15l-.62 2.33c-.22.86-.83 1.94-1.24 2.6.94.29 1.93.44 2.96.44 5.52 0 10-4.48 10-10S17.52 2 12 2z"/></svg>
          </a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-col">
        <h4 class="footer-col-title">Quick Links</h4>
        <a href="<?php echo SITE_URL; ?>">Home</a>
        <a href="shop.php">Shop All</a>
        <a href="shop.php?featured=1">Featured</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact</a>
        <a href="faq.php">FAQ</a>
      </div>

      <!-- Categories -->
      <div class="footer-col">
        <h4 class="footer-col-title">Collections</h4>
        <?php
        $footerCats = getAllCategories();
        $count = 0;
        foreach ($footerCats as $cat):
          if ($count >= 6) break;
        ?>
          <a href="shop.php?category=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
        <?php $count++; endforeach; ?>
      </div>

      <!-- Contact -->
      <div class="footer-col">
        <h4 class="footer-col-title">Contact Us</h4>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
          <span>Victoria Island, Lagos, Nigeria</span>
        </div>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
          <a href="tel:<?php echo SITE_PHONE; ?>"><?php echo SITE_PHONE; ?></a>
        </div>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
          <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>
        </div>
        <div class="footer-contact-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Mon–Sat: 9AM – 6PM</span>
        </div>
        <div class="footer-contact-row" style="margin-top:6px">
          <a href="https://wa.me/<?php echo preg_replace('/\D/','',SITE_WHATSAPP); ?>?text=Hi%20Phelyz%20Store!" target="_blank" rel="noopener"
             style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#25D366;color:white;border-radius:6px;font-size:12px;font-weight:600;margin-top:4px;transition:background 0.2s;"
             onmouseover="this.style.background='#128C7E'" onmouseout="this.style.background='#25D366'">
            <svg width="14" height="14" viewBox="0 0 32 32" fill="white"><path d="M16 2C8.27 2 2 8.27 2 16c0 2.47.65 4.79 1.79 6.8L2 30l7.38-1.76A13.9 13.9 0 0016 30c7.73 0 14-6.27 14-14S23.73 2 16 2z"/><path d="M23.5 19.4c-.3-.15-1.77-.87-2.04-.97-.28-.1-.48-.15-.68.15s-.78.97-.96 1.17c-.17.2-.35.22-.65.07-1.77-.88-2.93-1.58-4.1-3.57-.31-.53.31-.5.89-1.65.1-.2.05-.37-.03-.52-.07-.15-.68-1.63-.93-2.24-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.52.07-.79.37-.27.3-1.03 1.01-1.03 2.46s1.06 2.85 1.2 3.05c.15.2 2.07 3.16 5.02 4.43 1.87.81 2.6.88 3.54.74.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35z" fill="white" opacity="0.2"/></svg>
            WhatsApp Us
          </a>
        </div>
      </div>
    </div>

    <!-- Bottom -->
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> <strong style="color:rgba(255,255,255,0.75)">Phelyz Store</strong>. All rights reserved.</p>
      <div class="footer-bottom-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms &amp; Conditions</a>
        <a href="#">Sitemap</a>
      </div>
      <div class="payment-tags" style="display:flex;align-items:center;gap:6px">
        <span style="font-size:11px;color:rgba(255,255,255,0.30);margin-right:4px">We accept:</span>
        <span class="payment-tag">Visa</span>
        <span class="payment-tag">Mastercard</span>
        <span class="payment-tag">PayPal</span>
        <span class="payment-tag">Cash</span>
      </div>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/cart.js"></script>
</body>
</html>
