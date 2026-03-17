<?php
// ─── utils/Auth.php ──────────────────────────────────────────
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {

    public static function generateToken(string $userId, string $role): string {
        $payload = [
            'iss' => 'nms',
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY,
            'id'  => $userId,
            'role'=> $role
        ];
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    public static function getIdentity(): ?array {
        $headers = getallheaders();
        $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!$auth || !str_starts_with($auth, 'Bearer ')) return null;
        $token = substr($auth, 7);
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            return ['id' => $decoded->id, 'role' => $decoded->role];
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function requireAuth(): array {
        $identity = self::getIdentity();
        if (!$identity) Response::unauthorized();
        return $identity;
    }

    public static function requireRole(string ...$roles): array {
        $identity = self::requireAuth();
        if (!in_array($identity['role'], $roles)) Response::forbidden();
        return $identity;
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}
