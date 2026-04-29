<?php
/**
 * Configurazione App
 * BASE_PATH viene rilevato automaticamente dal percorso della cartella.
 * Funziona con qualsiasi nome di cartella (registrony, registrony-laboratorio, ecc.)
 */

// Auto-detect: trova la cartella root del progetto rispetto alla document root
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
 * Carica le etichette UI dalla lingua specificata.
 * Uso: $L = lang();  poi $L['chiave']
 *
 * @param string $lang  Codice lingua (default: 'it')
 * @return array
 */
function lang(string $lang = 'it'): array {
    static $cache = [];
    if (!isset($cache[$lang])) {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        $cache[$lang] = file_exists($file) ? (require $file) : [];
    }
    return $cache[$lang];
}

/**
 * Shortcut per ottenere una singola label.
 * Uso: L('chiave')  oppure  L('chiave', 'Fallback')
 *
 * @param string $key
 * @param string $fallback  Valore se la chiave non esiste
 * @return string
 */
function L(string $key, string $fallback = ''): string {
    $labels = lang();
    return $labels[$key] ?? ($fallback ?: $key);
}
