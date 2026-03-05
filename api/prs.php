<?php
// ============================================================
// API /api/prs.php – Gestione PR / Promoter
// GET    /api/prs.php             → lista PR + leaderboard
// GET    /api/prs.php?id=X        → dettaglio + stats
// POST                            → crea PR
// POST   ?_method=PUT&id=X       → aggiorna
// POST   ?_method=DELETE&id=X    → elimina
// ============================================================

require_once __DIR__ . '/bootstrap.php';

Auth::requireRole(ROLE_ADMIN);

$mgr    = new PrManager();
$method = $_SERVER['REQUEST_METHOD'];
$fake   = strtoupper($_POST['_method'] ?? $_GET['_method'] ?? '');
if ($fake && $method === 'POST') {
    $method = $fake;
}
$id = trim($_GET['id'] ?? '');

switch ($method) {
    // ----------------------------------------------------------
    case 'GET':
        if ($id) {
            $pr = $mgr->find($id);
            if (!$pr) {
                Response::notFound('PR non trovato.');
            }
            Response::success([
                'pr'    => $pr,
                'stats' => $mgr->stats($id),
            ]);
        }
        Response::success([
            'prs'         => $mgr->all(),
            'leaderboard' => $mgr->leaderboard(),
        ]);

    // ----------------------------------------------------------
    case 'POST':
        $data = array_merge($_POST, getJsonBody());
        if (empty($data['name'])) {
            Response::error('Nome PR obbligatorio.');
        }
        $pr = $mgr->create($data);
        Response::success($pr, 'PR creato con successo.');

    // ----------------------------------------------------------
    case 'PUT':
        if (!$id) {
            Response::error('ID PR mancante.');
        }
        $data = array_merge($_POST, getJsonBody());
        $pr   = $mgr->update($id, $data);
        if (!$pr) {
            Response::notFound('PR non trovato.');
        }
        Response::success($pr, 'PR aggiornato.');

    // ----------------------------------------------------------
    case 'DELETE':
        if (!$id) {
            Response::error('ID PR mancante.');
        }
        if (!$mgr->delete($id)) {
            Response::notFound('PR non trovato.');
        }
        Response::success(null, 'PR eliminato.');

    // ----------------------------------------------------------
    default:
        Response::error('Metodo non supportato.', 405);
}
