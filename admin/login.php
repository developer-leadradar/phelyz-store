<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isAdmin()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db   = getDB();
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND role = 'admin' AND is_active = 1",
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role']  = $user['role'];
            redirect('index.php');
        } else {
            $error = 'Invalid credentials or insufficient permissions.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Phelyz</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .pw-wrap { position: relative; }
    .pw-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--muted);
      display: flex; align-items: center; padding: 4px;
      transition: color var(--t);
    }
    .pw-toggle:hover { color: var(--gold); }
    .pw-wrap input { padding-right: 44px; }
  </style>
</head>
<body class="login-page">
  <div class="login-container">
    <div class="login-box">

      <div class="login-header">
        <h1>&#9830; PHELYZ</h1>
        <h2>Admin Panel</h2>
        <p>Sign in to manage your store</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="login-form">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 placeholder="admin@phelyz.com" required autocomplete="username"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="pw-toggle" aria-label="Toggle password"
                    onclick="var i=document.getElementById('password');i.type=i.type==='password'?'text':'password';">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                   stroke-width="2" stroke="currentColor" width="17" height="17">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
          Sign In to Dashboard
        </button>
      </form>

      <div class="login-footer">
        <a href="../index.php">&#8592; Back to store</a>
      </div>

    </div>
  </div>
</body>
</html>
