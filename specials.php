<?php
/**
 * specials.php — Manage daily & weekly specials for Aurum & Ember
 */
require_once 'config.php';
requireLogin();

$action  = $_GET['action'] ?? 'list';
$id      = $_GET['id'] ?? '';
$specials = readJson(SPECIALS_FILE);

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    if (in_array($postAction, ['add', 'edit'])) {
        $entry = [
            'id'          => $postAction === 'edit' ? ($_POST['id'] ?? generateId('spc')) : generateId('spc'),
            'name'        => trim($_POST['name'] ?? ''),
            'category'    => trim($_POST['category'] ?? 'Main'),
            'tag'         => trim($_POST['tag'] ?? ''),
            'desc'        => trim($_POST['desc'] ?? ''),
            'price'       => trim($_POST['price'] ?? ''),
            'badge_label' => trim($_POST['badge_label'] ?? ''),
            'day_of_week' => trim($_POST['day_of_week'] ?? 'Daily'),
            'date_from'   => trim($_POST['date_from'] ?? ''),
            'date_to'     => trim($_POST['date_to'] ?? ''),
            'active'      => isset($_POST['active']),
            'created'     => $postAction === 'add' ? date('Y-m-d H:i:s') : ($_POST['created'] ?? date('Y-m-d H:i:s')),
        ];

        if (empty($entry['name'])) {
            setFlash('error', 'Special name is required.');
            header('Location: specials.php?action=' . ($postAction === 'edit' ? "edit&id={$id}" : 'add'));
            exit;
        }

        if ($postAction === 'edit') {
            foreach ($specials as &$s) { if ($s['id'] === $entry['id']) { $s = $entry; break; } }
        } else {
            $specials[] = $entry;
        }
        writeJson(SPECIALS_FILE, $specials);
        setFlash('success', 'Special saved.');
        header('Location: specials.php');
        exit;
    }

    if ($postAction === 'delete') {
        $specials = array_values(array_filter($specials, fn($s) => $s['id'] !== ($_POST['id'] ?? '')));
        writeJson(SPECIALS_FILE, $specials);
        setFlash('success', 'Special deleted.');
        header('Location: specials.php');
        exit;
    }

    if ($postAction === 'toggle') {
        $tid = $_POST['id'] ?? '';
        foreach ($specials as &$s) { if ($s['id'] === $tid) { $s['active'] = !($s['active'] ?? true); break; } }
        writeJson(SPECIALS_FILE, $specials);
        header('Location: specials.php');
        exit;
    }

    // Activate all / deactivate all
    if ($postAction === 'activate_all') {
        foreach ($specials as &$s) $s['active'] = true;
        writeJson(SPECIALS_FILE, $specials);
        setFlash('info', 'All specials activated.');
        header('Location: specials.php'); exit;
    }
    if ($postAction === 'deactivate_all') {
        foreach ($specials as &$s) $s['active'] = false;
        writeJson(SPECIALS_FILE, $specials);
        setFlash('info', 'All specials deactivated.');
        header('Location: specials.php'); exit;
    }
}

// ── Find editing ─────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    foreach ($specials as $s) { if ($s['id'] === $id) { $editing = $s; break; } }
}

$active   = array_filter($specials, fn($s) => $s['active'] ?? true);
$inactive = array_filter($specials, fn($s) => !($s['active'] ?? true));

adminHead('Specials');
adminSidebar('specials.php');
?>
<div class="main">
<?php adminTopbar('Specials', 'Daily & Weekly Features'); ?>
<div class="content">
<?php flashHtml(); ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Form -->
<div class="card">
  <div class="card-title"><?= $editing ? 'Edit Special: ' . htmlspecialchars($editing['name']) : 'Create New Special' ?></div>
  <form method="POST">
    <input type="hidden" name="post_action" value="<?= $editing ? 'edit' : 'add' ?>"/>
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= htmlspecialchars($editing['id']) ?>"/>
      <input type="hidden" name="created" value="<?= htmlspecialchars($editing['created'] ?? '') ?>"/>
    <?php endif; ?>
    <div class="form-grid">
      <div class="form-group">
        <label>Special Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" placeholder="e.g. Biltong & Brie Bruschetta" required/>
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category">
          <?php foreach (['Starter','Main','Dessert','Drink','Platter','Combo'] as $c): ?>
            <option <?= ($editing['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Tag Label (e.g. "Starter Special")</label>
        <input type="text" name="tag" value="<?= htmlspecialchars($editing['tag'] ?? '') ?>" placeholder="Starter Special"/>
      </div>
      <div class="form-group">
        <label>Badge Text (e.g. "Chef's Pick")</label>
        <input type="text" name="badge_label" value="<?= htmlspecialchars($editing['badge_label'] ?? '') ?>" placeholder="Chef's Pick"/>
      </div>
      <div class="form-group">
        <label>Price</label>
        <input type="text" name="price" value="<?= htmlspecialchars($editing['price'] ?? '') ?>" placeholder="R 95"/>
      </div>
      <div class="form-group">
        <label>Available Day</label>
        <select name="day_of_week">
          <?php foreach (['Daily','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','Weekdays','Weekends'] as $d): ?>
            <option <?= ($editing['day_of_week'] ?? 'Daily') === $d ? 'selected' : '' ?>><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Available From (optional)</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($editing['date_from'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label>Available Until (optional)</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($editing['date_to'] ?? '') ?>"/>
      </div>
      <div class="form-group full">
        <label>Description</label>
        <textarea name="desc" placeholder="Describe this special dish…"><?= htmlspecialchars($editing['desc'] ?? '') ?></textarea>
      </div>
      <div class="form-group" style="flex-direction:row;align-items:center;gap:10px;">
        <label style="margin:0;">Active / Visible on website</label>
        <input type="checkbox" name="active" <?= ($editing['active'] ?? true) ? 'checked' : '' ?> style="width:auto;"/>
      </div>
    </div>
    <div class="flex gap-8 mt-16">
      <button type="submit" class="btn btn-gold"><?= $editing ? 'Save Changes' : 'Create Special' ?></button>
      <a href="specials.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php else: ?>

<!-- Header + bulk actions -->
<div class="flex-between" style="margin-bottom:16px;">
  <div>
    <span style="color:var(--gold);font-size:13px;letter-spacing:2px;">
      <?= count($active) ?> active · <?= count($inactive) ?> hidden
    </span>
  </div>
  <div class="flex gap-8">
    <form method="POST" style="display:inline;">
      <input type="hidden" name="post_action" value="activate_all"/>
      <button type="submit" class="btn btn-sm btn-outline">Activate All</button>
    </form>
    <form method="POST" style="display:inline;">
      <input type="hidden" name="post_action" value="deactivate_all"/>
      <button type="submit" class="btn btn-sm btn-outline">Deactivate All</button>
    </form>
    <a href="specials.php?action=add" class="btn btn-gold">+ New Special</a>
  </div>
</div>

<!-- Specials cards grid -->
<?php if (empty($specials)): ?>
  <div class="card"><p class="text-muted">No specials yet. <a href="specials.php?action=add" class="text-gold">Create one →</a></p></div>
<?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
  <?php foreach ($specials as $s): ?>
    <?php $isActive = $s['active'] ?? true; ?>
    <div style="background:var(--charcoal-mid);border:1px solid rgba(201,168,76,<?= $isActive ? '0.2' : '0.06' ?>);padding:24px;position:relative;opacity:<?= $isActive ? 1 : 0.5 ?>;">
      <!-- Status bar -->
      <div style="position:absolute;top:0;left:0;right:0;height:2px;background:<?= $isActive ? 'linear-gradient(to right,var(--crimson),var(--gold))' : 'rgba(100,80,30,0.3)' ?>;"></div>

      <div class="flex-between" style="margin-bottom:12px;">
        <span style="font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--crimson-glow);background:rgba(155,28,28,0.15);padding:3px 10px;border:1px solid rgba(155,28,28,0.3);">
          <?= htmlspecialchars($s['tag'] ?: $s['category']) ?>
        </span>
        <?php if ($s['badge_label']): ?>
          <span style="font-size:8px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);background:rgba(201,168,76,0.1);padding:3px 10px;border:1px solid rgba(201,168,76,0.2);">
            <?= htmlspecialchars($s['badge_label']) ?>
          </span>
        <?php endif; ?>
      </div>

      <div style="font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--ivory);margin-bottom:8px;">
        <?= htmlspecialchars($s['name']) ?>
      </div>
      <p style="font-family:'Cormorant Garamond',serif;font-size:14px;font-style:italic;color:var(--muted);margin-bottom:14px;line-height:1.6;">
        <?= htmlspecialchars($s['desc']) ?>
      </p>
      <div class="flex-between">
        <div>
          <span style="font-family:'Playfair Display',serif;font-size:22px;color:var(--gold);font-weight:700;">
            <?= htmlspecialchars($s['price'] ?: '—') ?>
          </span>
          <span style="font-size:10px;color:var(--muted);letter-spacing:2px;margin-left:8px;">
            <?= htmlspecialchars($s['day_of_week'] ?? 'Daily') ?>
          </span>
        </div>
        <?php if ($s['date_to']): ?>
          <span style="font-size:10px;color:var(--muted);">Until <?= htmlspecialchars(date('d M', strtotime($s['date_to']))) ?></span>
        <?php endif; ?>
      </div>

      <div class="divider"></div>
      <div class="flex gap-8">
        <form method="POST" style="flex:1;">
          <input type="hidden" name="post_action" value="toggle"/>
          <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>"/>
          <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline' : 'btn-gold' ?>" style="width:100%;">
            <?= $isActive ? 'Deactivate' : 'Activate' ?>
          </button>
        </form>
        <a href="specials.php?action=edit&id=<?= urlencode($s['id']) ?>" class="btn btn-sm btn-outline">Edit</a>
        <form method="POST">
          <input type="hidden" name="post_action" value="delete"/>
          <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>"/>
          <button type="submit" class="btn btn-sm btn-danger"
            onclick="return confirm('Delete this special?')">🗑</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php endif; ?>

<?php adminFoot(); ?>
