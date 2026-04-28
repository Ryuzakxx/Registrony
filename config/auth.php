<?php
/**
 * Sistema di autenticazione - MySQLi procedurale
 */

session_start();

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

function login(string $email, string $password): bool {
    $conn = getConnection();

    $email = mysqli_real_escape_string($conn, $email);
    $result = mysqli_query($conn, "SELECT id, nome, cognome, email, password, ruolo, attivo FROM utenti WHERE email = '$email'");
    $user = mysqli_fetch_assoc($result);

    if ($user && $user['attivo'] && $password === $user['password']) {
        $_SESSION['user_id']           = $user['id'];
        $_SESSION['user_nome']         = $user['nome'];
        $_SESSION['user_cognome']      = $user['cognome'];
        $_SESSION['user_email']        = $user['email'];
        $_SESSION['user_ruolo']        = $user['ruolo'];
        $_SESSION['user_nome_completo']= $user['cognome'] . ' ' . $user['nome'];
        return true;
    }
    return false;
}

function logout(): void {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser(): array {
    return [
        'id'           => $_SESSION['user_id']           ?? null,
        'nome'         => $_SESSION['user_nome']          ?? '',
        'cognome'      => $_SESSION['user_cognome']       ?? '',
        'email'        => $_SESSION['user_email']         ?? '',
        'ruolo'        => $_SESSION['user_ruolo']         ?? '',
        'nome_completo'=> $_SESSION['user_nome_completo'] ?? '',
    ];
}