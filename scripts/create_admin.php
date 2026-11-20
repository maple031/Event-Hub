<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

$username = strtolower(trim($argv[1] ?? ''));
$name = trim($argv[2] ?? '');
$password = getenv('ADMIN_INITIAL_PASSWORD');

if (!preg_match('/^[a-z0-9._-]{3,60}$/', $username) || $name === '' || strlen($name) > 100) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php <username> <name>\n");
    exit(1);
}
if ($password === false || strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Set ADMIN_INITIAL_PASSWORD to a password between 12 and 72 characters.\n");
    exit(1);
}

$db = mongo_database();
if ($db === null) {
    fwrite(STDERR, "MongoDB is unavailable. Check MONGODB_URI and the PHP MongoDB extension.\n");
    exit(1);
}

try {
    $admins = $db->selectCollection('admins');
    $admins->createIndex(['username' => 1], ['unique' => true, 'name' => 'unique_admin_username']);
    if ($admins->findOne(['username' => $username])) {
        fwrite(STDERR, "An admin account already exists for that username.\n");
        exit(1);
    }

    $admins->insertOne([
        'name' => $name,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ]);
    fwrite(STDOUT, "Admin account created for {$username}.\n");
} catch (Throwable $e) {
    error_log('Admin account creation failed: ' . $e->getMessage());
    fwrite(STDERR, "Admin account could not be created. Check the username and database connection.\n");
    exit(1);
}
