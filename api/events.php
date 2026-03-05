<?php
// ============================================================
// API /api/events.php
// GET    /api/events.php          → lista eventi
// POST   /api/events.php          → crea evento
// GET    /api/events.php?id=X     → dettaglio + stats
// POST   /api/events.php?_method=PUT&id=X  → aggiorna
// POST   /api/events.php?_method=DELETE&id=X → elimina
// ============================================================

require_once __DIR__ . '/bootstrap.php';

$mgr    = new EventManager();
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
            $event = $mgr->find($id);
            if (!$event) {
                Response::notFound('Evento non trovato.');
            }
            $stats = $mgr->stats($id);
            Response::success(['event' => $event, 'stats' => $stats]);
        }
        Response::success($mgr->all());

    // ----------------------------------------------------------
    case 'POST':
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);
        $data = array_merge($_POST, getJsonBody());
        if (empty($data['name']) || empty($data['date'])) {
            Response::error('Nome e data sono obbligatori.');
        }
        $event = $mgr->create($data);
        Response::success($event, 'Evento creato con successo.');

    // ----------------------------------------------------------
    case 'PUT':
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);
        if (!$id) {
            Response::error('ID evento mancante.');
        }
        $data  = array_merge($_POST, getJsonBody());
        $event = $mgr->update($id, $data);
        if (!$event) {
            Response::notFound('Evento non trovato.');
        }
        Response::success($event, 'Evento aggiornato.');

    // ----------------------------------------------------------
    case 'DELETE':
        Auth::requireRole(ROLE_ADMIN);
        if (!$id) {
            Response::error('ID evento mancante.');
        }
        if (!$mgr->delete($id)) {
            Response::notFound('Evento non trovato.');
        }
        Response::success(null, 'Evento eliminato.');

    // ----------------------------------------------------------
    default:
        Response::error('Metodo non supportato.', 405);
}
