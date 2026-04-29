<?php
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getConnection();
$L    = lang();
$id   = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_PATH . '/pages/segnalazioni/index.php?error=' . urlencode('Segnalazione non trovata'));
    exit;
}

// Carica la segnalazione prima di tutto (serve id_laboratorio per il check permessi)
$result       = mysqli_query($conn, "SELECT sg.*, l.nome AS laboratorio, l.aula, l.id_responsabile, l.id_assistente_tecnico, CONCAT(u.cognome, ' ', u.nome) AS segnalato_da FROM segnalazioni sg JOIN laboratori l ON sg.id_laboratorio = l.id JOIN utenti u ON sg.id_utente = u.id WHERE sg.id = $id");
$segnalazione = mysqli_fetch_assoc($result);

if (!$segnalazione) {
    header('Location: ' . BASE_PATH . '/pages/segnalazioni/index.php?error=' . urlencode('Segnalazione non trovata'));
    exit;
}

/**
 * Permesso di gestione: admin, responsabile del laboratorio O assistente tecnico.
 * I docenti ordinari possono solo visualizzare.
 * Usa canGestireSegnalazioni() definita in config/auth.php.
 */
$canManage = canGestireSegnalazioni((int)$segnalazione['id_laboratorio']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $nuovoStato      = mysqli_real_escape_string($conn, $_POST['stato'] ?? '');
    $noteRisoluzione = mysqli_real_escape_string($conn, trim($_POST['note_risoluzione'] ?? ''));
    $validStati      = ['aperta', 'in_lavorazione', 'risolta', 'chiusa'];

    if (in_array($nuovoStato, $validStati)) {
        $dataRisoluzione = in_array($nuovoStato, ['risolta', 'chiusa']) ? "'" . date('Y-m-d H:i:s') . "'" : 'NULL';
        $noteSQL         = $noteRisoluzione ? "'$noteRisoluzione'" : 'NULL';
        mysqli_query($conn, "UPDATE segnalazioni SET stato = '$nuovoStato', note_risoluzione = $noteSQL, data_risoluzione = $dataRisoluzione WHERE id = $id");
        header('Location: ' . BASE_PATH . '/pages/segnalazioni/dettaglio.php?id=' . $id . '&success=' . urlencode('Stato aggiornato!'));
        exit;
    }
}

$pageTitle = 'Dettaglio Segnalazione';
require_once __DIR__ . '/../../includes/header.php';

$sc = match($segnalazione['stato']) {
    'aperta'        => 'badge-danger',
    'in_lavorazione'=> 'badge-warning',
    'risolta'       => 'badge-success',
    default         => 'badge-secondary'
};
$bc = match($segnalazione['priorita']) {
    'urgente' => 'badge-danger',
    'alta'    => 'badge-warning',
    'media'   => 'badge-info',
    default   => 'badge-secondary'
};

// Etichetta ruolo per il pannello gestione
$gestoreLabel = '';
if ($canManage && !isAdmin()) {
    $userId = (int)getCurrentUserId();
    if ((int)$segnalazione['id_assistente_tecnico'] === $userId) {
        $gestoreLabel = '(Assistente Tecnico)';
    } elseif ((int)$segnalazione['id_responsabile'] === $userId) {
        $gestoreLabel = '(Responsabile laboratorio)';
    }
}
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Segnalazione #<?= $id ?></h3>
        <span class="badge <?= $sc ?>" style="font-size:13px;padding:6px 14px;"><?= str_replace('_', ' ', ucfirst($segnalazione['stato'])) ?></span>
    </div>
    <div class="card-body">
        <div class="form-row mb-2">
            <div><strong>Laboratorio:</strong><br><?= htmlspecialchars($segnalazione['laboratorio']) ?> (<?= htmlspecialchars($segnalazione['aula']) ?>)</div>
            <div><strong>Priorità:</strong><br><span class="badge <?= $bc ?>"><?= htmlspecialchars($segnalazione['priorita']) ?></span></div>
            <div><strong>Segnalato da:</strong><br><?= htmlspecialchars($segnalazione['segnalato_da']) ?></div>
            <div><strong>Data:</strong><br><?= date('d/m/Y H:i', strtotime($segnalazione['data_segnalazione'])) ?></div>
        </div>
        <div class="mb-2"><strong>Titolo:</strong>
            <h4><?= htmlspecialchars($segnalazione['titolo']) ?></h4>
        </div>
        <div class="mb-2">
            <strong>Descrizione:</strong>
            <p style="white-space:pre-wrap;background:#f8fafc;padding:12px;border-radius:6px;border:1px solid var(--border);"><?= htmlspecialchars($segnalazione['descrizione']) ?></p>
        </div>
        <?php if ($segnalazione['data_risoluzione']): ?>
            <div class="mb-2"><strong>Data risoluzione:</strong> <?= date('d/m/Y H:i', strtotime($segnalazione['data_risoluzione'])) ?></div>
        <?php endif; ?>
        <?php if ($segnalazione['note_risoluzione']): ?>
            <div class="mb-2">
                <strong>Note risoluzione:</strong>
                <p style="white-space:pre-wrap;background:var(--success-light);padding:12px;border-radius:6px;"><?= htmlspecialchars($segnalazione['note_risoluzione']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canManage): ?>
<div class="card">
    <div class="card-header">
        <h3>&#128736; Gestione Segnalazione
            <?php if (!isAdmin() && $gestoreLabel): ?>
                <small style="font-weight:normal;font-size:13px;opacity:.7"><?= htmlspecialchars($gestoreLabel) ?></small>
            <?php endif; ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Cambia Stato</label>
                    <select name="stato" class="form-control" required>
                        <option value="aperta"          <?= $segnalazione['stato'] === 'aperta'          ? 'selected' : '' ?>>Aperta</option>
                        <option value="in_lavorazione"  <?= $segnalazione['stato'] === 'in_lavorazione'  ? 'selected' : '' ?>>In lavorazione</option>
                        <option value="risolta"         <?= $segnalazione['stato'] === 'risolta'         ? 'selected' : '' ?>>Risolta</option>
                        <option value="chiusa"          <?= $segnalazione['stato'] === 'chiusa'          ? 'selected' : '' ?>>Chiusa</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Note Risoluzione</label>
                <textarea name="note_risoluzione" class="form-control" rows="3" placeholder="Descrivi come è stato risolto il problema..."><?= htmlspecialchars($segnalazione['note_risoluzione'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Aggiorna Stato</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert" style="background:#f0f8ff;border:1px solid #c0d8f0;color:#4a6f8a;border-radius:6px;padding:12px 16px;font-size:.9rem;">
    &#128274; Solo il tecnico o il responsabile del laboratorio possono gestire questa segnalazione.
</div>
<?php endif; ?>

<div class="mt-2">
    <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary">&#8592; Torna alle segnalazioni</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
