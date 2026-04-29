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

            /*
             * Nota: nella nuova sessione registriamo solo "quali materiali sono stati usati"
             * senza specificare la quantita. La quantita esatta si inserisce nel dettaglio.
             * Il checkbox serve come promemoria rapido; la deduzione della quantita avviene
             * nella pagina dettaglio con il form dedicato.
             */

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
   Carica i materiali per ogni laboratorio in formato JSON
   (usato dal JS per filtrare dinamicamente)
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
   Include header.php SOLO dopo tutti i possibili redirect
   ================================================================ */
$pageTitle = 'Nuova Sessione';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';

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

            <div class="d-flex gap-2" style="margin-top:16px;">
                <button type="submit" class="btn btn-success" id="btnSubmit">
                    <?= htmlspecialchars($L['sess_btn_registra']) ?>
                </button>
                <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">
                    <?= htmlspecialchars($L['annulla']) ?>
                </a>
            </div>

            <p class="form-text" style="margin-top:8px;">
                Dopo aver creato la sessione potrai aggiungere i materiali utilizzati con le relative quantita dalla pagina di dettaglio.
            </p>
        </form>
    </div>
</div>

<?php formFieldScripts(); ?>

<script>
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
