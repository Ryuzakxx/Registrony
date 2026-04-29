<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();

/* Docente deve aver selezionato un laboratorio */
if (isDocente()) {
    requireLabSelected();
}

$conn   = getConnection();
$today  = date('Y-m-d');
$userId = (int)getCurrentUserId();

/* ================================================================
   VISTA REGISTRO — solo per docenti con lab selezionato
   ================================================================ */
if (isDocente()) {
    $labId = (int)getSelectedLabId();

    /* Dettagli laboratorio + flag responsabile */
    $resLab = mysqli_query($conn,
        "SELECT *, (id_responsabile = $userId) AS is_responsabile
         FROM laboratori WHERE id = $labId LIMIT 1");
    $lab = mysqli_fetch_assoc($resLab);
    if (!$lab) { logout(); }

    $isResponsabile = (bool)$lab['is_responsabile'];

    /* Data da visualizzare (oggi o filtro storico) */
    $viewDate = $_GET['data'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;
    $isToday = ($viewDate === $today);

    /* KPI per questo laboratorio */
    $totS   = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio WHERE id_laboratorio = $labId AND data = '$today'"))[0];
    $totSgn = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM segnalazioni WHERE id_laboratorio = $labId AND stato IN ('aperta','in_lavorazione')"))[0];
    $totMat = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM materiali WHERE id_laboratorio = $labId AND attivo = 1 AND quantita_disponibile IS NOT NULL AND soglia_minima IS NOT NULL AND quantita_disponibile <= soglia_minima"))[0];

    /* Sessioni per la data selezionata con firme */
    $res = mysqli_query($conn, "
        SELECT s.id, s.data, s.ora_ingresso, s.ora_uscita, s.attivita_svolta, s.note,
               c.nome AS classe,
               GROUP_CONCAT(
                   CONCAT(u.cognome, ' ', u.nome, '|', f.tipo_presenza)
                   ORDER BY f.tipo_presenza SEPARATOR ';;'
               ) AS firme_raw
        FROM sessioni_laboratorio s
        JOIN classi c ON s.id_classe = c.id
        LEFT JOIN firme_sessioni f ON s.id = f.id_sessione
        LEFT JOIN utenti u ON f.id_docente = u.id
        WHERE s.id_laboratorio = $labId AND s.data = '$viewDate'
        GROUP BY s.id
        ORDER BY s.ora_ingresso
    ");
    $sessions = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['firme'] = [];
        if (!empty($row['firme_raw'])) {
            foreach (explode(';;', $row['firme_raw']) as $f) {
                [$nome, $tipo] = array_pad(explode('|', $f, 2), 2, '');
                $row['firme'][] = ['nome' => trim($nome), 'tipo' => trim($tipo)];
            }
        }
        $sessions[] = $row;
    }

    /* Segnalazioni aperte per questo laboratorio */
    $resSgns = mysqli_query($conn, "
        SELECT sg.id, sg.titolo, sg.priorita, sg.stato, sg.data_segnalazione,
               CONCAT(u.cognome, ' ', u.nome) AS segnalato_da
        FROM segnalazioni sg
        JOIN utenti u ON sg.id_utente = u.id
        WHERE sg.id_laboratorio = $labId AND sg.stato IN ('aperta','in_lavorazione')
        ORDER BY FIELD(sg.priorita,'urgente','alta','media','bassa'), sg.data_segnalazione DESC
        LIMIT 6
    ");
    $segnalazioni = [];
    while ($r = mysqli_fetch_assoc($resSgns)) $segnalazioni[] = $r;

    /* Materiali del laboratorio */
    $resMat2 = mysqli_query($conn, "
        SELECT nome, unita_misura, quantita_disponibile, soglia_minima
        FROM materiali
        WHERE id_laboratorio = $labId AND attivo = 1
        ORDER BY nome
    ");
    $materiali = [];
    while ($r = mysqli_fetch_assoc($resMat2)) $materiali[] = $r;

    /* Ultime 30 date con sessioni (per calendario mini) */
    $resDates = mysqli_query($conn, "
        SELECT DISTINCT data FROM sessioni_laboratorio
        WHERE id_laboratorio = $labId AND data <= '$today'
        ORDER BY data DESC LIMIT 30
    ");
    $pastDates = [];
    while ($r = mysqli_fetch_assoc($resDates)) $pastDates[] = $r['data'];

    $pageTitle = 'Registro — ' . htmlspecialchars($lab['nome']);
    require_once __DIR__ . '/includes/header.php';
?>

<!-- ===================== BANNER LABORATORIO ===================== -->
<div class="registro-banner">
    <div class="registro-banner-left">
        <span class="registro-lab-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </span>
        <div>
            <div class="registro-lab-nome"><?= htmlspecialchars($lab['nome']) ?></div>
            <div class="registro-lab-meta">
                Aula: <strong><?= htmlspecialchars($lab['aula']) ?></strong>
                <?php if ($isResponsabile): ?>
                    &nbsp;&#183;&nbsp;<span class="badge-resp">&#9733; Responsabile</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
        Cambia laboratorio
    </a>
</div>

<!-- ===================== KPI CARDS ===================== -->
<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card">
        <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totS ?></div>
            <div class="stat-label">Sessioni oggi</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totSgn ?></div>
            <div class="stat-label">Segnalazioni aperte</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totMat ?></div>
            <div class="stat-label">Materiali in esaurimento</div>
        </div>
    </div>
</div>

<!-- ===================== SESSIONI (REGISTRO GIORNALIERO) ===================== -->
<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= $isToday ? 'Sessioni di oggi' : 'Sessioni del ' . date('d/m/Y', strtotime($viewDate)) ?>
        </h3>
        <div class="d-flex gap-2 align-center flex-wrap">
            <!-- Navigazione storico -->
            <form method="GET" class="d-flex gap-1 align-center">
                <label for="data" style="font-size:.82rem;color:#666;white-space:nowrap">&#128197; Data:</label>
                <input type="date" id="data" name="data" value="<?= htmlspecialchars($viewDate) ?>"
                    max="<?= $today ?>" class="form-control" style="width:145px;padding:4px 8px;font-size:.85rem">
                <button type="submit" class="btn btn-secondary btn-sm">Vai</button>
                <?php if (!$isToday): ?>
                    <a href="<?= BASE_PATH ?>/index.php" class="btn btn-primary btn-sm">Oggi</a>
                <?php endif; ?>
            </form>
            <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="btn btn-primary btn-sm">+ Nuova</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($sessions)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h4>Nessuna sessione <?= $isToday ? 'oggi' : 'in questa data' ?></h4>
                <p><?= $isToday ? 'Clicca "+ Nuova" per registrare la prima sessione della giornata.' : 'Non ci sono sessioni registrate per questa data.' ?></p>
            </div>
        <?php else: ?>
            <!-- Timeline registro -->
            <div class="registro-timeline">
                <?php foreach ($sessions as $idx => $s): ?>
                <div class="registro-entry">
                    <div class="registro-orario">
                        <span class="ora-ingresso"><?= htmlspecialchars(substr($s['ora_ingresso'], 0, 5)) ?></span>
                        <?php if ($s['ora_uscita']): ?>
                            <span class="ora-sep">&#8595;</span>
                            <span class="ora-uscita"><?= htmlspecialchars(substr($s['ora_uscita'], 0, 5)) ?></span>
                        <?php else: ?>
                            <span class="badge badge-success" style="font-size:.7rem;margin-top:4px">In corso</span>
                        <?php endif; ?>
                    </div>
                    <div class="registro-body">
                        <div class="registro-header-row">
                            <span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span>
                            <a href="<?= BASE_PATH ?>/pages/sessioni/dettaglio.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" style="padding:2px 10px;font-size:.78rem">Dettaglio</a>
                        </div>
                        <?php if (!empty($s['attivita_svolta'])): ?>
                            <div class="registro-attivita"><?= htmlspecialchars($s['attivita_svolta']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($s['note'])): ?>
                            <div class="registro-note"><em>Note: <?= htmlspecialchars($s['note']) ?></em></div>
                        <?php endif; ?>
                        <!-- Firme -->
                        <div class="registro-firme">
                            <?php if (empty($s['firme'])): ?>
                                <span class="firma-vuota">&#9995; Nessuna firma registrata</span>
                            <?php else: ?>
                                <?php foreach ($s['firme'] as $f): ?>
                                    <?php
                                        $tipoClass = match($f['tipo']) {
                                            'docente'          => 'firma-docente',
                                            'tecnico'          => 'firma-tecnico',
                                            'supplente'        => 'firma-supplente',
                                            default            => 'firma-altro'
                                        };
                                        $tipoLabel = match($f['tipo']) {
                                            'docente'          => '&#9997; Docente',
                                            'tecnico'          => '&#128296; Tecnico',
                                            'supplente'        => '&#128100; Supplente',
                                            default            => '&#128100; ' . htmlspecialchars($f['tipo'])
                                        };
                                    ?>
                                    <span class="firma-chip <?= $tipoClass ?>">
                                        <?= $tipoLabel ?> &mdash; <?= htmlspecialchars($f['nome']) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($idx < count($sessions) - 1): ?>
                    <div class="registro-divider"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Mini-storico: ultime date con sessioni -->
        <?php if (!empty($pastDates)): ?>
            <div class="storico-mini">
                <span class="storico-mini-label">&#128337; Storico rapido:</span>
                <?php foreach ($pastDates as $d):
                    if ($d === $today) continue; // già mostrato
                ?>
                    <a href="?data=<?= $d ?>" class="storico-chip <?= $viewDate === $d ? 'storico-chip-active' : '' ?>">
                        <?= date('d/m', strtotime($d)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== SEGNALAZIONI ===================== -->
<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Segnalazioni aperte
        </h3>
        <div class="d-flex gap-2">
            <a href="<?= BASE_PATH ?>/pages/segnalazioni/nuova.php" class="btn btn-warning btn-sm">+ Segnala</a>
            <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary btn-sm">Vedi tutte</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($segnalazioni)): ?>
            <div class="empty-state">
                <div class="empty-icon" style="color:#22c55e">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h4>Tutto ok! Nessuna segnalazione aperta.</h4>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Titolo</th><th>Priorità</th><th>Stato</th><th>Segnalato da</th><th>Data</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($segnalazioni as $sg): ?>
                        <?php
                            $bc = match($sg['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' };
                            $sc = $sg['stato'] === 'aperta' ? 'badge-danger' : 'badge-warning';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sg['titolo']) ?></strong></td>
                            <td><span class="badge <?= $bc ?>"><?= $sg['priorita'] ?></span></td>
                            <td><span class="badge <?= $sc ?>"><?= str_replace('_',' ',$sg['stato']) ?></span></td>
                            <td><?= htmlspecialchars($sg['segnalato_da']) ?></td>
                            <td><?= date('d/m/Y', strtotime($sg['data_segnalazione'])) ?></td>
                            <td><a href="<?= BASE_PATH ?>/pages/segnalazioni/dettaglio.php?id=<?= $sg['id'] ?>" class="btn btn-primary btn-sm">Apri</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== MATERIALI ===================== -->
<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            Materiali del laboratorio
        </h3>
        <?php if ($isResponsabile): ?>
            <a href="<?= BASE_PATH ?>/pages/materiali/gestione.php?laboratorio=<?= $labId ?>" class="btn btn-primary btn-sm">&#128393; Gestisci</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($materiali)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <h4>Nessun materiale registrato</h4>
                <?php if ($isResponsabile): ?>
                    <p><a href="<?= BASE_PATH ?>/pages/materiali/gestione.php">Aggiungi il primo materiale</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="materiali-grid">
                <?php foreach ($materiali as $m):
                    $stato = 'ok';
                    if ($m['quantita_disponibile'] !== null && $m['quantita_disponibile'] <= 0) $stato = 'esaurito';
                    elseif ($m['quantita_disponibile'] !== null && $m['soglia_minima'] !== null && $m['quantita_disponibile'] <= $m['soglia_minima']) $stato = 'basso';
                ?>
                <div class="materiale-card materiale-<?= $stato ?>">
                    <div class="materiale-nome"><?= htmlspecialchars($m['nome']) ?></div>
                    <div class="materiale-qty">
                        <?php if ($m['quantita_disponibile'] !== null): ?>
                            <strong><?= $m['quantita_disponibile'] ?></strong>
                            <span class="materiale-unita"><?= htmlspecialchars($m['unita_misura'] ?? '') ?></span>
                        <?php else: ?>
                            <span style="color:#aaa">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="materiale-stato">
                        <?php if ($stato === 'esaurito'): ?>
                            <span class="badge badge-danger">Esaurito</span>
                        <?php elseif ($stato === 'basso'): ?>
                            <span class="badge badge-warning">In esaurimento</span>
                        <?php else: ?>
                            <span class="badge badge-success">OK</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ---- Registro banner ---- */
.registro-banner {
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;
    background:#fff; border:1.5px solid #d4d0ca; border-radius:10px;
    padding:1rem 1.25rem; margin-bottom:1.25rem;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.registro-banner-left { display:flex; align-items:center; gap:.85rem; }
.registro-lab-icon { color:#01696f; flex-shrink:0; }
.registro-lab-nome { font-size:1.15rem; font-weight:700; color:#1a1a1a; }
.registro-lab-meta { font-size:.82rem; color:#777; margin-top:2px; }
.badge-resp { background:#01696f; color:#fff; border-radius:20px; padding:1px 8px; font-size:.75rem; font-weight:600; }

/* ---- Timeline sessioni ---- */
.registro-timeline { display:flex; flex-direction:column; gap:0; }
.registro-entry { display:flex; gap:1.1rem; padding:.9rem 0; }
.registro-divider { height:1px; background:#e8e6e0; margin:0 0 0 68px; }
.registro-orario {
    display:flex; flex-direction:column; align-items:center; min-width:52px;
    font-family:monospace; font-size:.85rem; color:#555; flex-shrink:0; padding-top:2px;
}
.ora-ingresso { font-weight:700; color:#01696f; }
.ora-sep { color:#bbb; font-size:.75rem; }
.ora-uscita { color:#888; }
.registro-body { flex:1; min-width:0; }
.registro-header-row { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.4rem; }
.registro-attivita { font-size:.9rem; color:#333; margin:.3rem 0; line-height:1.45; }
.registro-note { font-size:.82rem; color:#888; margin-top:.25rem; }
.registro-firme { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.5rem; }
.firma-chip {
    display:inline-flex; align-items:center; gap:.3rem;
    font-size:.78rem; font-weight:500; padding:3px 10px; border-radius:20px;
}
.firma-docente  { background:#e8f4f4; color:#01696f; border:1px solid #b6d9d8; }
.firma-tecnico  { background:#fff4e6; color:#c05500; border:1px solid #ffd4a3; }
.firma-supplente{ background:#f0f0ff; color:#4f46e5; border:1px solid #c5c3ff; }
.firma-altro    { background:#f5f5f5; color:#555;    border:1px solid #ddd; }
.firma-vuota    { font-size:.8rem; color:#bbb; font-style:italic; }

/* ---- Storico rapido ---- */
.storico-mini { margin-top:1.25rem; padding-top:1rem; border-top:1px solid #eee; display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; }
.storico-mini-label { font-size:.78rem; color:#999; margin-right:.25rem; white-space:nowrap; }
.storico-chip {
    font-size:.78rem; padding:3px 9px; border-radius:20px;
    background:#f3f2ef; color:#555; border:1px solid #ddd;
    text-decoration:none; transition:background .15s, border-color .15s;
}
.storico-chip:hover { background:#e8f4f4; border-color:#01696f; color:#01696f; }
.storico-chip-active { background:#01696f; color:#fff; border-color:#01696f; }

/* ---- Materiali grid ---- */
.materiali-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.75rem; }
.materiale-card {
    background:#f9f8f5; border:1.5px solid #e0ddd8; border-radius:8px;
    padding:.75rem 1rem; display:flex; flex-direction:column; gap:.3rem;
}
.materiale-ok      { border-left:3px solid #22c55e; }
.materiale-basso   { border-left:3px solid #f59e0b; }
.materiale-esaurito{ border-left:3px solid #ef4444; }
.materiale-nome { font-weight:600; font-size:.88rem; color:#1a1a1a; }
.materiale-qty  { font-size:1.1rem; color:#333; }
.materiale-unita{ font-size:.78rem; color:#999; margin-left:2px; }
.materiale-stato{ margin-top:2px; }
</style>

<?php
    require_once __DIR__ . '/includes/footer.php';
    exit; /* Fine vista docente */
}

/* ================================================================
   VISTA GLOBALE — Admin / Tecnico
   ================================================================ */
$pageTitle = 'Dashboard';

/* Filtra per lab del tecnico se non è admin */
$labFilter = '';
if (isTecnico()) {
    $techLabs = getTechnicianLabs($userId);
    if (!empty($techLabs)) {
        $ids = implode(',', array_column($techLabs, 'id'));
        $labFilter = "AND s.id_laboratorio IN ($ids)";
        $labFilterMat = "AND id_laboratorio IN ($ids)";
        $labFilterSgn = "AND sg.id_laboratorio IN ($ids)";
        $labFilterLab = "AND id IN ($ids)";
    } else {
        $labFilter = $labFilterMat = $labFilterSgn = $labFilterLab = 'AND 1=0';
    }
} else {
    $labFilter = $labFilterMat = $labFilterSgn = $labFilterLab = '';
}

/* KPI */
$resT = mysqli_query($conn, "SELECT COUNT(*) FROM laboratori WHERE attivo = 1 $labFilterLab");
$totLabs = mysqli_fetch_row($resT)[0];

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio s WHERE s.data = '$today' $labFilter");
$totSessioniOggi = mysqli_fetch_row($resT)[0];

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM segnalazioni sg WHERE sg.stato IN ('aperta','in_lavorazione') $labFilterSgn");
$totSegnAperte = mysqli_fetch_row($resT)[0];

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM materiali WHERE attivo = 1 AND quantita_disponibile IS NOT NULL AND soglia_minima IS NOT NULL AND quantita_disponibile <= soglia_minima $labFilterMat");
$totMatEsaurimento = mysqli_fetch_row($resT)[0];

/* Sessioni oggi */
$result = mysqli_query($conn, "
    SELECT s.id, s.data, s.ora_ingresso, s.ora_uscita, s.attivita_svolta,
           l.nome AS laboratorio, l.aula, c.nome AS classe,
           GROUP_CONCAT(CONCAT(u.cognome, ' ', u.nome, ' (', f.tipo_presenza, ')') ORDER BY f.tipo_presenza SEPARATOR ', ') AS docenti
    FROM sessioni_laboratorio s
    JOIN laboratori l ON s.id_laboratorio = l.id
    JOIN classi c ON s.id_classe = c.id
    LEFT JOIN firme_sessioni f ON s.id = f.id_sessione
    LEFT JOIN utenti u ON f.id_docente = u.id
    WHERE s.data = '$today' $labFilter
    GROUP BY s.id
    ORDER BY s.ora_ingresso DESC
");
$sessioniOggi = [];
while ($row = mysqli_fetch_assoc($result)) $sessioniOggi[] = $row;

/* Segnalazioni aperte */
$result = mysqli_query($conn, "
    SELECT sg.id, sg.titolo, sg.priorita, sg.stato, sg.data_segnalazione,
           l.nome AS laboratorio, CONCAT(u.cognome, ' ', u.nome) AS segnalato_da
    FROM segnalazioni sg
    JOIN laboratori l ON sg.id_laboratorio = l.id
    JOIN utenti u ON sg.id_utente = u.id
    WHERE sg.stato IN ('aperta','in_lavorazione') $labFilterSgn
    ORDER BY FIELD(sg.priorita, 'urgente','alta','media','bassa'), sg.data_segnalazione DESC
    LIMIT 5
");
$segnalazioni = [];
while ($row = mysqli_fetch_assoc($result)) $segnalazioni[] = $row;

require_once __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totLabs ?></div>
            <div class="stat-label"><?= isTecnico() ? 'Miei laboratori' : 'Laboratori attivi' ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totSessioniOggi ?></div>
            <div class="stat-label">Sessioni oggi</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totSegnAperte ?></div>
            <div class="stat-label">Segnalazioni aperte</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totMatEsaurimento ?></div>
            <div class="stat-label">Materiali in esaurimento</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Sessioni di oggi (<?= date('d/m/Y') ?>)
        </h3>
        <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="btn btn-primary btn-sm">+ Nuova Sessione</a>
    </div>
    <div class="card-body">
        <?php if (empty($sessioniOggi)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h4>Nessuna sessione oggi</h4>
                <p>Non ci sono sessioni registrate per oggi.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Laboratorio</th><th>Aula</th><th>Classe</th><th>Ingresso</th><th>Uscita</th><th>Firme</th><th>Attività</th></tr></thead>
                    <tbody>
                        <?php foreach ($sessioniOggi as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['laboratorio']) ?></strong></td>
                            <td><?= htmlspecialchars($s['aula']) ?></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span></td>
                            <td><?= htmlspecialchars(substr($s['ora_ingresso'],0,5)) ?></td>
                            <td><?= $s['ora_uscita'] ? htmlspecialchars(substr($s['ora_uscita'],0,5)) : '<span class="badge badge-success">In corso</span>' ?></td>
                            <td><?= htmlspecialchars($s['docenti'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($s['attivita_svolta'] ?? '', 0, 60, '...')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Segnalazioni aperte
        </h3>
        <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary btn-sm">Vedi tutte</a>
    </div>
    <div class="card-body">
        <?php if (empty($segnalazioni)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h4>Nessuna segnalazione aperta</h4>
                <p>Tutto funziona correttamente.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Titolo</th><th>Laboratorio</th><th>Priorità</th><th>Stato</th><th>Segnalato da</th><th>Data</th></tr></thead>
                    <tbody>
                        <?php foreach ($segnalazioni as $sg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sg['titolo']) ?></strong></td>
                            <td><?= htmlspecialchars($sg['laboratorio']) ?></td>
                            <td>
                                <?php $bc = match($sg['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' }; ?>
                                <span class="badge <?= $bc ?>"><?= $sg['priorita'] ?></span>
                            </td>
                            <td><span class="badge <?= $sg['stato']==='aperta' ? 'badge-danger' : 'badge-warning' ?>"><?= $sg['stato'] ?></span></td>
                            <td><?= htmlspecialchars($sg['segnalato_da']) ?></td>
                            <td><?= date('d/m/Y', strtotime($sg['data_segnalazione'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
