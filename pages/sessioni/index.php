<?php
$pageTitle = 'Sessioni Laboratorio';
require_once __DIR__ . '/../../includes/header.php';

$conn   = getConnection();
$userId = (int)getCurrentUserId();

/*
 * Lab forzato:
 *   - Docente  -> solo il lab selezionato in sessione
 *   - Tecnico  -> solo i lab di cui è assistente
 *   - Admin    -> tutti, filtro libero
 */
$labForzatoId   = null;   // ID fisso (docente / tecnico mono-lab)
$labForzatoNome = null;
$labsConsentiti = null;   // array di ID consentiti (tecnico multi-lab)

if (isDocente()) {
    requireLabSelected();
    $labForzatoId = (int)getSelectedLabId();
    $r = mysqli_query($conn, "SELECT nome FROM laboratori WHERE id = $labForzatoId LIMIT 1");
    $labForzatoNome = mysqli_fetch_row($r)[0] ?? '';
} elseif (isTecnico()) {
    $techLabs = getTechnicianLabs($userId);
    $labsConsentiti = array_column($techLabs, 'id');
}

/* Filtri da GET (solo admin / tecnico multi-lab) */
$filtroLab    = ($labForzatoId !== null) ? $labForzatoId
              : intval($_GET['laboratorio'] ?? 0);
$filtroData   = mysqli_real_escape_string($conn, $_GET['data']       ?? '');
$filtroClasse = mysqli_real_escape_string($conn, $_GET['classe']     ?? '');

/* WHERE dinamico */
$where = [];
if ($filtroLab)    $where[] = "s.id_laboratorio = $filtroLab";
if ($filtroData)   $where[] = "s.data = '$filtroData'";
if ($filtroClasse) $where[] = "s.id_classe = $filtroClasse";
// Tecnico: limita ai suoi lab
if ($labsConsentiti !== null && !empty($labsConsentiti)) {
    $ids = implode(',', $labsConsentiti);
    $where[] = "s.id_laboratorio IN ($ids)";
} elseif ($labsConsentiti !== null && empty($labsConsentiti)) {
    $where[] = '1=0'; // tecnico senza lab
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$perPage    = 20;
$page       = max(1, intval($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;
$resCount   = mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio s $whereSQL");
$total      = mysqli_fetch_row($resCount)[0];
$totalPages = ceil($total / $perPage);

$result = mysqli_query($conn, "
    SELECT s.id, s.data, s.ora_ingresso, s.ora_uscita, s.attivita_svolta, s.note,
           l.nome AS laboratorio, l.aula, c.nome AS classe,
           GROUP_CONCAT(CONCAT(u.cognome, ' ', u.nome, ' (', f.tipo_presenza, ')') ORDER BY f.tipo_presenza SEPARATOR ', ') AS docenti
    FROM sessioni_laboratorio s
    JOIN laboratori l ON s.id_laboratorio = l.id
    JOIN classi c ON s.id_classe = c.id
    LEFT JOIN firme_sessioni f ON s.id = f.id_sessione
    LEFT JOIN utenti u ON f.id_docente = u.id
    $whereSQL
    GROUP BY s.id
    ORDER BY s.data DESC, s.ora_ingresso DESC
    LIMIT $perPage OFFSET $offset
");
$sessioni = [];
while ($row = mysqli_fetch_assoc($result)) $sessioni[] = $row;

/* Lab per filtro (solo admin / tecnico multi-lab) */
$labs = [];
if (isAdmin()) {
    $resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
    while ($r = mysqli_fetch_assoc($resLabs)) $labs[] = $r;
} elseif ($labsConsentiti && count($labsConsentiti) > 1) {
    $ids = implode(',', $labsConsentiti);
    $resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE id IN ($ids) ORDER BY nome");
    while ($r = mysqli_fetch_assoc($resLabs)) $labs[] = $r;
}

$resClassi = mysqli_query($conn, "SELECT id, nome, anno_scolastico FROM classi WHERE attivo = 1 ORDER BY nome");
$classi    = [];
while ($row = mysqli_fetch_assoc($resClassi)) $classi[] = $row;
?>

<?php if ($labForzatoId): ?>
<!-- Banner lab forzato -->
<div class="lab-lock-banner">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    Sessioni di <strong><?= htmlspecialchars($labForzatoNome) ?></strong>
    &nbsp;···&nbsp;
    <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php" class="lab-lock-change">Cambia laboratorio</a>
</div>
<?php endif; ?>

<!-- Filtri (data + classe sempre; lab solo se non forzato) -->
<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="form-row" style="align-items:flex-end;">
            <?php if (!empty($labs)): /* filtro lab: admin o tecnico multi-lab */ ?>
            <div class="form-group" style="margin-bottom:0">
                <label>Laboratorio</label>
                <select name="laboratorio" class="form-control">
                    <option value="">Tutti</option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab == $lab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group" style="margin-bottom:0">
                <label>Data</label>
                <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($filtroData) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Classe</label>
                <select name="classe" class="form-control">
                    <option value="">Tutte</option>
                    <?php foreach ($classi as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= $filtroClasse == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['nome'] . ' (' . $cl['anno_scolastico'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
                <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Sessioni (<?= $total ?> totali)</h3>
        <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="btn btn-primary btn-sm">+ Nuova Sessione</a>
    </div>
    <div class="card-body">
        <?php if (empty($sessioni)): ?>
            <div class="empty-state"><div class="empty-icon">&#128203;</div><h4>Nessuna sessione trovata</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <?php if (!$labForzatoId): ?><th>Laboratorio</th><?php endif; ?>
                            <th>Classe</th><th>Ingresso</th><th>Uscita</th><th>Firme</th><th>Attività</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessioni as $s): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($s['data'])) ?></td>
                            <?php if (!$labForzatoId): ?>
                            <td><strong><?= htmlspecialchars($s['laboratorio']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($s['aula']) ?></small></td>
                            <?php endif; ?>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span></td>
                            <td><?= substr($s['ora_ingresso'], 0, 5) ?></td>
                            <td><?= $s['ora_uscita'] ? substr($s['ora_uscita'], 0, 5) : '<span class="badge badge-success">In corso</span>' ?></td>
                            <td><?= htmlspecialchars($s['docenti'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($s['attivita_svolta'] ?? '-', 0, 50, '...')) ?></td>
                            <td><a href="<?= BASE_PATH ?>/pages/sessioni/dettaglio.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">Dettagli</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $queryParams = $_GET; $queryParams['page'] = $i; $url = '?' . http_build_query($queryParams); ?>
                        <?php if ($i == $page): ?><span class="active"><?= $i ?></span>
                        <?php else: ?><a href="<?= $url ?>"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
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
