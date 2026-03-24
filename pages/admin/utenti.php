<?php
$pageTitle = 'Gestione Utenti';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea') {
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // Presa direttamente
        $ruolo = $_POST['ruolo'] ?? 'docente';
        $telefono = trim($_POST['telefono'] ?? '');

        if ($nome && $cognome && $email && $password) {
            try {
                // Inserimento con password in chiaro
                $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, email, password, ruolo, telefono) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$nome, $cognome, $email, $password, $ruolo, $telefono ?: null]);
                
                header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente creato!');
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Email gia in uso');
                    exit;
                }
                throw $e;
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Compila tutti i campi obbligatori');
            exit;
        }
    }

    if ($action === 'modifica') {
        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $ruolo = $_POST['ruolo'] ?? 'docente';
        $telefono = trim($_POST['telefono'] ?? '');
        $attivo = isset($_POST['attivo']) ? 1 : 0;
        $newPassword = $_POST['new_password'] ?? '';

        if ($nome && $cognome && $email && $id) {
            try {
                if ($newPassword) {
                    // Aggiornamento con nuova password in chiaro
                    $stmt = $pdo->prepare("UPDATE utenti SET nome=?, cognome=?, email=?, password=?, ruolo=?, telefono=?, attivo=? WHERE id=?");
                    $stmt->execute([$nome, $cognome, $email, $newPassword, $ruolo, $telefono ?: null, $attivo, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE utenti SET nome=?, cognome=?, email=?, ruolo=?, telefono=?, attivo=? WHERE id=?");
                    $stmt->execute([$nome, $cognome, $email, $ruolo, $telefono ?: null, $attivo, $id]);
                }
                header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente aggiornato!');
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Email gia in uso');
                    exit;
                }
                throw $e;
            }
        }
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        if ($id != getCurrentUserId()) {
            try {
                $stmt = $pdo->prepare("DELETE FROM utenti WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente eliminato!');
            } catch (PDOException $e) {
                header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Impossibile eliminare: utente in uso');
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Non puoi eliminare te stesso!');
        }
        exit;
    }
}

$utenti = $pdo->query("SELECT * FROM utenti ORDER BY cognome, nome")->fetchAll();

$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM utenti WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editUser = $stmt->fetch();
}
?>

<div class="card">
    <div class="card-header">
        <h3><?= $editUser ? '&#9998; Modifica Utente' : '&#10133; Nuovo Utente' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editUser ? 'modifica' : 'crea' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?= htmlspecialchars($editUser['nome'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Cognome *</label>
                    <input type="text" name="cognome" class="form-control" required
                           value="<?= htmlspecialchars($editUser['cognome'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= $editUser ? 'Nuova Password (lascia vuoto per non cambiare)' : 'Password *' ?></label>
                    <input type="password" name="<?= $editUser ? 'new_password' : 'password' ?>" class="form-control"
                           <?= $editUser ? '' : 'required' ?> minlength="6">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Ruolo *</label>
                    <select name="ruolo" class="form-control" required>
                        <option value="docente" <?= ($editUser['ruolo'] ?? 'docente') === 'docente' ? 'selected' : '' ?>>Docente</option>
                        <option value="admin" <?= ($editUser['ruolo'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (Responsabile/Assistente)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Telefono</label>
                    <input type="text" name="telefono" class="form-control"
                           value="<?= htmlspecialchars($editUser['telefono'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editUser): ?>
                <div class="form-group">
                    <label style="font-weight:normal; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="attivo" value="1" <?= $editUser['attivo'] ? 'checked' : '' ?>> Account attivo
                    </label>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editUser ? 'Salva Modifiche' : 'Crea Utente' ?></button>
                <?php if ($editUser): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/utenti.php" class="btn btn-secondary">Annulla</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>&#128101; Utenti (<?= count($utenti) ?>)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Cognome Nome</th><th>Email</th><th>Ruolo</th><th>Telefono</th><th>Stato</th><th>Azioni</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($utenti as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['cognome'] . ' ' . $u['nome']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge <?= $u['ruolo'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>"><?= $u['ruolo'] ?></span></td>
                        <td><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                        <td><span class="badge <?= $u['attivo'] ? 'badge-success' : 'badge-danger' ?>"><?= $u['attivo'] ? 'Attivo' : 'Disattivato' ?></span></td>
                        <td class="actions">
                            <a href="?edit=<?= $u['id'] ?>" class="btn btn-primary btn-sm">Modifica</a>
                            <?php if ($u['id'] != getCurrentUserId()): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Sei sicuro?');">
                                <input type="hidden" name="action" value="elimina">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>