<?php
$pageTitle = 'Gestione Materiali';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome = trim($_POST['nome'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $unitaMisura = trim($_POST['unita_misura'] ?? '');
        $idLab = intval($_POST['id_laboratorio'] ?? 0);
        $quantita = $_POST['quantita_disponibile'] !== '' ? floatval($_POST['quantita_disponibile']) : null;
        $soglia = $_POST['soglia_minima'] !== '' ? floatval($_POST['soglia_minima']) : null;
        $attivo = isset($_POST['attivo']) ? 1 : 0;

        if ($nome && $idLab) {
            if ($action === 'crea') {
                $stmt = $pdo->prepare("INSERT INTO materiali (nome, descrizione, unita_misura, id_laboratorio, quantita_disponibile, soglia_minima, attivo) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$nome, $descrizione ?: null, $unitaMisura ?: null, $idLab, $quantita, $soglia, $attivo]);
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale creato!');
            } else {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE materiali SET nome=?, descrizione=?, unita_misura=?, id_laboratorio=?, quantita_disponibile=?, soglia_minima=?, attivo=? WHERE id=?");
                $stmt->execute([$nome, $descrizione ?: null, $unitaMisura ?: null, $idLab, $quantita, $soglia, $attivo, $id]);
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale aggiornato!');
            }
            exit;
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=Compila tutti i campi obbligatori');
            exit;
        }
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM materiali WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale eliminato!');
        } catch (PDOException $e) {
            header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=Impossibile eliminare: materiale in uso');
        }
        exit;
    }
}

$filtroLab = $_GET['laboratorio'] ?? '';
$where = '';
$params = [];
if ($filtroLab) {
    $where = "WHERE m.id_laboratorio = ?";
    $params[] = $filtroLab;
}

$stmt = $pdo->prepare("
    SELECT m.*, l.nome AS laboratorio
    FROM materiali m
    JOIN laboratori l ON m.id_laboratorio = l.id
    $where
    ORDER BY l.nome, m.nome
");
$stmt->execute($params);
$materiali = $stmt->fetchAll();

$labs = $pdo->query("SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome")->fetchAll();

$editMat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM materiali WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editMat = $stmt->fetch();
}
?>

<div class="card">
    <div class="card-header">
        <h3><?= $editMat ? '&#9998; Modifica Materiale' : '&#10133; Nuovo Materiale' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editMat ? 'modifica' : 'crea' ?>">
            <?php if ($editMat): ?>
                <input type="hidden" name="id" value="<?= $editMat['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Nome Materiale *</label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?= htmlspecialchars($editMat['nome'] ?? '') ?>"
                           placeholder="Es: Cavo Ethernet Cat.6">
                </div>
                <div class="form-group">
                    <label>Laboratorio *</label>
                    <select name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id'] == ($editMat['id_laboratorio'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lab['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Unita di Misura</label>
                    <input type="text" name="unita_misura" class="form-control"
                           value="<?= htmlspecialchars($editMat['unita_misura'] ?? '') ?>"
                           placeholder="Es: pezzi, litri, kg">
                </div>
                <div class="form-group">
                    <label>Quantita Disponibile</label>
                    <input type="number" name="quantita_disponibile" class="form-control" step="0.01" min="0"
                           value="<?= $editMat['quantita_disponibile'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Soglia Minima</label>
                    <input type="number" name="soglia_minima" class="form-control" step="0.01" min="0"
                           value="<?= $editMat['soglia_minima'] ?? '' ?>">
                    <div class="form-text">Segnalazione quando la quantita scende sotto questa soglia.</div>
                </div>
            </div>
            <div class="form-group">
                <label>Descrizione</label>
                <textarea name="descrizione" class="form-control" rows="2"><?= htmlspecialchars($editMat['descrizione'] ?? '') ?></textarea>
            </div>
            <?php if ($editMat): ?>
                <div class="form-group">
                    <label style="font-weight:normal; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="attivo" value="1" <?= $editMat['attivo'] ? 'checked' : '' ?>> Attivo
                    </label>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editMat ? 'Salva Modifiche' : 'Crea Materiale' ?></button>
                <?php if ($editMat): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="btn btn-secondary">Annulla</a>
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
                <label>Filtra per Laboratorio</label>
                <select name="laboratorio" class="form-control">
                    <option value="">Tutti</option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab == $lab['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lab['nome']) ?>
                        </option>
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
    <div class="card-header">
        <h3>&#128206; Materiali (<?= count($materiali) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state"><h4>Nessun materiale</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Nome</th><th>Laboratorio</th><th>Unita</th><th>Disponibile</th><th>Soglia</th><th>Stato</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiali as $m): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($m['nome']) ?></strong>
                                <?php if ($m['descrizione']): ?><br><small class="text-muted"><?= htmlspecialchars($m['descrizione']) ?></small><?php endif; ?>
                            </td>
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
                                <a href="?edit=<?= $m['id'] ?><?= $filtroLab ? '&laboratorio='.$filtroLab : '' ?>" class="btn btn-primary btn-sm">Modifica</a>
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
