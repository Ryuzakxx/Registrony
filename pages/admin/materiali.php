<?php
$pageTitle = 'Gestione Materiali';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';
requireAdmin();

$conn = getConnection();
$L    = lang();

$unitaPredefinite = ['pezzi','litri','ml','kg','g','metri','cm','rotoli','confezioni','scatole','flaconi','bottiglie'];

/* ================================================================
   ACTIONS
   ================================================================ */
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
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=' . urlencode($L['mat_ok_creato']));
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn, "UPDATE materiali SET nome='$n_e', descrizione=$d_SQL, unita_misura=$u_SQL, id_laboratorio=$idLab, quantita_disponibile=$q_SQL, soglia_minima=$s_SQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=' . urlencode($L['mat_ok_aggiornato']));
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM materiali WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=' . urlencode($L['mat_ok_eliminato']));
        else     header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error='   . urlencode($L['mat_err_in_uso']));
        exit;
    }
}

/* ================================================================
   READ
   ================================================================ */
$filtroLab = intval($_GET['laboratorio'] ?? 0);
$where     = $filtroLab ? "WHERE m.id_laboratorio = $filtroLab" : '';

$result    = mysqli_query($conn, "SELECT m.*, l.nome AS laboratorio FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id $where ORDER BY l.nome, m.nome");
$materiali = [];
while ($row = mysqli_fetch_assoc($result)) $materiali[] = $row;

$resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs    = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$editMat = null;
if (isset($_GET['edit'])) {
    $editId  = intval($_GET['edit']);
    $res     = mysqli_query($conn, "SELECT * FROM materiali WHERE id = $editId");
    $editMat = mysqli_fetch_assoc($res);
}

$isEdit = $editMat !== null;

/* Opzioni select */
$labsMap = ['' => $L['mat_seleziona_lab']];
foreach ($labs as $lab) { $labsMap[$lab['id']] = $lab['nome']; }

$unitaCorrente = $editMat['unita_misura'] ?? '';
$unitaMap = ['' => $L['mat_unita_vuota']];
foreach ($unitaPredefinite as $u) { $unitaMap[$u] = $u; }
if ($unitaCorrente && !in_array($unitaCorrente, $unitaPredefinite)) {
    $unitaMap[$unitaCorrente] = $unitaCorrente . ' (personalizzata)';
}
?>

<?php formFieldStyles(); ?>

<!-- ============================================================
     FORM CREA / MODIFICA
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars($isEdit ? '✏ Modifica Materiale' : '+ Nuovo Materiale') ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" id="formMateriale" novalidate>
            <input type="hidden" name="action" value="<?= $isEdit ? 'modifica' : 'crea' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editMat['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <?php
                formField('nome', $L['mat_nome'], [
                    'value'       => $editMat['nome'] ?? '',
                    'placeholder' => $L['mat_nome_placeholder'],
                    'required'    => true,
                    'max'         => 150,
                ]);

                if (empty($labs)) {
                    echo '<div class="alert alert-warning" style="margin:0">⚠ ' . htmlspecialchars($L['mat_err_nessun_lab']) . '</div>';
                } else {
                    formSelect('id_laboratorio', $L['mat_lab'], $labsMap, [
                        'selected' => (string)($editMat['id_laboratorio'] ?? ''),
                        'required' => true,
                    ]);
                }
                ?>
            </div>

            <div class="form-row">
                <?php
                formSelect('unita_misura', $L['mat_unita'], $unitaMap, [
                    'selected' => $unitaCorrente,
                ]);

                formField('quantita_disponibile', $L['mat_quantita'], [
                    'type'        => 'number',
                    'value'       => $editMat['quantita_disponibile'] ?? '',
                    'placeholder' => $L['mat_quantita_placeholder'],
                    'min'         => 0,
                    'step'        => '0.01',
                ]);

                formField('soglia_minima', $L['mat_soglia'], [
                    'type'        => 'number',
                    'value'       => $editMat['soglia_minima'] ?? '',
                    'placeholder' => $L['mat_soglia_placeholder'],
                    'hint'        => $L['mat_soglia_hint'],
                    'min'         => 0,
                    'step'        => '0.01',
                ]);
                ?>
            </div>

            <?php
            formTextarea('descrizione', $L['descrizione'], [
                'value'       => $editMat['descrizione'] ?? '',
                'placeholder' => $L['mat_desc_placeholder'],
                'rows'        => 2,
                'max'         => 500,
            ]);
            ?>

            <?php if ($isEdit): ?>
                <?php formCheckbox('attivo', $L['mat_attivo_label'], (bool)$editMat['attivo']); ?>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <?= htmlspecialchars($isEdit ? $L['mat_btn_salva'] : $L['mat_btn_crea']) ?>
                </button>
                <?php if ($isEdit): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="btn btn-secondary"><?= htmlspecialchars($L['annulla']) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Filtro -->
<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
            <div class="form-group" style="margin-bottom:0">
                <label><?= htmlspecialchars($L['mat_filtra_lab']) ?></label>
                <select name="laboratorio" class="form-control">
                    <option value=""><?= htmlspecialchars($L['tutti']) ?></option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab == $lab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:22px">
                <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars($L['filtra']) ?></button>
                <a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="btn btn-secondary btn-sm"><?= htmlspecialchars($L['reset']) ?></a>
            </div>
        </form>
    </div>
</div>

<!-- Lista -->
<div class="card">
    <div class="card-header">
        <h3>📦 <?= htmlspecialchars($L['mat_titolo_gest']) ?> (<?= count($materiali) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state"><div class="empty-icon">📦</div><h4><?= htmlspecialchars($L['mat_nessuno']) ?></h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($L['nome']) ?></th>
                            <th><?= htmlspecialchars($L['mat_lab']) ?></th>
                            <th><?= htmlspecialchars($L['mat_unita_col']) ?></th>
                            <th><?= htmlspecialchars($L['mat_disponibile']) ?></th>
                            <th><?= htmlspecialchars($L['mat_soglia_col']) ?></th>
                            <th><?= htmlspecialchars($L['stato']) ?></th>
                            <th><?= htmlspecialchars($L['azioni']) ?></th>
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
                            <td><?= htmlspecialchars($m['laboratorio']) ?></td>
                            <td><?= htmlspecialchars($m['unita_misura'] ?? '-') ?></td>
                            <td><strong><?= $m['quantita_disponibile'] ?? '-' ?></strong></td>
                            <td><?= $m['soglia_minima'] ?? '-' ?></td>
                            <td>
                                <?php
                                if (!$m['attivo']): ?>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($L['mat_stato_disattivato']) ?></span>
                                <?php elseif ($m['quantita_disponibile'] !== null && $m['quantita_disponibile'] <= 0): ?>
                                    <span class="badge badge-danger"><?= htmlspecialchars($L['mat_stato_esaurito']) ?></span>
                                <?php elseif ($m['quantita_disponibile'] !== null && $m['soglia_minima'] !== null && $m['quantita_disponibile'] <= $m['soglia_minima']): ?>
                                    <span class="badge badge-warning"><?= htmlspecialchars($L['mat_stato_esaurimento']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= htmlspecialchars($L['mat_stato_ok']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $m['id'] ?><?= $filtroLab ? '&laboratorio='.$filtroLab : '' ?>" class="btn btn-primary btn-sm">✏ <?= htmlspecialchars($L['modifica']) ?></a>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm(<?= json_encode(sprintf($L['confirm_elimina_materiale'], $m['nome'])) ?>)">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑 <?= htmlspecialchars($L['elimina']) ?></button>
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
            formShowErr(nome, 'err_nome', <?= json_encode($L['mat_err_nome']) ?>); valid = false;
        } else if (nome) formClearErr(nome, 'err_nome');

        const lab = document.getElementById('id_laboratorio');
        if (lab && !lab.value) {
            formShowErr(lab, 'err_id_laboratorio', <?= json_encode($L['mat_err_lab']) ?>); valid = false;
        } else if (lab) formClearErr(lab, 'err_id_laboratorio');

        const qEl = document.getElementById('quantita_disponibile');
        if (qEl && qEl.value !== '' && parseFloat(qEl.value) < 0) {
            formShowErr(qEl, 'err_quantita_disponibile', <?= json_encode($L['mat_err_quantita_neg']) ?>); valid = false;
        } else if (qEl) formClearErr(qEl, 'err_quantita_disponibile');

        // soglia vs quantità già gestita dal helper JS live

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
