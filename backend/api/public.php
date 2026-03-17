<?php
// ─── api/public.php ──────────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

$nlModel  = new NewsletterModel();
$arModel  = new ArticleModel();
$subModel = new SubscriberModel();
$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$id       = $_GET['id']     ?? '';
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

// GET published newsletters
if ($method === 'GET' && $action === 'newsletters') {
    $dept   = $_GET['department'] ?? null;
    $search = $_GET['search']     ?? null;
    $nl     = $nlModel->getPublished($dept ?: null, $search ?: null);
    Response::json(array_map([$nlModel, 'serialize'], $nl));
}

// GET single published newsletter (with articles)
if ($method === 'GET' && $action === 'newsletter' && $id) {
    $n = $nlModel->findById($id);
    if (!$n || $n['status'] !== 'published') Response::notFound();
    $data             = $nlModel->serialize($n);
    $articles         = $arModel->getByNewsletter($id);
    $data['articles'] = array_map([$arModel, 'serialize'], $articles);
    Response::json($data);
}

// GET departments list
if ($method === 'GET' && $action === 'departments') {
    $all   = $nlModel->getPublished();
    $depts = array_unique(array_filter(array_column($all, 'department')));
    sort($depts);
    Response::json(array_values($depts));
}

// POST subscribe
if ($method === 'POST' && $action === 'subscribe') {
    $email = trim($body['email'] ?? '');
    $nlId  = $body['newsletter_id'] ?? null;
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) Response::error('Valid email required');
    $ok = $subModel->subscribe($email, $nlId);
    Response::json(['message' => $ok ? 'Subscribed successfully' : 'Already subscribed']);
}

Response::notFound('Endpoint not found');
