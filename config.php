<?php
/**
 * config.php — Shared configuration & auth for Aurum & Ember backend
 * Include this at the top of every admin PHP file.
 */

// ─── TIMEZONE ────────────────────────────────────────────────────
date_default_timezone_set('Africa/Johannesburg');
session_start();

// ─── ADMIN CREDENTIALS ───────────────────────────────────────────
// Change these before going live!
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', password_hash('AurumEmber2025!', PASSWORD_BCRYPT));

// ─── PATHS ───────────────────────────────────────────────────────
define('DATA_DIR',           __DIR__ . '/data/');
define('RESERVATIONS_FILE',  DATA_DIR . 'reservations.json');
define('MENU_FILE',          DATA_DIR . 'menu.json');
define('EVENTS_FILE',        DATA_DIR . 'events.json');
define('SPECIALS_FILE',      DATA_DIR . 'specials.json');
define('ENQUIRIES_FILE',     DATA_DIR . 'enquiries.json');
define('NEWSLETTER_FILE',    DATA_DIR . 'newsletter.json');

// ─── RESTAURANT INFO ─────────────────────────────────────────────
define('RESTAURANT_NAME',    'Aurum & Ember Restaurant');
define('RESTAURANT_EMAIL',   'reservations@aurumandember.co.za');
define('RESTAURANT_PHONE',   '+27 12 345 6789');

// ─── ENSURE DATA DIR EXISTS ──────────────────────────────────────
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// ─── AUTH HELPERS ────────────────────────────────────────────────

function requireLogin(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

// ─── JSON DATA HELPERS ───────────────────────────────────────────

function readJson(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    return json_decode($raw, true) ?? [];
}

function writeJson(string $file, array $data): bool {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function generateId(string $prefix = 'id'): string {
    return $prefix . '_' . uniqid('', true);
}

// ─── FLASH MESSAGE HELPERS ───────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── SHARED ADMIN STYLES ─────────────────────────────────────────

function adminHead(string $title): void {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{$title} — Aurum & Ember Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Josefin+Sans:wght@300;400;600&family=Cormorant+Garamond:ital,wght@0,400;1,400&display=swap" rel="stylesheet">
  <style>
    :root {
      --crimson:      #9B1C1C;
      --crimson-deep: #6B0F0F;
      --crimson-glow: #C0392B;
      --gold:         #C9A84C;
      --gold-bright:  #F0C860;
      --gold-pale:    #E8D5A3;
      --ivory:        #FAF6EE;
      --charcoal:     #1A1208;
      --charcoal-mid: #2C1F0E;
      --smoke:        #3D2B1A;
      --muted:        #8A7560;
      --sidebar-w:    240px;
      --success:      #2E7D32;
      --error:        #9B1C1C;
      --warning:      #B8860B;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Josefin Sans', sans-serif;
      background: #0f0a05;
      color: var(--ivory);
      display: flex;
      min-height: 100vh;
    }
    /* SIDEBAR */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--charcoal-mid);
      border-right: 1px solid rgba(201,168,76,0.15);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 50;
    }
    .sidebar-brand {
      padding: 28px 20px 20px;
      border-bottom: 1px solid rgba(201,168,76,0.15);
    }
    .sidebar-brand .name {
      font-family: 'Playfair Display', serif;
      font-size: 18px;
      font-weight: 900;
      color: var(--gold);
      letter-spacing: 2px;
    }
    .sidebar-brand .sub {
      font-size: 9px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--muted);
      margin-top: 4px;
    }
    .sidebar-nav { flex: 1; padding: 16px 0; overflow-y: auto; }
    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 20px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--muted);
      text-decoration: none;
      transition: all 0.2s;
      border-left: 2px solid transparent;
    }
    .sidebar-nav a:hover { color: var(--gold-pale); background: rgba(201,168,76,0.05); }
    .sidebar-nav a.active { color: var(--gold); border-left-color: var(--gold); background: rgba(201,168,76,0.08); }
    .sidebar-nav .icon { font-size: 14px; width: 18px; text-align: center; }
    .sidebar-nav .section-label {
      font-size: 8px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--smoke);
      padding: 16px 20px 6px;
    }
    .sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid rgba(201,168,76,0.1);
    }
    .sidebar-footer a {
      display: block;
      font-size: 10px;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--crimson-glow);
      text-decoration: none;
      transition: color 0.2s;
    }
    .sidebar-footer a:hover { color: #e74c3c; }
    /* MAIN */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .topbar {
      background: var(--charcoal-mid);
      border-bottom: 1px solid rgba(201,168,76,0.15);
      padding: 16px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 40;
    }
    .topbar-title {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      font-weight: 700;
      color: var(--ivory);
    }
    .topbar-title span { color: var(--gold); font-style: italic; }
    .topbar-meta {
      font-size: 10px;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--muted);
    }
    .content { padding: 32px; flex: 1; }
    /* FLASH */
    .flash {
      padding: 14px 20px;
      margin-bottom: 24px;
      font-size: 13px;
      letter-spacing: 1px;
      border-left: 3px solid;
    }
    .flash-success { background: rgba(46,125,50,0.15); border-color: #4caf50; color: #81c784; }
    .flash-error   { background: rgba(155,28,28,0.2);  border-color: var(--crimson-glow); color: #e57373; }
    .flash-info    { background: rgba(201,168,76,0.1);  border-color: var(--gold); color: var(--gold-pale); }
    /* CARDS */
    .card {
      background: var(--charcoal-mid);
      border: 1px solid rgba(201,168,76,0.12);
      padding: 24px;
      margin-bottom: 24px;
    }
    .card-title {
      font-family: 'Playfair Display', serif;
      font-size: 18px;
      font-weight: 700;
      color: var(--gold);
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(201,168,76,0.15);
    }
    /* STATS */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }
    .stat-box {
      background: var(--charcoal-mid);
      border: 1px solid rgba(201,168,76,0.12);
      padding: 20px;
      text-align: center;
    }
    .stat-box .number {
      font-family: 'Playfair Display', serif;
      font-size: 40px;
      font-weight: 900;
      color: var(--crimson-glow);
      line-height: 1;
    }
    .stat-box .label {
      font-size: 9px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--gold);
      margin-top: 6px;
    }
    /* TABLE */
    table { width: 100%; border-collapse: collapse; }
    thead th {
      font-size: 9px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--gold);
      padding: 10px 14px;
      text-align: left;
      border-bottom: 1px solid rgba(201,168,76,0.2);
    }
    tbody tr { border-bottom: 1px solid rgba(201,168,76,0.06); transition: background 0.2s; }
    tbody tr:hover { background: rgba(201,168,76,0.04); }
    tbody td { padding: 12px 14px; font-size: 13px; color: var(--gold-pale); vertical-align: middle; }
    /* FORMS */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: span 2; }
    .form-group label {
      font-size: 9px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--gold);
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(201,168,76,0.2);
      padding: 10px 14px;
      color: var(--ivory);
      font-family: 'Josefin Sans', sans-serif;
      font-size: 13px;
      outline: none;
      transition: border-color 0.2s;
      width: 100%;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: var(--gold); }
    .form-group select option { background: var(--charcoal-mid); }
    .form-group textarea { resize: vertical; min-height: 90px; }
    /* BUTTONS */
    .btn {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 3px;
      text-transform: uppercase;
      padding: 10px 24px;
      border: 1px solid;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: all 0.2s;
    }
    .btn-gold    { background: var(--gold); border-color: var(--gold); color: var(--charcoal); }
    .btn-gold:hover { background: var(--gold-bright); border-color: var(--gold-bright); }
    .btn-crimson { background: var(--crimson); border-color: var(--crimson); color: var(--ivory); }
    .btn-crimson:hover { background: var(--crimson-deep); }
    .btn-outline { background: transparent; border-color: var(--gold); color: var(--gold); }
    .btn-outline:hover { background: rgba(201,168,76,0.1); }
    .btn-danger  { background: transparent; border-color: var(--crimson-glow); color: var(--crimson-glow); font-size: 9px; padding: 6px 14px; }
    .btn-danger:hover { background: var(--crimson); border-color: var(--crimson); color: var(--ivory); }
    .btn-sm { padding: 6px 16px; font-size: 9px; }
    /* BADGE */
    .badge {
      display: inline-block;
      font-size: 8px;
      letter-spacing: 2px;
      text-transform: uppercase;
      padding: 3px 10px;
      border-radius: 20px;
    }
    .badge-pending  { background: rgba(201,168,76,0.15); border: 1px solid rgba(201,168,76,0.3); color: var(--gold); }
    .badge-confirmed{ background: rgba(46,125,50,0.2);   border: 1px solid #4caf50; color: #81c784; }
    .badge-cancelled{ background: rgba(155,28,28,0.2);   border: 1px solid var(--crimson-glow); color: #e57373; }
    .badge-active   { background: rgba(46,125,50,0.2);   border: 1px solid #4caf50; color: #81c784; }
    .badge-inactive { background: rgba(100,80,30,0.2);   border: 1px solid rgba(201,168,76,0.3); color: var(--muted); }
    /* MISC */
    .divider { height: 1px; background: rgba(201,168,76,0.1); margin: 24px 0; }
    .text-gold   { color: var(--gold); }
    .text-muted  { color: var(--muted); font-style: italic; }
    .text-right  { text-align: right; }
    .flex        { display: flex; align-items: center; }
    .flex-between{ display: flex; align-items: center; justify-content: space-between; }
    .gap-8       { gap: 8px; }
    .gap-16      { gap: 16px; }
    .mt-16       { margin-top: 16px; }
    .mt-24       { margin-top: 24px; }
    @media (max-width: 768px) {
      .sidebar { width: 0; overflow: hidden; }
      .main    { margin-left: 0; }
      .form-grid { grid-template-columns: 1fr; }
      .form-group.full { grid-column: span 1; }
    }
  </style>
</head>
<body>
HTML;
}

function adminSidebar(string $active): void {
    $links = [
        ['href' => 'dashboard.php',  'icon' => '▦', 'label' => 'Dashboard'],
        ['href' => 'reservations.php','icon' => '📋', 'label' => 'Reservations'],
        ['href' => 'menu_manager.php','icon' => '🍽', 'label' => 'Menu Manager'],
        ['href' => 'events.php',     'icon' => '📅', 'label' => 'Events'],
        ['href' => 'specials.php',   'icon' => '⭐', 'label' => 'Specials'],
        ['href' => 'enquiries.php',  'icon' => '✉', 'label' => 'Enquiries'],
    ];

    echo '<aside class="sidebar">';
    echo '<div class="sidebar-brand"><div class="name">AURUM&amp;EMBER</div><div class="sub">Admin Panel</div></div>';
    echo '<nav class="sidebar-nav">';
    echo '<div class="section-label">Management</div>';
    foreach ($links as $l) {
        $cls = ($l['href'] === $active) ? ' active' : '';
        echo "<a href=\"{$l['href']}\" class=\"{$cls}\"><span class=\"icon\">{$l['icon']}</span>{$l['label']}</a>";
    }
    echo '<div class="section-label">Site</div>';
    echo '<a href="../index.html" target="_blank"><span class="icon">🌐</span>View Website</a>';
    echo '</nav>';
    echo '<div class="sidebar-footer"><a href="logout.php">⏻ &nbsp;Logout</a></div>';
    echo '</aside>';
}

function adminTopbar(string $title, string $subtitle = ''): void {
    $date = date('l, d F Y · H:i');
    echo <<<HTML
    <div class="topbar">
      <div class="topbar-title">{$title} <span>{$subtitle}</span></div>
      <div class="topbar-meta">{$date} SAST</div>
    </div>
HTML;
}

function flashHtml(): void {
    $flash = getFlash();
    if ($flash) {
        $cls = 'flash flash-' . htmlspecialchars($flash['type']);
        $msg = htmlspecialchars($flash['message']);
        echo "<div class=\"{$cls}\">{$msg}</div>";
    }
}

function adminFoot(): void {
    echo '</div></div></body></html>';
}
