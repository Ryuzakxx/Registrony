<?php
/* ================================================================
   POST logic before any HTML output
   ================================================================ */
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getConnection();
$L    = lang();
$errors = [];

/* ----------------------------------------------------------------
   REGOLE DI ACCESSO
   - tecnico  → non può creare sessioni
   - docente  → deve aver selezionato un laboratorio;
                può creare sessioni SOLO per quel laboratorio
   - admin    → nessuna restrizione
   ---------------------------------------------------------------- */
if (isTecnico()) {
    // I tecnici gestiscono materiali e segnalazioni, non aprono sessioni
    header('Location: ' . BASE_PATH . '/pages/sessioni/index.php?error=' .
        urlencode($L['sess_err_tecnico_no_create'] ?? 'I tecnici non possono creare nuove sessioni'));
    exit;
}

$selectedLabId   = null;   // id lab selezionato (docente)
$selectedLabInfo = null;   // ['nome', 'aula'] per la UI

if (isDocente()) {
    $selectedLabId = getSelectedLabId();
    if (!$selectedLabId) {
        // Il docente non ha ancora scelto un lab → forzalo a scegliere
        header('Location: ' . BASE_PATH . '/pages/seleziona_laboratorio.php');
        exit;
    }
    // Carica info lab per la visualizzazione bloccata
    $resLab = mysqli_query($conn, "SELECT nome, aula FROM laboratori WHERE id = $selectedLabId AND attivo = 1 LIMIT 1");
    $selectedLabInfo = mysqli_fetch_assoc($resLab);
    if (!$selectedLabInfo) {
        // Lab non più valido → forza ri-selezione
        unset($_SESSION['selected_lab_id']);
        header('Location: ' . BASE_PATH . '/pages/seleziona_laboratorio.php');
        exit;
    }
}

/* ----------------------------------------------------------------
   Carica dati per i <select> del form
   ---------------------------------------------------------------- */
// Labs: admin vede tutti, docente vede solo il suo selezionato
if (isAdmin()) {
    $resLabs = mysqli_query($conn, "SELECT id, nome, aula FROM laboratori WHERE attivo = 1 ORDER BY nome");
    $labs = [];
    while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;
} else {
    // docente: array con solo il lab selezionato (usato per i materiali JS)
    $labs = [['id' => $selectedLabId, 'nome' => $selectedLabInfo['nome'], 'aula' => $selectedLabInfo['aula']]];
}

$resClassi = mysqli_query($conn, "SELECT id, nome, anno_scolastico FROM classi WHERE attivo = 1 ORDER BY nome");
$classi    = [];
while ($row = mysqli_fetch_assoc($resClassi)) $classi[] = $row;

$resDocenti = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo = 1 ORDER BY cognome, nome");
$docenti    = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

$today   = date('Y-m-d');
$nowTime = date('H:i');

/* ----------------------------------------------------------------
   Gestione POST
   ---------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sicurezza: per i docenti l'id_laboratorio è sempre quello della sessione,
    // mai il valore inviato dal form (prevenzione manomissioni)
    if (isDocente()) {
        $idLab = $selectedLabId;
    } else {
        $idLab = intval($_POST['id_laboratorio'] ?? 0);
    }

    $idClasse           = intval($_POST['id_classe']           ?? 0);
    $data               = $_POST['data']                       ?? '';
    $oraIngresso        = $_POST['ora_ingresso']               ?? '';
    $oraUscita          = $_POST['ora_uscita']                 ?? '';
    $attivita           = trim($_POST['attivita_svolta']       ?? '');
    $note               = trim($_POST['note']                  ?? '');
    $docenteTitolare    = intval($_POST['docente_titolare']    ?? 0);
    $docenteCompresenza = intval($_POST['docente_compresenza'] ?? 0);

    // --- Validation ---
    if (!$idLab)    $errors[] = $L['sess_err_lab'];
    if (!$idClasse) $errors[] = $L['sess_err_classe'];

    if (!$data) {
        $errors[] = $L['sess_err_data'];
    } elseif ($data > $today) {
        $errors[] = $L['sess_err_data_futura'];
    }

    if (!$oraIngresso) {
        $errors[] = $L['sess_err_ora_ingresso'];
    } elseif ($data === $today && $oraIngresso > $nowTime) {
        $errors[] = $L['sess_err_ora_ingresso_futura'] ?? ($L['sess_err_ora_ingresso'] . ' (future)');
    }

    if ($oraUscita) {
        if ($oraUscita <= $oraIngresso) {
            $errors[] = $L['sess_err_ora_uscita'];
        } elseif ($data === $today && $oraUscita > $nowTime) {
            $errors[] = $L['sess_err_ora_uscita_futura'] ?? ($L['sess_err_ora_uscita'] . ' (future)');
        }
    }

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

            mysqli_commit($conn);
            header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $sessId . '&success=' . urlencode($L['sess_ok_creata']));
            exit;
        } catch (Exception $ex) {
            mysqli_rollback($conn);
            $errors[] = 'Database error: ' . $ex->getMessage();
        }
    }
}

$currentUserId = getCurrentUserId();

/* ================================================================
   Materiali per lab come JSON per il filtro JS dinamico
   ================================================================ */
$resMat = mysqli_query($conn, "
    SELECT m.id, m.nome, m.unita_misura, m.quantita_disponibile, m.id_laboratorio
    FROM materiali m
    WHERE m.attivo = 1
    ORDER BY m.nome
");
$materialiPerLab = [];
while ($row = mysqli_fetch_assoc($resMat)) {
    $materialiPerLab[$row['id_laboratorio']][] = $row;
}

/* ================================================================
   Include header SOLO dopo tutti i possibili redirect
   ================================================================ */
$pageTitle = $L['sess_titolo_nuova'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';

/* Select maps */
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
        <button class="alert-close" onclick="this.closest('.alert').remove()" aria-label="Chiudi">&times;</button>
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

                <?php if (isDocente()): ?>
                    <?php
                    /*
                     * Docente: il laboratorio è bloccato sul valore della sessione.
                     * Mostriamo un display visivo + campo hidden, con link per cambiare.
                     */
                    ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($L['sess_laboratorio'] ?? 'Laboratorio') ?></label>
                        <div style="display:flex; align-items:center; gap:12px; padding:9px 13px;
                                    border:1px solid var(--border); border-radius:6px;
                                    background:var(--bg); font-size:14px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent);flex-shrink:0;"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                            <strong><?= htmlspecialchars($selectedLabInfo['nome']) ?></strong>
                            <span style="color:var(--text-light);">(<?= htmlspecialchars($selectedLabInfo['aula']) ?>)</span>
                            <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php"
                               style="margin-left:auto; font-size:12px; color:var(--accent);"
                               title="Cambia laboratorio">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Cambia laboratorio
                            </a>
                        </div>
                        <input type="hidden" name="id_laboratorio" value="<?= intval($selectedLabId) ?>">
                    </div>

                <?php elseif (empty($labs)): ?>
                    <div class="alert alert-warning">
                        <?= htmlspecialchars($L['sess_nessun_lab']) ?>
                        <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php"><?= $L['crea'] ?></a>.
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
                        <a href="<?= BASE_PATH ?>/pages/admin/classi.php"><?= $L['crea'] ?></a>.
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
                    'value'    => $_POST['data'] ?? $today,
                    'required' => true,
                    'extra'    => 'max="' . $today . '" id="fldData"',
                ]);
                formOrario('ora_ingresso', $L['sess_ora_ingresso'], [
                    'value'    => $_POST['ora_ingresso'] ?? $nowTime,
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
                    'hint'     => $L['nessuno'] . ' = no co-presence',
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

            <div class="d-flex gap-2" style="margin-top:16px;">
                <button type="submit" class="btn btn-success" id="btnSubmit">
                    <?= htmlspecialchars($L['sess_btn_registra']) ?>
                </button>
                <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">
                    <?= htmlspecialchars($L['annulla']) ?>
                </a>
            </div>

            <p class="form-text" style="margin-top:8px;">
                <?= $L['sess_hint_ora_uscita'] ?>
            </p>
        </form>
    </div>
</div>

<?php formFieldScripts(); ?>

<script>
/* Client-side validation — mirrors PHP checks */
(function () {
    const form    = document.getElementById('formNuovaSessione');
    const today   = <?= json_encode($today) ?>;
    const nowTime = <?= json_encode($nowTime) ?>;
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;

        // Required selects (skip id_laboratorio per docente — è hidden)
        const checks = [
            { id: 'id_classe',        msg: <?= json_encode($L['sess_err_classe']) ?>,       check: v => !!v },
            { id: 'ora_ingresso',     msg: <?= json_encode($L['sess_err_ora_ingresso']) ?>, check: v => !!v },
            { id: 'docente_titolare', msg: <?= json_encode($L['sess_err_docente_tit']) ?>,  check: v => !!v },
        ];
        <?php if (isAdmin()): ?>
        checks.unshift({ id: 'id_laboratorio', msg: <?= json_encode($L['sess_err_lab']) ?>, check: v => !!v });
        <?php endif; ?>

        checks.forEach(function (c) {
            const el = document.getElementById(c.id);
            if (!el) return;
            if (!c.check(el.value.trim())) { formShowErr(el, 'err_' + c.id, c.msg); valid = false; }
            else formClearErr(el, 'err_' + c.id);
        });

        // Date: required, not in the future
        const dataEl = document.getElementById('fldData') || document.getElementById('data');
        let selectedDate = '';
        if (dataEl) {
            selectedDate = dataEl.value;
            if (!selectedDate) {
                formShowErr(dataEl, 'err_data', <?= json_encode($L['sess_err_data']) ?>); valid = false;
            } else if (selectedDate > today) {
                formShowErr(dataEl, 'err_data', <?= json_encode($L['sess_err_data_futura']) ?>); valid = false;
            } else {
                formClearErr(dataEl, 'err_data');
            }
        }

        // Entry time validation
        const oraI = document.getElementById('ora_ingresso');
        let oraIngressoValid = true;
        if (oraI) {
            if (!oraI.value) {
                // campo vuoto — già gestito dal check required sopra
                oraIngressoValid = false;
            } else if (selectedDate === today && oraI.value > nowTime) {
                formShowErr(oraI, 'err_ora_ingresso', <?= json_encode($L['sess_err_ora_ingresso_futura'] ?? $L['sess_err_ora_ingresso']) ?>);
                oraIngressoValid = false;
                valid = false;
            } else {
                formClearErr(oraI, 'err_ora_ingresso');
            }
        }

        // Exit time: controllata SOLO se ora_ingresso è valida
        const oraU = document.getElementById('ora_uscita');
        if (oraI && oraU && oraU.value && oraIngressoValid) {
            if (oraU.value <= oraI.value) {
                formShowErr(oraU, 'err_ora_uscita', <?= json_encode($L['sess_err_ora_uscita']) ?>);
                valid = false;
            } else if (selectedDate === today && oraU.value > nowTime) {
                formShowErr(oraU, 'err_ora_uscita', <?= json_encode($L['sess_err_ora_uscita_futura'] ?? $L['sess_err_ora_uscita']) ?>);
                valid = false;
            } else {
                formClearErr(oraU, 'err_ora_uscita');
            }
        }

        // Co-present teacher must differ from lead
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
