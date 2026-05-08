<?php
session_start();

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

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
        unset($_SESSION['selected_lab_id']);
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

function isTecnico(): bool {
    return isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'tecnico';
}

function isDocente(): bool {
    return isset($_SESSION['user_ruolo']) && $_SESSION['user_ruolo'] === 'docente';
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

function hasUserColumn(mysqli $conn, string $column): bool {
    $col = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM utenti LIKE '$col'");
    return $res && mysqli_num_rows($res) > 0;
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
