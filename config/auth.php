<?php
/**
 * Sistema di autenticazione
 */

session_start();

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';


/**
 * Verifica login utente
 */
function login(string $email, string $password): bool {
    $pdo = getConnection();
    
    // Query corretta: assicurati che 'pwd' sia il nome esatto nel DB
    $sql = "SELECT id, nome, cognome, email, pwd, ruolo, attivo FROM utenti WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Controllo password in chiaro (visto che abbiamo tolto l'hashing)
    if ($user && $user['attivo'] && $password === $user['pwd']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_cognome'] = $user['cognome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_ruolo'] = $user['ruolo'];
        $_SESSION['user_nome_completo'] = $user['cognome'] . ' ' . $user['nome'];
        return true;
    }
    return false;
}
/**
 * Logout utente
 */
function logout(): void {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

/**
 * Verifica se l'utente e' loggato
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se l'utente e' admin
 */
function isAdmin(): bool {
    return isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'admin';
}

/**
 * Richiede login, reindirizza se non loggato
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}

/**
 * Richiede ruolo admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

/**
 * Ottieni ID utente corrente
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottieni dati utente corrente
 */
function getCurrentUser(): array {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'nome' => $_SESSION['user_nome'] ?? '',
        'cognome' => $_SESSION['user_cognome'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'ruolo' => $_SESSION['user_ruolo'] ?? '',
        'nome_completo' => $_SESSION['user_nome_completo'] ?? '',
    ];
}