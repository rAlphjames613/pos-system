<?php

class CSRF {
    private static string $key = '_csrf_token';

    public static function generate(): string {
        if (empty($_SESSION[self::$key])) {
            $_SESSION[self::$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$key];
    }

    public static function verify(string $token): bool {
        return isset($_SESSION[self::$key])
            && hash_equals($_SESSION[self::$key], $token);
    }

    public static function input(): string {
        return '<input type="hidden" name="_csrf_token" value="' . self::generate() . '">';
    }

    public static function check(): void {
        $token = $_POST['_csrf_token'] ?? '';
        if (!self::verify($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'CSRF token mismatch.']));
        }
        // Rotate token after use
        unset($_SESSION[self::$key]);
    }
}
