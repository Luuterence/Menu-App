<?php
/**
 * dashboard.php — Admin overview for Aurum & Ember
 */
require_once 'config.php';
requireLogin();

$reservations = readJson(RESERVATIONS_FILE);
$enquiries    = readJson(ENQUIRIES_FILE);
$events       = readJson(EVENTS_FILE);
$menu         = readJson(MENU_FILE);
$specials     = readJson(SPECIALS_FILE);

// ── Stats ──────────────────────────────────────────────────────
$totalRes      = count($reservations);
$pendingRes    = count(array_filter($reservations, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
$confirmedRes  = count(array_filter($reservations, fn($r) => ($r['status'] ?? '') === 'confirmed'));
$todayStr      = date('Y-m-d');
$todayRes      = count(array_filter($reservations, fn($r) => str_starts_with($r['date_raw'] ?? '', $todayStr)));
$newEnquiries  = count(array_filter($enquiries, fn($e) => ($e['status'] ?? 'new') === 'new'));
$upcomingEvents= count(array_filter($events, fn($e) => ($e['date'] ?? '') >= $todayStr && ($e['active'] ?? true)));
$totalMenuItems= array_sum(array_map(fn($cat) => count($cat['items'] ?? []), $menu));
$activeSpecials= count(array_filter($specials, fn($s) => $s['active'] ?? true));

// ── Recent reservations (last 8) ──────────────────────────────
$recent = array_slice(array_reverse($reservations), 0, 8);

adminHead('Dashboard');
adminSidebar('dashboard.php');
?>
<div class="main">
<?php adminTopbar('Dashboard', 'Overview'); ?>
<div class="content">
<?php flashHtml(); ?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-box">
    <div class="number"><?= $totalRes ?></div>
    <div class="label">Total Reservations</div>
  </div>
  <div class="stat-box">
    <div class="number" style="color:var(--gold)"><?= $pendingRes ?></div>
    <div class="label">Pending Approval</div>
  </div>
  <div class="stat-box">
    <div class="number" style="color:#81c784"><?= $confirmedRes ?></div>
    <div class="label">Confirmed</div>
  </div>
  <div class="stat-box">
    <div class="number"><?= $todayRes ?></div>
    <div class="label">Today's Bookings</div>
  </div>
  <div class="stat-box">
    <div class="number" style="color:var(--gold)"><?= $newEnquiries ?></div>
    <div class="label">New Enquiries</div>
  </div>
  <div class="stat-box">
    <div class="number"><?= $upcomingEvents ?></div>
    <div class="label">Upcoming Events</div>
  </div>
  <div class="stat-box">
    <div class="number"><?= $totalMenuItems ?></div>
    <div class="label">Menu Items</div>
  </div>
  <div class="stat-box">
    <div class="number" style="color:#81c784"><?= $activeSpecials ?></div>
    <div class="label">Active Specials</div>
  </div>
</div>

<!-- Quick actions -->
<div class="card">
  <div class="card-title">Quick Actions</div>
  <div class="flex gap-16" style="flex-wrap:wrap;">
    <a href="reservations.php" class="btn btn-crimson">View Reservations</a>
    <a href="events.php?action=add" class="btn btn-outline">+ Add Event</a>
    <a href="specials.php?action=add" class="btn btn-outline">+ Add Special</a>
    <a href="menu_manager.php?action=add" class="btn btn-outline">+ Add Menu Item</a>
    <a href="enquiries.php" class="btn btn-outline">View Enquiries</a>
  </div>
</div>

<!-- Recent reservations -->
<div class="card">
  <div class="card-title">Recent Reservations</div>
  <?php if (empty($recent)): ?>
    <p class="text-muted">No reservations yet.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Guest</th>
        <th>Date</th>
        <th>Time</th>
        <th>Guests</th>
        <th>Occasion</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($recent as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= htmlspecialchars($r['date'] ?? $r['date_raw'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['time'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['guests'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['occasion'] ?? '—') ?></td>
        <td>
          <?php $st = $r['status'] ?? 'pending'; ?>
          <span class="badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
        </td>
        <td>
          <a href="reservations.php?action=confirm&id=<?= urlencode($r['id']) ?>" class="btn btn-sm btn-outline">Confirm</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="mt-16">
    <a href="reservations.php" class="btn btn-outline btn-sm">View All →</a>
  </div>
  <?php endif; ?>
</div>

<?php adminFoot(); ?>
