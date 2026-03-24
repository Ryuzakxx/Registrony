<?php
$pageTitle = 'Gestione Laboratori';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$pdo = getConnection();

// Azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome = trim($_POST['nome'] ?? '');
        $aula = trim($_POST['aula'] ?? '');
        $idAssistente = intval($_POST['id_assistente_tecnico'] ?? 0);
        $idResponsabile = intval($_POST['id_responsabile'] ?? 0);
        $descrizione = trim($_POST['descrizione'] ?? '');
        $attivo = isset($_POST['attivo']) ? 1 : 0;

        if ($nome && $aula && $idAssistente && $idResponsabile) {
            if ($action === 'crea') {
                $stmt = $pdo->prepare("INSERT INTO laboratori (nome, aula, id_assistente_tecnico, id_responsabile, descrizione, attivo) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$nome, $aula, $idAssistente, $idResponsabile, $descrizione ?: null, $attivo]);
                header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=Laboratorio creato!');
            } else {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE laboratori SET nome=?, aula=?, id_assistente_tecnico=?, id_responsabile=?, descrizione=?, attivo=? WHERE id=?");
                $stmt->execute([$nome, $aula, $idAssistente, $idResponsabile, $descrizione ?: null, $attivo, $id]);
                header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=Laboratorio aggiornato!');
            }
            exit;
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?error=Compila tutti i campi obbligatori');
            exit;
        }
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM laboratori WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=Laboratorio eliminato!');
        } catch (PDOException $e) {
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?error=Impossibile eliminare: laboratorio in uso');
        }
        exit;
    }
}

$labs = $pdo->query("
    SELECT l.*, 
           CONCAT(a.cognome, ' ', a.nome) AS assistente,
           CONCAT(r.cognome, ' ', r.nome) AS responsabile
    FROM laboratori l
    JOIN utenti a ON l.id_assistente_tecnico = a.id
    JOIN utenti r ON l.id_responsabile = r.id
    ORDER BY l.nome
")->fetchAll();

$admins = $pdo->query("SELECT id, nome, cognome FROM utenti WHERE ruolo = 'admin' AND attivo = 1 ORDER BY cognome, nome")->fetchAll();

// Per modifica
$editLab = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM laboratori WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editLab = $stmt->fetch();
}
?>

<div class="card">
    <div class="card-header">
        <h3><?= $editLab ? '&#9998; Modifica Laboratorio' : '&#10133; Nuovo Laboratorio' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editLab ? 'modifica' : 'crea' ?>">
            <?php if ($editLab): ?>
                <input type="hidden" name="id" value="<?= $editLab['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Nome Laboratorio *</label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?= htmlspecialchars($editLab['nome'] ?? '') ?>"
                           placeholder="Es: Laboratorio Informatica 1">
                </div>
                <div class="form-group">
                    <label>Aula *</label>
                    <input type="text" name="aula" class="form-control" required
                           value="<?= htmlspecialchars($editLab['aula'] ?? '') ?>"
                           placeholder="Es: A101">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Assistente Tecnico *</label>
                    <select name="id_assistente_tecnico" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= ($a['id'] == ($editLab['id_assistente_tecnico'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['cognome'] . ' ' . $a['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Professore Responsabile *</label>
                    <select name="id_responsabile" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= ($a['id'] == ($editLab['id_responsabile'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['cognome'] . ' ' . $a['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descrizione</label>
                <textarea name="descrizione" class="form-control" rows="2"><?= htmlspecialchars($editLab['descrizione'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label style="font-weight:normal; display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" name="attivo" value="1" <?= ($editLab['attivo'] ?? 1) ? 'checked' : '' ?>> Attivo
                </label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editLab ? 'Salva Modifiche' : 'Crea Laboratorio' ?></button>
                <?php if ($editLab): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php" class="btn btn-secondary">Annulla</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>&#128187; Laboratori (<?= count($labs) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($labs)): ?>
            <div class="empty-state"><h4>Nessun laboratorio</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Nome</th><th>Aula</th><th>Assistente</th><th>Responsabile</th><th>Stato</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labs as $l): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($l['nome']) ?></strong>
                                <?php if ($l['descrizione']): ?><br><small class="text-muted"><?= htmlspecialchars($l['descrizione']) ?></small><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($l['aula']) ?></td>
                            <td><?= htmlspecialchars($l['assistente']) ?></td>
                            <td><?= htmlspecialchars($l['responsabile']) ?></td>
                            <td><span class="badge <?= $l['attivo'] ? 'badge-success' : 'badge-secondary' ?>"><?= $l['attivo'] ? 'Attivo' : 'Disattivato' ?></span></td>
                            <td class="actions">
                                <a href="?edit=<?= $l['id'] ?>" class="btn btn-primary btn-sm">Modifica</a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Sei sicuro di voler eliminare questo laboratorio?">Elimina</button>
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
