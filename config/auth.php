<?php
/**
 * Sistema di autenticazione - MySQLi procedurale
 * PASSWORD HASHING: le password vengono salvate con password_hash()
 * e verificate con password_verify() — MAI in chiaro.
 */

session_start();

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

/**
 * Login utente.
 * Usa password_verify() per confrontare la password inserita
 * con l'hash bcrypt salvato nel database.
 */
function login(string $email, string $password): bool {
    $conn = getConnection();

    // Prepariamo la query con prepared statement (sicuro contro SQL injection)
    $stmt = mysqli_prepare($conn, "SELECT id, nome, cognome, email, password, ruolo, attivo FROM utenti WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || !$user['attivo']) {
        return false;
    }

    // password_verify() confronta la password in chiaro con l'hash nel DB
    // Se la password NON e' ancora hashata (migrazione), la hassiamo al volo
    if (password_needs_rehash_or_plain($user['password'])) {
        // Password ancora in chiaro: verifica diretta e poi la hashiamo
        if ($password !== $user['password']) {
            return false;
        }
        // Migrazione automatica: salviamo subito l'hash al posto della password in chiaro
        migratePasswordHash($user['id'], $password);
    } elseif (!password_verify($password, $user['password'])) {
        // Password hashata: verifica normale
        return false;
    }

    // Controlla se l'hash e' obsoleto e va aggiornato (es. cambio cost factor)
    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
        migratePasswordHash($user['id'], $password);
    }

    $_SESSION['user_id']            = $user['id'];
    $_SESSION['user_nome']          = $user['nome'];
    $_SESSION['user_cognome']       = $user['cognome'];
    $_SESSION['user_email']         = $user['email'];
    $_SESSION['user_ruolo']         = $user['ruolo'];
    $_SESSION['user_nome_completo'] = $user['cognome'] . ' ' . $user['nome'];
    return true;
}

/**
 * Controlla se una stringa e' una password in chiaro (non un hash bcrypt).
 * Gli hash bcrypt iniziano sempre con $2y$
 */
function password_needs_rehash_or_plain(string $hash): bool {
    return !str_starts_with($hash, '$2y$') && !str_starts_with($hash, '$2b$');
}

/**
 * Aggiorna la password nel DB salvando l'hash bcrypt.
 * Viene chiamata automaticamente al primo login dopo la migrazione.
 */
function migratePasswordHash(int $userId, string $plainPassword): void {
    $conn = getConnection();
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = mysqli_prepare($conn, "UPDATE utenti SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Crea un nuovo utente con password hashata.
 * Da usare in setup.php o nella gestione utenti admin.
 */
function hashPassword(string $plainPassword): string {
    return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Cambia la password di un utente (verifica quella vecchia prima).
 */
function changePassword(int $userId, string $oldPassword, string $newPassword): bool {
    $conn = getConnection();
    $stmt = mysqli_prepare($conn, "SELECT password FROM utenti WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($oldPassword, $user['password'])) {
        return false;
    }

    migratePasswordHash($userId, $newPassword);
    return true;
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
        'id'            => $_SESSION['user_id']            ?? null,
        'nome'          => $_SESSION['user_nome']           ?? '',
        'cognome'       => $_SESSION['user_cognome']        ?? '',
        'email'         => $_SESSION['user_email']          ?? '',
        'ruolo'         => $_SESSION['user_ruolo']          ?? '',
        'nome_completo' => $_SESSION['user_nome_completo']  ?? '',
    ];
}
