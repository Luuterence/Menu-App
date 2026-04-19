<?php
/**
 * menu_manager.php — Manage restaurant menu for Aurum & Ember
 */
require_once 'config.php';
requireLogin();

$action   = $_GET['action'] ?? 'list';
$catSlug  = $_GET['cat'] ?? '';
$itemId   = $_GET['item'] ?? '';
$menu     = readJson(MENU_FILE);

// ── Default menu structure if empty ─────────────────────────
if (empty($menu)) {
    $menu = [
        ['slug' => 'starters',  'label' => 'Starters',  'items' => []],
        ['slug' => 'mains',     'label' => 'Mains',     'items' => []],
        ['slug' => 'sides',     'label' => 'Sides',     'items' => []],
        ['slug' => 'desserts',  'label' => 'Desserts',  'items' => []],
        ['slug' => 'drinks',    'label' => 'Drinks',    'items' => []],
    ];
    writeJson(MENU_FILE, $menu);
}

// ── Helper: find category index ─────────────────────────────
function catIndex(array $menu, string $slug): int {
    foreach ($menu as $i => $c) { if ($c['slug'] === $slug) return $i; }
    return -1;
}

// ── POST: Save item ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    // Add category
    if ($postAction === 'add_category') {
        $label = trim($_POST['cat_label'] ?? '');
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/', '_', $label));
        if ($label && catIndex($menu, $slug) === -1) {
            $menu[] = ['slug' => $slug, 'label' => $label, 'items' => []];
            writeJson(MENU_FILE, $menu);
            setFlash('success', "Category '{$label}' added.");
        }
        header('Location: menu_manager.php');
        exit;
    }

    // Delete category
    if ($postAction === 'delete_category') {
        $slug  = $_POST['cat_slug'] ?? '';
        $menu  = array_values(array_filter($menu, fn($c) => $c['slug'] !== $slug));
        writeJson(MENU_FILE, $menu);
        setFlash('info', 'Category deleted.');
        header('Location: menu_manager.php');
        exit;
    }

    // Add / Edit item
    if (in_array($postAction, ['add_item', 'edit_item'])) {
        $cat  = $_POST['cat'] ?? '';
        $ci   = catIndex($menu, $cat);
        if ($ci === -1) { setFlash('error', 'Category not found.'); header('Location: menu_manager.php'); exit; }

        $newItem = [
            'id'     => $postAction === 'edit_item' ? ($_POST['item_id'] ?? generateId('item')) : generateId('item'),
            'name'   => trim($_POST['name'] ?? ''),
            'desc'   => trim($_POST['desc'] ?? ''),
            'price'  => trim($_POST['price'] ?? ''),
            'badges' => array_filter(array_map('trim', explode(',', $_POST['badges'] ?? ''))),
            'active' => isset($_POST['active']),
        ];

        if (empty($newItem['name'])) { setFlash('error', 'Item name is required.'); header('Location: menu_manager.php?action=add&cat=' . $cat); exit; }

        if ($postAction === 'edit_item') {
            foreach ($menu[$ci]['items'] as &$item) {
                if ($item['id'] === $newItem['id']) { $item = $newItem; break; }
            }
        } else {
            $menu[$ci]['items'][] = $newItem;
        }

        writeJson(MENU_FILE, $menu);
        setFlash('success', 'Menu item saved successfully.');
        header('Location: menu_manager.php?cat=' . urlencode($cat));
        exit;
    }

    // Delete item
    if ($postAction === 'delete_item') {
        $cat  = $_POST['cat'] ?? '';
        $iid  = $_POST['item_id'] ?? '';
        $ci   = catIndex($menu, $cat);
        if ($ci !== -1) {
            $menu[$ci]['items'] = array_values(array_filter($menu[$ci]['items'], fn($i) => $i['id'] !== $iid));
            writeJson(MENU_FILE, $menu);
        }
        setFlash('success', 'Item deleted.');
        header('Location: menu_manager.php?cat=' . urlencode($cat));
        exit;
    }

    // Toggle item active
    if ($postAction === 'toggle_item') {
        $cat = $_POST['cat'] ?? '';
        $iid = $_POST['item_id'] ?? '';
        $ci  = catIndex($menu, $cat);
        if ($ci !== -1) {
            foreach ($menu[$ci]['items'] as &$item) {
                if ($item['id'] === $iid) { $item['active'] = !($item['active'] ?? true); break; }
            }
            writeJson(MENU_FILE, $menu);
        }
        header('Location: menu_manager.php?cat=' . urlencode($cat));
        exit;
    }
}

// ── View helpers ─────────────────────────────────────────────
$activeCat   = $catSlug ? $menu[catIndex($menu, $catSlug)] ?? null : null;
$editingItem = null;
if ($action === 'edit' && $activeCat && $itemId) {
    foreach ($activeCat['items'] as $i) {
        if ($i['id'] === $itemId) { $editingItem = $i; break; }
    }
}

adminHead('Menu Manager');
adminSidebar('menu_manager.php');
?>
<div class="main">
<?php adminTopbar('Menu Manager', 'Items & Categories'); ?>
<div class="content">
<?php flashHtml(); ?>

<div style="display:grid;grid-template-columns:220px 1fr;gap:24px;align-items:start;">

  <!-- Category sidebar -->
  <div>
    <div class="card">
      <div class="card-title">Categories</div>
      <nav style="display:flex;flex-direction:column;gap:4px;margin-bottom:20px;">
        <?php foreach ($menu as $cat): ?>
          <div class="flex-between" style="padding:8px 12px;background:<?= $catSlug === $cat['slug'] ? 'rgba(201,168,76,0.1)' : 'transparent' ?>;border:1px solid <?= $catSlug === $cat['slug'] ? 'rgba(201,168,76,0.3)' : 'transparent' ?>;">
            <a href="menu_manager.php?cat=<?= urlencode($cat['slug']) ?>"
               style="color:<?= $catSlug === $cat['slug'] ? 'var(--gold)' : 'var(--muted)' ?>;text-decoration:none;font-size:11px;letter-spacing:2px;text-transform:uppercase;flex:1;">
              <?= htmlspecialchars($cat['label']) ?>
              <span style="color:var(--muted);font-size:10px;">(<?= count($cat['items']) ?>)</span>
            </a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="post_action" value="delete_category"/>
              <input type="hidden" name="cat_slug" value="<?= htmlspecialchars($cat['slug']) ?>"/>
              <button type="submit" style="background:none;border:none;color:var(--crimson-glow);cursor:pointer;font-size:11px;"
                onclick="return confirm('Delete category and ALL its items?')">✕</button>
            </form>
          </div>
        <?php endforeach; ?>
      </nav>
      <!-- Add category -->
      <form method="POST">
        <input type="hidden" name="post_action" value="add_category"/>
        <div class="form-group">
          <label>New Category</label>
          <input type="text" name="cat_label" placeholder="e.g. Cocktails"/>
        </div>
        <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px;width:100%;">+ Add Category</button>
      </form>
    </div>
  </div>

  <!-- Items area -->
  <div>
    <?php if (!$activeCat): ?>
      <div class="card">
        <p class="text-muted">← Select a category to manage its items.</p>
      </div>
    <?php else: ?>

      <!-- Add / Edit form -->
      <div class="card">
        <div class="card-title"><?= $editingItem ? 'Edit Item' : 'Add Item to ' . htmlspecialchars($activeCat['label']) ?></div>
        <form method="POST">
          <input type="hidden" name="post_action" value="<?= $editingItem ? 'edit_item' : 'add_item' ?>"/>
          <input type="hidden" name="cat" value="<?= htmlspecialchars($activeCat['slug']) ?>"/>
          <?php if ($editingItem): ?>
            <input type="hidden" name="item_id" value="<?= htmlspecialchars($editingItem['id']) ?>"/>
          <?php endif; ?>
          <div class="form-grid">
            <div class="form-group">
              <label>Item Name *</label>
              <input type="text" name="name" value="<?= htmlspecialchars($editingItem['name'] ?? '') ?>" placeholder="e.g. Karoo Lamb Rack" required/>
            </div>
            <div class="form-group">
              <label>Price (e.g. R 285 or Market)</label>
              <input type="text" name="price" value="<?= htmlspecialchars($editingItem['price'] ?? '') ?>" placeholder="R 285"/>
            </div>
            <div class="form-group full">
              <label>Description</label>
              <textarea name="desc" placeholder="Brief description of the dish…"><?= htmlspecialchars($editingItem['desc'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>Badges (comma-separated)</label>
              <input type="text" name="badges" value="<?= htmlspecialchars(implode(', ', $editingItem['badges'] ?? [])) ?>" placeholder="Vegetarian, Gluten Free, Chef's Fav"/>
            </div>
            <div class="form-group" style="justify-content:flex-end;flex-direction:row;align-items:center;gap:10px;">
              <label style="margin:0;font-size:11px;">Active on menu</label>
              <input type="checkbox" name="active" <?= ($editingItem['active'] ?? true) ? 'checked' : '' ?> style="width:auto;"/>
            </div>
          </div>
          <div class="flex gap-8 mt-16">
            <button type="submit" class="btn btn-gold"><?= $editingItem ? 'Save Changes' : 'Add Item' ?></button>
            <?php if ($editingItem): ?>
              <a href="menu_manager.php?cat=<?= urlencode($activeCat['slug']) ?>" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Item list -->
      <div class="card">
        <div class="card-title"><?= htmlspecialchars($activeCat['label']) ?> (<?= count($activeCat['items']) ?> items)</div>
        <?php if (empty($activeCat['items'])): ?>
          <p class="text-muted">No items in this category yet.</p>
        <?php else: ?>
        <table>
          <thead>
            <tr><th>Name</th><th>Description</th><th>Price</th><th>Badges</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($activeCat['items'] as $item): ?>
            <tr>
              <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
              <td style="max-width:200px;font-size:12px;color:var(--muted);"><?= htmlspecialchars(substr($item['desc'] ?? '', 0, 80)) ?></td>
              <td style="color:var(--gold);"><?= htmlspecialchars($item['price'] ?? '—') ?></td>
              <td style="font-size:11px;"><?= htmlspecialchars(implode(', ', $item['badges'] ?? [])) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="post_action" value="toggle_item"/>
                  <input type="hidden" name="cat" value="<?= htmlspecialchars($activeCat['slug']) ?>"/>
                  <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>"/>
                  <button type="submit" class="badge <?= ($item['active'] ?? true) ? 'badge-active' : 'badge-inactive' ?>"
                    style="cursor:pointer;border:none;font-family:inherit;">
                    <?= ($item['active'] ?? true) ? 'Active' : 'Hidden' ?>
                  </button>
                </form>
              </td>
              <td>
                <div class="flex gap-8">
                  <a href="menu_manager.php?cat=<?= urlencode($activeCat['slug']) ?>&action=edit&item=<?= urlencode($item['id']) ?>" class="btn btn-sm btn-outline">Edit</a>
                  <form method="POST">
                    <input type="hidden" name="post_action" value="delete_item"/>
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($activeCat['slug']) ?>"/>
                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>"/>
                    <button type="submit" class="btn btn-sm btn-danger"
                      onclick="return confirm('Delete this item?')">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div><!-- /grid -->

<?php adminFoot(); ?>
