<?php
$pageTitle = 'Materiali';
require_once __DIR__ . '/../../includes/header.php';

$conn      = getConnection();
$filtroLab = mysqli_real_escape_string($conn, $_GET['laboratorio'] ?? '');

$where = "WHERE m.attivo = 1";
if ($filtroLab) $where .= " AND m.id_laboratorio = '$filtroLab'";

$result   = mysqli_query($conn, "SELECT m.*, l.nome AS laboratorio, l.aula FROM materiali m JOIN laboratori l ON m.id_laboratorio = l.id $where ORDER BY l.nome, m.nome");
$materiali= [];
while ($row = mysqli_fetch_assoc($result)) $materiali[] = $row;

$resLabs = mysqli_query($conn, "SELECT id, nome FROM laboratori WHERE attivo = 1 ORDER BY nome");
$labs    = [];
while ($row = mysqli_fetch_assoc($resLabs)) $labs[] = $row;
?>

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

<div class="card">
    <div class="card-header"><h3>&#128230; Elenco Materiali</h3></div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state"><div class="empty-icon">&#128230;</div><h4>Nessun materiale trovato</h4></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Materiale</th><th>Laboratorio</th><th>Unita</th><th>Disponibile</th><th>Soglia Min.</th><th>Stato</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiali as $m): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($m['nome']) ?></strong>
                                <?php if ($m['descrizione']): ?><br><small class="text-muted"><?= htmlspecialchars($m['descrizione']) ?></small><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($m['laboratorio']) ?></td>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>