<?php
// ============================================================
// Response – Helper per risposte JSON nelle API
// ============================================================

class Response {
    public static function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK'): never {
        self::json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    public static function error(string $message, int $status = 400, mixed $data = null): never {
        self::json(['success' => false, 'message' => $message, 'data' => $data], $status);
    }

    public static function unauthorized(string $message = 'Non autorizzato.'): never {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Accesso negato.'): never {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Risorsa non trovata.'): never {
        self::error($message, 404);
    }

    public static function redirect(string $url): never {
        header('Location: ' . $url);
        exit;
    }
}
