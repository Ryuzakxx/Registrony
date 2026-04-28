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
    $idLab            = intval($_POST['id_laboratorio'] ?? 0);
    $idClasse         = intval($_POST['id_classe'] ?? 0);
    $data             = mysqli_real_escape_string($conn, $_POST['data'] ?? '');
    $oraIngresso      = mysqli_real_escape_string($conn, $_POST['ora_ingresso'] ?? '');
    $oraUscita        = mysqli_real_escape_string($conn, $_POST['ora_uscita'] ?? '');
    $attivita         = mysqli_real_escape_string($conn, trim($_POST['attivita_svolta'] ?? ''));
    $note             = mysqli_real_escape_string($conn, trim($_POST['note'] ?? ''));
    $docenteTitolare  = intval($_POST['docente_titolare'] ?? 0);
    $docenteCompresenza = intval($_POST['docente_compresenza'] ?? 0);

    if (!$idLab)           $errors[] = 'Seleziona un laboratorio.';
    if (!$idClasse)        $errors[] = 'Seleziona una classe.';
    if (!$data)            $errors[] = 'Inserisci la data.';
    if (!$oraIngresso)     $errors[] = "Inserisci l'ora di ingresso.";
    if (!$docenteTitolare) $errors[] = 'Seleziona il docente titolare.';
    if ($docenteCompresenza && $docenteCompresenza === $docenteTitolare)
        $errors[] = 'Il docente in compresenza deve essere diverso dal titolare.';

    if (empty($errors)) {
        $oraUscitaSQL = $oraUscita ? "'$oraUscita'" : 'NULL';
        $attivitaSQL  = $attivita  ? "'$attivita'"  : 'NULL';
        $noteSQL      = $note      ? "'$note'"       : 'NULL';

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO sessioni_laboratorio (id_laboratorio, id_classe, data, ora_ingresso, ora_uscita, attivita_svolta, note) VALUES ($idLab, $idClasse, '$data', '$oraIngresso', $oraUscitaSQL, $attivitaSQL, $noteSQL)");
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
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>- <?= htmlspecialchars($err) ?><br><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>&#10133; Registra nuova sessione di laboratorio</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Laboratorio *</label>
                    <select name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona laboratorio --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id'] == ($_POST['id_laboratorio'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($lab['nome'] . ' (' . $lab['aula'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Classe *</label>
                    <select name="id_classe" class="form-control" required>
                        <option value="">-- Seleziona classe --</option>
                        <?php foreach ($classi as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= ($cl['id'] == ($_POST['id_classe'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($cl['nome'] . ' - ' . $cl['anno_scolastico']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Data *</label>
                    <input type="date" name="data" class="form-control" required value="<?= htmlspecialchars($_POST['data'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label>Ora Ingresso *</label>
                    <input type="time" name="ora_ingresso" class="form-control" required value="<?= htmlspecialchars($_POST['ora_ingresso'] ?? date('H:i')) ?>">
                </div>
                <div class="form-group">
                    <label>Ora Uscita</label>
                    <input type="time" name="ora_uscita" class="form-control" value="<?= htmlspecialchars($_POST['ora_uscita'] ?? '') ?>">
                    <div class="form-text">Lascia vuoto se la sessione e' in corso.</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Docente Titolare *</label>
                    <select name="docente_titolare" class="form-control" required>
                        <option value="">-- Seleziona docente --</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == ($_POST['docente_titolare'] ?? getCurrentUserId())) ? 'selected' : '' ?>><?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Docente Compresenza</label>
                    <select name="docente_compresenza" class="form-control">
                        <option value="">-- Nessuno --</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == ($_POST['docente_compresenza'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Attivita Svolta</label>
                <textarea name="attivita_svolta" class="form-control" rows="3" placeholder="Descrivi l'attivita svolta..."><?= htmlspecialchars($_POST['attivita_svolta'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Note</label>
                <textarea name="note" class="form-control" rows="2"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">&#10004; Registra Sessione</button>
                <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">Annulla</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>