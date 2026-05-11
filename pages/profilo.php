<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$conn = getConnection();
$L    = lang();
$uid  = (int)getCurrentUserId();

function tableExists(mysqli $conn, string $table): bool {
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    return $r && mysqli_num_rows($r) > 0;
}

function reloadUser(mysqli $conn, int $uid): array {
    $r = mysqli_query($conn, "SELECT * FROM utenti WHERE id = $uid LIMIT 1");
    return mysqli_fetch_assoc($r) ?? [];
}

$user = reloadUser($conn, $uid);

/* ── Cartella upload ── */
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── Upload foto profilo ── */
    if ($action === 'upload_avatar') {
        $errors = [];
        if (empty($_FILES['avatar']['tmp_name']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Nessun file selezionato o errore durante il caricamento.';
        } else {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mime     = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);
            $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxBytes = 2 * 1024 * 1024; // 2 MB

            if (!in_array($mime, $allowed)) {
                $errors[] = 'Formato non supportato. Usa JPG, PNG, GIF o WebP.';
            } elseif ($_FILES['avatar']['size'] > $maxBytes) {
                $errors[] = 'Il file supera il limite di 2 MB.';
            } else {
                $ext     = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime];
                $newFile = $uid . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFile;

                // Cancella vecchio avatar se esiste
                if (!empty($user['avatar'])) {
                    $old = __DIR__ . '/../' . $user['avatar'];
                    if (is_file($old)) unlink($old);
                }

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                    $relPath = 'uploads/avatars/' . $newFile;
                    $safe    = mysqli_real_escape_string($conn, $relPath);
                    mysqli_query($conn, "UPDATE utenti SET avatar='$safe' WHERE id=$uid");
                    $_SESSION['user_avatar'] = $relPath;
                    header('Location: ' . BASE_PATH . '/pages/profilo.php?success=' . urlencode('Foto profilo aggiornata.'));
                } else {
                    $errors[] = 'Impossibile salvare il file. Controlla i permessi della cartella uploads/avatars/.';
                }
            }
        }
        if (!empty($errors)) {
            header('Location: ' . BASE_PATH . '/pages/profilo.php?error=' . urlencode(implode(' | ', $errors)) . '&tab=info');
        }
        exit;
    }

    /* ── Rimuovi foto profilo ── */
    if ($action === 'remove_avatar') {
        if (!empty($user['avatar'])) {
            $old = __DIR__ . '/../' . $user['avatar'];
            if (is_file($old)) unlink($old);
        }
        mysqli_query($conn, "UPDATE utenti SET avatar=NULL WHERE id=$uid");
        $_SESSION['user_avatar'] = null;
        header('Location: ' . BASE_PATH . '/pages/profilo.php?success=' . urlencode('Foto profilo rimossa.'));
        exit;
    }

    if ($action === 'update_info') {
        $nome     = mb_convert_case(mb_strtolower(trim($_POST['nome']    ?? ''), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $cognome  = mb_convert_case(mb_strtolower(trim($_POST['cognome'] ?? ''), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $telefono = trim($_POST['telefono'] ?? '');
        $errors   = [];

        if (!$nome)    $errors[] = 'Il nome è obbligatorio.';
        if (!$cognome) $errors[] = 'Il cognome è obbligatorio.';
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,25}$/', $telefono)) $errors[] = 'Formato telefono non valido.';

        if (empty($errors)) {
            $n_e   = mysqli_real_escape_string($conn, $nome);
            $c_e   = mysqli_real_escape_string($conn, $cognome);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';
            mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', telefono=$t_SQL WHERE id=$uid");
            $_SESSION['user_nome']          = $nome;
            $_SESSION['user_cognome']       = $cognome;
            $_SESSION['user_nome_completo'] = $cognome . ' ' . $nome;
            header('Location: ' . BASE_PATH . '/pages/profilo.php?success=' . urlencode('Informazioni aggiornate con successo.'));
        } else {
            header('Location: ' . BASE_PATH . '/pages/profilo.php?error=' . urlencode(implode(' | ', $errors)) . '&tab=info');
        }
        exit;
    }

    if ($action === 'update_email') {
        $newEmail  = strtolower(trim($_POST['email']         ?? ''));
        $confEmail = strtolower(trim($_POST['email_confirm'] ?? ''));
        $pwdCheck  = $_POST['password_check'] ?? '';
        $errors    = [];

        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Inserisci un indirizzo email valido.';
        if ($newEmail !== $confEmail)  $errors[] = 'Le due email non coincidono.';
        if (!$pwdCheck)                $errors[] = 'Inserisci la password attuale per confermare.';

        if (empty($errors) && !password_verify($pwdCheck, $user['password'])) {
            $errors[] = 'Password attuale errata.';
        }
        if (empty($errors)) {
            $e_safe = mysqli_real_escape_string($conn, $newEmail);
            $dup = mysqli_query($conn, "SELECT id FROM utenti WHERE email = '$e_safe' AND id != $uid LIMIT 1");
            if (mysqli_num_rows($dup) > 0) $errors[] = 'Email già in uso da un altro account.';
        }

        if (empty($errors)) {
            $e_safe = mysqli_real_escape_string($conn, $newEmail);
            mysqli_query($conn, "UPDATE utenti SET email='$e_safe' WHERE id=$uid");
            $_SESSION['user_email'] = $newEmail;
            header('Location: ' . BASE_PATH . '/pages/profilo.php?success=' . urlencode('Email aggiornata con successo.'));
        } else {
            header('Location: ' . BASE_PATH . '/pages/profilo.php?error=' . urlencode(implode(' | ', $errors)) . '&tab=email');
        }
        exit;
    }

    if ($action === 'update_password') {
        $oldPwd  = $_POST['old_password']     ?? '';
        $newPwd  = $_POST['new_password']     ?? '';
        $confPwd = $_POST['confirm_password'] ?? '';
        $errors  = [];

        if (!$oldPwd)             $errors[] = 'Inserisci la password attuale.';
        if (strlen($newPwd) < 6)  $errors[] = 'La nuova password deve avere almeno 6 caratteri.';
        if ($newPwd !== $confPwd) $errors[] = 'Le due password non coincidono.';

        if (empty($errors) && !password_verify($oldPwd, $user['password'])) {
            $errors[] = 'La password attuale non è corretta.';
        }

        if (empty($errors)) {
            $hash   = mysqli_real_escape_string($conn, password_hash($newPwd, PASSWORD_BCRYPT));
            $hasMCP = _columnExists($conn, 'utenti', 'must_change_password');
            $mcpSet = $hasMCP ? ', must_change_password = 0' : '';
            mysqli_query($conn, "UPDATE utenti SET password='$hash'$mcpSet WHERE id=$uid");
            header('Location: ' . BASE_PATH . '/pages/profilo.php?success=' . urlencode('Password cambiata con successo.'));
        } else {
            header('Location: ' . BASE_PATH . '/pages/profilo.php?error=' . urlencode(implode(' | ', $errors)) . '&tab=password');
        }
        exit;
    }
}

$user      = reloadUser($conn, $uid);
// Sincronizza avatar in sessione
$_SESSION['user_avatar'] = $user['avatar'] ?? null;
$activeTab = $_GET['tab'] ?? 'info';

$sessioni = [];
$totSess  = 0;
if (tableExists($conn, 'sessioni_laboratorio')) {
    $rSess = mysqli_query($conn, "
        SELECT sl.id,
               sl.data,
               sl.ora_ingresso,
               sl.ora_uscita,
               sl.attivita_svolta AS note,
               l.nome  AS lab_nome,
               l.aula  AS lab_aula,
               c.nome  AS classe_nome
        FROM sessioni_laboratorio sl
        INNER JOIN firme_sessioni fs ON fs.id_sessione = sl.id AND fs.id_docente = $uid
        LEFT  JOIN laboratori l ON l.id = sl.id_laboratorio
        LEFT  JOIN classi     c ON c.id = sl.id_classe
        ORDER BY sl.data DESC, sl.ora_ingresso DESC
        LIMIT 8
    ");
    if ($rSess) while ($row = mysqli_fetch_assoc($rSess)) $sessioni[] = $row;

    $rTot = mysqli_query($conn, "
        SELECT COUNT(*) AS n
        FROM sessioni_laboratorio sl
        INNER JOIN firme_sessioni fs ON fs.id_sessione = sl.id AND fs.id_docente = $uid
    ");
    $totSess = $rTot ? (int)(mysqli_fetch_assoc($rTot)['n'] ?? 0) : 0;
}

$totSegnR = 0;
if (tableExists($conn, 'segnalazioni')) {
    $rSegn = mysqli_query($conn, "SELECT COUNT(*) AS n FROM segnalazioni WHERE id_utente = $uid");
    $totSegnR = $rSegn ? (int)(mysqli_fetch_assoc($rSegn)['n'] ?? 0) : 0;
}

// Helper: URL avatar o null
$avatarUrl = !empty($user['avatar']) ? BASE_PATH . '/' . htmlspecialchars($user['avatar']) : null;
$initials  = strtoupper(mb_substr($user['nome'],0,1,'UTF-8') . mb_substr($user['cognome'],0,1,'UTF-8'));

$pageTitle = 'Il mio profilo';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/form_helpers.php';
?>

<?php formFieldStyles(); ?>

<style>
.profilo-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 900px) {
    .profilo-grid { grid-template-columns: 1fr; }
}
.profilo-card-user {
    background: var(--bg-white);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
}
.profilo-card-header {
    background: linear-gradient(135deg, var(--primary) 0%, #1e3a5f 100%);
    padding: 2rem 1.5rem 1.25rem;
    text-align: center;
    position: relative;
}
/* Avatar grande nella card profilo */
.profilo-avatar-big {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.22);
    color: #fff;
    font-size: 1.75rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto .75rem;
    border: 3px solid rgba(255,255,255,.45);
    letter-spacing: .5px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    flex-shrink: 0;
}
.profilo-avatar-big img {
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}
.profilo-avatar-overlay {
    position: absolute; inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,.45);
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity .18s;
}
.profilo-avatar-big:hover .profilo-avatar-overlay { opacity: 1; }
.profilo-avatar-overlay svg { color: #fff; }
.profilo-card-name  { color: #f1f5f9; font-weight: 700; font-size: 1.05rem; margin-bottom: .2rem; }
.profilo-card-role  {
    display: inline-block;
    background: rgba(255,255,255,.15);
    color: rgba(255,255,255,.9);
    font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .8px;
    padding: 2px 10px; border-radius: 20px; margin-top: .25rem;
}
.profilo-card-email { color: rgba(255,255,255,.7); font-size: .8rem; margin-top: .5rem; word-break: break-all; }
.profilo-stats {
    display: grid; grid-template-columns: 1fr 1fr;
    border-top: 1px solid var(--border);
    background: var(--bg-white);
}
.profilo-stat {
    text-align: center; padding: .9rem .5rem;
    border-right: 1px solid var(--border);
}
.profilo-stat:last-child { border-right: none; }
.profilo-stat-num   { font-size: 1.5rem; font-weight: 700; color: var(--accent); line-height: 1; }
.profilo-stat-label { font-size: .72rem; color: var(--text-light); margin-top: 3px; text-transform: uppercase; letter-spacing: .5px; }
.profilo-card-body  { padding: 1rem 1.25rem; background: var(--bg-white); }
.profilo-info-row   {
    display: flex; align-items: flex-start; gap: .6rem;
    padding: .55rem 0; border-bottom: 1px solid var(--border);
    font-size: .875rem; color: var(--text);
}
.profilo-info-row:last-child { border-bottom: none; }
.profilo-info-label { color: var(--text-light); min-width: 82px; font-size: .78rem; text-transform: uppercase; letter-spacing: .4px; padding-top: 1px; flex-shrink: 0; }
.profilo-info-value { font-weight: 500; flex: 1; word-break: break-all; color: var(--text); }
.profilo-tabs {
    display: flex; gap: .25rem;
    margin-bottom: 1.25rem;
    border-bottom: 2px solid var(--border);
    flex-wrap: wrap;
}
.profilo-tab {
    padding: .55rem 1.1rem;
    font-size: .875rem; font-weight: 600;
    color: var(--text-light);
    background: none; border: none; cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
    border-radius: 6px 6px 0 0;
    display: flex; align-items: center; gap: .4rem;
    white-space: nowrap;
}
.profilo-tab:hover { color: var(--accent); }
.profilo-tab.active { color: var(--accent); border-bottom-color: var(--accent); background: rgba(59,130,246,.06); }
.profilo-panel { display: none; }
.profilo-panel.active { display: block; }
.sessioni-list { display: flex; flex-direction: column; gap: .5rem; }
.sessione-row {
    display: flex; align-items: center; gap: .75rem;
    padding: .65rem .9rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px; font-size: .85rem;
}
.sessione-dot   { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex-shrink: 0; margin-top: 2px; }
.sessione-info  { flex: 1; min-width: 0; }
.sessione-date  { font-weight: 600; color: var(--text); }
.sessione-meta  { color: var(--text-light); font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sessione-badge { font-size: .72rem; padding: 2px 8px; border-radius: 20px; background: var(--primary-light); color: var(--accent); border: 1px solid #bfdbfe; white-space: nowrap; }
.pwd-toggle-btn {
    position: absolute; right: 32px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; font-size: 15px;
    color: var(--text-light); padding: 4px; line-height: 1;
}
.pwd-toggle-btn:hover { color: var(--accent); }
/* Zone upload avatar */
.avatar-upload-zone {
    background: var(--bg);
    border: 2px dashed var(--border);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    transition: border-color .18s, background .18s;
    cursor: pointer;
    position: relative;
}
.avatar-upload-zone:hover, .avatar-upload-zone.drag-over {
    border-color: var(--accent);
    background: rgba(59,130,246,.04);
}
.avatar-upload-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.avatar-preview {
    width: 80px; height: 80px; border-radius: 50%;
    object-fit: cover; border: 3px solid var(--border);
    margin: 0 auto .75rem; display: block;
    background: var(--bg-white);
}
.avatar-preview-initials {
    width: 80px; height: 80px; border-radius: 50%;
    background: var(--primary); color: #fff;
    font-size: 1.6rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto .75rem;
    border: 3px solid var(--border);
    flex-shrink: 0;
}
</style>

<?php
// Helper: mostra avatar o iniziali nella card header
$hasAvatar = !empty($avatarUrl);
?>

<div class="profilo-grid">
    <div>
        <div class="profilo-card-user">
            <div class="profilo-card-header">
                <!-- Avatar cliccabile → apre file picker -->
                <label for="avatar-quick-input" style="cursor:pointer;display:block;width:fit-content;margin:0 auto;">
                    <div class="profilo-avatar-big" title="Clicca per cambiare foto">
                        <?php if ($hasAvatar): ?>
                            <img src="<?= $avatarUrl ?>" alt="Foto profilo" loading="lazy">
                        <?php else: ?>
                            <?= $initials ?>
                        <?php endif; ?>
                        <div class="profilo-avatar-overlay">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </div>
                    </div>
                </label>
                <!-- Input file nascosto collegato all'avatar -->
                <form method="POST" enctype="multipart/form-data" id="avatar-quick-form">
                    <input type="hidden" name="action" value="upload_avatar">
                    <input type="file" id="avatar-quick-input" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" aria-label="Carica foto profilo">
                </form>
                <div class="profilo-card-name"><?= htmlspecialchars($user['nome'] . ' ' . $user['cognome']) ?></div>
                <div class="profilo-card-role"><?= htmlspecialchars(ucfirst($user['ruolo'])) ?></div>
                <div class="profilo-card-email"><?= htmlspecialchars($user['email']) ?></div>
                <?php if ($hasAvatar): ?>
                <form method="POST" style="margin-top:.6rem;">
                    <input type="hidden" name="action" value="remove_avatar">
                    <button type="submit" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.8);font-size:.72rem;padding:3px 10px;border-radius:20px;cursor:pointer;transition:background .15s;" onclick="return confirm('Rimuovere la foto profilo?')">
                        ✕ Rimuovi foto
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="profilo-stats">
                <div class="profilo-stat">
                    <div class="profilo-stat-num"><?= $totSess ?></div>
                    <div class="profilo-stat-label">Sessioni</div>
                </div>
                <div class="profilo-stat">
                    <div class="profilo-stat-num"><?= $totSegnR ?></div>
                    <div class="profilo-stat-label">Segnalazioni</div>
                </div>
            </div>
            <div class="profilo-card-body">
                <div class="profilo-info-row">
                    <span class="profilo-info-label">Nome</span>
                    <span class="profilo-info-value"><?= htmlspecialchars($user['nome']) ?></span>
                </div>
                <div class="profilo-info-row">
                    <span class="profilo-info-label">Cognome</span>
                    <span class="profilo-info-value"><?= htmlspecialchars($user['cognome']) ?></span>
                </div>
                <div class="profilo-info-row">
                    <span class="profilo-info-label">Email</span>
                    <span class="profilo-info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="profilo-info-row">
                    <span class="profilo-info-label">Telefono</span>
                    <span class="profilo-info-value">
                        <?= $user['telefono'] ? htmlspecialchars($user['telefono']) : '<em style="color:var(--text-light)">—</em>' ?>
                    </span>
                </div>
                <div class="profilo-info-row">
                    <span class="profilo-info-label">Ruolo</span>
                    <span class="profilo-info-value"><?= htmlspecialchars(ucfirst($user['ruolo'])) ?></span>
                </div>
                <?php if (!empty($user['created_at'])): ?>
                <div class="profilo-info-row">
                    <span class="profilo-info-label">Membro dal</span>
                    <span class="profilo-info-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div>
        <div class="profilo-tabs" role="tablist">
            <button class="profilo-tab <?= $activeTab === 'info' ? 'active' : '' ?>" data-tab="info" role="tab" aria-selected="<?= $activeTab === 'info' ? 'true' : 'false' ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Informazioni
            </button>
            <button class="profilo-tab <?= $activeTab === 'avatar' ? 'active' : '' ?>" data-tab="avatar" role="tab" aria-selected="<?= $activeTab === 'avatar' ? 'true' : 'false' ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Foto profilo
            </button>
            <button class="profilo-tab <?= $activeTab === 'email' ? 'active' : '' ?>" data-tab="email" role="tab" aria-selected="<?= $activeTab === 'email' ? 'true' : 'false' ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Cambia Email
            </button>
            <button class="profilo-tab <?= $activeTab === 'password' ? 'active' : '' ?>" data-tab="password" role="tab" aria-selected="<?= $activeTab === 'password' ? 'true' : 'false' ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Cambia Password
            </button>
            <button class="profilo-tab <?= $activeTab === 'sessioni' ? 'active' : '' ?>" data-tab="sessioni" role="tab" aria-selected="<?= $activeTab === 'sessioni' ? 'true' : 'false' ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Sessioni recenti
            </button>
        </div>

        <!-- TAB: Informazioni -->
        <div class="profilo-panel card <?= $activeTab === 'info' ? 'active' : '' ?>" id="panel-info" role="tabpanel">
            <div class="card-header"><h3>Informazioni personali</h3></div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="update_info">
                    <div class="form-row">
                        <?php
                        formField('nome', 'Nome', ['value' => $user['nome'], 'required' => true, 'max' => 100, 'extra' => 'autocapitalize="words"']);
                        formField('cognome', 'Cognome', ['value' => $user['cognome'], 'required' => true, 'max' => 100, 'extra' => 'autocapitalize="words"']);
                        ?>
                    </div>
                    <?php formTelefono('telefono', 'Telefono', ['value' => $user['telefono'] ?? '', 'placeholder' => 'es. 333 1234567', 'hint' => 'Opzionale']); ?>
                    <p style="font-size:.8rem;color:var(--text-light);margin-bottom:1rem;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Per cambiare email o password usa le tab dedicate.
                    </p>
                    <button type="submit" class="btn btn-success">Salva modifiche</button>
                </form>
            </div>
        </div>

        <!-- TAB: Foto profilo -->
        <div class="profilo-panel card <?= $activeTab === 'avatar' ? 'active' : '' ?>" id="panel-avatar" role="tabpanel">
            <div class="card-header"><h3>Foto profilo</h3></div>
            <div class="card-body">
                <p style="font-size:.875rem;color:var(--text-light);margin-bottom:1.25rem;">
                    Carica una foto che verrà mostrata nell'app. JPG, PNG, GIF o WebP — max 2 MB.
                </p>
                <form method="POST" enctype="multipart/form-data" novalidate id="form-avatar-upload">
                    <input type="hidden" name="action" value="upload_avatar">
                    <div class="avatar-upload-zone" id="avatarDropZone">
                        <input type="file" name="avatar" id="avatarFile" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Seleziona foto profilo">
                        <?php if ($hasAvatar): ?>
                            <img src="<?= $avatarUrl ?>" alt="Anteprima foto" class="avatar-preview" id="avatarPreview">
                        <?php else: ?>
                            <div class="avatar-preview-initials" id="avatarPreviewInitials"><?= $initials ?></div>
                            <img src="" alt="Anteprima" class="avatar-preview" id="avatarPreview" style="display:none">
                        <?php endif; ?>
                        <p style="font-size:.85rem;color:var(--text-light);margin:.25rem 0 0;">
                            <strong>Clicca</strong> o trascina qui la tua foto
                        </p>
                        <p style="font-size:.75rem;color:var(--text-light);margin:.2rem 0 0;">JPG, PNG, GIF, WebP &bull; max 2 MB</p>
                    </div>
                    <div style="margin-top:1rem;display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary" id="btnSaveAvatar" disabled>Salva foto</button>
                        <?php if ($hasAvatar): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="remove_avatar">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Rimuovere la foto profilo?')">Rimuovi foto</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: Cambia Email -->
        <div class="profilo-panel card <?= $activeTab === 'email' ? 'active' : '' ?>" id="panel-email" role="tabpanel">
            <div class="card-header"><h3>Cambia indirizzo email</h3></div>
            <div class="card-body">
                <p style="font-size:.875rem;color:var(--text-light);margin-bottom:1.25rem;">
                    Email attuale: <strong style="color:var(--text)"><?= htmlspecialchars($user['email']) ?></strong>
                </p>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="update_email">
                    <?php
                    formField('email', 'Nuova email', ['type' => 'email', 'placeholder' => 'nuova@email.com', 'required' => true, 'max' => 255, 'autocomplete' => 'email']);
                    formField('email_confirm', 'Conferma nuova email', ['type' => 'email', 'placeholder' => 'ripeti la nuova email', 'required' => true, 'max' => 255, 'autocomplete' => 'off', 'extra' => 'autocorrect="off" autocapitalize="off"']);
                    ?>
                    <div class="form-group" style="margin-top:.5rem;">
                        <label for="password_check">Password attuale <span class="req-mark" aria-hidden="true">*</span></label>
                        <div class="field-wrap">
                            <input type="password" id="password_check" name="password_check" class="form-control" required autocomplete="current-password" placeholder="Inserisci la tua password attuale">
                            <button type="button" onclick="togglePwd('password_check')" class="pwd-toggle-btn" title="Mostra/nascondi" aria-label="Mostra o nascondi password">&#128065;</button>
                            <span class="field-status" aria-hidden="true"></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Aggiorna email</button>
                </form>
            </div>
        </div>

        <!-- TAB: Cambia Password -->
        <div class="profilo-panel card <?= $activeTab === 'password' ? 'active' : '' ?>" id="panel-password" role="tabpanel">
            <div class="card-header"><h3>Cambia password</h3></div>
            <div class="card-body">
                <form method="POST" novalidate id="formPwd">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-group">
                        <label for="old_password">Password attuale <span class="req-mark" aria-hidden="true">*</span></label>
                        <div class="field-wrap">
                            <input type="password" id="old_password" name="old_password" class="form-control" required autocomplete="current-password" placeholder="La tua password attuale">
                            <button type="button" onclick="togglePwd('old_password')" class="pwd-toggle-btn" title="Mostra/nascondi" aria-label="Mostra o nascondi">&#128065;</button>
                            <span class="field-status" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:.75rem;">
                        <label for="new_password">Nuova password <span class="req-mark" aria-hidden="true">*</span></label>
                        <div class="field-wrap">
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password" placeholder="Almeno 6 caratteri" style="padding-right:72px">
                            <button type="button" onclick="togglePwd('new_password')" class="pwd-toggle-btn" title="Mostra/nascondi" aria-label="Mostra o nascondi">&#128065;</button>
                            <span class="field-status" aria-hidden="true"></span>
                        </div>
                        <div id="pwdStrength" style="margin-top:6px;display:none">
                            <div style="height:4px;border-radius:2px;background:var(--border);overflow:hidden">
                                <div id="pwdBar" style="height:100%;width:0;transition:width .3s,background .3s"></div>
                            </div>
                            <div id="pwdLabel" style="font-size:11px;margin-top:2px"></div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:.75rem;">
                        <label for="confirm_password">Conferma nuova password <span class="req-mark" aria-hidden="true">*</span></label>
                        <div class="field-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6" autocomplete="new-password" placeholder="Ripeti la nuova password" style="padding-right:72px">
                            <button type="button" onclick="togglePwd('confirm_password')" class="pwd-toggle-btn" title="Mostra/nascondi" aria-label="Mostra o nascondi">&#128065;</button>
                            <span class="field-status" aria-hidden="true"></span>
                        </div>
                        <div class="field-error" id="err_confirm_password" role="alert"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Cambia password</button>
                </form>
            </div>
        </div>

        <!-- TAB: Sessioni recenti -->
        <div class="profilo-panel card <?= $activeTab === 'sessioni' ? 'active' : '' ?>" id="panel-sessioni" role="tabpanel">
            <div class="card-header">
                <h3>Sessioni recenti <?php if ($totSess > 0): ?><small style="font-weight:400;font-size:.8rem;color:var(--text-light)">(ultime 8 di <?= $totSess ?>)</small><?php endif; ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($sessioni)): ?>
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <h4>Nessuna sessione registrata</h4>
                        <p style="font-size:.875rem">Le sessioni che firmi compariranno qui.</p>
                    </div>
                <?php else: ?>
                    <div class="sessioni-list">
                        <?php foreach ($sessioni as $s): ?>
                        <div class="sessione-row">
                            <div class="sessione-dot"></div>
                            <div class="sessione-info">
                                <div class="sessione-date"><?= htmlspecialchars($s['data']) ?> &mdash; <?= htmlspecialchars($s['ora_ingresso']) ?></div>
                                <div class="sessione-meta"><?= htmlspecialchars($s['lab_nome'] ?? '—') ?> &bull; Aula <?= htmlspecialchars($s['lab_aula'] ?? '—') ?><?php if ($s['classe_nome']): ?> &bull; <?= htmlspecialchars($s['classe_nome']) ?><?php endif; ?></div>
                            </div>
                            <span class="sessione-badge"><?= $s['ora_uscita'] ? 'Chiusa' : 'Aperta' ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($totSess > 8): ?>
                    <div style="margin-top:1rem;text-align:center">
                        <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary btn-sm">Vedi tutte le sessioni</a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php formFieldScripts(); ?>

<script>
/* Tab navigation */
(function () {
    const tabs = document.querySelectorAll('.profilo-tab');
    const panels = document.querySelectorAll('.profilo-panel');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            tab.setAttribute('aria-selected','true');
            const panel = document.getElementById('panel-' + tab.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });
})();

/* Avatar: anteprima in tempo reale + submit auto dal click nell'header */
(function () {
    function handleFile(file, previewEl, initialsEl, btnSave) {
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            if (previewEl) { previewEl.src = e.target.result; previewEl.style.display = 'block'; }
            if (initialsEl) initialsEl.style.display = 'none';
            if (btnSave) btnSave.disabled = false;
        };
        reader.readAsDataURL(file);
    }

    /* Picker rapido dall'header */
    var quickInput = document.getElementById('avatar-quick-input');
    var quickForm  = document.getElementById('avatar-quick-form');
    if (quickInput && quickForm) {
        quickInput.addEventListener('change', function () {
            if (quickInput.files && quickInput.files[0]) quickForm.submit();
        });
    }

    /* Tab foto profilo */
    var fileInput   = document.getElementById('avatarFile');
    var preview     = document.getElementById('avatarPreview');
    var initials    = document.getElementById('avatarPreviewInitials');
    var btnSave     = document.getElementById('btnSaveAvatar');
    var dropZone    = document.getElementById('avatarDropZone');

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) handleFile(fileInput.files[0], preview, initials, btnSave);
        });
    }
    if (dropZone) {
        dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('drag-over'); });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault(); dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file) {
                // Crea un DataTransfer per assegnare il file all'input
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                } catch(ex) {}
                handleFile(file, preview, initials, btnSave);
            }
        });
    }
})();

/* Password strength */
(function () {
    const pwd = document.getElementById('new_password');
    const bar = document.getElementById('pwdBar');
    const label = document.getElementById('pwdLabel');
    const wrap = document.getElementById('pwdStrength');
    if (!pwd || !bar || !label || !wrap) return;
    pwd.addEventListener('input', function () {
        const v = pwd.value;
        if (!v) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';
        let score = 0;
        if (v.length >= 6) score++;
        if (v.length >= 10) score++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const levels = [
            { pct:'20%', color:'#dc2626', text:'Molto debole' },
            { pct:'40%', color:'#d97706', text:'Debole' },
            { pct:'60%', color:'#ca8a04', text:'Media' },
            { pct:'80%', color:'#16a34a', text:'Forte' },
            { pct:'100%', color:'#15803d', text:'Molto forte' },
        ];
        const l = levels[Math.min(score - 1, 4)] || levels[0];
        bar.style.width = l.pct;
        bar.style.background = l.color;
        label.textContent = l.text;
        label.style.color = l.color;
    });
})();

/* Conferma password */
(function () {
    const form = document.getElementById('formPwd');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        const np = document.getElementById('new_password');
        const cp = document.getElementById('confirm_password');
        const errEl = document.getElementById('err_confirm_password');
        if (!np || !cp || !errEl) return;
        if (np.value !== cp.value) {
            e.preventDefault();
            errEl.textContent = 'Le due password non coincidono.';
            errEl.style.display = 'block';
            cp.classList.add('is-invalid');
        } else {
            errEl.textContent = '';
            errEl.style.display = 'none';
            cp.classList.remove('is-invalid');
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
