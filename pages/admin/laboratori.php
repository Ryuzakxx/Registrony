<?php
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea') {
        $nome     = trim($_POST['nome']     ?? '');
        $aula     = trim($_POST['aula']     ?? '');
        $note     = trim($_POST['note']     ?? '');
        $resp     = intval($_POST['id_responsabile']       ?? 0);
        $tecnico  = intval($_POST['id_assistente_tecnico'] ?? 0);

        if ($nome && $aula) {
            $n = mysqli_real_escape_string($conn, $nome);
            $a = mysqli_real_escape_string($conn, $aula);
            $no = mysqli_real_escape_string($conn, $note);
            $r_sql  = $resp    ? $resp    : 'NULL';
            $t_sql  = $tecnico ? $tecnico : 'NULL';
            mysqli_query($conn, "INSERT INTO laboratori (nome, aula, note, id_responsabile, id_assistente_tecnico) VALUES ('$n','$a','$no',$r_sql,$t_sql)");
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=Laboratorio+creato');
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?error=Nome+e+aula+obbligatori');
        }
        exit;
    }

    if ($action === 'modifica') {
        $id      = intval($_POST['id']   ?? 0);
        $nome    = trim($_POST['nome']   ?? '');
        $aula    = trim($_POST['aula']   ?? '');
        $note    = trim($_POST['note']   ?? '');
        $attivo  = isset($_POST['attivo']) ? 1 : 0;
        $resp    = intval($_POST['id_responsabile']       ?? 0);
        $tecnico = intval($_POST['id_assistente_tecnico'] ?? 0);

        // Vincolo: un tecnico NON può essere il responsabile dello stesso laboratorio
        if ($resp && $tecnico && $resp === $tecnico) {
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?edit=' . $id . '&error=' . urlencode('Il responsabile e il tecnico non possono essere la stessa persona.'));
            exit;
        }

        // Vincolo: il responsabile deve avere ruolo docente o admin
        if ($resp) {
            $rr = mysqli_query($conn, "SELECT ruolo FROM utenti WHERE id=$resp LIMIT 1");
            $rrow = mysqli_fetch_assoc($rr);
            if ($rrow && $rrow['ruolo'] === 'tecnico') {
                header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?edit=' . $id . '&error=' . urlencode('Un assistente tecnico non può essere impostato come responsabile del laboratorio.'));
                exit;
            }
        }

        if ($nome && $aula && $id) {
            $n = mysqli_real_escape_string($conn, $nome);
            $a = mysqli_real_escape_string($conn, $aula);
            $no = mysqli_real_escape_string($conn, $note);
            $r_sql  = $resp    ? $resp    : 'NULL';
            $t_sql  = $tecnico ? $tecnico : 'NULL';
            mysqli_query($conn, "UPDATE laboratori SET nome='$n', aula='$a', note='$no', attivo=$attivo, id_responsabile=$r_sql, id_assistente_tecnico=$t_sql WHERE id=$id");
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=Laboratorio+aggiornato');
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?edit=' . $id . '&error=Dati+non+validi');
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        mysqli_query($conn, "UPDATE laboratori SET attivo=0 WHERE id=$id");
        header('Location: ' . BASE_PATH . '/pages/admin/laboratori.php?success=Laboratorio+disattivato');
        exit;
    }
}

$pageTitle = 'Gestione Laboratori';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/form_helpers.php';

if (isset($_GET['edit'])) {
    $editId  = intval($_GET['edit']);
    $resEdit = mysqli_query($conn, "SELECT * FROM laboratori WHERE id=$editId LIMIT 1");
    $editLab = mysqli_fetch_assoc($resEdit);
} else {
    $editLab = null;
}
$isEdit = $editLab !== null;

$resLabs = mysqli_query($conn, "SELECT l.*, u.nome AS resp_nome, u.cognome AS resp_cognome, t.nome AS tec_nome, t.cognome AS tec_cognome FROM laboratori l LEFT JOIN utenti u ON l.id_responsabile=u.id LEFT JOIN utenti t ON l.id_assistente_tecnico=t.id ORDER BY l.attivo DESC, l.nome");
$labs = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$resDocenti = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo=1 AND ruolo IN ('docente','admin') ORDER BY cognome, nome");
$docenti = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

$resTecnici = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo=1 AND ruolo='tecnico' ORDER BY cognome, nome");
$tecnici = [];
while ($row = mysqli_fetch_assoc($resTecnici)) $tecnici[] = $row;

?>

<?php formFieldStyles(); ?>

<div class="card">
    <div class="card-header">
        <h3><?= $isEdit ? 'Modifica Laboratorio' : 'Nuovo Laboratorio' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $isEdit ? 'modifica' : 'crea' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$editLab['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <?php
                formField('nome', 'Nome laboratorio', [
                    'value'    => $editLab['nome'] ?? '',
                    'required' => true,
                    'max'      => 150,
                ]);
                formField('aula', 'Aula / Ubicazione', [
                    'value'    => $editLab['aula'] ?? '',
                    'required' => true,
                    'max'      => 50,
                ]);
                ?>
            </div>

            <?php
            $docMap = ['' => '— Nessun responsabile'];
            foreach ($docenti as $d) $docMap[$d['id']] = $d['cognome'] . ' ' . $d['nome'];
            formSelect('id_responsabile', 'Responsabile', $docMap, [
                'selected' => (string)($editLab['id_responsabile'] ?? ''),
                'hint'     => 'Docente o admin responsabile del laboratorio.',
            ]);

            $tecMap = ['' => '— Nessun assistente tecnico'];
            foreach ($tecnici as $t) $tecMap[$t['id']] = $t['cognome'] . ' ' . $t['nome'];
            formSelect('id_assistente_tecnico', 'Assistente Tecnico', $tecMap, [
                'selected' => (string)($editLab['id_assistente_tecnico'] ?? ''),
                'hint'     => 'Il tecnico che gestisce i materiali del laboratorio.',
            ]);
            ?>

            <?php
            formTextarea('note', 'Note', [
                'value'       => $editLab['note'] ?? '',
                'placeholder' => 'Eventuali note sul laboratorio...',
                'rows'        => 2,
            ]);
            ?>

            <?php if ($isEdit): ?>
                <?php formCheckbox('attivo', 'Laboratorio attivo', (bool)$editLab['attivo']); ?>
            <?php endif; ?>

            <div class="d-flex gap-2" style="margin-top:16px;">
                <button type="submit" class="btn btn-success"><?= $isEdit ? 'Salva modifiche' : 'Crea laboratorio' ?></button>
                <?php if ($isEdit): ?>
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
            <p class="text-muted">Nessun laboratorio.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Aula</th>
                            <th>Responsabile</th>
                            <th>Ass. Tecnico</th>
                            <th>Note</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labs as $lab): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($lab['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($lab['aula']) ?></td>
                            <td><?= $lab['resp_cognome'] ? htmlspecialchars($lab['resp_cognome'] . ' ' . $lab['resp_nome']) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= $lab['tec_cognome'] ? htmlspecialchars($lab['tec_cognome'] . ' ' . $lab['tec_nome']) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= htmlspecialchars($lab['note'] ?? '') ?></td>
                            <td>
                                <span class="badge <?= $lab['attivo'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $lab['attivo'] ? 'Attivo' : 'Inattivo' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $lab['id'] ?>" class="btn btn-primary btn-sm">Modifica</a>
                                <?php if ($lab['attivo']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Disattivare il laboratorio?')">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $lab['id'] ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Disattiva</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php formFieldScripts(); ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
