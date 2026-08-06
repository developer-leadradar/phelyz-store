<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

require_once __DIR__ . '/email-campaigns.php';

/**
 * Lifecycle email automation.
 *
 * A cron job calls automationRunAll() every 30 minutes. Each automation looks
 * for people in a particular situation (cart left behind, order delivered a
 * week ago, birthday coming up) and emails them once.
 *
 * Three rules apply to everything here:
 *   - nobody gets the same automation twice for the same thing, enforced by a
 *     unique key on the log rather than by hoping the query is exact;
 *   - nothing sends during quiet hours, because a 3am email reads as spam;
 *   - one automated email per person per day, so somebody who trips three
 *     automations at once still only hears from us once.
 */

define('AUTOMATION_QUIET_START', 21);   // 9pm
define('AUTOMATION_QUIET_END',    8);   // 8am
define('AUTOMATION_DAILY_CAP',    1);   // automated emails per person per day
define('AUTOMATION_BATCH',       30);   // max sends per run

/** Is it a civil hour to be emailing people? */
function automationWithinSendingHours() {
    $hour = (int)date('G');
    return $hour >= AUTOMATION_QUIET_END && $hour < AUTOMATION_QUIET_START;
}

/** The automations, with the copy the admin can edit from the panel. */
function automationDefaults() {
    $shop = SITE_URL . '/shop.php';
    return [
        'abandoned_cart' => [
            'label'       => 'Abandoned cart',
            'description' => 'Someone added to their bag and did not check out',
            'subject'     => 'You left something behind',
            'heading'     => 'Still thinking it over?',
            'body'        => "Hello {name},\n\nThe piece you were looking at is still in your bag. We have not taken it off hold yet, but stock moves quickly.\n\nIf anything is holding you back, just reply to this email and we will help.",
            'cta_text'    => 'Finish my order',
            'cta_url'     => SITE_URL . '/cart.php',
            'delay_hours' => 5,
        ],
        'review_request' => [
            'label'       => 'Review request',
            'description' => 'A week after an order is marked delivered',
            'subject'     => 'How is your piece?',
            'heading'     => 'We would love to hear from you',
            'body'        => "Hello {name},\n\nYour order arrived about a week ago. If you have a moment, a short review helps other shoppers far more than anything we could write ourselves.\n\nAnd if something is not right, reply and we will put it right.",
            'cta_text'    => 'Leave a review',
            'cta_url'     => SITE_URL . '/customer-orders.php',
            'delay_hours' => 168,
        ],
        'win_back' => [
            'label'       => 'Win-back',
            'description' => 'A customer who has not ordered in a while',
            'subject'     => 'It has been a while',
            'heading'     => 'Something new for you',
            'body'        => "Hello {name},\n\nIt has been a couple of months since your last order and the collection has moved on since then.\n\nCome and see what has arrived.",
            'cta_text'    => 'See what is new',
            'cta_url'     => $shop,
            'delay_hours' => 1440,
        ],
        'birthday' => [
            'label'       => 'Birthday greeting',
            'description' => 'Sent a few days before a customer birthday',
            'subject'     => 'A little something for your birthday',
            'heading'     => 'Happy birthday from all of us',
            'body'        => "Hello {name},\n\nWe hope your day is a good one. Treat yourself to something that lasts.",
            'cta_text'    => 'Choose your treat',
            'cta_url'     => $shop,
            'delay_hours' => 0,
        ],
        'lead_nudge' => [
            'label'       => 'Welcome code reminder',
            'description' => 'A lead took the welcome code but has not ordered',
            'subject'     => 'Your discount is still waiting',
            'heading'     => 'Your code has not been used yet',
            'body'        => "Hello {name},\n\nYou picked up a welcome discount from us a few days ago and it is still sitting there unused.\n\nIt only works on a first order, so this is the moment to use it.",
            'cta_text'    => 'Use my code',
            'cta_url'     => $shop,
            'delay_hours' => 72,
        ],
    ];
}

/** Make sure every automation exists as a row, without touching edited copy. */
function automationEnsureRows() {
    $db = getDB();
    foreach (automationDefaults() as $key => $d) {
        try {
            $exists = $db->fetchOne("SELECT id FROM email_automations WHERE automation_key = ?", [$key]);
            if ($exists) continue;
            $db->insert('email_automations', [
                'automation_key' => $key,
                'label'          => $d['label'],
                'description'    => $d['description'],
                'subject'        => $d['subject'],
                'heading'        => $d['heading'],
                'body'           => $d['body'],
                'cta_text'       => $d['cta_text'],
                'cta_url'        => $d['cta_url'],
                'delay_hours'    => $d['delay_hours'],
                'is_active'      => 0,
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    return true;
}

/** Has this person already had this automation for this thing? */
function automationAlreadySent($key, $email, $reference) {
    try {
        return (bool)getDB()->fetchOne(
            "SELECT id FROM email_automation_log
             WHERE automation_key = ? AND email = ? AND reference <=> ?",
            [$key, strtolower($email), $reference]
        );
    } catch (Exception $e) { return true; }
}

/** Has this person had any automated email today already? */
function automationCapReached($email) {
    try {
        $row = getDB()->fetchOne(
            "SELECT COUNT(*) AS c FROM email_automation_log
             WHERE email = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
            [strtolower($email)]
        );
        return (int)($row['c'] ?? 0) >= AUTOMATION_DAILY_CAP;
    } catch (Exception $e) { return true; }
}

/** Send one automated email and write it to the log. */
function automationSend($automation, $email, $firstName, $reference, $userId = null) {
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

    $optedOut = campaignOptOutList();
    if (isset($optedOut[$email])) return false;
    if (automationAlreadySent($automation['automation_key'], $email, $reference)) return false;
    if (automationCapReached($email)) return false;

    $body = $automation['body'];
    if (!empty($automation['coupon_code'])) {
        $body .= "\n\nUse code " . $automation['coupon_code'] . " at checkout.";
    }

    $html = campaignRenderEmail([
        'subject'  => $automation['subject'],
        'heading'  => $automation['heading'],
        'body'     => $body,
        'cta_text' => $automation['cta_text'],
        'cta_url'  => $automation['cta_url'],
    ], $firstName, $email);

    $ok = false;
    try { $ok = sendEmail($email, $automation['subject'], $html); }
    catch (Exception $e) { $ok = false; }

    if (!$ok) return false;

    $db = getDB();
    try {
        $db->insert('email_automation_log', [
            'automation_key' => $automation['automation_key'],
            'email'          => $email,
            'user_id'        => $userId,
            'reference'      => $reference,
        ]);
        $db->query("UPDATE email_automations SET sent_count = sent_count + 1 WHERE automation_key = ?",
            [$automation['automation_key']]);
    } catch (Exception $e) {
        // Already logged by a concurrent run; the email still went.
    }
    return true;
}

/** Who each automation should be emailing right now. */
function automationTargets($key, $delayHours) {
    $db = getDB();
    $h  = max(0, (int)$delayHours);

    try {
        switch ($key) {
            case 'abandoned_cart':
                // A cart last touched before the cutoff, belonging to a signed-up
                // customer, where no order has been placed since.
                return $db->fetchAll(
                    "SELECT u.id, u.email, u.first_name, c.id AS ref
                     FROM cart c
                     JOIN users u ON u.id = c.user_id
                     JOIN cart_items ci ON ci.cart_id = c.id
                     WHERE u.role = 'customer' AND u.is_active = 1
                       AND c.updated_at <= DATE_SUB(NOW(), INTERVAL {$h} HOUR)
                       AND c.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       AND NOT EXISTS (SELECT 1 FROM orders o
                                       WHERE o.user_id = u.id AND o.created_at >= c.updated_at)
                     GROUP BY u.id, u.email, u.first_name, c.id"
                );

            case 'review_request':
                return $db->fetchAll(
                    "SELECT u.id, u.email, u.first_name, o.id AS ref
                     FROM orders o
                     JOIN users u ON u.id = o.user_id
                     WHERE o.status = 'delivered'
                       AND o.updated_at <= DATE_SUB(NOW(), INTERVAL {$h} HOUR)
                       AND o.updated_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                       AND u.is_active = 1"
                );

            case 'win_back':
                return $db->fetchAll(
                    "SELECT u.id, u.email, u.first_name, DATE_FORMAT(NOW(), '%Y-%m') AS ref
                     FROM users u
                     WHERE u.role = 'customer' AND u.is_active = 1
                       AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)
                       AND NOT EXISTS (SELECT 1 FROM orders o2 WHERE o2.user_id = u.id
                                       AND o2.created_at >= DATE_SUB(NOW(), INTERVAL {$h} HOUR))"
                );

            case 'birthday':
                // Within the next week, matched on day and month only.
                return $db->fetchAll(
                    "SELECT u.id, u.email, u.first_name, YEAR(NOW()) AS ref
                     FROM users u
                     WHERE u.role = 'customer' AND u.is_active = 1
                       AND u.date_of_birth IS NOT NULL
                       AND DAYOFYEAR(u.date_of_birth) BETWEEN DAYOFYEAR(NOW()) AND DAYOFYEAR(NOW()) + 6"
                );

            case 'lead_nudge':
                return $db->fetchAll(
                    "SELECT NULL AS id, l.email, l.first_name, l.id AS ref
                     FROM leads l
                     WHERE l.converted = 0
                       AND l.created_at <= DATE_SUB(NOW(), INTERVAL {$h} HOUR)
                       AND l.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                       AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = l.email
                                       AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id))"
                );
        }
    } catch (Exception $e) {
        return [];
    }
    return [];
}

/** Run every active automation. Returns a per-automation tally. */
function automationRunAll() {
    $results = ['skipped_quiet_hours' => false, 'sent' => [], 'scheduled_campaigns' => 0];

    if (!automationWithinSendingHours()) {
        $results['skipped_quiet_hours'] = true;
        return $results;
    }

    $db = getDB();
    try { $rows = $db->fetchAll("SELECT * FROM email_automations WHERE is_active = 1"); }
    catch (Exception $e) { return $results; }

    $budget = AUTOMATION_BATCH;
    foreach ($rows as $automation) {
        if ($budget <= 0) break;
        $count = 0;
        foreach (automationTargets($automation['automation_key'], $automation['delay_hours']) as $t) {
            if ($budget <= 0) break;
            if (automationSend($automation, $t['email'], $t['first_name'] ?? '', (string)$t['ref'], $t['id'] ?? null)) {
                $count++; $budget--;
            }
        }
        if ($count) $results['sent'][$automation['automation_key']] = $count;
    }

    $results['scheduled_campaigns'] = campaignRunScheduled();
    return $results;
}

// ── Seasonal and scheduled campaigns ─────────────────────────────────────────

/**
 * The trading calendar worth writing to. Dates are day/month so they hold from
 * year to year; the movable feasts are flagged so the admin sets the date.
 */
function campaignSeasons() {
    $shop = SITE_URL . '/shop.php';
    return [
        'new_year' => [
            'label' => 'New Year', 'when' => '01-01', 'movable' => false,
            'subject' => 'A brilliant new year from Phelyz Store',
            'heading' => 'Here is to a year that shines',
            'body'    => "Hello {name},\n\nThank you for being part of our year. Whatever you are stepping into, we hope it is bright.\n\nStart the year with something that lasts.",
            'cta'     => 'See the new collection', 'url' => $shop,
        ],
        'valentines' => [
            'label' => "Valentine's Day", 'when' => '02-14', 'movable' => false,
            'subject' => 'Say it with something she will keep',
            'heading' => 'A gift that outlasts the flowers',
            'body'    => "Hello {name},\n\nValentine's is close. Flowers fade and chocolate goes, but a piece she can wear is remembered every time she puts it on.\n\nOrder in good time and we will make sure it arrives before the day.",
            'cta'     => 'Find her gift', 'url' => $shop,
        ],
        'mothers_day' => [
            'label' => "Mother's Day", 'when' => null, 'movable' => true,
            'subject' => 'For the woman who gave you everything',
            'heading' => 'Something worthy of her',
            'body'    => "Hello {name},\n\nMother's Day is coming. If you are looking for something she will actually treasure, a piece of fine jewelry says it better than most things can.\n\nWe will wrap it beautifully.",
            'cta'     => 'Choose her gift', 'url' => $shop,
        ],
        'easter' => [
            'label' => 'Easter', 'when' => null, 'movable' => true,
            'subject' => 'Easter blessings from Phelyz Store',
            'heading' => 'Wishing you a peaceful Easter',
            'body'    => "Hello {name},\n\nFrom all of us in Uyo, a happy and restful Easter to you and your family.\n\nIf you are seeing family this season, we have pieces that make a lovely thank you.",
            'cta'     => 'Browse the collection', 'url' => $shop,
        ],
        'eid' => [
            'label' => 'Eid', 'when' => null, 'movable' => true,
            'subject' => 'Eid Mubarak from Phelyz Store',
            'heading' => 'Eid Mubarak',
            'body'    => "Hello {name},\n\nWishing you and your loved ones a joyful Eid filled with good company and good food.\n\nIf you are giving gifts this Eid, we would be glad to help you choose.",
            'cta'     => 'See gift ideas', 'url' => $shop,
        ],
        'independence' => [
            'label' => 'Nigeria Independence', 'when' => '10-01', 'movable' => false,
            'subject' => 'Happy Independence Day',
            'heading' => 'Proudly Nigerian',
            'body'    => "Hello {name},\n\nHappy Independence Day. We are proud to be a Nigerian business serving Nigerian customers, and grateful you shop with us.\n\nHere is to everything ahead.",
            'cta'     => 'Shop the collection', 'url' => $shop,
        ],
        'black_friday' => [
            'label' => 'Black Friday', 'when' => null, 'movable' => true,
            'subject' => 'Our biggest prices of the year',
            'heading' => 'Black Friday starts now',
            'body'    => "Hello {name},\n\nThis is the one week a year our prices come down properly. Stock is limited and the best pieces go first.\n\nIf there is something you have been watching, now is the moment.",
            'cta'     => 'Shop the deals', 'url' => $shop,
        ],
        'christmas' => [
            'label' => 'Christmas', 'when' => '12-25', 'movable' => false,
            'subject' => 'Merry Christmas from Phelyz Store',
            'heading' => 'Merry Christmas',
            'body'    => "Hello {name},\n\nThank you for a wonderful year. Every order, every message and every kind word has meant a great deal to us.\n\nFrom all of us here in Uyo, a very merry Christmas to you and your family.",
            'cta'     => 'Find a Christmas gift', 'url' => $shop,
        ],
        'boxing_week' => [
            'label' => 'End of year sale', 'when' => '12-26', 'movable' => false,
            'subject' => 'End of year prices',
            'heading' => 'One last treat before the year closes',
            'body'    => "Hello {name},\n\nThe year is nearly done. We are clearing space for what is coming, so this is the last chance at this year's prices.\n\nTreat yourself. You have earned it.",
            'cta'     => 'See what is left', 'url' => $shop,
        ],
    ];
}

/**
 * Send any campaign whose scheduled time has arrived.
 * Returns how many campaigns were started.
 */
function campaignRunScheduled() {
    $db = getDB();
    try {
        $due = $db->fetchAll(
            "SELECT * FROM email_campaigns
             WHERE status = 'draft' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()
             ORDER BY scheduled_at ASC LIMIT 3"
        );
    } catch (Exception $e) { return 0; }

    $started = 0;
    foreach ($due as $c) {
        $recipients = campaignRecipientsFor($c['audience']);
        if (!$recipients) {
            $db->update('email_campaigns', ['status' => 'cancelled'], 'id = ?', [$c['id']]);
            continue;
        }
        foreach ($recipients as $r) {
            try {
                $db->insert('email_campaign_recipients', [
                    'campaign_id' => $c['id'],
                    'user_id'     => $r['id'] ?? null,
                    'email'       => $r['email'],
                    'first_name'  => $r['first_name'] ?? '',
                ]);
            } catch (Exception $e) { /* duplicate address */ }
        }
        $db->update('email_campaigns',
            ['status' => 'sending', 'total_recipients' => count($recipients)],
            'id = ?', [$c['id']]);
        $started++;
    }

    // Push whatever is mid-flight a batch further along.
    try {
        $sending = $db->fetchAll("SELECT id FROM email_campaigns WHERE status = 'sending' LIMIT 2");
        foreach ($sending as $s) { campaignSendBatch($s['id']); }
    } catch (Exception $e) {}

    return $started;
}
