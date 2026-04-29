<?php
$pageTitle = 'Gestione Utenti';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';
requireAdmin();

$conn = getConnection();
$L    = lang();

/* ================================================================
   ACTIONS
   ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea') {
        $nome     = trim($_POST['nome']     ?? '');
        $cognome  = trim($_POST['cognome']  ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $ruolo    = in_array($_POST['ruolo'] ?? '', ['admin','docente']) ? $_POST['ruolo'] : 'docente';
        $telefono = trim($_POST['telefono'] ?? '');   // già unito da JS (prefisso + numero)
        $errors   = [];

        if (!$nome)                                                  $errors[] = $L['utenti_err_nome'];
        if (!$cognome)                                               $errors[] = $L['utenti_err_cognome'];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = $L['utenti_err_email'];
        if (strlen($password) < 6)                                   $errors[] = $L['utenti_err_pwd'];
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,25}$/', $telefono)) $errors[] = $L['utenti_err_tel'];

        if (empty($errors)) {
            $n_e  = mysqli_real_escape_string($conn, $nome);
            $c_e  = mysqli_real_escape_string($conn, $cognome);
            $e_e  = mysqli_real_escape_string($conn, $email);
            $p_e  = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
            $r_e  = mysqli_real_escape_string($conn, $ruolo);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';
            $ok = mysqli_query($conn, "INSERT INTO utenti (nome, cognome, email, password, ruolo, telefono) VALUES ('$n_e','$c_e','$e_e','$p_e','$r_e',$t_SQL)");
            if ($ok) { header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=' . urlencode($L['utenti_ok_creato']));   exit; }
            else     { header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error='   . urlencode($L['utenti_err_email_uso'])); exit; }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode(implode(' | ', $errors)));
            exit;
        }
    }

    if ($action === 'modifica') {
        $id       = intval($_POST['id']      ?? 0);
        $nome     = trim($_POST['nome']      ?? '');
        $cognome  = trim($_POST['cognome']   ?? '');
        $email    = trim($_POST['email']     ?? '');
        $ruolo    = in_array($_POST['ruolo'] ?? '', ['admin','docente']) ? $_POST['ruolo'] : 'docente';
        $telefono = trim($_POST['telefono']  ?? '');
        $attivo   = isset($_POST['attivo'])  ? 1 : 0;
        $newPass  = $_POST['new_password']   ?? '';
        $errors   = [];

        if (!$nome)                                                  $errors[] = $L['utenti_err_nome'];
        if (!$cognome)                                               $errors[] = $L['utenti_err_cognome'];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = $L['utenti_err_email'];
        if ($newPass && strlen($newPass) < 6)                        $errors[] = $L['utenti_err_pwd'];
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,25}$/', $telefono)) $errors[] = $L['utenti_err_tel'];

        if (empty($errors) && $id) {
            $n_e  = mysqli_real_escape_string($conn, $nome);
            $c_e  = mysqli_real_escape_string($conn, $cognome);
            $e_e  = mysqli_real_escape_string($conn, $email);
            $r_e  = mysqli_real_escape_string($conn, $ruolo);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';
            if ($newPass) {
                $p_e = mysqli_real_escape_string($conn, password_hash($newPass, PASSWORD_DEFAULT));
                mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', email='$e_e', password='$p_e', ruolo='$r_e', telefono=$t_SQL, attivo=$attivo WHERE id=$id");
            } else {
                mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', email='$e_e', ruolo='$r_e', telefono=$t_SQL, attivo=$attivo WHERE id=$id");
            }
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=' . urlencode($L['utenti_ok_aggiornato']));
            exit;
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?edit=' . $id . '&error=' . urlencode(implode(' | ', $errors)));
            exit;
        }
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        if ($id != getCurrentUserId()) {
            mysqli_query($conn, "DELETE FROM utenti WHERE id = $id");
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=' . urlencode($L['utenti_ok_eliminato']));
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode($L['utenti_err_no_self']));
        }
        exit;
    }
}

/* ================================================================
   READ
   ================================================================ */
$result = mysqli_query($conn, "SELECT * FROM utenti ORDER BY cognome, nome");
$utenti = [];
while ($row = mysqli_fetch_assoc($result)) $utenti[] = $row;

$editUser = null;
if (isset($_GET['edit'])) {
    $editId   = intval($_GET['edit']);
    $res      = mysqli_query($conn, "SELECT * FROM utenti WHERE id = $editId");
    $editUser = mysqli_fetch_assoc($res);
}

$isEdit = $editUser !== null;
?>

<?php formFieldStyles(); ?>

<!-- ============================================================
     FORM CREA / MODIFICA
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <h3><?= $isEdit ? htmlspecialchars($L['utenti_form_titolo_mod']) : htmlspecialchars($L['utenti_form_titolo_crea']) ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" id="formUtente" novalidate>
            <input type="hidden" name="action" value="<?= $isEdit ? 'modifica' : 'crea' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <?php
                formField('nome', $L['utenti_nome'], [
                    'value'        => $editUser['nome'] ?? '',
                    'required'     => true,
                    'max'          => 100,
                    'autocomplete' => 'given-name',
                ]);
                formField('cognome', $L['utenti_cognome'], [
                    'value'        => $editUser['cognome'] ?? '',
                    'required'     => true,
                    'max'          => 100,
                    'autocomplete' => 'family-name',
                ]);
                ?>
            </div>

            <div class="form-row">
                <?php
                formField('email', $L['utenti_email'], [
                    'type'         => 'email',
                    'value'        => $editUser['email'] ?? '',
                    'placeholder'  => $L['utenti_email_placeholder'],
                    'required'     => true,
                    'max'          => 255,
                    'autocomplete' => 'email',
                ]);
                ?>

                <!-- Password con toggle visibilità e indicatore forza -->
                <div class="form-group fg-password">
                    <label for="<?= $isEdit ? 'new_password' : 'password' ?>">
                        <?= htmlspecialchars($isEdit ? $L['utenti_pwd_nuova_label'] : $L['utenti_pwd_label']) ?>
                        <?php if ($isEdit): ?>
                            <span class="field-hint" style="font-weight:normal;display:inline"><?= htmlspecialchars($L['utenti_pwd_nuova_hint']) ?></span>
                        <?php else: ?>
                            <span class="req-mark" aria-hidden="true">*</span>
                        <?php endif; ?>
                    </label>
                    <div class="field-wrap">
                        <input
                            type="password"
                            id="<?= $isEdit ? 'new_password' : 'password' ?>"
                            name="<?= $isEdit ? 'new_password' : 'password' ?>"
                            class="form-control"
                            placeholder="<?= htmlspecialchars($L['utenti_pwd_placeholder']) ?>"
                            minlength="6"
                            autocomplete="new-password"
                            <?= $isEdit ? '' : 'required' ?>
                            style="padding-right:72px"
                        >
                        <button
                            type="button"
                            onclick="togglePwd('<?= $isEdit ? 'new_password' : 'password' ?>')"
                            class="pwd-toggle-btn"
                            title="Mostra/nascondi password"
                            aria-label="Mostra o nascondi password"
                        >👁</button>
                        <span class="field-status" aria-hidden="true"></span>
                    </div>
                    <?php if (!$isEdit): ?>
                        <div id="pwdStrength" style="margin-top:6px;display:none">
                            <div style="height:4px;border-radius:2px;background:var(--border);overflow:hidden">
                                <div id="pwdBar" style="height:100%;width:0;transition:width .3s,background .3s"></div>
                            </div>
                            <div id="pwdLabel" style="font-size:11px;margin-top:2px"></div>
                        </div>
                    <?php endif; ?>
                    <div class="field-error" id="err_<?= $isEdit ? 'new_password' : 'password' ?>" role="alert"></div>
                </div>
            </div>

            <div class="form-row">
                <?php
                formSelect('ruolo', $L['utenti_ruolo'], [
                    '' => $L['seleziona'],
                    'docente' => $L['utenti_ruolo_docente'],
                    'admin'   => $L['utenti_ruolo_admin'],
                ], [
                    'selected' => $editUser['ruolo'] ?? 'docente',
                    'required' => true,
                ]);

                // Campo telefono con dropdown prefisso internazionale
                formTelefono('tel_numero', $L['utenti_telefono'], [
                    'value'       => $editUser['telefono'] ?? '',
                    'placeholder' => $L['utenti_telefono_placeholder'],
                    'hint'        => $L['utenti_telefono_hint'],
                ]);
                ?>
            </div>

            <?php if ($isEdit): ?>
                <?php formCheckbox('attivo', $L['utenti_attivo_label'], (bool)$editUser['attivo']); ?>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <?= htmlspecialchars($isEdit ? $L['utenti_btn_salva'] : $L['utenti_btn_crea']) ?>
                </button>
                <?php if ($isEdit): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/utenti.php" class="btn btn-secondary"><?= htmlspecialchars($L['annulla']) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     LISTA UTENTI
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <h3>👥 <?= htmlspecialchars($L['utenti_titolo']) ?> (<?= count($utenti) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($utenti)): ?>
            <div class="empty-state"><div class="empty-icon">👥</div><h4><?= htmlspecialchars($L['utenti_nessuno']) ?></h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($L['utenti_col_nome_cognome']) ?></th>
                            <th><?= htmlspecialchars($L['utenti_col_email']) ?></th>
                            <th><?= htmlspecialchars($L['utenti_col_ruolo']) ?></th>
                            <th><?= htmlspecialchars($L['utenti_col_telefono']) ?></th>
                            <th><?= htmlspecialchars($L['utenti_col_stato']) ?></th>
                            <th><?= htmlspecialchars($L['azioni']) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utenti as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['cognome'] . ' ' . $u['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['ruolo'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>">
                                    <?= $u['ruolo'] === 'admin' ? '⚙ Admin' : '📋 Docente' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $u['attivo'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $u['attivo'] ? htmlspecialchars($L['attivo']) : htmlspecialchars($L['disattivato']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $u['id'] ?>" class="btn btn-primary btn-sm">✏ <?= htmlspecialchars($L['modifica']) ?></a>
                                <?php if ($u['id'] != getCurrentUserId()): ?>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm(<?= json_encode(sprintf($L['confirm_elimina_utente'], $u['cognome'] . ' ' . $u['nome'])) ?>)">
                                        <input type="hidden" name="action" value="elimina">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑 <?= htmlspecialchars($L['elimina']) ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.pwd-toggle-btn {
    position: absolute;
    right: 32px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
    color: var(--text-light);
    padding: 4px;
    line-height: 1;
}
.pwd-toggle-btn:hover { color: var(--primary); }
</style>

<?php formFieldScripts(); ?>

<script>
/* Validazione submit utenti */
(function () {
    const form = document.getElementById('formUtente');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        const checks = [
            { id: 'nome',    msg: <?= json_encode($L['utenti_err_nome']) ?>,    check: v => !!v },
            { id: 'cognome', msg: <?= json_encode($L['utenti_err_cognome']) ?>, check: v => !!v },
            { id: 'email',   msg: <?= json_encode($L['utenti_err_email']) ?>,   check: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) },
        ];
        checks.forEach(function (c) {
            const el = document.getElementById(c.id);
            if (!el) return;
            if (!c.check(el.value.trim())) { formShowErr(el, 'err_' + c.id, c.msg); valid = false; }
            else formClearErr(el, 'err_' + c.id);
        });

        /* Password */
        const isEdit = !!document.querySelector('input[name=new_password]');
        const pwdEl  = document.getElementById(isEdit ? 'new_password' : 'password');
        if (pwdEl) {
            if (!isEdit && pwdEl.value.length < 6) {
                formShowErr(pwdEl, 'err_password', <?= json_encode($L['utenti_err_pwd']) ?>);
                valid = false;
            } else if (isEdit && pwdEl.value && pwdEl.value.length < 6) {
                formShowErr(pwdEl, 'err_new_password', <?= json_encode($L['utenti_err_pwd']) ?>);
                valid = false;
            }
        }

        /* Telefono: usa valore hidden già assemblato */
        const telHidden = document.getElementById('tel_numero_full');
        if (telHidden && telHidden.value) {
            const re = /^[0-9\s\+\-\.()]{7,25}$/;
            if (!re.test(telHidden.value)) {
                const telEl = document.getElementById('tel_numero');
                formShowErr(telEl, 'err_tel_numero', <?= json_encode($L['utenti_err_tel']) ?>);
                valid = false;
            }
        }

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
