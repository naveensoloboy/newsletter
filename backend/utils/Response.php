<?php
// ─── utils/Response.php ──────────────────────────────────────

class Response {
    public static function json($data, int $code = 200): void {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = [], string $message = 'Success', int $code = 200): void {
        self::json(array_merge(['message' => $message], is_array($data) ? $data : ['data' => $data]), $code);
    }

    public static function error(string $message, int $code = 400): void {
        self::json(['error' => $message], $code);
    }

    public static function notFound(string $msg = 'Not found'): void {
        self::error($msg, 404);
    }

    public static function unauthorized(string $msg = 'Authentication required'): void {
        self::error($msg, 401);
    }

    public static function forbidden(string $msg = 'Access denied'): void {
        self::error($msg, 403);
    }
}
