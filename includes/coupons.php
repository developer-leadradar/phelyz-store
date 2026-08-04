<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Coupons.
 *
 * A single table drives every kind of code: money off, percent off, free
 * shipping, first-order-only, birthday-only, and per-influencer codes (which
 * are ordinary codes carrying a `source` label so reporting can split them).
 *
 * The applied code lives in the session and is re-validated on every request
 * by getCartSummary(), so a code cannot stay attached after it expires, after
 * the cart drops below the minimum spend, or after its usage cap is reached.
 */

/** Look up a code. Returns null if it does not exist. */
function couponFind($code) {
    $code = strtoupper(trim($code));
    if ($code === '') return null;
    try {
        return getDB()->fetchOne("SELECT * FROM coupons WHERE UPPER(code) = ?", [$code]) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/** The part of the cart a coupon is allowed to discount. */
function couponEligibleSubtotal($coupon, $items) {
    $eligible = 0.0;
    foreach ($items as $item) {
        // Made-to-order pieces can be held back from discounting.
        if (!empty($coupon['exclude_express']) && function_exists('effectiveStockStatus')) {
            $eff = effectiveStockStatus($item);
            if ($eff === 'express' || $eff === 'preorder') continue;
        }
        if (!empty($coupon['category_id']) && (int)$item['category_id'] !== (int)$coupon['category_id']) {
            continue;
        }
        $eligible += (float)$item['price'] * (int)$item['quantity'];
    }
    return $eligible;
}

/** How many times this shopper has already used the code. */
function couponUsesByCustomer($couponId, $userId, $email) {
    try {
        if ($userId) {
            $r = getDB()->fetchOne(
                "SELECT COUNT(*) AS c FROM coupon_redemptions WHERE coupon_id = ? AND user_id = ?",
                [$couponId, $userId]
            );
        } elseif ($email) {
            $r = getDB()->fetchOne(
                "SELECT COUNT(*) AS c FROM coupon_redemptions WHERE coupon_id = ? AND email = ?",
                [$couponId, strtolower($email)]
            );
        } else {
            return 0;
        }
        return (int)($r['c'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/** Is today inside the customer's birthday window? Only day and month matter. */
function couponBirthdayMatches($dob, $windowDays) {
    if (!$dob) return false;
    $ts = strtotime($dob);
    if (!$ts) return false;

    $today = new DateTime('today');
    foreach ([date('Y'), date('Y') + 1] as $year) {
        $bday = DateTime::createFromFormat('Y-m-d', $year . '-' . date('m-d', $ts));
        if (!$bday) continue;
        $bday->setTime(0, 0);
        $diff = (int)$today->diff($bday)->format('%r%a');
        if ($diff >= -$windowDays && $diff <= $windowDays) return true;
    }
    return false;
}

/**
 * Check a coupon against the current cart and shopper.
 *
 * @return array ok, message, discount, free_shipping
 */
function couponValidate($coupon, $items, $subtotal, $shipping = 0.0) {
    $fail = function ($msg) {
        return ['ok' => false, 'message' => $msg, 'discount' => 0.0, 'free_shipping' => false];
    };

    if (!$coupon)                     return $fail('That code is not recognised.');
    if (empty($coupon['is_active']))  return $fail('That code is no longer active.');

    $now = time();
    if (!empty($coupon['starts_at']) && strtotime($coupon['starts_at']) > $now) {
        return $fail('That code is not available yet.');
    }
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < $now) {
        return $fail('That code has expired.');
    }
    if (!empty($coupon['max_uses']) && (int)$coupon['used_count'] >= (int)$coupon['max_uses']) {
        return $fail('That code has been fully claimed.');
    }
    if ((float)$coupon['min_spend'] > 0 && $subtotal < (float)$coupon['min_spend']) {
        return $fail('Spend ' . formatPrice($coupon['min_spend']) . ' or more to use this code.');
    }

    $userId = isLoggedIn() ? (int)$_SESSION['user_id'] : null;
    $email  = $_SESSION['user_email'] ?? ($_SESSION['guest_email'] ?? null);

    // First-order codes
    if (!empty($coupon['first_order_only'])) {
        if (!$userId) return $fail('Sign in to use this code, it is for first orders only.');
        try {
            $prior = getDB()->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE user_id = ?", [$userId]);
            if ((int)($prior['c'] ?? 0) > 0) return $fail('This code is for first orders only.');
        } catch (Exception $e) {}
    }

    // Birthday codes
    if (!empty($coupon['birthday_only'])) {
        if (!$userId) return $fail('Sign in to use your birthday code.');
        try {
            $u = getDB()->fetchOne("SELECT date_of_birth FROM users WHERE id = ?", [$userId]);
            $window = max(0, (int)$coupon['birthday_window_days']);
            if (!couponBirthdayMatches($u['date_of_birth'] ?? null, $window)) {
                return $fail('This code only works around your birthday. Add your birthday in your profile.');
            }
        } catch (Exception $e) {
            return $fail('Could not check your birthday just now.');
        }
    }

    // Per-customer usage
    $perCustomer = (int)$coupon['max_uses_per_customer'];
    if ($perCustomer > 0 && ($userId || $email)) {
        if (couponUsesByCustomer((int)$coupon['id'], $userId, $email) >= $perCustomer) {
            return $fail('You have already used this code.');
        }
    }

    // Work out the money
    if ($coupon['type'] === 'free_shipping') {
        if ($shipping <= 0) {
            return ['ok' => true, 'message' => 'Shipping is already free on this order.',
                    'discount' => 0.0, 'free_shipping' => true];
        }
        return ['ok' => true, 'message' => 'Free shipping applied.',
                'discount' => 0.0, 'free_shipping' => true];
    }

    $eligible = couponEligibleSubtotal($coupon, $items);
    if ($eligible <= 0) {
        return $fail('This code does not apply to anything in your bag.');
    }

    if ($coupon['type'] === 'percent') {
        $discount = $eligible * ((float)$coupon['value'] / 100);
        if (!empty($coupon['max_discount'])) {
            $discount = min($discount, (float)$coupon['max_discount']);
        }
    } else {
        $discount = (float)$coupon['value'];
    }

    // Never discount below zero.
    $discount = round(min($discount, $eligible), 2);
    if ($discount <= 0) return $fail('This code has no value on your current bag.');

    return ['ok' => true, 'message' => 'Code applied.', 'discount' => $discount, 'free_shipping' => false];
}

// ── Session handling ─────────────────────────────────────────────────────────

function couponSessionCode()  { return $_SESSION['phelyz_coupon'] ?? ''; }
function couponSessionSet($c) { $_SESSION['phelyz_coupon'] = strtoupper(trim($c)); }
function couponSessionClear() { unset($_SESSION['phelyz_coupon']); }

/** Record a redemption once an order is actually placed. */
function couponRecordRedemption($code, $orderId, $discount) {
    $coupon = couponFind($code);
    if (!$coupon) return;
    $db = getDB();
    try {
        $db->insert('coupon_redemptions', [
            'coupon_id' => $coupon['id'],
            'order_id'  => $orderId,
            'user_id'   => isLoggedIn() ? (int)$_SESSION['user_id'] : null,
            'email'     => strtolower($_SESSION['user_email'] ?? ($_SESSION['guest_email'] ?? '')) ?: null,
            'discount'  => $discount,
        ]);
        $db->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$coupon['id']]);
    } catch (Exception $e) {
        // A reporting failure must never lose the order.
    }
}
