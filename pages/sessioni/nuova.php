<?php
$pageTitle = 'Nuova Sessione';
require_once __DIR__ . '/../../includes/header.php';

$conn   = getConnection();
$errors = [];

$resLabs   = mysqli_query($conn, "SELECT id, nome, aula FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs      = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$resClassi = mysqli_query($conn, "SELECT id, nome, anno_scolastico FROM classi WHERE attivo = 1 ORDER BY nome");
$classi    = [];
while ($row = mysqli_fetch_assoc($resClassi)) $classi[] = $row;

$resDocenti = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo = 1 ORDER BY cognome, nome");
$docenti    = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idLab              = intval($_POST['id_laboratorio'] ?? 0);
    $idClasse           = intval($_POST['id_classe'] ?? 0);
    $data               = $_POST['data'] ?? '';
    $oraIngresso        = $_POST['ora_ingresso'] ?? '';
    $oraUscita          = $_POST['ora_uscita'] ?? '';
    $attivita           = trim($_POST['attivita_svolta'] ?? '');
    $note               = trim($_POST['note'] ?? '');
    $docenteTitolare    = intval($_POST['docente_titolare'] ?? 0);
    $docenteCompresenza = intval($_POST['docente_compresenza'] ?? 0);

    // Validazioni server-side
    if (!$idLab)       $errors[] = 'Seleziona un laboratorio.';
    if (!$idClasse)    $errors[] = 'Seleziona una classe.';
    if (!$data)        $errors[] = 'Inserisci la data.';
    if ($data && $data > date('Y-m-d')) $errors[] = 'La data non può essere futura.';
    if (!$oraIngresso) $errors[] = "Inserisci l'ora di ingresso.";
    if ($oraUscita && $oraUscita <= $oraIngresso) $errors[] = "L'ora di uscita deve essere successiva all'ora di ingresso.";
    if (!$docenteTitolare) $errors[] = 'Seleziona il docente titolare.';
    if ($docenteCompresenza && $docenteCompresenza === $docenteTitolare)
        $errors[] = 'Il docente in compresenza deve essere diverso dal titolare.';
    if (strlen($attivita) > 1000) $errors[] = 'Attività svolta: max 1000 caratteri.';
    if (strlen($note) > 500)      $errors[] = 'Note: max 500 caratteri.';

    if (empty($errors)) {
        $data_e      = mysqli_real_escape_string($conn, $data);
        $oraI_e      = mysqli_real_escape_string($conn, $oraIngresso);
        $oraU_SQL    = $oraUscita  ? "'" . mysqli_real_escape_string($conn, $oraUscita) . "'" : 'NULL';
        $att_SQL     = $attivita   ? "'" . mysqli_real_escape_string($conn, $attivita)  . "'" : 'NULL';
        $note_SQL    = $note       ? "'" . mysqli_real_escape_string($conn, $note)      . "'" : 'NULL';

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO sessioni_laboratorio (id_laboratorio, id_classe, data, ora_ingresso, ora_uscita, attivita_svolta, note) VALUES ($idLab, $idClasse, '$data_e', '$oraI_e', $oraU_SQL, $att_SQL, $note_SQL)");
            $sessioneId = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES ($sessioneId, $docenteTitolare, 'titolare')");
            if ($docenteCompresenza) {
                mysqli_query($conn, "INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES ($sessioneId, $docenteCompresenza, 'compresenza')");
            }
            mysqli_commit($conn);
            header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $sessioneId . '&success=Sessione creata con successo!');
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = 'Errore database: ' . $e->getMessage();
        }
    }
}

$currentUserId = getCurrentUserId();
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>&#10060; <?= htmlspecialchars($err) ?><br><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>&#10133; Registra nuova sessione di laboratorio</h3></div>
    <div class="card-body">
        <form method="POST" id="formNuovaSessione" novalidate>

            <div class="form-row">
                <div class="form-group">
                    <label for="id_laboratorio">Laboratorio *</label>
                    <?php if (empty($labs)): ?>
                        <div class="alert alert-warning" style="margin:0">&#9888; Nessun laboratorio attivo disponibile. <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php">Creane uno</a>.</div>
                    <?php else: ?>
                    <select id="id_laboratorio" name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona laboratorio --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id'] == ($_POST['id_laboratorio'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lab['nome'] . ' (' . $lab['aula'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_laboratorio"></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="id_classe">Classe *</label>
                    <?php if (empty($classi)): ?>
                        <div class="alert alert-warning" style="margin:0">&#9888; Nessuna classe attiva. <a href="<?= BASE_PATH ?>/pages/admin/classi.php">Creane una</a>.</div>
                    <?php else: ?>
                    <select id="id_classe" name="id_classe" class="form-control" required>
                        <option value="">-- Seleziona classe --</option>
                        <?php foreach ($classi as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= ($cl['id'] == ($_POST['id_classe'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cl['nome'] . ' - ' . $cl['anno_scolastico']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_classe"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="data">Data *</label>
                    <input type="date" id="data" name="data" class="form-control" required
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['data'] ?? date('Y-m-d')) ?>">
                    <div class="field-error" id="err_data"></div>
                </div>
                <div class="form-group">
                    <label for="ora_ingresso">Ora Ingresso *</label>
                    <input type="time" id="ora_ingresso" name="ora_ingresso" class="form-control" required
                           value="<?= htmlspecialchars($_POST['ora_ingresso'] ?? date('H:i')) ?>">
                    <div class="field-error" id="err_ora_ingresso"></div>
                </div>
                <div class="form-group">
                    <label for="ora_uscita">Ora Uscita</label>
                    <input type="time" id="ora_uscita" name="ora_uscita" class="form-control"
                           value="<?= htmlspecialchars($_POST['ora_uscita'] ?? '') ?>">
                    <div class="form-text">Lascia vuoto se la sessione è in corso.</div>
                    <div class="field-error" id="err_ora_uscita"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="docente_titolare">Docente Titolare *</label>
                    <select id="docente_titolare" name="docente_titolare" class="form-control" required>
                        <option value="">-- Seleziona docente --</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == ($_POST['docente_titolare'] ?? $currentUserId)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_docente_titolare"></div>
                </div>
                <div class="form-group">
                    <label for="docente_compresenza">Docente Compresenza</label>
                    <select id="docente_compresenza" name="docente_compresenza" class="form-control">
                        <option value="">-- Nessuno --</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == ($_POST['docente_compresenza'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_docente_compresenza"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="attivita_svolta">Attività Svolta <span class="text-muted" style="font-weight:normal">(max 1000 car.)</span></label>
                <textarea id="attivita_svolta" name="attivita_svolta" class="form-control" rows="3"
                          maxlength="1000"
                          placeholder="Descrivi l'attività svolta..."><?= htmlspecialchars($_POST['attivita_svolta'] ?? '') ?></textarea>
                <div class="form-text char-counter" data-target="attivita_svolta" data-max="1000">0 / 1000</div>
            </div>

            <div class="form-group">
                <label for="note">Note <span class="text-muted" style="font-weight:normal">(max 500 car.)</span></label>
                <textarea id="note" name="note" class="form-control" rows="2"
                          maxlength="500"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                <div class="form-text char-counter" data-target="note" data-max="500">0 / 500</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success" id="btnSubmit">&#10004; Registra Sessione</button>
                <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">Annulla</a>
            </div>
        </form>
    </div>
</div>

<style>
.field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display:none; }
.form-control.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(220,38,38,0.12); }
.form-control.is-valid   { border-color: var(--success); }
</style>

<script>
(function() {
    const form = document.getElementById('formNuovaSessione');
    if (!form) return;

    // Char counters
    document.querySelectorAll('.char-counter').forEach(function(el) {
        const targetId = el.dataset.target;
        const max      = parseInt(el.dataset.max);
        const input    = document.getElementById(targetId);
        if (!input) return;
        function update() {
            const len = input.value.length;
            el.textContent = len + ' / ' + max;
            el.style.color = len > max * 0.9 ? 'var(--warning)' : '';
            if (len >= max) el.style.color = 'var(--danger)';
        }
        input.addEventListener('input', update);
        update();
    });

    // Sync docente compresenza: rimuovi dal tendina il titolare selezionato
    const selTitolare    = document.getElementById('docente_titolare');
    const selCompresenza = document.getElementById('docente_compresenza');
    if (selTitolare && selCompresenza) {
        function syncDocenti() {
            const val = selTitolare.value;
            Array.from(selCompresenza.options).forEach(function(opt) {
                opt.disabled = (opt.value !== '' && opt.value === val);
            });
            if (selCompresenza.value === val) selCompresenza.value = '';
        }
        selTitolare.addEventListener('change', syncDocenti);
        syncDocenti();
    }

    // Validazione ora uscita > ingresso
    const oraI = document.getElementById('ora_ingresso');
    const oraU = document.getElementById('ora_uscita');
    if (oraI && oraU) {
        function checkOre() {
            const errEl = document.getElementById('err_ora_uscita');
            if (oraU.value && oraU.value <= oraI.value) {
                showError(oraU, errEl, "L'ora di uscita deve essere successiva all'ingresso.");
            } else {
                clearError(oraU, errEl);
            }
        }
        oraI.addEventListener('change', checkOre);
        oraU.addEventListener('change', checkOre);
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

    // Validazione submit
    form.addEventListener('submit', function(e) {
        let valid = true;

        // Laboratorio
        const lab = document.getElementById('id_laboratorio');
        if (lab) { if (!lab.value) { showError(lab, document.getElementById('err_laboratorio'), 'Seleziona un laboratorio.'); valid = false; } else clearError(lab, document.getElementById('err_laboratorio')); }

        // Classe
        const cls = document.getElementById('id_classe');
        if (cls) { if (!cls.value) { showError(cls, document.getElementById('err_classe'), 'Seleziona una classe.'); valid = false; } else clearError(cls, document.getElementById('err_classe')); }

        // Data
        const dataEl = document.getElementById('data');
        if (dataEl) {
            if (!dataEl.value) { showError(dataEl, document.getElementById('err_data'), 'Inserisci la data.'); valid = false; }
            else if (dataEl.value > '<?= date('Y-m-d') ?>') { showError(dataEl, document.getElementById('err_data'), 'La data non può essere futura.'); valid = false; }
            else clearError(dataEl, document.getElementById('err_data'));
        }

        // Ora ingresso
        if (oraI && !oraI.value) { showError(oraI, document.getElementById('err_ora_ingresso'), "Inserisci l'ora di ingresso."); valid = false; }
        else if (oraI) clearError(oraI, document.getElementById('err_ora_ingresso'));

        // Ora uscita
        if (oraU && oraU.value && oraI && oraU.value <= oraI.value) {
            showError(oraU, document.getElementById('err_ora_uscita'), "L'ora di uscita deve essere successiva all'ingresso.");
            valid = false;
        }

        // Docente titolare
        const dt = document.getElementById('docente_titolare');
        if (dt) { if (!dt.value) { showError(dt, document.getElementById('err_docente_titolare'), 'Seleziona il docente titolare.'); valid = false; } else clearError(dt, document.getElementById('err_docente_titolare')); }

        // Docente compresenza != titolare
        const dc = document.getElementById('docente_compresenza');
        if (dc && dt && dc.value && dc.value === dt.value) {
            showError(dc, document.getElementById('err_docente_compresenza'), 'Il docente in compresenza deve essere diverso dal titolare.');
            valid = false;
        } else if (dc) clearError(dc, document.getElementById('err_docente_compresenza'));

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>