<?php
// ─── api/health.php ──────────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

try {
    $db = Database::getInstance()->getDB();
    // Ping MongoDB
    $db->command(['ping' => 1]);
    $mongoStatus = 'connected';
} catch (\Exception $e) {
    $mongoStatus = 'error: ' . $e->getMessage();
}

Response::json([
    'status'  => 'ok',
    'message' => 'Newsletter Management System (PHP) running',
    'php'     => PHP_VERSION,
    'mongodb' => $mongoStatus,
    'time'    => date('Y-m-d H:i:s')
]);
