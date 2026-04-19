<?php
/**
 * contact.php — Public-facing enquiry form handler for Aurum & Ember
 *
 * POST to this from index.html's contact form.
 * Returns JSON. Also handles the admin enquiries view when accessed with ?admin=1
 */
require_once 'config.php';

// ── Admin mode: view enquiries ───────────────────────────────
if (isset($_GET['admin'])) {
    requireLogin();
    adminEnquiriesView();
    exit;
}

// ── Public POST: receive enquiry ─────────────────────────────
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Sanitise
function field(string $key, bool $required = true): string {
    $v = trim($_POST[$key] ?? '');
    $v = htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if ($required && $v === '') respond(false, "The field '{$key}' is required.");
    return $v;
}
function respond(bool $ok, string $msg): void {
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

$name    = field('name');
$email   = field('email');
$phone   = field('phone', false);
$subject = field('subject');
$message = field('message');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Please enter a valid email address.');
if (strlen($message) < 10) respond(false, 'Please write a longer message.');

$entry = [
    'id'           => generateId('enq'),
    'name'         => $name,
    'email'        => $email,
    'phone'        => $phone,
    'subject'      => $subject,
    'message'      => $message,
    'status'       => 'new',
    'submitted_at' => date('Y-m-d H:i:s'),
    'ip'           => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
];

// Save
$all   = readJson(ENQUIRIES_FILE);
$all[] = $entry;
writeJson(ENQUIRIES_FILE, $all);

// Notify restaurant
$body  = "New website enquiry from {$name} ({$email})\n\n";
$body .= "Subject : {$subject}\n";
$body .= "Phone   : {$phone}\n\n";
$body .= "Message :\n{$message}\n\n";
$body .= "Submitted: {$entry['submitted_at']}";
mail(RESTAURANT_EMAIL, '[Enquiry] ' . $subject, $body, 'From: noreply@aurumandember.co.za' . "\r\nReply-To: {$email}");

// Auto-reply to sender
$reply  = "Dear {$name},\n\nThank you for reaching out to " . RESTAURANT_NAME . ".\n\n";
$reply .= "We have received your enquiry regarding:\n\"{$subject}\"\n\n";
$reply .= "Our team will respond within 24 hours.\n\n";
$reply .= "Kind regards,\n" . RESTAURANT_NAME . "\n" . RESTAURANT_PHONE;
mail($email, 'Thank you for your enquiry — ' . RESTAURANT_NAME, $reply, 'From: ' . RESTAURANT_EMAIL);

respond(true, 'Thank you! Your enquiry has been received. We will be in touch within 24 hours.');

// ═════════════════════════════════════════════════════════════
// ADMIN VIEW (accessed via enquiries.php which includes this)
// ═════════════════════════════════════════════════════════════
function adminEnquiriesView(): void {
    global $_GET, $_POST, $_SERVER;

    $enquiries = readJson(ENQUIRIES_FILE);

    // Mark as read / delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pa = $_POST['post_action'] ?? '';
        $eid = $_POST['id'] ?? '';

        if ($pa === 'mark_read') {
            foreach ($enquiries as &$e) { if ($e['id'] === $eid) { $e['status'] = 'read'; break; } }
            writeJson(ENQUIRIES_FILE, $enquiries);
        }
        if ($pa === 'delete') {
            $enquiries = array_values(array_filter($enquiries, fn($e) => $e['id'] !== $eid));
            writeJson(ENQUIRIES_FILE, $enquiries);
            setFlash('success', 'Enquiry deleted.');
        }
        if ($pa === 'mark_all_read') {
            foreach ($enquiries as &$e) $e['status'] = 'read';
            writeJson(ENQUIRIES_FILE, $enquiries);
            setFlash('info', 'All enquiries marked as read.');
        }
        header('Location: enquiries.php'); exit;
    }

    $filter = $_GET['status'] ?? 'all';
    $filtered = $filter === 'all' ? $enquiries : array_filter($enquiries, fn($e) => ($e['status'] ?? 'new') === $filter);
    $filtered = array_reverse(array_values($filtered));

    $newCount = count(array_filter($enquiries, fn($e) => ($e['status'] ?? 'new') === 'new'));

    adminHead('Enquiries');
    adminSidebar('enquiries.php');
    ?>
    <div class="main">
    <?php adminTopbar('Enquiries', 'Contact Messages'); ?>
    <div class="content">
    <?php flashHtml(); ?>

    <div class="flex-between" style="margin-bottom:20px;">
      <div class="flex gap-8">
        <?php foreach (['all','new','read'] as $s): ?>
          <a href="enquiries.php?status=<?= $s ?>"
             class="btn btn-sm <?= $filter === $s ? 'btn-gold' : 'btn-outline' ?>">
            <?= ucfirst($s) ?><?= $s === 'new' && $newCount ? " ({$newCount})" : '' ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($newCount): ?>
      <form method="POST">
        <input type="hidden" name="post_action" value="mark_all_read"/>
        <button type="submit" class="btn btn-sm btn-outline">Mark All Read</button>
      </form>
      <?php endif; ?>
    </div>

    <?php if (empty($filtered)): ?>
      <div class="card"><p class="text-muted">No enquiries found.</p></div>
    <?php else: ?>
      <?php foreach ($filtered as $e): ?>
        <?php $isNew = ($e['status'] ?? 'new') === 'new'; ?>
        <div class="card" style="margin-bottom:12px;border-left:3px solid <?= $isNew ? 'var(--gold)' : 'transparent' ?>;">
          <div class="flex-between" style="margin-bottom:12px;">
            <div>
              <strong style="color:var(--ivory);"><?= htmlspecialchars($e['name']) ?></strong>
              <?php if ($isNew): ?><span class="badge badge-pending" style="margin-left:8px;">New</span><?php endif; ?>
              <div style="font-size:11px;color:var(--muted);margin-top:4px;">
                <?= htmlspecialchars($e['email']) ?>
                <?= $e['phone'] ? ' · ' . htmlspecialchars($e['phone']) : '' ?>
                · <?= htmlspecialchars(substr($e['submitted_at'], 0, 16)) ?>
              </div>
            </div>
            <div class="flex gap-8">
              <?php if ($isNew): ?>
              <form method="POST">
                <input type="hidden" name="post_action" value="mark_read"/>
                <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>"/>
                <button type="submit" class="btn btn-sm btn-outline">Mark Read</button>
              </form>
              <?php endif; ?>
              <a href="mailto:<?= htmlspecialchars($e['email']) ?>?subject=Re: <?= urlencode($e['subject'] ?? '') ?>"
                 class="btn btn-sm btn-gold">Reply</a>
              <form method="POST">
                <input type="hidden" name="post_action" value="delete"/>
                <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>"/>
                <button type="submit" class="btn btn-sm btn-danger"
                  onclick="return confirm('Delete this enquiry?')">🗑</button>
              </form>
            </div>
          </div>
          <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:8px;">
            <?= htmlspecialchars($e['subject'] ?? '(No subject)') ?>
          </div>
          <p style="font-family:'Cormorant Garamond',serif;font-size:16px;font-style:italic;color:var(--muted);line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($e['message']) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php adminFoot(); ?>
    <?php
}
