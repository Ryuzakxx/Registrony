<?php
/**
 * Sistema di autenticazione - MySQLi procedurale
 *
 * RUOLI:
 *   admin   → accesso completo a tutto
 *   tecnico → gestisce i propri laboratori (id_assistente_tecnico nel lab)
 *   docente → vede i lab assegnati (docenti_laboratori);
 *             al login seleziona il lab su cui aprire il registro
 */

session_start();

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

// ----------------------------------------------------------------
// Login / Logout
// ----------------------------------------------------------------

function login(string $email, string $password): bool {
    $conn  = getConnection();
    $email = mysqli_real_escape_string($conn, $email);
    $result = mysqli_query($conn, "SELECT id, nome, cognome, email, password, ruolo, attivo FROM utenti WHERE email = '$email'");
    $user   = mysqli_fetch_assoc($result);

    if ($user && $user['attivo'] && $password === $user['password']) {
        $_SESSION['user_id']            = $user['id'];
        $_SESSION['user_nome']          = $user['nome'];
        $_SESSION['user_cognome']       = $user['cognome'];
        $_SESSION['user_email']         = $user['email'];
        $_SESSION['user_ruolo']         = $user['ruolo'];
        $_SESSION['user_nome_completo'] = $user['cognome'] . ' ' . $user['nome'];
        unset($_SESSION['selected_lab_id']); // reset selezione lab ad ogni login
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

// ----------------------------------------------------------------
// Controlli di ruolo
// ----------------------------------------------------------------

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

// ----------------------------------------------------------------
// Laboratori per ruolo
// ----------------------------------------------------------------

/**
 * Laboratori in cui l'utente è assistente tecnico.
 * Usato solo per il ruolo 'tecnico'.
 */
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

/**
 * Laboratori assegnati a un docente (tabella docenti_laboratori).
 * Restituisce anche il flag is_responsabile.
 */
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

// ----------------------------------------------------------------
// Laboratorio selezionato (sessione docente)
// ----------------------------------------------------------------

/** Restituisce l'id del laboratorio selezionato dal docente, o null. */
function getSelectedLabId(): ?int {
    return isset($_SESSION['selected_lab_id']) ? (int)$_SESSION['selected_lab_id'] : null;
}

/** Imposta il laboratorio attivo per la sessione del docente. */
function setSelectedLabId(int $idLab): void {
    $_SESSION['selected_lab_id'] = $idLab;
}

// ----------------------------------------------------------------
// Verifica accesso a un laboratorio
// ----------------------------------------------------------------

/**
 * Restituisce true se l'utente corrente può accedere al laboratorio.
 *   admin   → sempre
 *   tecnico → solo i lab di cui è assistente (id_assistente_tecnico)
 *   docente → solo i lab assegnati in docenti_laboratori
 */
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

/**
 * Verifica se l'utente corrente è il responsabile del laboratorio indicato.
 * Admin e tecnico del lab bypassano il controllo.
 */
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

/**
 * True se l'utente può gestire ALMENO un laboratorio.
 * Usato per mostrare/nascondere voci di menu.
 */
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

// ----------------------------------------------------------------
// Guard functions (require*)
// ----------------------------------------------------------------

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}

/** Solo admin. */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

/** Admin o tecnico. */
function requireTecnicoOrAdmin(): void {
    requireLogin();
    if (!isAdmin() && !isTecnico()) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

/**
 * Per il docente: forza la selezione di un laboratorio.
 * Tecnico e admin non passano per questa pagina.
 */
function requireLabSelected(): void {
    requireLogin();
    if (isDocente() && !getSelectedLabId()) {
        header('Location: ' . BASE_PATH . '/pages/seleziona_laboratorio.php');
        exit;
    }
}

/**
 * Richiede che l'utente abbia accesso al laboratorio $idLab.
 * Usa canAccessLab() internamente.
 */
function requireLabAccess(int $idLab): void {
    requireLogin();
    if (!canAccessLab($idLab)) {
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

// ----------------------------------------------------------------
// Helpers utente corrente
// ----------------------------------------------------------------

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
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
