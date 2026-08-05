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
  <title>Admin Login | Phelyz Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@400;600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #CA8A04;
      --gold-light: #D97706;
      --black: #1C1917;
      --stone: #44403C;
      --stone-mid: #78716C;
      --cream: #FAFAF9;
      --cream-dark: #F5F5F4;
      --border: #E7E5E4;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 32px 20px;
      font-family: 'Montserrat', system-ui, sans-serif;
      background: radial-gradient(circle at 20% 0%, #2A2624 0%, var(--black) 55%, #0C0A09 100%);
      color: var(--black);
    }

    .login-shell { width: 100%; max-width: 420px; }

    .login-brand { text-align: center; margin-bottom: 26px; }
    .login-brand img { height: 92px; width: auto; max-width: 78%; display: inline-block; }
    .login-brand span {
      display: inline-block; margin-top: 12px;
      font-size: 10px; font-weight: 700; letter-spacing: 0.22em;
      text-transform: uppercase; color: var(--gold);
      border: 1px solid rgba(202,138,4,0.45);
      border-radius: 99px; padding: 4px 12px;
    }

    .login-card {
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(0,0,0,0.42);
    }
    .login-card::before {
      content: ''; display: block; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light), var(--gold));
    }
    .login-card-inner { padding: 38px 34px 32px; }

    .login-card h1 {
      font-family: 'Cormorant', Georgia, serif;
      font-size: 30px; font-weight: 700; line-height: 1.15;
      color: var(--black); margin-bottom: 6px;
      text-align: center;
    }
    .login-card .sub {
      font-size: 13px; color: var(--stone-mid); margin-bottom: 26px;
      text-align: center;
    }

    .alert-error {
      background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C;
      border-radius: 10px; padding: 12px 14px;
      font-size: 13px; font-weight: 500; margin-bottom: 20px;
    }

    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block; font-size: 11px; font-weight: 700;
      letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--stone); margin-bottom: 7px;
    }
    .form-group input {
      width: 100%; padding: 13px 15px;
      font-family: inherit; font-size: 14.5px; color: var(--black);
      background: var(--cream);
      border: 1.5px solid var(--border); border-radius: 10px;
      transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
    }
    .form-group input:focus {
      outline: none; background: #fff;
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(202,138,4,0.13);
    }
    .form-group input::placeholder { color: #B5AFA9; }

    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 46px; }
    .pw-toggle {
      position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--stone-mid);
      display: flex; align-items: center; padding: 8px; border-radius: 8px;
      transition: color 0.18s;
    }
    .pw-toggle:hover { color: var(--gold); }

    .btn-signin {
      width: 100%; margin-top: 8px; padding: 14px 20px;
      font-family: inherit; font-size: 13px; font-weight: 700;
      letter-spacing: 0.09em; text-transform: uppercase;
      color: #fff; background: var(--black);
      border: none; border-radius: 10px; cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    }
    .btn-signin:hover {
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      transform: translateY(-1px);
      box-shadow: 0 8px 22px rgba(202,138,4,0.34);
    }

    .login-foot {
      text-align: center; padding: 16px 34px 26px;
      border-top: 1px solid var(--cream-dark);
      margin: 6px 0 0;
    }
    .login-foot a {
      font-size: 12.5px; font-weight: 600; color: var(--stone-mid);
      text-decoration: none; transition: color 0.18s;
    }
    .login-foot a:hover { color: var(--gold); }

    @media (max-width: 460px) {
      .login-card-inner { padding: 30px 22px 26px; }
      .login-foot { padding: 14px 22px 22px; }
      .login-card h1 { font-size: 26px; }
      .login-brand img { height: 74px; }
    }
  </style>
</head>
<body>
  <div class="login-shell">

    <div class="login-brand">
      <img src="<?php echo SITE_URL; ?>/assets/images/phelyz-logo-light.svg"
           alt="<?php echo htmlspecialchars(SITE_NAME); ?>">
      <br>
      <span>Admin Panel</span>
    </div>

    <div class="login-card">
      <div class="login-card-inner">

        <h1>Welcome back</h1>
        <p class="sub">Sign in to manage your store</p>

        <?php if ($error): ?>
          <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="admin@phelyzstore.com" required autocomplete="username"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="pw-wrap">
              <input type="password" id="password" name="password"
                     placeholder="••••••••" required autocomplete="current-password">
              <button type="button" class="pw-toggle" aria-label="Show or hide password"
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

          <button type="submit" class="btn-signin">Sign In to Dashboard</button>
        </form>
      </div>

      <div class="login-foot">
        <a href="<?php echo SITE_URL; ?>">&#8592; Back to store</a>
      </div>
    </div>

  </div>
</body>
</html>
