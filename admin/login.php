<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';

session_start_secure();
if (Auth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($email, $password)) {
        header('Location: /admin/index.php');
        exit;
    }
    $error = 'Email o password non corretti.';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login – <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body class="login-page">
<div class="login-box">
  <div class="login-logo">
    <span class="logo-icon">🎵</span>
    <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    <p>Gestione eventi & guest list</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="/admin/login.php" class="login-form">
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             placeholder="admin@guestlist.it" autocomplete="email">
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required
             placeholder="••••••••" autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary btn-full">Accedi</button>
  </form>
</div>
</body>
</html>
