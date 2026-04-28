<?php
$pageTitle = 'Gestione Classi';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$conn = getConnection();

// Indirizzi predefiniti
$indirizziPredefiniti = [
    'Informatica e Telecomunicazioni',
    'Elettronica ed Elettrotecnica',
    'Meccanica, Meccatronica ed Energia',
    'Chimica, Materiali e Biotecnologie',
    'Amministrazione Finanza e Marketing',
    'Liceo Scientifico',
    'Liceo Classico',
    'Liceo Linguistico',
    'Altro',
];

// Anno scolastico corrente
$annoCorrente = date('n') >= 9 ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crea' || $action === 'modifica') {
        $nome   = strtoupper(trim($_POST['nome'] ?? ''));
        $anno   = trim($_POST['anno_scolastico'] ?? '');
        $ind    = trim($_POST['indirizzo'] ?? '');
        $attivo = isset($_POST['attivo']) ? 1 : 0;
        $errors = [];

        if (!$nome) $errors[] = 'Nome classe obbligatorio.';
        if (!preg_match('/^[0-9][A-Z]{1,3}$/', $nome)) $errors[] = 'Formato nome classe non valido (es: 3A, 4AB).';
        if (!$anno) $errors[] = 'Anno scolastico obbligatorio.';
        if (!preg_match('/^\d{4}\/\d{4}$/', $anno)) $errors[] = "Formato anno scolastico non valido (es: 2025/2026).";
        else {
            [$y1, $y2] = explode('/', $anno);
            if ((int)$y2 !== (int)$y1 + 1) $errors[] = 'Anno scolastico: il secondo anno deve essere il successivo.';
        }

        if (empty($errors)) {
            $n_e   = mysqli_real_escape_string($conn, $nome);
            $a_e   = mysqli_real_escape_string($conn, $anno);
            $i_SQL = $ind ? "'" . mysqli_real_escape_string($conn, $ind) . "'" : 'NULL';

            if ($action === 'crea') {
                $ok = mysqli_query($conn, "INSERT INTO classi (nome, anno_scolastico, indirizzo, attivo) VALUES ('$n_e','$a_e',$i_SQL,$attivo)");
                if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=Classe creata!');
                else     header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=' . urlencode('Classe già esistente per questo anno'));
            } else {
                $id = intval($_POST['id'] ?? 0);
                mysqli_query($conn, "UPDATE classi SET nome='$n_e', anno_scolastico='$a_e', indirizzo=$i_SQL, attivo=$attivo WHERE id=$id");
                header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=Classe aggiornata!');
            }
        } else {
            header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=' . urlencode(implode(' | ', $errors)));
        }
        exit;
    }

    if ($action === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        $ok = mysqli_query($conn, "DELETE FROM classi WHERE id = $id");
        if ($ok) header('Location: ' . BASE_PATH . '/pages/admin/classi.php?success=Classe eliminata!');
        else     header('Location: ' . BASE_PATH . '/pages/admin/classi.php?error=' . urlencode('Impossibile eliminare: classe in uso'));
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
    <div class="card-header"><h3><?= $editClasse ? '&#9998; Modifica Classe' : '&#10133; Nuova Classe' ?></h3></div>
    <div class="card-body">
        <form method="POST" id="formClasse" novalidate>
            <input type="hidden" name="action" value="<?= $editClasse ? 'modifica' : 'crea' ?>">
            <?php if ($editClasse): ?><input type="hidden" name="id" value="<?= $editClasse['id'] ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Classe *</label>
                    <input type="text" id="nome" name="nome" class="form-control" required
                           maxlength="10"
                           pattern="[0-9][A-Za-z]{1,3}"
                           style="text-transform:uppercase"
                           placeholder="Es: 3A, 4AB"
                           value="<?= htmlspecialchars($editClasse['nome'] ?? '') ?>">
                    <div class="form-text">Formato: cifra + lettere (es: 3A, 4AB, 5INF)</div>
                    <div class="field-error" id="err_nome"></div>
                </div>

                <div class="form-group">
                    <label for="anno_scolastico">Anno Scolastico *</label>
                    <select id="anno_scolastico" name="anno_scolastico" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <?php
                        $annoBase = (int)date('Y');
                        $anniFuturi = [];
                        for ($y = $annoBase - 2; $y <= $annoBase + 1; $y++) {
                            $opt = $y . '/' . ($y + 1);
                            $anniFuturi[] = $opt;
                        }
                        foreach ($anniFuturi as $opt):
                            $sel = ($editClasse['anno_scolastico'] ?? $annoCorrente) === $opt ? 'selected' : '';
                        ?>
                            <option value="<?= $opt ?>" <?= $sel ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_anno"></div>
                </div>

                <div class="form-group">
                    <label for="indirizzo">Indirizzo</label>
                    <select id="indirizzo" name="indirizzo" class="form-control">
                        <option value="">-- Nessuno / Non specificato --</option>
                        <?php foreach ($indirizziPredefiniti as $ind): ?>
                            <option value="<?= htmlspecialchars($ind) ?>" <?= ($editClasse['indirizzo'] ?? '') === $ind ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ind) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php
                        // Se l'indirizzo esistente non è in lista, mostralo comunque
                        $ind_attuale = $editClasse['indirizzo'] ?? '';
                        if ($ind_attuale && !in_array($ind_attuale, $indirizziPredefiniti)):
                        ?>
                            <option value="<?= htmlspecialchars($ind_attuale) ?>" selected><?= htmlspecialchars($ind_attuale) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <?php if ($editClasse): ?>
                <div class="form-group">
                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="attivo" value="1" <?= $editClasse['attivo'] ? 'checked' : '' ?>> Classe attiva
                    </label>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><?= $editClasse ? '&#10004; Salva Modifiche' : '&#10133; Crea Classe' ?></button>
                <?php if ($editClasse): ?><a href="<?= BASE_PATH ?>/pages/admin/classi.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>&#127979; Classi (<?= count($classi) ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($classi)): ?>
            <div class="empty-state"><div class="empty-icon">&#127979;</div><h4>Nessuna classe</h4></div>
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
                            <td><span class="badge <?= $c['attivo'] ? 'badge-success' : 'badge-secondary' ?>"><?= $c['attivo'] ? 'Attiva' : 'Disattivata' ?></span></td>
                            <td class="actions">
                                <a href="?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm">&#9998; Modifica</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Eliminare la classe <?= htmlspecialchars($c['nome'], ENT_QUOTES) ?>?');">
                                    <input type="hidden" name="action" value="elimina">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
    const form = document.getElementById('formClasse');
    if (!form) return;

    const nomeEl = document.getElementById('nome');
    if (nomeEl) {
        nomeEl.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

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

    form.addEventListener('submit', function(e) {
        let valid = true;

        const nome = document.getElementById('nome');
        if (nome) {
            const re = /^[0-9][A-Z]{1,3}$/;
            if (!nome.value.trim()) { showError(nome, document.getElementById('err_nome'), 'Nome classe obbligatorio.'); valid = false; }
            else if (!re.test(nome.value.toUpperCase())) { showError(nome, document.getElementById('err_nome'), 'Formato non valido (es: 3A, 4AB).'); valid = false; }
            else clearError(nome, document.getElementById('err_nome'));
        }

        const anno = document.getElementById('anno_scolastico');
        if (anno) {
            if (!anno.value) { showError(anno, document.getElementById('err_anno'), 'Anno scolastico obbligatorio.'); valid = false; }
            else clearError(anno, document.getElementById('err_anno'));
        }

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>