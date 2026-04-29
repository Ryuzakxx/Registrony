<?php
/* ================================================================
   Gestione Materiali
   Accesso: solo admin e responsabili di laboratorio.
   Tecnici e docenti ordinari vengono bloccati qui.
   ================================================================ */
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getConnection();
$L    = lang();

/* --- Guardia esplicita: solo admin o responsabile di almeno un lab --- */
if (!isAdmin()) {
    if (!isDocente()) {
        // tecnici, ruoli sconosciuti
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
    $uid_guard = intval(getCurrentUserId());
    $r_guard   = mysqli_query($conn,
        "SELECT 1 FROM laboratori WHERE id_responsabile = $uid_guard AND attivo = 1 LIMIT 1");
    if (mysqli_num_rows($r_guard) === 0) {
        header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
        exit;
    }
}

/* Laboratori che l'utente può gestire */
if (isAdmin()) {
    $resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
} else {
    $userId  = intval(getCurrentUserId());
    $resLabs = mysqli_query($conn,
        "SELECT id, nome FROM laboratori WHERE attivo = 1 AND id_responsabile = $userId ORDER BY nome");
}
$labs = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

/* Failsafe: se per qualsiasi motivo $labs è vuoto, redirect */
if (empty($labs)) {
    header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
    exit;
}

$labIds   = array_column($labs, 'id');
$labIdsIn = implode(',', $labIds);

/* Se docente, pre-seleziona il lab attivo in sessione */
$defaultLab = null;
if (isDocente() && getSelectedLabId() && in_array((int)getSelectedLabId(), $labIds)) {
    $defaultLab = (int)getSelectedLabId();
}

$unitaPredefinite = ['pezzi','litri','ml','kg','g','metri','cm','rotoli','confezioni','scatole','flaconi','bottiglie'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome     = trim($_POST['nome']                ?? '');
        $desc     = trim($_POST['descrizione']         ?? '');
        $unita    = trim($_POST['unita_misura']        ?? '');
        $idLab    = intval($_POST['id_laboratorio']    ?? 0);
        $quantita = ($_POST['quantita_disponibile'] ?? '') !== '' ? floatval($_POST['quantita_disponibile']) : null;
        $soglia   = ($_POST['soglia_minima']        ?? '') !== '' ? floatval($_POST['soglia_minima'])        : null;
        $attivo   = isset($_POST['attivo']) ? 1 : 0;
        $errors   = [];

        if (!in_array($idLab, $labIds)) {
            header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?error=' . urlencode('Laboratorio non autorizzato'));
            exit;
        }

        if (!$nome)  $errors[] = $L['mat_err_nome'];
        if (!$idLab) $errors[] = $L['mat_err_lab'];
        if ($quantita !== null && $quantita < 0) $errors[] = $L['mat_err_quantita_neg'];
        if ($soglia   !== null && $soglia   < 0) $errors[] = $L['mat_err_soglia_neg'];
        if ($soglia !== null && $quantita !== null && $soglia > $quantita) $errors[] = $L['mat_err_soglia_sup'];

        if (empty($errors)) {
            $n_e   = mysqli_real_escape_string($conn, $nome);
            $d_SQL = $desc  ? "'" . mysqli_real_escape_string($conn, $desc)  . "'" : 'NULL';
            $u_SQL = $unita ? "'" . mysqli_real_escape_string($conn, $unita) . "'" : 'NULL';
            $q_SQL = $quantita !== null ? $quantita : 'NULL';
            $s_SQL = $soglia   !== null ? $soglia   : 'NULL';

            if ($action === 'crea') {
                mysqli_query($conn, "INSERT INTO materiali (nome, descrizione, unita_misura, id_laboratorio, quantita_disponibile, soglia_minima, attivo) VALUES ('$n_e',$d_SQL,$u_SQL,$idLab,$q_SQL,$s_SQL,$attivo)");
                header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?success=' . urlencode($L['mat_ok_creato']));
            } else {
                $id    = intval($_POST['id'] ?? 0);
                $check = mysqli_query($conn, "SELECT id_laboratorio FROM materiali WHERE id = $id");
                $mat   = mysqli_fetch_assoc($check);
                if (!$mat || !in_array((int)$mat['id_laboratorio'], $labIds)) {
                    header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?error=' . urlencode('Non autorizzato'));
                    exit;
                }
                mysqli_query($conn, "UPDATE materiali SET nome='$n_e', descrizione=$d_SQL, unita_misura=$u_SQL, id_laboratorio=$idLab, quantita_disponibile=$q_SQL, soglia_minima=$s_SQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?success=' . urlencode($L['mat_ok_aggiornato']));
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id    = intval($_POST['id'] ?? 0);
        $check = mysqli_query($conn, "SELECT id_laboratorio FROM materiali WHERE id = $id");
        $mat   = mysqli_fetch_assoc($check);
        if (!$mat || !in_array((int)$mat['id_laboratorio'], $labIds)) {
            header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?error=' . urlencode('Non autorizzato'));
            exit;
        }
        $ok = mysqli_query($conn, "DELETE FROM materiali WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?success=' . urlencode($L['mat_ok_eliminato']));
        else     header('Location: ' . BASE_PATH . '/pages/materiali/gestione.php?error='   . urlencode($L['mat_err_in_uso']));
        exit;
    }
}

$pageTitle = $L['mat_titolo_gest'] ?? 'Gestione Materiali';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';

/* Filtro lab: per docenti blocca sul lab della sessione, admin può scegliere */
$filtroLab = intval($_GET['laboratorio'] ?? ($defaultLab ?? 0));
if ($filtroLab && !in_array($filtroLab, $labIds)) $filtroLab = 0;
$labForzato = (!isAdmin() && count($labs) === 1);

$whereClause = "WHERE m.id_laboratorio IN ($labIdsIn)";
if ($filtroLab) $whereClause .= " AND m.id_laboratorio = $filtroLab";

$result    = mysqli_query($conn, "SELECT m.*, l.nome AS laboratorio FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id $whereClause ORDER BY l.nome, m.nome");
$materiali = [];
while ($row = mysqli_fetch_assoc($result)) $materiali[] = $row;

$editMat = null;
if (isset($_GET['edit'])) {
    $editId    = intval($_GET['edit']);
    $res       = mysqli_query($conn, "SELECT * FROM materiali WHERE id = $editId");
    $candidate = mysqli_fetch_assoc($res);
    if ($candidate && in_array((int)$candidate['id_laboratorio'], $labIds)) {
        $editMat = $candidate;
    }
}

$isEdit = $editMat !== null;

$labsMap = [''];
if (!$labForzato) $labsMap[''] = $L['mat_seleziona_lab'] ?? '-- Seleziona laboratorio --';
foreach ($labs as $lab) { $labsMap[$lab['id']] = $lab['nome']; }

$unitaCorrente = $editMat['unita_misura'] ?? '';
$unitaMap      = ['' => $L['mat_unita_vuota'] ?? '-- Unità --'];
foreach ($unitaPredefinite as $u) { $unitaMap[$u] = $u; }
if ($unitaCorrente && !in_array($unitaCorrente, $unitaPredefinite)) {
    $unitaMap[$unitaCorrente] = $unitaCorrente . ' (personalizzata)';
}
?>

<?php formFieldStyles(); ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars($isEdit ? ($L['mat_btn_salva'] ?? 'Modifica Materiale') : ('+ ' . ($L['mat_btn_crea'] ?? 'Nuovo Materiale'))) ?></h3>
        <?php if (!isAdmin()): ?>
            <small style="opacity:.7">Stai gestendo i laboratori di cui sei responsabile</small>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" id="formMateriale" novalidate>
            <input type="hidden" name="action" value="<?= $isEdit ? 'modifica' : 'crea' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editMat['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <?php
                formField('nome', $L['mat_nome'] ?? 'Nome', [
                    'value'       => $editMat['nome'] ?? '',
                    'placeholder' => $L['mat_nome_placeholder'] ?? 'Nome materiale',
                    'required'    => true,
                    'max'         => 150,
                ]);
                if ($labForzato) {
                    // Lab unico: campo nascosto + label visibile
                    echo '<div class="form-group"><label>' . htmlspecialchars($L['mat_lab'] ?? 'Laboratorio') . '</label>';
                    echo '<div style="padding:8px 12px;background:#e8f4f4;border-radius:6px;font-weight:600;color:#01696f">' . htmlspecialchars($labs[0]['nome']) . '</div>';
                    echo '<input type="hidden" name="id_laboratorio" value="' . $labs[0]['id'] . '"></div>';
                } else {
                    formSelect('id_laboratorio', $L['mat_lab'] ?? 'Laboratorio', $labsMap, [
                        'selected' => (string)($editMat['id_laboratorio'] ?? ($defaultLab ?? '')),
                        'required' => true,
                    ]);
                }
                ?>
            </div>

            <div class="form-row">
                <?php
                formSelect('unita_misura', $L['mat_unita'] ?? 'Unità di misura', $unitaMap, [
                    'selected' => $unitaCorrente,
                ]);
                formField('quantita_disponibile', $L['mat_quantita'] ?? 'Quantità disponibile', [
                    'type'  => 'number', 'value' => $editMat['quantita_disponibile'] ?? '',
                    'placeholder' => '0', 'min' => 0, 'step' => '0.01',
                ]);
                formField('soglia_minima', $L['mat_soglia'] ?? 'Soglia minima', [
                    'type'  => 'number', 'value' => $editMat['soglia_minima'] ?? '',
                    'placeholder' => '0', 'hint' => $L['mat_soglia_hint'] ?? 'Avviso scorte basse',
                    'min'   => 0, 'step' => '0.01',
                ]);
                ?>
            </div>

            <?php
            formTextarea('descrizione', $L['descrizione'] ?? 'Descrizione', [
                'value'       => $editMat['descrizione'] ?? '',
                'placeholder' => $L['mat_desc_placeholder'] ?? '',
                'rows'        => 2,
                'max'         => 500,
            ]);
            ?>

            <?php if ($isEdit): ?>
                <?php formCheckbox('attivo', $L['mat_attivo_label'] ?? 'Attivo', (bool)$editMat['attivo']); ?>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <?= htmlspecialchars($isEdit ? ($L['mat_btn_salva'] ?? 'Salva') : ($L['mat_btn_crea'] ?? 'Crea')) ?>
                </button>
                <?php if ($isEdit): ?>
                    <a href="<?= BASE_PATH ?>/pages/materiali/gestione.php" class="btn btn-secondary"><?= htmlspecialchars($L['annulla'] ?? 'Annulla') ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!$labForzato): /* Filtro lab solo se l'utente gestisce più lab */ ?>
<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
            <div class="form-group" style="margin-bottom:0">
                <label><?= htmlspecialchars($L['mat_filtra_lab'] ?? 'Filtra per laboratorio') ?></label>
                <select name="laboratorio" class="form-control">
                    <option value=""><?= htmlspecialchars($L['tutti'] ?? 'Tutti') ?></option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab == $lab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:22px">
                <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars($L['filtra'] ?? 'Filtra') ?></button>
                <a href="<?= BASE_PATH ?>/pages/materiali/gestione.php" class="btn btn-secondary btn-sm"><?= htmlspecialchars($L['reset'] ?? 'Reset') ?></a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars($L['mat_titolo_gest'] ?? 'Materiali') ?> (<?= count($materiali) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state">
                <div class="empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
                <h4><?= htmlspecialchars($L['mat_nessuno'] ?? 'Nessun materiale') ?></h4>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($L['nome'] ?? 'Nome') ?></th>
                            <?php if (!$labForzato): ?><th><?= htmlspecialchars($L['mat_lab'] ?? 'Lab') ?></th><?php endif; ?>
                            <th><?= htmlspecialchars($L['mat_unita_col'] ?? 'Unità') ?></th>
                            <th><?= htmlspecialchars($L['mat_disponibile'] ?? 'Disponibile') ?></th>
                            <th><?= htmlspecialchars($L['mat_soglia_col'] ?? 'Soglia') ?></th>
                            <th><?= htmlspecialchars($L['stato'] ?? 'Stato') ?></th>
                            <th><?= htmlspecialchars($L['azioni'] ?? 'Azioni') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiali as $m): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($m['nome']) ?></strong>
                                <?php if ($m['descrizione']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($m['descrizione']) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php if (!$labForzato): ?><td><?= htmlspecialchars($m['laboratorio']) ?></td><?php endif; ?>
                            <td><?= htmlspecialchars($m['unita_misura'] ?? '-') ?></td>
                            <td><strong><?= $m['quantita_disponibile'] ?? '-' ?></strong></td>
                            <td><?= $m['soglia_minima'] ?? '-' ?></td>
                            <td>
                                <?php if (!$m['attivo']): ?>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($L['mat_stato_disattivato'] ?? 'Disattivato') ?></span>
                                <?php elseif ($m['quantita_disponibile'] !== null && $m['quantita_disponibile'] <= 0): ?>
                                    <span class="badge badge-danger"><?= htmlspecialchars($L['mat_stato_esaurito'] ?? 'Esaurito') ?></span>
                                <?php elseif ($m['quantita_disponibile'] !== null && $m['soglia_minima'] !== null && $m['quantita_disponibile'] <= $m['soglia_minima']): ?>
                                    <span class="badge badge-warning"><?= htmlspecialchars($L['mat_stato_esaurimento'] ?? 'In esaurimento') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= htmlspecialchars($L['mat_stato_ok'] ?? 'OK') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $m['id'] ?><?= $filtroLab ? '&laboratorio='.$filtroLab : '' ?>" class="btn btn-primary btn-sm">Modifica</a>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm(<?= json_encode(sprintf($L['confirm_elimina_materiale'] ?? 'Eliminare %s?', $m['nome'])) ?>)">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php formFieldScripts(); ?>

<script>
(function () {
    const form = document.getElementById('formMateriale');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let valid = true;
        const nome = document.getElementById('nome');
        if (nome && !nome.value.trim()) {
            formShowErr(nome, 'err_nome', <?= json_encode($L['mat_err_nome'] ?? 'Nome obbligatorio') ?>); valid = false;
        } else if (nome) formClearErr(nome, 'err_nome');
        const lab = document.getElementById('id_laboratorio');
        if (lab && !lab.value) {
            formShowErr(lab, 'err_id_laboratorio', <?= json_encode($L['mat_err_lab'] ?? 'Laboratorio obbligatorio') ?>); valid = false;
        } else if (lab) formClearErr(lab, 'err_id_laboratorio');
        const qEl = document.getElementById('quantita_disponibile');
        if (qEl && qEl.value !== '' && parseFloat(qEl.value) < 0) {
            formShowErr(qEl, 'err_quantita_disponibile', <?= json_encode($L['mat_err_quantita_neg'] ?? 'Quantità non può essere negativa') ?>); valid = false;
        } else if (qEl) formClearErr(qEl, 'err_quantita_disponibile');
        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
