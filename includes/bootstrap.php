<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
define('APP_BOOTSTRAPPED', true);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
$db = mongo_database();
$dbReady = $db !== null;

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type, 'message'=>$message]; }
function redirect(string $page): never { header('Location: index.php?page=' . urlencode($page)); exit; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function collection($name) { global $db; return $db->selectCollection($name); }
function count_docs($name): int { return collection($name)->countDocuments(); }

function post_string(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_is_valid(): bool {
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted);
}

function current_user(): ?array {
    $user = $_SESSION['user'] ?? null;
    if (
        !is_array($user) ||
        !isset($user['id'], $user['role'], $user['name']) ||
        !is_string($user['id']) ||
        !in_array($user['role'], ['admin', 'student'], true)
    ) {
        return null;
    }
    if ($user['role'] === 'student' && !isset($user['roll_number'])) return null;
    return $user;
}

function sign_in($account, string $role): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (string)$account['_id'],
        'role' => $role,
        'name' => $account['name'],
    ];
    if ($role === 'student') $_SESSION['user']['roll_number'] = $account['roll_number'];
    unset($_SESSION['csrf_token']);
}

function sign_out(): void {
    unset($_SESSION['user'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

function require_role(string $role): void {
    $user = current_user();
    if ($user === null) {
        flash('error', 'Please sign in to continue.');
        redirect($role === 'admin' ? 'admin_login' : 'login');
    }
    if ($user['role'] !== $role) {
        flash('error', 'You do not have permission to view that page.');
        redirect($user['role'] === 'admin' ? 'dashboard' : 'student_dashboard');
    }

    try {
        $account = collection($role === 'admin' ? 'admins' : 'students')->findOne(['_id' => new MongoDB\BSON\ObjectId($user['id'])]);
    } catch (Throwable $e) {
        error_log('Account authorization check failed: ' . $e->getMessage());
        flash('error', 'Your account could not be verified. Please sign in again.');
        sign_out();
        redirect($role === 'admin' ? 'admin_login' : 'login');
    }

    if (!$account) {
        sign_out();
        flash('error', 'Your account is no longer available. Please sign in again.');
        redirect($role === 'admin' ? 'admin_login' : 'login');
    }

    $_SESSION['user'] = ['id' => (string)$account['_id'], 'role' => $role, 'name' => $account['name']];
    if ($role === 'student') $_SESSION['user']['roll_number'] = $account['roll_number'];
}

function ensure_student_roll_index(): void {
    static $created = false;
    if ($created) return;
    collection('students')->createIndex(
        ['roll_number' => 1],
        [
            'unique' => true,
            'name' => 'unique_student_roll_number',
            'partialFilterExpression' => ['roll_number' => ['$type' => 'string']],
        ]
    );
    $created = true;
}

function ensure_admin_username_index(): void {
    static $created = false;
    if ($created) return;
    collection('admins')->createIndex(['username' => 1], ['unique' => true, 'name' => 'unique_admin_username']);
    $created = true;
}

function ensure_registration_student_event_index(): void {
    static $created = false;
    if ($created) return;
    collection('registrations')->createIndex(
        ['registration_key' => 1],
        [
            'unique' => true,
            'name' => 'unique_student_event_registration',
            'partialFilterExpression' => ['registration_key' => ['$type' => 'string']],
        ]
    );
    collection('registrations')->createIndex(
        ['student_id' => 1, 'registered_at' => -1],
        ['name' => 'registrations_by_student']
    );
    collection('registrations')->createIndex(
        ['event_id' => 1, 'status' => 1],
        ['name' => 'registrations_by_event_status']
    );
    $created = true;
}

function ensure_event_date_index(): void {
    static $created = false;
    if ($created) return;
    collection('events')->createIndex(['event_date' => 1], ['name' => 'events_by_date']);
    $created = true;
}

function object_id_from_string(string $id): ?MongoDB\BSON\ObjectId {
    return preg_match('/^[a-f0-9]{24}$/i', $id) ? new MongoDB\BSON\ObjectId($id) : null;
}

function safe_poster_path($poster): ?string {
    if (!is_string($poster) || !preg_match('~^uploads/events/[a-f0-9]{32}\.(jpg|png|webp)$~', $poster)) return null;
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $poster);
}

function event_datetime($event): ?DateTimeImmutable {
    $date = $event['event_date'] ?? '';
    $time = $event['event_time'] ?? '23:59';
    if (!is_string($date) || !is_string($time)) return null;
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
    return $parsed && $parsed->format('Y-m-d H:i') === $date . ' ' . $time ? $parsed : null;
}

function event_registration_is_open($event): bool {
    $start = event_datetime($event);
    if (!$start || $start <= new DateTimeImmutable()) return false;
    $deadline = $event['registration_deadline'] ?? null;
    if (is_string($deadline) && $deadline !== '') {
        $parsedDeadline = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $deadline);
        if (!$parsedDeadline || $parsedDeadline <= new DateTimeImmutable()) return false;
    }
    return true;
}
