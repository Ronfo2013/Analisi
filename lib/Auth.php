<?php
// ============================================================
// Auth – Autenticazione, sessioni, CSRF
// ============================================================

require_once LIB_PATH . '/JsonStore.php';

class Auth {
    private static JsonStore $store;

    private static function store(): JsonStore {
        if (!isset(self::$store)) {
            self::$store = new JsonStore(ROOT_PATH . '/config/users.json');
        }
        return self::$store;
    }

    // ----------------------------------------------------------
    // Login / Logout
    // ----------------------------------------------------------
    public static function login(string $email, string $password): bool {
        session_start_secure();
        $user = self::findByEmail($email);
        if (!$user || empty($user['active'])) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        // Rigenera session ID per prevenire session fixation
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['csrf']      = self::generateCsrf();
        return true;
    }

    public static function logout(): void {
        session_start_secure();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ----------------------------------------------------------
    // Controllo accesso
    // ----------------------------------------------------------
    public static function check(): bool {
        session_start_secure();
        return !empty($_SESSION['logged_in']);
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void {
        self::requireLogin();
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            http_response_code(403);
            exit('Accesso negato.');
        }
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        return [
            'id'   => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public static function role(): string {
        return $_SESSION['user_role'] ?? '';
    }

    // ----------------------------------------------------------
    // CSRF
    // ----------------------------------------------------------
    public static function csrfToken(): string {
        session_start_secure();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = self::generateCsrf();
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(string $token): bool {
        session_start_secure();
        return hash_equals($_SESSION['csrf'] ?? '', $token);
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }

    private static function generateCsrf(): string {
        return bin2hex(random_bytes(32));
    }

    // ----------------------------------------------------------
    // Gestione utenti
    // ----------------------------------------------------------
    private static function findByEmail(string $email): ?array {
        foreach (self::store()->all() as $u) {
            if (strtolower($u['email']) === strtolower($email)) {
                return $u;
            }
        }
        return null;
    }

    public static function createUser(string $name, string $email, string $password, string $role = ROLE_STAFF): array {
        return self::store()->insert([
            'name'          => $name,
            'email'         => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
            'role'          => $role,
            'active'        => true,
        ]);
    }

    public static function allUsers(): array {
        return array_map(function ($u) {
            unset($u['password_hash']);
            return $u;
        }, self::store()->all());
    }
}
