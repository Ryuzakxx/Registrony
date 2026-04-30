<?php
require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$conn   = getConnection();
$userId = (int)getCurrentUserId();

$isResp = isAdmin();
if (!$isResp) {
    $res = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id_responsabile = $userId AND attivo = 1 LIMIT 1");
    $isResp = mysqli_num_rows($res) > 0;
}
if (!$isResp) {
    http_response_code(403);
    exit('Accesso non autorizzato.');
}

$tipo       = $_GET['tipo'] ?? 'settimana';
$idLab      = intval($_GET['laboratorio'] ?? 0);
$dataFine   = date('Y-m-d');

switch ($tipo) {
    case 'mese':    $dataInizio = date('Y-m-01');                        break;
    case 'anno':    $dataInizio = date('Y-01-01');                       break;
    case 'custom':  $dataInizio = $_GET['data_inizio'] ?? date('Y-m-01');
                    $dataFine   = $_GET['data_fine']   ?? date('Y-m-d'); break;
    default:        $dataInizio = date('Y-m-d', strtotime('-7 days'));   break;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInizio)) $dataInizio = date('Y-m-d', strtotime('-7 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFine))   $dataFine   = date('Y-m-d');

if ($idLab && !isAdmin()) {
    $r = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id = $idLab AND id_responsabile = $userId LIMIT 1");
    if (mysqli_num_rows($r) === 0) {
        http_response_code(403);
        exit('Accesso non autorizzato a questo laboratorio.');
    }
}

$labNome = 'Tutti i laboratori';
if ($idLab) {
    $rLab   = mysqli_query($conn, "SELECT nome, aula FROM laboratori WHERE id = $idLab LIMIT 1");
    $labRow = mysqli_fetch_assoc($rLab);
    if ($labRow) $labNome = $labRow['nome'] . ' (' . $labRow['aula'] . ')';
}

$whereArr = ["s.data BETWEEN '$dataInizio' AND '$dataFine'"];
if ($idLab) {
    $whereArr[] = "s.id_laboratorio = $idLab";
} elseif (!isAdmin()) {
    $rMyLabs  = mysqli_query($conn, "SELECT id FROM laboratori WHERE id_responsabile = $userId AND attivo = 1");
    $myLabIds = [];
    while ($r = mysqli_fetch_assoc($rMyLabs)) $myLabIds[] = $r['id'];
    if (empty($myLabIds)) exit('Nessun laboratorio assegnato.');
    $whereArr[] = 's.id_laboratorio IN (' . implode(',', $myLabIds) . ')';
}
$where = 'WHERE ' . implode(' AND ', $whereArr);

$result = mysqli_query($conn, "
    SELECT s.id, s.data, s.ora_ingresso, s.ora_uscita, s.attivita_svolta, s.note,
           l.nome AS lab_nome, l.aula, c.nome AS classe, c.anno_scolastico
    FROM sessioni_laboratorio s
    JOIN laboratori l ON s.id_laboratorio = l.id
    JOIN classi c ON s.id_classe = c.id
    $where
    ORDER BY s.data ASC, s.ora_ingresso ASC
");

$sessioni = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sid = $row['id'];

    $rDoc = mysqli_query($conn, "
        SELECT CONCAT(u.cognome, ' ', u.nome) AS nome, f.tipo_presenza
        FROM firme_sessioni f
        JOIN utenti u ON f.id_docente = u.id
        WHERE f.id_sessione = $sid
        ORDER BY f.tipo_presenza
    ");
    $row['docenti'] = [];
    while ($d = mysqli_fetch_assoc($rDoc)) $row['docenti'][] = $d;

    $rMat = mysqli_query($conn, "
        SELECT m.nome, um.quantita_usata, m.unita_misura, um.note
        FROM utilizzo_materiali um
        JOIN materiali m ON um.id_materiale = m.id
        WHERE um.id_sessione = $sid
    ");
    $row['materiali'] = [];
    while ($m = mysqli_fetch_assoc($rMat)) $row['materiali'][] = $m;

    $sessioni[] = $row;
}

require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4',
    'margin_top'    => 20,
    'margin_bottom' => 20,
    'margin_left'   => 15,
    'margin_right'  => 15,
]);

$mpdf->SetHTMLFooter('<div style="text-align:center;font-size:8pt;color:#999;">Registrony — Resoconto generato il ' . date('d/m/Y H:i') . ' — Pagina {PAGENO} di {nbpg}</div>');

switch ($tipo) {
    case 'mese':   $periodoLabel = 'Mese corrente (' . date('m/Y') . ')'; break;
    case 'anno':   $periodoLabel = 'Anno ' . date('Y');                   break;
    case 'custom': $periodoLabel = 'Dal ' . date('d/m/Y', strtotime($dataInizio)) . ' al ' . date('d/m/Y', strtotime($dataFine)); break;
    default:       $periodoLabel = 'Ultima settimana';                    break;
}

$totale     = count($sessioni);
$labLabel   = htmlspecialchars($labNome);
$dataReport = date('d/m/Y H:i');

ob_start(); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body        { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1a1a1a; }
.rpt-header { border-bottom: 3px solid #01696f; padding-bottom: 10px; margin-bottom: 18px; }
.rpt-header h1  { font-size: 17pt; color: #01696f; margin: 0 0 5px 0; }
.rpt-header .meta { font-size: 9pt; color: #555; }
table       { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9pt; }
th          { background: #01696f; color: #fff; padding: 7px 9px; text-align: left; }
td          { padding: 5px 9px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
td.lbl      { font-weight: bold; color: #01696f; width: 130px; white-space: nowrap; }
.badge-doc  { display:inline-block; background:#e8f4f4; color:#01696f; border:1px solid #b6d9d8;
              border-radius:4px; padding:2px 7px; margin:1px; font-size:8pt; }
.no-data    { text-align:center; color:#888; font-style:italic; padding:24px; }
.block      { page-break-inside: avoid; margin-bottom: 16px; }
</style>
</head>
<body>

<div class="rpt-header">
    <h1>Registrony &mdash; Resoconto Laboratorio</h1>
    <div class="meta">
        <strong>Laboratorio:</strong> <?= $labLabel ?> &nbsp;&bull;&nbsp;
        <strong>Periodo:</strong> <?= htmlspecialchars($periodoLabel) ?> &nbsp;&bull;&nbsp;
        <strong>Sessioni totali:</strong> <?= $totale ?> &nbsp;&bull;&nbsp;
        <strong>Generato il:</strong> <?= $dataReport ?>
    </div>
</div>

<?php if (empty($sessioni)): ?>
    <p class="no-data">Nessuna sessione trovata per il periodo selezionato.</p>
<?php else: ?>
    <?php foreach ($sessioni as $s): ?>
    <div class="block">
        <table>
            <tr>
                <th colspan="2">
                    <?= date('d/m/Y', strtotime($s['data'])) ?>
                    &mdash; <?= htmlspecialchars($s['lab_nome']) ?> (<?= htmlspecialchars($s['aula']) ?>)
                    &mdash; Classe: <?= htmlspecialchars($s['classe']) ?> (<?= htmlspecialchars($s['anno_scolastico']) ?>)
                </th>
            </tr>
            <tr>
                <td class="lbl">Orario</td>
                <td>
                    <?= substr($s['ora_ingresso'], 0, 5) ?> &rarr;
                    <?= $s['ora_uscita'] ? substr($s['ora_uscita'], 0, 5) : '<em>in corso</em>' ?>
                </td>
            </tr>
            <tr>
                <td class="lbl">Docenti</td>
                <td>
                    <?php if (empty($s['docenti'])): ?>
                        <em>—</em>
                    <?php else: ?>
                        <?php foreach ($s['docenti'] as $d): ?>
                            <span class="badge-doc"><?= htmlspecialchars($d['nome']) ?> (<?= htmlspecialchars($d['tipo_presenza']) ?>)</span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="lbl">Attività svolta</td>
                <td><?= nl2br(htmlspecialchars($s['attivita_svolta'] ?? '—')) ?></td>
            </tr>
            <?php if (!empty($s['note'])): ?>
            <tr>
                <td class="lbl">Note</td>
                <td><?= nl2br(htmlspecialchars($s['note'])) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($s['materiali'])): ?>
            <tr>
                <td class="lbl">Materiali usati</td>
                <td>
                    <?php foreach ($s['materiali'] as $mat): ?>
                        <?= htmlspecialchars($mat['nome']) ?>:
                        <strong><?= htmlspecialchars($mat['quantita_usata']) ?> <?= htmlspecialchars($mat['unita_misura']) ?></strong>
                        <?= $mat['note'] ? '— ' . htmlspecialchars($mat['note']) : '' ?><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

$filename = 'resoconto_' . ($idLab ? 'lab' . $idLab . '_' : 'tutti_') . $tipo . '_' . date('Ymd') . '.pdf';
$mpdf->WriteHTML($html);
$mpdf->Output($filename, 'D');
exit;
