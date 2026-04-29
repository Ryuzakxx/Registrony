<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$conn  = getConnection();
$today = date('Y-m-d');

// Stat: laboratori attivi
$res     = mysqli_query($conn, "SELECT COUNT(*) FROM laboratori WHERE attivo = 1");
$totLabs = mysqli_fetch_row($res)[0];

// Stat: sessioni oggi
$res            = mysqli_query($conn, "SELECT COUNT(*) FROM sessioni_laboratorio WHERE data = '$today'");
$totSessioniOggi= mysqli_fetch_row($res)[0];

// Stat: segnalazioni aperte
$res          = mysqli_query($conn, "SELECT COUNT(*) FROM segnalazioni WHERE stato IN ('aperta','in_lavorazione')");
$totSegnAperte= mysqli_fetch_row($res)[0];

// Stat: materiali in esaurimento
$res             = mysqli_query($conn, "SELECT COUNT(*) FROM materiali WHERE attivo = 1 AND quantita_disponibile IS NOT NULL AND soglia_minima IS NOT NULL AND quantita_disponibile <= soglia_minima");
$totMatEsaurimento= mysqli_fetch_row($res)[0];

// Sessioni di oggi
$result      = mysqli_query($conn, "
    SELECT s.id, s.data, s.ora_ingresso, s.ora_uscita, s.attivita_svolta,
           l.nome AS laboratorio, l.aula, c.nome AS classe,
           GROUP_CONCAT(CONCAT(u.cognome, ' ', u.nome, ' (', f.tipo_presenza, ')') ORDER BY f.tipo_presenza SEPARATOR ', ') AS docenti
    FROM sessioni_laboratorio s
    JOIN laboratori l ON s.id_laboratorio = l.id
    JOIN classi c ON s.id_classe = c.id
    LEFT JOIN firme_sessioni f ON s.id = f.id_sessione
    LEFT JOIN utenti u ON f.id_docente = u.id
    WHERE s.data = '$today'
    GROUP BY s.id
    ORDER BY s.ora_ingresso DESC
");
$sessioniOggi = [];
while ($row = mysqli_fetch_assoc($result)) $sessioniOggi[] = $row;

// Segnalazioni aperte
$result = mysqli_query($conn, "
    SELECT sg.id, sg.titolo, sg.priorita, sg.stato, sg.data_segnalazione,
           l.nome AS laboratorio, CONCAT(u.cognome, ' ', u.nome) AS segnalato_da
    FROM segnalazioni sg
    JOIN laboratori l ON sg.id_laboratorio = l.id
    JOIN utenti u ON sg.id_utente = u.id
    WHERE sg.stato IN ('aperta','in_lavorazione')
    ORDER BY FIELD(sg.priorita, 'urgente','alta','media','bassa'), sg.data_segnalazione DESC
    LIMIT 5
");
$segnalazioni = [];
while ($row = mysqli_fetch_assoc($result)) $segnalazioni[] = $row;
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totLabs ?></div>
            <div class="stat-label">Laboratori attivi</div>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <h4>Nessuna sessione oggi</h4>
                <p>Non ci sono sessioni registrate per oggi.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Laboratorio</th><th>Aula</th><th>Classe</th><th>Ingresso</th><th>Uscita</th><th>Docenti</th><th>Attivita</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessioniOggi as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['laboratorio']) ?></strong></td>
                            <td><?= htmlspecialchars($s['aula']) ?></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($s['classe']) ?></span></td>
                            <td><?= $s['ora_ingresso'] ?></td>
                            <td><?= $s['ora_uscita'] ? $s['ora_uscita'] : '<span class="badge badge-success">In corso</span>' ?></td>
                            <td><?= htmlspecialchars($s['docenti'] ?? 'Nessuna firma') ?></td>
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
                    <thead>
                        <tr><th>Titolo</th><th>Laboratorio</th><th>Priorita</th><th>Stato</th><th>Segnalato da</th><th>Data</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segnalazioni as $sg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sg['titolo']) ?></strong></td>
                            <td><?= htmlspecialchars($sg['laboratorio']) ?></td>
                            <td>
                                <?php $bc = match($sg['priorita']) { 'urgente'=>'badge-danger','alta'=>'badge-warning','media'=>'badge-info',default=>'badge-secondary' }; ?>
                                <span class="badge <?= $bc ?>"><?= $sg['priorita'] ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $sg['stato']==='aperta' ? 'badge-danger' : 'badge-warning' ?>"><?= $sg['stato'] ?></span>
                            </td>
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
