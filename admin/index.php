<?php
// ============================================================
// Dashboard principale
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/EventManager.php';
require_once LIB_PATH . '/GuestManager.php';
require_once LIB_PATH . '/PrManager.php';

$page_title = 'Dashboard';
$active_nav = 'dashboard';

$eventMgr = new EventManager();
$guestMgr = new GuestManager();
$prMgr    = new PrManager();

$events        = $eventMgr->all();
$upcomingEvts  = $eventMgr->upcoming();
$allGuests     = $guestMgr->all();
$checkedIn     = array_filter($allGuests, fn($g) => !empty($g['checked_in']));
$vips          = array_filter($allGuests, fn($g) => !empty($g['vip']));
$leaderboard   = $prMgr->leaderboard();

include TMPL_PATH . '/admin_layout.php';
?>

<!-- Stats cards -->
<div class="stats-grid">
  <div class="stat-card primary">
    <span class="stat-icon">🎉</span>
    <span class="stat-value"><?= count($events) ?></span>
    <span class="stat-label">Totale eventi</span>
  </div>
  <div class="stat-card info">
    <span class="stat-icon">📅</span>
    <span class="stat-value"><?= count($upcomingEvts) ?></span>
    <span class="stat-label">Prossimi eventi</span>
  </div>
  <div class="stat-card success">
    <span class="stat-icon">👥</span>
    <span class="stat-value"><?= count($allGuests) ?></span>
    <span class="stat-label">Ospiti registrati</span>
  </div>
  <div class="stat-card accent">
    <span class="stat-icon">✅</span>
    <span class="stat-value"><?= count($checkedIn) ?></span>
    <span class="stat-label">Check-in totali</span>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin-bottom:1.2rem;">

  <!-- Prossimi eventi -->
  <div class="card">
    <div class="card-header">
      📅 Prossimi eventi
      <a href="/admin/events.php" class="btn btn-outline btn-sm">Tutti</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($upcomingEvts)): ?>
      <p style="padding:1rem;color:var(--text-muted);">Nessun evento in programma.</p>
      <?php else: ?>
      <table>
        <thead><tr><th>Evento</th><th>Data</th><th>Ospiti</th><th></th></tr></thead>
        <tbody>
        <?php foreach (array_slice($upcomingEvts, 0, 5) as $evt):
          $stats = $eventMgr->stats($evt['id']);
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($evt['name']) ?></strong></td>
          <td><?= date(DATE_FORMAT, strtotime($evt['date'])) ?></td>
          <td><?= $stats['total_guests'] ?></td>
          <td>
            <a href="/admin/guests.php?event_id=<?= urlencode($evt['id']) ?>" class="btn btn-outline btn-sm">Gestisci</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top PR -->
  <div class="card">
    <div class="card-header">
      🌟 Top Promoter
      <a href="/admin/prs.php" class="btn btn-outline btn-sm">Tutti</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($leaderboard)): ?>
      <p style="padding:1rem;color:var(--text-muted);">Nessun promoter registrato.</p>
      <?php else: ?>
      <table>
        <thead><tr><th>#</th><th>Promoter</th><th>Invitati</th><th>Presenti</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($leaderboard, 0, 5) as $i => $row): ?>
        <tr>
          <td><strong>#<?= $i+1 ?></strong></td>
          <td><?= htmlspecialchars($row['pr']['name']) ?></td>
          <td><?= $row['guests_total'] ?></td>
          <td><span class="badge badge-success"><?= $row['guests_checked'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Grafico check-in per evento (ultimi 5 eventi) -->
<?php
$chartLabels = [];
$chartTotal  = [];
$chartChecked= [];
foreach (array_slice(array_reverse($events), 0, 6) as $evt) {
    $s = $eventMgr->stats($evt['id']);
    $chartLabels[]  = $evt['name'];
    $chartTotal[]   = $s['total_guests'];
    $chartChecked[] = $s['checked_in'];
}
?>
<div class="card">
  <div class="card-header">📈 Check-in per evento</div>
  <div class="card-body">
    <canvas id="checkinChart" height="90"></canvas>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('checkinChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [
        {
          label: 'Ospiti registrati',
          data: <?= json_encode($chartTotal) ?>,
          backgroundColor: 'rgba(108,63,197,.4)',
          borderColor: 'rgba(108,63,197,1)',
          borderWidth: 1,
          borderRadius: 4,
        },
        {
          label: 'Check-in effettuati',
          data: <?= json_encode($chartChecked) ?>,
          backgroundColor: 'rgba(16,185,129,.4)',
          borderColor: 'rgba(16,185,129,1)',
          borderWidth: 1,
          borderRadius: 4,
        },
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { labels: { color: '#e2e8f0' } } },
      scales: {
        x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,.05)' } },
        y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,.05)' }, beginAtZero: true },
      }
    }
  });
});
</script>

<?php include TMPL_PATH . '/admin_layout_end.php'; ?>
