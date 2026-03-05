<?php
// ============================================================
// Admin – Guest list di un evento
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/EventManager.php';
require_once LIB_PATH . '/GuestManager.php';
require_once LIB_PATH . '/PrManager.php';

$eventId  = trim($_GET['event_id'] ?? '');
$eventMgr = new EventManager();
$guestMgr = new GuestManager();
$prMgr    = new PrManager();

$event = $eventId ? $eventMgr->find($eventId) : null;
$page_title = $event ? 'Guest List – ' . $event['name'] : 'Guest List';
$active_nav = 'guests';

$msg  = '';
$type = 'success';

// Azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_guest') {
        $guestMgr->create($_POST);
        $msg = 'Ospite aggiunto!';
    } elseif ($action === 'delete_guest') {
        $guestMgr->delete($_POST['id'] ?? '');
        $msg = 'Ospite rimosso.';
    } elseif ($action === 'toggle_vip') {
        $g = $guestMgr->find($_POST['id'] ?? '');
        if ($g) {
            $guestMgr->update($g['id'], ['vip' => !$g['vip']]);
        }
        $msg = 'VIP aggiornato.';
    }
    // Redirect per evitare re-submit
    header('Location: /admin/guests.php?event_id=' . urlencode($eventId) . '&msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

$guests = $event ? $guestMgr->byEvent($event['id']) : [];
$stats  = $event ? $eventMgr->stats($event['id']) : [];
$events = $eventMgr->all();  // per selector
$prs    = $prMgr->active();

// Filtri
$filter = $_GET['filter'] ?? 'all';
if ($filter === 'vip')        $guests = array_filter($guests, fn($g) => !empty($g['vip']));
if ($filter === 'checked_in') $guests = array_filter($guests, fn($g) => !empty($g['checked_in']));
if ($filter === 'pending')    $guests = array_filter($guests, fn($g) => empty($g['checked_in']));

$search = trim($_GET['search'] ?? '');
if ($search && $event) {
    $guests = $guestMgr->search($event['id'], $search);
}

include TMPL_PATH . '/admin_layout.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Selettore evento -->
<div class="card" style="margin-bottom:1rem;padding:.8rem 1.2rem;">
  <form method="GET" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <label style="color:var(--text-muted);font-size:.85rem;">Evento:</label>
    <select name="event_id" onchange="this.form.submit()"
            style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                   padding:.4rem .8rem;border-radius:6px;font-size:.85rem;">
      <option value="">-- Seleziona evento --</option>
      <?php foreach ($events as $e): ?>
      <option value="<?= htmlspecialchars($e['id']) ?>"
              <?= $e['id'] === $eventId ? 'selected' : '' ?>>
        <?= htmlspecialchars($e['name']) ?> – <?= date(DATE_FORMAT, strtotime($e['date'])) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($event): ?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:1rem;">
  <div class="stat-card primary">
    <span class="stat-icon">👥</span>
    <span class="stat-value"><?= $stats['total_guests'] ?></span>
    <span class="stat-label">Ospiti totali</span>
  </div>
  <div class="stat-card success">
    <span class="stat-icon">✅</span>
    <span class="stat-value"><?= $stats['checked_in'] ?></span>
    <span class="stat-label">Check-in</span>
  </div>
  <div class="stat-card accent">
    <span class="stat-icon">⭐</span>
    <span class="stat-value"><?= $stats['vip_total'] ?></span>
    <span class="stat-label">VIP</span>
  </div>
  <div class="stat-card info">
    <span class="stat-icon">🪑</span>
    <span class="stat-value"><?= $stats['tables_used'] ?>/<?= $stats['tables_total'] ?: '∞' ?></span>
    <span class="stat-label">Tavoli</span>
  </div>
</div>

<!-- Guest list table -->
<div class="card">
  <div class="card-header">
    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
      <span>👥 Ospiti – <?= htmlspecialchars($event['name']) ?></span>
      <a href="?event_id=<?= urlencode($eventId) ?>&filter=all"
         class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-outline' ?>">Tutti</a>
      <a href="?event_id=<?= urlencode($eventId) ?>&filter=checked_in"
         class="btn btn-sm <?= $filter==='checked_in'?'btn-success':'btn-outline' ?>">Presenti</a>
      <a href="?event_id=<?= urlencode($eventId) ?>&filter=pending"
         class="btn btn-sm <?= $filter==='pending'?'btn-danger':'btn-outline' ?>">In attesa</a>
      <a href="?event_id=<?= urlencode($eventId) ?>&filter=vip"
         class="btn btn-sm <?= $filter==='vip'?'btn-accent':'btn-outline' ?>">⭐ VIP</a>
    </div>
    <div style="display:flex;gap:.5rem;">
      <form method="GET" class="search-box">
        <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Cerca ospite..." style="min-width:160px;">
      </form>
      <button class="btn btn-primary btn-sm" onclick="openModal('modalAddGuest')">+ Aggiungi</button>
    </div>
  </div>

  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nome</th><th>Telefono</th><th>PAX</th><th>Promoter</th>
            <th>Tavolo</th><th>VIP</th><th>Check-in</th><th>QR</th><th>Azioni</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($guests as $g):
          $prName = '—';
          if (!empty($g['pr_id'])) {
              $pr = $prMgr->find($g['pr_id']);
              $prName = $pr ? $pr['name'] : '?';
          }
        ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($g['name']) ?></strong>
            <?php if (!empty($g['note'])): ?>
            <br><small style="color:var(--text-muted)"><?= htmlspecialchars($g['note']) ?></small>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($g['phone'] ?: '—') ?></td>
          <td><?= (int)($g['pax'] ?? 1) ?></td>
          <td><?= htmlspecialchars($prName) ?></td>
          <td><?= htmlspecialchars($g['table_number'] ?? '—') ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <?= Auth::csrfField() ?>
              <input type="hidden" name="action" value="toggle_vip">
              <input type="hidden" name="id"     value="<?= htmlspecialchars($g['id']) ?>">
              <button class="btn btn-sm <?= !empty($g['vip']) ? 'btn-accent' : 'btn-outline' ?>"
                      title="Toggle VIP">⭐</button>
            </form>
          </td>
          <td>
            <?php if (!empty($g['checked_in'])): ?>
            <span class="badge badge-success">✅ <?= date('H:i', strtotime($g['checkin_at'])) ?></span>
            <?php else: ?>
            <span class="badge badge-muted">In attesa</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-outline btn-sm"
              onclick="showQr('<?= htmlspecialchars($guestMgr->qrUrl($g)) ?>',
                             '<?= htmlspecialchars(addslashes($g['name'])) ?>',
                             '<?= htmlspecialchars(addslashes($event['name'])) ?>')">
              📱 QR
            </button>
          </td>
          <td>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Rimuovere ospite?')">
              <?= Auth::csrfField() ?>
              <input type="hidden" name="action" value="delete_guest">
              <input type="hidden" name="id"     value="<?= htmlspecialchars($g['id']) ?>">
              <button class="btn btn-danger btn-sm">🗑</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($guests)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:2rem;">
          Nessun ospite trovato.
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal aggiungi ospite -->
<div class="modal-backdrop" id="modalAddGuest">
  <div class="modal">
    <div class="modal-header">
      ➕ Aggiungi Ospite
      <button class="modal-close" onclick="closeModal('modalAddGuest')">✕</button>
    </div>
    <form method="POST">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="action"   value="add_guest">
      <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>Nome *</label>
            <input type="text" name="name" required placeholder="Mario Rossi">
          </div>
          <div class="form-group">
            <label>Telefono</label>
            <input type="tel" name="phone" placeholder="+39 333 1234567">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="mario@example.it">
          </div>
          <div class="form-group">
            <label>PAX (persone)</label>
            <input type="number" name="pax" value="1" min="1" max="20">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Tavolo</label>
            <input type="text" name="table_number" placeholder="Es: T12">
          </div>
          <div class="form-group">
            <label>Promoter</label>
            <select name="pr_id">
              <option value="">— Nessuno —</option>
              <?php foreach ($prs as $pr): ?>
              <option value="<?= htmlspecialchars($pr['id']) ?>"><?= htmlspecialchars($pr['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Note</label>
          <input type="text" name="note" placeholder="Note PR, richieste speciali...">
        </div>
        <div class="form-check">
          <input type="checkbox" id="chk_vip" name="vip" value="1">
          <label for="chk_vip">⭐ Ospite VIP</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalAddGuest')">Annulla</button>
        <button type="submit" class="btn btn-primary">Aggiungi ospite</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal QR -->
<div class="modal-backdrop" id="modalQr">
  <div class="modal" style="max-width:320px;text-align:center;">
    <div class="modal-header">
      📱 QR Check-In
      <button class="modal-close" onclick="closeModal('modalQr')">✕</button>
    </div>
    <div class="modal-body">
      <div class="qr-preview">
        <img id="qrImg" src="" alt="QR Code">
        <div class="qr-name" id="qrName"></div>
        <div class="qr-sub"  id="qrEvent"></div>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:center;">
      <a id="qrDownload" href="#" download="qrcode.png" class="btn btn-primary btn-sm">⬇ Scarica</a>
      <button class="btn btn-outline btn-sm" onclick="closeModal('modalQr')">Chiudi</button>
    </div>
  </div>
</div>

<script>
function showQr(url, name, eventName) {
  document.getElementById('qrImg').src     = url;
  document.getElementById('qrName').textContent  = name;
  document.getElementById('qrEvent').textContent = eventName;
  document.getElementById('qrDownload').href     = url;
  openModal('modalQr');
}
</script>

<?php else: ?>
<div class="alert alert-info">Seleziona un evento per vedere la guest list.</div>
<?php endif; ?>

<?php include TMPL_PATH . '/admin_layout_end.php'; ?>
