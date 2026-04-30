<?php
/**
 * pages/report/pdf.php
 * Genera e scarica un resoconto PDF delle sessioni di laboratorio.
 * Accessibile solo a responsabili di laboratorio e admin.
 *
 * Parametri GET:
 *   tipo        : settimana | mese | anno | custom
 *   laboratorio : id laboratorio (opzionale, solo admin può lasciare vuoto)
 *   data_inizio : Y-m-d (solo se tipo=custom)
 *   data_fine   : Y-m-d (solo se tipo=custom)
 */

require_once __DIR__ . '/../../config/auth.php';
requireLogin();

$conn   = getConnection();
$userId = (int)getCurrentUserId();

// ── Controllo accesso: admin o responsabile di almeno un lab ──
$isResp = isAdmin();
if (!$isResp) {
    $res    = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id_responsabile = $userId AND attivo = 1 LIMIT 1");
    $isResp = mysqli_num_rows($res) > 0;
}
if (!$isResp) {
    header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
    exit;
}

// ── Parametri ──
$tipo  = in_array($_GET['tipo'] ?? '', ['settimana','mese','anno','custom']) ? $_GET['tipo'] : 'settimana';
$labId = (isset($_GET['laboratorio']) && $_GET['laboratorio'] !== '') ? (int)$_GET['laboratorio'] : null;
$oggi  = date('Y-m-d');

switch ($tipo) {
    case 'settimana':
        $dataInizio   = date('Y-m-d', strtotime('-6 days'));
        $dataFine     = $oggi;
        $periodoLabel = 'Ultima settimana (' . date('d/m/Y', strtotime('-6 days')) . ' – ' . date('d/m/Y') . ')';
        break;
    case 'mese':
        $dataInizio   = date('Y-m-01');
        $dataFine     = date('Y-m-t');
        $periodoLabel = 'Mese corrente — ' . strftime('%B %Y') ?: date('m/Y');
        break;
    case 'anno':
        $dataInizio   = date('Y-01-01');
        $dataFine     = date('Y-12-31');
        $periodoLabel = 'Anno ' . date('Y');
        break;
    case 'custom':
        $dataInizio   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_inizio'] ?? '') ? $_GET['data_inizio'] : date('Y-m-01');
        $dataFine     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fine']   ?? '') ? $_GET['data_fine']   : $oggi;
        $periodoLabel = 'Periodo: ' . date('d/m/Y', strtotime($dataInizio)) . ' – ' . date('d/m/Y', strtotime($dataFine));
        break;
}

// ── Verifica accesso al laboratorio selezionato ──
$labFilter   = '';
$labLabel    = 'Tutti i laboratori';
if ($labId) {
    if (!isAdmin()) {
        $chk = mysqli_query($conn, "SELECT 1 FROM laboratori WHERE id = $labId AND id_responsabile = $userId LIMIT 1");
        if (mysqli_num_rows($chk) === 0) {
            header('Location: ' . BASE_PATH . '/index.php?error=unauthorized');
            exit;
        }
    }
    $labFilter = "AND sl.id_laboratorio = $labId";
    $resLab    = mysqli_query($conn, "SELECT nome, aula FROM laboratori WHERE id = $labId");
    $labRow    = mysqli_fetch_assoc($resLab);
    $labLabel  = $labRow ? htmlspecialchars($labRow['nome']) . ' — Aula ' . htmlspecialchars($labRow['aula']) : 'Laboratorio';
} elseif (!isAdmin()) {
    $labFilter = "AND l.id_responsabile = $userId";
    $labLabel  = 'I miei laboratori';
}

// ── Query sessioni nel periodo ──
$diInizio = mysqli_real_escape_string($conn, $dataInizio);
$diFine   = mysqli_real_escape_string($conn, $dataFine);

$resSessioni = mysqli_query($conn, "
    SELECT sl.id, sl.data, sl.ora_ingresso, sl.ora_uscita,
           sl.attivita_svolta, sl.note,
           l.nome AS lab_nome, l.aula,
           c.nome AS classe_nome
    FROM   sessioni_laboratorio sl
    JOIN   laboratori l ON sl.id_laboratorio = l.id
    JOIN   classi     c ON sl.id_classe       = c.id
    WHERE  sl.data BETWEEN '$diInizio' AND '$diFine'
    $labFilter
    ORDER  BY sl.data ASC, sl.ora_ingresso ASC
");

$sessioni = [];
while ($row = mysqli_fetch_assoc($resSessioni)) $sessioni[] = $row;

// Per ogni sessione carica docenti e materiali
foreach ($sessioni as &$s) {
    $sid = (int)$s['id'];

    $resDoc = mysqli_query($conn, "
        SELECT u.cognome, u.nome, fs.tipo_presenza
        FROM   firme_sessioni fs
        JOIN   utenti u ON fs.id_docente = u.id
        WHERE  fs.id_sessione = $sid
        ORDER  BY fs.tipo_presenza, u.cognome
    ");
    $s['docenti'] = [];
    while ($d = mysqli_fetch_assoc($resDoc)) $s['docenti'][] = $d;

    $resMat = mysqli_query($conn, "
        SELECT m.nome, um.quantita_usata, m.unita_misura, um.note
        FROM   utilizzo_materiali um
        JOIN   materiali m ON um.id_materiale = m.id
        WHERE  um.id_sessione = $sid
    ");
    $s['materiali'] = [];
    while ($m = mysqli_fetch_assoc($resMat)) $s['materiali'][] = $m;
}
unset($s);

// ─────────────────────────────────────────────────────────────
// GENERAZIONE PDF con FPDF
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

/**
 * Converte stringa UTF-8 in ISO-8859-1 per FPDF core fonts.
 * Gestisce correttamente i caratteri italiani accentati.
 */
function _u(string $text): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
}

class ResocondoPDF extends FPDF
{
    public string $periodoLabel = '';
    public string $labLabel     = '';

    function Header(): void
    {
        // Logo / titolo applicazione
        $this->SetFont('Helvetica', 'B', 15);
        $this->SetTextColor(20, 80, 150);
        $this->Cell(0, 9, _u('REGISTRONY'), 0, 1, 'C');

        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 7, _u('Resoconto Sessioni di Laboratorio'), 0, 1, 'C');

        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 5, _u($this->labLabel), 0, 1, 'C');
        $this->Cell(0, 5, _u($this->periodoLabel), 0, 1, 'C');

        // Linea separatrice
        $this->SetDrawColor(20, 80, 150);
        $this->SetLineWidth(0.5);
        $this->Line($this->lMargin, $this->GetY() + 1, $this->GetPageWidth() - $this->rMargin, $this->GetY() + 1);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0);
        $this->Ln(4);
    }

    function Footer(): void
    {
        $this->SetY(-13);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(150);
        $this->Cell(0, 5, _u('Generato il ' . date('d/m/Y H:i') . '   —   Pagina ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
}

$pdf = new ResocondoPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetAuthor('Registrony', true);
$pdf->SetTitle('Resoconto Lab — ' . $periodoLabel, true);
$pdf->periodoLabel = $periodoLabel;
$pdf->labLabel     = $labLabel;
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

if (empty($sessioni)) {
    $pdf->SetFont('Helvetica', 'I', 11);
    $pdf->SetTextColor(130);
    $pdf->Cell(0, 12, _u('Nessuna sessione trovata per il periodo selezionato.'), 0, 1, 'C');
} else {
    $totale = count($sessioni);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(90);
    $pdf->Cell(0, 6, _u("Totale sessioni nel periodo: $totale"), 0, 1, 'R');
    $pdf->Ln(1);

    foreach ($sessioni as $s) {
        // ── Controllo page break manuale ──
        if ($pdf->GetY() > 245) $pdf->AddPage();

        // ── Intestazione sessione (barra colorata) ──
        $pdf->SetFillColor(20, 80, 150);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Helvetica', 'B', 9);

        $dataFmt  = date('d/m/Y', strtotime($s['data']));
        $orario   = substr($s['ora_ingresso'], 0, 5);
        if ($s['ora_uscita']) $orario .= ' – ' . substr($s['ora_uscita'], 0, 5);
        else                  $orario .= ' (in corso)';

        $titleRow = _u("  Sessione #{$s['id']}   |   $dataFmt   |   {$s['lab_nome']} ({$s['aula']})   |   Classe: {$s['classe_nome']}   |   Orario: $orario");
        $pdf->Cell(0, 7, $titleRow, 0, 1, 'L', true);
        $pdf->SetTextColor(0);

        // ── Corpo sessione ──
        $pdf->SetFont('Helvetica', '', 9);
        $lineH = 5.5;

        // Docenti
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(35, $lineH, _u('Docenti:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        if (!empty($s['docenti'])) {
            $parts = array_map(
                fn($d) => $d['cognome'] . ' ' . $d['nome'] . ' (' . $d['tipo_presenza'] . ')',
                $s['docenti']
            );
            $pdf->MultiCell(0, $lineH, _u(implode(', ', $parts)));
        } else {
            $pdf->Cell(0, $lineH, _u('Non registrati'), 0, 1);
        }

        // Attività
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(35, $lineH, _u('Attività svolta:'), 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->MultiCell(0, $lineH, _u($s['attivita_svolta'] ?: '—'));

        // Materiali usati
        if (!empty($s['materiali'])) {
            if ($pdf->GetY() > 250) $pdf->AddPage();

            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(0, $lineH, _u('Materiali utilizzati:'), 0, 1);

            // Intestazione tabella materiali
            $pdf->SetFillColor(210, 225, 245);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(80, 5, _u('Materiale'),    'B', 0, 'L', true);
            $pdf->Cell(28, 5, _u('Qtà usata'),    'B', 0, 'C', true);
            $pdf->Cell(25, 5, _u('Unità misura'), 'B', 0, 'C', true);
            $pdf->Cell(0,  5, _u('Note'),          'B', 1, 'L', true);

            $pdf->SetFont('Helvetica', '', 8);
            $altRow = false;
            foreach ($s['materiali'] as $mat) {
                $pdf->SetFillColor($altRow ? 245 : 255, $altRow ? 249 : 255, $altRow ? 255 : 255);
                $pdf->Cell(80, 5, _u($mat['nome']),            0, 0, 'L', $altRow);
                $pdf->Cell(28, 5, _u($mat['quantita_usata']),  0, 0, 'C', $altRow);
                $pdf->Cell(25, 5, _u($mat['unita_misura']),    0, 0, 'C', $altRow);
                $pdf->Cell(0,  5, _u($mat['note'] ?? ''),      0, 1, 'L', $altRow);
                $altRow = !$altRow;
            }
        }

        // Note aggiuntive
        if (!empty($s['note'])) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(35, $lineH, _u('Note:'), 0, 0);
            $pdf->SetFont('Helvetica', 'I', 9);
            $pdf->SetTextColor(80);
            $pdf->MultiCell(0, $lineH, _u($s['note']));
            $pdf->SetTextColor(0);
        }

        // Spaziatura tra sessioni
        $pdf->Ln(4);
    }
}

// ── Output come download ──
$nomeFile = 'resoconto_' . preg_replace('/[^a-z0-9]/i', '_', $labLabel) . '_' . date('Ymd_Hi') . '.pdf';
$pdf->Output('D', $nomeFile);
exit;
