<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Parcel tracking.
 *
 * Model:
 *   order   — what the customer bought (one order number, e.g. PHZ-2026-000417)
 *   parcel  — the physical box that ships. Every item bought together normally
 *             travels in ONE parcel, so they share one parcel/tracking number.
 *             If a shipment has to be split, extra parcels are added to the
 *             same order and numbered -P1, -P2 …
 *   events  — the timeline; each status change appends one row.
 *
 * The map position is derived from the parcel's status (origin → destination)
 * unless a specific location has been set by hand in the admin, which always wins.
 */

// Where parcels ship from.
define('PHELYZ_ORIGIN_LABEL', 'Uyo, Akwa Ibom');
define('PHELYZ_ORIGIN_LAT',  5.0377);
define('PHELYZ_ORIGIN_LNG',  7.9128);

/** The delivery pipeline, in order. */
function parcelStatuses() {
    return [
        'processing'       => ['label' => 'Order Processing',   'desc' => 'We are preparing your item for dispatch', 'icon' => 'box',   'colour' => '#A8A29E', 'progress' => 0.00],
        'packed'           => ['label' => 'Packed & Ready',     'desc' => 'Your parcel is sealed and awaiting pickup','icon' => 'box',   'colour' => '#8B5CF6', 'progress' => 0.05],
        'picked_up'        => ['label' => 'Shipped',            'desc' => 'Dispatched and collected by our courier',  'icon' => 'truck', 'colour' => '#0EA5E9', 'progress' => 0.15],
        'in_transit'       => ['label' => 'In Transit',         'desc' => 'On the way to your state',                 'icon' => 'truck', 'colour' => '#CA8A04', 'progress' => 0.55],
        'arrived_hub'      => ['label' => 'Arrived at Hub',     'desc' => 'Reached the delivery hub in your area',    'icon' => 'pin',   'colour' => '#CA8A04', 'progress' => 0.85],
        'out_for_delivery' => ['label' => 'Out for Delivery',   'desc' => 'With the rider — arriving today',          'icon' => 'truck', 'colour' => '#F59E0B', 'progress' => 0.95],
        'delivered'        => ['label' => 'Delivered',          'desc' => 'Handed over successfully',                 'icon' => 'check', 'colour' => '#22C55E', 'progress' => 1.00],
        'exception'        => ['label' => 'Delivery Issue',     'desc' => 'We hit a problem — our team is on it',     'icon' => 'alert', 'colour' => '#EF4444', 'progress' => 0.60],
        'returned'         => ['label' => 'Returned to Us',     'desc' => 'The parcel came back to our facility',     'icon' => 'alert', 'colour' => '#EF4444', 'progress' => 0.00],
    ];
}

function parcelStatusMeta($status) {
    $all = parcelStatuses();
    return $all[$status] ?? $all['processing'];
}

/** The happy-path sequence shown as the progress tracker. */
function parcelMainFlow() {
    return ['processing', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
}

/**
 * Approximate centre of each Nigerian state, so we can plot a destination
 * without needing a paid geocoding service.
 */
function nigeriaStateCoords() {
    return [
        'Abia' => [5.4527, 7.5248],        'Adamawa' => [9.3265, 12.3984],
        'Akwa Ibom' => [5.0377, 7.9128],   'Anambra' => [6.2209, 6.9370],
        'Bauchi' => [10.3158, 9.8442],     'Bayelsa' => [4.7719, 6.0699],
        'Benue' => [7.3369, 8.7404],       'Borno' => [11.8333, 13.1500],
        'Cross River' => [4.9757, 8.3417], 'Delta' => [6.2000, 6.7333],
        'Ebonyi' => [6.3249, 8.1137],      'Edo' => [6.3350, 5.6037],
        'Ekiti' => [7.6211, 5.2214],       'Enugu' => [6.4584, 7.5464],
        'FCT (Abuja)' => [9.0765, 7.3986], 'Gombe' => [10.2897, 11.1673],
        'Imo' => [5.4836, 7.0333],         'Jigawa' => [12.2280, 9.5616],
        'Kaduna' => [10.5222, 7.4383],     'Kano' => [12.0022, 8.5920],
        'Katsina' => [12.9908, 7.6018],    'Kebbi' => [12.4539, 4.1975],
        'Kogi' => [7.7337, 6.6906],        'Kwara' => [8.4966, 4.5421],
        'Lagos' => [6.5244, 3.3792],       'Nasarawa' => [8.4900, 8.5200],
        'Niger' => [9.6140, 6.5569],       'Ogun' => [7.1557, 3.3451],
        'Ondo' => [7.2500, 5.1950],        'Osun' => [7.7660, 4.5560],
        'Oyo' => [7.3775, 3.9470],         'Plateau' => [9.8965, 8.8583],
        'Rivers' => [4.8156, 7.0498],      'Sokoto' => [13.0059, 5.2476],
        'Taraba' => [8.8870, 11.3600],     'Yobe' => [11.7460, 11.9660],
        'Zamfara' => [12.1704, 6.6641],
    ];
}

function stateCoords($state) {
    $c = nigeriaStateCoords();
    return $c[$state] ?? [9.0820, 8.6753]; // geographic centre of Nigeria
}

/** Public tracking id, e.g. PHZTRK-7K2M9QX4. */
function generateTrackingId() {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous 0/O/1/I
    $out = '';
    for ($i = 0; $i < 8; $i++) $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return 'PHZTRK-' . $out;
}

/**
 * Create the parcel for an order. Items bought together share this one parcel
 * (and therefore one parcel number and one tracking id).
 */
function createParcelForOrder($orderId, $courier = null) {
    $db = getDB();
    try {
        $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) return null;

        // Existing parcels on this order decide the suffix (-P1, -P2 …)
        $existing = $db->fetchAll("SELECT id FROM parcels WHERE order_id = ?", [$orderId]);
        $seq = count($existing) + 1;
        $parcelNumber = $order['order_number'] . '-P' . $seq;

        // Unique tracking id
        $trackingId = generateTrackingId();
        for ($i = 0; $i < 5; $i++) {
            $clash = $db->fetchOne("SELECT id FROM parcels WHERE tracking_id = ?", [$trackingId]);
            if (!$clash) break;
            $trackingId = generateTrackingId();
        }

        [$dLat, $dLng] = stateCoords($order['shipping_state'] ?? '');
        $destLabel = trim(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? ''), ', ');

        $parcelId = $db->insert('parcels', [
            'order_id'      => $orderId,
            'parcel_number' => $parcelNumber,
            'tracking_id'   => $trackingId,
            'courier'       => $courier,
            'status'        => 'processing',
            'current_label' => PHELYZ_ORIGIN_LABEL,
            'current_lat'   => PHELYZ_ORIGIN_LAT,
            'current_lng'   => PHELYZ_ORIGIN_LNG,
            'dest_label'    => $destLabel ?: null,
            'dest_lat'      => $dLat,
            'dest_lng'      => $dLng,
            'eta_date'      => date('Y-m-d', strtotime('+4 days')),
        ]);

        if ($parcelId) {
            addParcelEvent($parcelId, 'processing', PHELYZ_ORIGIN_LABEL, 'Order received and being prepared.');
        }
        return $parcelId;
    } catch (Exception $e) {
        error_log('createParcelForOrder failed: ' . $e->getMessage());
        return null;
    }
}

/** Append a timeline event and move the parcel's headline status. */
function addParcelEvent($parcelId, $status, $label = null, $note = null, $lat = null, $lng = null) {
    $db = getDB();
    try {
        $db->insert('parcel_events', [
            'parcel_id' => $parcelId,
            'status'    => $status,
            'label'     => $label,
            'lat'       => $lat,
            'lng'       => $lng,
            'note'      => $note,
        ]);
        $upd = ['status' => $status];
        if ($label !== null) $upd['current_label'] = $label;
        if ($lat !== null)   $upd['current_lat']   = $lat;
        if ($lng !== null)   $upd['current_lng']   = $lng;
        $db->update('parcels', $upd, 'id = ?', [$parcelId]);
        return true;
    } catch (Exception $e) {
        error_log('addParcelEvent failed: ' . $e->getMessage());
        return false;
    }
}

function getParcelsByOrder($orderId) {
    try { return getDB()->fetchAll("SELECT * FROM parcels WHERE order_id = ? ORDER BY id ASC", [$orderId]); }
    catch (Exception $e) { return []; }
}

/** Look a parcel up by its public tracking id (or its parcel number). */
function getParcelByTracking($trackingId) {
    $t = strtoupper(trim($trackingId));
    if ($t === '') return null;
    try {
        return getDB()->fetchOne(
            "SELECT p.*, o.order_number, o.status AS order_status, o.created_at AS order_date,
                    o.shipping_first_name, o.shipping_last_name, o.shipping_city, o.shipping_state
             FROM parcels p JOIN orders o ON o.id = p.order_id
             WHERE UPPER(p.tracking_id) = ? OR UPPER(p.parcel_number) = ?
             LIMIT 1",
            [$t, $t]
        );
    } catch (Exception $e) { return null; }
}

function getParcelEvents($parcelId) {
    try {
        return getDB()->fetchAll(
            "SELECT * FROM parcel_events WHERE parcel_id = ? ORDER BY created_at ASC, id ASC",
            [$parcelId]
        );
    } catch (Exception $e) { return []; }
}

/**
 * Where to draw the parcel on the map.
 * A hand-set position in the admin always wins; otherwise we interpolate
 * along the origin→destination line using the status progress.
 */
function parcelMapPosition($parcel) {
    $oLat = PHELYZ_ORIGIN_LAT;  $oLng = PHELYZ_ORIGIN_LNG;
    if (!is_array($parcel)) {
        return ['lat' => $oLat, 'lng' => $oLng, 'origin' => [$oLat, $oLng], 'dest' => [$oLat, $oLng], 'progress' => 0.0];
    }
    $dLat = (float)($parcel['dest_lat'] ?: $oLat);
    $dLng = (float)($parcel['dest_lng'] ?: $oLng);
    $meta = parcelStatusMeta($parcel['status']);
    $t    = (float)$meta['progress'];

    // Explicit override set by the admin
    if ($parcel['current_lat'] !== null && $parcel['current_lng'] !== null) {
        $cLat = (float)$parcel['current_lat'];
        $cLng = (float)$parcel['current_lng'];
        $isOrigin = (abs($cLat - $oLat) < 0.0001 && abs($cLng - $oLng) < 0.0001);
        if (!$isOrigin || $t <= 0.05) {
            return ['lat' => $cLat, 'lng' => $cLng, 'origin' => [$oLat, $oLng], 'dest' => [$dLat, $dLng], 'progress' => $t];
        }
    }
    return [
        'lat'      => $oLat + ($dLat - $oLat) * $t,
        'lng'      => $oLng + ($dLng - $oLng) * $t,
        'origin'   => [$oLat, $oLng],
        'dest'     => [$dLat, $dLng],
        'progress' => $t,
    ];
}
