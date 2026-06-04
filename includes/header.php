<?php
ob_start();
if (!defined('PHELYZ_ACCESS')) { define('PHELYZ_ACCESS', true); }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$cartCount  = getCartCount();
$categories = getAllCategories();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : ''; ?><?php echo SITE_NAME; ?></title>
<meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Premium jewelry — exquisite rings, necklaces, bracelets and more. Certified authentic, free shipping over ₦50,000.'; ?>">
<meta name="theme-color" content="#1C1917">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        gold:  { DEFAULT: '#CA8A04', light: '#D97706', pale: '#FEF3C7', dark: '#92400E' },
        stone: { 50:'#FAFAF9', 100:'#F5F5F4', 200:'#E7E5E4', 300:'#D6D3D1', 400:'#A8A29E', 500:'#78716C', 600:'#57534E', 700:'#44403C', 800:'#292524', 900:'#1C1917', 950:'#0C0A09' }
      },
      fontFamily: {
        display: ['Cormorant','Georgia','serif'],
        sans:    ['Montserrat','system-ui','sans-serif'],
      },
    }
  }
}
</script>

<!-- Custom CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__.'/../assets/css/style.css'); ?>">
</head>
<body>

<!-- ── Toast Container ──────────────────────────────── -->
<div id="toast-container"></div>

<!-- ── Announcement Bar ─────────────────────────────── -->
<div id="announcement-bar">
  ✦ Free Shipping on Orders Over ₦50,000 &nbsp;·&nbsp; Certified Authentic Diamonds &nbsp;·&nbsp;
  <a href="tel:<?php echo SITE_PHONE; ?>">Call <?php echo SITE_PHONE; ?></a>
  <button id="close-bar" onclick="this.parentElement.style.display='none'" aria-label="Dismiss">✕</button>
</div>

<!-- ── Search Overlay ───────────────────────────────── -->
<div id="search-overlay" role="dialog" aria-modal="true" aria-label="Search" onclick="if(event.target===this)closeSearch()">
  <div class="search-box-wrap">
    <form action="<?php echo SITE_URL; ?>/search.php" method="GET" onsubmit="return validateSearch(this)">
      <input type="text" name="q" id="search-input" placeholder="Search for rings, necklaces, bracelets…" autocomplete="off" aria-label="Search">
      <button type="submit" class="search-submit-btn" aria-label="Search">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      </button>
    </form>
    <div id="autocomplete-results"></div>
    <p style="margin-top:12px;font-size:12px;color:rgba(255,255,255,0.45);text-align:center">Press ESC to close</p>
  </div>
</div>

<!-- ── Mobile Drawer Backdrop ───────────────────────── -->
<div id="drawer-backdrop" onclick="closeDrawer()"></div>

<!-- ── Mobile Drawer ────────────────────────────────── -->
<nav id="mobile-drawer" aria-label="Mobile navigation">
  <div class="drawer-header">
    <span class="drawer-logo">PHELYZ</span>
    <button class="drawer-close" onclick="closeDrawer()" aria-label="Close menu">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>

  <div class="drawer-nav">
    <a href="<?php echo SITE_URL; ?>" class="drawer-link <?php echo $currentPage==='index.php'?'active':''; ?>">
      Home
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
    </a>

    <!-- Shop + categories -->
    <button class="drawer-link w-full text-left" onclick="toggleDrawerCats()" style="width:100%;background:none;border:none;">
      <span>Shop</span>
      <svg id="cats-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" style="transition:transform 0.2s"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
    </button>
    <div id="drawer-cats" class="drawer-cats" style="display:none">
      <a href="shop.php" class="drawer-cat-link">All Products</a>
      <?php foreach ($categories as $cat): ?>
        <a href="shop.php?category=<?php echo $cat['id']; ?>" class="drawer-cat-link">
          <?php echo htmlspecialchars($cat['name']); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <a href="shop.php?featured=1" class="drawer-link">Featured</a>
    <a href="about.php" class="drawer-link <?php echo $currentPage==='about.php'?'active':''; ?>">About</a>
    <a href="contact.php" class="drawer-link <?php echo $currentPage==='contact.php'?'active':''; ?>">Contact</a>
    <a href="faq.php" class="drawer-link <?php echo $currentPage==='faq.php'?'active':''; ?>">FAQ</a>

    <?php if (isLoggedIn()): ?>
      <div style="height:1px;background:var(--cream-dark);margin:4px 0;"></div>
      <a href="customer-wishlist.php" class="drawer-link">Wishlist</a>
      <a href="customer-orders.php" class="drawer-link">My Orders</a>
      <a href="customer-dashboard.php" class="drawer-link">My Account</a>
    <?php endif; ?>
  </div>

  <div class="drawer-footer">
    <?php if (isLoggedIn()): ?>
      <a href="customer-dashboard.php" class="drawer-btn drawer-btn-gold">My Account</a>
      <a href="logout.php" class="drawer-btn drawer-btn-outline">Sign Out</a>
    <?php else: ?>
      <a href="login.php" class="drawer-btn drawer-btn-gold">Sign In</a>
      <a href="register.php" class="drawer-btn drawer-btn-outline">Create Account</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── Main Navigation ──────────────────────────────── -->
<header id="main-nav">
  <div class="nav-inner">

    <!-- Logo -->
    <a href="<?php echo SITE_URL; ?>" class="nav-logo">
      <svg class="nav-logo-gem" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M6.5 2h11l4 6-9.5 14L2.5 8l4-6zm0 0L2.5 8h19l-4-6z"/>
        <path d="M2.5 8l9.5 14 9.5-14M8 8l4 10 4-10M6.5 2L8 8h8l1.5-6" stroke="currentColor" stroke-width="0.5" fill="none"/>
      </svg>
      PHELYZ
    </a>

    <!-- Desktop links -->
    <nav class="nav-links" aria-label="Main navigation">
      <a href="<?php echo SITE_URL; ?>" class="nav-link <?php echo $currentPage==='index.php'?'active':''; ?>">Home</a>

      <div class="nav-dropdown">
        <a href="shop.php" class="nav-link <?php echo $currentPage==='shop.php'?'active':''; ?>">
          Shop
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="12" height="12" style="display:inline;margin-left:3px;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </a>
        <div class="nav-dropdown-menu">
          <a href="shop.php" class="nav-dd-item" style="font-weight:600;color:var(--gold)">All Products</a>
          <div style="height:1px;background:var(--cream-dark);margin:4px 0;"></div>
          <?php foreach ($categories as $cat): ?>
            <a href="shop.php?category=<?php echo $cat['id']; ?>" class="nav-dd-item">
              <?php echo htmlspecialchars($cat['name']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <a href="shop.php?featured=1" class="nav-link">Featured</a>
      <a href="about.php" class="nav-link <?php echo $currentPage==='about.php'?'active':''; ?>">About</a>
      <a href="contact.php" class="nav-link <?php echo $currentPage==='contact.php'?'active':''; ?>">Contact</a>
    </nav>

    <!-- Actions -->
    <div class="nav-actions">

      <!-- Search -->
      <button class="nav-action-btn" onclick="openSearch()" aria-label="Search">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      </button>

      <!-- Account -->
      <?php if (isLoggedIn()): ?>
        <a href="customer-dashboard.php" class="nav-action-btn" aria-label="My Account" title="My Account">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
        <!-- Wishlist (desktop only) -->
        <a href="customer-wishlist.php" class="nav-action-btn hidden md:flex" aria-label="Wishlist" title="Wishlist">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
        </a>
      <?php else: ?>
        <a href="login.php" class="nav-action-btn hidden md:flex" aria-label="Sign in" title="Sign in">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
      <?php endif; ?>

      <!-- Cart -->
      <a href="cart.php" class="nav-action-btn" id="cart-nav-link" aria-label="Cart (<?php echo $cartCount; ?> items)" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm5.625 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
        <span class="nav-badge" id="cart-badge" style="<?php echo $cartCount > 0 ? '' : 'display:none'; ?>"><?php echo $cartCount; ?></span>
      </a>

      <!-- Mobile hamburger -->
      <button class="nav-action-btn md:hidden" onclick="openDrawer()" aria-label="Open menu" style="display:none" id="hamburger-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
      </button>
    </div>
  </div>
</header>

<!-- ── Page Main ─────────────────────────────────────── -->
<main class="page-main">

<script>
/* ── Nav scroll ── */
(function(){
  var nav = document.getElementById('main-nav');
  window.addEventListener('scroll', function(){
    nav.classList.toggle('scrolled', window.scrollY > 40);
  }, {passive:true});

  /* Show hamburger on mobile */
  var hb = document.getElementById('hamburger-btn');
  function checkMobile(){ hb.style.display = window.innerWidth < 768 ? 'flex' : 'none'; }
  checkMobile();
  window.addEventListener('resize', checkMobile);
})();

/* ── Drawer ── */
function openDrawer(){
  document.getElementById('mobile-drawer').classList.add('open');
  document.getElementById('drawer-backdrop').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeDrawer(){
  document.getElementById('mobile-drawer').classList.remove('open');
  document.getElementById('drawer-backdrop').classList.remove('open');
  document.body.style.overflow='';
}
function toggleDrawerCats(){
  var el=document.getElementById('drawer-cats');
  var arrow=document.getElementById('cats-arrow');
  var open=el.style.display==='block';
  el.style.display=open?'none':'block';
  arrow.style.transform=open?'':'rotate(180deg)';
}

/* ── Search overlay ── */
function openSearch(){
  document.getElementById('search-overlay').classList.add('open');
  document.body.style.overflow='hidden';
  setTimeout(function(){ document.getElementById('search-input').focus(); },100);
}
function closeSearch(){
  document.getElementById('search-overlay').classList.remove('open');
  document.body.style.overflow='';
  document.getElementById('autocomplete-results').innerHTML='';
}
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){ closeSearch(); closeDrawer(); }
});
function validateSearch(form){
  if(!form.q.value.trim()){ showToast('Please enter a search term','error'); return false; }
  return true;
}

/* ── Autocomplete ── */
var acTimer;
document.getElementById('search-input').addEventListener('input', function(){
  clearTimeout(acTimer);
  var q=this.value.trim();
  var box=document.getElementById('autocomplete-results');
  if(q.length<2){ box.innerHTML=''; return; }
  acTimer=setTimeout(function(){
    fetch('<?php echo SITE_URL; ?>/api/search-autocomplete.php?q='+encodeURIComponent(q))
      .then(function(r){ return r.json(); })
      .then(function(data){
        var products = Array.isArray(data) ? data : (data.products || []);
        if(!products.length){ box.innerHTML=''; return; }
        box.innerHTML=products.map(function(p){
          return '<a href="<?php echo SITE_URL; ?>/product.php?id='+p.id+'" class="ac-item">'+
            '<img src="'+p.image+'" alt="'+p.name+'" onerror="this.src=\'https://placehold.co/40x40/F5F5F4/78716C?text=J\'">'+
            '<div><div style="font-size:13px;font-weight:600;color:var(--black)">'+p.name+'</div>'+
            '<div style="font-size:12px;color:var(--gold);font-weight:600">'+p.price+'</div></div>'+
            '</a>';
        }).join('');
      }).catch(function(){});
  },300);
});

/* ── Toast ── */
function showToast(msg,type,dur){
  type=type||'success'; dur=dur||3000;
  var icons={success:'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#22C55E" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',error:'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#EF4444" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>',info:'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#3B82F6" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>'};
  var t=document.createElement('div');
  t.className='toast toast-'+type;
  t.innerHTML=icons[type]+'<span class="toast-msg">'+msg+'</span>';
  document.getElementById('toast-container').appendChild(t);
  requestAnimationFrame(function(){ requestAnimationFrame(function(){ t.classList.add('show'); }); });
  setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ t.remove(); },400); },dur);
}
</script>
