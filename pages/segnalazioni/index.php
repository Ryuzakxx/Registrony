<?php
$pageTitle = L('segn_titolo_lista');
require_once __DIR__ . '/../../includes/header.php';

$conn   = getConnection();
$userId = (int)getCurrentUserId();

/*
 * Lab forzato per ruolo:
 *   Docente  -> solo il lab selezionato in sessione
 *   Tecnico  -> solo i lab di cui è assistente
 *   Admin    -> tutti, filtro libero
 */
$labForzatoId   = null;
$labForzatoNome = null;
$labsConsentiti = null;

if (isDocente()) {
    requireLabSelected();
    $labForzatoId = (int)getSelectedLabId();
    $r = mysqli_query($conn, "SELECT nome FROM laboratori WHERE id = $labForzatoId LIMIT 1");
    $labForzatoNome = mysqli_fetch_row($r)[0] ?? '';
} elseif (isTecnico()) {
    $techLabs = getTechnicianLabs($userId);
    $labsConsentiti = array_column($techLabs, 'id');
}

/* Filtri da GET */
$filtroStato = mysqli_real_escape_string($conn, $_GET['stato']       ?? '');
$filtroLab   = ($labForzatoId !== null) ? $labForzatoId
             : intval($_GET['laboratorio'] ?? 0);

$where = [];
if ($filtroStato) $where[] = "sg.stato = '$filtroStato'";
if ($filtroLab)   $where[] = "sg.id_laboratorio = $filtroLab";
if ($labsConsentiti !== null && !empty($labsConsentiti)) {
    $ids = implode(',', $labsConsentiti);
    $where[] = "sg.id_laboratorio IN ($ids)";
} elseif ($labsConsentiti !== null && empty($labsConsentiti)) {
    $where[] = '1=0';
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$result = mysqli_query($conn, "
    SELECT sg.*, l.nome AS laboratorio, CONCAT(u.cognome, ' ', u.nome) AS segnalato_da
    FROM segnalazioni sg
    JOIN laboratori l ON sg.id_laboratorio = l.id
    JOIN utenti u ON sg.id_utente = u.id
    $whereSQL
    ORDER BY FIELD(sg.priorita, 'urgente','alta','media','bassa'), sg.data_segnalazione DESC
");
$segnalazioni = [];
while ($row = mysqli_fetch_assoc($result)) $segnalazioni[] = $row;

/* Lab per filtro (solo admin / tecnico multi-lab) */
$labs = [];
if (isAdmin()) {
    $r2 = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
    while ($r = mysqli_fetch_assoc($r2)) $labs[] = $r;
} elseif ($labsConsentiti && count($labsConsentiti) > 1) {
    $ids = implode(',', $labsConsentiti);
    $r2  = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE id IN ($ids) ORDER BY nome");
    while ($r = mysqli_fetch_assoc($r2)) $labs[] = $r;
}
?>

<?php if ($labForzatoId): ?>
<div class="lab-lock-banner">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <?= L('segn_titolo_lista') ?> <strong><?= htmlspecialchars($labForzatoNome) ?></strong>
    &nbsp;···&nbsp;
    <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php" class="lab-lock-change"><?= L('nav_cambia_lab') ?></a>
</div>
<?php endif; ?>

<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
            <div class="form-group" style="margin-bottom:0">
                <label><?= L('segn_filtra_stato') ?></label>
                <select name="stato" class="form-control">
                    <option value=""><?= L('tutti') ?></option>
                    <option value="aperta"         <?= $filtroStato==='aperta'         ? 'selected':'' ?>><?= L('segn_stato_aperta') ?></option>
                    <option value="in_lavorazione"  <?= $filtroStato==='in_lavorazione' ? 'selected':'' ?>><?= L('segn_stato_in_lavorazione') ?></option>
                    <option value="risolta"        <?= $filtroStato==='risolta'        ? 'selected':'' ?>><?= L('segn_stato_risolta') ?></option>
                    <option value="chiusa"         <?= $filtroStato==='chiusa'         ? 'selected':'' ?>><?= L('segn_stato_chiusa') ?></option>
                </select>
            </div>
            <?php if (!empty($labs)): ?>
            <div class="form-group" style="margin-bottom:0">
                <label><?= L('segn_filtra_lab') ?></label>
                <select name="laboratorio" class="form-control">
                    <option value=""><?= L('tutti') ?></option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab==$lab['id'] ? 'selected':'' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div style="margin-top:22px">
                <button type="submit" class="btn btn-primary btn-sm"><?= L('filtra') ?></button>
                <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary btn-sm"><?= L('reset') ?></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>&#9888; <?= L('segn_titolo_lista') ?> (<?= count($segnalazioni) ?>)</h3>
        <a href="<?= BASE_PATH ?>/pages/segnalazioni/nuova.php" class="btn btn-warning btn-sm"><?= L('segn_btn_nuova') ?></a>
    </div>
    <div class="card-body">
        <?php if (empty($segnalazioni)): ?>
            <div class="empty-state"><div class="empty-icon">&#10004;</div><h4><?= L('segn_nessuna') ?></h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= L('segn_titolo_campo') ?></th>
                            <?php if (!$labForzatoId): ?><th><?= L('segn_lab') ?></th><?php endif; ?>
                            <th><?= L('segn_priorita') ?></th>
                            <th><?= L('segn_stato') ?></th>
                            <th><?= L('segn_segnalato_da') ?></th>
                            <th><?= L('segn_data') ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segnalazioni as $sg): ?>
                        <?php
                            $bc = match($sg['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' };
                            $sc = match($sg['stato']) { 'aperta'=>'badge-danger','in_lavorazione'=>'badge-warning','risolta'=>'badge-success',default=>'badge-secondary' };
                            $prioLabel  = match($sg['priorita']) { 'urgente'=>L('segn_prio_urgente'),'alta'=>L('segn_prio_alta'),'media'=>L('segn_prio_media'),default=>L('segn_prio_bassa') };
                            $statoLabel = match($sg['stato']) { 'aperta'=>L('segn_stato_aperta'),'in_lavorazione'=>L('segn_stato_in_lavorazione'),'risolta'=>L('segn_stato_risolta'),default=>L('segn_stato_chiusa') };
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sg['titolo']) ?></strong></td>
                            <?php if (!$labForzatoId): ?>
                                <td><?= htmlspecialchars($sg['laboratorio']) ?></td>
                            <?php endif; ?>
                            <td><span class="badge <?= $bc ?>"><?= $prioLabel ?></span></td>
                            <td><span class="badge <?= $sc ?>"><?= $statoLabel ?></span></td>
                            <td><?= htmlspecialchars($sg['segnalato_da']) ?></td>
                            <td><?= date('d/m/Y', strtotime($sg['data_segnalazione'])) ?></td>
                            <td><a href="<?= BASE_PATH ?>/pages/segnalazioni/dettaglio.php?id=<?= $sg['id'] ?>" class="btn btn-primary btn-sm"><?= L('dettagli') ?></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.lab-lock-banner {
    display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;
    background:#e8f4f4;border:1px solid #b6d9d8;border-radius:8px;
    padding:.6rem 1rem;margin-bottom:1rem;font-size:.88rem;color:#1a1a1a;
}
.lab-lock-change { color:#01696f;text-decoration:none;font-weight:600;margin-left:auto; }
.lab-lock-change:hover { text-decoration:underline; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
