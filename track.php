<?php
$pageTitle = "Track Your Order";
require_once 'includes/header.php';
require_once 'includes/tracking.php';

$query  = trim($_GET['id'] ?? $_POST['id'] ?? '');
$parcel = $query !== '' ? getParcelByTracking($query) : null;
$events = $parcel ? getParcelEvents($parcel['id']) : [];
$notFound = ($query !== '' && !$parcel);

$statuses = parcelStatuses();
$flow     = parcelMainFlow();
$pos      = $parcel ? parcelMapPosition($parcel) : null;
$meta     = $parcel ? parcelStatusMeta($parcel['status']) : null;

// How far along the happy-path tracker we are (exceptions sit outside it)
$flowIndex = $parcel ? array_search($parcel['status'], $flow, true) : false;
?>

<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;text-align:center;">
    <div class="section-eyebrow" style="justify-content:center;">Delivery</div>
    <h1 style="font-family:'Cormorant',serif;font-size:clamp(30px,4.2vw,48px);font-weight:700;color:white;letter-spacing:-0.02em;margin-bottom:10px;">
      Track Your Order
    </h1>
    <p style="font-size:14px;color:rgba(255,255,255,0.65);max-width:520px;margin:0 auto;">
      Enter the tracking ID from your order confirmation to see exactly where your parcel is.
    </p>
  </div>
</div>

<div class="container" style="padding-top:36px;padding-bottom:64px;">

  <!-- Search -->
  <form method="GET" class="card" style="padding:20px;margin-bottom:26px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;max-width:620px;margin-left:auto;margin-right:auto;">
    <input type="text" name="id" value="<?php echo htmlspecialchars($query); ?>" required
           placeholder="e.g. PHZTRK-7K2M9QX4"
           class="form-input" style="flex:1;min-width:210px;margin:0;text-transform:uppercase;letter-spacing:0.04em;">
    <button type="submit" class="btn btn-gold" style="white-space:nowrap;">Track Parcel</button>
  </form>

  <?php if ($notFound): ?>
    <div class="card" style="padding:34px;text-align:center;max-width:620px;margin:0 auto;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="var(--stone-mid)" style="width:46px;height:46px;margin:0 auto 14px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
      </svg>
      <h3 style="font-family:'Cormorant',serif;font-size:21px;font-weight:700;color:var(--black);margin:0 0 8px;">We couldn't find that tracking ID</h3>
      <p style="font-size:13.5px;color:var(--stone-mid);line-height:1.6;margin:0 0 18px;">
        Double-check the ID from your order confirmation. Tracking becomes available shortly after your order is confirmed.
      </p>
      <a href="https://wa.me/<?php echo preg_replace('/\D/','',SITE_WHATSAPP); ?>?text=Hi%20Phelyz,%20I%20need%20help%20tracking%20my%20order"
         target="_blank" rel="noopener" class="btn btn-outline">Ask us on WhatsApp</a>
    </div>

  <?php elseif ($parcel): ?>
    <?php $isProblem = in_array($parcel['status'], ['exception','returned'], true); ?>

    <!-- Summary -->
    <div class="card" style="padding:26px 28px;margin-bottom:20px;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap;">
        <div>
          <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:5px;">Tracking ID</div>
          <div style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;color:var(--black);letter-spacing:0.02em;">
            <?php echo htmlspecialchars($parcel['tracking_id']); ?>
          </div>
          <div style="font-size:12.5px;color:var(--stone-mid);margin-top:5px;">
            Order <strong style="color:var(--black);"><?php echo htmlspecialchars($parcel['order_number']); ?></strong>
            &nbsp;·&nbsp; Parcel <?php echo htmlspecialchars($parcel['parcel_number']); ?>
          </div>
        </div>
        <div style="text-align:right;">
          <span style="display:inline-flex;align-items:center;gap:7px;background:<?php echo $meta['colour']; ?>1A;color:<?php echo $meta['colour']; ?>;padding:8px 16px;border-radius:999px;font-size:13px;font-weight:700;">
            <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $meta['colour']; ?>;"></span>
            <?php echo htmlspecialchars($meta['label']); ?>
          </span>
          <?php if (!empty($parcel['eta_date']) && $parcel['status'] !== 'delivered' && !$isProblem): ?>
            <div style="font-size:12.5px;color:var(--stone-mid);margin-top:9px;">
              Estimated arrival<br><strong style="color:var(--black);"><?php echo date('D, j M Y', strtotime($parcel['eta_date'])); ?></strong>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Progress tracker -->
      <?php if (!$isProblem): ?>
      <div style="margin-top:26px;padding-top:22px;border-top:1px solid var(--cream-dark);">
        <div style="display:flex;align-items:flex-start;position:relative;">
          <!-- connecting line -->
          <div style="position:absolute;top:15px;left:6%;right:6%;height:3px;background:var(--cream-dark);border-radius:99px;"></div>
          <div style="position:absolute;top:15px;left:6%;height:3px;background:var(--gold);border-radius:99px;transition:width .4s;
                      width:<?php echo $flowIndex === false ? 0 : round(($flowIndex / (count($flow)-1)) * 88); ?>%;"></div>
          <?php foreach ($flow as $i => $s):
            $sm   = $statuses[$s];
            $done = ($flowIndex !== false && $i <= $flowIndex); ?>
            <div style="flex:1;position:relative;z-index:2;text-align:center;">
              <div style="width:32px;height:32px;border-radius:50%;margin:0 auto 9px;display:flex;align-items:center;justify-content:center;
                          background:<?php echo $done ? 'var(--gold)' : 'var(--white)'; ?>;
                          border:3px solid <?php echo $done ? 'var(--gold)' : 'var(--cream-dark)'; ?>;">
                <?php if ($done): ?>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="white" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                <?php endif; ?>
              </div>
              <div style="font-size:11.5px;font-weight:<?php echo $done ? '700' : '500'; ?>;color:<?php echo $done ? 'var(--black)' : 'var(--stone-mid)'; ?>;line-height:1.35;">
                <?php echo htmlspecialchars($sm['label']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php else: ?>
        <div class="alert alert-error" style="margin:22px 0 0;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
          <div><strong><?php echo htmlspecialchars($meta['label']); ?>.</strong> <?php echo htmlspecialchars($meta['desc']); ?> — please contact us on WhatsApp and we'll sort it out.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Map + timeline -->
    <div style="display:grid;grid-template-columns:1.55fr 1fr;gap:20px;" class="track-grid">
      <div class="card" style="padding:0;overflow:hidden;">
        <div id="track-map" style="width:100%;height:420px;background:var(--cream-dark);"></div>
        <div style="padding:14px 18px;display:flex;align-items:center;gap:9px;border-top:1px solid var(--cream-dark);flex-wrap:wrap;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--gold)" width="16" height="16" style="flex-shrink:0;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
          </svg>
          <span style="font-size:13px;color:var(--stone);">
            Currently near <strong style="color:var(--black);"><?php echo htmlspecialchars($parcel['current_label'] ?: PHELYZ_ORIGIN_LABEL); ?></strong>
          </span>
          <span style="margin-left:auto;font-size:11.5px;color:var(--stone-mid);">Position is indicative</span>
        </div>
      </div>

      <div class="card" style="padding:24px;">
        <h3 style="font-family:'Cormorant',serif;font-size:19px;font-weight:700;color:var(--black);margin:0 0 18px;">Journey</h3>
        <?php if (empty($events)): ?>
          <p style="font-size:13px;color:var(--stone-mid);">No updates recorded yet.</p>
        <?php else: ?>
          <div style="position:relative;padding-left:26px;">
            <div style="position:absolute;left:7px;top:6px;bottom:6px;width:2px;background:var(--cream-dark);"></div>
            <?php foreach (array_reverse($events) as $i => $ev):
              $em = parcelStatusMeta($ev['status']);
              $latest = ($i === 0); ?>
              <div style="position:relative;padding-bottom:20px;">
                <div style="position:absolute;left:-26px;top:2px;width:16px;height:16px;border-radius:50%;
                            background:<?php echo $latest ? $em['colour'] : 'var(--white)'; ?>;
                            border:3px solid <?php echo $em['colour']; ?>;
                            <?php echo $latest ? 'box-shadow:0 0 0 4px '.$em['colour'].'26;' : ''; ?>"></div>
                <div style="font-size:13.5px;font-weight:700;color:var(--black);"><?php echo htmlspecialchars($em['label']); ?></div>
                <?php if (!empty($ev['label'])): ?>
                  <div style="font-size:12.5px;color:var(--stone);margin-top:2px;"><?php echo htmlspecialchars($ev['label']); ?></div>
                <?php endif; ?>
                <?php if (!empty($ev['note'])): ?>
                  <div style="font-size:12px;color:var(--stone-mid);margin-top:3px;line-height:1.5;"><?php echo htmlspecialchars($ev['note']); ?></div>
                <?php endif; ?>
                <div style="font-size:11.5px;color:var(--stone-mid);margin-top:4px;">
                  <?php echo date('j M Y, g:ia', strtotime($ev['created_at'])); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div style="margin-top:14px;padding-top:16px;border-top:1px solid var(--cream-dark);">
          <div style="font-size:11px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:6px;">Delivering to</div>
          <div style="font-size:13px;color:var(--black);line-height:1.55;">
            <?php echo htmlspecialchars(trim(($parcel['shipping_first_name'] ?? '').' '.($parcel['shipping_last_name'] ?? ''))); ?><br>
            <span style="color:var(--stone-mid);"><?php echo htmlspecialchars($parcel['dest_label'] ?: trim(($parcel['shipping_city'] ?? '').', '.($parcel['shipping_state'] ?? ''), ', ')); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Leaflet map (OpenStreetMap — free, no API key) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    (function(){
      var origin  = [<?php echo $pos['origin'][0]; ?>, <?php echo $pos['origin'][1]; ?>];
      var dest    = [<?php echo $pos['dest'][0]; ?>,   <?php echo $pos['dest'][1]; ?>];
      var current = [<?php echo $pos['lat']; ?>,       <?php echo $pos['lng']; ?>];
      var colour  = <?php echo json_encode($meta['colour']); ?>;

      var map = L.map('track-map', { scrollWheelZoom: false, zoomControl: true });
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 19
      }).addTo(map);

      function dot(colour, size) {
        return L.divIcon({
          className: '',
          html: '<div style="width:'+size+'px;height:'+size+'px;border-radius:50%;background:'+colour+
                ';border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);"></div>',
          iconSize: [size, size], iconAnchor: [size/2, size/2]
        });
      }

      // Route line: travelled vs remaining
      L.polyline([origin, dest], { color: '#D6D3D1', weight: 3, dashArray: '7,9' }).addTo(map);
      L.polyline([origin, current], { color: colour, weight: 4 }).addTo(map);

      L.marker(origin, { icon: dot('#78716C', 14) }).addTo(map)
       .bindPopup('<b>Dispatched from</b><br><?php echo addslashes(PHELYZ_ORIGIN_LABEL); ?>');
      L.marker(dest, { icon: dot('#1C1917', 14) }).addTo(map)
       .bindPopup('<b>Destination</b><br><?php echo addslashes($parcel['dest_label'] ?: 'Your address'); ?>');

      var here = L.marker(current, { icon: dot(colour, 22) }).addTo(map)
        .bindPopup('<b><?php echo addslashes($meta['label']); ?></b><br><?php echo addslashes($parcel['current_label'] ?: PHELYZ_ORIGIN_LABEL); ?>');
      here.openPopup();

      // Gentle pulse on the live position
      var pulse = L.circleMarker(current, { radius: 16, color: colour, fillColor: colour, fillOpacity: 0.15, weight: 1 }).addTo(map);
      var grow = true, r = 16;
      setInterval(function(){
        r += grow ? 0.6 : -0.6;
        if (r > 26) grow = false; if (r < 16) grow = true;
        pulse.setRadius(r);
      }, 60);

      map.fitBounds(L.latLngBounds([origin, dest, current]).pad(0.35));
      if (map.getZoom() > 11) map.setZoom(11);
    })();
    </script>

  <?php else: ?>
    <!-- Empty state -->
    <div class="card" style="padding:38px;text-align:center;max-width:620px;margin:0 auto;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="var(--gold)" style="width:52px;height:52px;margin:0 auto 16px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
      </svg>
      <h3 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);margin:0 0 8px;">Where's my order?</h3>
      <p style="font-size:13.5px;color:var(--stone-mid);line-height:1.65;margin:0;">
        Paste your tracking ID above. You'll find it on your order confirmation page and in
        <a href="customer-orders.php" style="color:var(--gold);font-weight:600;">My Orders</a> if you have an account.
      </p>
    </div>
  <?php endif; ?>
</div>

<style>
@media (max-width: 900px) {
  .track-grid { grid-template-columns: 1fr !important; }
  #track-map { height: 320px !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
