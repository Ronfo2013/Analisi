<?php
// ============================================================
// API /api/auth.php
// POST { action: "login", email, password }
// POST { action: "logout" }
// GET  → stato sessione corrente
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/Response.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$data   = $method === 'POST'
    ? array_merge($_POST, json_decode(file_get_contents('php://input'), true) ?? [])
    : [];

if ($method === 'GET') {
    session_start_secure();
    Response::json([
        'logged_in' => Auth::check(),
        'user'      => Auth::user(),
        'csrf'      => Auth::csrfToken(),
    ]);
}

$action = $data['action'] ?? '';

switch ($action) {
    case 'login':
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        if (!$email || !$password) {
            Response::error('Email e password richiesti.');
        }
        if (Auth::login($email, $password)) {
            Response::success([
                'user' => Auth::user(),
                'csrf' => Auth::csrfToken(),
            ], 'Login effettuato.');
        }
        Response::error('Credenziali non valide.', 401);

    case 'logout':
        Auth::logout();
        Response::success(null, 'Logout effettuato.');

    default:
        Response::error('Azione non valida.');
}
