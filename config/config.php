<?php
// ============================================================
// GuestList Manager Italia - Configurazione principale
// ============================================================

define('APP_NAME',    'GuestList Manager');
define('APP_VERSION', '1.0.0');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost');

// Percorsi
define('ROOT_PATH',   dirname(__DIR__));
define('DATA_PATH',   ROOT_PATH . '/data');
define('LIB_PATH',    ROOT_PATH . '/lib');
define('TMPL_PATH',   ROOT_PATH . '/templates');

// Sessioni
define('SESSION_NAME',    'glm_session');
define('SESSION_LIFETIME', 3600 * 8); // 8 ore
define('CSRF_TOKEN_NAME', 'glm_csrf');

// Sicurezza
define('BCRYPT_COST', 12);

// QR code (usa API esterna Google Charts o libreria locale)
define('QR_BASE_URL', 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=');

// Impostazioni app
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('DEFAULT_TIMEZONE', 'Europe/Rome');
define('DATE_FORMAT',      'd/m/Y');
define('DATETIME_FORMAT',  'd/m/Y H:i');

// Ruoli utente
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
define('ROLE_PR',    'pr');

// Impostazione timezone globale
date_default_timezone_set(DEFAULT_TIMEZONE);

// Avvio sessione sicura
function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
