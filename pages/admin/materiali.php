<?php
$pageTitle = 'Gestione Materiali';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$conn = getConnection();

// Unità di misura predefinite
$unitaPredefinite = ['pezzi','litri','ml','kg','g','metri','cm','rotoli','confezioni','scatole','flaconi','bottiglie'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome     = trim($_POST['nome'] ?? '');
        $desc     = trim($_POST['descrizione'] ?? '');
        $unita    = trim($_POST['unita_misura'] ?? '');
        $idLab    = intval($_POST['id_laboratorio'] ?? 0);
        $quantita = ($_POST['quantita_disponibile'] ?? '') !== '' ? floatval($_POST['quantita_disponibile']) : null;
        $soglia   = ($_POST['soglia_minima'] ?? '')   !== '' ? floatval($_POST['soglia_minima'])   : null;
        $attivo   = isset($_POST['attivo']) ? 1 : 0;
        $errors   = [];

        if (!$nome)  $errors[] = 'Nome materiale obbligatorio.';
        if (!$idLab) $errors[] = 'Seleziona un laboratorio.';
        if ($quantita !== null && $quantita < 0) $errors[] = 'La quantità disponibile non può essere negativa.';
        if ($soglia   !== null && $soglia < 0)   $errors[] = 'La soglia minima non può essere negativa.';
        if ($soglia !== null && $quantita !== null && $soglia > $quantita) $errors[] = 'La soglia minima non può essere maggiore della quantità disponibile.';

        if (empty($errors)) {
            $n_e     = mysqli_real_escape_string($conn, $nome);
            $d_SQL   = $desc  ? "'" . mysqli_real_escape_string($conn, $desc)  . "'" : 'NULL';
            $u_SQL   = $unita ? "'" . mysqli_real_escape_string($conn, $unita) . "'" : 'NULL';
            $q_SQL   = $quantita !== null ? $quantita : 'NULL';
            $s_SQL   = $soglia   !== null ? $soglia   : 'NULL';

            if ($action === 'crea') {
                mysqli_query($conn, "INSERT INTO materiali (nome, descrizione, unita_misura, id_laboratorio, quantita_disponibile, soglia_minima, attivo) VALUES ('$n_e',$d_SQL,$u_SQL,$idLab,$q_SQL,$s_SQL,$attivo)");
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale creato!');
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn, "UPDATE materiali SET nome='$n_e', descrizione=$d_SQL, unita_misura=$u_SQL, id_laboratorio=$idLab, quantita_disponibile=$q_SQL, soglia_minima=$s_SQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale aggiornato!');
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM materiali WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?success=Materiale eliminato!');
        else     header('Location: ' . BASE_PATH . '/pages/admin/materiali.php?error=' . urlencode('Impossibile eliminare: materiale in uso'));
        exit;
    }
}

$filtroLab = intval($_GET['laboratorio'] ?? 0);
$where     = $filtroLab ? "WHERE m.id_laboratorio = $filtroLab" : '';

$result    = mysqli_query($conn, "SELECT m.*, l.nome AS laboratorio FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id $where ORDER BY l.nome, m.nome");
$materiali = [];
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
    <div class="card-header"><h3><?= $editMat ? '&#9998; Modifica Materiale' : '&#10133; Nuovo Materiale' ?></h3></div>
    <div class="card-body">
        <form method="POST" id="formMateriale" novalidate>
            <input type="hidden" name="action" value="<?= $editMat ? 'modifica' : 'crea' ?>">
            <?php if ($editMat): ?><input type="hidden" name="id" value="<?= $editMat['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Materiale *</label>
                    <input type="text" id="nome" name="nome" class="form-control" required
                           maxlength="150" placeholder="Es: Cavo Ethernet Cat.6"
                           value="<?= htmlspecialchars($editMat['nome'] ?? '') ?>">
                    <div class="field-error" id="err_nome"></div>
                </div>
                <div class="form-group">
                    <label for="id_laboratorio">Laboratorio *</label>
                    <?php if (empty($labs)): ?>
                        <div class="alert alert-warning" style="margin:0">&#9888; Nessun laboratorio attivo.</div>
                    <?php else: ?>
                    <select id="id_laboratorio" name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id'] == ($editMat['id_laboratorio'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lab['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_lab"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="unita_misura">Unità di Misura</label>
                    <select id="unita_misura" name="unita_misura" class="form-control">
                        <option value="">-- Non specificata --</option>
                        <?php
                        $unitaCorrente = $editMat['unita_misura'] ?? '';
                        foreach ($unitaPredefinite as $u):
                        ?>
                            <option value="<?= $u ?>" <?= $unitaCorrente === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                        <?php if ($unitaCorrente && !in_array($unitaCorrente, $unitaPredefinite)): ?>
                            <option value="<?= htmlspecialchars($unitaCorrente) ?>" selected><?= htmlspecialchars($unitaCorrente) ?> (personalizzata)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantita_disponibile">Quantità Disponibile</label>
                    <input type="number" id="quantita_disponibile" name="quantita_disponibile"
                           class="form-control" step="0.01" min="0"
                           placeholder="Es: 50"
                           value="<?= $editMat['quantita_disponibile'] ?? '' ?>">
                    <div class="field-error" id="err_quantita"></div>
                </div>
                <div class="form-group">
                    <label for="soglia_minima">Soglia Minima Scorta</label>
                    <input type="number" id="soglia_minima" name="soglia_minima"
                           class="form-control" step="0.01" min="0"
                           placeholder="Es: 10"
                           value="<?= $editMat['soglia_minima'] ?? '' ?>">
                    <div class="form-text">Sotto questa soglia viene segnalato &ldquo;In esaurimento&rdquo;.</div>
                    <div class="field-error" id="err_soglia"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" class="form-control" rows="2"
                          maxlength="500" placeholder="Descrizione opzionale..."><?= htmlspecialchars($editMat['descrizione'] ?? '') ?></textarea>
            </div>

            <?php if ($editMat): ?>
                <div class="form-group">
                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="attivo" value="1" <?= $editMat['attivo'] ? 'checked' : '' ?>> Materiale attivo
                    </label>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editMat ? '&#10004; Salva Modifiche' : '&#10133; Crea Materiale' ?></button>
                <?php if ($editMat): ?><a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
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
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab == $lab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($lab['nome']) ?></option>
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
            <div class="empty-state"><div class="empty-icon">&#128206;</div><h4>Nessun materiale</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Nome</th><th>Laboratorio</th><th>Unità</th><th>Disponibile</th><th>Soglia</th><th>Stato</th><th>Azioni</th></tr></thead>
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
                                <a href="?edit=<?= $m['id'] ?><?= $filtroLab ? '&laboratorio='.$filtroLab : '' ?>" class="btn btn-primary btn-sm">&#9998; Modifica</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Eliminare il materiale <?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>?');">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">&#128465; Elimina</button>
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

<style>
.field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display: none; }
.form-control.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(220,38,38,0.12); }
.form-control.is-valid   { border-color: var(--success); }
</style>

<script>
(function() {
    const form = document.getElementById('formMateriale');
    if (!form) return;

    function showError(input, errEl, msg) {
        if (!errEl) return;
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        errEl.textContent = msg;
        errEl.style.display = 'block';
    }
    function clearError(input, errEl) {
        if (!errEl) return;
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        errEl.textContent = '';
        errEl.style.display = 'none';
    }

    // Validazione live quantità vs soglia
    const qEl = document.getElementById('quantita_disponibile');
    const sEl = document.getElementById('soglia_minima');
    function checkQS() {
        if (qEl && sEl && qEl.value !== '' && sEl.value !== '') {
            if (parseFloat(sEl.value) > parseFloat(qEl.value)) {
                showError(sEl, document.getElementById('err_soglia'), 'La soglia non può superare la quantità disponibile.');
            } else {
                clearError(sEl, document.getElementById('err_soglia'));
            }
        }
    }
    if (qEl) qEl.addEventListener('input', checkQS);
    if (sEl) sEl.addEventListener('input', checkQS);

    form.addEventListener('submit', function(e) {
        let valid = true;

        const nome = document.getElementById('nome');
        if (nome && !nome.value.trim()) { showError(nome, document.getElementById('err_nome'), 'Nome obbligatorio.'); valid = false; }
        else if (nome) clearError(nome, document.getElementById('err_nome'));

        const lab = document.getElementById('id_laboratorio');
        if (lab && !lab.value) { showError(lab, document.getElementById('err_lab'), 'Seleziona un laboratorio.'); valid = false; }
        else if (lab) clearError(lab, document.getElementById('err_lab'));

        if (qEl && qEl.value !== '' && parseFloat(qEl.value) < 0) { showError(qEl, document.getElementById('err_quantita'), 'Quantità non può essere negativa.'); valid = false; }
        else if (qEl) clearError(qEl, document.getElementById('err_quantita'));

        checkQS();
        if (sEl && sEl.classList.contains('is-invalid')) valid = false;

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>