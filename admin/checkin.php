<?php
// ============================================================
// Admin – Pannello Check-In (scanner QR manuale)
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/EventManager.php';
require_once LIB_PATH . '/GuestManager.php';

$page_title = 'Check-In';
$active_nav = 'checkin';
$eventId    = trim($_GET['event_id'] ?? '');
$eventMgr   = new EventManager();
$guestMgr   = new GuestManager();

$events = $eventMgr->all();
$event  = $eventId ? $eventMgr->find($eventId) : null;
$stats  = $event   ? $eventMgr->stats($eventId) : [];

include TMPL_PATH . '/admin_layout.php';
?>

<!-- Selettore evento -->
<div class="card" style="margin-bottom:1rem;padding:.8rem 1.2rem;">
  <form method="GET" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <label style="color:var(--text-muted);font-size:.85rem;">Evento:</label>
    <select name="event_id" onchange="this.form.submit()"
            style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                   padding:.4rem .8rem;border-radius:6px;font-size:.85rem;">
      <option value="">-- Seleziona evento --</option>
      <?php foreach ($events as $e): ?>
      <option value="<?= htmlspecialchars($e['id']) ?>" <?= $e['id']===$eventId?'selected':'' ?>>
        <?= htmlspecialchars($e['name']) ?> – <?= date(DATE_FORMAT, strtotime($e['date'])) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($event): ?>

<!-- Stats live -->
<div class="stats-grid" style="margin-bottom:1rem;" id="statsGrid">
  <div class="stat-card success">
    <span class="stat-icon">✅</span>
    <span class="stat-value" id="statChecked"><?= $stats['checked_in'] ?></span>
    <span class="stat-label">Check-in effettuati</span>
  </div>
  <div class="stat-card primary">
    <span class="stat-icon">👥</span>
    <span class="stat-value" id="statTotal"><?= $stats['total_guests'] ?></span>
    <span class="stat-label">Ospiti registrati</span>
  </div>
  <div class="stat-card accent">
    <span class="stat-icon">⭐</span>
    <span class="stat-value" id="statVip"><?= $stats['vip_total'] ?></span>
    <span class="stat-label">VIP totali</span>
  </div>
  <div class="stat-card info">
    <span class="stat-icon">⏳</span>
    <span class="stat-value" id="statPending"><?= $stats['total_guests'] - $stats['checked_in'] ?></span>
    <span class="stat-label">In attesa</span>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">

  <!-- Scanner QR / Token manuale -->
  <div class="card">
    <div class="card-header">📱 Check-In Rapido</div>
    <div class="card-body">
      <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1rem;">
        Inserisci il token QR dell'ospite o scansionalo con la webcam (vedi sotto).
      </p>

      <div id="checkinResult" style="display:none;" class="alert"></div>

      <div class="form-group">
        <label>Token QR ospite</label>
        <div style="display:flex;gap:.5rem;">
          <input type="text" id="qrTokenInput" placeholder="Incolla o digita token..."
                 autofocus style="flex:1;" autocomplete="off">
          <button class="btn btn-success" onclick="doCheckin()">✅ Check-in</button>
        </div>
      </div>

      <!-- Ricerca per nome -->
      <hr style="border-color:var(--border);margin:1rem 0;">
      <div class="form-group">
        <label>Oppure cerca per nome</label>
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input type="text" id="nameSearch" placeholder="Nome ospite..."
                 oninput="searchGuest(this.value)">
        </div>
      </div>
      <div id="searchResults"></div>
    </div>
  </div>

  <!-- Ultimi check-in -->
  <div class="card">
    <div class="card-header">
      ⏱ Ultimi check-in
      <button class="btn btn-outline btn-sm" onclick="refreshCheckins()">↻ Aggiorna</button>
    </div>
    <div class="card-body" style="padding:0;">
      <div id="recentCheckins" style="max-height:440px;overflow-y:auto;">
        <table>
          <thead><tr><th>Ospite</th><th>PAX</th><th>Ora</th></tr></thead>
          <tbody id="recentBody">
            <?php
            $allGuests  = $guestMgr->byEvent($eventId);
            $checkedIn  = array_filter($allGuests, fn($g) => !empty($g['checked_in']));
            usort($checkedIn, fn($a, $b) => strcmp($b['checkin_at'] ?? '', $a['checkin_at'] ?? ''));
            foreach (array_slice($checkedIn, 0, 20) as $g): ?>
            <tr>
              <td>
                <?= htmlspecialchars($g['name']) ?>
                <?php if (!empty($g['vip'])): ?><span class="badge badge-accent">VIP</span><?php endif; ?>
              </td>
              <td><?= (int)($g['pax'] ?? 1) ?></td>
              <td><?= date('H:i', strtotime($g['checkin_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
const EVENT_ID   = '<?= htmlspecialchars($eventId) ?>';
const CSRF_TOKEN = '<?= Auth::csrfToken() ?>';

function doCheckin(token) {
  const t = token || document.getElementById('qrTokenInput').value.trim();
  if (!t) { showResult('Inserisci un token QR.', false); return; }

  fetch('/api/checkin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      token: t,
      [CSRF_TOKEN_NAME]: CSRF_TOKEN
    })
  })
  .then(r => r.json())
  .then(data => {
    showResult(data.message, data.success);
    if (data.success) {
      document.getElementById('qrTokenInput').value = '';
      addRecentEntry(data.data?.guest);
      refreshStats();
    }
  })
  .catch(() => showResult('Errore di rete.', false));
}

function showResult(msg, ok) {
  const el = document.getElementById('checkinResult');
  el.style.display = 'block';
  el.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
  el.textContent = msg;
  if (ok) setTimeout(() => el.style.display = 'none', 4000);
}

function addRecentEntry(guest) {
  if (!guest) return;
  const tbody = document.getElementById('recentBody');
  const row   = document.createElement('tr');
  const now   = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
  row.innerHTML = `<td>${guest.name}${guest.vip ? ' <span class="badge badge-accent">VIP</span>' : ''}</td>
                   <td>${guest.pax || 1}</td><td>${now}</td>`;
  tbody.prepend(row);
}

function refreshStats() {
  fetch('/api/events.php?id=' + EVENT_ID)
    .then(r => r.json())
    .then(d => {
      const s = d.data?.stats;
      if (!s) return;
      document.getElementById('statChecked').textContent = s.checked_in;
      document.getElementById('statTotal').textContent   = s.total_guests;
      document.getElementById('statVip').textContent     = s.vip_total;
      document.getElementById('statPending').textContent = s.total_guests - s.checked_in;
    });
}

function refreshCheckins() {
  location.reload();
}

// Ricerca ospite per nome
let searchTimer;
function searchGuest(q) {
  clearTimeout(searchTimer);
  if (!q.trim()) { document.getElementById('searchResults').innerHTML = ''; return; }
  searchTimer = setTimeout(() => {
    fetch(`/api/guests.php?event_id=${EVENT_ID}&search=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(d => {
        const guests = d.data || [];
        const html   = guests.map(g => `
          <div style="display:flex;align-items:center;justify-content:space-between;
                      padding:.5rem 0;border-bottom:1px solid var(--border);">
            <div>
              <strong>${g.name}</strong>
              ${g.vip ? '<span class="badge badge-accent">VIP</span>' : ''}
              ${g.checked_in ? '<span class="badge badge-success">✅</span>' : ''}
              <br><small style="color:var(--text-muted)">${g.phone || ''} – PAX: ${g.pax||1}</small>
            </div>
            <button class="btn btn-success btn-sm" onclick="doCheckin('${g.qr_token}')" ${g.checked_in ? 'disabled' : ''}>
              Check-in
            </button>
          </div>`).join('');
        document.getElementById('searchResults').innerHTML = html || '<p style="color:var(--text-muted);margin-top:.5rem;">Nessun risultato.</p>';
      });
  }, 300);
}

// Enter nel campo token
document.getElementById('qrTokenInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') doCheckin();
});

// Auto-refresh stats ogni 30s
setInterval(refreshStats, 30000);

const CSRF_TOKEN_NAME = '<?= CSRF_TOKEN_NAME ?>';
</script>

<?php else: ?>
<div class="alert alert-info">Seleziona un evento per aprire il pannello check-in.</div>
<?php endif; ?>

<?php include TMPL_PATH . '/admin_layout_end.php'; ?>
