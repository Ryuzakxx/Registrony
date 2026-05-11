        </div><!-- .page-content -->
    </main>
</div><!-- .app-layout -->

<?php
$_bp = BASE_PATH;
$_cp = basename($_SERVER['PHP_SELF'], '.php');
$_pf = $_SERVER['PHP_SELF'];
function _bnActive(string $check): string {
    global $_cp, $_pf;
    return $check === 'dashboard'
        ? ($_cp === 'index' && strpos($_pf, 'pages') === false ? ' active' : '')
        : (strpos($_pf, $check) !== false ? ' active' : '');
}
?>
<nav class="mobile-bottom-nav" aria-label="Navigazione rapida" role="navigation">
    <a href="<?= $_bp ?>/index.php" class="<?= _bnActive('dashboard') ?>" aria-label="Dashboard">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
        </svg>
        <span>Dashboard</span>
    </a>
    <a href="<?= $_bp ?>/pages/sessioni/index.php" class="<?= _bnActive('sessioni') ?>" aria-label="Sessioni">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        <span>Sessioni</span>
    </a>
    <a href="<?= $_bp ?>/pages/sessioni/nuova.php" class="<?= _bnActive('nuova') ?> bn-cta" aria-label="Nuova sessione">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
        </svg>
        <span>+ Nuova</span>
    </a>
    <a href="<?= $_bp ?>/pages/segnalazioni/index.php" class="<?= _bnActive('segnalazioni') ?>" aria-label="Segnalazioni">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span>Segnalaz.</span>
    </a>
    <a href="#" id="mobileMoreBtn" aria-label="Apri menu completo" aria-expanded="false" aria-controls="sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        <span>Menu</span>
    </a>
</nav>

<!-- app.js gestisce tutta la logica JS: sidebar, overlay, bottom nav, dropdown, modal -->
<script src="<?= BASE_PATH ?>/assets/js/app.js"></script>
</body>
</html>
