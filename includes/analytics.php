<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Phelyz first-party analytics.
 *
 * Records a row per page view server-side (so it survives ad-blockers and
 * needs no third-party script), and works out which marketing channel the
 * visitor arrived from.
 *
 * Privacy: we store a rotating hashed visitor id, never a raw IP address.
 */

/** Stable-but-anonymous visitor id (hashed, salted per month so it rotates). */
function analyticsVisitorId() {
    if (!empty($_COOKIE['phelyz_vid'])) {
        return substr(preg_replace('/[^a-f0-9]/', '', $_COOKIE['phelyz_vid']), 0, 64);
    }
    if (!empty($_SESSION['phelyz_vid'])) return $_SESSION['phelyz_vid'];

    $seed = ($_SERVER['REMOTE_ADDR'] ?? '')
          . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')
          . '|' . date('Y-m');                 // rotates monthly
    $vid = hash('sha256', $seed . '|phelyz');
    $_SESSION['phelyz_vid'] = $vid;
    if (!headers_sent()) {
        setcookie('phelyz_vid', $vid, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return $vid;
}

/**
 * Work out the acquisition channel from UTM params + referrer.
 * Returns one of: whatsapp, instagram, facebook, tiktok, twitter, google,
 *                 search, email, referral, direct, paid
 */
function analyticsDetectChannel($referrer, $utmSource = '', $utmMedium = '') {
    $src = strtolower(trim((string)$utmSource));
    $med = strtolower(trim((string)$utmMedium));

    // Paid campaigns declare themselves
    if (in_array($med, ['cpc', 'ppc', 'paid', 'paidsocial', 'paid_social'], true)) return 'paid';
    if ($med === 'email' || $src === 'email' || $src === 'newsletter')            return 'email';

    // Explicit UTM source wins over referrer
    $map = [
        'whatsapp' => 'whatsapp', 'wa' => 'whatsapp',
        'instagram' => 'instagram', 'ig' => 'instagram',
        'facebook' => 'facebook', 'fb' => 'facebook',
        'tiktok' => 'tiktok',
        'twitter' => 'twitter', 'x' => 'twitter',
        'google' => 'google',
    ];
    if ($src !== '' && isset($map[$src])) return $map[$src];

    $ref = strtolower(trim((string)$referrer));
    if ($ref === '') return $src !== '' ? 'referral' : 'direct';

    $host = parse_url($ref, PHP_URL_HOST) ?: '';
    $host = preg_replace('/^www\./', '', $host);

    // Same-site referrals are not an acquisition channel
    $selfHost = preg_replace('/^www\./', '', parse_url(SITE_URL, PHP_URL_HOST) ?: '');
    if ($selfHost && $host === $selfHost) return 'internal';

    $patterns = [
        'whatsapp'  => ['wa.me', 'whatsapp.com', 'api.whatsapp.com', 'chat.whatsapp.com'],
        'instagram' => ['instagram.com', 'l.instagram.com', 'ig.me'],
        'facebook'  => ['facebook.com', 'l.facebook.com', 'm.facebook.com', 'fb.me', 'lm.facebook.com'],
        'tiktok'    => ['tiktok.com', 'vm.tiktok.com'],
        'twitter'   => ['twitter.com', 't.co', 'x.com'],
        'google'    => ['google.com', 'google.com.ng', 'google.co.uk'],
        'search'    => ['bing.com', 'duckduckgo.com', 'yahoo.com', 'search.brave.com', 'ecosia.org'],
        'email'     => ['mail.google.com', 'outlook.live.com', 'outlook.office.com', 'mail.yahoo.com'],
    ];
    foreach ($patterns as $channel => $hosts) {
        foreach ($hosts as $h) {
            if ($host === $h || str_ends_with($host, '.' . $h)) return $channel;
        }
    }
    return 'referral';
}

/** Coarse device class from the user agent. */
function analyticsDevice() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') return 'unknown';
    if (preg_match('/ipad|tablet|playbook|silk/', $ua)) return 'tablet';
    if (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone|blackberry/', $ua)) return 'mobile';
    return 'desktop';
}

/**
 * Is this request a robot rather than a shopper?
 *
 * A new domain gets scanned heavily the moment its TLS certificate shows up in
 * the public certificate-transparency logs, and a lot of that traffic sends a
 * perfectly ordinary-looking user agent. A keyword blocklist alone therefore
 * lets plenty through and quietly inflates the visitor count, so this also
 * insists the request looks like it came from a real browser.
 */
function analyticsIsBot() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

    // No user agent at all is never a person.
    if ($ua === '') return true;

    // Obvious self-identifying robots, scanners and HTTP libraries.
    if (preg_match(
        '/bot|crawl|spider|slurp|search|scrape|scrapy|archiver|feedfetcher|'
      . 'bingpreview|facebookexternalhit|whatsapp|telegram|discord|slack|twitterbot|linkedinbot|'
      . 'preview|monitor|uptime|pingdom|statuscake|newrelic|datadog|site24x7|'
      . 'curl|wget|libwww|httpclient|http_request|python|go-http|java\/|okhttp|axios|node-fetch|guzzle|postman|insomnia|'
      . 'headless|phantomjs|puppeteer|playwright|selenium|lighthouse|pagespeed|gtmetrix|'
      . 'semrush|ahrefs|mj12|dotbot|blexbot|petalbot|seznam|yandex|baidu|sogou|'
      . 'censys|shodan|zgrab|masscan|nmap|expanse|internet-measurement|paloalto|netcraft|'
      . 'checkhost|validator|w3c|nuclei|wpscan|acunetix|nikto/',
        $ua
    )) return true;

    // Anything claiming to be a browser must actually look like one.
    if (strpos($ua, 'mozilla/') !== 0 && strpos($ua, 'opera/') !== 0) return true;

    // Real browsers always advertise a language preference. Almost nothing
    // that fakes a browser user agent bothers to send this header.
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return true;

    // Real browsers ask for HTML on a page request.
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    if ($accept !== '' && strpos($accept, 'text/html') === false && strpos($accept, '*/*') === false) return true;

    return false;
}

/**
 * Record one page view. Safe to call on every request - it never throws and
 * never blocks page rendering.
 *
 * @param string   $pageType  home|product|shop|cart|checkout|order|content|other
 * @param int|null $productId
 */
function trackPageView($pageType = 'other', $productId = null) {
    // Never track admin, API endpoints, or bots
    if (analyticsIsBot()) return;
    if (php_sapi_name() === 'cli') return;

    // Only real page loads count: not form posts, not HEAD probes.
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'GET') return;

    // Browsers and link previews quietly pre-fetch pages the user never opened.
    if (!empty($_SERVER['HTTP_PURPOSE']) || !empty($_SERVER['HTTP_X_PURPOSE'])
        || !empty($_SERVER['HTTP_X_MOZ']) || !empty($_SERVER['HTTP_SEC_PURPOSE'])) return;

    // The shop owner browsing their own storefront is not a customer visit.
    if (isAdmin()) return;

    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if (stripos($path, '/admin') !== false || stripos($path, '/api/') !== false) return;

    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $utmSource   = isset($_GET['utm_source'])   ? substr(sanitize($_GET['utm_source']), 0, 100)   : '';
    $utmMedium   = isset($_GET['utm_medium'])   ? substr(sanitize($_GET['utm_medium']), 0, 100)   : '';
    $utmCampaign = isset($_GET['utm_campaign']) ? substr(sanitize($_GET['utm_campaign']), 0, 100) : '';

    $channel = analyticsDetectChannel($referrer, $utmSource, $utmMedium);

    // First-touch attribution: remember how this visitor originally found us,
    // so we can credit the order to the right channel at checkout.
    if (!isset($_SESSION['phelyz_attr']) && $channel !== 'internal') {
        $_SESSION['phelyz_attr'] = [
            'channel'      => $channel,
            'referrer'     => substr($referrer, 0, 500),
            'utm_source'   => $utmSource,
            'utm_campaign' => $utmCampaign,
        ];
    }

    try {
        getDB()->insert('page_views', [
            'visitor_id'   => analyticsVisitorId(),
            'session_id'   => session_id() ?: 'none',
            'user_id'      => isLoggedIn() ? (int)$_SESSION['user_id'] : null,
            'path'         => substr($path, 0, 255),
            'page_type'    => $pageType,
            'product_id'   => $productId ? (int)$productId : null,
            'channel'      => $channel,
            'referrer'     => $referrer ? substr($referrer, 0, 500) : null,
            'utm_source'   => $utmSource ?: null,
            'utm_medium'   => $utmMedium ?: null,
            'utm_campaign' => $utmCampaign ?: null,
            'country'      => $_SERVER['HTTP_X_VERCEL_IP_COUNTRY'] ?? null,
            'device'       => analyticsDevice(),
        ]);
    } catch (Exception $e) {
        // Analytics must never break the storefront.
    }
}

/** The stored first-touch attribution for this session (used when an order is placed). */
function analyticsAttribution() {
    return $_SESSION['phelyz_attr'] ?? [
        'channel' => 'direct', 'referrer' => null, 'utm_source' => null, 'utm_campaign' => null,
    ];
}

// ── Reporting helpers (used by admin/reports.php) ───────────────────────────

/** Human label + colour for each channel. */
function analyticsChannelMeta($channel) {
    $meta = [
        'whatsapp'  => ['WhatsApp',        '#25D366'],
        'instagram' => ['Instagram',       '#E1306C'],
        'facebook'  => ['Facebook',        '#1877F2'],
        'tiktok'    => ['TikTok',          '#000000'],
        'twitter'   => ['X / Twitter',     '#1DA1F2'],
        'google'    => ['Google Search',   '#EA4335'],
        'search'    => ['Other Search',    '#8B5CF6'],
        'email'     => ['Email',           '#0EA5E9'],
        'paid'      => ['Paid Ads',        '#F59E0B'],
        'referral'  => ['Other Websites',  '#78716C'],
        'direct'    => ['Direct / Typed',  '#CA8A04'],
        'internal'  => ['Internal',        '#D6D3D1'],
    ];
    return $meta[$channel] ?? [ucfirst($channel), '#A8A29E'];
}

/** Overall traffic stats for the last N days. */
function analyticsOverview($days = 30) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        $row = $db->fetchOne(
            "SELECT COUNT(*) AS views,
                    COUNT(DISTINCT visitor_id) AS visitors,
                    COUNT(DISTINCT session_id) AS sessions
             FROM page_views WHERE created_at >= ?",
            [$since]
        );
        return [
            'views'    => (int)($row['views'] ?? 0),
            'visitors' => (int)($row['visitors'] ?? 0),
            'sessions' => (int)($row['sessions'] ?? 0),
        ];
    } catch (Exception $e) {
        return ['views' => 0, 'visitors' => 0, 'sessions' => 0];
    }
}

/** Visitors per day, for the trend chart. */
function analyticsDailyVisitors($days = 30) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        return $db->fetchAll(
            "SELECT DATE(created_at) AS d,
                    COUNT(DISTINCT visitor_id) AS visitors,
                    COUNT(*) AS views
             FROM page_views WHERE created_at >= ?
             GROUP BY DATE(created_at) ORDER BY d ASC",
            [$since]
        );
    } catch (Exception $e) { return []; }
}

/** Most-viewed products. */
function analyticsTopProducts($days = 30, $limit = 10) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        return $db->fetchAll(
            "SELECT pv.product_id, p.name, p.image, p.price,
                    COUNT(*) AS views,
                    COUNT(DISTINCT pv.visitor_id) AS unique_views
             FROM page_views pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.product_id IS NOT NULL AND pv.created_at >= ?
             GROUP BY pv.product_id, p.name, p.image, p.price
             ORDER BY views DESC
             LIMIT " . (int)$limit,
            [$since]
        );
    } catch (Exception $e) { return []; }
}

/** Traffic split by acquisition channel. */
function analyticsByChannel($days = 30) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        return $db->fetchAll(
            "SELECT channel,
                    COUNT(DISTINCT visitor_id) AS visitors,
                    COUNT(*) AS views
             FROM page_views
             WHERE created_at >= ? AND channel <> 'internal'
             GROUP BY channel ORDER BY visitors DESC",
            [$since]
        );
    } catch (Exception $e) { return []; }
}

/** Revenue and order count attributed to each channel. */
function analyticsRevenueByChannel($days = 30) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        return $db->fetchAll(
            "SELECT COALESCE(channel, 'direct') AS channel,
                    COUNT(*) AS orders,
                    SUM(total) AS revenue
             FROM orders
             WHERE created_at >= ? AND status <> 'cancelled'
             GROUP BY COALESCE(channel, 'direct')
             ORDER BY revenue DESC",
            [$since]
        );
    } catch (Exception $e) { return []; }
}

/** Device split. */
function analyticsByDevice($days = 30) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        return $db->fetchAll(
            "SELECT device, COUNT(DISTINCT visitor_id) AS visitors
             FROM page_views WHERE created_at >= ?
             GROUP BY device ORDER BY visitors DESC",
            [$since]
        );
    } catch (Exception $e) { return []; }
}

/** Most-visited pages (excluding product pages, which have their own table). */
function analyticsTopPages($days = 30, $limit = 8) {
    $db = getDB();
    $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
    try {
        return $db->fetchAll(
            "SELECT path, page_type, COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
             FROM page_views WHERE created_at >= ?
             GROUP BY path, page_type ORDER BY views DESC LIMIT " . (int)$limit,
            [$since]
        );
    } catch (Exception $e) { return []; }
}
