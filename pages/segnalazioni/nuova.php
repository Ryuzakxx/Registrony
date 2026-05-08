<?php
/* ================================================================
   POST logic before any HTML output
   ================================================================ */
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$conn = getConnection();

$errors = [];
$preselectedLab      = intval($_GET['id_laboratorio'] ?? 0);
$preselectedSessione = intval($_GET['id_sessione'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idLab      = intval($_POST['id_laboratorio'] ?? 0);
    $idSessione = intval($_POST['id_sessione'] ?? 0) ?: null;
    $titolo     = trim($_POST['titolo'] ?? '');
    $descrizione= trim($_POST['descrizione'] ?? '');
    $priorita   = $_POST['priorita'] ?? 'media';

    if (!in_array($priorita, ['bassa','media','alta','urgente'])) $priorita = 'media';

    if (!$idLab)                        $errors[] = 'Seleziona un laboratorio.';
    if (!$titolo)                       $errors[] = 'Inserisci un titolo.';
    if (strlen($titolo) > 255)          $errors[] = 'Titolo: max 255 caratteri.';
    if (!$descrizione)                  $errors[] = 'Inserisci una descrizione del problema.';
    if (strlen($descrizione) > 2000)    $errors[] = 'Descrizione: max 2000 caratteri.';

    if (empty($errors)) {
        $t_e  = mysqli_real_escape_string($conn, $titolo);
        $d_e  = mysqli_real_escape_string($conn, $descrizione);
        $p_e  = mysqli_real_escape_string($conn, $priorita);
        $id_s = $idSessione ? $idSessione : 'NULL';
        $uid  = getCurrentUserId();
        mysqli_query($conn, "INSERT INTO segnalazioni (id_laboratorio, id_sessione, id_utente, titolo, descrizione, priorita) VALUES ($idLab, $id_s, $uid, '$t_e', '$d_e', '$p_e')");
        header('Location: ' . BASE_PATH . '/pages/segnalazioni/index.php?success=' . urlencode('Segnalazione inviata con successo!'));
        exit;
    }
}

$resLabs = mysqli_query($conn, "SELECT id, nome, aula FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs    = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$pageTitle = 'Nuova Segnalazione';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>&#10060; <?= htmlspecialchars($err) ?><br><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>&#9888; Nuova Segnalazione Problema</h3>
    </div>
    <div class="card-body">
        <form method="POST" id="formSegnalazione" novalidate>

            <div class="form-row">
                <div class="form-group">
                    <label for="id_laboratorio">Laboratorio *</label>
                    <?php if (empty($labs)): ?>
                        <div class="alert alert-warning" style="margin:0">&#9888; Nessun laboratorio attivo.</div>
                    <?php else: ?>
                    <select id="id_laboratorio" name="id_laboratorio" class="form-control" required>
                        <option value="">-- Seleziona laboratorio --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= ($lab['id'] == ($_POST['id_laboratorio'] ?? $preselectedLab)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lab['nome'] . ' (' . $lab['aula'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-error" id="err_lab"></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="priorita">Priorità *</label>
                    <select id="priorita" name="priorita" class="form-control" required>
                        <option value="bassa"   <?= ($_POST['priorita'] ?? '') === 'bassa'   ? 'selected' : '' ?>>&#9660; Bassa</option>
                        <option value="media"   <?= ($_POST['priorita'] ?? 'media') === 'media' ? 'selected' : '' ?>>&#9644; Media</option>
                        <option value="alta"    <?= ($_POST['priorita'] ?? '') === 'alta'    ? 'selected' : '' ?>>&#9650; Alta</option>
                        <option value="urgente" <?= ($_POST['priorita'] ?? '') === 'urgente' ? 'selected' : '' ?>>&#128308; Urgente</option>
                    </select>
                </div>
            </div>

            <?php if ($preselectedSessione): ?>
                <input type="hidden" name="id_sessione" value="<?= $preselectedSessione ?>">
                <div class="alert alert-info" style="font-size:13px">&#128203; Segnalazione collegata alla sessione #<?= $preselectedSessione ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="titolo">Titolo * <span class="text-muted" style="font-weight:normal">(max 255 car.)</span></label>
                <input type="text" id="titolo" name="titolo" class="form-control" required
                       maxlength="255"
                       placeholder="Es: PC postazione 5 non funziona"
                       value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>">
                <div class="field-error" id="err_titolo"></div>
            </div>

            <div class="form-group">
                <label for="descrizione">Descrizione del problema * <span class="text-muted" style="font-weight:normal">(max 2000 car.)</span></label>
                <textarea id="descrizione" name="descrizione" class="form-control" rows="5" required
                          maxlength="2000"
                          placeholder="Descrivi il problema in dettaglio: cosa non funziona, da quando, eventuali tentativi di risoluzione..."><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
                <div class="form-text char-counter" data-target="descrizione" data-max="2000">0 / 2000</div>
                <div class="field-error" id="err_descrizione"></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">&#9888; Invia Segnalazione</button>
                <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary">Annulla</a>
            </div>
        </form>
    </div>
</div>

<style>
.field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display: none; }
.form-control.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(220,38,38,0.12); }
.form-control.is-valid   { border-color: var(--success); }
</style>

<script>
(function() {
    // Char counter
    document.querySelectorAll('.char-counter').forEach(function(el) {
        const t   = document.getElementById(el.dataset.target);
        const max = parseInt(el.dataset.max);
        if (!t) return;
        function upd() {
            const l = t.value.length;
            el.textContent = l + ' / ' + max;
            el.style.color = l > max * 0.9 ? (l >= max ? 'var(--danger)' : 'var(--warning)') : '';
        }
        t.addEventListener('input', upd); upd();
    });

    const form = document.getElementById('formSegnalazione');
    if (!form) return;

    function showError(input, errEl, msg) {
        if (!errEl) return;
        input.classList.add('is-invalid'); input.classList.remove('is-valid');
        errEl.textContent = msg; errEl.style.display = 'block';
    }
    function clearError(input, errEl) {
        if (!errEl) return;
        input.classList.remove('is-invalid'); input.classList.add('is-valid');
        errEl.textContent = ''; errEl.style.display = 'none';
    }

    form.addEventListener('submit', function(e) {
        let valid = true;

        const lab = document.getElementById('id_laboratorio');
        if (lab) { if (!lab.value) { showError(lab, document.getElementById('err_lab'), 'Seleziona un laboratorio.'); valid = false; } else clearError(lab, document.getElementById('err_lab')); }

        const titolo = document.getElementById('titolo');
        if (titolo) { if (!titolo.value.trim()) { showError(titolo, document.getElementById('err_titolo'), 'Inserisci un titolo.'); valid = false; } else clearError(titolo, document.getElementById('err_titolo')); }

        const desc = document.getElementById('descrizione');
        if (desc) { if (!desc.value.trim()) { showError(desc, document.getElementById('err_descrizione'), 'Inserisci una descrizione.'); valid = false; } else clearError(desc, document.getElementById('err_descrizione')); }

        if (!valid) e.preventDefault();
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
