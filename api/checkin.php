<?php
// ============================================================
// API /api/checkin.php – Check-in via QR token
// POST { token: "..." }
// ============================================================

require_once __DIR__ . '/bootstrap.php';

Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Solo POST.', 405);
}

$data  = array_merge($_POST, getJsonBody());
$token = trim($data['token'] ?? '');

if (!$token) {
    Response::error('Token QR mancante.');
}

$mgr    = new GuestManager();
$result = $mgr->checkIn($token);

if ($result['success']) {
    Response::success($result, $result['message']);
} else {
    Response::error($result['message'], 409, $result['guest'] ?? null);
}
