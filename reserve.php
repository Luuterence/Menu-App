<?php
/**
 * Aurum & Ember Restaurant — Reservation Backend
 * File: reserve.php
 *
 * Handles POST form submissions from the reservation form.
 * Validates input, saves to a JSON log, and sends email notifications.
 *
 * Usage: Place in the same directory as index.html on your server.
 * Requirements: PHP 7.4+, mail() function enabled (or configure SMTP below).
 */

// ─── CONFIGURATION ───────────────────────────────────────────────
define('RESTAURANT_NAME',  'Aurum & Ember Restaurant');
define('RESTAURANT_EMAIL', 'reservations@aurumandember.co.za'); // Receives booking alerts
define('RESTAURANT_PHONE', '+27 12 345 6789');
define('LOG_FILE',         __DIR__ . '/reservations.json');      // Local log of all bookings
define('TIMEZONE',         'Africa/Johannesburg');

// Optional: SMTP config (uncomment and fill in for production)
// define('SMTP_HOST', 'smtp.yourprovider.co.za');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'your@email.co.za');
// define('SMTP_PASS', 'yourpassword');

// ─── BOOTSTRAP ───────────────────────────────────────────────────
date_default_timezone_set(TIMEZONE);
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ─── HELPERS ─────────────────────────────────────────────────────

/**
 * Sanitise a string field from POST.
 */
function field(string $key, bool $required = true): string
{
    $value = trim($_POST[$key] ?? '');
    $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if ($required && $value === '') {
        respond(false, "The field '{$key}' is required.");
    }
    return $value;
}

/**
 * Send a JSON response and exit.
 */
function respond(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

/**
 * Append a reservation entry to the JSON log file.
 */
function logReservation(array $entry): void
{
    $log = [];
    if (file_exists(LOG_FILE)) {
        $raw = file_get_contents(LOG_FILE);
        $log = json_decode($raw, true) ?? [];
    }
    $log[] = $entry;
    file_put_contents(LOG_FILE, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Send notification email to the restaurant.
 */
function notifyRestaurant(array $entry): bool
{
    $subject = '[New Reservation] ' . $entry['first_name'] . ' ' . $entry['last_name']
             . ' — ' . $entry['date'] . ' at ' . $entry['time'];

    $body  = "═══════════════════════════════════════\n";
    $body .= "  NEW RESERVATION — " . RESTAURANT_NAME . "\n";
    $body .= "═══════════════════════════════════════\n\n";
    $body .= "Name       : {$entry['first_name']} {$entry['last_name']}\n";
    $body .= "Email      : {$entry['email']}\n";
    $body .= "Phone      : {$entry['phone']}\n";
    $body .= "Date       : {$entry['date']}\n";
    $body .= "Time       : {$entry['time']}\n";
    $body .= "Guests     : {$entry['guests']}\n";
    $body .= "Occasion   : {$entry['occasion']}\n";
    $body .= "Notes      : {$entry['notes']}\n\n";
    $body .= "Submitted  : {$entry['submitted_at']}\n\n";
    $body .= "─── Log ID : {$entry['id']} ───\n";

    $headers  = "From: noreply@aurumandember.co.za\r\n";
    $headers .= "Reply-To: {$entry['email']}\r\n";
    $headers .= "X-Mailer: PHP/" . PHP_VERSION;

    return mail(RESTAURANT_EMAIL, $subject, $body, $headers);
}

/**
 * Send confirmation email to the guest.
 */
function confirmGuest(array $entry): bool
{
    $subject = 'Your Reservation at ' . RESTAURANT_NAME . ' — ' . $entry['date'];

    $body  = "Dear {$entry['first_name']},\n\n";
    $body .= "Thank you for choosing " . RESTAURANT_NAME . ".\n\n";
    $body .= "Your reservation request has been received:\n\n";
    $body .= "  Date    : {$entry['date']}\n";
    $body .= "  Time    : {$entry['time']}\n";
    $body .= "  Guests  : {$entry['guests']}\n\n";
    if (!empty($entry['occasion']) && $entry['occasion'] !== 'None') {
        $body .= "  Occasion: {$entry['occasion']}\n\n";
    }
    $body .= "We will confirm your booking within 24 hours.\n\n";
    $body .= "Need to make changes? Call us: " . RESTAURANT_PHONE . "\n\n";
    $body .= "We look forward to welcoming you.\n\n";
    $body .= "Warm regards,\n";
    $body .= RESTAURANT_NAME . "\n";
    $body .= "432 Church Street, Arcadia, Pretoria\n";
    $body .= RESTAURANT_PHONE . "\n";

    $headers  = "From: " . RESTAURANT_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . PHP_VERSION;

    return mail($entry['email'], $subject, $body, $headers);
}

// ─── VALIDATION ──────────────────────────────────────────────────

$firstName = field('first_name');
$lastName  = field('last_name');
$email     = field('email');
$phone     = field('phone');
$date      = field('date');
$time      = field('time');
$guests    = field('guests');
$occasion  = field('occasion', false);
$notes     = field('notes', false);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}

// Validate date is not in the past
$reservationDate = DateTime::createFromFormat('Y-m-d', $date);
$today           = new DateTime('today');
if (!$reservationDate || $reservationDate < $today) {
    respond(false, 'Please select a future date for your reservation.');
}

// Validate time format (basic)
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    respond(false, 'Please select a valid time.');
}

// Validate guests
$allowedGuests = ['1','2','3','4','5','6','7','8','9','9+'];
if (!in_array($guests, $allowedGuests, true)) {
    respond(false, 'Please select the number of guests.');
}

// Sanitise phone (allow +, spaces, digits, dashes)
$phone = preg_replace('/[^\d\s\+\-\(\)]/', '', $phone);
if (strlen($phone) < 7) {
    respond(false, 'Please enter a valid phone number.');
}

// ─── BUILD ENTRY ─────────────────────────────────────────────────

$entry = [
    'id'           => uniqid('res_', true),
    'first_name'   => $firstName,
    'last_name'    => $lastName,
    'email'        => $email,
    'phone'        => $phone,
    'date'         => $reservationDate->format('l, d F Y'),
    'date_raw'     => $date,
    'time'         => $time,
    'guests'       => $guests,
    'occasion'     => $occasion ?: 'None',
    'notes'        => $notes ?: '—',
    'status'       => 'pending',
    'submitted_at' => date('Y-m-d H:i:s'),
    'ip'           => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
];

// ─── SAVE & NOTIFY ───────────────────────────────────────────────

// Log to file
try {
    logReservation($entry);
} catch (Throwable $e) {
    // Non-fatal — log failure shouldn't block guest confirmation
    error_log('[AurumEmber] Log write failed: ' . $e->getMessage());
}

// Notify restaurant
notifyRestaurant($entry);

// Confirm guest
confirmGuest($entry);

// ─── RESPOND ─────────────────────────────────────────────────────

respond(true, 'Reservation received.', [
    'reservation_id' => $entry['id'],
    'date'           => $entry['date'],
    'time'           => $entry['time'],
    'guests'         => $entry['guests'],
]);
