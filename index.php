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

    $resLab = mysqli_query($conn,
        "SELECT *, (id_responsabile = $userId) AS is_responsabile
         FROM laboratori WHERE id = $labId LIMIT 1");
    $lab = mysqli_fetch_assoc($resLab);
    if (!$lab) { logout(); }

    $isResponsabile = (bool)$lab['is_responsabile'];

    $viewDate = $_GET['data'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;
    $isToday = ($viewDate === $today);

    $totS   = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio WHERE id_laboratorio = $labId AND data = '$today'"))[0];
    $totSgn = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM segnalazioni WHERE id_laboratorio = $labId AND stato IN ('aperta','in_lavorazione')"))[0];
    $totMat = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM materiali WHERE id_laboratorio = $labId AND attivo = 1 AND quantita_disponibile IS NOT NULL AND soglia_minima IS NOT NULL AND quantita_disponibile <= soglia_minima"))[0];

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

    $resDates = mysqli_query($conn, "
        SELECT DISTINCT data FROM sessioni_laboratorio
        WHERE id_laboratorio = $labId AND data <= '$today'
        ORDER BY data DESC LIMIT 30
    ");
    $pastDates = [];
    while ($r = mysqli_fetch_assoc($resDates)) $pastDates[] = $r['data'];

    $pageTitle = L('nav_registro_attivo') . ' — ' . htmlspecialchars($lab['nome']);
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
                <?= L('label_aula') ?>: <strong><?= htmlspecialchars($lab['aula']) ?></strong>
                <?php if ($isResponsabile): ?>
                    &nbsp;&#183;&nbsp;<span class="badge-resp"><?= L('label_responsabile') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- KPI inline nel banner -->
    <div class="banner-kpi">
        <div class="banner-kpi-item">
            <span class="banner-kpi-val green"><?= $totS ?></span>
            <span class="banner-kpi-label"><?= L('dash_sessioni_oggi') ?></span>
        </div>
        <?php if ($totSgn > 0): ?>
        <div class="banner-kpi-item">
            <span class="banner-kpi-val orange"><?= $totSgn ?></span>
            <span class="banner-kpi-label"><?= L('dash_segnalazioni_aperte') ?></span>
        </div>
        <?php endif; ?>
        <?php if ($totMat > 0): ?>
        <div class="banner-kpi-item">
            <span class="banner-kpi-val red"><?= $totMat ?></span>
            <span class="banner-kpi-label"><?= L('dash_mat_esaurimento') ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== SESSIONI (REGISTRO GIORNALIERO) ===================== -->
<div class="card">
    <div class="card-header dash-card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= $isToday ? L('dash_sessioni_oggi_titolo') : L('sess_titolo_dettaglio') . ' ' . date('d/m/Y', strtotime($viewDate)) ?>
        </h3>
        <div class="dash-header-actions">
            <form method="GET" class="dash-date-form">
                <input type="date" id="data" name="data" value="<?= htmlspecialchars($viewDate) ?>"
                    max="<?= $today ?>" class="form-control dash-date-input" aria-label="<?= L('data') ?>">
                <button type="submit" class="btn btn-secondary btn-sm"><?= L('filtra') ?></button>
                <?php if (!$isToday): ?>
                    <a href="<?= BASE_PATH ?>/index.php" class="btn btn-secondary btn-sm"><?= L('dash_sessioni_oggi') ?></a>
                <?php endif; ?>
            </form>
            <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="btn btn-primary btn-sm dash-btn-nuova">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <?= L('sess_btn_nuova') ?>
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($sessions)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h4><?= $isToday ? L('dash_nessuna_sessione') : L('sess_nessuna') ?></h4>
                <p><?= $isToday ? L('sess_nessuna_oggi') : L('sess_nessuna') ?></p>
            </div>
        <?php else: ?>
            <div class="registro-timeline">
                <?php foreach ($sessions as $idx => $s):
                    $durata = '';
                    if ($s['ora_ingresso'] && $s['ora_uscita']) {
                        $inizio = strtotime($s['ora_ingresso']);
                        $fine   = strtotime($s['ora_uscita']);
                        $min    = round(($fine - $inizio) / 60);
                        if ($min > 0) $durata = $min . ' min';
                    }
                ?>
                <div class="registro-entry">
                    <div class="registro-orario">
                        <span class="ora-ingresso"><?= htmlspecialchars(substr($s['ora_ingresso'], 0, 5)) ?></span>
                        <?php if ($s['ora_uscita']): ?>
                            <span class="ora-sep">&#8595;</span>
                            <span class="ora-uscita"><?= htmlspecialchars(substr($s['ora_uscita'], 0, 5)) ?></span>
                            <?php if ($durata): ?><span class="ora-durata"><?= $durata ?></span><?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-success" style="font-size:.68rem;margin-top:4px;padding:2px 6px"><?= L('sess_in_corso') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="registro-body">
                        <div class="registro-header-row">
                            <span class="badge badge-primary badge-classe"><?= htmlspecialchars($s['classe']) ?></span>
                            <a href="<?= BASE_PATH ?>/pages/sessioni/dettaglio.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm btn-dettagli"><?= L('dettagli') ?></a>
                        </div>
                        <?php if (!empty($s['attivita_svolta'])): ?>
                            <div class="registro-attivita"><?= htmlspecialchars($s['attivita_svolta']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($s['note'])): ?>
                            <div class="registro-note"><em><?= L('note') ?>: <?= htmlspecialchars($s['note']) ?></em></div>
                        <?php endif; ?>
                        <div class="registro-firme">
                            <?php if (empty($s['firme'])): ?>
                                <span class="firma-vuota">&#9995; <?= L('firme_nessuna') ?></span>
                            <?php else: ?>
                                <?php foreach ($s['firme'] as $f): ?>
                                    <?php
                                        $tipoClass = match($f['tipo']) {
                                            'docente'    => 'firma-docente',
                                            'tecnico'    => 'firma-tecnico',
                                            'supplente'  => 'firma-supplente',
                                            default      => 'firma-altro'
                                        };
                                        $tipoLabel = match($f['tipo']) {
                                            'docente'    => '&#9997; ' . L('firme_docente'),
                                            'tecnico'    => '&#128296; ' . L('firme_tipo'),
                                            'supplente'  => '&#128100; ' . L('sess_docente_compresenza'),
                                            default      => '&#128100; ' . htmlspecialchars($f['tipo'])
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

        <?php if (!empty($pastDates)): ?>
            <div class="storico-mini">
                <span class="storico-mini-label">&#128337; <?= L('sess_titolo_lista') ?>:</span>
                <?php foreach ($pastDates as $d):
                    if ($d === $today) continue;
                ?>
                    <a href="?data=<?= $d ?>" class="storico-chip <?= $viewDate === $d ? 'storico-chip-active' : '' ?>">
                        <?= date('d/m', strtotime($d)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ======================================================
   DASHBOARD — stili specifici (registro docente)
   ====================================================== */

/* Banner laboratorio */
.registro-banner {
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;
    background:#fff; border:1.5px solid #d4d0ca; border-radius:12px;
    padding:1rem 1.25rem; margin-bottom:1.25rem;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.registro-banner-left { display:flex; align-items:center; gap:.85rem; min-width:0; }
.registro-lab-icon { color:#01696f; flex-shrink:0; }
.registro-lab-nome { font-size:1.05rem; font-weight:700; color:#1a1a1a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.registro-lab-meta { font-size:.82rem; color:#777; margin-top:2px; }
.badge-resp { background:#01696f; color:#fff; border-radius:20px; padding:1px 8px; font-size:.75rem; font-weight:600; }

/* KPI inline nel banner */
.banner-kpi {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-shrink: 0;
}
.banner-kpi-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
}
.banner-kpi-val {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.banner-kpi-val.green  { color: #16a34a; }
.banner-kpi-val.orange { color: #d97706; }
.banner-kpi-val.red    { color: #dc2626; }
.banner-kpi-label {
    font-size: .7rem;
    color: #888;
    text-align: center;
    max-width: 70px;
    line-height: 1.2;
}

/* Card header con filtro data */
.dash-card-header { flex-wrap: wrap; gap: 10px; }
.dash-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
    justify-content: flex-end;
}
.dash-date-form {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}
.dash-date-input {
    width: 138px;
    padding: 5px 8px;
    font-size: .84rem;
}
.dash-btn-nuova { white-space: nowrap; }

/* Timeline sessioni */
.registro-timeline { display:flex; flex-direction:column; gap:0; }
.registro-entry { display:flex; gap:1.1rem; padding:1rem 0; }
.registro-divider { height:1px; background:#edeae4; margin:0 0 0 68px; }
.registro-orario {
    display:flex; flex-direction:column; align-items:center; min-width:52px;
    font-family:monospace; font-size:.85rem; color:#555; flex-shrink:0; padding-top:2px;
}
.ora-ingresso { font-weight:700; color:#01696f; font-size:.9rem; }
.ora-sep { color:#ccc; font-size:.7rem; line-height:1; }
.ora-uscita { color:#777; font-size:.82rem; }
.ora-durata { font-size:.68rem; color:#aaa; margin-top:2px; letter-spacing:.01em; }
.registro-body { flex:1; min-width:0; }
.registro-header-row { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.5rem; }
.badge-classe { font-size:.82rem; padding: 3px 10px; }
.btn-dettagli { padding:3px 12px; font-size:.78rem; }
.registro-attivita { font-size:.88rem; color:#222; margin:.3rem 0; line-height:1.5; }
.registro-note { font-size:.8rem; color:#999; margin-top:.2rem; font-style:italic; }
.registro-firme { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.55rem; }
.firma-chip {
    display:inline-flex; align-items:center; gap:.3rem;
    font-size:.76rem; font-weight:500; padding:3px 10px; border-radius:20px;
}
.firma-docente  { background:#e8f4f4; color:#01696f; border:1px solid #b6d9d8; }
.firma-tecnico  { background:#fff4e6; color:#c05500; border:1px solid #ffd4a3; }
.firma-supplente{ background:#f0f0ff; color:#4f46e5; border:1px solid #c5c3ff; }
.firma-altro    { background:#f5f5f5; color:#555;    border:1px solid #ddd; }
.firma-vuota    { font-size:.78rem; color:#ccc; }

/* Storico rapido */
.storico-mini { margin-top:1.25rem; padding-top:1rem; border-top:1px solid #eee; display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; }
.storico-mini-label { font-size:.76rem; color:#aaa; margin-right:.25rem; white-space:nowrap; }
.storico-chip {
    font-size:.76rem; padding:3px 9px; border-radius:20px;
    background:#f3f2ef; color:#555; border:1px solid #ddd;
    text-decoration:none; transition:background .15s, border-color .15s;
}
.storico-chip:hover { background:#e8f4f4; border-color:#01696f; color:#01696f; }
.storico-chip-active { background:#01696f; color:#fff; border-color:#01696f; }

/* ---- MOBILE (< 768px) ---- */
@media (max-width: 767px) {

    /* Banner: verticale compatto */
    .registro-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: .85rem 1rem;
        gap: .5rem;
        border-radius: 12px;
        margin-bottom: 1rem;
    }
    .registro-banner-left { gap: .65rem; }
    .registro-lab-nome { font-size: .95rem; }

    /* KPI inline orizzontali in fondo al banner */
    .banner-kpi {
        gap: 1rem;
        width: 100%;
        padding-top: .4rem;
        border-top: 1px solid #eee;
    }
    .banner-kpi-val { font-size: 1.25rem; }
    .banner-kpi-label { font-size: .66rem; }

    /* Card header sessioni: impila verticalmente */
    .dash-card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px 14px;
    }
    .dash-card-header h3 { font-size: 13.5px; }
    .dash-header-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 7px;
        justify-content: flex-start;
    }
    .dash-date-form {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 6px;
        width: 100%;
    }
    .dash-date-form a.btn { grid-column: 1 / -1; justify-content: center; }
    .dash-date-input {
        width: 100%;
        font-size: 16px; /* previene zoom iOS */
    }
    .dash-btn-nuova { width: 100%; justify-content: center; }

    /* Registro timeline ottimizzato */
    .registro-entry { gap: .55rem; padding: .75rem 0; }
    .registro-orario { min-width: 44px; font-size: .78rem; }
    .registro-divider { margin-left: 50px; }
    .registro-attivita { font-size: .83rem; }
    .firma-chip { font-size: .71rem; padding: 2px 7px; }
    .btn-dettagli { font-size: .74rem; padding: 3px 9px; }

    /* Storico: scroll orizzontale */
    .storico-mini {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 4px;
        gap: .3rem;
    }
    .storico-chip { flex-shrink: 0; font-size: .74rem; }
    .storico-mini-label { flex-shrink: 0; }
}

/* ---- TABLET (768–1023px) ---- */
@media (min-width: 768px) and (max-width: 1023px) {
    .dash-card-header { flex-wrap: wrap; }
    .dash-header-actions { flex-wrap: wrap; }
    .registro-banner .banner-kpi { gap: 1.5rem; }
}
</style>

<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/* ================================================================
   VISTA GLOBALE — Admin / Tecnico
   ================================================================ */
$pageTitle = L('dash_titolo');

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

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM laboratori WHERE attivo = 1 $labFilterLab");
$totLabs = mysqli_fetch_row($resT)[0];

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio s WHERE s.data = '$today' $labFilter");
$totSessioniOggi = mysqli_fetch_row($resT)[0];

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM segnalazioni sg WHERE sg.stato IN ('aperta','in_lavorazione') $labFilterSgn");
$totSegnAperte = mysqli_fetch_row($resT)[0];

$resT = mysqli_query($conn, "SELECT COUNT(*) FROM materiali WHERE attivo = 1 AND quantita_disponibile IS NOT NULL AND soglia_minima IS NOT NULL AND quantita_disponibile <= soglia_minima $labFilterMat");
$totMatEsaurimento = mysqli_fetch_row($resT)[0];

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

<div class="stats-grid dash-kpi" style="margin-bottom:1.25rem">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totLabs ?></div>
            <div class="stat-label"><?= isTecnico() ? L('nav_laboratori') : L('dash_lab_attivi') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totSessioniOggi ?></div>
            <div class="stat-label"><?= L('dash_sessioni_oggi') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totSegnAperte ?></div>
            <div class="stat-label"><?= L('dash_segnalazioni_aperte') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totMatEsaurimento ?></div>
            <div class="stat-label"><?= L('dash_mat_esaurimento') ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= L('dash_sessioni_oggi_titolo') ?> (<?= date('d/m/Y') ?>)
        </h3>
        <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="btn btn-primary btn-sm"><?= L('sess_btn_nuova') ?></a>
    </div>
    <div class="card-body">
        <?php if (empty($sessioniOggi)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h4><?= L('dash_nessuna_sessione') ?></h4>
                <p><?= L('sess_nessuna_oggi') ?></p>
            </div>
        <?php else: ?>
            <!-- DESKTOP: tabella -->
            <div class="table-responsive dash-table-desktop">
                <table class="table">
                    <thead><tr>
                        <th><?= L('sess_laboratorio') ?></th>
                        <th><?= L('label_aula') ?></th>
                        <th><?= L('sess_classe') ?></th>
                        <th><?= L('sess_ora_ingresso') ?></th>
                        <th><?= L('sess_ora_uscita') ?></th>
                        <th><?= L('firme_titolo') ?></th>
                        <th><?= L('sess_attivita') ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($sessioniOggi as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['laboratorio']) ?></strong></td>
                            <td><?= htmlspecialchars($s['aula']) ?></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span></td>
                            <td><?= htmlspecialchars(substr($s['ora_ingresso'],0,5)) ?></td>
                            <td><?= $s['ora_uscita'] ? htmlspecialchars(substr($s['ora_uscita'],0,5)) : '<span class="badge badge-success">' . L('sess_in_corso') . '</span>' ?></td>
                            <td><?= htmlspecialchars($s['docenti'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($s['attivita_svolta'] ?? '', 0, 60, '...')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- MOBILE: card list -->
            <div class="dash-card-list dash-table-mobile">
                <?php foreach ($sessioniOggi as $s): ?>
                <div class="dcl-item">
                    <div class="dcl-main">
                        <span class="dcl-title"><?= htmlspecialchars($s['laboratorio']) ?> — <?= htmlspecialchars($s['aula']) ?></span>
                        <span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span>
                    </div>
                    <div class="dcl-meta">
                        <span><?= htmlspecialchars(substr($s['ora_ingresso'],0,5)) ?><?= $s['ora_uscita'] ? ' → '.htmlspecialchars(substr($s['ora_uscita'],0,5)) : ' <span class="badge badge-success">'.L('sess_in_corso').'</span>' ?></span>
                        <span><?= htmlspecialchars($s['docenti'] ?? '—') ?></span>
                    </div>
                    <?php if (!empty($s['attivita_svolta'])): ?>
                    <div style="font-size:.8rem;color:#555;line-height:1.4"><?= htmlspecialchars(mb_strimwidth($s['attivita_svolta'], 0, 80, '...')) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <?= L('dash_segnalazioni_titolo') ?>
        </h3>
        <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="btn btn-secondary btn-sm"><?= L('vedi_tutte') ?></a>
    </div>
    <div class="card-body">
        <?php if (empty($segnalazioni)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h4><?= L('dash_tutto_ok') ?></h4>
                <p><?= L('dash_nessuna_segn') ?></p>
            </div>
        <?php else: ?>
            <!-- DESKTOP: tabella -->
            <div class="table-responsive dash-table-desktop">
                <table class="table">
                    <thead><tr>
                        <th><?= L('segn_titolo_campo') ?></th>
                        <th><?= L('sess_laboratorio') ?></th>
                        <th><?= L('segn_priorita') ?></th>
                        <th><?= L('segn_stato') ?></th>
                        <th><?= L('segn_segnalato_da') ?></th>
                        <th><?= L('segn_data') ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($segnalazioni as $sg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sg['titolo']) ?></strong></td>
                            <td><?= htmlspecialchars($sg['laboratorio']) ?></td>
                            <td>
                                <?php $bc = match($sg['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' }; ?>
                                <?php $prioLabel = match($sg['priorita']) { 'urgente'=>L('segn_prio_urgente'),'alta'=>L('segn_prio_alta'),'media'=>L('segn_prio_media'),default=>L('segn_prio_bassa') }; ?>
                                <span class="badge <?= $bc ?>"><?= $prioLabel ?></span>
                            </td>
                            <td>
                                <?php $sc = match($sg['stato']) { 'aperta'=>'badge-danger','in_lavorazione'=>'badge-warning','risolta'=>'badge-success',default=>'badge-secondary' }; ?>
                                <?php $statoLabel = match($sg['stato']) { 'aperta'=>L('segn_stato_aperta'),'in_lavorazione'=>L('segn_stato_in_lavorazione'),'risolta'=>L('segn_stato_risolta'),default=>L('segn_stato_chiusa') }; ?>
                                <span class="badge <?= $sc ?>"><?= $statoLabel ?></span>
                            </td>
                            <td><?= htmlspecialchars($sg['segnalato_da']) ?></td>
                            <td><?= date('d/m/Y', strtotime($sg['data_segnalazione'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- MOBILE: card list -->
            <div class="dash-card-list dash-table-mobile">
                <?php foreach ($segnalazioni as $sg):
                    $bc = match($sg['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' };
                    $sc = match($sg['stato']) { 'aperta'=>'badge-danger','in_lavorazione'=>'badge-warning','risolta'=>'badge-success',default=>'badge-secondary' };
                    $prioLabel = match($sg['priorita']) { 'urgente'=>L('segn_prio_urgente'),'alta'=>L('segn_prio_alta'),'media'=>L('segn_prio_media'),default=>L('segn_prio_bassa') };
                    $statoLabel = match($sg['stato']) { 'aperta'=>L('segn_stato_aperta'),'in_lavorazione'=>L('segn_stato_in_lavorazione'),'risolta'=>L('segn_stato_chiusa') }; ?>
                <div class="dcl-item">
                    <div class="dcl-main">
                        <span class="dcl-title"><?= htmlspecialchars($sg['titolo']) ?></span>
                        <div class="dcl-badges">
                            <span class="badge <?= $bc ?>"><?= $prioLabel ?></span>
                            <span class="badge <?= $sc ?>"><?= $statoLabel ?></span>
                        </div>
                    </div>
                    <div class="dcl-meta">
                        <span><?= htmlspecialchars($sg['segnalato_da']) ?> &bull; <?= htmlspecialchars($sg['laboratorio']) ?></span>
                        <span><?= date('d/m/Y', strtotime($sg['data_segnalazione'])) ?></span>
                    </div>
                    <a href="<?= BASE_PATH ?>/pages/segnalazioni/dettaglio.php?id=<?= $sg['id'] ?>" class="btn btn-primary btn-sm dcl-btn"><?= L('dettagli') ?></a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Vista Admin/Tecnico — stili specifici */
.dash-kpi { margin-bottom: 1.25rem; }
.dash-card-list { display:flex; flex-direction:column; gap:10px; }
.dcl-item {
    background:#f9f8f5; border:1px solid #e0ddd8; border-radius:10px;
    padding:12px 14px; display:flex; flex-direction:column; gap:7px;
}
.dcl-main { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.dcl-title { font-weight:700; font-size:.88rem; color:#1a1a1a; flex:1; min-width:0; }
.dcl-badges { display:flex; gap:5px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end; }
.dcl-meta { display:flex; justify-content:space-between; font-size:.78rem; color:#888; gap:8px; flex-wrap:wrap; }
.dcl-btn { align-self:flex-end; }
.dash-table-mobile  { display: none; }
.dash-table-desktop { display: block; }

@media (max-width: 767px) {
    .dash-kpi {
        grid-template-columns: repeat(2, 1fr);
        gap: 9px;
    }
    .dash-kpi .stat-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 12px 8px;
        gap: 6px;
        border-radius: 12px;
    }
    .dash-kpi .stat-icon { width: 36px; height: 36px; border-radius: 8px; }
    .dash-kpi .stat-icon svg { width: 17px; height: 17px; }
    .dash-kpi .stat-value { font-size: 20px; line-height: 1; }
    .dash-kpi .stat-label { font-size: 10px; line-height: 1.3; color: #666; }
    .dash-table-desktop { display: none; }
    .dash-table-mobile  { display: flex; }
}
@media (min-width: 768px) and (max-width: 1023px) {
    .dash-kpi { grid-template-columns: repeat(4, 1fr); gap: 12px; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
