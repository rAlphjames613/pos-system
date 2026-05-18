<?php

require_once __DIR__ . '/../core/Database.php';

class Auth {
    public static function login(string $username, string $password): bool {
        $user = Database::fetch(
            "SELECT * FROM users WHERE username = ? AND status = 'active'",
            [$username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = $user;

        return true;
    }

    public static function logout(): void {
        session_unset();
        session_destroy();
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function register(array $data): bool {
        $exists = Database::fetch(
            "SELECT id FROM users WHERE username = ?",
            [$data['username']]
        );

        if ($exists) return false;

        Database::insert(
            "INSERT INTO users (first_name, last_name, username, password, role)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['first_name'],
                $data['last_name'],
                $data['username'],
                self::hashPassword($data['password']),
                $data['role'] ?? 'cashier',
            ]
        );

        return true;
    }
}
