    <!-- /PAGE CONTENT -->
    <div style="margin-top:48px;padding-top:16px;border-top:1px solid #E9ECEF;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--stone-mid);">
      <span>&copy; <?php echo date('Y'); ?> Phelyz Store. All rights reserved.</span>
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

  function checkMobile(){
    var isMobile = window.innerWidth < 1024;
    if (mobilebar) mobilebar.style.display = isMobile ? 'flex' : 'none';
    if (desktopTopbar) desktopTopbar.style.display = isMobile ? 'none' : 'flex';
    if (sidebar && isMobile) {
      sidebar.style.position = 'fixed';
      sidebar.style.left = '-260px';
      sidebar.style.top = '0';
      sidebar.style.height = '100vh';
      sidebar.style.zIndex = '50';
      sidebar.style.transition = 'left 0.3s ease';
      sidebar.style.display = 'flex';
    } else if (sidebar) {
      sidebar.style.position = 'sticky';
      sidebar.style.left = '';
      sidebar.style.transition = '';
      sidebar.style.display = 'flex';
    }
  }

  window.openAdminNav = function(){
    if (sidebar) sidebar.style.left = '0';
    if (overlay) overlay.style.display = 'block';
    document.body.style.overflow = 'hidden';
  };
  window.closeAdminNav = function(){
    if (sidebar) sidebar.style.left = '-260px';
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
  };

  checkMobile();
  window.addEventListener('resize', checkMobile);
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
