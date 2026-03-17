<?php
// ─── api/newsletters.php ─────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

$nlModel = new NewsletterModel();
$arModel = new ArticleModel();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';
$id      = $_GET['id']     ?? '';
$body    = json_decode(file_get_contents('php://input'), true) ?? [];

// GET list
if ($method === 'GET' && $action === 'list') {
    $identity    = Auth::requireRole('faculty', 'admin');
    $filter      = $identity['role'] === 'admin' ? [] : ['created_by' => $identity['id']];
    $newsletters = $nlModel->getAll($filter);
    Response::json(array_map([$nlModel, 'serialize'], $newsletters));
}

// GET single
if ($method === 'GET' && $action === 'get' && $id) {
    $n = $nlModel->findById($id);
    if (!$n) Response::notFound();
    Response::json($nlModel->serialize($n));
}

// POST create
if ($method === 'POST' && $action === 'create') {
    $identity = Auth::requireRole('faculty', 'admin');
    if (!($body['newsletter_title'] ?? '')) Response::error('Newsletter title required');
    $newId = $nlModel->create($body, $identity['id']);
    Response::json(['message' => 'Newsletter created', 'id' => $newId], 201);
}

// PUT update
if ($method === 'PUT' && $action === 'update' && $id) {
    $identity = Auth::requireRole('faculty', 'admin');
    $n        = $nlModel->findById($id);
    if (!$n) Response::notFound();
    if ($identity['role'] !== 'admin' && $n['created_by'] !== $identity['id']) Response::forbidden();
    $nlModel->update($id, $body);
    Response::json(['message' => 'Updated']);
}

// DELETE
if ($method === 'DELETE' && $action === 'delete' && $id) {
    $identity = Auth::requireRole('faculty', 'admin');
    $n        = $nlModel->findById($id);
    if (!$n) Response::notFound();
    if ($identity['role'] !== 'admin' && $n['created_by'] !== $identity['id']) Response::forbidden();
    $nlModel->delete($id);
    Response::json(['message' => 'Deleted']);
}

// POST submit for review
if ($method === 'POST' && $action === 'submit' && $id) {
    Auth::requireRole('faculty', 'admin');
    $nlModel->update($id, ['status' => 'submitted']);
    Response::json(['message' => 'Submitted for review']);
}

// GET download PDF
if ($method === 'GET' && $action === 'pdf' && $id) {
    $n        = $nlModel->findById($id);
    if (!$n) Response::notFound();
    $articles = $arModel->getByNewsletter($id);
    $articles = array_map([$arModel, 'serialize'], $articles);
    $nData    = $nlModel->serialize($n);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nData['newsletter_title'] ?? 'newsletter');

    // Try real PDF
    try {
        $pdfString = PdfGenerator::generateString($nData, $articles);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($pdfString));
        header('Cache-Control: no-cache');
        echo $pdfString;
        exit;
    } catch (\Exception $e) {
        // No PDF library — serve beautiful print-HTML fallback
        $printHtml = PdfGenerator::buildHtml($nData, $articles);
        // Inject print toolbar
        $toolbar = '
        <style>
          @media print { .no-print { display:none!important; } }
          .print-bar { background:#0f1f4b; color:#fff; padding:10px 24px; display:flex; align-items:center; justify-content:space-between; font-family:Arial,sans-serif; font-size:13px; position:sticky; top:0; z-index:99; }
          .print-bar button { background:#c8962a; color:#fff; border:none; padding:8px 20px; border-radius:4px; cursor:pointer; font-size:13px; font-weight:bold; margin-left:8px; }
        </style>';
        $bar = '<div class="print-bar no-print">
          <span>&#128240; ' . htmlspecialchars($nData['newsletter_title'] ?? '') . '</span>
          <div>
            <span style="font-size:12px;opacity:.7;margin-right:12px">No PDF library installed — use Print to save as PDF</span>
            <button onclick="window.print()">&#128424; Print / Save as PDF</button>
            <button onclick="window.close()" style="background:rgba(255,255,255,.15)">Close</button>
          </div>
        </div>';
        $printHtml = str_replace('</head>', $toolbar . '</head>', $printHtml);
        $printHtml = str_replace('<body>', '<body>' . $bar, $printHtml);
        header('Content-Type: text/html; charset=UTF-8');
        echo $printHtml;
        exit;
    }
}

Response::notFound('Endpoint not found');