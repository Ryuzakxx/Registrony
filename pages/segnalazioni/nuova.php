<?php
$pageTitle = 'Nuova Segnalazione';
require_once __DIR__ . '/../../includes/header.php';

$conn   = getConnection();
$errors = [];

$resLabs = mysqli_query($conn, "SELECT id, nome, aula FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs    = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$preselectedLab     = intval($_GET['id_laboratorio'] ?? 0);
$preselectedSessione= intval($_GET['id_sessione'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idLab      = intval($_POST['id_laboratorio'] ?? 0);
    $idSessione = intval($_POST['id_sessione'] ?? 0) ?: null;
    $titolo     = mysqli_real_escape_string($conn, trim($_POST['titolo'] ?? ''));
    $descrizione= mysqli_real_escape_string($conn, trim($_POST['descrizione'] ?? ''));
    $priorita   = mysqli_real_escape_string($conn, $_POST['priorita'] ?? 'media');

    if (!$idLab)       $errors[] = 'Seleziona un laboratorio.';
    if (!$titolo)      $errors[] = 'Inserisci un titolo.';
    if (!$descrizione) $errors[] = 'Inserisci una descrizione.';

    if (empty($errors)) {
        $idSessioneSQL = $idSessione ? $idSessione : 'NULL';
        $userId        = getCurrentUserId();
        mysqli_query($conn, "INSERT INTO segnalazioni (id_laboratorio, id_sessione, id_utente, titolo, descrizione, priorita) VALUES ($idLab, $idSessioneSQL, $userId, '$titolo', '$descrizione', '$priorita')");
        header('Location: ' . BASE_PATH . '/pages/segnalazioni/index.php?success=Segnalazione inviata con successo!');
        exit;
    }
}
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $err): ?>- <?= htmlspecialchars($err) ?><br><?php endforeach; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>&#9888; Nuova Segnalazione Problema</h3></div>
    <div class="card-body">
        <form method="POST">
            <?php if ($preselectedSessione): ?>
                <input type="hidden" name="id_sessione" value="<?= $preselectedSessione ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Laboratorio *</label>
                    <select name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id'] == ($_POST['id_laboratorio'] ?? $preselectedLab)) ? 'selected':'' ?>><?= htmlspecialchars($lab['nome'] . ' (' . $lab['aula'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priorita *</label>
                    <select name="priorita" class="form-control" required>
                        <option value="bassa"   <?= ($_POST['priorita'] ?? '')      ==='bassa'   ? 'selected':'' ?>>Bassa</option>
                        <option value="media"   <?= ($_POST['priorita'] ?? 'media') ==='media'   ? 'selected':'' ?>>Media</option>
                        <option value="alta"    <?= ($_POST['priorita'] ?? '')      ==='alta'    ? 'selected':'' ?>>Alta</option>
                        <option value="urgente" <?= ($_POST['priorita'] ?? '')      ==='urgente' ? 'selected':'' ?>>Urgente</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Titolo *</label>
                <input type="text" name="titolo" class="form-control" required placeholder="Es: PC postazione 5 non funziona" value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Descrizione *</label>
                <textarea name="descrizione" class="form-control" rows="4" required placeholder="Descrivi il problema..."><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">&#9888; Invia Segnalazione</button>
                <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary">Annulla</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>