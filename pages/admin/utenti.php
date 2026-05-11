<?php
/* ================================================================
   ACTIONS — devono stare PRIMA di qualsiasi output (header.php)
   ================================================================ */
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$conn = getConnection();
$L    = lang();

// Capitalizza ogni parola mantenendo le accentate (es. "maria grazia" → "Maria Grazia")
function capitalizzaNome(string $s): string {
    return mb_convert_case(mb_strtolower(trim($s), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea') {
        $nome     = capitalizzaNome($_POST['nome']    ?? '');
        $cognome  = capitalizzaNome($_POST['cognome'] ?? '');
        $email    = strtolower(trim($_POST['email']   ?? ''));
        $password = $_POST['password']      ?? '';
        $ruolo    = in_array($_POST['ruolo'] ?? '', ['admin','docente','tecnico']) ? $_POST['ruolo'] : 'docente';
        $telefono = trim($_POST['telefono'] ?? '');
        $errors   = [];

        if (!$nome)                                                  $errors[] = $L['utenti_err_nome'];
        if (!$cognome)                                               $errors[] = $L['utenti_err_cognome'];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = $L['utenti_err_email'];
        if (strlen($password) < 6)                                   $errors[] = $L['utenti_err_pwd'];
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,25}$/', $telefono)) $errors[] = $L['utenti_err_tel'];

        // Pre-check email già registrata (evita eccezione da UNIQUE KEY)
        if (empty($errors)) {
            $e_safe = mysqli_real_escape_string($conn, $email);
            $dup = mysqli_query($conn, "SELECT id FROM utenti WHERE email = '$e_safe' LIMIT 1");
            if (mysqli_num_rows($dup) > 0) {
                $errors[] = $L['utenti_err_email_uso'];
            }
        }

        if (empty($errors)) {
            $n_e   = mysqli_real_escape_string($conn, $nome);
            $c_e   = mysqli_real_escape_string($conn, $cognome);
            $e_e   = mysqli_real_escape_string($conn, $email);
            $r_e   = mysqli_real_escape_string($conn, $ruolo);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';
            // Salva la password fornita come hash bcrypt.
            // must_change_password = 1: al primo accesso l'utente dovrà impostare la propria password.
            $p_hash = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_BCRYPT));
            $hasMCP = _columnExists($conn, 'utenti', 'must_change_password');
            $mcpField = $hasMCP ? ', must_change_password' : '';
            $mcpValue = $hasMCP ? ', 1' : '';
            mysqli_query($conn, "INSERT INTO utenti (nome, cognome, email, password, ruolo, telefono$mcpField) VALUES ('$n_e','$c_e','$e_e','$p_hash','$r_e',$t_SQL$mcpValue)");
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=' . urlencode($L['utenti_ok_creato']));
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'modifica') {
        $id       = intval($_POST['id']      ?? 0);
        $nome     = capitalizzaNome($_POST['nome']    ?? '');
        $cognome  = capitalizzaNome($_POST['cognome'] ?? '');
        $email    = strtolower(trim($_POST['email']   ?? ''));
        $ruolo    = in_array($_POST['ruolo'] ?? '', ['admin','docente','tecnico']) ? $_POST['ruolo'] : 'docente';
        $telefono = trim($_POST['telefono']  ?? '');
        $attivo   = isset($_POST['attivo'])  ? 1 : 0;
        $newPass  = $_POST['new_password']   ?? '';
        $labIds   = array_map('intval', $_POST['lab_ids'] ?? []);
        $errors   = [];

        if (!$nome)                                                  $errors[] = $L['utenti_err_nome'];
        if (!$cognome)                                               $errors[] = $L['utenti_err_cognome'];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = $L['utenti_err_email'];
        if ($newPass && strlen($newPass) < 6)                        $errors[] = $L['utenti_err_pwd'];
        if ($telefono && !preg_match('/^[0-9\s\+\-\.()]{7,25}$/', $telefono)) $errors[] = $L['utenti_err_tel'];

        // Pre-check email già usata da un altro utente
        if (empty($errors) && $id) {
            $e_safe = mysqli_real_escape_string($conn, $email);
            $dup = mysqli_query($conn, "SELECT id FROM utenti WHERE email = '$e_safe' AND id != $id LIMIT 1");
            if (mysqli_num_rows($dup) > 0) {
                $errors[] = $L['utenti_err_email_uso'];
            }
        }

        // Vincolo: un tecnico NON può essere responsabile di un laboratorio
        if ($ruolo === 'tecnico') {
            $resResp = mysqli_query($conn, "SELECT nome FROM laboratori WHERE id_responsabile = $id");
            if (mysqli_num_rows($resResp) > 0) {
                $nomiLab = [];
                while ($r = mysqli_fetch_assoc($resResp)) $nomiLab[] = $r['nome'];
                $errors[] = 'Un assistente tecnico non può essere responsabile di laboratorio. Rimuovi prima la responsabilità su: ' . implode(', ', $nomiLab);
            }
        }

        if (empty($errors) && $id) {
            $n_e   = mysqli_real_escape_string($conn, $nome);
            $c_e   = mysqli_real_escape_string($conn, $cognome);
            $e_e   = mysqli_real_escape_string($conn, $email);
            $r_e   = mysqli_real_escape_string($conn, $ruolo);
            $t_SQL = $telefono ? "'" . mysqli_real_escape_string($conn, $telefono) . "'" : 'NULL';

            if ($newPass) {
                // Nuova password impostata dall'admin: hash bcrypt, reset del flag primo accesso
                $p_hash = mysqli_real_escape_string($conn, password_hash($newPass, PASSWORD_BCRYPT));
                $hasMCP = _columnExists($conn, 'utenti', 'must_change_password');
                // Se l'admin resetta la password, rimette must_change_password=1
                // così l'utente dovrà cambiarla al prossimo accesso
                $mcpSet = $hasMCP ? ', must_change_password = 1' : '';
                mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', email='$e_e', password='$p_hash', ruolo='$r_e', telefono=$t_SQL, attivo=$attivo$mcpSet WHERE id=$id");
            } else {
                mysqli_query($conn, "UPDATE utenti SET nome='$n_e', cognome='$c_e', email='$e_e', ruolo='$r_e', telefono=$t_SQL, attivo=$attivo WHERE id=$id");
            }

            // Gestisci assegnazione laboratori: solo per docenti
            mysqli_query($conn, "DELETE FROM docenti_laboratori WHERE id_docente=$id");
            if ($ruolo === 'docente' && !empty($labIds)) {
                foreach ($labIds as $labId) {
                    if ($labId > 0) {
                        mysqli_query($conn, "INSERT IGNORE INTO docenti_laboratori (id_docente, id_laboratorio) VALUES ($id, $labId)");
                    }
                }
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
        if ($id == getCurrentUserId()) {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode($L['utenti_err_no_self']));
            exit;
        }

        // Controlla se è responsabile di qualche laboratorio
        $resLab = mysqli_query($conn, "SELECT nome FROM laboratori WHERE id_responsabile = $id");
        if (mysqli_num_rows($resLab) > 0) {
            $nomiLab = [];
            while ($row = mysqli_fetch_assoc($resLab)) $nomiLab[] = $row['nome'];
            $errMsg = 'Impossibile eliminare: l\'utente è responsabile dei laboratori: ' . implode(', ', $nomiLab) . '. Riassegna prima il responsabile.';
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode($errMsg));
            exit;
        }

        // Controlla se è assistente tecnico di qualche laboratorio
        $resTec = mysqli_query($conn, "SELECT nome FROM laboratori WHERE id_assistente_tecnico = $id");
        if (mysqli_num_rows($resTec) > 0) {
            $nomiLab = [];
            while ($row = mysqli_fetch_assoc($resTec)) $nomiLab[] = $row['nome'];
            $errMsg = 'Impossibile eliminare: l\'utente è assistente tecnico dei laboratori: ' . implode(', ', $nomiLab) . '. Riassegna prima il tecnico.';
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=' . urlencode($errMsg));
            exit;
        }

        mysqli_query($conn, "DELETE FROM utenti WHERE id = $id");
        header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=' . urlencode($L['utenti_ok_eliminato']));
        exit;
    }
}

/* ================================================================
   READ — dopo i redirect, ora l'HTML può iniziare
   ================================================================ */
$pageTitle = 'Gestione Utenti';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';

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

// Tutti i laboratori attivi (per il pannello assegnazione)
$allLabs = [];
$resLabs = mysqli_query($conn, "SELECT id, nome, aula FROM laboratori WHERE attivo=1 ORDER BY nome");
while ($row = mysqli_fetch_assoc($resLabs)) $allLabs[] = $row;

// Lab già assegnati al docente in modifica
$assignedLabIds = [];
if ($isEdit && $editUser['ruolo'] === 'docente') {
    $resAssigned = mysqli_query($conn, "SELECT id_laboratorio FROM docenti_laboratori WHERE id_docente=" . (int)$editUser['id']);
    while ($row = mysqli_fetch_assoc($resAssigned)) $assignedLabIds[] = (int)$row['id_laboratorio'];
}

// Mappa docente→lab per la colonna nella tabella utenti
$docenteLabsMap = [];
$resDocLabs = mysqli_query($conn, "
    SELECT dl.id_docente, l.nome, l.aula, (l.id_responsabile = dl.id_docente) AS is_resp
    FROM docenti_laboratori dl
    JOIN laboratori l ON dl.id_laboratorio = l.id
    ORDER BY dl.id_docente, l.nome
");
while ($row = mysqli_fetch_assoc($resDocLabs)) {
    $docenteLabsMap[$row['id_docente']][] = $row;
}
?>

<?php formFieldStyles(); ?>

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
                    'extra'        => 'autocapitalize="words"',
                ]);
                formField('cognome', $L['utenti_cognome'], [
                    'value'        => $editUser['cognome'] ?? '',
                    'required'     => true,
                    'max'          => 100,
                    'autocomplete' => 'family-name',
                    'extra'        => 'autocapitalize="words"',
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
                        <button type="button" onclick="togglePwd('<?= $isEdit ? 'new_password' : 'password' ?>')" class="pwd-toggle-btn" title="Mostra/nascondi password" aria-label="Mostra o nascondi password">&#128065;</button>
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
                $ruoloOptions = [
                    ''        => $L['seleziona'],
                    'docente' => $L['utenti_ruolo_docente'] ?? 'Docente',
                    'tecnico' => 'Tecnico',
                    'admin'   => $L['utenti_ruolo_admin'],
                ];
                formSelect('ruolo', $L['utenti_ruolo'], $ruoloOptions, [
                    'selected' => $editUser['ruolo'] ?? 'docente',
                    'required' => true,
                    'extra'    => 'id="selectRuolo"',
                ]);
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

            <div id="labAssignSection" style="display:<?= ($isEdit && $editUser['ruolo'] === 'docente') ? 'block' : 'none' ?>; margin-top:1.5rem;">
                <hr style="margin-bottom:1rem; border:none; border-top:1px solid var(--border,#e0e0e0);">
                <h4 style="margin-bottom:.5rem; font-size:1rem; font-weight:600; display:flex; align-items:center; gap:.4rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Laboratori assegnati al docente
                </h4>
                <p style="color:var(--text-light,#888);font-size:.85rem;margin-bottom:.75rem;">
                    Spunta i laboratori a cui questo docente può accedere. Il badge <strong>Responsabile</strong> si assegna nel pannello <em>Laboratori</em>.
                </p>
                <?php if (empty($allLabs)): ?>
                    <p style="color:var(--text-light,#888);font-size:.9rem;">Nessun laboratorio attivo disponibile.</p>
                <?php else: ?>
                    <div class="lab-checkboxes-grid">
                        <?php foreach ($allLabs as $lab): ?>
                            <label class="lab-checkbox-item">
                                <input type="checkbox" name="lab_ids[]" value="<?= (int)$lab['id'] ?>"
                                    <?= in_array((int)$lab['id'], $assignedLabIds) ? 'checked' : '' ?>>
                                <span class="lab-checkbox-nome"><?= htmlspecialchars($lab['nome']) ?></span>
                                <span class="lab-checkbox-aula">Aula <?= htmlspecialchars($lab['aula']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p style="margin-top:.6rem;font-size:.78rem;color:var(--text-light,#888);">
                    &#9888;&#65039; I tecnici NON si assegnano qui: la loro associazione ai lab avviene nella gestione <strong>Laboratori</strong> (campo "Assistente Tecnico").
                </p>
            </div>

            <div class="d-flex gap-2" style="margin-top:1.25rem;">
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

<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars($L['utenti_titolo']) ?> (<?= count($utenti) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($utenti)): ?>
            <div class="empty-state"><h4><?= htmlspecialchars($L['utenti_nessuno']) ?></h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($L['utenti_col_nome_cognome']) ?></th>
                            <th><?= htmlspecialchars($L['utenti_col_email']) ?></th>
                            <th><?= htmlspecialchars($L['utenti_col_ruolo']) ?></th>
                            <th>Laboratori</th>
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
                                <?php
                                $badgeClass = match($u['ruolo']) {
                                    'admin'   => 'badge-primary',
                                    'tecnico' => 'badge-warning',
                                    default   => 'badge-secondary',
                                };
                                $ruoloLabel = match($u['ruolo']) {
                                    'admin'   => 'Admin',
                                    'tecnico' => 'Tecnico',
                                    default   => 'Docente',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $ruoloLabel ?></span>
                            </td>
                            <td>
                                <?php if ($u['ruolo'] === 'docente' && !empty($docenteLabsMap[$u['id']])): ?>
                                    <div class="lab-tags">
                                        <?php foreach ($docenteLabsMap[$u['id']] as $dl): ?>
                                            <span class="lab-tag <?= $dl['is_resp'] ? 'lab-tag-resp' : '' ?>">
                                                <?= htmlspecialchars($dl['nome']) ?>
                                                <?php if ($dl['is_resp']): ?><em title="Responsabile"> &#9733;</em><?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($u['ruolo'] === 'tecnico'): ?>
                                    <?php
                                    $resTecLabs = mysqli_query($conn, "SELECT nome FROM laboratori WHERE id_assistente_tecnico={$u['id']} AND attivo=1 ORDER BY nome");
                                    $tecLabs = [];
                                    while ($tl = mysqli_fetch_assoc($resTecLabs)) $tecLabs[] = $tl['nome'];
                                    ?>
                                    <?php if (!empty($tecLabs)): ?>
                                        <div class="lab-tags">
                                            <?php foreach ($tecLabs as $tln): ?>
                                                <span class="lab-tag lab-tag-tec"><?= htmlspecialchars($tln) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-light,#aaa);font-size:.85rem;">—</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--text-light,#aaa);font-size:.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $u['attivo'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $u['attivo'] ? htmlspecialchars($L['attivo']) : htmlspecialchars($L['disattivato']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $u['id'] ?>" class="btn btn-primary btn-sm"><?= htmlspecialchars($L['modifica']) ?></a>
                                <?php if ($u['id'] != getCurrentUserId()): ?>
                                    <form method="POST" style="display:inline" onsubmit="return confirm(<?= json_encode(sprintf($L['confirm_elimina_utente'], $u['cognome'] . ' ' . $u['nome'])) ?>)">
                                        <input type="hidden" name="action" value="elimina">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><?= htmlspecialchars($L['elimina']) ?></button>
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
.pwd-toggle-btn { position:absolute; right:32px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:15px; color:var(--text-light,#888); padding:4px; line-height:1; }
.pwd-toggle-btn:hover { color:var(--primary,#01696f); }
.lab-checkboxes-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px,1fr)); gap:.45rem; margin-bottom:.5rem; }
.lab-checkbox-item { display:flex; align-items:center; gap:.5rem; padding:.45rem .75rem; background:var(--bg-light,#f9f9f9); border:1.5px solid var(--border,#e0e0e0); border-radius:6px; cursor:pointer; transition:border-color .15s, background .15s; user-select:none; }
.lab-checkbox-item:hover { border-color:var(--primary,#01696f); background:#f0f8f8; }
.lab-checkbox-item input[type=checkbox] { flex-shrink:0; width:16px; height:16px; accent-color:var(--primary,#01696f); cursor:pointer; }
.lab-checkbox-item input[type=checkbox]:checked ~ .lab-checkbox-nome { color:var(--primary,#01696f); }
.lab-checkbox-nome { font-weight:600; font-size:.88rem; flex:1; }
.lab-checkbox-aula { font-size:.75rem; color:var(--text-light,#888); white-space:nowrap; }
.lab-tags { display:flex; flex-wrap:wrap; gap:.25rem; }
.lab-tag { font-size:.72rem; padding:2px 8px; border-radius:20px; background:#e8f4f4; color:#01696f; border:1px solid #c5e0e0; white-space:nowrap; }
.lab-tag-resp { background:#fef9e7; color:#b8860b; border-color:#f0d060; }
.lab-tag-tec  { background:#fef3e2; color:#c07000; border-color:#f0c080; }
.lab-tag em   { font-style:normal; margin-left:2px; }
</style>

<?php formFieldScripts(); ?>

<script>
/* Auto-capitalizza mentre si scrive */
(function () {
    function titleCase(str) {
        return str.replace(/\b(\w)/g, function(ch) { return ch.toUpperCase(); });
    }
    ['nome', 'cognome'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function() {
            const pos = el.selectionStart;
            el.value = titleCase(el.value);
            el.setSelectionRange(pos, pos);
        });
        el.addEventListener('blur', function() {
            el.value = titleCase(el.value.trim());
        });
    });
})();

/* Mostra/nascondi sezione assegnazione lab */
(function () {
    const sel     = document.getElementById('selectRuolo');
    const section = document.getElementById('labAssignSection');
    if (!sel || !section) return;
    function toggle() {
        section.style.display = sel.value === 'docente' ? 'block' : 'none';
    }
    sel.addEventListener('change', toggle);
    toggle();
})();

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
        const isEdit = !!document.querySelector('input[name=new_password]');
        const pwdEl  = document.getElementById(isEdit ? 'new_password' : 'password');
        if (pwdEl) {
            if (!isEdit && pwdEl.value.length < 6) { formShowErr(pwdEl, 'err_password', <?= json_encode($L['utenti_err_pwd']) ?>); valid = false; }
            else if (isEdit && pwdEl.value && pwdEl.value.length < 6) { formShowErr(pwdEl, 'err_new_password', <?= json_encode($L['utenti_err_pwd']) ?>); valid = false; }
        }
        const telHidden = document.getElementById('tel_numero_full');
        if (telHidden && telHidden.value) {
            if (!/^[0-9\s\+\-\.()]{7,25}$/.test(telHidden.value)) {
                formShowErr(document.getElementById('tel_numero'), 'err_tel_numero', <?= json_encode($L['utenti_err_tel']) ?>);
                valid = false;
            }
        }
        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
