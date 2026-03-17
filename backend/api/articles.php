<?php
// ─── api/articles.php ────────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

$arModel = new ArticleModel();
$nlModel = new NewsletterModel();
$usModel = new UserModel();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';
$id      = $_GET['id']     ?? '';
$body    = json_decode(file_get_contents('php://input'), true) ?? [];

// POST upload image
if ($method === 'POST' && $action === 'upload-image') {
    Auth::requireRole('faculty', 'admin');
    if (!isset($_FILES['image'])) Response::error('No file uploaded');
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) Response::error('Upload error');
    if ($file['size'] > MAX_FILE_SIZE)    Response::error('File too large (max 16MB)');
    if (!in_array($file['type'], ALLOWED_TYPES)) Response::error('Invalid file type');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $dest     = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) Response::error('Failed to save file');
    Response::json(['url' => '/uploads/' . $filename]);
}

// GET articles by newsletter
if ($method === 'GET' && $action === 'by-newsletter' && $id) {
    $articles = $arModel->getByNewsletter($id);
    Response::json(array_map([$arModel, 'serialize'], $articles));
}

// GET my articles
if ($method === 'GET' && $action === 'my') {
    $identity = Auth::requireRole('faculty', 'admin');
    $articles = $arModel->getByUser($identity['id']);
    Response::json(array_map([$arModel, 'serialize'], $articles));
}

// POST create
if ($method === 'POST' && $action === 'create') {
    $identity = Auth::requireRole('faculty', 'admin');
    if (!($body['title'] ?? '') || !($body['newsletter_id'] ?? '') || !($body['content'] ?? ''))
        Response::error('Title, newsletter_id and content required');
    $artId = $arModel->create(
        $body['newsletter_id'],
        $body['title'],
        $body['author'] ?? '',
        $body['content'],
        $body['image']  ?? '',
        $identity['id']
    );
    Response::json(['message' => 'Article created', 'id' => $artId], 201);
}

// PUT update
if ($method === 'PUT' && $action === 'update' && $id) {
    $identity = Auth::requireRole('faculty', 'admin');
    $article  = $arModel->findById($id);
    if (!$article) Response::notFound();
    if ($identity['role'] !== 'admin' && $article['created_by'] !== $identity['id']) Response::forbidden();
    $arModel->update($id, $body);
    Response::json(['message' => 'Updated']);
}

// DELETE
if ($method === 'DELETE' && $action === 'delete' && $id) {
    $identity = Auth::requireRole('faculty', 'admin');
    $article  = $arModel->findById($id);
    if (!$article) Response::notFound();
    if ($identity['role'] !== 'admin' && $article['created_by'] !== $identity['id']) Response::forbidden();
    $arModel->delete($id);
    Response::json(['message' => 'Deleted']);
}

// POST submit for review
if ($method === 'POST' && $action === 'submit' && $id) {
    $identity = Auth::requireRole('faculty', 'admin');
    $article  = $arModel->findById($id);
    if (!$article) Response::notFound();
    $arModel->update($id, ['status' => 'submitted']);

    // Email admins
    $faculty    = $usModel->findById($identity['id']);
    $newsletter = $nlModel->findById($article['newsletter_id']);
    $admins     = $usModel->getAll('admin');
    foreach ($admins as $admin) {
        Email::articleSubmitted(
            $admin['email'],
            $faculty['name']         ?? 'Faculty',
            $article['title'],
            $newsletter['newsletter_title'] ?? 'Unknown'
        );
    }
    Response::json(['message' => 'Submitted for review']);
}

Response::notFound('Endpoint not found');
