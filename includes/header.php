<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
// Avatar initials
$nameParts = explode(' ', trim($currentUser['nome_completo']));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
// Language
$currentLang    = currentLang();
$otherLang      = $currentLang === 'it' ? 'en' : 'it';
$otherLangLabel = $currentLang === 'it' ? '🇬🇧 EN' : '🇮🇹 IT';
$currentUrl     = $_SERVER['REQUEST_URI'] ?? '/';

/* Lab attivo (docenti) */
$activeLab = null;
if (isDocente() && getSelectedLabId()) {
    $conn2 = getConnection();
    $lid   = (int)getSelectedLabId();
    $uid2  = (int)getCurrentUserId();
    $rl    = mysqli_query($conn2,
        "SELECT nome, aula, (id_responsabile = $uid2) AS is_resp FROM laboratori WHERE id = $lid LIMIT 1");
    $activeLab = mysqli_fetch_assoc($rl);
}

/* Può gestire materiali? (admin o responsabile di almeno un lab) */
$canGestMateriali = isAdmin();
if (!$canGestMateriali && isDocente()) {
    $conn3 = getConnection();
    $uid3  = (int)getCurrentUserId();
    $rr    = mysqli_query($conn3,
        "SELECT 1 FROM laboratori WHERE id_responsabile = $uid3 AND attivo = 1 LIMIT 1");
    $canGestMateriali = mysqli_num_rows($rr) > 0;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Registrony del Laboratoriony') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
    <style>
        .lang-toggle {
            display:inline-flex;align-items:center;gap:4px;padding:4px 10px;
            border:1px solid var(--border-color,#ddd);border-radius:6px;
            background:transparent;font-size:.78rem;font-weight:600;color:inherit;
            cursor:pointer;text-decoration:none;transition:background .15s,border-color .15s;white-space:nowrap;
        }
        .lang-toggle:hover{background:var(--hover-bg,rgba(0,0,0,.06));border-color:var(--primary-color,#4f46e5);}
        /* Lab chip nella top header */
        .lab-chip-header {
            display:inline-flex;align-items:center;gap:.4rem;
            background:#e8f4f4;color:#01696f;border:1px solid #b6d9d8;
            border-radius:20px;padding:3px 12px;font-size:.8rem;font-weight:600;
            text-decoration:none;cursor:default;
        }
        .lab-chip-header.resp { background:#01696f;color:#fff;border-color:#01696f; }
    </style>
</head>
<body>
<div class="app-layout">
    <div class="sidebar-overlay"></div>

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?= BASE_PATH ?>/assets/img/logo.svg" alt="Registrony" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Registrony</span>
                <span class="brand-sub">del Laboratoriony</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <!-- Lab selezionato (docenti) -->
            <?php if ($activeLab): ?>
            <div class="nav-section" style="margin-top:0">Registro attivo</div>
            <div style="padding:0 .5rem .5rem;">
                <div style="background:#e8f4f4;border:1px solid #b6d9d8;border-radius:8px;padding:.6rem .8rem;">
                    <div style="font-weight:700;font-size:.9rem;color:#01696f"><?= htmlspecialchars($activeLab['nome']) ?></div>
                    <div style="font-size:.75rem;color:#777;margin-top:2px">Aula: <?= htmlspecialchars($activeLab['aula']) ?>
                        <?php if ($activeLab['is_resp']): ?>&nbsp;&bull;&nbsp;<span style="color:#01696f;font-weight:600">★ Responsabile</span><?php endif; ?>
                    </div>
                </div>
                <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php"
                   style="display:block;margin-top:.4rem;font-size:.78rem;color:#01696f;text-align:center;text-decoration:none;">
                    &#8646; Cambia laboratorio
                </a>
            </div>
            <?php endif; ?>

            <div class="nav-section"><?= L('nav_sezione_principale') ?></div>

            <a href="<?= BASE_PATH ?>/index.php" class="<?= $currentPage === 'index' && strpos($_SERVER['PHP_SELF'], 'pages') === false ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                </span>
                <?= L('nav_dashboard') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="<?= $currentPage === 'index' && strpos($_SERVER['PHP_SELF'], 'sessioni') !== false ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </span>
                <?= L('nav_sessioni') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="<?= $currentPage === 'nuova' ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                </span>
                <?= L('nav_nuova_sessione') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/materiali/utilizzo.php" class="<?= $currentPage === 'utilizzo' ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <?= L('nav_materiali') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'segnalazioni') !== false && $currentPage === 'index' ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <?= L('nav_segnalazioni') ?>
            </a>

            <?php if ($canGestMateriali): ?>
            <a href="<?= BASE_PATH ?>/pages/materiali/gestione.php<?= ($activeLab && getSelectedLabId()) ? '?laboratorio=' . (int)getSelectedLabId() : '' ?>" class="<?= $currentPage === 'gestione' && strpos($_SERVER['PHP_SELF'], 'materiali') !== false ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                Gestione Materiali
            </a>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <div class="nav-section"><?= L('nav_sezione_admin') ?></div>

            <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php" class="<?= $currentPage === 'laboratori' ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </span>
                <?= L('nav_laboratori') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/admin/utenti.php" class="<?= $currentPage === 'utenti' ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <?= L('nav_utenti') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/admin/classi.php" class="<?= $currentPage === 'classi' ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </span>
                <?= L('nav_classi') ?>
            </a>

            <a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="<?= $currentPage === 'materiali' && strpos($_SERVER['PHP_SELF'],'admin') !== false ? 'active' : '' ?>">
                <span class="nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                <?= L('nav_gest_materiali') ?>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['nome_completo']) ?></div>
                <div class="user-role"><?= htmlspecialchars($currentUser['ruolo']) ?></div>
            </div>
            <a href="<?= BASE_PATH ?>/logout.php" class="logout-btn" title="Esci">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="d-flex align-center gap-2">
                <button class="menu-toggle" aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
                <!-- Lab chip nella top bar per i docenti -->
                <?php if ($activeLab): ?>
                    <span class="lab-chip-header <?= $activeLab['is_resp'] ? 'resp' : '' ?>">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        <?= htmlspecialchars($activeLab['nome']) ?>
                        <?php if ($activeLab['is_resp']): ?> &#9733;<?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <span class="text-muted"><?= date('d/m/Y') ?></span>
                <?php if (isDocente() && $activeLab): ?>
                    <a href="<?= BASE_PATH ?>/pages/seleziona_laboratorio.php" class="lang-toggle" title="Cambia laboratorio">
                        &#8646; Lab
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_PATH ?>/lang/set_lang.php?lang=<?= urlencode($otherLang) ?>&redirect=<?= urlencode($currentUrl) ?>"
                   class="lang-toggle"
                   title="<?= $currentLang === 'it' ? 'Switch to English' : 'Passa all\'italiano' ?>">
                    <?= htmlspecialchars($otherLangLabel) ?>
                </a>
            </div>
        </header>
        <div class="page-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" data-auto-dismiss>
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" data-auto-dismiss>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
