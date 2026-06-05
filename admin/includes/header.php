<?php
if (!defined('PHELYZ_ACCESS')) { define('PHELYZ_ACCESS', true); }
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$adminUser   = getCurrentUser();
$currentFile = basename($_SERVER['PHP_SELF']);
$db          = getDB();
$pendingCount= $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE status='pending'")['c'] ?? 0;
$adminNav = [
  'Overview' => [
    ['index.php','Dashboard','M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5',false],
    ['reports.php','Analytics','M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',false],
  ],
  'Catalog' => [
    ['products.php','Products','M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',false],
    ['add-product.php','Add Product','M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',false],
    ['categories.php','Categories','M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',false],
  ],
  'Sales' => [
    ['orders.php','Orders','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z',true],
    ['customers.php','Customers','M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',false],
  ],
  'Settings' => [
    ['settings.php','Store Settings','M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.99l1.005.828c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28zM15 12a3 3 0 11-6 0 3 3 0 016 0z',false],
  ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle).' — ' : ''; ?>Admin · Phelyz Store</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{gold:{DEFAULT:'#CA8A04',light:'#D97706'},stone:{950:'#0C0A09',900:'#1C1917',800:'#292524',700:'#44403C',600:'#57534E',500:'#78716C',400:'#A8A29E',300:'#D6D3D1',200:'#E7E5E4',100:'#F5F5F4',50:'#FAFAF9'}},fontFamily:{display:['Cormorant','Georgia','serif'],sans:['Montserrat','system-ui','sans-serif']}}}}</script>
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__.'/../../assets/css/style.css'); ?>">
</head>
<body style="margin:0;padding:0;background:#F4F5F7;font-family:'Montserrat',sans-serif;">

<div class="admin-wrap">

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-logo-area">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 2h11l4 6-9.5 14L2.5 8l4-6z"/></svg>
      PHELYZ
      <span class="admin-logo-tag">ADMIN</span>
    </div>

    <nav style="flex:1;overflow-y:auto;padding:4px 0;">
      <?php foreach ($adminNav as $section => $links): ?>
        <div class="admin-nav-group">
          <div class="admin-nav-section"><?php echo $section; ?></div>
          <?php foreach ($links as [$file,$label,$icon,$showBadge]): ?>
            <a href="<?php echo $file; ?>" class="admin-nav-link <?php echo $currentFile===$file?'active':''; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
              <?php echo $label; ?>
              <?php if ($showBadge && $pendingCount>0): ?><span class="admin-nav-badge"><?php echo $pendingCount; ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>

    <div style="border-top:1px solid rgba(255,255,255,0.07);padding:8px 0;">
      <a href="../index.php" class="admin-nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        View Store
      </a>
      <a href="../logout.php" class="admin-nav-link" style="color:rgba(239,68,68,0.8) !important;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#EF4444" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
        Sign Out
      </a>
    </div>
  </aside>

  <!-- Mobile sidebar overlay -->
  <div id="admin-overlay" onclick="closeAdminNav()"
       style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:49;"></div>

  <!-- Main content area -->
  <div class="admin-main">
    <!-- Mobile top bar with hamburger -->
    <div id="admin-mobile-topbar" style="display:none;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;padding:12px 0;border-bottom:1px solid #E9ECEF;">
      <button onclick="openAdminNav()" style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:var(--black);color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;flex-shrink:0;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        Menu
      </button>
      <span style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);text-align:right;"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></span>
    </div>

    <!-- Top bar (desktop) -->
    <div id="admin-desktop-topbar" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #E9ECEF;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;"><?php echo date('l, F j, Y'); ?></div>
        <h1 class="admin-page-title"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></h1>
      </div>
      <div style="display:flex;align-items:center;gap:8px;padding:10px 16px;background:white;border:1px solid #E9ECEF;border-radius:10px;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-family:'Cormorant',serif;font-size:16px;font-weight:700;color:white;"><?php echo strtoupper(substr($adminUser['first_name'],0,1)); ?></div>
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--black);"><?php echo htmlspecialchars($adminUser['first_name'].' '.$adminUser['last_name']); ?></div>
          <div style="font-size:11px;color:var(--stone-mid);">Administrator</div>
        </div>
      </div>
    </div>
    <!-- PAGE CONTENT -->
