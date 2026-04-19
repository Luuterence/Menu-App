<?php
/**
 * login.php — Admin login for Aurum & Ember
 */
require_once 'config.php';

// Already logged in → redirect
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $username;
        $_SESSION['login_time']      = date('Y-m-d H:i:s');
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login — Aurum & Ember</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,400&family=Josefin+Sans:wght@300;400;600&family=Cormorant+Garamond:ital,wght@0,400;1,400&display=swap" rel="stylesheet">
  <style>
    :root {
      --crimson:      #9B1C1C;
      --gold:         #C9A84C;
      --gold-bright:  #F0C860;
      --gold-pale:    #E8D5A3;
      --ivory:        #FAF6EE;
      --charcoal:     #1A1208;
      --charcoal-mid: #2C1F0E;
      --muted:        #8A7560;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Josefin Sans', sans-serif;
      background: var(--charcoal);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 700px; height: 700px;
      background: radial-gradient(ellipse, rgba(155,28,28,0.18) 0%, transparent 70%);
      pointer-events: none;
    }
    .login-box {
      background: var(--charcoal-mid);
      border: 1px solid rgba(201,168,76,0.2);
      padding: 52px 48px;
      width: 100%;
      max-width: 420px;
      position: relative;
      z-index: 1;
    }
    /* top gold line */
    .login-box::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(to right, var(--crimson), var(--gold));
    }
    .login-brand {
      text-align: center;
      margin-bottom: 36px;
    }
    .login-brand .name {
      font-family: 'Playfair Display', serif;
      font-size: 26px;
      font-weight: 900;
      color: var(--gold);
      letter-spacing: 3px;
    }
    .login-brand .sub {
      font-size: 9px;
      letter-spacing: 5px;
      text-transform: uppercase;
      color: var(--muted);
      margin-top: 6px;
    }
    .divider {
      display: flex; align-items: center; gap: 12px;
      margin: 0 0 28px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px;
      background: rgba(201,168,76,0.2);
    }
    .divider span { font-size: 14px; color: var(--gold); opacity: 0.6; }
    .error-msg {
      background: rgba(155,28,28,0.2);
      border: 1px solid rgba(192,57,43,0.4);
      color: #e57373;
      padding: 10px 14px;
      font-size: 12px;
      letter-spacing: 1px;
      margin-bottom: 20px;
    }
    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block;
      font-size: 9px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 8px;
    }
    .form-group input {
      width: 100%;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(201,168,76,0.2);
      padding: 12px 16px;
      color: var(--ivory);
      font-family: 'Josefin Sans', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-group input:focus { border-color: var(--gold); }
    .form-group input::placeholder { color: var(--muted); }
    .btn-login {
      width: 100%;
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 4px;
      text-transform: uppercase;
      padding: 14px;
      background: var(--crimson);
      border: 1px solid var(--crimson);
      color: var(--ivory);
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 8px;
    }
    .btn-login:hover { background: #7B1010; border-color: var(--gold); }
    .login-footer {
      text-align: center;
      margin-top: 28px;
      font-size: 10px;
      letter-spacing: 2px;
      color: var(--muted);
    }
    .login-footer a {
      color: var(--gold-pale);
      text-decoration: none;
    }
    .login-footer a:hover { color: var(--gold); }
    .default-creds {
      margin-top: 20px;
      padding: 10px 14px;
      background: rgba(201,168,76,0.06);
      border: 1px solid rgba(201,168,76,0.15);
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 1px;
      text-align: center;
    }
    .default-creds strong { color: var(--gold-pale); }
  </style>
</head>
<body>
<div class="login-box">
  <div class="login-brand">
    <div class="name">AURUM &amp; EMBER</div>
    <div class="sub">Admin Portal</div>
  </div>
  <div class="divider"><span>✦</span></div>

  <?php if ($error): ?>
    <div class="error-msg">⚠ &nbsp;<?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="admin" autocomplete="username" required/>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="••••••••••••" autocomplete="current-password" required/>
    </div>
    <button type="submit" class="btn-login">Sign In to Admin</button>
  </form>

  <div class="default-creds">
    Default: <strong>admin</strong> / <strong>AurumEmber2025!</strong><br/>
    Change in <code>config.php</code> before going live.
  </div>

  <div class="login-footer">
    <a href="../index.html">← Back to Website</a>
  </div>
</div>
</body>
</html>
