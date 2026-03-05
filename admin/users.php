<?php
// ============================================================
// Admin – Gestione utenti (solo Admin)
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';

Auth::requireRole(ROLE_ADMIN);

$page_title = 'Utenti';
$active_nav = 'users';
$msg        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? ROLE_STAFF;

        if ($name && $email && $password) {
            Auth::createUser($name, $email, $password, $role);
            $msg = 'Utente creato con successo!';
        } else {
            $msg = 'Compila tutti i campi obbligatori.';
        }
    }
}

$users = Auth::allUsers();
include TMPL_PATH . '/admin_layout.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    🔑 Utenti sistema
    <button class="btn btn-primary btn-sm" onclick="openModal('modalCreateUser')">+ Nuovo utente</button>
  </div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr><th>Nome</th><th>Email</th><th>Ruolo</th><th>Creato</th><th>Stato</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <span class="badge <?= $u['role'] === ROLE_ADMIN ? 'badge-primary' : 'badge-muted' ?>">
            <?= htmlspecialchars(ucfirst($u['role'])) ?>
          </span>
        </td>
        <td><?= isset($u['created_at']) ? date(DATE_FORMAT, strtotime($u['created_at'])) : '—' ?></td>
        <td>
          <?php if (!empty($u['active'])): ?>
          <span class="badge badge-success">Attivo</span>
          <?php else: ?>
          <span class="badge badge-danger">Disabilitato</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal crea utente -->
<div class="modal-backdrop" id="modalCreateUser">
  <div class="modal">
    <div class="modal-header">
      ➕ Nuovo Utente
      <button class="modal-close" onclick="closeModal('modalCreateUser')">✕</button>
    </div>
    <form method="POST">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="action" value="create_user">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>Nome *</label>
            <input type="text" name="name" required placeholder="Mario Bianchi">
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required placeholder="mario@example.it">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required placeholder="Min 8 caratteri">
          </div>
          <div class="form-group">
            <label>Ruolo</label>
            <select name="role">
              <option value="staff">Staff</option>
              <option value="pr">PR</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <p style="font-size:.78rem;color:var(--text-muted);">
          <strong>Admin</strong>: accesso completo · <strong>Staff</strong>: gestione eventi/ospiti · <strong>PR</strong>: solo aggiunta ospiti propri
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalCreateUser')">Annulla</button>
        <button type="submit" class="btn btn-primary">Crea utente</button>
      </div>
    </form>
  </div>
</div>

<?php include TMPL_PATH . '/admin_layout_end.php'; ?>
