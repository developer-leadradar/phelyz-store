    <!-- /PAGE CONTENT -->
    <div class="admin-footer-bar" style="margin-top:48px;padding-top:16px;border-top:1px solid #E9ECEF;display:flex;align-items:center;justify-content:center;gap:16px;font-size:12px;color:var(--stone-mid);text-align:center;flex-wrap:wrap;">
      <span>&copy; <?php echo date('Y'); ?> Phelyz Store. All rights reserved.</span>
      <span style="color:var(--cream-dark);">|</span>
      <span>Admin Panel v1.0</span>
    </div>
  </div><!-- /admin-main -->
</div><!-- /admin-wrap -->

<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
<script>
/* ── Admin mobile sidebar ── */
(function(){
  var sidebar = document.getElementById('admin-sidebar');
  var overlay = document.getElementById('admin-overlay');
  var mobilebar = document.getElementById('admin-mobile-topbar');
  var desktopTopbar = mobilebar ? mobilebar.nextElementSibling : null;

  /* The single source of truth for whether the drawer is showing. Layout code
     reads this instead of assuming a state, so a re-layout can never leave the
     drawer and its dark overlay disagreeing with each other. */
  var isOpen = false;
  var lastWidth = window.innerWidth;

  /* Prefer dynamic viewport height so the drawer tracks the browser chrome by
     itself. Older browsers fall back to a measured pixel height. */
  var supportsDvh = window.CSS && CSS.supports && CSS.supports('height', '100dvh');

  function paintState(){
    if (sidebar) sidebar.style.left = isOpen ? '0' : '-260px';
    if (overlay) overlay.style.display = isOpen ? 'block' : 'none';
    document.body.style.overflow = isOpen ? 'hidden' : '';
  }

  function applyLayout(){
    var isMobile = window.innerWidth < 1024;
    if (mobilebar) mobilebar.style.display = isMobile ? 'flex' : 'none';
    if (desktopTopbar) desktopTopbar.style.display = isMobile ? 'none' : 'flex';

    if (sidebar && isMobile) {
      sidebar.style.position = 'fixed';
      sidebar.style.top = '0';
      sidebar.style.height = supportsDvh ? '100dvh' : (window.innerHeight + 'px');
      sidebar.style.zIndex = '50';
      sidebar.style.transition = 'left 0.3s ease';
      sidebar.style.display = 'flex';
      paintState();
    } else if (sidebar) {
      /* Back on desktop the drawer concept does not apply, so clear it out
         completely rather than leaving a stray overlay or locked body scroll. */
      isOpen = false;
      sidebar.style.position = 'sticky';
      sidebar.style.left = '';
      sidebar.style.height = '';
      sidebar.style.transition = '';
      sidebar.style.display = 'flex';
      if (overlay) overlay.style.display = 'none';
      document.body.style.overflow = '';
    }
  }

  window.openAdminNav = function(){ isOpen = true;  paintState(); };
  window.closeAdminNav = function(){ isOpen = false; paintState(); };

  applyLayout();

  /* Scrolling on a phone shows and hides the browser address bar, which fires
     resize with a new height. Re-running the layout on those events was closing
     the drawer mid-scroll while leaving the dark overlay stuck over the page.
     Only a genuine width change means the layout actually needs rebuilding. */
  window.addEventListener('resize', function(){
    if (window.innerWidth === lastWidth) return;
    lastWidth = window.innerWidth;
    applyLayout();
  });

  window.addEventListener('orientationchange', function(){
    setTimeout(function(){ lastWidth = window.innerWidth; applyLayout(); }, 150);
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && isOpen) window.closeAdminNav();
  });

  /* Tapping a destination should take the drawer with it. */
  if (sidebar) {
    sidebar.addEventListener('click', function(e){
      if (e.target.closest('a') && window.innerWidth < 1024) window.closeAdminNav();
    });
  }
})();

function showToast(msg,type){
  var t=document.createElement('div');
  t.style.cssText='position:fixed;top:20px;right:20px;z-index:9999;padding:14px 18px;background:white;border-radius:10px;box-shadow:0 8px 32px rgba(28,25,23,0.15);border-left:4px solid '+(type==='error'?'#EF4444':'#CA8A04')+';font-size:14px;font-weight:500;min-width:280px;transition:opacity 0.3s;';
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(function(){t.style.opacity='0';setTimeout(function(){t.remove();},300);},3000);
}
function confirmDelete(msg){return confirm(msg||'Are you sure you want to delete this? This action cannot be undone.');}
</script>
</body>
</html>
