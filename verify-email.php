<?php
$pageTitle = "Verify Email";
require_once 'includes/header.php';

$db    = getDB();
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$state = 'invalid'; // invalid | expired | success | already_active

if ($token) {
    $record = $db->fetchOne(
        "SELECT * FROM email_verifications WHERE token = ?",
        [$token]
    );

    if ($record) {
        // Check if already verified
        $user = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$record['user_id']]);

        if ($user && $user['is_active'] == 1) {
            $state = 'already_active';
        } elseif (strtotime($record['expires_at']) < time()) {
            $state = 'expired';
        } else {
            // Activate account
            $db->update('users', ['is_active' => 1], 'id = ?', [$record['user_id']]);
            // Delete used token
            $db->delete('email_verifications', 'token = ?', [$token]);
            $state = 'success';
        }
    }
}
?>
<style>
.verify-page {
    min-height: calc(100vh - 160px);
    display: flex; align-items: center; justify-content: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, var(--off-white) 0%, #FDF8F0 100%);
    position: relative;
}
.verify-page::before {
    content: '';
    position: absolute; top: -100px; right: -100px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(212,175,55,0.07) 0%, transparent 70%);
    pointer-events: none;
}
.verify-card {
    background: var(--white);
    border-radius: 24px;
    border: 1px solid var(--border);
    box-shadow: 0 8px 48px rgba(10,22,40,0.10);
    width: 100%; max-width: 480px;
    overflow: hidden; position: relative; z-index: 1;
    text-align: center;
}
.verify-card::before { content:''; display:block; height:4px; background:linear-gradient(90deg,var(--gold),var(--rose),var(--gold)); }
.verify-card-inner { padding: 52px 44px; }
.verify-big-icon { font-size: 64px; display: block; margin-bottom: 24px; }
.verify-card h2 { font-family:var(--font-h); font-size:30px; font-weight:400; color:var(--navy); margin-bottom:12px; }
.verify-card p  { font-size:15px; color:var(--dark-gray); font-weight:500; line-height:1.7; margin-bottom:32px; }
.btn-verify {
    display:inline-flex; align-items:center; justify-content:center;
    padding:14px 36px;
    background:linear-gradient(135deg,var(--navy),var(--navy-light));
    color:var(--white); border-radius:8px; font-size:15px; font-weight:800;
    font-family:var(--font-b); text-decoration:none; transition:all 0.22s;
}
.btn-verify:hover { background:linear-gradient(135deg,var(--gold),var(--rose)); color:var(--navy); transform:translateY(-2px); box-shadow:0 8px 24px rgba(212,175,55,0.3); }
.btn-verify-outline {
    display:inline-flex; align-items:center; justify-content:center;
    padding:14px 36px; margin-left: 12px;
    background:transparent; border:2px solid var(--border);
    color:var(--text-soft, #4A5568); border-radius:8px; font-size:15px; font-weight:700;
    font-family:var(--font-b); text-decoration:none; transition:all 0.22s;
}
.btn-verify-outline:hover { border-color:var(--navy); color:var(--navy); }
.btn-group { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
@media(max-width:520px){ .verify-card-inner{padding:40px 24px;} .verify-card h2{font-size:24px;} }
</style>

<div class="verify-page">
    <div class="verify-card">
        <div class="verify-card-inner">

            <?php if ($state === 'success'): ?>
                <span class="verify-big-icon">✅</span>
                <h2>Email Verified!</h2>
                <p>Your email address has been confirmed and your account is now active. You can log in and start shopping.</p>
                <div class="btn-group">
                    <a href="login.php" class="btn-verify">Login Now →</a>
                </div>

            <?php elseif ($state === 'already_active'): ?>
                <span class="verify-big-icon">👍</span>
                <h2>Already Verified</h2>
                <p>Your account is already active. Go ahead and log in.</p>
                <div class="btn-group">
                    <a href="login.php" class="btn-verify">Go to Login →</a>
                </div>

            <?php elseif ($state === 'expired'): ?>
                <span class="verify-big-icon">⏰</span>
                <h2>Link Expired</h2>
                <p>This verification link has expired. Verification links are valid for 24 hours. Please register again to get a new link.</p>
                <div class="btn-group">
                    <a href="register.php" class="btn-verify">Register Again →</a>
                    <a href="login.php" class="btn-verify-outline">Login</a>
                </div>

            <?php else: ?>
                <span class="verify-big-icon">⚠️</span>
                <h2>Invalid Link</h2>
                <p>This verification link is not valid. It may have already been used or does not exist.</p>
                <div class="btn-group">
                    <a href="register.php" class="btn-verify">Register →</a>
                    <a href="login.php" class="btn-verify-outline">Login</a>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>