<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$db = mongo_database();
$dbReady = $db !== null;

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type, 'message'=>$message]; }
function redirect(string $page): never { header('Location: index.php?page=' . urlencode($page)); exit; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function collection($name) { global $db; return $db->selectCollection($name); }
function count_docs($name): int { try { return collection($name)->countDocuments(); } catch (Throwable $e) { return 0; } }
