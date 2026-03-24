<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Registrony del Laboratoriony') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>&#128300; Registrony</h2>
            <small>del Laboratoriony</small>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Principale</div>
            <a href="<?= BASE_PATH ?>/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">
                <span class="nav-icon">&#127968;</span> Dashboard
            </a>
            <a href="<?= BASE_PATH ?>/pages/sessioni/index.php" class="<?= $currentPage === 'index' && strpos($_SERVER['PHP_SELF'], 'sessioni') !== false ? 'active' : '' ?>">
                <span class="nav-icon">&#9997;</span> Sessioni Lab
            </a>
            <a href="<?= BASE_PATH ?>/pages/sessioni/nuova.php" class="<?= $currentPage === 'nuova' ? 'active' : '' ?>">
                <span class="nav-icon">&#10133;</span> Nuova Sessione
            </a>
            <a href="<?= BASE_PATH ?>/pages/materiali/utilizzo.php" class="<?= $currentPage === 'utilizzo' ? 'active' : '' ?>">
                <span class="nav-icon">&#128230;</span> Materiali
            </a>
            <a href="<?= BASE_PATH ?>/pages/segnalazioni/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'segnalazioni') !== false && $currentPage === 'index' ? 'active' : '' ?>">
                <span class="nav-icon">&#9888;</span> Segnalazioni
            </a>

            <?php if (isAdmin()): ?>
            <div class="nav-section">Amministrazione</div>
            <a href="<?= BASE_PATH ?>/pages/admin/laboratori.php" class="<?= $currentPage === 'laboratori' ? 'active' : '' ?>">
                <span class="nav-icon">&#128187;</span> Laboratori
            </a>
            <a href="<?= BASE_PATH ?>/pages/admin/utenti.php" class="<?= $currentPage === 'utenti' ? 'active' : '' ?>">
                <span class="nav-icon">&#128101;</span> Utenti
            </a>
            <a href="<?= BASE_PATH ?>/pages/admin/classi.php" class="<?= $currentPage === 'classi' ? 'active' : '' ?>">
                <span class="nav-icon">&#127979;</span> Classi
            </a>
            <a href="<?= BASE_PATH ?>/pages/admin/materiali.php" class="<?= $currentPage === 'materiali' ? 'active' : '' ?>">
                <span class="nav-icon">&#128206;</span> Gestione Materiali
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['nome_completo']) ?></div>
                <div class="user-role"><?= htmlspecialchars($currentUser['ruolo']) ?></div>
            </div>
            <a href="<?= BASE_PATH ?>/logout.php" class="logout-btn" title="Esci">&#9211;</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-header">
            <div class="d-flex align-center gap-2">
                <button class="menu-toggle" aria-label="Menu">&#9776;</button>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
            </div>
            <div class="header-actions">
                <span class="text-muted"><?= date('d/m/Y') ?></span>
            </div>
        </header>
        <div class="page-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" data-auto-dismiss>
                    &#10004; <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" data-auto-dismiss>
                    &#10060; <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
