<?php
// ============================================================
// Admin – Gestione PR / Promoter
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/PrManager.php';
require_once LIB_PATH . '/EventManager.php';

Auth::requireRole(ROLE_ADMIN);

$page_title = 'PR / Promoter';
$active_nav = 'prs';

$prMgr    = new PrManager();
$eventMgr = new EventManager();
$msg      = '';

// Azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_pr') {
        $prMgr->create($_POST);
        $msg = 'Promoter creato!';
    } elseif ($action === 'delete_pr') {
        $prMgr->delete($_POST['id'] ?? '');
        $msg = 'Promoter eliminato.';
    } elseif ($action === 'toggle_pr') {
        $pr = $prMgr->find($_POST['id'] ?? '');
        if ($pr) {
            $prMgr->update($pr['id'], ['active' => !$pr['active']]);
        }
        $msg = 'Stato aggiornato.';
    }
    header('Location: /admin/prs.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

$leaderboard = $prMgr->leaderboard();
$events      = $eventMgr->upcoming();

include TMPL_PATH . '/admin_layout.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    🌟 Promoter / PR
    <button class="btn btn-primary btn-sm" onclick="openModal('modalCreatePr')">+ Nuovo PR</button>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Nome</th><th>Email</th><th>Telefono</th>
            <th>Commissione</th><th>Invitati</th><th>Presenti</th>
            <th>Guadagno</th><th>Stato</th><th>Link</th><th>Azioni</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($leaderboard as $i => $row):
          $pr = $row['pr'];
        ?>
        <tr>
          <td><strong>#<?= $i+1 ?></strong></td>
          <td><strong><?= htmlspecialchars($pr['name']) ?></strong></td>
          <td><?= htmlspecialchars($pr['email'] ?? '—') ?></td>
          <td><?= htmlspecialchars($pr['phone'] ?? '—') ?></td>
          <td><?= number_format(($pr['commission_rate'] ?? 0) * 100, 0) ?>%</td>
          <td><span class="badge badge-primary"><?= $row['guests_total'] ?></span></td>
          <td><span class="badge badge-success"><?= $row['guests_checked'] ?></span></td>
          <td><strong>€ <?= number_format($row['commission'], 2) ?></strong></td>
          <td>
            <?php if (!empty($pr['active'])): ?>
            <span class="badge badge-success">Attivo</span>
            <?php else: ?>
            <span class="badge badge-muted">Inattivo</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($events)): ?>
            <button class="btn btn-outline btn-sm"
              onclick="showLink('<?= htmlspecialchars($pr['link_token']) ?>', '<?= htmlspecialchars(addslashes($pr['name'])) ?>')">
              🔗 Link
            </button>
            <?php else: ?>
            <span class="badge badge-muted">No eventi</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:.3rem;">
              <form method="POST" style="display:inline;">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="toggle_pr">
                <input type="hidden" name="id"     value="<?= htmlspecialchars($pr['id']) ?>">
                <button class="btn btn-outline btn-sm">⏸</button>
              </form>
              <form method="POST" style="display:inline;"
                    onsubmit="return confirm('Eliminare promoter?')">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="delete_pr">
                <input type="hidden" name="id"     value="<?= htmlspecialchars($pr['id']) ?>">
                <button class="btn btn-danger btn-sm">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($leaderboard)): ?>
        <tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:2rem;">
          Nessun promoter registrato.
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal crea PR -->
<div class="modal-backdrop" id="modalCreatePr">
  <div class="modal">
    <div class="modal-header">
      ✨ Nuovo Promoter
      <button class="modal-close" onclick="closeModal('modalCreatePr')">✕</button>
    </div>
    <form method="POST">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="action" value="create_pr">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>Nome *</label>
            <input type="text" name="name" required placeholder="Luca Bianchi">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="luca@example.it">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Telefono</label>
            <input type="tel" name="phone" placeholder="+39 333 9876543">
          </div>
          <div class="form-group">
            <label>Commissione (%)</label>
            <input type="number" name="commission_rate" min="0" max="1" step="0.01"
                   value="0.10" placeholder="0.10 = 10%">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalCreatePr')">Annulla</button>
        <button type="submit" class="btn btn-primary">Crea promoter</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal link PR -->
<div class="modal-backdrop" id="modalLink">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      🔗 Link registrazione – <span id="linkPrName"></span>
      <button class="modal-close" onclick="closeModal('modalLink')">✕</button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1rem;">
        Seleziona l'evento e condividi il link con il promoter:
      </p>
      <div class="form-group">
        <label>Evento</label>
        <select id="linkEventSel" onchange="updateLink()">
          <?php foreach ($events as $e): ?>
          <option value="<?= htmlspecialchars($e['id']) ?>">
            <?= htmlspecialchars($e['name']) ?> – <?= date(DATE_FORMAT, strtotime($e['date'])) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Link da condividere</label>
        <div style="display:flex;gap:.5rem;">
          <input type="text" id="linkOutput" readonly style="flex:1;font-size:.8rem;">
          <button class="btn btn-outline btn-sm" onclick="copyLink()">📋 Copia</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentPrToken = '';

function showLink(token, name) {
  currentPrToken = token;
  document.getElementById('linkPrName').textContent = name;
  updateLink();
  openModal('modalLink');
}

function updateLink() {
  const sel = document.getElementById('linkEventSel');
  if (!sel) return;
  const eventId = sel.value;
  const url = `<?= APP_URL ?>/public/register.php?pr=${currentPrToken}&event=${eventId}`;
  document.getElementById('linkOutput').value = url;
}

function copyLink() {
  const input = document.getElementById('linkOutput');
  input.select();
  document.execCommand('copy');
  const btn = event.target;
  btn.textContent = '✅ Copiato';
  setTimeout(() => btn.textContent = '📋 Copia', 2000);
}
</script>

<?php include TMPL_PATH . '/admin_layout_end.php'; ?>
