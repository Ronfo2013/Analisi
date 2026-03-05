<?php
// ============================================================
// Admin – Gestione eventi
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/EventManager.php';

$page_title = 'Eventi';
$active_nav = 'events';

$mgr  = new EventManager();
$msg  = '';
$type = 'success';

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);
        $mgr->create($_POST);
        $msg = 'Evento creato con successo!';
    } elseif ($action === 'delete' && Auth::role() === ROLE_ADMIN) {
        $mgr->delete($_POST['id'] ?? '');
        $msg = 'Evento eliminato.';
    } elseif ($action === 'toggle') {
        Auth::requireRole(ROLE_ADMIN, ROLE_STAFF);
        $event = $mgr->find($_POST['id'] ?? '');
        if ($event) {
            $mgr->update($event['id'], ['active' => !($event['active'] ?? true)]);
            $msg = 'Stato evento aggiornato.';
        }
    }
}

$events = $mgr->all();
include TMPL_PATH . '/admin_layout.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.2rem;">
  <div class="card-header">
    🎉 Tutti gli eventi
    <button class="btn btn-primary btn-sm" onclick="openModal('modalCreateEvent')">+ Nuovo evento</button>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nome</th><th>Data</th><th>Luogo</th><th>DJ</th>
            <th>Ospiti</th><th>Check-in</th><th>Stato</th><th>Azioni</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $evt):
          $stats = $mgr->stats($evt['id']);
          $pct   = $stats['total_guests'] > 0 ? round($stats['checked_in'] / $stats['total_guests'] * 100) : 0;
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($evt['name']) ?></strong></td>
          <td><?= date(DATE_FORMAT, strtotime($evt['date'] ?? '')) ?></td>
          <td><?= htmlspecialchars($evt['location'] ?? '—') ?></td>
          <td><?= htmlspecialchars($evt['dj'] ?? '—') ?></td>
          <td><?= $stats['total_guests'] ?></td>
          <td>
            <div class="checkin-bar-wrap">
              <div class="checkin-bar">
                <div class="checkin-bar-fill" style="width:<?= $pct ?>%"></div>
              </div>
              <div class="checkin-bar-label"><?= $stats['checked_in'] ?>/<?= $stats['total_guests'] ?> (<?= $pct ?>%)</div>
            </div>
          </td>
          <td>
            <?php if (!empty($evt['active'])): ?>
              <span class="badge badge-success">Attivo</span>
            <?php else: ?>
              <span class="badge badge-muted">Inattivo</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
              <a href="/admin/guests.php?event_id=<?= urlencode($evt['id']) ?>"
                 class="btn btn-outline btn-sm">👥 Ospiti</a>
              <a href="/admin/checkin.php?event_id=<?= urlencode($evt['id']) ?>"
                 class="btn btn-success btn-sm">✅ Check-in</a>
              <?php if (Auth::role() === ROLE_ADMIN): ?>
              <form method="POST" style="display:inline;"
                    onsubmit="return confirm('Eliminare evento?')">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= htmlspecialchars($evt['id']) ?>">
                <button class="btn btn-danger btn-sm">🗑</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($events)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">
          Nessun evento trovato. Creane uno!
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal crea evento -->
<div class="modal-backdrop" id="modalCreateEvent">
  <div class="modal">
    <div class="modal-header">
      ✨ Nuovo Evento
      <button class="modal-close" onclick="closeModal('modalCreateEvent')">✕</button>
    </div>
    <form method="POST">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label>Nome evento *</label>
          <input type="text" name="name" required placeholder="Es: Opium Night">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Data *</label>
            <input type="date" name="date" required>
          </div>
          <div class="form-group">
            <label>Luogo</label>
            <input type="text" name="location" placeholder="Es: Club XXX, Milano">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Apertura</label>
            <input type="time" name="time_open" value="22:00">
          </div>
          <div class="form-group">
            <label>Chiusura</label>
            <input type="time" name="time_close" value="06:00">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>DJ / Artista</label>
            <input type="text" name="dj" placeholder="Es: DJ Max">
          </div>
          <div class="form-group">
            <label>Tema</label>
            <input type="text" name="theme" placeholder="Es: Black & Gold">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Tavoli disponibili</label>
            <input type="number" name="tables_total" min="0" value="0">
          </div>
          <div class="form-group">
            <label>Max ospiti</label>
            <input type="number" name="max_guests" min="0" value="0" placeholder="0 = illimitato">
          </div>
        </div>
        <div class="form-group">
          <label>Descrizione</label>
          <textarea name="description" rows="2" placeholder="Note sull'evento..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalCreateEvent')">Annulla</button>
        <button type="submit" class="btn btn-primary">Crea evento</button>
      </div>
    </form>
  </div>
</div>

<?php include TMPL_PATH . '/admin_layout_end.php'; ?>
