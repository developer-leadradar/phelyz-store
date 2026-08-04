<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Festive / promotional banner slider.
 *
 * Design goal: the shop owner is not a designer. So a banner is created by
 * picking a ready-made PRESET (colour scheme + emoji + suggested wording) and
 * editing the text. No image editing, no colour picking, nothing to get wrong.
 * An optional background photo can be layered in if they want one.
 */

/**
 * The preset catalogue. Each entry is a complete look:
 *   grad  - CSS background for the banner
 *   text  - main text colour
 *   accent- CTA button background
 *   onAcc - CTA text colour
 *   emoji - default decorative emoji
 *   copy  - suggested headline / subtitle / CTA the admin can accept or edit
 */
function bannerPresets() {
    return [
        'christmas' => [
            'label' => 'Christmas',
            'grad'  => 'linear-gradient(115deg,#7F1D1D 0%,#B91C1C 45%,#14532D 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FDE68A', 'onAcc' => '#7F1D1D', 'emoji' => '🎄',
            'copy'  => ['Christmas Collection', 'Give something they will keep forever - free delivery over ₦50,000', 'Shop Christmas'],
        ],
        'newyear' => [
            'label' => 'New Year',
            'grad'  => 'linear-gradient(115deg,#0C0A09 0%,#292524 50%,#CA8A04 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FDE68A', 'onAcc' => '#1C1917', 'emoji' => '✨',
            'copy'  => ['New Year, New Sparkle', 'Start the year with a piece that marks the moment', 'Shop New Arrivals'],
        ],
        'valentine' => [
            'label' => "Valentine's Day",
            'grad'  => 'linear-gradient(115deg,#831843 0%,#BE185D 50%,#F472B6 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FFFFFF', 'onAcc' => '#BE185D', 'emoji' => '💝',
            'copy'  => ["Valentine's Edit", 'Say it properly this year - curated gifts she will actually wear', 'Shop Gifts for Her'],
        ],
        'eid' => [
            'label' => 'Eid',
            'grad'  => 'linear-gradient(115deg,#064E3B 0%,#047857 50%,#D4AF37 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FDE68A', 'onAcc' => '#064E3B', 'emoji' => '🌙',
            'copy'  => ['Eid Mubarak', 'Celebrate with timeless gold - free delivery on orders over ₦50,000', 'Shop the Edit'],
        ],
        'blackfriday' => [
            'label' => 'Black Friday',
            'grad'  => 'linear-gradient(115deg,#000000 0%,#1C1917 60%,#CA8A04 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FACC15', 'onAcc' => '#000000', 'emoji' => '🔥',
            'copy'  => ['Black Friday', 'Our biggest reductions of the year - while stock lasts', 'Shop the Sale'],
        ],
        'mothersday' => [
            'label' => "Mother's Day",
            'grad'  => 'linear-gradient(115deg,#7E22CE 0%,#A855F7 50%,#F5D0FE 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FFFFFF', 'onAcc' => '#7E22CE', 'emoji' => '💐',
            'copy'  => ["Mother's Day", 'For the woman who gave you everything', 'Shop Gifts for Her'],
        ],
        'independence' => [
            'label' => 'Independence Day',
            'grad'  => 'linear-gradient(115deg,#065F46 0%,#FFFFFF 50%,#065F46 100%)',
            'text'  => '#064E3B', 'accent' => '#065F46', 'onAcc' => '#FFFFFF', 'emoji' => '🇳🇬',
            'copy'  => ['Independence Sale', 'Celebrating Nigeria - special pricing all week', 'Shop Now'],
        ],
        'easter' => [
            'label' => 'Easter',
            'grad'  => 'linear-gradient(115deg,#5B21B6 0%,#8B5CF6 50%,#FDE68A 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FFFFFF', 'onAcc' => '#5B21B6', 'emoji' => '🌸',
            'copy'  => ['Easter Collection', 'Fresh pieces for the season of new beginnings', 'Shop Easter'],
        ],
        'sale' => [
            'label' => 'General Sale',
            'grad'  => 'linear-gradient(115deg,#9A3412 0%,#EA580C 55%,#FB923C 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FFFFFF', 'onAcc' => '#EA580C', 'emoji' => '🏷️',
            'copy'  => ['Limited-Time Offer', 'Selected pieces reduced - ends soon', 'Shop the Sale'],
        ],
        'gold' => [
            'label' => 'Phelyz Gold (house style)',
            'grad'  => 'linear-gradient(115deg,#1C1917 0%,#44403C 55%,#CA8A04 100%)',
            'text'  => '#FFFFFF', 'accent' => '#CA8A04', 'onAcc' => '#FFFFFF', 'emoji' => '✦',
            'copy'  => ['New In This Week', 'Fresh arrivals, hand-picked', 'Shop New Arrivals'],
        ],
        'restock' => [
            'label' => 'Back in Stock',
            'grad'  => 'linear-gradient(115deg,#0C4A6E 0%,#0284C7 55%,#7DD3FC 100%)',
            'text'  => '#FFFFFF', 'accent' => '#FFFFFF', 'onAcc' => '#0284C7', 'emoji' => '📦',
            'copy'  => ['Back in Stock', 'Your most-requested pieces have returned', 'Shop Restocked'],
        ],
    ];
}

function bannerPreset($key) {
    $all = bannerPresets();
    return $all[$key] ?? $all['gold'];
}

/**
 * Banners that should show on the storefront right now:
 * active, and within their scheduled date window (blank dates = always).
 */
function getActiveBanners() {
    try {
        $today = date('Y-m-d');
        return getDB()->fetchAll(
            "SELECT * FROM promo_banners
             WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= ?)
               AND (ends_at   IS NULL OR ends_at   >= ?)
             ORDER BY sort_order ASC, id DESC",
            [$today, $today]
        );
    } catch (Exception $e) {
        return [];
    }
}

/** All banners, for the admin list. */
function getAllBanners() {
    try {
        return getDB()->fetchAll("SELECT * FROM promo_banners ORDER BY sort_order ASC, id DESC");
    } catch (Exception $e) { return []; }
}

/** Is this banner live today? (for the admin status pill) */
function bannerIsLive($b) {
    if (empty($b['is_active'])) return false;
    $today = date('Y-m-d');
    if (!empty($b['starts_at']) && $b['starts_at'] > $today) return false;
    if (!empty($b['ends_at'])   && $b['ends_at']   < $today) return false;
    return true;
}

/**
 * Render one banner slide's inline styles.
 * Shared by the storefront slider and the admin live preview so what the
 * owner previews is exactly what customers see.
 */
function bannerSlideStyle($b) {
    $p = bannerPreset($b['preset'] ?? 'gold');
    $bg = $p['grad'];
    if (!empty($b['bg_image'])) {
        $bg = "linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)), url('" . htmlspecialchars($b['bg_image'], ENT_QUOTES) . "') center/cover no-repeat";
    }
    return ['bg' => $bg, 'text' => $p['text'], 'accent' => $p['accent'], 'onAcc' => $p['onAcc']];
}
