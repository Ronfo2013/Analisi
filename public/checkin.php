<?php
// ============================================================
// Pagina pubblica – Landing QR check-in ospite
// URL: /public/checkin.php?token=TOKEN
// Questa pagina è il target del QR code dell'ospite.
// Lo staff può scansionarla oppure l'ospite mostra il QR.
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/GuestManager.php';
require_once LIB_PATH . '/EventManager.php';

$token    = trim($_GET['token'] ?? '');
$guestMgr = new GuestManager();
$eventMgr = new EventManager();

$guest = $token ? $guestMgr->findByToken($token) : null;
$event = $guest  ? $eventMgr->find($guest['event_id'] ?? '') : null;

$checkedIn = !empty($guest['checked_in']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Check-In – <?= APP_NAME ?></title>
<style>
:root {
  --primary: #6c3fc5;
  --bg: #0f0f1a;
  --bg-card: #1a1a2e;
  --border: #2d2d4a;
  --text: #e2e8f0;
  --text-muted: #94a3b8;
  --success: #10b981;
  --danger: #ef4444;
  --accent: #f59e0b;
  --radius: 12px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Segoe UI', system-ui, sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 100%;
  max-width: 360px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.5);
  text-align: center;
}
.card-top {
  padding: 2rem 1.5rem 1.5rem;
}
.status-icon { font-size: 4rem; margin-bottom: .8rem; display: block; }
.guest-name {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: .3rem;
}
.event-name { color: var(--text-muted); font-size: .9rem; }
.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .5rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border);
  text-align: left;
}
.meta-item label { font-size: .7rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: .05em; }
.meta-item span  { font-size: .95rem; font-weight: 600; }
.status-bar {
  padding: .9rem 1.5rem;
  font-weight: 600;
  font-size: .95rem;
}
.status-bar.ok      { background: rgba(16,185,129,.2); color: #6ee7b7; }
.status-bar.pending { background: rgba(108,63,197,.2); color: #c4b5fd; }
.status-bar.already { background: rgba(245,158,11,.2); color: #fde68a; }
.status-bar.error   { background: rgba(239,68,68,.2);  color: #fca5a5; }
.qr-small { padding: .5rem; background: #fff; border-radius: 6px; display: inline-block; margin: 1rem; }
.qr-small img { width: 100px; height: 100px; display: block; }
.footer-note { font-size: .75rem; color: var(--text-muted); padding: .8rem 1rem 1rem; }
</style>
</head>
<body>
<div class="card">
  <?php if (!$guest): ?>
  <!-- Token non valido -->
  <div class="card-top">
    <span class="status-icon">❌</span>
    <div class="guest-name">QR non valido</div>
    <div class="event-name">Token non trovato nel sistema.</div>
  </div>
  <div class="status-bar error">Accesso non consentito</div>

  <?php elseif ($checkedIn): ?>
  <!-- Già entrato -->
  <div class="card-top">
    <span class="status-icon">⚠️</span>
    <div class="guest-name"><?= htmlspecialchars($guest['name']) ?></div>
    <div class="event-name"><?= $event ? htmlspecialchars($event['name']) : '' ?></div>
  </div>
  <div class="meta-grid">
    <div class="meta-item">
      <label>Check-in alle</label>
      <span><?= date('H:i', strtotime($guest['checkin_at'])) ?></span>
    </div>
    <div class="meta-item">
      <label>PAX</label>
      <span><?= (int)($guest['pax'] ?? 1) ?></span>
    </div>
    <div class="meta-item">
      <label>VIP</label>
      <span><?= !empty($guest['vip']) ? '⭐ Sì' : 'No' ?></span>
    </div>
    <?php if (!empty($guest['table_number'])): ?>
    <div class="meta-item">
      <label>Tavolo</label>
      <span><?= htmlspecialchars($guest['table_number']) ?></span>
    </div>
    <?php endif; ?>
  </div>
  <div class="status-bar already">⚠️ Già registrato alle <?= date('H:i', strtotime($guest['checkin_at'])) ?></div>

  <?php else: ?>
  <!-- In lista, non ancora entrato – mostra QR per staff -->
  <div class="card-top">
    <span class="status-icon">🎫</span>
    <div class="guest-name"><?= htmlspecialchars($guest['name']) ?></div>
    <div class="event-name"><?= $event ? htmlspecialchars($event['name']) : '' ?></div>
    <div class="qr-small">
      <img src="<?= htmlspecialchars($guestMgr->qrUrl($guest)) ?>" alt="QR">
    </div>
  </div>
  <div class="meta-grid">
    <div class="meta-item">
      <label>Data evento</label>
      <span><?= $event ? date(DATE_FORMAT, strtotime($event['date'])) : '—' ?></span>
    </div>
    <div class="meta-item">
      <label>PAX</label>
      <span><?= (int)($guest['pax'] ?? 1) ?></span>
    </div>
    <div class="meta-item">
      <label>VIP</label>
      <span><?= !empty($guest['vip']) ? '⭐ Sì' : 'No' ?></span>
    </div>
    <?php if (!empty($guest['table_number'])): ?>
    <div class="meta-item">
      <label>Tavolo</label>
      <span><?= htmlspecialchars($guest['table_number']) ?></span>
    </div>
    <?php endif; ?>
  </div>
  <div class="status-bar pending">🎟 In lista – Mostra al personale</div>
  <?php endif; ?>

  <div class="footer-note"><?= APP_NAME ?> · <?= date('Y') ?></div>
</div>
</body>
</html>
