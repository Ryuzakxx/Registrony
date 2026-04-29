<?php
/* ================================================================
   Logica POST prima di qualsiasi output HTML
   ================================================================ */
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lang/it.php';

$conn = getConnection();
$id   = intval($_GET['id'] ?? 0);
$L    = lang();

if (!$id) {
    header('Location: ' . BASE_PATH . '/pages/sessioni/index.php?error=' . urlencode('Sessione non trovata'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---- Chiudi sessione ---- */
    if ($action === 'chiudi_sessione') {
        $oraUscita = mysqli_real_escape_string($conn, $_POST['ora_uscita'] ?? date('H:i'));
        mysqli_query($conn, "UPDATE sessioni_laboratorio SET ora_uscita = '$oraUscita' WHERE id = $id AND ora_uscita IS NULL");
        header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=' . urlencode('Sessione chiusa!')); exit;
    }

    /* ---- Aggiorna attivita/note ---- */
    if ($action === 'aggiorna') {
        $attivita    = mysqli_real_escape_string($conn, trim($_POST['attivita_svolta'] ?? ''));
        $note        = mysqli_real_escape_string($conn, trim($_POST['note'] ?? ''));
        $attivitaSQL = $attivita ? "'$attivita'" : 'NULL';
        $noteSQL     = $note     ? "'$note'"     : 'NULL';
        mysqli_query($conn, "UPDATE sessioni_laboratorio SET attivita_svolta = $attivitaSQL, note = $noteSQL WHERE id = $id");
        header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=' . urlencode('Sessione aggiornata!')); exit;
    }

    /* ---- Aggiungi materiale + aggiorna quantita ---- */
    if ($action === 'aggiungi_materiale') {
        $idMateriale = intval($_POST['id_materiale'] ?? 0);
        $quantita    = floatval($_POST['quantita_usata'] ?? 0);
        $esaurito    = isset($_POST['esaurito']) ? 1 : 0;
        $noteMat     = mysqli_real_escape_string($conn, trim($_POST['note_materiale'] ?? ''));
        $noteMatSQL  = $noteMat ? "'$noteMat'" : 'NULL';

        if ($idMateriale && $quantita > 0) {
            mysqli_begin_transaction($conn);
            try {
                /* Inserisci riga utilizzo */
                $ok = mysqli_query($conn, "INSERT INTO utilizzo_materiali (id_sessione, id_materiale, quantita_usata, esaurito, note) VALUES ($id, $idMateriale, $quantita, $esaurito, $noteMatSQL)");
                if (!$ok) throw new Exception('Materiale gia registrato per questa sessione');

                /* Aggiorna quantita_disponibile sul materiale */
                if ($esaurito) {
                    /* Segnato come esaurito: porta a zero */
                    mysqli_query($conn, "UPDATE materiali SET quantita_disponibile = 0 WHERE id = $idMateriale AND quantita_disponibile IS NOT NULL");
                } else {
                    /* Sottrai la quantita usata, non scendere sotto 0 */
                    mysqli_query($conn, "UPDATE materiali
                        SET quantita_disponibile = GREATEST(0, quantita_disponibile - $quantita)
                        WHERE id = $idMateriale AND quantita_disponibile IS NOT NULL");
                }

                mysqli_commit($conn);
                header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=' . urlencode('Materiale registrato e quantita aggiornata!')); exit;
            } catch (Exception $ex) {
                mysqli_rollback($conn);
                header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&error=' . urlencode($ex->getMessage())); exit;
            }
        }
    }

    /* ---- Rimuovi materiale da sessione + ripristina quantita ---- */
    if ($action === 'rimuovi_materiale') {
        $idUtilizzo  = intval($_POST['id_utilizzo'] ?? 0);
        if ($idUtilizzo) {
            mysqli_begin_transaction($conn);
            try {
                /* Leggi i dati prima di cancellare */
                $r = mysqli_query($conn, "SELECT id_materiale, quantita_usata, esaurito FROM utilizzo_materiali WHERE id = $idUtilizzo AND id_sessione = $id");
                $um = mysqli_fetch_assoc($r);
                if ($um) {
                    mysqli_query($conn, "DELETE FROM utilizzo_materiali WHERE id = $idUtilizzo");
                    /* Ripristina la quantita (se era esaurito non sappiamo quanto c'era: aggiungiamo solo la quantita usata) */
                    mysqli_query($conn, "UPDATE materiali
                        SET quantita_disponibile = quantita_disponibile + {$um['quantita_usata']}
                        WHERE id = {$um['id_materiale']} AND quantita_disponibile IS NOT NULL");
                }
                mysqli_commit($conn);
                header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=' . urlencode('Utilizzo rimosso e quantita ripristinata.')); exit;
            } catch (Exception $ex) {
                mysqli_rollback($conn);
                header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&error=' . urlencode($ex->getMessage())); exit;
            }
        }
    }

    /* ---- Aggiungi firma ---- */
    if ($action === 'aggiungi_firma') {
        $idDocente = intval($_POST['id_docente'] ?? 0);
        if ($idDocente) {
            $ok = mysqli_query($conn, "INSERT INTO firme_sessioni (id_sessione, id_docente, tipo_presenza) VALUES ($id, $idDocente, 'compresenza')");
            if ($ok) header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&success=' . urlencode('Firma aggiunta!'));
            else     header('Location: ' . BASE_PATH . '/pages/sessioni/dettaglio.php?id=' . $id . '&error='   . urlencode('Errore aggiunta firma'));
            exit;
        }
    }
}

/* ================================================================
   READ — solo dopo i redirect
   ================================================================ */
$pageTitle = 'Dettaglio Sessione';
require_once __DIR__ . '/../../includes/header.php';

$result   = mysqli_query($conn, "SELECT s.*, l.nome AS laboratorio, l.aula, c.nome AS classe, c.anno_scolastico FROM sessioni_laboratorio s JOIN laboratori l ON s.id_laboratorio = l.id JOIN classi c ON s.id_classe = c.id WHERE s.id = $id");
$sessione = mysqli_fetch_assoc($result);
if (!$sessione) {
    header('Location: ' . BASE_PATH . '/pages/sessioni/index.php?error=' . urlencode('Sessione non trovata'));
    exit;
}

$resFirme = mysqli_query($conn, "SELECT f.*, u.nome, u.cognome FROM firme_sessioni f JOIN utenti u ON f.id_docente = u.id WHERE f.id_sessione = $id ORDER BY f.tipo_presenza");
$firme    = [];
while ($row = mysqli_fetch_assoc($resFirme)) $firme[] = $row;

/* Materiali gia usati in questa sessione */
$resMat = mysqli_query($conn, "
    SELECT um.id AS id_utilizzo, um.quantita_usata, um.esaurito, um.note,
           m.nome AS materiale, m.unita_misura, m.quantita_disponibile, m.id AS id_materiale
    FROM utilizzo_materiali um
    JOIN materiali m ON um.id_materiale = m.id
    WHERE um.id_sessione = $id
    ORDER BY m.nome
");
$materialiUsati = [];
while ($row = mysqli_fetch_assoc($resMat)) $materialiUsati[] = $row;

/* Materiali disponibili per questo laboratorio, NON ancora usati in questa sessione */
$idUsati    = array_column($materialiUsati, 'id_materiale');
$excludeSQL = $idUsati ? 'AND m.id NOT IN (' . implode(',', $idUsati) . ')' : '';
$resDisp    = mysqli_query($conn, "
    SELECT m.id, m.nome, m.unita_misura, m.quantita_disponibile
    FROM materiali m
    WHERE m.id_laboratorio = {$sessione['id_laboratorio']}
      AND m.attivo = 1
      $excludeSQL
    ORDER BY m.nome
");
$materialiDisponibili = [];
while ($row = mysqli_fetch_assoc($resDisp)) $materialiDisponibili[] = $row;

$resDocenti = mysqli_query($conn, "SELECT id, nome, cognome FROM utenti WHERE attivo = 1 ORDER BY cognome, nome");
$docenti    = [];
while ($row = mysqli_fetch_assoc($resDocenti)) $docenti[] = $row;

$inCorso = is_null($sessione['ora_uscita']);
?>

<!-- Sessione header -->
<div class="card">
    <div class="card-header">
        <h3>Sessione #<?= $id ?></h3>
        <?php if ($inCorso): ?>
            <span class="badge badge-success" style="font-size:13px;padding:6px 14px;">IN CORSO</span>
        <?php else: ?>
            <span class="badge badge-secondary" style="font-size:13px;padding:6px 14px;">Completata</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div><strong>Laboratorio:</strong><br><?= htmlspecialchars($sessione['laboratorio']) ?> (<?= htmlspecialchars($sessione['aula']) ?>)</div>
            <div><strong>Classe:</strong><br><span class="badge badge-primary"><?= htmlspecialchars($sessione['classe']) ?></span> <?= htmlspecialchars($sessione['anno_scolastico']) ?></div>
            <div><strong>Data:</strong><br><?= date('d/m/Y', strtotime($sessione['data'])) ?></div>
            <div><strong>Orario:</strong><br><?= substr($sessione['ora_ingresso'], 0, 5) ?> &ndash; <?= $sessione['ora_uscita'] ? substr($sessione['ora_uscita'], 0, 5) : '...' ?></div>
        </div>
    </div>
</div>

<!-- Firme -->
<div class="card">
    <div class="card-header"><h3>Firme Docenti</h3></div>
    <div class="card-body">
        <?php if (empty($firme)): ?>
            <p class="text-muted">Nessuna firma registrata.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Docente</th><th>Tipo</th><th>Ora Firma</th></tr></thead>
                    <tbody>
                        <?php foreach ($firme as $f): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['cognome'] . ' ' . $f['nome']) ?></strong></td>
                            <td><span class="badge <?= $f['tipo_presenza']==='titolare' ? 'badge-primary' : 'badge-info' ?>"><?= htmlspecialchars($f['tipo_presenza']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($f['ora_firma'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if (count($firme) < 2): ?>
            <form method="POST" class="mt-2 d-flex gap-2 align-center flex-wrap">
                <input type="hidden" name="action" value="aggiungi_firma">
                <select name="id_docente" class="form-control" style="max-width:300px" required>
                    <option value="">-- Aggiungi firma compresenza --</option>
                    <?php
                    $firmatiIds = array_column($firme, 'id_docente');
                    foreach ($docenti as $doc):
                        if (in_array($doc['id'], $firmatiIds)) continue;
                    ?>
                        <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Aggiungi Firma</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Chiudi sessione -->
<?php if ($inCorso): ?>
<div class="card">
    <div class="card-header"><h3>Chiudi Sessione</h3></div>
    <div class="card-body">
        <form method="POST" class="d-flex gap-2 align-center flex-wrap">
            <input type="hidden" name="action" value="chiudi_sessione">
            <div class="form-group" style="margin-bottom:0">
                <label>Ora Uscita</label>
                <input type="time" name="ora_uscita" class="form-control" value="<?= date('H:i') ?>" required>
            </div>
            <button type="submit" class="btn btn-danger btn-sm" style="margin-top:22px;">Chiudi Sessione</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Attivita e Note -->
<div class="card">
    <div class="card-header"><h3>Attivita e Note</h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="aggiorna">
            <div class="form-group">
                <label>Attivita Svolta</label>
                <textarea name="attivita_svolta" class="form-control" rows="3"><?= htmlspecialchars($sessione['attivita_svolta'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Note</label>
                <textarea name="note" class="form-control" rows="2"><?= htmlspecialchars($sessione['note'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Aggiorna</button>
        </form>
    </div>
</div>

<!-- Materiali Utilizzati -->
<div class="card">
    <div class="card-header">
        <h3>Materiali Utilizzati</h3>
        <small class="text-muted" style="font-weight:400;">Solo i materiali assegnati a <strong><?= htmlspecialchars($sessione['laboratorio']) ?></strong></small>
    </div>
    <div class="card-body">

        <?php if (!empty($materialiUsati)): ?>
        <div class="table-responsive mb-3">
            <table class="table">
                <thead>
                    <tr>
                        <th>Materiale</th>
                        <th>Q.ta Usata</th>
                        <th>Rimanente</th>
                        <th>Stato</th>
                        <th>Note</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materialiUsati as $mu): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($mu['materiale']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($mu['unita_misura'] ?? '') ?></small></td>
                        <td><?= number_format((float)$mu['quantita_usata'], 2, ',', '.') ?> <?= htmlspecialchars($mu['unita_misura'] ?? '') ?></td>
                        <td>
                            <?php if ($mu['quantita_disponibile'] !== null): ?>
                                <?php
                                $disp = (float)$mu['quantita_disponibile'];
                                $cls  = $disp <= 0 ? 'badge-danger' : ($disp < 5 ? 'badge-warning' : 'badge-success');
                                ?>
                                <span class="badge <?= $cls ?>"><?= number_format($disp, 2, ',', '.') ?> <?= htmlspecialchars($mu['unita_misura'] ?? '') ?></span>
                            <?php else: ?>
                                <span class="text-muted">n.d.</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $mu['esaurito'] ? 'badge-danger' : 'badge-success' ?>"><?= $mu['esaurito'] ? 'Esaurito' : 'OK' ?></span></td>
                        <td><?= htmlspecialchars($mu['note'] ?? '-') ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Rimuovere questo utilizzo e ripristinare la quantita?')">
                                <input type="hidden" name="action"     value="rimuovi_materiale">
                                <input type="hidden" name="id_utilizzo" value="<?= $mu['id_utilizzo'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Rimuovi</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-3">Nessun materiale registrato per questa sessione.</p>
        <?php endif; ?>

        <!-- Form aggiunta materiale -->
        <?php if (!empty($materialiDisponibili)): ?>
        <details <?= empty($materialiUsati) ? 'open' : '' ?>>
            <summary style="cursor:pointer; font-weight:600; font-size:13px; padding:6px 0; user-select:none;">+ Aggiungi utilizzo materiale</summary>
            <form method="POST" class="mt-2">
                <input type="hidden" name="action" value="aggiungi_materiale">
                <div class="form-row">
                    <div class="form-group">
                        <label>Materiale</label>
                        <select name="id_materiale" class="form-control" required id="selMateriale">
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($materialiDisponibili as $md): ?>
                                <option value="<?= $md['id'] ?>"
                                        data-disp="<?= $md['quantita_disponibile'] ?? '' ?>"
                                        data-unita="<?= htmlspecialchars($md['unita_misura'] ?? '') ?>">
                                    <?= htmlspecialchars($md['nome']) ?>
                                    <?php if ($md['quantita_disponibile'] !== null): ?>
                                        (disp: <?= number_format((float)$md['quantita_disponibile'], 2, ',', '.') ?> <?= htmlspecialchars($md['unita_misura'] ?? '') ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="dispInfo" style="font-size:12px; color:var(--text-light); margin-top:4px; display:none;"></div>
                    </div>
                    <div class="form-group">
                        <label>Quantita Usata</label>
                        <input type="number" name="quantita_usata" id="inpQuantita" class="form-control" step="0.01" min="0.01" required placeholder="es. 2">
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <input type="text" name="note_materiale" class="form-control" placeholder="Opzionale">
                    </div>
                </div>
                <div class="d-flex gap-2 align-center">
                    <label style="font-weight:normal;font-size:13px;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="esaurito" value="1" id="chkEsaurito"> Materiale esaurito / finito
                    </label>
                    <button type="submit" class="btn btn-warning btn-sm">Registra utilizzo</button>
                </div>
            </form>
        </details>
        <?php elseif (empty($materialiDisponibili) && empty($materialiUsati)): ?>
            <p class="text-muted" style="font-size:13px;">Nessun materiale assegnato al laboratorio <strong><?= htmlspecialchars($sessione['laboratorio']) ?></strong>. Aggiungili dalla sezione <a href="<?= BASE_PATH ?>/pages/admin/materiali.php">Gestione Materiali</a>.</p>
        <?php else: ?>
            <p class="text-muted" style="font-size:12px;">Tutti i materiali assegnati a questo laboratorio sono stati registrati.</p>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex gap-2 mt-2">
    <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="btn btn-secondary">&larr; Torna alle sessioni</a>
    <a href="<?= BASE_PATH ?>/pages/segnalazioni/nuova.php?id_laboratorio=<?= $sessione['id_laboratorio'] ?>&id_sessione=<?= $id ?>" class="btn btn-warning">Segnala Problema</a>
</div>

<script>
/* Mostra disponibilita quando si seleziona un materiale */
(function () {
    const sel  = document.getElementById('selMateriale');
    const info = document.getElementById('dispInfo');
    const inp  = document.getElementById('inpQuantita');
    const chk  = document.getElementById('chkEsaurito');
    if (!sel) return;

    sel.addEventListener('change', function () {
        const opt  = this.options[this.selectedIndex];
        const disp = opt.dataset.disp;
        const un   = opt.dataset.unita || '';
        if (disp !== '' && disp !== undefined) {
            const d = parseFloat(disp);
            info.style.display = 'block';
            info.innerHTML = 'Disponibile: <strong>' + d.toFixed(2).replace('.',',') + (un ? ' ' + un : '') + '</strong>';
            info.style.color = d <= 0 ? 'var(--danger)' : (d < 5 ? 'var(--warning)' : 'var(--success)');
            if (inp && d > 0) inp.setAttribute('max', d);
        } else {
            info.style.display = 'none';
        }
    });

    /* Se esaurito spuntato, disabilita il campo quantita (verra azzerata) */
    if (chk && inp) {
        chk.addEventListener('change', function () {
            inp.disabled = this.checked;
            if (this.checked) inp.value = '0.01';
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
