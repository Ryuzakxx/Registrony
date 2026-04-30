<?php
$pageTitle = 'Report PDF';
require_once __DIR__ . '/../../includes/header.php';

$conn   = getConnection();
$userId = (int)getCurrentUserId();

$isResp = isAdmin();
if (!$isResp) {
    $res = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id_responsabile = $userId AND attivo = 1 LIMIT 1");
    $isResp = mysqli_num_rows($res) > 0;
}
if (!$isResp) {
    header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
    exit;
}

if (isAdmin()) {
    $resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
} else {
    $resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE id_responsabile = $userId AND attivo = 1 ORDER BY nome");
}
$labs = [];
while ($r = mysqli_fetch_assoc($resLabs)) $labs[] = $r;
?>

<div class="card" style="max-width:560px;">
    <div class="card-header"><h3>Genera Resoconto PDF</h3></div>
    <div class="card-body">
        <form method="GET" action="<?= BASE_PATH ?>/pages/report/pdf.php" target="_blank">

            <div class="form-group">
                <label>Laboratorio</label>
                <select name="laboratorio" class="form-control">
                    <?php if (isAdmin()): ?>
                    <option value="">Tutti i laboratori</option>
                    <?php endif; ?>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>"><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Periodo</label>
                <select name="tipo" id="tipoPeriodo" class="form-control" onchange="toggleCustom()">
                    <option value="settimana">Ultima settimana</option>
                    <option value="mese">Mese corrente</option>
                    <option value="anno">Anno corrente</option>
                    <option value="custom">Periodo personalizzato</option>
                </select>
            </div>

            <div id="customRange" style="display:none;">
                <div class="form-group">
                    <label>Data inizio</label>
                    <input type="date" name="data_inizio" class="form-control" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="form-group">
                    <label>Data fine</label>
                    <input type="date" name="data_fine" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Scarica PDF
            </button>
        </form>
    </div>
</div>

<script>
function toggleCustom() {
    document.getElementById('customRange').style.display =
        document.getElementById('tipoPeriodo').value === 'custom' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
