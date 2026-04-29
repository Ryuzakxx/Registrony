<?php
$pageTitle = 'Materiali';
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

$filtroLab = ($labForzatoId !== null) ? $labForzatoId
           : intval($_GET['laboratorio'] ?? 0);

$where = "WHERE m.attivo = 1";
if ($filtroLab) {
    $where .= " AND m.id_laboratorio = $filtroLab";
} elseif ($labsConsentiti !== null) {
    if (!empty($labsConsentiti)) {
        $ids    = implode(',', $labsConsentiti);
        $where .= " AND m.id_laboratorio IN ($ids)";
    } else {
        $where .= ' AND 1=0';
    }
}

$result    = mysqli_query($conn, "SELECT m.*, l.nome AS laboratorio, l.aula FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id $where ORDER BY l.nome, m.nome");
$materiali = [];
while ($row = mysqli_fetch_assoc($result)) $materiali[] = $row;

/* Lab per filtro dropdown (admin / tecnico multi-lab) */
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
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
    Materiali di <strong><?= htmlspecialchars($labForzatoNome) ?></strong>
    &nbsp;···&nbsp;
    <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php" class="lab-lock-change">Cambia laboratorio</a>
</div>
<?php endif; ?>

<?php if (!empty($labs)): /* Filtro lab: solo se admin o tecnico multi-lab */ ?>
<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
            <div class="form-group" style="margin-bottom:0">
                <label>Laboratorio</label>
                <select name="laboratorio" class="form-control">
                    <option value="">Tutti</option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab==$lab['id'] ? 'selected':'' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:22px">
                <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
                <a href="<?= BASE_PATH ?>/pages/materiali/utilizzo.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>&#128230; Elenco Materiali</h3></div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state"><div class="empty-icon">&#128230;</div><h4>Nessun materiale trovato</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Materiale</th>
                            <?php if (!$labForzatoId): ?><th>Laboratorio</th><?php endif; ?>
                            <th>Unità</th><th>Disponibile</th><th>Soglia Min.</th><th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiali as $m): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($m['nome']) ?></strong>
                                <?php if ($m['descrizione']): ?><br><small class="text-muted"><?= htmlspecialchars($m['descrizione']) ?></small><?php endif; ?>
                            </td>
                            <?php if (!$labForzatoId): ?><td><?= htmlspecialchars($m['laboratorio']) ?></td><?php endif; ?>
                            <td><?= htmlspecialchars($m['unita_misura'] ?? '-') ?></td>
                            <td><?= $m['quantita_disponibile'] !== null ? '<strong>' . $m['quantita_disponibile'] . '</strong>' : '-' ?></td>
                            <td><?= $m['soglia_minima'] ?? '-' ?></td>
                            <td>
                                <?php if ($m['quantita_disponibile'] !== null && $m['soglia_minima'] !== null): ?>
                                    <?php if ($m['quantita_disponibile'] <= 0): ?>
                                        <span class="badge badge-danger">Esaurito</span>
                                    <?php elseif ($m['quantita_disponibile'] <= $m['soglia_minima']): ?>
                                        <span class="badge badge-warning">In esaurimento</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Disponibile</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">N/D</span>
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
