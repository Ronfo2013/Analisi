<?php
// ============================================================
// Pagina pubblica – Registrazione ospite via link PR
// URL: /public/register.php?pr=TOKEN&event=EVENT_ID
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
require_once LIB_PATH . '/PrManager.php';
require_once LIB_PATH . '/EventManager.php';
require_once LIB_PATH . '/GuestManager.php';

$prToken  = trim($_GET['pr']    ?? '');
$eventId  = trim($_GET['event'] ?? '');

$prMgr    = new PrManager();
$eventMgr = new EventManager();
$guestMgr = new GuestManager();

$pr    = $prToken ? $prMgr->findByToken($prToken) : null;
$event = $eventId ? $eventMgr->find($eventId)     : null;

$step  = 'form'; // form | success | error
$guest = null;
$error = '';

if (!$pr || empty($pr['active'])) {
    $step  = 'error';
    $error = 'Link non valido o scaduto.';
} elseif (!$event || empty($event['active'])) {
    $step  = 'error';
    $error = 'Evento non trovato o non più disponibile.';
}

if ($step === 'form' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pax   = max(1, (int)($_POST['pax'] ?? 1));

    if (!$name) {
        $error = 'Il nome è obbligatorio.';
    } else {
        $guest = $guestMgr->create([
            'event_id' => $event['id'],
            'pr_id'    => $pr['id'],
            'name'     => $name,
            'phone'    => $phone,
            'email'    => $email,
            'pax'      => $pax,
        ]);
        $step = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrazione – <?= $event ? htmlspecialchars($event['name']) : APP_NAME ?></title>
<style>
:root {
  --primary: #6c3fc5;
  --primary-light: #8b5cf6;
  --bg: #0f0f1a;
  --bg-card: #1a1a2e;
  --border: #2d2d4a;
  --text: #e2e8f0;
  --text-muted: #94a3b8;
  --success: #10b981;
  --danger: #ef4444;
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
  background-image: radial-gradient(ellipse at 50% 0%, #1e0a3c 0%, transparent 70%);
}
.card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 100%;
  max-width: 460px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.5);
}
.card-hero {
  background: linear-gradient(135deg, var(--primary) 0%, #1e0a3c 100%);
  padding: 2rem 1.5rem;
  text-align: center;
}
.card-hero .event-emoji { font-size: 2.5rem; }
.card-hero h1 { font-size: 1.4rem; margin: .5rem 0 .2rem; }
.card-hero .meta { font-size: .85rem; opacity: .8; }
.card-body { padding: 1.5rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: .82rem; color: var(--text-muted); margin-bottom: .3rem; }
.form-group input, .form-group select {
  width: 100%;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: .6rem .9rem;
  color: var(--text);
  font-size: .9rem;
}
.form-group input:focus, .form-group select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(108,63,197,.2);
}
.btn-submit {
  width: 100%;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: .8rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: .5rem;
  transition: background .2s;
}
.btn-submit:hover { background: #4f2ea3; }
.alert-error {
  background: rgba(239,68,68,.15);
  border: 1px solid rgba(239,68,68,.3);
  color: #fca5a5;
  padding: .7rem .9rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: .88rem;
}
.success-box { text-align: center; padding: 2rem; }
.success-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.success-title { font-size: 1.3rem; font-weight: 700; margin-bottom: .5rem; }
.success-sub { color: var(--text-muted); font-size: .9rem; margin-bottom: 1.5rem; }
.qr-wrap { display: inline-block; padding: .8rem; background: #fff; border-radius: 8px; }
.qr-wrap img { width: 180px; height: 180px; display: block; }
.pr-tag {
  font-size: .78rem;
  color: var(--text-muted);
  text-align: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
}
.error-box { text-align: center; padding: 2.5rem 1.5rem; }
.error-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--danger); }
</style>
</head>
<body>
<div class="card">

  <?php if ($step === 'error'): ?>
  <div class="card-hero">
    <div class="event-emoji">🎵</div>
    <h1><?= APP_NAME ?></h1>
  </div>
  <div class="card-body error-box">
    <div class="error-icon">⚠️</div>
    <p style="font-weight:600;margin-bottom:.5rem;">Link non valido</p>
    <p style="color:var(--text-muted);font-size:.88rem;"><?= htmlspecialchars($error) ?></p>
  </div>

  <?php elseif ($step === 'success' && $guest): ?>
  <div class="card-hero">
    <div class="event-emoji">🎉</div>
    <h1><?= htmlspecialchars($event['name']) ?></h1>
    <div class="meta"><?= date(DATE_FORMAT, strtotime($event['date'])) ?> · <?= htmlspecialchars($event['location'] ?? '') ?></div>
  </div>
  <div class="card-body">
    <div class="success-box">
      <div class="success-icon">✅</div>
      <div class="success-title">Sei in lista!</div>
      <div class="success-sub">
        Ciao <strong><?= htmlspecialchars($guest['name']) ?></strong>!<br>
        Mostra questo QR all'ingresso.
      </div>
      <div class="qr-wrap">
        <img src="<?= htmlspecialchars($guestMgr->qrUrl($guest)) ?>"
             alt="QR Check-In">
      </div>
      <p style="font-size:.78rem;color:var(--text-muted);margin-top:.8rem;">
        Salva questa pagina o fai screenshot del QR.
      </p>
    </div>
    <?php if ($pr): ?>
    <div class="pr-tag">Invitato da <?= htmlspecialchars($pr['name']) ?></div>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div class="card-hero">
    <div class="event-emoji">🎉</div>
    <h1><?= htmlspecialchars($event['name']) ?></h1>
    <div class="meta">
      <?= date(DATE_FORMAT, strtotime($event['date'])) ?>
      <?php if (!empty($event['location'])): ?> · <?= htmlspecialchars($event['location']) ?><?php endif; ?>
      <?php if (!empty($event['dj'])): ?> · DJ: <?= htmlspecialchars($event['dj']) ?><?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.2rem;">
      Compila il modulo per iscriverti alla guest list. Riceverai un QR da mostrare all'ingresso.
    </p>
    <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Nome e Cognome *</label>
        <input type="text" name="name" required placeholder="Mario Rossi"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Telefono</label>
        <input type="tel" name="phone" placeholder="+39 333 1234567"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="mario@example.it"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Numero persone (incluso te)</label>
        <select name="pax">
          <?php for ($i = 1; $i <= 10; $i++): ?>
          <option value="<?= $i ?>" <?= ($i===1?'selected':'') ?>><?= $i ?> person<?= $i===1?'a':'e' ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button type="submit" class="btn-submit">📋 Iscriviti alla guest list</button>
    </form>
    <?php if ($pr): ?>
    <div class="pr-tag">Invitato da <?= htmlspecialchars($pr['name']) ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
