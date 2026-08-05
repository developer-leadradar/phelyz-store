<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Mass email (campaigns).
 *
 * Sending happens in small batches driven by the admin page rather than one
 * long request: shared hosting caps how many messages you may send per hour
 * and will time out a script that tries to loop through a whole list. Each
 * recipient is written to email_campaign_recipients up front, so a campaign
 * can be paused, resumed or retried without anyone being emailed twice.
 */

/** How many messages to send per batch request. */
define('CAMPAIGN_BATCH_SIZE', 15);

// ── Audiences ────────────────────────────────────────────────────────────────

/**
 * Every audience the admin can pick, with the SQL that selects it.
 * All of them exclude admins and deactivated accounts.
 */
function campaignAudiences() {
    return [
        'all' => [
            'label' => 'Everyone',
            'desc'  => 'All active customer accounts',
            'sql'   => "SELECT u.id, u.email, u.first_name FROM users u
                        WHERE u.role = 'customer' AND u.is_active = 1",
        ],
        'buyers' => [
            'label' => 'Customers who have ordered',
            'desc'  => 'Placed at least one order',
            'sql'   => "SELECT u.id, u.email, u.first_name FROM users u
                        WHERE u.role = 'customer' AND u.is_active = 1
                        AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)",
        ],
        'non_buyers' => [
            'label' => 'Signed up but never ordered',
            'desc'  => 'Great for a first-order nudge',
            'sql'   => "SELECT u.id, u.email, u.first_name FROM users u
                        WHERE u.role = 'customer' AND u.is_active = 1
                        AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)",
        ],
        'recent' => [
            'label' => 'Joined in the last 30 days',
            'desc'  => 'Welcome and introduce the brand',
            'sql'   => "SELECT u.id, u.email, u.first_name FROM users u
                        WHERE u.role = 'customer' AND u.is_active = 1
                        AND u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        ],
        'lapsed' => [
            'label' => 'Has not ordered in 90 days',
            'desc'  => 'Win-back offer',
            'sql'   => "SELECT u.id, u.email, u.first_name FROM users u
                        WHERE u.role = 'customer' AND u.is_active = 1
                        AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)
                        AND NOT EXISTS (SELECT 1 FROM orders o2 WHERE o2.user_id = u.id
                                        AND o2.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY))",
        ],
        'wishlist' => [
            'label' => 'Has items on a wishlist',
            'desc'  => 'Already showed interest in a piece',
            'sql'   => "SELECT u.id, u.email, u.first_name FROM users u
                        WHERE u.role = 'customer' AND u.is_active = 1
                        AND EXISTS (SELECT 1 FROM wishlist w WHERE w.user_id = u.id)",
        ],
    ];
}

/**
 * Audiences built from coupon usage, keyed "coupon_12".
 *
 * People who redeemed a code are proven buyers, so being able to mail exactly
 * that group (say, everyone who used WELCOME10) is the most useful segment
 * the shop has. Guests count too: the redemption row carries their email even
 * when there is no account behind it.
 */
function campaignCouponAudiences() {
    $out = [];
    try {
        $rows = getDB()->fetchAll(
            "SELECT c.id, c.code, COUNT(DISTINCT r.email) AS people
             FROM coupons c
             JOIN coupon_redemptions r ON r.coupon_id = c.id
             WHERE r.email IS NOT NULL AND r.email <> ''
             GROUP BY c.id, c.code
             ORDER BY c.code ASC"
        );
    } catch (Exception $e) {
        return $out;
    }

    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $out['coupon_' . $id] = [
            'label' => 'Used coupon ' . $r['code'],
            'desc'  => 'Proven buyers, ideal for a follow-up offer',
            'sql'   => "SELECT MIN(u.id) AS id, r.email AS email, MIN(u.first_name) AS first_name
                        FROM coupon_redemptions r
                        LEFT JOIN users u ON u.id = r.user_id
                        WHERE r.coupon_id = " . $id . "
                          AND r.email IS NOT NULL AND r.email <> ''
                        GROUP BY r.email",
        ];
    }
    return $out;
}

/** Every audience, fixed plus the coupon-derived ones. */
function campaignAllAudiences() {
    return campaignAudiences() + campaignCouponAudiences();
}

/** Resolve an audience key to its recipient rows, minus anyone unsubscribed. */
function campaignRecipientsFor($audienceKey) {
    $audiences = campaignAllAudiences();
    if (!isset($audiences[$audienceKey])) return [];

    try {
        $rows = getDB()->fetchAll($audiences[$audienceKey]['sql']);
    } catch (Exception $e) {
        return [];
    }

    // Drop opt-outs and any duplicate address.
    $optedOut = campaignOptOutList();
    $seen = [];
    $out  = [];
    foreach ($rows as $r) {
        $email = strtolower(trim($r['email']));
        if ($email === '' || isset($seen[$email]) || isset($optedOut[$email])) continue;
        $seen[$email] = true;
        $out[] = $r;
    }
    return $out;
}

/** Lowercased email => true for everyone who opted out of marketing. */
function campaignOptOutList() {
    static $list = null;
    if ($list !== null) return $list;
    $list = [];
    try {
        foreach (getDB()->fetchAll("SELECT email FROM email_unsubscribes") as $r) {
            $list[strtolower(trim($r['email']))] = true;
        }
    } catch (Exception $e) {
        // Table missing just means nobody has opted out yet.
    }
    return $list;
}

/** How many people an audience currently reaches. */
function campaignAudienceCount($audienceKey) {
    return count(campaignRecipientsFor($audienceKey));
}

// ── Unsubscribe links ────────────────────────────────────────────────────────

/** Signature that proves an unsubscribe link really came from us. */
function campaignUnsubToken($email) {
    return substr(hash_hmac('sha256', strtolower(trim($email)), APP_SECRET), 0, 32);
}

function campaignUnsubUrl($email) {
    return SITE_URL . '/unsubscribe.php?e=' . urlencode($email) . '&t=' . campaignUnsubToken($email);
}

// ── Templates ────────────────────────────────────────────────────────────────

/**
 * Ready-made messages the admin can start from. {name} is swapped for the
 * customer's first name when the email goes out.
 */
function campaignTemplates() {
    $shop = SITE_URL . '/shop.php';
    return [
        'new_arrival' => [
            'name'    => 'New arrivals',
            'subject' => 'Just in: new pieces at Phelyz Store',
            'heading' => 'Fresh from the workshop',
            'body'    => "Hello {name},\n\nWe have just added new pieces to the collection, chosen for the way they catch the light and hold their shine for years.\n\nHave a look while your favourite is still in stock.",
            'cta'     => 'Shop new arrivals',
            'url'     => $shop,
        ],
        'sale' => [
            'name'    => 'Sale or promotion',
            'subject' => 'A little something off, just for you',
            'heading' => 'Our prices just got kinder',
            'body'    => "Hello {name},\n\nFor a short while, selected pieces are available at a reduced price. This is a good moment to treat yourself, or to get ahead on a gift.\n\nOffer ends soon, and stock is limited.",
            'cta'     => 'See what is on offer',
            'url'     => $shop,
        ],
        'back_in_stock' => [
            'name'    => 'Back in stock',
            'subject' => 'It is back',
            'heading' => 'The piece you wanted has returned',
            'body'    => "Hello {name},\n\nGood news. A piece that sold out has been restocked and is available again.\n\nThese tend to go quickly, so do not wait too long.",
            'cta'     => 'View the piece',
            'url'     => $shop,
        ],
        'festive' => [
            'name'    => 'Festive greeting',
            'subject' => 'Season\'s greetings from Phelyz Store',
            'heading' => 'Wishing you a beautiful season',
            'body'    => "Hello {name},\n\nThank you for being part of the Phelyz family this year. We are grateful for every order, every message and every kind word.\n\nFrom all of us here in Uyo, we wish you a warm and joyful season.",
            'cta'     => 'Find the perfect gift',
            'url'     => $shop,
        ],
        'thank_you' => [
            'name'    => 'Thank you / loyalty',
            'subject' => 'Thank you for shopping with us',
            'heading' => 'We appreciate you',
            'body'    => "Hello {name},\n\nThank you for choosing Phelyz Store. Every piece we send out is checked by hand, and knowing it is going to someone who cares makes the work worthwhile.\n\nIf you ever need help with sizing, cleaning or care, just reply to this email.",
            'cta'     => 'Browse the collection',
            'url'     => $shop,
        ],
        'win_back' => [
            'name'    => 'We miss you',
            'subject' => 'It has been a while',
            'heading' => 'We saved your seat',
            'body'    => "Hello {name},\n\nIt has been a little while since your last visit, and plenty has changed. New arrivals, new styles, and the same certified quality you know.\n\nCome and see what is new.",
            'cta'     => 'See what is new',
            'url'     => $shop,
        ],
        'first_order' => [
            'name'    => 'First order nudge',
            'subject' => 'Your first piece is waiting',
            'heading' => 'Ready when you are',
            'body'    => "Hello {name},\n\nYou created an account with us but have not chosen a piece yet. If you were not sure where to start, our bestsellers are a safe bet, and every order over 50,000 naira ships free.\n\nAny questions at all, just reply to this email.",
            'cta'     => 'Start shopping',
            'url'     => $shop,
        ],
        'blank' => [
            'name'    => 'Start from scratch',
            'subject' => '',
            'heading' => '',
            'body'    => "Hello {name},\n\n",
            'cta'     => '',
            'url'     => '',
        ],
    ];
}

// ── Building and sending ─────────────────────────────────────────────────────

/** Turn the stored campaign into the HTML one customer will receive. */
function campaignRenderEmail($campaign, $firstName, $email) {
    $name = trim((string)$firstName);
    if ($name === '') $name = 'there';

    $bodyText = str_replace(['{name}', '{store}'], [$name, SITE_NAME], $campaign['body']);

    $html = '';
    if (!empty($campaign['heading'])) {
        $html .= '<h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:normal;color:#1C1917;">'
               . htmlspecialchars(str_replace('{name}', $name, $campaign['heading'])) . '</h1>';
    }

    // Plain text with blank lines becomes paragraphs.
    foreach (preg_split("/\n\s*\n/", trim($bodyText)) as $para) {
        $para = trim($para);
        if ($para === '') continue;
        $html .= '<p style="margin:0 0 14px;color:#44403C;">' . nl2br(htmlspecialchars($para)) . '</p>';
    }

    if (!empty($campaign['cta_text']) && !empty($campaign['cta_url'])) {
        $html .= phelyzEmailButton($campaign['cta_text'], $campaign['cta_url']);
    }

    // Marketing mail must offer a way out.
    $html .= '<p style="margin:26px 0 0;padding-top:16px;border-top:1px solid #E7E5E4;color:#A8A29E;font-size:11.5px;line-height:1.6;">'
           . 'You are receiving this because you have an account with ' . htmlspecialchars(SITE_NAME) . '. '
           . '<a href="' . htmlspecialchars(campaignUnsubUrl($email)) . '" style="color:#78716C;">Unsubscribe from offers</a>.'
           . '</p>';

    return phelyzEmailTemplate($html, mb_substr(strip_tags($bodyText), 0, 120));
}

/**
 * Send the next batch for a campaign.
 *
 * @return array ['sent'=>int,'failed'=>int,'remaining'=>int,'done'=>bool]
 */
function campaignSendBatch($campaignId, $limit = CAMPAIGN_BATCH_SIZE) {
    $db = getDB();
    $campaign = $db->fetchOne("SELECT * FROM email_campaigns WHERE id = ?", [$campaignId]);
    if (!$campaign) return ['sent' => 0, 'failed' => 0, 'remaining' => 0, 'done' => true, 'error' => 'Campaign not found'];

    $batch = $db->fetchAll(
        "SELECT * FROM email_campaign_recipients
         WHERE campaign_id = ? AND status = 'pending'
         ORDER BY id ASC LIMIT " . (int)$limit,
        [$campaignId]
    );

    $sent = 0; $failed = 0;
    foreach ($batch as $r) {
        $html = campaignRenderEmail($campaign, $r['first_name'], $r['email']);
        $ok   = false;
        try {
            $ok = sendEmail($r['email'], $campaign['subject'], $html);
        } catch (Exception $e) {
            $ok = false;
        }

        if ($ok) {
            $sent++;
            $db->update('email_campaign_recipients',
                ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')],
                'id = ?', [$r['id']]);
        } else {
            $failed++;
            $db->update('email_campaign_recipients',
                ['status' => 'failed', 'error' => 'Send failed'],
                'id = ?', [$r['id']]);
        }
    }

    $remainingRow = $db->fetchOne(
        "SELECT COUNT(*) AS c FROM email_campaign_recipients WHERE campaign_id = ? AND status = 'pending'",
        [$campaignId]
    );
    $remaining = (int)($remainingRow['c'] ?? 0);

    $totals = $db->fetchOne(
        "SELECT
            SUM(status = 'sent')   AS sent,
            SUM(status = 'failed') AS failed
         FROM email_campaign_recipients WHERE campaign_id = ?",
        [$campaignId]
    );

    $update = [
        'sent_count'   => (int)($totals['sent'] ?? 0),
        'failed_count' => (int)($totals['failed'] ?? 0),
    ];
    if ($remaining === 0) {
        $update['status']  = 'sent';
        $update['sent_at'] = date('Y-m-d H:i:s');
    }
    $db->update('email_campaigns', $update, 'id = ?', [$campaignId]);

    return [
        'sent'      => $sent,
        'failed'    => $failed,
        'remaining' => $remaining,
        'done'      => $remaining === 0,
        'totalSent' => $update['sent_count'],
    ];
}
