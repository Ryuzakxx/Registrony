<?php
$pageTitle = 'Gestione Utenti';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea') {
        $nome     = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $cognome  = mysqli_real_escape_string($conn, trim($_POST['cognome'] ?? ''));
        $email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $password = mysqli_real_escape_string($conn, $_POST['password'] ?? '');
        $ruolo    = mysqli_real_escape_string($conn, $_POST['ruolo'] ?? 'docente');
        $telefono = mysqli_real_escape_string($conn, trim($_POST['telefono'] ?? ''));
        $telefonoSQL = $telefono ? "'$telefono'" : 'NULL';

        if ($nome && $cognome && $email && $password) {
            $ok = mysqli_query($conn, "INSERT INTO utenti (nome, cognome, email, password, ruolo, telefono) VALUES ('$nome','$cognome','$email','$password','$ruolo',$telefonoSQL)");
            if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente creato!');
            else     header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Email gia in uso');
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Compila tutti i campi obbligatori');
        }
        exit;
    }

    if ($action === 'modifica') {
        $id       = intval($_POST['id'] ?? 0);
        $nome     = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $cognome  = mysqli_real_escape_string($conn, trim($_POST['cognome'] ?? ''));
        $email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $ruolo    = mysqli_real_escape_string($conn, $_POST['ruolo'] ?? 'docente');
        $telefono = mysqli_real_escape_string($conn, trim($_POST['telefono'] ?? ''));
        $attivo   = isset($_POST['attivo']) ? 1 : 0;
        $newPass  = mysqli_real_escape_string($conn, $_POST['new_password'] ?? '');
        $telefonoSQL = $telefono ? "'$telefono'" : 'NULL';

        if ($nome && $cognome && $email && $id) {
            if ($newPass) {
                mysqli_query($conn, "UPDATE utenti SET nome='$nome', cognome='$cognome', email='$email', password='$newPass', ruolo='$ruolo', telefono=$telefonoSQL, attivo=$attivo WHERE id=$id");
            } else {
                mysqli_query($conn, "UPDATE utenti SET nome='$nome', cognome='$cognome', email='$email', ruolo='$ruolo', telefono=$telefonoSQL, attivo=$attivo WHERE id=$id");
            }
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente aggiornato!');
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        if ($id != getCurrentUserId()) {
            mysqli_query($conn, "DELETE FROM utenti WHERE id = $id");
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?success=Utente eliminato!');
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/utenti.php?error=Non puoi eliminare te stesso!');
        }
        exit;
    }
}

$result  = mysqli_query($conn, "SELECT * FROM utenti ORDER BY cognome, nome");
$utenti  = [];
while ($row = mysqli_fetch_assoc($result)) $utenti[] = $row;

$editUser = null;
if (isset($_GET['edit'])) {
    $editId   = intval($_GET['edit']);
    $res      = mysqli_query($conn, "SELECT * FROM utenti WHERE id = $editId");
    $editUser = mysqli_fetch_assoc($res);
}
?>

<div class="card">
    <div class="card-header"><h3><?= $editUser ? '&#9998; Modifica Utente' : '&#10133; Nuovo Utente' ?></h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editUser ? 'modifica' : 'crea' ?>">
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label>Nome *</label><input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($editUser['nome'] ?? '') ?>"></div>
                <div class="form-group"><label>Cognome *</label><input type="text" name="cognome" class="form-control" required value="<?= htmlspecialchars($editUser['cognome'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>"></div>
                <div class="form-group">
                    <label><?= $editUser ? 'Nuova Password (lascia vuoto per non cambiare)' : 'Password *' ?></label>
                    <input type="password" name="<?= $editUser ? 'new_password' : 'password' ?>" class="form-control" <?= $editUser ? '' : 'required' ?> minlength="6">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Ruolo *</label>
                    <select name="ruolo" class="form-control" required>
                        <option value="docente" <?= ($editUser['ruolo'] ?? 'docente')==='docente' ? 'selected':'' ?>>Docente</option>
                        <option value="admin"   <?= ($editUser['ruolo'] ?? '')==='admin'          ? 'selected':'' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group"><label>Telefono</label><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($editUser['telefono'] ?? '') ?>"></div>
            </div>
            <?php if ($editUser): ?>
                <div class="form-group">
                    <label style="font-weight:normal;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="attivo" value="1" <?= $editUser['attivo'] ? 'checked':'' ?>> Account attivo
                    </label>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editUser ? 'Salva Modifiche' : 'Crea Utente' ?></button>
                <?php if ($editUser): ?><a href="<?= BASE_PATH ?>/pages/admin/utenti.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#128101; Utenti (<?= count($utenti) ?>)</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Cognome Nome</th><th>Email</th><th>Ruolo</th><th>Telefono</th><th>Stato</th><th>Azioni</th></tr></thead>
                <tbody>
                    <?php foreach ($utenti as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['cognome'] . ' ' . $u['nome']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge <?= $u['ruolo']==='admin' ? 'badge-primary':'badge-secondary' ?>"><?= $u['ruolo'] ?></span></td>
                        <td><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                        <td><span class="badge <?= $u['attivo'] ? 'badge-success':'badge-danger' ?>"><?= $u['attivo'] ? 'Attivo':'Disattivato' ?></span></td>
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