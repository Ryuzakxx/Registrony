<?php
$pageTitle = 'Gestione Classi';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';
requireAdmin();

$conn = getConnection();
$L    = lang();

$indirizziPredefiniti = [
    'Informatica e Telecomunicazioni',
    'Elettronica ed Elettrotecnica',
    'Meccanica, Meccatronica ed Energia',
    'Chimica, Materiali e Biotecnologie',
    'Amministrazione Finanza e Marketing',
    'Liceo Scientifico',
    'Liceo Classico',
    'Liceo Linguistico',
    'Altro',
];

$annoCorrente = date('n') >= 9 ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y');

/* ================================================================
   ACTIONS
   ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome   = strtoupper(trim($_POST['nome']            ?? ''));
        $anno   = trim($_POST['anno_scolastico']             ?? '');
        $ind    = trim($_POST['indirizzo']                   ?? '');
        $attivo = isset($_POST['attivo']) ? 1 : 0;
        $errors = [];

        if (!$nome) $errors[] = $L['classi_err_nome'];
        if (!preg_match('/^[0-9][A-Z]{1,3}$/', $nome)) $errors[] = $L['classi_err_nome_formato'];
        if (!$anno) $errors[] = $L['classi_err_anno'];
        if ($anno && !preg_match('/^\d{4}\/\d{4}$/', $anno)) $errors[] = $L['classi_err_anno_formato'];
        elseif ($anno) {
            [$y1, $y2] = explode('/', $anno);
            if ((int)$y2 !== (int)$y1 + 1) $errors[] = $L['classi_err_anno_seq'];
        }

        if (empty($errors)) {
            $n_e   = mysqli_real_escape_string($conn, $nome);
            $a_e   = mysqli_real_escape_string($conn, $anno);
            $i_SQL = $ind ? "'" . mysqli_real_escape_string($conn, $ind) . "'" : 'NULL';

            if ($action === 'crea') {
                $ok = mysqli_query($conn, "INSERT INTO classi (nome, anno_scolastico, indirizzo, attivo) VALUES ('$n_e','$a_e',$i_SQL,$attivo)");
                if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=' . urlencode($L['classi_ok_creata']));
                else     header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error='   . urlencode($L['classi_err_gia_esistente']));
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn, "UPDATE classi SET nome='$n_e', anno_scolastico='$a_e', indirizzo=$i_SQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=' . urlencode($L['classi_ok_aggiornata']));
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM classi WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=' . urlencode($L['classi_ok_eliminata']));
        else     header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error='   . urlencode($L['classi_err_in_uso']));
        exit;
    }
}

/* ================================================================
   READ
   ================================================================ */
$result = mysqli_query($conn, "SELECT * FROM classi ORDER BY anno_scolastico DESC, nome");
$classi = [];
while ($row = mysqli_fetch_assoc($result)) $classi[] = $row;

$editClasse = null;
if (isset($_GET['edit'])) {
    $editId     = intval($_GET['edit']);
    $res        = mysqli_query($conn, "SELECT * FROM classi WHERE id = $editId");
    $editClasse = mysqli_fetch_assoc($res);
}

$isEdit = $editClasse !== null;

/* Opzioni anno scolastico */
$annoBase  = (int)date('Y');
$anniMap   = ['' => $L['classi_anno_vuoto']];
for ($y = $annoBase - 2; $y <= $annoBase + 1; $y++) {
    $opt = $y . '/' . ($y + 1);
    $anniMap[$opt] = $opt;
}

/* Opzioni indirizzo */
$indMap = ['' => $L['classi_indirizzo_vuoto']];
foreach ($indirizziPredefiniti as $ind) {
    $indMap[$ind] = $ind;
}
// Se indirizzo attuale non è in lista, aggiungilo
$indAttuale = $editClasse['indirizzo'] ?? '';
if ($indAttuale && !in_array($indAttuale, $indirizziPredefiniti)) {
    $indMap[$indAttuale] = $indAttuale . ' (personalizzato)';
}
?>

<?php formFieldStyles(); ?>

<!-- ============================================================
     FORM CREA / MODIFICA
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars($isEdit ? $L['classi_form_titolo_mod'] : $L['classi_form_titolo_crea']) ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" id="formClasse" novalidate>
            <input type="hidden" name="action" value="<?= $isEdit ? 'modifica' : 'crea' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$editClasse['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <?php
                formField('nome', $L['classi_nome'], [
                    'value'       => $editClasse['nome'] ?? '',
                    'placeholder' => $L['classi_nome_placeholder'],
                    'hint'        => $L['classi_nome_hint'],
                    'required'    => true,
                    'max'         => 10,
                    'pattern'     => '[0-9][A-Za-z]{1,3}',
                    'extra'       => 'style="text-transform:uppercase"',
                ]);

                formSelect('anno_scolastico', $L['classi_anno'], $anniMap, [
                    'selected' => $editClasse['anno_scolastico'] ?? $annoCorrente,
                    'required' => true,
                ]);

                formSelect('indirizzo', $L['classi_indirizzo'], $indMap, [
                    'selected' => $indAttuale,
                ]);
                ?>
            </div>

            <?php if ($isEdit): ?>
                <?php formCheckbox('attivo', $L['classi_attivo_label'], (bool)$editClasse['attivo']); ?>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <?= htmlspecialchars($isEdit ? $L['classi_btn_salva'] : $L['classi_btn_crea']) ?>
                </button>
                <?php if ($isEdit): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/classi.php" class="btn btn-secondary"><?= htmlspecialchars($L['annulla']) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     LISTA CLASSI
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <h3>🏫 <?= htmlspecialchars($L['classi_titolo']) ?> (<?= count($classi) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($classi)): ?>
            <div class="empty-state">
                <div class="empty-icon">🏫</div>
                <h4><?= htmlspecialchars($L['classi_nessuna']) ?></h4>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($L['classi_col_nome']) ?></th>
                            <th><?= htmlspecialchars($L['classi_col_anno']) ?></th>
                            <th><?= htmlspecialchars($L['classi_col_indirizzo']) ?></th>
                            <th><?= htmlspecialchars($L['classi_col_stato']) ?></th>
                            <th><?= htmlspecialchars($L['azioni']) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classi as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($c['anno_scolastico']) ?></td>
                            <td><?= htmlspecialchars($c['indirizzo'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $c['attivo'] ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= $c['attivo'] ? htmlspecialchars($L['classi_badge_attiva']) : htmlspecialchars($L['classi_badge_disattivata']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm">✏ <?= htmlspecialchars($L['modifica']) ?></a>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm(<?= json_encode(sprintf($L['confirm_elimina_classe'], $c['nome'])) ?>)">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
/* Validazione submit classi */
(function () {
    const form = document.getElementById('formClasse');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;

        const nome = document.getElementById('nome');
        if (nome) {
            const re = /^[0-9][A-Z]{1,3}$/;
            if (!nome.value.trim()) {
                formShowErr(nome, 'err_nome', <?= json_encode($L['classi_err_nome']) ?>); valid = false;
            } else if (!re.test(nome.value.toUpperCase())) {
                formShowErr(nome, 'err_nome', <?= json_encode($L['classi_err_nome_formato']) ?>); valid = false;
            } else {
                formClearErr(nome, 'err_nome');
            }
        }

        const anno = document.getElementById('anno_scolastico');
        if (anno && !anno.value) {
            formShowErr(anno, 'err_anno_scolastico', <?= json_encode($L['classi_err_anno']) ?>); valid = false;
        } else if (anno) {
            formClearErr(anno, 'err_anno_scolastico');
        }

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
