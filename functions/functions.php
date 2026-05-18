<?php

/**
 * Load .env file
 */
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load .env on require
loadEnv(dirname(__DIR__) . '/.env');

/**
 * Get env variable with default
 */
function env(string $key, mixed $default = null): mixed {
    $val = $_ENV[$key] ?? getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

/**
 * Sanitize output (prevent XSS)
 */
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect
 */
function redirect(string $url): never {
    header("Location: $url");
    exit;
}

/**
 * Flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Role check
 */
function isAdmin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Require login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

/**
 * Require admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect('/dashboard.php');
    }
}

/**
 * Format currency (Philippine Peso)
 */
function peso(float $amount): string {
    return '₱ ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($date));
}

/**
 * Get Font Awesome icon class based on product name and category
 */
function getProductIcon(string $name, string $category): string {
    $n = strtolower($name);
    $c = strtolower($category);

    // Match by product name keywords first
    if (str_contains($n, 'coca-cola') || str_contains($n, 'royal') || str_contains($n, 'pepsi') || str_contains($n, 'sprite')) return 'fa-bottle-droplet';
    if (str_contains($n, 'mineral water') || str_contains($n, 'water')) return 'fa-droplet';
    if (str_contains($n, 'coffee') || str_contains($n, 'nescafe') || str_contains($n, '3-in-1')) return 'fa-mug-hot';
    if (str_contains($n, 'crackers') || str_contains($n, 'skyflakes') || str_contains($n, 'piattos') || str_contains($n, 'nova') || str_contains($n, 'chip')) return 'fa-cookie-bite';
    if (str_contains($n, 'pancit') || str_contains($n, 'noodle') || str_contains($n, 'canton') || str_contains($n, 'lucky me') || str_contains($n, 'nissin')) return 'fa-bowl-food';
    if (str_contains($n, 'ketchup') || str_contains($n, 'soy sauce') || str_contains($n, 'vinegar') || str_contains($n, 'magic sarap') || str_contains($n, 'sauce')) return 'fa-bottle-water';
    if (str_contains($n, 'detergent') || str_contains($n, 'champion') || str_contains($n, 'ariel') || str_contains($n, 'surf')) return 'fa-soap';
    if (str_contains($n, 'soap') || str_contains($n, 'safeguard') || str_contains($n, 'dove')) return 'fa-soap';
    if (str_contains($n, 'shampoo') || str_contains($n, 'head') || str_contains($n, 'conditioner') || str_contains($n, 'shoulders')) return 'fa-pump-soap';
    if (str_contains($n, 'cigarette') || str_contains($n, 'marlboro') || str_contains($n, 'mighty') || str_contains($n, 'tobacco')) return 'fa-smoking';
    if (str_contains($n, 'milk') || str_contains($n, 'bear brand') || str_contains($n, 'alaska') || str_contains($n, 'evap') || str_contains($n, 'condensed')) return 'fa-cow';
    if (str_contains($n, 'tuna') || str_contains($n, 'sardines') || str_contains($n, 'century') || str_contains($n, 'canned')) return 'fa-fish';
    if (str_contains($n, 'rice')) return 'fa-bowl-rice';
    if (str_contains($n, 'egg') || str_contains($n, 'itlog')) return 'fa-egg';
    if (str_contains($n, 'bread') || str_contains($n, 'pandesal') || str_contains($n, 'biscuit')) return 'fa-bread-slice';
    if (str_contains($n, 'candy') || str_contains($n, 'chocolate') || str_contains($n, 'gummy')) return 'fa-candy-cane';
    if (str_contains($n, 'ice cream') || str_contains($n, 'ice drop')) return 'fa-ice-cream';
    if (str_contains($n, 'matches') || str_contains($n, 'lighter')) return 'fa-fire';
    if (str_contains($n, 'candle')) return 'fa-candle-holder';
    if (str_contains($n, 'battery') || str_contains($n, 'baterya')) return 'fa-battery-half';
    if (str_contains($n, 'juice') || str_contains($n, 'tang') || str_contains($n, 'eight o\'clock')) return 'fa-glass-water';

    // Fallback by category
    return match(true) {
        str_contains($c, 'beverage') || str_contains($c, 'drink')   => 'fa-bottle-droplet',
        str_contains($c, 'snack')                                    => 'fa-cookie-bite',
        str_contains($c, 'instant food') || str_contains($c, 'food') => 'fa-bowl-food',
        str_contains($c, 'condiment')                                => 'fa-bottle-water',
        str_contains($c, 'household')                                => 'fa-soap',
        str_contains($c, 'personal care')                            => 'fa-pump-soap',
        str_contains($c, 'tobacco')                                  => 'fa-smoking',
        str_contains($c, 'dairy')                                    => 'fa-cow',
        str_contains($c, 'canned')                                   => 'fa-fish',
        default                                                      => 'fa-box',
    };
}


function baseUrl(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'casestudy';
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}
