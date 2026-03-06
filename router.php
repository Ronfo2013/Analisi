<?php
// ============================================================
// Router per php -S (sviluppo locale)
// Emula le regole .htaccess di Apache
// ============================================================

$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__;

// 1. Blocca accesso a cartelle sensibili
if (preg_match('#^/(data|config|lib|templates)(/|$)#', $uri)) {
    http_response_code(403);
    echo '<h1>403 Forbidden</h1>';
    return true;
}

// 2. Servi file statici esistenti (CSS, JS, immagini...)
$file = $root . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // PHP built-in server serve il file direttamente
}

// 3. Redirect root → admin
if ($uri === '/') {
    header('Location: /admin/index.php');
    exit;
}

// 4. Prova a servire il file PHP richiesto
if (is_file($file . '.php')) {
    require $file . '.php';
    return true;
}

if (is_file($file)) {
    require $file;
    return true;
}

// Fallback index delle directory
if (is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}

http_response_code(404);
echo '<h1>404 – Pagina non trovata</h1><p>' . htmlspecialchars($uri) . '</p>';
return true;
