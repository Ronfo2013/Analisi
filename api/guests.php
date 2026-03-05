<?php
// ============================================================
// API /api/guests.php
// GET    ?event_id=X              → lista ospiti evento
// GET    ?id=X                    → dettaglio ospite
// GET    ?event_id=X&search=Y     → ricerca
// POST                            → aggiungi ospite
// POST   ?_method=PUT&id=X       → aggiorna
// POST   ?_method=DELETE&id=X    → elimina
// ============================================================

require_once __DIR__ . '/bootstrap.php';

$mgr    = new GuestManager();
$method = $_SERVER['REQUEST_METHOD'];
$fake   = strtoupper($_POST['_method'] ?? $_GET['_method'] ?? '');
if ($fake && $method === 'POST') {
    $method = $fake;
}
$id      = trim($_GET['id'] ?? '');
$eventId = trim($_GET['event_id'] ?? '');

switch ($method) {
    // ----------------------------------------------------------
    case 'GET':
        if ($id) {
            $guest = $mgr->find($id);
            if (!$guest) {
                Response::notFound('Ospite non trovato.');
            }
            $guestMgr = new GuestManager();
            $qrUrl    = $guestMgr->qrUrl($guest);
            Response::success(['guest' => $guest, 'qr_url' => $qrUrl]);
        }
        if (!$eventId) {
            Response::error('event_id obbligatorio.');
        }
        $search = trim($_GET['search'] ?? '');
        if ($search) {
            Response::success($mgr->search($eventId, $search));
        }
        // Filtri facoltativi
        $guests = $mgr->byEvent($eventId);
        if (isset($_GET['vip'])) {
            $vip    = filter_var($_GET['vip'], FILTER_VALIDATE_BOOLEAN);
            $guests = array_values(array_filter($guests, fn($g) => (bool)$g['vip'] === $vip));
        }
        if (isset($_GET['checked_in'])) {
            $ci     = filter_var($_GET['checked_in'], FILTER_VALIDATE_BOOLEAN);
            $guests = array_values(array_filter($guests, fn($g) => (bool)$g['checked_in'] === $ci));
        }
        Response::success($guests);

    // ----------------------------------------------------------
    case 'POST':
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF, ROLE_PR);
        $data = array_merge($_POST, getJsonBody());
        if (empty($data['name']) || empty($data['event_id'])) {
            Response::error('Nome ed event_id sono obbligatori.');
        }
        // PR può aggiungere solo ospiti con il proprio pr_id
        if (Auth::role() === ROLE_PR) {
            $data['pr_id'] = Auth::user()['id'];
        }
        $guest = $mgr->create($data);
        Response::success(
            ['guest' => $guest, 'qr_url' => $mgr->qrUrl($guest)],
            'Ospite aggiunto con successo.'
        );

    // ----------------------------------------------------------
    case 'PUT':
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);
        if (!$id) {
            Response::error('ID ospite mancante.');
        }
        $data  = array_merge($_POST, getJsonBody());
        $guest = $mgr->update($id, $data);
        if (!$guest) {
            Response::notFound('Ospite non trovato.');
        }
        Response::success($guest, 'Ospite aggiornato.');

    // ----------------------------------------------------------
    case 'DELETE':
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);
        if (!$id) {
            Response::error('ID ospite mancante.');
        }
        if (!$mgr->delete($id)) {
            Response::notFound('Ospite non trovato.');
        }
        Response::success(null, 'Ospite rimosso.');

    // ----------------------------------------------------------
    default:
        Response::error('Metodo non supportato.', 405);
}
