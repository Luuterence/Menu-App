<?php
/**
 * events.php — Manage events for Aurum & Ember
 */
require_once 'config.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? '';
$events = readJson(EVENTS_FILE);
$today  = date('Y-m-d');

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    if (in_array($postAction, ['add', 'edit'])) {
        $entry = [
            'id'       => $postAction === 'edit' ? ($_POST['id'] ?? generateId('evt')) : generateId('evt'),
            'name'     => trim($_POST['name'] ?? ''),
            'type'     => trim($_POST['type'] ?? ''),
            'date'     => trim($_POST['date'] ?? ''),
            'time'     => trim($_POST['time'] ?? ''),
            'end_time' => trim($_POST['end_time'] ?? ''),
            'price'    => trim($_POST['price'] ?? ''),
            'desc'     => trim($_POST['desc'] ?? ''),
            'capacity' => intval($_POST['capacity'] ?? 0),
            'featured' => isset($_POST['featured']),
            'active'   => isset($_POST['active']),
            'created'  => $postAction === 'add' ? date('Y-m-d H:i:s') : ($_POST['created'] ?? date('Y-m-d H:i:s')),
        ];

        if (empty($entry['name']) || empty($entry['date'])) {
            setFlash('error', 'Name and Date are required.');
            header('Location: events.php?action=' . ($postAction === 'edit' ? "edit&id={$id}" : 'add'));
            exit;
        }

        if ($postAction === 'edit') {
            foreach ($events as &$e) { if ($e['id'] === $entry['id']) { $e = $entry; break; } }
        } else {
            $events[] = $entry;
        }
        writeJson(EVENTS_FILE, $events);
        setFlash('success', 'Event saved successfully.');
        header('Location: events.php');
        exit;
    }

    if ($postAction === 'delete') {
        $events = array_values(array_filter($events, fn($e) => $e['id'] !== ($_POST['id'] ?? '')));
        writeJson(EVENTS_FILE, $events);
        setFlash('success', 'Event deleted.');
        header('Location: events.php');
        exit;
    }

    if ($postAction === 'toggle') {
        $tid = $_POST['id'] ?? '';
        foreach ($events as &$e) { if ($e['id'] === $tid) { $e['active'] = !($e['active'] ?? true); break; } }
        writeJson(EVENTS_FILE, $events);
        header('Location: events.php');
        exit;
    }
}

// ── Find editing event ──────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    foreach ($events as $e) { if ($e['id'] === $id) { $editing = $e; break; } }
}

// ── Sort events by date ─────────────────────────────────────
usort($events, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
$upcoming = array_filter($events, fn($e) => ($e['date'] ?? '') >= $today);
$past     = array_filter($events, fn($e) => ($e['date'] ?? '') < $today);

adminHead('Events');
adminSidebar('events.php');
?>
<div class="main">
<?php adminTopbar('Events', 'Create & Manage'); ?>
<div class="content">
<?php flashHtml(); ?>

<!-- Add / Edit Form -->
<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="card">
  <div class="card-title"><?= $editing ? 'Edit Event: ' . htmlspecialchars($editing['name']) : 'Create New Event' ?></div>
  <form method="POST">
    <input type="hidden" name="post_action" value="<?= $editing ? 'edit' : 'add' ?>"/>
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= htmlspecialchars($editing['id']) ?>"/>
      <input type="hidden" name="created" value="<?= htmlspecialchars($editing['created'] ?? '') ?>"/>
    <?php endif; ?>
    <div class="form-grid">
      <div class="form-group">
        <label>Event Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" placeholder="e.g. Cape Winelands Pairing Dinner" required/>
      </div>
      <div class="form-group">
        <label>Event Type</label>
        <select name="type">
          <?php foreach (['Featured Event','Weekly','Monthly','Special Event','Private','Workshop','Live Music','Brunch','Dinner'] as $t): ?>
            <option <?= ($editing['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Date *</label>
        <input type="date" name="date" value="<?= htmlspecialchars($editing['date'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label>Start Time</label>
        <input type="time" name="time" value="<?= htmlspecialchars($editing['time'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label>End Time</label>
        <input type="time" name="end_time" value="<?= htmlspecialchars($editing['end_time'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label>Price / Ticket Info</label>
        <input type="text" name="price" value="<?= htmlspecialchars($editing['price'] ?? '') ?>" placeholder="e.g. R 650 per person"/>
      </div>
      <div class="form-group">
        <label>Max Capacity (0 = unlimited)</label>
        <input type="number" name="capacity" value="<?= htmlspecialchars($editing['capacity'] ?? 0) ?>" min="0"/>
      </div>
      <div class="form-group" style="flex-direction:row;align-items:center;gap:20px;justify-content:flex-start;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="featured" <?= ($editing['featured'] ?? false) ? 'checked' : '' ?> style="width:auto;"/> Featured
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="active" <?= ($editing['active'] ?? true) ? 'checked' : '' ?> style="width:auto;"/> Active
        </label>
      </div>
      <div class="form-group full">
        <label>Description</label>
        <textarea name="desc" placeholder="Describe the event experience…" style="min-height:120px;"><?= htmlspecialchars($editing['desc'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="flex gap-8 mt-16">
      <button type="submit" class="btn btn-gold"><?= $editing ? 'Save Changes' : 'Create Event' ?></button>
      <a href="events.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>
<?php else: ?>

<!-- Upcoming events table -->
<div class="flex-between" style="margin-bottom:16px;">
  <h3 style="color:var(--gold);font-family:'Playfair Display',serif;">Upcoming Events (<?= count($upcoming) ?>)</h3>
  <a href="events.php?action=add" class="btn btn-gold">+ Create Event</a>
</div>

<div class="card">
  <?php if (empty($upcoming)): ?>
    <p class="text-muted">No upcoming events. <a href="events.php?action=add" class="text-gold">Create one →</a></p>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>Date</th><th>Event</th><th>Type</th><th>Time</th><th>Price</th><th>Cap.</th><th>Featured</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($upcoming as $e): ?>
      <tr>
        <td style="color:var(--gold);white-space:nowrap;"><?= htmlspecialchars(date('D d M Y', strtotime($e['date']))) ?></td>
        <td><strong><?= htmlspecialchars($e['name']) ?></strong></td>
        <td style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($e['type'] ?? '—') ?></td>
        <td><?= htmlspecialchars(($e['time'] ?? '') . ($e['end_time'] ? ' – ' . $e['end_time'] : '')) ?></td>
        <td><?= htmlspecialchars($e['price'] ?? '—') ?></td>
        <td style="text-align:center;"><?= $e['capacity'] ? $e['capacity'] : '∞' ?></td>
        <td style="text-align:center;"><?= ($e['featured'] ?? false) ? '⭐' : '—' ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="post_action" value="toggle"/>
            <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>"/>
            <button type="submit" class="badge <?= ($e['active'] ?? true) ? 'badge-active' : 'badge-inactive' ?>"
              style="cursor:pointer;border:none;font-family:inherit;">
              <?= ($e['active'] ?? true) ? 'Active' : 'Hidden' ?>
            </button>
          </form>
        </td>
        <td>
          <div class="flex gap-8">
            <a href="events.php?action=edit&id=<?= urlencode($e['id']) ?>" class="btn btn-sm btn-outline">Edit</a>
            <form method="POST">
              <input type="hidden" name="post_action" value="delete"/>
              <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>"/>
              <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('Delete this event?')">🗑</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php if (!empty($past)): ?>
<details style="margin-top:8px;">
  <summary style="cursor:pointer;color:var(--muted);font-size:11px;letter-spacing:2px;text-transform:uppercase;padding:8px 0;">
    Past Events (<?= count($past) ?>)
  </summary>
  <div class="card" style="margin-top:12px;opacity:0.7;">
    <table>
      <thead><tr><th>Date</th><th>Event</th><th>Type</th><th>Price</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach (array_reverse(array_values($past)) as $e): ?>
        <tr>
          <td style="color:var(--muted);"><?= htmlspecialchars(date('d M Y', strtotime($e['date']))) ?></td>
          <td><?= htmlspecialchars($e['name']) ?></td>
          <td style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($e['type'] ?? '—') ?></td>
          <td><?= htmlspecialchars($e['price'] ?? '—') ?></td>
          <td>
            <form method="POST">
              <input type="hidden" name="post_action" value="delete"/>
              <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>"/>
              <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('Delete this event?')">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</details>
<?php endif; ?>

<?php endif; ?>

<?php adminFoot(); ?>
