<?php
$pageTitle = 'Dettaglio Sessione';
require_once __DIR__ . '/../../includes/header.php';

$conn = getConnection();
$id   = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: ' . BASE_PATH . '/pages/sessioni/index.php?error=Sessione non trovata'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'chiudi_sessione') {
        $oraUscita = mysqli_real_escape_string($conn, $_POST['ora_uscita'] ?? date('H:i'));
        mysqli_query($conn, "UPDATE sessioni_laboratorio SET ora_uscita = '$oraUscita' WHERE id = $id AND ora_uscita IS NULL");
        header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=Sessione chiusa!'); exit;
    }

    if ($action === 'aggiorna') {
        $attivita = mysqli_real_escape_string($conn, trim($_POST['attivita_svolta'] ?? ''));
        $note     = mysqli_real_escape_string($conn, trim($_POST['note'] ?? ''));
        $attivitaSQL = $attivita ? "'$attivita'" : 'NULL';
        $noteSQL     = $note     ? "'$note'"     : 'NULL';
        mysqli_query($conn, "UPDATE sessioni_laboratorio SET attivita_svolta = $attivitaSQL, note = $noteSQL WHERE id = $id");
        header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=Sessione aggiornata!'); exit;
    }

    if ($action === 'aggiungi_materiale') {
        $idMateriale = intval($_POST['id_materiale'] ?? 0);
        $quantita    = floatval($_POST['quantita_usata'] ?? 0);
        $esaurito    = isset($_POST['esaurito']) ? 1 : 0;
        $noteMat     = mysqli_real_escape_string($conn, trim($_POST['note_materiale'] ?? ''));
        $noteMatSQL  = $noteMat ? "'$noteMat'" : 'NULL';
        if ($idMateriale && $quantita > 0) {
            $ok = mysqli_query($conn, "INSERT INTO utilizzo_materiali (id_sessione, id_materiale, quantita_usata, esaurito, note) VALUES ($id, $idMateriale, $quantita, $esaurito, $noteMatSQL)");
            if ($ok) { header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=Materiale registrato!'); }
            else     { header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&error=Materiale gia registrato per questa sessione'); }
            exit;
        }
    }

    if ($action === 'aggiungi_firma') {
        $idDocente = intval($_POST['id_docente'] ?? 0);
        if ($idDocente) {
            $ok = mysqli_query($conn, "INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES ($id, $idDocente, 'compresenza')");
            if ($ok) { header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=Firma aggiunta!'); }
            else     { header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&error=Errore aggiunta firma'); }
            exit;
        }
    }
}

$result   = mysqli_query($conn, "SELECT s.*, l.nome AS laboratorio, l.aula, c.nome AS classe, c.anno_scolastico FROM sessioni_laboratorio s JOIN laboratori l ON s.id_laboratorio = l.id JOIN classi c ON s.id_classe = c.id WHERE s.id = $id");
$sessione = mysqli_fetch_assoc($result);
if (!$sessione) { header('Location: ' . BASE_PATH . '/pages/sessioni/index.php?error=Sessione non trovata'); exit; }

$resFirme = mysqli_query($conn, "SELECT f.*, u.nome, u.cognome FROM firme_sessioni f JOIN utenti u ON f.id_docente = u.id WHERE f.id_sessione = $id ORDER BY f.tipo_presenza");
$firme    = [];
while ($row = mysqli_fetch_assoc($resFirme)) $firme[] = $row;

$resMat = mysqli_query($conn, "SELECT um.*, m.nome AS materiale, m.unita_misura FROM utilizzo_materiali um JOIN materiali m ON um.id_materiale = m.id WHERE um.id_sessione = $id");
$materialiUsati = [];
while ($row = mysqli_fetch_assoc($resMat)) $materialiUsati[] = $row;

$idUsati = array_column($materialiUsati, 'id_materiale');
$excludeSQL = $idUsati ? 'AND m.id NOT IN (' . implode(',', $idUsati) . ')' : '';
$resDisp = mysqli_query($conn, "SELECT m.id, m.nome, m.unita_misura, m.quantita_disponibile FROM materiali m WHERE m.id_laboratorio = {$sessione['id_laboratorio']} AND m.attivo = 1 $excludeSQL ORDER BY m.nome");
$materialiDisponibili = [];
while ($row = mysqli_fetch_assoc($resDisp)) $materialiDisponibili[] = $row;

$resDocenti = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo = 1 ORDER BY cognome, nome");
$docenti    = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

$inCorso = is_null($sessione['ora_uscita']);
?>

<div class="card">
    <div class="card-header">
        <h3>&#128203; Sessione #<?= $id ?></h3>
        <?php if ($inCorso): ?>
            <span class="badge badge-success" style="font-size:13px;padding:6px 14px;">&#9679; IN CORSO</span>
        <?php else: ?>
            <span class="badge badge-secondary" style="font-size:13px;padding:6px 14px;">Completata</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div><strong>Laboratorio:</strong><br><?= htmlspecialchars($sessione['laboratorio']) ?> (<?= htmlspecialchars($sessione['aula']) ?>)</div>
            <div><strong>Classe:</strong><br><span class="badge badge-primary"><?= htmlspecialchars($sessione['classe']) ?></span> <?= htmlspecialchars($sessione['anno_scolastico']) ?></div>
            <div><strong>Data:</strong><br><?= date('d/m/Y', strtotime($sessione['data'])) ?></div>
            <div><strong>Orario:</strong><br><?= substr($sessione['ora_ingresso'], 0, 5) ?> - <?= $sessione['ora_uscita'] ? substr($sessione['ora_uscita'], 0, 5) : '...' ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#9997; Firme Docenti</h3></div>
    <div class="card-body">
        <?php if (empty($firme)): ?>
            <p class="text-muted">Nessuna firma registrata.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Docente</th><th>Tipo</th><th>Ora Firma</th></tr></thead>
                    <tbody>
                        <?php foreach ($firme as $f): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['cognome'] . ' ' . $f['nome']) ?></strong></td>
                            <td><span class="badge <?= $f['tipo_presenza']==='titolare' ? 'badge-primary' : 'badge-info' ?>"><?= $f['tipo_presenza'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($f['ora_firma'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if (count($firme) < 2): ?>
            <form method="POST" class="mt-2 d-flex gap-2 align-center flex-wrap">
                <input type="hidden" name="action" value="aggiungi_firma">
                <select name="id_docente" class="form-control" style="max-width:300px" required>
                    <option value="">-- Aggiungi firma compresenza --</option>
                    <?php
                    $firmatiIds = array_column($firme, 'id_docente');
                    foreach ($docenti as $doc):
                        if (in_array($doc['id'], $firmatiIds)) continue;
                    ?>
                        <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Aggiungi Firma</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($inCorso): ?>
<div class="card">
    <div class="card-header"><h3>&#128308; Chiudi Sessione</h3></div>
    <div class="card-body">
        <form method="POST" class="d-flex gap-2 align-center flex-wrap">
            <input type="hidden" name="action" value="chiudi_sessione">
            <div class="form-group" style="margin-bottom:0">
                <label>Ora Uscita</label>
                <input type="time" name="ora_uscita" class="form-control" value="<?= date('H:i') ?>" required>
            </div>
            <button type="submit" class="btn btn-danger btn-sm" style="margin-top:22px;">Chiudi Sessione</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>&#128221; Attivita e Note</h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="aggiorna">
            <div class="form-group">
                <label>Attivita Svolta</label>
                <textarea name="attivita_svolta" class="form-control" rows="3"><?= htmlspecialchars($sessione['attivita_svolta'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Note</label>
                <textarea name="note" class="form-control" rows="2"><?= htmlspecialchars($sessione['note'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Aggiorna</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#128230; Materiali Utilizzati</h3></div>
    <div class="card-body">
        <?php if (!empty($materialiUsati)): ?>
            <div class="table-responsive mb-2">
                <table class="table">
                    <thead><tr><th>Materiale</th><th>Quantita</th><th>Unita</th><th>Esaurito</th><th>Note</th></tr></thead>
                    <tbody>
                        <?php foreach ($materialiUsati as $mu): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($mu['materiale']) ?></strong></td>
                            <td><?= $mu['quantita_usata'] ?></td>
                            <td><?= htmlspecialchars($mu['unita_misura'] ?? '-') ?></td>
                            <td><span class="badge <?= $mu['esaurito'] ? 'badge-danger' : 'badge-success' ?>"><?= $mu['esaurito'] ? 'Esaurito' : 'OK' ?></span></td>
                            <td><?= htmlspecialchars($mu['note'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-2">Nessun materiale registrato.</p>
        <?php endif; ?>

        <?php if (!empty($materialiDisponibili)): ?>
            <form method="POST" class="mt-1">
                <input type="hidden" name="action" value="aggiungi_materiale">
                <h4 class="mb-1" style="font-size:14px;font-weight:600;">Aggiungi materiale:</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Materiale</label>
                        <select name="id_materiale" class="form-control" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($materialiDisponibili as $md): ?>
                                <option value="<?= $md['id'] ?>"><?= htmlspecialchars($md['nome']) ?><?= $md['quantita_disponibile'] !== null ? ' (disp: ' . $md['quantita_disponibile'] . ' ' . htmlspecialchars($md['unita_misura'] ?? '') . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantita Usata</label>
                        <input type="number" name="quantita_usata" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <input type="text" name="note_materiale" class="form-control" placeholder="Opzionale">
                    </div>
                </div>
                <div class="d-flex gap-2 align-center">
                    <label style="font-weight:normal;font-size:13px;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="esaurito" value="1"> Materiale esaurito/finito
                    </label>
                    <button type="submit" class="btn btn-warning btn-sm">Aggiungi Materiale</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex gap-2 mt-2">
    <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">&#8592; Torna alle sessioni</a>
    <a href="<?= BASE_PATH ?>/pages/segnalazioni/nuova.php?id_laboratorio=<?= $sessione['id_laboratorio'] ?>&id_sessione=<?= $id ?>" class="btn btn-warning">&#9888; Segnala Problema</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>