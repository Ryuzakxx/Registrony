<?php
$pageTitle = 'Sessioni Laboratorio';
require_once __DIR__ . '/../../includes/header.php';

$conn = getConnection();

$filtroLab   = mysqli_real_escape_string($conn, $_GET['laboratorio'] ?? '');
$filtroData  = mysqli_real_escape_string($conn, $_GET['data'] ?? '');
$filtroClasse= mysqli_real_escape_string($conn, $_GET['classe'] ?? '');

$where  = [];
if ($filtroLab)    $where[] = "s.id_laboratorio = '$filtroLab'";
if ($filtroData)   $where[] = "s.data = '$filtroData'";
if ($filtroClasse) $where[] = "s.id_classe = '$filtroClasse'";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$perPage = 20;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$resCount = mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio s $whereSQL");
$total    = mysqli_fetch_row($resCount)[0];
$totalPages = ceil($total / $perPage);

$result  = mysqli_query($conn, "
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

$resLabs  = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs     = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;

$resClassi = mysqli_query($conn, "SELECT id, nome, anno_scolastico FROM classi WHERE attivo = 1 ORDER BY nome");
$classi    = [];
while ($row = mysqli_fetch_assoc($resClassi)) $classi[] = $row;
?>

<div class="card mb-2">
    <div class="card-body">
        <form method="GET" class="form-row" style="align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0">
                <label>Laboratorio</label>
                <select name="laboratorio" class="form-control">
                    <option value="">Tutti</option>
                    <?php foreach ($labs as $lab): ?>
                        <option value="<?= $lab['id'] ?>" <?= $filtroLab == $lab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($lab['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                        <tr><th>Data</th><th>Laboratorio</th><th>Classe</th><th>Ingresso</th><th>Uscita</th><th>Docenti</th><th>Attivita</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessioni as $s): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($s['data'])) ?></td>
                            <td><strong><?= htmlspecialchars($s['laboratorio']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($s['aula']) ?></small></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span></td>
                            <td><?= substr($s['ora_ingresso'], 0, 5) ?></td>
                            <td><?= $s['ora_uscita'] ? substr($s['ora_uscita'], 0, 5) : '<span class="badge badge-success">In corso</span>' ?></td>
                            <td><?= htmlspecialchars($s['docenti'] ?? 'Nessuna firma') ?></td>
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
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $url ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>