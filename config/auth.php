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

/**
 * Verifica se l'utente corrente è il responsabile del laboratorio indicato.
 * Gli admin bypassano sempre il controllo.
 */
function isResponsabileLab(int $idLab): bool {
    if (isAdmin()) return true;
    $conn   = getConnection();
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId || !$idLab) return false;
    $res = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id = $idLab AND id_responsabile = $userId LIMIT 1");
    return mysqli_num_rows($res) > 0;
}

/**
 * Verifica se l'utente può gestire ALMENO un laboratorio.
 * Usato per mostrare/nascondere voci di menu.
 */
function canManageAnyLab(): bool {
    if (isAdmin()) return true;
    $conn   = getConnection();
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId) return false;
    $res = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id_responsabile = $userId AND attivo = 1 LIMIT 1");
    return mysqli_num_rows($res) > 0;
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

/**
 * Richiede che l'utente sia admin OPPURE responsabile del laboratorio $idLab.
 */
function requireResponsabileLab(int $idLab): void {
    requireLogin();
    if (!isResponsabileLab($idLab)) {
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