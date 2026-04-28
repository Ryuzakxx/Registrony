<?php
$pageTitle = 'Dettaglio Segnalazione';
require_once __DIR__ . '/../../includes/header.php';

$conn = getConnection();
$id   = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: ' . BASE_PATH . '/pages/segnalazioni/index.php?error=Segnalazione non trovata'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $nuovoStato      = mysqli_real_escape_string($conn, $_POST['stato'] ?? '');
    $noteRisoluzione = mysqli_real_escape_string($conn, trim($_POST['note_risoluzione'] ?? ''));
    $validStati      = ['aperta','in_lavorazione','risolta','chiusa'];

    if (in_array($nuovoStato, $validStati)) {
        $dataRisoluzione = in_array($nuovoStato, ['risolta','chiusa']) ? "'" . date('Y-m-d H:i:s') . "'" : 'NULL';
        $noteSQL         = $noteRisoluzione ? "'$noteRisoluzione'" : 'NULL';
        mysqli_query($conn, "UPDATE segnalazioni SET stato = '$nuovoStato', note_risoluzione = $noteSQL, data_risoluzione = $dataRisoluzione WHERE id = $id");
        header('Location: ' . BASE_PATH . '/pages/segnalazioni/dettaglio.php?id=' . $id . '&success=Stato aggiornato!');
        exit;
    }
}

$result      = mysqli_query($conn, "SELECT sg.*, l.nome AS laboratorio, l.aula, CONCAT(u.cognome, ' ', u.nome) AS segnalato_da FROM segnalazioni sg JOIN laboratori l ON sg.id_laboratorio = l.id JOIN utenti u ON sg.id_utente = u.id WHERE sg.id = $id");
$segnalazione= mysqli_fetch_assoc($result);
if (!$segnalazione) { header('Location: ' . BASE_PATH . '/pages/segnalazioni/index.php?error=Segnalazione non trovata'); exit; }

$sc = match($segnalazione['stato']) { 'aperta'=>'badge-danger','in_lavorazione'=>'badge-warning','risolta'=>'badge-success',default=>'badge-secondary' };
$bc = match($segnalazione['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' };
?>

<div class="card">
    <div class="card-header">
        <h3>Segnalazione #<?= $id ?></h3>
        <span class="badge <?= $sc ?>" style="font-size:13px;padding:6px 14px;"><?= str_replace('_',' ',ucfirst($segnalazione['stato'])) ?></span>
    </div>
    <div class="card-body">
        <div class="form-row mb-2">
            <div><strong>Laboratorio:</strong><br><?= htmlspecialchars($segnalazione['laboratorio']) ?> (<?= htmlspecialchars($segnalazione['aula']) ?>)</div>
            <div><strong>Priorita:</strong><br><span class="badge <?= $bc ?>"><?= $segnalazione['priorita'] ?></span></div>
            <div><strong>Segnalato da:</strong><br><?= htmlspecialchars($segnalazione['segnalato_da']) ?></div>
            <div><strong>Data:</strong><br><?= date('d/m/Y H:i', strtotime($segnalazione['data_segnalazione'])) ?></div>
        </div>
        <div class="mb-2"><strong>Titolo:</strong><h4><?= htmlspecialchars($segnalazione['titolo']) ?></h4></div>
        <div class="mb-2">
            <strong>Descrizione:</strong>
            <p style="white-space:pre-wrap;background:#f8fafc;padding:12px;border-radius:6px;border:1px solid var(--border);"><?= htmlspecialchars($segnalazione['descrizione']) ?></p>
        </div>
        <?php if ($segnalazione['data_risoluzione']): ?>
            <div class="mb-2"><strong>Data risoluzione:</strong> <?= date('d/m/Y H:i', strtotime($segnalazione['data_risoluzione'])) ?></div>
        <?php endif; ?>
        <?php if ($segnalazione['note_risoluzione']): ?>
            <div class="mb-2">
                <strong>Note risoluzione:</strong>
                <p style="white-space:pre-wrap;background:var(--success-light);padding:12px;border-radius:6px;"><?= htmlspecialchars($segnalazione['note_risoluzione']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isAdmin()): ?>
<div class="card">
    <div class="card-header"><h3>&#128736; Gestione Segnalazione (Admin)</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Cambia Stato</label>
                    <select name="stato" class="form-control" required>
                        <option value="aperta"         <?= $segnalazione['stato']==='aperta'         ? 'selected':'' ?>>Aperta</option>
                        <option value="in_lavorazione" <?= $segnalazione['stato']==='in_lavorazione' ? 'selected':'' ?>>In lavorazione</option>
                        <option value="risolta"        <?= $segnalazione['stato']==='risolta'        ? 'selected':'' ?>>Risolta</option>
                        <option value="chiusa"         <?= $segnalazione['stato']==='chiusa'         ? 'selected':'' ?>>Chiusa</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Note Risoluzione</label>
                <textarea name="note_risoluzione" class="form-control" rows="3" placeholder="Descrivi come e' stato risolto il problema..."><?= htmlspecialchars($segnalazione['note_risoluzione'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Aggiorna Stato</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="mt-2">
    <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary">&#8592; Torna alle segnalazioni</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>