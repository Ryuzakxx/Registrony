<?php
/* ================================================================
   Logica POST prima di qualsiasi output HTML
   ================================================================ */
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lang/it.php';

$conn   = getConnection();
$L      = lang();
$errors = [];

$resLabs    = mysqli_query($conn, "SELECT id, nome, aula FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs       = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$resClassi  = mysqli_query($conn, "SELECT id, nome, anno_scolastico FROM classi WHERE attivo = 1 ORDER BY nome");
$classi     = [];
while ($row = mysqli_fetch_assoc($resClassi)) $classi[] = $row;

$resDocenti = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo = 1 ORDER BY cognome, nome");
$docenti    = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idLab              = intval($_POST['id_laboratorio']      ?? 0);
    $idClasse           = intval($_POST['id_classe']           ?? 0);
    $data               = $_POST['data']                       ?? '';
    $oraIngresso        = $_POST['ora_ingresso']               ?? '';
    $oraUscita          = $_POST['ora_uscita']                 ?? '';
    $attivita           = trim($_POST['attivita_svolta']       ?? '');
    $note               = trim($_POST['note']                  ?? '');
    $docenteTitolare    = intval($_POST['docente_titolare']    ?? 0);
    $docenteCompresenza = intval($_POST['docente_compresenza'] ?? 0);
    $materialiUsati     = array_map('intval', $_POST['materiali_usati'] ?? []);

    if (!$idLab)       $errors[] = $L['sess_err_lab'];
    if (!$idClasse)    $errors[] = $L['sess_err_classe'];
    if (!$data)        $errors[] = $L['sess_err_data'];
    if ($data && $data > date('Y-m-d')) $errors[] = $L['sess_err_data_futura'];
    if (!$oraIngresso) $errors[] = $L['sess_err_ora_ingresso'];
    if ($oraUscita && $oraUscita <= $oraIngresso) $errors[] = $L['sess_err_ora_uscita'];
    if (!$docenteTitolare) $errors[] = $L['sess_err_docente_tit'];
    if ($docenteCompresenza && $docenteCompresenza === $docenteTitolare) $errors[] = $L['sess_err_docente_comp'];
    if (strlen($attivita) > 1000) $errors[] = $L['sess_err_attivita_lunga'];
    if (strlen($note)     > 500)  $errors[] = $L['sess_err_note_lunghe'];

    if (empty($errors)) {
        $data_e   = mysqli_real_escape_string($conn, $data);
        $oraI_e   = mysqli_real_escape_string($conn, $oraIngresso);
        $oraU_SQL = $oraUscita ? "'" . mysqli_real_escape_string($conn, $oraUscita) . "'" : 'NULL';
        $att_SQL  = $attivita  ? "'" . mysqli_real_escape_string($conn, $attivita)  . "'" : 'NULL';
        $note_SQL = $note      ? "'" . mysqli_real_escape_string($conn, $note)      . "'" : 'NULL';

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO sessioni_laboratorio (id_laboratorio, id_classe, data, ora_ingresso, ora_uscita, attivita_svolta, note) VALUES ($idLab, $idClasse, '$data_e', '$oraI_e', $oraU_SQL, $att_SQL, $note_SQL)");
            $sessId = mysqli_insert_id($conn);

            mysqli_query($conn, "INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES ($sessId, $docenteTitolare, 'titolare')");
            if ($docenteCompresenza) {
                mysqli_query($conn, "INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES ($sessId, $docenteCompresenza, 'compresenza')");
            }

            // Salva i materiali usati nella sessione
            foreach ($materialiUsati as $idMat) {
                if ($idMat > 0) {
                    mysqli_query($conn, "INSERT INTO sessioni_materiali (id_sessione, id_materiale) VALUES ($sessId, $idMat)");
                }
            }

            mysqli_commit($conn);
            header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $sessId . '&success=' . urlencode($L['sess_ok_creata']));
            exit;
        } catch (Exception $ex) {
            mysqli_rollback($conn);
            $errors[] = 'Errore database: ' . $ex->getMessage();
        }
    }
}

$currentUserId = getCurrentUserId();

/* ================================================================
   Include header.php SOLO dopo tutti i possibili redirect
   ================================================================ */
$pageTitle = 'Nuova Sessione';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';

/* Carica materiali disponibili per il laboratorio selezionato */
$materialiDisponibili = [];
$idLabSel = intval($_POST['id_laboratorio'] ?? 0);
if ($idLabSel) {
    $resMat = mysqli_query($conn, "SELECT id, nome, unita_misura, quantita_disponibile FROM materiali WHERE id_laboratorio = $idLabSel AND attivo = 1 ORDER BY nome");
    while ($row = mysqli_fetch_assoc($resMat)) $materialiDisponibili[] = $row;
} else {
    // Carica tutti i materiali attivi se non c'e' ancora filtro lab
    $resMat = mysqli_query($conn, "SELECT m.id, m.nome, m.unita_misura, m.quantita_disponibile, l.nome AS laboratorio FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id WHERE m.attivo = 1 ORDER BY l.nome, m.nome");
    while ($row = mysqli_fetch_assoc($resMat)) $materialiDisponibili[] = $row;
}
$materialiSelezionati = array_map('intval', $_POST['materiali_usati'] ?? []);

/* Mappe select */
$labsMap = ['' => $L['sess_seleziona_lab']];
foreach ($labs as $lab) {
    $labsMap[$lab['id']] = $lab['nome'] . ' (' . $lab['aula'] . ')';
}
$classiMap = ['' => $L['sess_seleziona_classe']];
foreach ($classi as $cl) {
    $classiMap[$cl['id']] = $cl['nome'] . ' — ' . $cl['anno_scolastico'];
}
$docentiMap = ['' => $L['sess_seleziona_docente']];
foreach ($docenti as $doc) {
    $docentiMap[$doc['id']] = $doc['cognome'] . ' ' . $doc['nome'];
}
$docentiCompMap = ['' => '— ' . $L['nessuno']];
foreach ($docenti as $doc) {
    $docentiCompMap[$doc['id']] = $doc['cognome'] . ' ' . $doc['nome'];
}
?>

<?php formFieldStyles(); ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars($L['sess_titolo_nuova']) ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" id="formNuovaSessione" novalidate>

            <div class="form-row">
                <?php if (empty($labs)): ?>
                    <div class="alert alert-warning">
                        <?= htmlspecialchars($L['sess_nessun_lab']) ?>
                        <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php">Creane uno</a>.
                    </div>
                <?php else: ?>
                    <?php formSelect('id_laboratorio', $L['sess_laboratorio'], $labsMap, [
                        'selected' => (string)($_POST['id_laboratorio'] ?? ''),
                        'required' => true,
                        'extra'    => 'id="selLab"',
                    ]); ?>
                <?php endif; ?>

                <?php if (empty($classi)): ?>
                    <div class="alert alert-warning">
                        <?= htmlspecialchars($L['sess_nessuna_classe']) ?>
                        <a href="<?= BASE_PATH ?>/pages/admin/classi.php">Creane una</a>.
                    </div>
                <?php else: ?>
                    <?php formSelect('id_classe', $L['sess_classe'], $classiMap, [
                        'selected' => (string)($_POST['id_classe'] ?? ''),
                        'required' => true,
                    ]); ?>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <?php
                formField('data', $L['sess_data'], [
                    'type'     => 'date',
                    'value'    => $_POST['data'] ?? date('Y-m-d'),
                    'required' => true,
                    'extra'    => 'max="' . date('Y-m-d') . '"',
                ]);
                formOrario('ora_ingresso', $L['sess_ora_ingresso'], [
                    'value'    => $_POST['ora_ingresso'] ?? date('H:i'),
                    'required' => true,
                ]);
                formOrario('ora_uscita', $L['sess_ora_uscita'], [
                    'value' => $_POST['ora_uscita'] ?? '',
                    'hint'  => $L['sess_hint_ora_uscita'],
                ]);
                ?>
            </div>

            <div class="form-row">
                <?php
                formSelect('docente_titolare', $L['sess_docente_titolare'], $docentiMap, [
                    'selected' => (string)($_POST['docente_titolare'] ?? $currentUserId),
                    'required' => true,
                ]);
                formSelect('docente_compresenza', $L['sess_docente_compresenza'], $docentiCompMap, [
                    'selected' => (string)($_POST['docente_compresenza'] ?? ''),
                    'hint'     => $L['nessuno'] . ' = nessuna compresenza',
                ]);
                ?>
            </div>

            <?php
            formTextarea('attivita_svolta', $L['sess_attivita'], [
                'value'       => $_POST['attivita_svolta'] ?? '',
                'placeholder' => $L['sess_attivita_placeholder'],
                'rows'        => 3,
                'max'         => 1000,
                'counter'     => true,
                'hint'        => $L['sess_hint_attivita'],
            ]);

            formTextarea('note', $L['note'], [
                'value'       => $_POST['note'] ?? '',
                'placeholder' => $L['sess_note_placeholder'],
                'rows'        => 2,
                'max'         => 500,
                'counter'     => true,
                'hint'        => $L['sess_hint_note'],
            ]);
            ?>

            <!-- ================================================
                 MATERIALI USATI DURANTE LA SESSIONE
                 ================================================ -->
            <div class="form-group" style="margin-top: 8px;">
                <label style="font-weight:600; font-size:12.5px; margin-bottom:8px; display:block;">
                    Materiali utilizzati
                    <span style="font-weight:400; color:var(--text-light); margin-left:4px;">(opzionale — seleziona tutti i materiali usati)</span>
                </label>

                <?php if (empty($materialiDisponibili)): ?>
                    <p class="text-muted" style="font-size:13px;">Nessun materiale disponibile. Aggiungili prima dalla sezione Gestione Materiali.</p>
                <?php else: ?>
                    <div id="materialiGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap:8px;">
                        <?php foreach ($materialiDisponibili as $mat): ?>
                            <?php $checked = in_array($mat['id'], $materialiSelezionati); ?>
                            <label class="mat-check-item <?= $checked ? 'selected' : '' ?>" style="
                                display:flex; align-items:center; gap:10px;
                                padding: 10px 14px;
                                border: 1px solid var(--border);
                                border-radius: 7px;
                                cursor: pointer;
                                background: var(--bg-white);
                                transition: all 0.15s ease;
                                user-select: none;
                            ">
                                <input type="checkbox"
                                       name="materiali_usati[]"
                                       value="<?= $mat['id'] ?>"
                                       <?= $checked ? 'checked' : '' ?>
                                       style="width:16px; height:16px; accent-color: var(--accent); flex-shrink:0;">
                                <div>
                                    <div style="font-weight:600; font-size:13px; color:var(--text);">
                                        <?= htmlspecialchars($mat['nome']) ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-light);">
                                        <?php if (isset($mat['laboratorio'])): ?>
                                            <?= htmlspecialchars($mat['laboratorio']) ?> &mdash;
                                        <?php endif; ?>
                                        Disponibili: <strong><?= $mat['quantita_disponibile'] ?? 'n.d.' ?></strong>
                                        <?php if ($mat['unita_misura']): ?>
                                            <?= htmlspecialchars($mat['unita_misura']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="form-text" style="margin-top:6px;">I materiali selezionati vengono registrati come utilizzati in questa sessione.</p>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2" style="margin-top:16px;">
                <button type="submit" class="btn btn-success" id="btnSubmit">
                    <?= htmlspecialchars($L['sess_btn_registra']) ?>
                </button>
                <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">
                    <?= htmlspecialchars($L['annulla']) ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php formFieldScripts(); ?>

<style>
.mat-check-item:hover { border-color: var(--accent); background: #f0f7ff; }
.mat-check-item.selected { border-color: var(--accent); background: #eff6ff; }
</style>

<script>
/* Evidenzia le card selezionate */
document.querySelectorAll('.mat-check-item input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        this.closest('.mat-check-item').classList.toggle('selected', this.checked);
    });
});

/* Validazione form */
(function () {
    const form   = document.getElementById('formNuovaSessione');
    const today  = '<?= date('Y-m-d') ?>';
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        const checks = [
            { id: 'id_laboratorio',   msg: <?= json_encode($L['sess_err_lab']) ?>,          check: v => !!v },
            { id: 'id_classe',        msg: <?= json_encode($L['sess_err_classe']) ?>,        check: v => !!v },
            { id: 'ora_ingresso',     msg: <?= json_encode($L['sess_err_ora_ingresso']) ?>,  check: v => !!v },
            { id: 'docente_titolare', msg: <?= json_encode($L['sess_err_docente_tit']) ?>,   check: v => !!v },
        ];
        checks.forEach(function (c) {
            const el = document.getElementById(c.id);
            if (!el) return;
            if (!c.check(el.value.trim())) { formShowErr(el, 'err_' + c.id, c.msg); valid = false; }
            else formClearErr(el, 'err_' + c.id);
        });
        const dataEl = document.getElementById('data');
        if (dataEl) {
            if (!dataEl.value) {
                formShowErr(dataEl, 'err_data', <?= json_encode($L['sess_err_data']) ?>); valid = false;
            } else if (dataEl.value > today) {
                formShowErr(dataEl, 'err_data', <?= json_encode($L['sess_err_data_futura']) ?>); valid = false;
            } else {
                formClearErr(dataEl, 'err_data');
            }
        }
        const oraI = document.getElementById('ora_ingresso');
        const oraU = document.getElementById('ora_uscita');
        if (oraI && oraU && oraU.value && oraU.value <= oraI.value) {
            formShowErr(oraU, 'err_ora_uscita', <?= json_encode($L['sess_err_ora_uscita']) ?>);
            valid = false;
        }
        const dt = document.getElementById('docente_titolare');
        const dc = document.getElementById('docente_compresenza');
        if (dt && dc && dc.value && dc.value === dt.value) {
            formShowErr(dc, 'err_docente_compresenza', <?= json_encode($L['sess_err_docente_comp']) ?>);
            valid = false;
        }
        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
