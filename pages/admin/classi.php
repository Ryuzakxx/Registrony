<?php
$pageTitle = 'Gestione Classi';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome    = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $anno    = mysqli_real_escape_string($conn, trim($_POST['anno_scolastico'] ?? ''));
        $ind     = mysqli_real_escape_string($conn, trim($_POST['indirizzo'] ?? ''));
        $attivo  = isset($_POST['attivo']) ? 1 : 0;
        $indSQL  = $ind ? "'$ind'" : 'NULL';

        if ($nome && $anno) {
            if ($action === 'crea') {
                $ok = mysqli_query($conn, "INSERT INTO classi (nome, anno_scolastico, indirizzo, attivo) VALUES ('$nome','$anno',$indSQL,$attivo)");
                if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=Classe creata!');
                else     header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=Classe gia esistente per questo anno');
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn, "UPDATE classi SET nome='$nome', anno_scolastico='$anno', indirizzo=$indSQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=Classe aggiornata!');
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=Compila tutti i campi obbligatori');
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM classi WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=Classe eliminata!');
        else     header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=Impossibile eliminare: classe in uso');
        exit;
    }
}

$result = mysqli_query($conn, "SELECT * FROM classi ORDER BY anno_scolastico DESC, nome");
$classi = [];
while ($row = mysqli_fetch_assoc($result)) $classi[] = $row;

$editClasse = null;
if (isset($_GET['edit'])) {
    $editId     = intval($_GET['edit']);
    $res        = mysqli_query($conn, "SELECT * FROM classi WHERE id = $editId");
    $editClasse = mysqli_fetch_assoc($res);
}
?>

<div class="card">
    <div class="card-header"><h3><?= $editClasse ? '&#9998; Modifica Classe':'&#10133; Nuova Classe' ?></h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editClasse ? 'modifica':'crea' ?>">
            <?php if ($editClasse): ?><input type="hidden" name="id" value="<?= $editClasse['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label>Nome Classe *</label><input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($editClasse['nome'] ?? '') ?>" placeholder="Es: 3A"></div>
                <div class="form-group"><label>Anno Scolastico *</label><input type="text" name="anno_scolastico" class="form-control" required value="<?= htmlspecialchars($editClasse['anno_scolastico'] ?? date('Y').'/'.(date('Y')+1)) ?>" placeholder="Es: 2025/2026"></div>
                <div class="form-group"><label>Indirizzo</label><input type="text" name="indirizzo" class="form-control" value="<?= htmlspecialchars($editClasse['indirizzo'] ?? '') ?>" placeholder="Es: Informatica"></div>
            </div>
            <?php if ($editClasse): ?>
                <div class="form-group"><label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="attivo" value="1" <?= $editClasse['attivo'] ? 'checked':'' ?>> Attiva</label></div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editClasse ? 'Salva Modifiche':'Crea Classe' ?></button>
                <?php if ($editClasse): ?><a href="<?= BASE_PATH ?>/pages/admin/classi.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#127979; Classi (<?= count($classi) ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($classi)): ?>
            <div class="empty-state"><h4>Nessuna classe</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Nome</th><th>Anno</th><th>Indirizzo</th><th>Stato</th><th>Azioni</th></tr></thead>
                    <tbody>
                        <?php foreach ($classi as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($c['anno_scolastico']) ?></td>
                            <td><?= htmlspecialchars($c['indirizzo'] ?? '-') ?></td>
                            <td><span class="badge <?= $c['attivo'] ? 'badge-success':'badge-secondary' ?>"><?= $c['attivo'] ? 'Attiva':'Disattivata' ?></span></td>
                            <td class="actions">
                                <a href="?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Modifica</a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
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