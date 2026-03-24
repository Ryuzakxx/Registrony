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
    // Fallback: estrai dal percorso dello script
    $parts = explode('/', ltrim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/'));
    define('BASE_PATH', '/' . ($parts[0] ?? 'registrony'));
}
unset($_appRoot, $_docRoot);
