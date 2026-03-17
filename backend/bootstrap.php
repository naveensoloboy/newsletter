<?php
// ─── bootstrap.php ───────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Auth.php';
require_once __DIR__ . '/utils/Email.php';
require_once __DIR__ . '/utils/PdfGenerator.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/NewsletterModel.php';
require_once __DIR__ . '/models/ArticleModel.php';
require_once __DIR__ . '/models/SubscriberModel.php';

// Set CORS headers on every request
header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}