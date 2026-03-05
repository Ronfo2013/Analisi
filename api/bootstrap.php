<?php
// ============================================================
// Bootstrap API – include dipendenze comuni
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/Response.php';
require_once LIB_PATH . '/EventManager.php';
require_once LIB_PATH . '/GuestManager.php';
require_once LIB_PATH . '/PrManager.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Verifica autenticazione per tutte le API admin
if (!Auth::check()) {
    Response::unauthorized();
}

// Verifica CSRF per richieste POST/PUT/DELETE
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Auth::verifyCsrf($token)) {
        Response::error('Token CSRF non valido.', 403);
    }
}

// Legge JSON body se presente
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
