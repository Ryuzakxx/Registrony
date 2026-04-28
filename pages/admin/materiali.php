<?php
$pageTitle = 'Gestione Materiali';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome     = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $desc     = mysqli_real_escape_string($conn, trim($_POST['descrizione'] ?? ''));
        $unita    = mysqli_real_escape_string($conn, trim($_POST['unita_misura'] ?? ''));
        $idLab    = intval($_POST['id_laboratorio'] ?? 0);
        $quantita = $_POST['quantita_disponibile'] !== '' ? floatval($_POST['quantita_disponibile']) : null;
        $soglia   = $_POST['soglia_minima'] !== '' ? floatval($_POST['soglia_minima']) : null;
        $attivo   = isset($_POST['attivo']) ? 1 : 0;

        $descSQL     = $desc     ? "'$desc'"     : 'NULL';
        $unitaSQL    = $unita    ? "'$unita'"    : 'NULL';
        $quantitaSQL = $quantita !== null ? $quantita : 'NULL';
        $sogliaSQL   = $soglia   !== null ? $soglia   : 'NULL';

        if ($nome && $idLab) {
            if ($action === 'crea') {
                mysqli_query($conn, "INSERT INTO materiali (nome, descrizione, unita_misura, id_laboratorio, quantita_disponibile, soglia_minima, attivo) VALUES ('$nome',$descSQL,$unitaSQL,$idLab,$quantitaSQL,$sogliaSQL,$attivo)");
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale creato!');
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn, "UPDATE materiali SET nome='$nome', descrizione=$descSQL, unita_misura=$unitaSQL, id_laboratorio=$idLab, quantita_disponibile=$quantitaSQL, soglia_minima=$sogliaSQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale aggiornato!');
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=Compila tutti i campi obbligatori');
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM materiali WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale eliminato!');
        else     header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=Impossibile eliminare: materiale in uso');
        exit;
    }
}

$filtroLab = mysqli_real_escape_string($conn, $_GET['laboratorio'] ?? '');
$where     = $filtroLab ? "WHERE m.id_laboratorio = '$filtroLab'" : '';

$result   = mysqli_query($conn, "SELECT m.*, l.nome AS laboratorio FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id $where ORDER BY l.nome, m.nome");
$materiali= [];
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
?>

<div class="card">
    <div class="card-header"><h3><?= $editMat ? '&#9998; Modifica Materiale':'&#10133; Nuovo Materiale' ?></h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editMat ? 'modifica':'crea' ?>">
            <?php if ($editMat): ?><input type="hidden" name="id" value="<?= $editMat['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label>Nome *</label><input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($editMat['nome'] ?? '') ?>" placeholder="Es: Cavo Ethernet Cat.6"></div>
                <div class="form-group">
                    <label>Laboratorio *</label>
                    <select name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id']==($editMat['id_laboratorio']??'')) ? 'selected':'' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Unita di Misura</label><input type="text" name="unita_misura" class="form-control" value="<?= htmlspecialchars($editMat['unita_misura'] ?? '') ?>" placeholder="Es: pezzi, litri"></div>
                <div class="form-group"><label>Quantita Disponibile</label><input type="number" name="quantita_disponibile" class="form-control" step="0.01" min="0" value="<?= $editMat['quantita_disponibile'] ?? '' ?>"></div>
                <div class="form-group"><label>Soglia Minima</label><input type="number" name="soglia_minima" class="form-control" step="0.01" min="0" value="<?= $editMat['soglia_minima'] ?? '' ?>"></div>
            </div>
            <div class="form-group"><label>Descrizione</label><textarea name="descrizione" class="form-control" rows="2"><?= htmlspecialchars($editMat['descrizione'] ?? '') ?></textarea></div>
            <?php if ($editMat): ?>
                <div class="form-group"><label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="attivo" value="1" <?= $editMat['attivo'] ? 'checked':'' ?>> Attivo</label></div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editMat ? 'Salva Modifiche':'Crea Materiale' ?></button>
                <?php if ($editMat): ?><a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
            <div class="form-group" style="margin-bottom:0">
                <label>Filtra per Laboratorio</label>
                <select name="laboratorio" class="form-control">
                    <option value="">Tutti</option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab==$lab['id'] ? 'selected':'' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:22px">
                <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
                <a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#128206; Materiali (<?= count($materiali) ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state"><h4>Nessun materiale</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Nome</th><th>Laboratorio</th><th>Unita</th><th>Disponibile</th><th>Soglia</th><th>Stato</th><th>Azioni</th></tr></thead>
                    <tbody>
                        <?php foreach ($materiali as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['nome']) ?></strong><?php if ($m['descrizione']): ?><br><small class="text-muted"><?= htmlspecialchars($m['descrizione']) ?></small><?php endif; ?></td>
                            <td><?= htmlspecialchars($m['laboratorio']) ?></td>
                            <td><?= htmlspecialchars($m['unita_misura'] ?? '-') ?></td>
                            <td><strong><?= $m['quantita_disponibile'] ?? '-' ?></strong></td>
                            <td><?= $m['soglia_minima'] ?? '-' ?></td>
                            <td>
                                <?php if (!$m['attivo']): ?>
                                    <span class="badge badge-secondary">Disattivato</span>
                                <?php elseif ($m['quantita_disponibile'] !== null && $m['quantita_disponibile'] <= 0): ?>
                                    <span class="badge badge-danger">Esaurito</span>
                                <?php elseif ($m['quantita_disponibile'] !== null && $m['soglia_minima'] !== null && $m['quantita_disponibile'] <= $m['soglia_minima']): ?>
                                    <span class="badge badge-warning">In esaurimento</span>
                                <?php else: ?>
                                    <span class="badge badge-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $m['id'] ?><?= $filtroLab ? '&laboratorio='.$filtroLab:'' ?>" class="btn btn-primary btn-sm">Modifica</a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Sei sicuro?">Elimina</button>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>