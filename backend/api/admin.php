<?php
// ─── api/admin.php ───────────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

$usModel  = new UserModel();
$nlModel  = new NewsletterModel();
$arModel  = new ArticleModel();
$subModel = new SubscriberModel();
$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$id       = $_GET['id']     ?? '';
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

Auth::requireRole('admin');

// GET stats
if ($method === 'GET' && $action === 'stats') {
    Response::json([
        'total_users'           => count($usModel->getAll()),
        'total_faculty'         => count($usModel->getAll('faculty')),
        'total_newsletters'     => count($nlModel->getAll()),
        'published_newsletters' => count($nlModel->getAll(['status' => 'published'])),
        'pending_articles'      => count($arModel->getAll('submitted')),
        'total_subscribers'     => $subModel->count()
    ]);
}

// GET pending articles
if ($method === 'GET' && $action === 'pending-articles') {
    $articles = $arModel->getAll('submitted');
    Response::json(array_map([$arModel, 'serialize'], $articles));
}

// POST approve article
if ($method === 'POST' && $action === 'approve-article' && $id) {
    $article = $arModel->findById($id);
    if (!$article) Response::notFound();
    $arModel->update($id, ['status' => 'approved']);
    $faculty = $usModel->findById($article['created_by']);
    if ($faculty) Email::articleApproved($faculty['email'], $faculty['name'], $article['title']);
    Response::json(['message' => 'Article approved']);
}

// POST reject article
if ($method === 'POST' && $action === 'reject-article' && $id) {
    $article = $arModel->findById($id);
    if (!$article) Response::notFound();
    $reason = $body['reason'] ?? 'Content does not meet guidelines';
    $arModel->update($id, ['status' => 'rejected', 'rejection_reason' => $reason]);
    $faculty = $usModel->findById($article['created_by']);
    if ($faculty) Email::articleRejected($faculty['email'], $faculty['name'], $article['title'], $reason);
    Response::json(['message' => 'Article rejected']);
}

// POST publish newsletter
if ($method === 'POST' && $action === 'publish-newsletter' && $id) {
    $n = $nlModel->findById($id);
    if (!$n) Response::notFound();
    $nlModel->update($id, ['status' => 'published']);

    $emails = $subModel->getAllEmails();

    if (!empty($emails)) {
        // Get articles for this newsletter
        $articles  = $arModel->getAll();
        $nlArticles = array_filter($articles, fn($a) => $a['newsletter_id'] === $id);
        $nlArticles = array_map([$arModel, 'serialize'], array_values($nlArticles));
        $nData      = $nlModel->serialize($n);

        // Generate PDF as string
        try {
            $pdfString = PdfGenerator::generateString($nData, $nlArticles);
            $filename  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nData['newsletter_title']) . '.pdf';
            Email::newsletterPublished($emails, $nData['newsletter_title'], $pdfString, $filename);
        } catch (\Exception $e) {
            error_log('PDF generation failed: ' . $e->getMessage());
            // Fallback: send email without PDF
            $url = FRONTEND_URL . '/frontend/public/newsletter.html?id=' . $id;
            foreach ($emails as $email) {
                Email::send($email, "📰 New Newsletter: {$n['newsletter_title']}",
                    "<p>New newsletter published: <strong>{$n['newsletter_title']}</strong></p>
                     <p><a href='$url'>Read online</a></p>");
            }
        }
    }

    Response::json(['message' => 'Published. ' . count($emails) . ' subscriber(s) notified.']);
}

// ── User Management ────────────────────────────────────────────

// GET all users
if ($method === 'GET' && $action === 'users') {
    $users = $usModel->getAll();
    Response::json(array_map([$usModel, 'serialize'], $users));
}

// POST create user
if ($method === 'POST' && $action === 'create-user') {
    if (!($body['email'] ?? '') || !($body['name'] ?? '') || !($body['password'] ?? ''))
        Response::error('Name, email and password required');
    if ($usModel->findByEmail($body['email'])) Response::error('Email already exists', 409);
    $uid = $usModel->create($body['name'], $body['email'], $body['password'], $body['role'] ?? 'faculty', $body['department'] ?? '');
    Response::json(['message' => 'User created', 'id' => $uid], 201);
}

// PUT update user
if ($method === 'PUT' && $action === 'update-user' && $id) {
    $usModel->update($id, $body);
    Response::json(['message' => 'User updated']);
}

// DELETE user
if ($method === 'DELETE' && $action === 'delete-user' && $id) {
    $usModel->delete($id);
    Response::json(['message' => 'User deleted']);
}

Response::notFound('Endpoint not found');