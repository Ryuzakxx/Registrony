<?php
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome        = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $aula        = mysqli_real_escape_string($conn, trim($_POST['aula'] ?? ''));
        $idAss       = intval($_POST['id_assistente_tecnico'] ?? 0);
        $idResp      = intval($_POST['id_responsabile']       ?? 0);
        $descrizione = mysqli_real_escape_string($conn, trim($_POST['descrizione'] ?? ''));
        $attivo      = isset($_POST['attivo']) ? 1 : 0;
        $descSQL     = $descrizione ? "'$descrizione'" : 'NULL';

        $errors = [];

        if (!$nome) $errors[] = 'Il nome è obbligatorio.';
        if (!$aula) $errors[] = 'L\'aula è obbligatoria.';

        /* Assistente tecnico: deve esistere e avere ruolo 'tecnico' */
        if (!$idAss) {
            $errors[] = 'L\'assistente tecnico è obbligatorio: ogni laboratorio deve avere un tecnico assegnato.';
        } else {
            $cAss = mysqli_query($conn,
                "SELECT id FROM utenti WHERE id = $idAss AND ruolo = 'tecnico' AND attivo = 1 LIMIT 1");
            if (mysqli_num_rows($cAss) === 0)
                $errors[] = 'L\'assistente tecnico selezionato non è un tecnico attivo.';
        }

        /* Responsabile: deve esistere e avere ruolo 'docente' */
        if (!$idResp) {
            $errors[] = 'Il responsabile è obbligatorio: ogni laboratorio deve avere un responsabile.';
        } else {
            $cResp = mysqli_query($conn,
                "SELECT id FROM utenti WHERE id = $idResp AND ruolo = 'docente' AND attivo = 1 LIMIT 1");
            if (mysqli_num_rows($cResp) === 0)
                $errors[] = 'Il responsabile selezionato deve essere un docente attivo.';
        }

        if (empty($errors)) {
            if ($action === 'crea') {
                mysqli_query($conn,
                    "INSERT INTO laboratori (nome, aula, id_assistente_tecnico, id_responsabile, descrizione, attivo)"
                    . " VALUES ('$nome','$aula',$idAss,$idResp,$descSQL,$attivo)");
                header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=' . urlencode('Laboratorio creato!'));
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn,
                    "UPDATE laboratori SET nome='$nome', aula='$aula',"
                    . " id_assistente_tecnico=$idAss, id_responsabile=$idResp,"
                    . " descrizione=$descSQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=' . urlencode('Laboratorio aggiornato!'));
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM laboratori WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=' . urlencode('Laboratorio eliminato!'));
        else     header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?error='   . urlencode('Impossibile eliminare: laboratorio in uso'));
        exit;
    }
}

/* ================================================================
   READ
   ================================================================ */
$pageTitle = 'Gestione Laboratori';
require_once __DIR__ . '/../../includes/header.php';

$result = mysqli_query($conn, "
    SELECT l.*,
           CONCAT(a.cognome,' ',a.nome) AS assistente,
           CONCAT(r.cognome,' ',r.nome) AS responsabile
    FROM laboratori l
    JOIN utenti a ON l.id_assistente_tecnico = a.id
    JOIN utenti r ON l.id_responsabile       = r.id
    ORDER BY l.nome
");
$labs = [];
while ($row = mysqli_fetch_assoc($result)) $labs[] = $row;

/* Assistenti tecnici: solo utenti con ruolo 'tecnico' */
$resTecnici = mysqli_query($conn,
    "SELECT id, nome, cognome FROM utenti WHERE ruolo = 'tecnico' AND attivo = 1 ORDER BY cognome, nome");
$tecnici = [];
while ($row = mysqli_fetch_assoc($resTecnici)) $tecnici[] = $row;

/* Responsabili: solo docenti attivi */
$resDocenti = mysqli_query($conn,
    "SELECT id, nome, cognome FROM utenti WHERE ruolo = 'docente' AND attivo = 1 ORDER BY cognome, nome");
$docenti = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

$editLab = null;
if (isset($_GET['edit'])) {
    $editId  = intval($_GET['edit']);
    $res     = mysqli_query($conn, "SELECT * FROM laboratori WHERE id = $editId");
    $editLab = mysqli_fetch_assoc($res);
}
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?= $editLab ? 'Modifica Laboratorio' : 'Nuovo Laboratorio' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editLab ? 'modifica':'crea' ?>">
            <?php if ($editLab): ?><input type="hidden" name="id" value="<?= $editLab['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?= htmlspecialchars($editLab['nome'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Aula *</label>
                    <input type="text" name="aula" class="form-control" required
                           value="<?= htmlspecialchars($editLab['aula'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        Assistente Tecnico *
                        <small class="text-muted">(tecnico)</small>
                    </label>
                    <select name="id_assistente_tecnico" class="form-control" required>
                        <option value="">-- Seleziona tecnico --</option>
                        <?php if (empty($tecnici)): ?>
                            <option disabled>Nessun tecnico attivo disponibile</option>
                        <?php else: ?>
                            <?php foreach ($tecnici as $t): ?>
                                <option value="<?= $t['id'] ?>"
                                    <?= ($t['id'] == ($editLab['id_assistente_tecnico'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['cognome'] . ' ' . $t['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        Responsabile *
                        <small class="text-muted">(docente)</small>
                    </label>
                    <select name="id_responsabile" class="form-control" required>
                        <option value="">-- Seleziona docente --</option>
                        <?php if (empty($docenti)): ?>
                            <option disabled>Nessun docente attivo disponibile</option>
                        <?php else: ?>
                            <?php foreach ($docenti as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= ($d['id'] == ($editLab['id_responsabile'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['cognome'] . ' ' . $d['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Descrizione</label>
                <textarea name="descrizione" class="form-control" rows="2"><?= htmlspecialchars($editLab['descrizione'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label style="font-weight:normal;display:flex;align-items:center;gap:6px;">
                    <input type="checkbox" name="attivo" value="1"
                           <?= ($editLab['attivo'] ?? 1) ? 'checked' : '' ?>> Attivo
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <?= $editLab ? 'Salva Modifiche' : 'Crea Laboratorio' ?>
                </button>
                <?php if ($editLab): ?>
                    <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php" class="btn btn-secondary">Annulla</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Laboratori (<?= count($labs) ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($labs)): ?>
            <div class="empty-state"><h4>Nessun laboratorio</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th><th>Aula</th>
                            <th>Assistente Tecnico</th><th>Responsabile</th>
                            <th>Stato</th><th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labs as $l): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($l['nome']) ?></strong>
                                <?php if ($l['descrizione']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($l['descrizione']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($l['aula']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    &#9881; <?= htmlspecialchars($l['assistente']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    &#9733; <?= htmlspecialchars($l['responsabile']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $l['attivo'] ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= $l['attivo'] ? 'Attivo' : 'Disattivato' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $l['id'] ?>" class="btn btn-primary btn-sm">Modifica</a>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo laboratorio?')">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
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
