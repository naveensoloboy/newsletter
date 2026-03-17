<?php
// ─── api/auth.php ────────────────────────────────────────────
require_once __DIR__ . '/../bootstrap.php';

$users  = new UserModel();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// POST /api/auth.php?action=login
if ($method === 'POST' && $action === 'login') {
    $email    = $body['email']    ?? '';
    $password = $body['password'] ?? '';
    if (!$email || !$password) Response::error('Email and password required');

    $user = $users->findByEmail($email);
    if (!$user || !Auth::verifyPassword($password, $user['password'])) {
        Response::error('Invalid email or password', 401);
    }
    $token = Auth::generateToken($user['_id'], $user['role']);
    Response::json(['token' => $token, 'user' => $users->serialize($user)]);
}

// POST /api/auth.php?action=register
if ($method === 'POST' && $action === 'register') {
    $name     = trim($body['name']     ?? '');
    $email    = trim($body['email']    ?? '');
    $password = $body['password']      ?? '';
    $dept     = $body['department']    ?? '';
    $role     = $body['role']          ?? 'faculty';
    if (!$name || !$email || !$password) Response::error('Name, email and password required');
    if (strlen($password) < 6)          Response::error('Password must be at least 6 characters');
    if ($users->findByEmail($email))    Response::error('Email already registered', 409);

    $id = $users->create($name, $email, $password, $role, $dept);
    Response::json(['message' => 'Registered successfully', 'id' => $id], 201);
}

// GET /api/auth.php?action=me
if ($method === 'GET' && $action === 'me') {
    $identity = Auth::requireAuth();
    $user     = $users->findById($identity['id']);
    if (!$user) Response::notFound('User not found');
    Response::json($users->serialize($user));
}

// POST /api/auth.php?action=change-password
if ($method === 'POST' && $action === 'change-password') {
    $identity = Auth::requireAuth();
    $user     = $users->findById($identity['id']);
    if (!Auth::verifyPassword($body['current_password'] ?? '', $user['password'])) {
        Response::error('Current password incorrect');
    }
    $users->update($identity['id'], ['password' => $body['new_password']]);
    Response::json(['message' => 'Password changed']);
}

// Seed default admin if collection empty
$all = $users->getAll('admin');
if (empty($all)) {
    $users->create('System Administrator', 'admin@college.edu', 'Admin@123', 'admin', 'Management');
}

Response::error('Invalid endpoint', 404);
