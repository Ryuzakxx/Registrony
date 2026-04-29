<?php
/**
 * Language switcher endpoint.
 * Usage: /lang/set_lang.php?lang=en&redirect=/pages/sessioni/index.php
 */
require_once __DIR__ . '/../config/auth.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed = ['it', 'en'];
$lang    = $_GET['lang'] ?? 'it';
if (!in_array($lang, $allowed, true)) {
    $lang = 'it';
}
$_SESSION['lang'] = $lang;

$redirect = $_GET['redirect'] ?? '/';
// Safety: only allow relative paths
if (!preg_match('#^/#', $redirect)) {
    $redirect = '/';
}
header('Location: ' . $redirect);
exit;
