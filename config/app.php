<?php
/**
 * App Configuration
 * BASE_PATH is auto-detected from the folder path relative to document root.
 * Works with any folder name (registrony, registrony-laboratorio, etc.)
 */

// Auto-detect: find the project root relative to the document root
$_appRoot = str_replace('\\', '/', dirname(__DIR__));
$_docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));

if ($_docRoot && strpos($_appRoot, $_docRoot) === 0) {
    define('BASE_PATH', rtrim(substr($_appRoot, strlen($_docRoot)), '/'));
} else {
    $parts = explode('/', ltrim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/'));
    define('BASE_PATH', '/' . ($parts[0] ?? 'registrony'));
}
unset($_appRoot, $_docRoot);

/**
 * Returns the current UI locale code.
 * Priority: $_SESSION['lang'] > 'it' (default)
 *
 * @return string  e.g. 'it' | 'en'
 */
function currentLang(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $allowed = ['it', 'en'];
    $lang    = $_SESSION['lang'] ?? 'it';
    return in_array($lang, $allowed, true) ? $lang : 'it';
}

/**
 * Loads UI labels for the given language.
 * Falls back to Italian if the file does not exist.
 * Usage: $L = lang();  then $L['key']
 *
 * @param string|null $lang  Language code; null = read from session
 * @return array
 */
function lang(?string $lang = null): array {
    static $cache = [];
    if ($lang === null) {
        $lang = currentLang();
    }
    if (!isset($cache[$lang])) {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        if (!file_exists($file)) {
            $file = __DIR__ . '/../lang/it.php'; // fallback
        }
        $cache[$lang] = file_exists($file) ? (require $file) : [];
    }
    return $cache[$lang];
}

/**
 * Shortcut to get a single label.
 * Usage: L('key')  or  L('key', 'Fallback')
 *
 * @param string $key
 * @param string $fallback  Value if the key does not exist
 * @return string
 */
function L(string $key, string $fallback = ''): string {
    $labels = lang();
    return $labels[$key] ?? ($fallback ?: $key);
}
