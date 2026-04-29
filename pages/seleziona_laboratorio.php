<?php
/**
 * Selezione laboratorio per il docente.
 * Viene mostrata dopo il login quando il docente ha più laboratori assegnati.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';

// Solo i docenti usano questa pagina
requireLogin();
if (!isDocente()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$userId = (int)getCurrentUserId();
$labs   = getDocenteLabs($userId);

// Se non ha lab assegnati, torna al login con errore
if (count($labs) === 0) {
    logout();
}

// Gestione POST: docente ha scelto un lab
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idLab = (int)($_POST['id_laboratorio'] ?? 0);
    if ($idLab && canAccessLab($idLab)) {
        setSelectedLabId($idLab);
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
    }
    $errore = 'Selezione non valida. Scegli uno dei tuoi laboratori.';
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleziona Laboratorio - Registrony</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
    <style>
        .lab-selection-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f0;
            padding: 2rem 1rem;
        }
        .lab-selection-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.09);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 520px;
        }
        .lab-selection-card .logo {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .lab-selection-card .logo .icon { font-size: 2.5rem; }
        .lab-selection-card .logo h1 { font-size: 1.6rem; font-weight: 700; margin: .25rem 0 0; }
        .lab-selection-card .logo p  { color: #777; font-size: .9rem; margin: .2rem 0 0; }
        .lab-selection-card h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: .4rem;
            text-align: center;
        }
        .lab-selection-card .subtitle {
            text-align: center;
            color: #666;
            font-size: .9rem;
            margin-bottom: 1.5rem;
        }
        .lab-list { display: flex; flex-direction: column; gap: .75rem; }
        .lab-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
            padding: .9rem 1.1rem;
            background: #f9f9f9;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            text-align: left;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }
        .lab-btn:hover, .lab-btn:focus {
            border-color: #01696f;
            background: #f0f8f8;
            box-shadow: 0 2px 8px rgba(1,105,111,.12);
            outline: none;
        }
        .lab-btn-icon {
            width: 40px; height: 40px;
            background: #e8f4f4;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: #01696f;
        }
        .lab-btn-info { flex: 1; }
        .lab-btn-nome  { font-weight: 600; color: #1a1a1a; }
        .lab-btn-aula  { font-size: .82rem; color: #888; margin-top: 2px; }
        .lab-btn-badge {
            font-size: .72rem;
            background: #01696f;
            color: #fff;
            border-radius: 20px;
            padding: 2px 8px;
            flex-shrink: 0;
        }
        .footer-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .85rem;
            color: #999;
        }
        .footer-links a { color: #01696f; text-decoration: none; }
        .footer-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="lab-selection-page">
    <div class="lab-selection-card">
        <div class="logo">
            <div class="icon">&#128300;</div>
            <h1>Registrony</h1>
            <p>del Laboratoriony</p>
        </div>

        <?php if (!empty($errore)): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">&#10060; <?= htmlspecialchars($errore) ?></div>
        <?php endif; ?>

        <h2>Seleziona il laboratorio</h2>
        <p class="subtitle">
            Ciao <strong><?= htmlspecialchars($user['nome']) ?></strong>, scegli il registro da aprire:
        </p>

        <div class="lab-list">
            <?php foreach ($labs as $lab): ?>
            <form method="POST" action="">
                <input type="hidden" name="id_laboratorio" value="<?= (int)$lab['id'] ?>">
                <button type="submit" class="lab-btn">
                    <div class="lab-btn-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <div class="lab-btn-info">
                        <div class="lab-btn-nome"><?= htmlspecialchars($lab['nome']) ?></div>
                        <div class="lab-btn-aula">Aula: <?= htmlspecialchars($lab['aula']) ?></div>
                    </div>
                    <?php if ($lab['is_responsabile']): ?>
                        <span class="lab-btn-badge">Responsabile</span>
                    <?php endif; ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>

        <div class="footer-links">
            <a href="<?= BASE_PATH ?>/logout.php">&#8592; Esci e cambia account</a>
        </div>
    </div>
</div>
</body>
</html>
