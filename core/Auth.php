<?php
require_once __DIR__ . '/Database.php';

class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $email, string $password): bool {
        self::start();
        $user = Database::queryOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        Database::execute('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        return true;
    }

    public static function logout(): void {
        self::start();
        session_destroy();
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user']);
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return self::user()['id'] ?? null;
    }

    public static function role(): string {
        return self::user()['role'] ?? '';
    }

    public static function isAdmin(): bool {
        return self::role() === 'admin';
    }

    // Redirect to login if not authenticated
    public static function require(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    // Redirect with 403 if role not allowed
    public static function requireRole(string ...$roles): void {
        self::require();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            die('<h1>403 Forbidden</h1>');
        }
    }
}
