<?php
/**
 * reservations.php — Manage bookings for Aurum & Ember
 */
require_once 'config.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? '';
$data   = readJson(RESERVATIONS_FILE);

// ── Actions ───────────────────────────────────────────────────
if ($action === 'confirm' && $id) {
    foreach ($data as &$r) {
        if ($r['id'] === $id) { $r['status'] = 'confirmed'; break; }
    }
    writeJson(RESERVATIONS_FILE, $data);

    // Send confirmation email to guest
    $res = current(array_filter($data, fn($r) => $r['id'] === $id));
    if ($res) {
        $subject = 'Reservation Confirmed — ' . RESTAURANT_NAME;
        $body    = "Dear {$res['first_name']},\n\nYour reservation on {$res['date']} at {$res['time']} for {$res['guests']} guest(s) has been CONFIRMED.\n\nWe look forward to welcoming you!\n\n" . RESTAURANT_NAME;
        mail($res['email'], $subject, $body, 'From: ' . RESTAURANT_EMAIL);
    }

    setFlash('success', 'Reservation confirmed and guest notified by email.');
    header('Location: reservations.php');
    exit;
}

if ($action === 'cancel' && $id) {
    foreach ($data as &$r) {
        if ($r['id'] === $id) { $r['status'] = 'cancelled'; break; }
    }
    writeJson(RESERVATIONS_FILE, $data);
    setFlash('info', 'Reservation marked as cancelled.');
    header('Location: reservations.php');
    exit;
}

if ($action === 'delete' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array_values(array_filter($data, fn($r) => $r['id'] !== $id));
    writeJson(RESERVATIONS_FILE, $data);
    setFlash('success', 'Reservation deleted.');
    header('Location: reservations.php');
    exit;
}

// ── Filters ───────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'all';
$filterDate   = $_GET['date'] ?? '';
$search       = strtolower(trim($_GET['q'] ?? ''));

$filtered = $data;
if ($filterStatus !== 'all') {
    $filtered = array_filter($filtered, fn($r) => ($r['status'] ?? 'pending') === $filterStatus);
}
if ($filterDate) {
    $filtered = array_filter($filtered, fn($r) => str_starts_with($r['date_raw'] ?? '', $filterDate));
}
if ($search) {
    $filtered = array_filter($filtered, function($r) use ($search) {
        return str_contains(strtolower($r['first_name'] . ' ' . $r['last_name'] . ' ' . $r['email']), $search);
    });
}
$filtered = array_reverse(array_values($filtered));

// Stats
$total     = count($data);
$pending   = count(array_filter($data, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
$confirmed = count(array_filter($data, fn($r) => ($r['status'] ?? '') === 'confirmed'));
$cancelled = count(array_filter($data, fn($r) => ($r['status'] ?? '') === 'cancelled'));

adminHead('Reservations');
adminSidebar('reservations.php');
?>
<div class="main">
<?php adminTopbar('Reservations', 'Manage Bookings'); ?>
<div class="content">
<?php flashHtml(); ?>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-box"><div class="number"><?= $total ?></div><div class="label">Total</div></div>
  <div class="stat-box"><div class="number" style="color:var(--gold)"><?= $pending ?></div><div class="label">Pending</div></div>
  <div class="stat-box"><div class="number" style="color:#81c784"><?= $confirmed ?></div><div class="label">Confirmed</div></div>
  <div class="stat-box"><div class="number" style="color:#e57373"><?= $cancelled ?></div><div class="label">Cancelled</div></div>
</div>

<!-- Filters -->
<div class="card">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="flex:1;min-width:160px;">
      <label>Search Guest</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Name or email…"/>
    </div>
    <div class="form-group" style="min-width:140px;">
      <label>Status</label>
      <select name="status">
        <?php foreach (['all','pending','confirmed','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="min-width:160px;">
      <label>Date</label>
      <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>"/>
    </div>
    <button type="submit" class="btn btn-gold">Filter</button>
    <a href="reservations.php" class="btn btn-outline">Reset</a>
  </form>
</div>

<!-- Table -->
<div class="card">
  <div class="flex-between" style="margin-bottom:16px;">
    <div class="card-title" style="margin:0;border:0;padding:0;">
      Showing <?= count($filtered) ?> reservation<?= count($filtered) !== 1 ? 's' : '' ?>
    </div>
  </div>

  <?php if (empty($filtered)): ?>
    <p class="text-muted">No reservations match your filters.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table>
    <thead>
      <tr>
        <th>Guest</th>
        <th>Contact</th>
        <th>Date &amp; Time</th>
        <th>Guests</th>
        <th>Occasion</th>
        <th>Notes</th>
        <th>Status</th>
        <th>Submitted</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($filtered as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></strong></td>
        <td>
          <div><?= htmlspecialchars($r['email']) ?></div>
          <div style="color:var(--muted);font-size:11px;"><?= htmlspecialchars($r['phone']) ?></div>
        </td>
        <td>
          <div><?= htmlspecialchars($r['date'] ?? $r['date_raw'] ?? '—') ?></div>
          <div style="color:var(--gold);font-size:12px;"><?= htmlspecialchars($r['time'] ?? '') ?></div>
        </td>
        <td style="text-align:center;"><?= htmlspecialchars($r['guests'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['occasion'] ?? '—') ?></td>
        <td style="max-width:160px;font-size:12px;color:var(--muted);"><?= htmlspecialchars(substr($r['notes'] ?? '—', 0, 80)) ?></td>
        <td>
          <?php $st = $r['status'] ?? 'pending'; ?>
          <span class="badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
        </td>
        <td style="font-size:11px;color:var(--muted);"><?= htmlspecialchars(substr($r['submitted_at'] ?? '', 0, 16)) ?></td>
        <td>
          <div class="flex gap-8" style="flex-wrap:nowrap;">
            <?php if ($st === 'pending'): ?>
              <a href="reservations.php?action=confirm&id=<?= urlencode($r['id']) ?>" class="btn btn-sm btn-gold" title="Confirm">✓</a>
              <a href="reservations.php?action=cancel&id=<?= urlencode($r['id']) ?>" class="btn btn-sm btn-danger" title="Cancel"
                 onclick="return confirm('Cancel this reservation?')">✗</a>
            <?php elseif ($st === 'confirmed'): ?>
              <a href="reservations.php?action=cancel&id=<?= urlencode($r['id']) ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Cancel this reservation?')">Cancel</a>
            <?php endif; ?>
            <form method="POST" action="reservations.php?action=delete&id=<?= urlencode($r['id']) ?>" style="display:inline;">
              <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('Permanently delete this reservation?')">🗑</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php adminFoot(); ?>
