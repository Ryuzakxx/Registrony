<?php
$_appRoot = str_replace('\\', '/', dirname(__DIR__));
$_docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));

if ($_docRoot && strpos($_appRoot, $_docRoot) === 0) {
    define('BASE_PATH', rtrim(substr($_appRoot, strlen($_docRoot)), '/'));
} else {
    $parts = explode('/', ltrim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/'));
    define('BASE_PATH', '/' . ($parts[0] ?? 'registrony'));
}
unset($_appRoot, $_docRoot);

function currentLang(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $allowed = ['it', 'en'];
    $lang    = $_SESSION['lang'] ?? 'it';
    return in_array($lang, $allowed, true) ? $lang : 'it';
}

function lang(?string $lang = null): array {
    static $cache = [];
    if ($lang === null) $lang = currentLang();
    if (!isset($cache[$lang])) {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        if (!file_exists($file)) $file = __DIR__ . '/../lang/it.php';
        $cache[$lang] = file_exists($file) ? (require $file) : [];
    }
    return $cache[$lang];
}

function L(string $key, string $fallback = ''): string {
    $labels = lang();
    return $labels[$key] ?? ($fallback ?: $key);
}
