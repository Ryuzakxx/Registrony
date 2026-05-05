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

// ── Helper: nome mese in italiano (evita strftime deprecata in PHP 8.1+) ──
function mese_italiano(string $data): string {
    $mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
              'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $m = (int)date('n', strtotime($data));
    return $mesi[$m] . ' ' . date('Y', strtotime($data));
}

// ── Parametri ──
$tipo  = in_array($_GET['tipo'] ?? '', ['settimana','mese','anno','custom']) ? $_GET['tipo'] : 'settimana';
$labId = (isset($_GET['laboratorio']) && $_GET['laboratorio'] !== '') ? (int)$_GET['laboratorio'] : null;
$oggi  = date('Y-m-d');

switch ($tipo) {
    case 'settimana':
        $dataInizio   = date('Y-m-d', strtotime('-6 days'));
        $dataFine     = $oggi;
        $periodoLabel = 'Ultima settimana (' . date('d/m/Y', strtotime('-6 days')) . ' - ' . date('d/m/Y') . ')';
        break;
    case 'mese':
        $dataInizio   = date('Y-m-01');
        $dataFine     = date('Y-m-t');
        $periodoLabel = 'Mese corrente - ' . mese_italiano($dataInizio);
        break;
    case 'anno':
        $dataInizio   = date('Y-01-01');
        $dataFine     = date('Y-12-31');
        $periodoLabel = 'Anno ' . date('Y');
        break;
    case 'custom':
        $dataInizio   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_inizio'] ?? '') ? $_GET['data_inizio'] : date('Y-m-01');
        $dataFine     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fine']   ?? '') ? $_GET['data_fine']   : $oggi;
        $periodoLabel = 'Periodo: ' . date('d/m/Y', strtotime($dataInizio)) . ' - ' . date('d/m/Y', strtotime($dataFine));
        break;
}

// ── Verifica accesso al laboratorio selezionato ──
$labFilter = '';
$labLabel  = 'Tutti i laboratori';
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
    $labLabel  = $labRow ? $labRow['nome'] . ' - Aula ' . $labRow['aula'] : 'Laboratorio';
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

// ── Calcola statistiche per il riepilogo finale ──
$totaleOreMin   = 0;
$docenteCount   = [];
$materialeCount = [];

foreach ($sessioni as $s) {
    if ($s['ora_ingresso'] && $s['ora_uscita']) {
        $inizio = strtotime($s['data'] . ' ' . $s['ora_ingresso']);
        $fine   = strtotime($s['data'] . ' ' . $s['ora_uscita']);
        if ($fine > $inizio) $totaleOreMin += (int)(($fine - $inizio) / 60);
    }
    foreach ($s['docenti'] as $d) {
        $nome = $d['cognome'] . ' ' . $d['nome'];
        $docenteCount[$nome] = ($docenteCount[$nome] ?? 0) + 1;
    }
    foreach ($s['materiali'] as $m) {
        $materialeCount[$m['nome']] = ($materialeCount[$m['nome']] ?? 0) + (float)$m['quantita_usata'];
    }
}
arsort($docenteCount);
arsort($materialeCount);

// ─────────────────────────────────────────────────────────────
// GENERAZIONE PDF con FPDF
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

/**
 * Converte stringa UTF-8 in ISO-8859-1 per FPDF core fonts.
 */
function _u(string $text): string {
    $result = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    return ($result !== false && $result !== '') ? $result : $text;
}

class ResocontoPDF extends FPDF
{
    public string $periodoLabel = '';
    public string $labLabel     = '';

    function Header(): void
    {
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(0, 0, 0);

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(20, 80, 150);
        $this->Cell(0, 9, _u('REGISTRONY'), 0, 1, 'C');

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 6, _u('Resoconto Sessioni di Laboratorio'), 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 5, _u($this->labLabel), 0, 1, 'C');
        $this->Cell(0, 5, _u($this->periodoLabel), 0, 1, 'C');

        $this->SetDrawColor(20, 80, 150);
        $this->SetLineWidth(0.7);
        $this->Line($this->lMargin, $this->GetY() + 1.5, $this->GetPageWidth() - $this->rMargin, $this->GetY() + 1.5);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
        $this->Ln(5);
    }

    function Footer(): void
    {
        $this->SetY(-13);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, _u('Generato il ' . date('d/m/Y H:i') . '   |   Pagina ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
}

$pdf = new ResocontoPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetAuthor('Registrony', true);
$pdf->SetTitle(_u('Resoconto Lab - ' . $periodoLabel), true);
$pdf->periodoLabel = $periodoLabel;
$pdf->labLabel     = $labLabel;
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

if (empty($sessioni)) {
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->SetTextColor(130, 130, 130);
    $pdf->Ln(10);
    $pdf->Cell(0, 12, _u('Nessuna sessione trovata per il periodo selezionato.'), 0, 1, 'C');
} else {
    $totale = count($sessioni);

    // ── Box riepilogo rapido in cima ──
    $oreH   = (int)($totaleOreMin / 60);
    $oreMin = $totaleOreMin % 60;
    $oreStr = $oreH > 0 ? "{$oreH}h {$oreMin}min" : "{$oreMin}min";

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(20, 80, 150);
    $pdf->Cell(0, 6, _u('RIEPILOGO'), 0, 1, 'L');
    $pdf->SetDrawColor(200, 215, 240);
    $pdf->SetLineWidth(0.3);
    $pdf->Line($pdf->lMargin, $pdf->GetY(), $pdf->GetPageWidth() - $pdf->rMargin, $pdf->GetY());
    $pdf->Ln(2);

    $colW = ($pdf->GetPageWidth() - 30) / 3;
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell($colW, 5.5, _u('Sessioni totali'), 0, 0, 'L');
    $pdf->Cell($colW, 5.5, _u('Ore totali registrate'), 0, 0, 'L');
    $pdf->Cell($colW, 5.5, _u('Docenti coinvolti'), 0, 1, 'L');

    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(20, 80, 150);
    $pdf->Cell($colW, 7, _u((string)$totale), 0, 0, 'L');
    $pdf->Cell($colW, 7, _u($oreStr), 0, 0, 'L');
    $pdf->Cell($colW, 7, _u((string)count($docenteCount)), 0, 1, 'L');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetDrawColor(200, 215, 240);
    $pdf->Line($pdf->lMargin, $pdf->GetY(), $pdf->GetPageWidth() - $pdf->rMargin, $pdf->GetY());
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);
    $pdf->Ln(5);

    // ── Elenco sessioni ──
    foreach ($sessioni as $s) {
        $lineH = 5.5;

        $stimaAltezza = 7
            + $lineH
            + $lineH * 2
            + (empty($s['materiali']) ? 0 : 5 + count($s['materiali']) * 5 + 3)
            + (empty(trim($s['note'] ?? '')) ? 0 : $lineH * 2);

        if ($pdf->GetY() + $stimaAltezza > $pdf->GetPageHeight() - 25) {
            $pdf->AddPage();
        }

        // ── Barra titolo sessione ──
        $pdf->SetFillColor(20, 80, 150);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8.5);

        $dataFmt = date('d/m/Y', strtotime($s['data']));
        $orario  = substr($s['ora_ingresso'], 0, 5);
        if ($s['ora_uscita']) {
            $orario .= ' - ' . substr($s['ora_uscita'], 0, 5);
        } else {
            $orario .= ' (in corso)';
        }

        $larghezza = $pdf->GetPageWidth() - $pdf->lMargin - $pdf->rMargin;
        $pdf->Cell($larghezza * 0.55, 7,
            _u("  #{$s['id']}  |  {$dataFmt}  |  {$s['lab_nome']} ({$s['aula']})"),
            0, 0, 'L', true);
        $pdf->Cell($larghezza * 0.45, 7,
            _u("Classe: {$s['classe_nome']}  |  {$orario}  "),
            0, 1, 'R', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);

        // Docenti
        $pdf->SetX($pdf->lMargin);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->Cell(35, $lineH, _u('Docenti:'), 0, 0);
        $pdf->SetFont('Arial', '', 8.5);
        if (!empty($s['docenti'])) {
            $parts = [];
            foreach ($s['docenti'] as $d) {
                $parts[] = $d['cognome'] . ' ' . $d['nome'] . ' (' . $d['tipo_presenza'] . ')';
            }
            $x = $pdf->GetX();
            $w = $pdf->GetPageWidth() - $pdf->rMargin - $x;
            $pdf->MultiCell($w, $lineH, _u(implode(', ', $parts)));
        } else {
            $pdf->Cell(0, $lineH, _u('Non registrati'), 0, 1);
        }

        // Attivita svolta
        $pdf->SetX($pdf->lMargin);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->Cell(35, $lineH, _u('Attivita svolta:'), 0, 0);
        $pdf->SetFont('Arial', '', 8.5);
        $x = $pdf->GetX();
        $w = $pdf->GetPageWidth() - $pdf->rMargin - $x;
        $pdf->MultiCell($w, $lineH, _u($s['attivita_svolta'] ?: '-'));

        // Materiali usati
        if (!empty($s['materiali'])) {
            $pdf->SetX($pdf->lMargin);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->Cell(0, $lineH, _u('Materiali utilizzati:'), 0, 1);

            $pdf->SetFillColor(210, 225, 245);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetX($pdf->lMargin + 5);
            $pdf->Cell(78, 5, _u('Materiale'),    0, 0, 'L', true);
            $pdf->Cell(27, 5, _u('Qta usata'),    0, 0, 'C', true);
            $pdf->Cell(27, 5, _u('Unita misura'), 0, 0, 'C', true);
            $pdf->Cell(0,  5, _u('Note'),          0, 1, 'L', true);

            $pdf->SetFont('Arial', '', 8);
            $altRow = false;
            foreach ($s['materiali'] as $mat) {
                $pdf->SetFillColor($altRow ? 245 : 255, $altRow ? 249 : 255, 255);
                $pdf->SetX($pdf->lMargin + 5);
                $pdf->Cell(78, 5, _u($mat['nome']),                    0, 0, 'L', $altRow);
                $pdf->Cell(27, 5, _u((string)$mat['quantita_usata']), 0, 0, 'C', $altRow);
                $pdf->Cell(27, 5, _u($mat['unita_misura']),            0, 0, 'C', $altRow);
                $pdf->Cell(0,  5, _u($mat['note'] ?? ''),              0, 1, 'L', $altRow);
                $altRow = !$altRow;
            }
            $pdf->SetFillColor(255, 255, 255);
        }

        // Note aggiuntive
        if (!empty(trim($s['note'] ?? ''))) {
            $pdf->SetX($pdf->lMargin);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->Cell(35, $lineH, _u('Note:'), 0, 0);
            $pdf->SetFont('Arial', 'I', 8.5);
            $pdf->SetTextColor(80, 80, 80);
            $x = $pdf->GetX();
            $w = $pdf->GetPageWidth() - $pdf->rMargin - $x;
            $pdf->MultiCell($w, $lineH, _u($s['note']));
            $pdf->SetTextColor(0, 0, 0);
        }

        // Separatore tra sessioni
        $pdf->SetDrawColor(200, 215, 240);
        $pdf->SetLineWidth(0.2);
        $pdf->Line($pdf->lMargin, $pdf->GetY(), $pdf->GetPageWidth() - $pdf->rMargin, $pdf->GetY());
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Ln(4);
    }

    // ── Pagina statistiche finali ──
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(20, 80, 150);
    $pdf->Cell(0, 8, _u('STATISTICHE PERIODO'), 0, 1, 'L');
    $pdf->SetDrawColor(20, 80, 150);
    $pdf->SetLineWidth(0.5);
    $pdf->Line($pdf->lMargin, $pdf->GetY(), $pdf->GetPageWidth() - $pdf->rMargin, $pdf->GetY());
    $pdf->SetLineWidth(0.2);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Ln(5);
    $pdf->SetTextColor(0, 0, 0);

    // Tabella docenti
    if (!empty($docenteCount)) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(20, 80, 150);
        $pdf->Cell(0, 6, _u('Docenti per numero di sessioni'), 0, 1);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(210, 225, 245);
        $pdf->Cell(130, 5, _u('Docente'), 0, 0, 'L', true);
        $pdf->Cell(0,   5, _u('Sessioni'), 0, 1, 'C', true);
        $pdf->SetFont('Arial', '', 8.5);
        $alt = false;
        foreach ($docenteCount as $nome => $cnt) {
            $pdf->SetFillColor($alt ? 245 : 255, $alt ? 249 : 255, 255);
            $pdf->Cell(130, 5, _u($nome), 0, 0, 'L', $alt);
            $pdf->Cell(0,   5, _u((string)$cnt), 0, 1, 'C', $alt);
            $alt = !$alt;
        }
        $pdf->Ln(5);
    }

    // Tabella materiali top 10
    if (!empty($materialeCount)) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(20, 80, 150);
        $pdf->Cell(0, 6, _u("Materiali piu' utilizzati (top 10)"), 0, 1);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(210, 225, 245);
        $pdf->Cell(130, 5, _u('Materiale'), 0, 0, 'L', true);
        $pdf->Cell(0,   5, _u('Qta totale'), 0, 1, 'C', true);
        $pdf->SetFont('Arial', '', 8.5);
        $alt = false;
        $top = 0;
        foreach ($materialeCount as $nome => $qta) {
            if (++$top > 10) break;
            $pdf->SetFillColor($alt ? 245 : 255, $alt ? 249 : 255, 255);
            $pdf->Cell(130, 5, _u($nome), 0, 0, 'L', $alt);
            $pdf->Cell(0,   5, _u((string)$qta), 0, 1, 'C', $alt);
            $alt = !$alt;
        }
    }
}

// ── Output come download ──
$nomeFile = 'resoconto_' . preg_replace('/[^a-z0-9_]/i', '_', $labLabel) . '_' . date('Ymd_Hi') . '.pdf';
$pdf->Output('D', $nomeFile);
exit;
