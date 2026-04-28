<?php
$pageTitle = 'Gestione Utenti';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea') {
        $nome     = trim($_POST['nome'] ?? '');
        $cognome  = trim($_POST['cognome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ruolo    = in_array($_POST['ruolo'] ?? '', ['admin','docente']) ? $_POST['ruolo'] : 'docente';
        $telefono = trim($_POST['telefono'] ?? '');
        $errors   = [];

        if (!$nome)                       $errors[] = 'Nome obbligatorio.';
        if (!$cognome)                    $errors[] = 'Cognome obbligatorio.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
        if (strlen($password) < 6)        $errors[] = 'Password: minimo 6 caratteri.';
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,20}$/', $telefono)) $errors[] = 'Telefono non valido.';

        if (empty($errors)) {
            $n_e = mysqli_real_escape_string($conn, $nome);
            $c_e = mysqli_real_escape_string($conn, $cognome);
            $e_e = mysqli_real_escape_string($conn, $email);
            $p_e = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
            $r_e = mysqli_real_escape_string($conn, $ruolo);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';
            $ok = mysqli_query($conn, "INSERT INTO utenti (nome, cognome, email, password_hash, ruolo, telefono) VALUES ('$n_e','$c_e','$e_e','$p_e','$r_e',$t_SQL)");
            if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente creato!');
            else     header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode('Email già in uso o errore DB'));
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'modifica') {
        $id      = intval($_POST['id'] ?? 0);
        $nome    = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $ruolo   = in_array($_POST['ruolo'] ?? '', ['admin','docente']) ? $_POST['ruolo'] : 'docente';
        $telefono= trim($_POST['telefono'] ?? '');
        $attivo  = isset($_POST['attivo']) ? 1 : 0;
        $newPass = $_POST['new_password'] ?? '';
        $errors  = [];

        if (!$nome)    $errors[] = 'Nome obbligatorio.';
        if (!$cognome) $errors[] = 'Cognome obbligatorio.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
        if ($newPass && strlen($newPass) < 6) $errors[] = 'Password: minimo 6 caratteri.';
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,20}$/', $telefono)) $errors[] = 'Telefono non valido.';

        if (empty($errors) && $id) {
            $n_e = mysqli_real_escape_string($conn, $nome);
            $c_e = mysqli_real_escape_string($conn, $cognome);
            $e_e = mysqli_real_escape_string($conn, $email);
            $r_e = mysqli_real_escape_string($conn, $ruolo);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';
            if ($newPass) {
                $p_e = mysqli_real_escape_string($conn, password_hash($newPass, PASSWORD_DEFAULT));
                mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', email='$e_e', password_hash='$p_e', ruolo='$r_e', telefono=$t_SQL, attivo=$attivo WHERE id=$id");
            } else {
                mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', email='$e_e', ruolo='$r_e', telefono=$t_SQL, attivo=$attivo WHERE id=$id");
            }
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente aggiornato!');
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?edit=' . $id . '&error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        if ($id != getCurrentUserId()) {
            mysqli_query($conn, "DELETE FROM utenti WHERE id = $id");
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente eliminato!');
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode('Non puoi eliminare te stesso!'));
        }
        exit;
    }
}

$result = mysqli_query($conn, "SELECT * FROM utenti ORDER BY cognome, nome");
$utenti = [];
while ($row = mysqli_fetch_assoc($result)) $utenti[] = $row;

$editUser = null;
if (isset($_GET['edit'])) {
    $editId   = intval($_GET['edit']);
    $res      = mysqli_query($conn, "SELECT * FROM utenti WHERE id = $editId");
    $editUser = mysqli_fetch_assoc($res);
}
?>

<div class="card">
    <div class="card-header"><h3><?= $editUser ? '&#9998; Modifica Utente' : '&#10133; Nuovo Utente' ?></h3></div>
    <div class="card-body">
        <form method="POST" id="formUtente" novalidate>
            <input type="hidden" name="action" value="<?= $editUser ? 'modifica' : 'crea' ?>">
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome *</label>
                    <input type="text" id="nome" name="nome" class="form-control" required
                           maxlength="100" autocomplete="given-name"
                           value="<?= htmlspecialchars($editUser['nome'] ?? '') ?>">
                    <div class="field-error" id="err_nome"></div>
                </div>
                <div class="form-group">
                    <label for="cognome">Cognome *</label>
                    <input type="text" id="cognome" name="cognome" class="form-control" required
                           maxlength="100" autocomplete="family-name"
                           value="<?= htmlspecialchars($editUser['cognome'] ?? '') ?>">
                    <div class="field-error" id="err_cognome"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           maxlength="255" autocomplete="email"
                           placeholder="nome@scuola.it"
                           value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
                    <div class="field-error" id="err_email"></div>
                </div>
                <div class="form-group">
                    <label for="password_field"><?= $editUser ? 'Nuova Password (lascia vuoto per non cambiare)' : 'Password *' ?></label>
                    <div style="position:relative">
                        <input type="password" id="password_field"
                               name="<?= $editUser ? 'new_password' : 'password' ?>"
                               class="form-control" <?= $editUser ? '' : 'required' ?>
                               minlength="6"
                               placeholder="Minimo 6 caratteri"
                               autocomplete="<?= $editUser ? 'new-password' : 'new-password' ?>"
                               style="padding-right:42px">
                        <button type="button" onclick="togglePwd()" title="Mostra/nascondi"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:var(--text-light)">&#128065;</button>
                    </div>
                    <div class="field-error" id="err_password"></div>
                    <?php if (!$editUser): ?>
                    <div class="pwd-strength" id="pwdStrength" style="margin-top:6px;display:none">
                        <div style="height:4px;border-radius:2px;background:var(--border);overflow:hidden">
                            <div id="pwdBar" style="height:100%;width:0;transition:width 0.3s,background 0.3s"></div>
                        </div>
                        <div id="pwdLabel" style="font-size:11px;margin-top:2px"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ruolo">Ruolo *</label>
                    <select id="ruolo" name="ruolo" class="form-control" required>
                        <option value="docente" <?= ($editUser['ruolo'] ?? 'docente') === 'docente' ? 'selected' : '' ?>>&#128203; Docente</option>
                        <option value="admin"   <?= ($editUser['ruolo'] ?? '') === 'admin'          ? 'selected' : '' ?>>&#9881; Amministratore</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="telefono">Telefono</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control"
                           maxlength="20" autocomplete="tel"
                           placeholder="Es: 333-1234567"
                           pattern="[0-9\s\+\-\.()]{7,20}"
                           value="<?= htmlspecialchars($editUser['telefono'] ?? '') ?>">
                    <div class="form-text">Formato: cifre, spazi, +, -, .</div>
                    <div class="field-error" id="err_telefono"></div>
                </div>
            </div>

            <?php if ($editUser): ?>
                <div class="form-group">
                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="attivo" value="1" <?= $editUser['attivo'] ? 'checked' : '' ?>> Account attivo
                    </label>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editUser ? '&#10004; Salva Modifiche' : '&#10133; Crea Utente' ?></button>
                <?php if ($editUser): ?><a href="<?= BASE_PATH ?>/pages/admin/utenti.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#128101; Utenti (<?= count($utenti) ?>)</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Cognome Nome</th><th>Email</th><th>Ruolo</th><th>Telefono</th><th>Stato</th><th>Azioni</th></tr></thead>
                <tbody>
                    <?php foreach ($utenti as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['cognome'] . ' ' . $u['nome']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge <?= $u['ruolo'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>"><?= $u['ruolo'] === 'admin' ? '&#9881; Admin' : '&#128203; Docente' ?></span></td>
                        <td><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                        <td><span class="badge <?= $u['attivo'] ? 'badge-success' : 'badge-danger' ?>"><?= $u['attivo'] ? 'Attivo' : 'Disattivato' ?></span></td>
                        <td class="actions">
                            <a href="?edit=<?= $u['id'] ?>" class="btn btn-primary btn-sm">&#9998; Modifica</a>
                            <?php if ($u['id'] != getCurrentUserId()): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Eliminare ' + <?= json_encode($u['cognome'] . ' ' . $u['nome']) ?> + '?');">
                                <input type="hidden" name="action" value="elimina">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">&#128465; Elimina</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display: none; }
.form-control.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(220,38,38,0.12); }
.form-control.is-valid   { border-color: var(--success); }
</style>

<script>
function togglePwd() {
    const f = document.getElementById('password_field');
    f.type = f.type === 'password' ? 'text' : 'password';
}

(function() {
    const form = document.getElementById('formUtente');
    if (!form) return;

    // Indicatore forza password
    const pwdInput = document.getElementById('password_field');
    const pwdBar   = document.getElementById('pwdBar');
    const pwdLabel = document.getElementById('pwdLabel');
    const pwdWrap  = document.getElementById('pwdStrength');
    if (pwdInput && pwdBar) {
        pwdInput.addEventListener('input', function() {
            const v = pwdInput.value;
            pwdWrap.style.display = v ? 'block' : 'none';
            let score = 0;
            if (v.length >= 6)  score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const levels = [
                { w: '20%',  bg: 'var(--danger)',  l: 'Molto debole' },
                { w: '40%',  bg: 'var(--warning)', l: 'Debole' },
                { w: '60%',  bg: '#f59e0b',        l: 'Accettabile' },
                { w: '80%',  bg: 'var(--info)',     l: 'Forte' },
                { w: '100%', bg: 'var(--success)',  l: 'Molto forte' },
            ];
            const lv = levels[Math.max(0, score - 1)];
            pwdBar.style.width      = lv.w;
            pwdBar.style.background = lv.bg;
            if (pwdLabel) { pwdLabel.textContent = lv.l; pwdLabel.style.color = lv.bg; }
        });
    }

    function showError(input, errEl, msg) {
        if (!errEl) return;
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        errEl.textContent = msg;
        errEl.style.display = 'block';
    }
    function clearError(input, errEl) {
        if (!errEl) return;
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        errEl.textContent = '';
        errEl.style.display = 'none';
    }

    form.addEventListener('submit', function(e) {
        let valid = true;

        const nome = document.getElementById('nome');
        if (nome) { if (!nome.value.trim()) { showError(nome, document.getElementById('err_nome'), 'Nome obbligatorio.'); valid = false; } else clearError(nome, document.getElementById('err_nome')); }

        const cognome = document.getElementById('cognome');
        if (cognome) { if (!cognome.value.trim()) { showError(cognome, document.getElementById('err_cognome'), 'Cognome obbligatorio.'); valid = false; } else clearError(cognome, document.getElementById('err_cognome')); }

        const email = document.getElementById('email');
        if (email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim() || !re.test(email.value)) { showError(email, document.getElementById('err_email'), 'Email non valida.'); valid = false; }
            else clearError(email, document.getElementById('err_email'));
        }

        const pwd = document.getElementById('password_field');
        const isEdit = pwd && pwd.name === 'new_password';
        if (pwd) {
            if (!isEdit && pwd.value.length < 6) { showError(pwd, document.getElementById('err_password'), 'Password: minimo 6 caratteri.'); valid = false; }
            else if (isEdit && pwd.value && pwd.value.length < 6) { showError(pwd, document.getElementById('err_password'), 'Password: minimo 6 caratteri.'); valid = false; }
            else clearError(pwd, document.getElementById('err_password'));
        }

        const tel = document.getElementById('telefono');
        if (tel && tel.value.trim()) {
            const reT = /^[0-9\s\+\-\.()]{7,20}$/;
            if (!reT.test(tel.value.trim())) { showError(tel, document.getElementById('err_telefono'), 'Formato telefono non valido.'); valid = false; }
            else clearError(tel, document.getElementById('err_telefono'));
        }

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>