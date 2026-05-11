<?php
session_start();

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

/**
 * Verifica le credenziali e avvia la sessione.
 * Ordine corretto: prima verifica password, poi controlla attivo.
 * Supporta sia password in chiaro (legacy) sia bcrypt.
 * Se la password è legacy, la migra automaticamente a bcrypt.
 * Sicuro anche se la colonna must_change_password non esiste ancora nel DB.
 */
function login(string $email, string $password): bool {
    $conn      = getConnection();
    $emailSafe = mysqli_real_escape_string($conn, $email);

    // Controlla se la colonna must_change_password esiste
    $hasMCP = _columnExists($conn, 'utenti', 'must_change_password');

    $fields = $hasMCP
        ? 'id, nome, cognome, email, password, ruolo, attivo, must_change_password'
        : 'id, nome, cognome, email, password, ruolo, attivo';

    $result = mysqli_query($conn, "
        SELECT $fields
        FROM utenti
        WHERE email = '$emailSafe'
        LIMIT 1
    ");

    if (!$result) return false;
    $user = mysqli_fetch_assoc($result);

    // Utente non trovato
    if (!$user) return false;

    // Verifica password (prima del check attivo, così l'errore è sempre generico)
    $valid = false;

    if (password_verify($password, $user['password'])) {
        // Password bcrypt corretta
        $valid = true;
    } elseif ($password === $user['password']) {
        // Password legacy in chiaro: valida, migriamo subito a bcrypt
        $valid = true;
        $hash  = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));
        mysqli_query($conn, "UPDATE utenti SET password = '$hash' WHERE id = {$user['id']}");
    }

    // Password sbagliata O account disattivato → stesso messaggio generico al client
    if (!$valid || !$user['attivo']) return false;

    $_SESSION['user_id']              = $user['id'];
    $_SESSION['user_nome']            = $user['nome'];
    $_SESSION['user_cognome']         = $user['cognome'];
    $_SESSION['user_email']           = $user['email'];
    $_SESSION['user_ruolo']           = $user['ruolo'];
    $_SESSION['user_nome_completo']   = $user['cognome'] . ' ' . $user['nome'];
    $_SESSION['must_change_password'] = $hasMCP ? (bool)($user['must_change_password'] ?? false) : false;
    unset($_SESSION['selected_lab_id']);
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

function isTecnico(): bool {
    return isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'tecnico';
}

function isDocente(): bool {
    return isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'docente';
}

function mustChangePassword(): bool {
    return !empty($_SESSION['must_change_password']);
}

/**
 * Helper: verifica se una colonna esiste in una tabella.
 */
function _columnExists(mysqli $conn, string $table, string $column): bool {
    $t   = mysqli_real_escape_string($conn, $table);
    $c   = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $res && mysqli_num_rows($res) > 0;
}

function getTechnicianLabs(int $userId): array {
    $conn   = getConnection();
    $userId = intval($userId);
    $res    = mysqli_query($conn, "
        SELECT id, nome, aula
        FROM laboratori
        WHERE id_assistente_tecnico = $userId AND attivo = 1
        ORDER BY nome
    ");
    $labs = [];
    while ($row = mysqli_fetch_assoc($res)) $labs[] = $row;
    return $labs;
}

function getDocenteLabs(int $userId): array {
    $conn   = getConnection();
    $userId = intval($userId);
    $res    = mysqli_query($conn, "
        SELECT l.id, l.nome, l.aula,
               (l.id_responsabile = $userId) AS is_responsabile
        FROM docenti_laboratori dl
        JOIN laboratori l ON dl.id_laboratorio = l.id
        WHERE dl.id_docente = $userId AND l.attivo = 1
        ORDER BY l.nome
    ");
    $labs = [];
    while ($row = mysqli_fetch_assoc($res)) $labs[] = $row;
    return $labs;
}

function getSelectedLabId(): ?int {
    return isset($_SESSION['selected_lab_id']) ? (int)$_SESSION['selected_lab_id'] : null;
}

function setSelectedLabId(int $idLab): void {
    $_SESSION['selected_lab_id'] = $idLab;
}

function canAccessLab(int $idLab): bool {
    if (isAdmin()) return true;
    $conn   = getConnection();
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId || !$idLab) return false;
    if (isTecnico()) {
        $res = mysqli_query($conn, "
            SELECT 1 FROM laboratori
            WHERE id = $idLab AND id_assistente_tecnico = $userId AND attivo = 1
            LIMIT 1
        ");
        return mysqli_num_rows($res) > 0;
    }
    if (isDocente()) {
        $res = mysqli_query($conn, "
            SELECT 1 FROM docenti_laboratori
            WHERE id_docente = $userId AND id_laboratorio = $idLab
            LIMIT 1
        ");
        return mysqli_num_rows($res) > 0;
    }
    return false;
}

function isResponsabileLab(int $idLab): bool {
    if (isAdmin()) return true;
    $conn   = getConnection();
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId || !$idLab) return false;
    $res = mysqli_query($conn, "
        SELECT 1 FROM laboratori
        WHERE id = $idLab AND id_responsabile = $userId
        LIMIT 1
    ");
    return mysqli_num_rows($res) > 0;
}

function canGestireSegnalazioni(int $idLab): bool {
    if (isAdmin()) return true;
    $conn   = getConnection();
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId || !$idLab) return false;
    $res = mysqli_query($conn, "
        SELECT 1 FROM laboratori
        WHERE id = $idLab
          AND (id_responsabile = $userId OR id_assistente_tecnico = $userId)
        LIMIT 1
    ");
    return mysqli_num_rows($res) > 0;
}

function canManageAnyLab(): bool {
    if (isAdmin()) return true;
    $conn   = getConnection();
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId) return false;
    if (isTecnico()) {
        $res = mysqli_query($conn, "
            SELECT 1 FROM laboratori
            WHERE id_assistente_tecnico = $userId AND attivo = 1
            LIMIT 1
        ");
        return mysqli_num_rows($res) > 0;
    }
    if (isDocente()) {
        $res = mysqli_query($conn, "
            SELECT 1 FROM docenti_laboratori dl
            JOIN laboratori l ON dl.id_laboratorio = l.id
            WHERE dl.id_docente = $userId AND l.attivo = 1
            LIMIT 1
        ");
        return mysqli_num_rows($res) > 0;
    }
    return false;
}

/**
 * Richiede che l'utente sia loggato.
 * Se deve cambiare password (primo accesso), lo reindirizza alla pagina dedicata.
 * Anti-loop: usa realpath(SCRIPT_FILENAME) per confronto sul path fisico reale.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
    if (mustChangePassword()) {
        $realSelf   = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
        $realTarget = realpath(__DIR__ . '/../pages/cambia_password.php');
        if ($realSelf !== $realTarget) {
            header('Location: ' . BASE_PATH . '/pages/cambia_password.php');
            exit;
        }
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

function requireTecnicoOrAdmin(): void {
    requireLogin();
    if (!isAdmin() && !isTecnico()) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

function requireLabSelected(): void {
    requireLogin();
    if (isDocente() && !getSelectedLabId()) {
        header('Location: ' . BASE_PATH . '/pages/seleziona_laboratorio.php');
        exit;
    }
}

function requireLabAccess(int $idLab): void {
    requireLogin();
    if (!canAccessLab($idLab)) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

function requireResponsabileLab(int $idLab): void {
    requireLogin();
    if (!isResponsabileLab($idLab)) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function getCurrentUser(): array {
    return [
        'id'            => $_SESSION['user_id']           ?? null,
        'nome'          => $_SESSION['user_nome']          ?? '',
        'cognome'       => $_SESSION['user_cognome']       ?? '',
        'email'         => $_SESSION['user_email']         ?? '',
        'ruolo'         => $_SESSION['user_ruolo']         ?? '',
        'nome_completo' => $_SESSION['user_nome_completo'] ?? '',
    ];
}
