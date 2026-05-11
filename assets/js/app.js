/**
 * REGISTRONY DEL LABORATORIONY
 * Unica fonte di verità per tutta la logica JS.
 * NON aggiungere altri listener su sidebar/overlay/menuToggle altrove.
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ================================================================
       SIDEBAR MOBILE
       ================================================================ */
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebarOverlay');
    var toggle   = document.getElementById('menuToggle');
    var moreBtn  = document.getElementById('mobileMoreBtn');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        if (toggle)  toggle.setAttribute('aria-expanded', 'true');
        if (moreBtn) moreBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        if (toggle)  toggle.setAttribute('aria-expanded', 'false');
        if (moreBtn) moreBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    function toggleSidebar() {
        sidebar && sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    }

    if (toggle)  toggle.addEventListener('click',   toggleSidebar);
    if (moreBtn) moreBtn.addEventListener('click', function (e) { e.preventDefault(); toggleSidebar(); });
    if (overlay) overlay.addEventListener('click',  closeSidebar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
            closeDropdown();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) closeSidebar();
    });

    /* ================================================================
       DROPDOWN PROFILO UTENTE
       ================================================================ */
    var userTrigger  = document.getElementById('userDropdownTrigger');
    var userDropdown = document.getElementById('userDropdown');
    var userChevron  = document.getElementById('userChevron');
    var dropOpen     = false;

    function openDropdown() {
        if (!userDropdown) return;
        dropOpen = true;
        userDropdown.classList.add('open');
        if (userTrigger) userTrigger.setAttribute('aria-expanded', 'true');
        if (userChevron) userChevron.style.transform = 'rotate(180deg)';
    }

    function closeDropdown() {
        if (!userDropdown) return;
        dropOpen = false;
        userDropdown.classList.remove('open');
        if (userTrigger) userTrigger.setAttribute('aria-expanded', 'false');
        if (userChevron) userChevron.style.transform = 'rotate(0deg)';
    }

    if (userTrigger) {
        userTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropOpen ? closeDropdown() : openDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (dropOpen && userDropdown && !userDropdown.contains(e.target) && e.target !== userTrigger) {
            closeDropdown();
        }
    });

    /* ================================================================
       AUTO-DISMISS ALERTS
       ================================================================ */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity   = '0';
            setTimeout(function () { alert.remove(); }, 320);
        }, 5000);
    });

    /* ================================================================
       CONFIRM DELETE
       ================================================================ */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    /* ================================================================
       MODAL
       ================================================================ */
    document.querySelectorAll('[data-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            var modal = document.getElementById(this.getAttribute('data-modal'));
            if (modal) modal.classList.add('active');
        });
    });

    document.querySelectorAll('.modal-close, .modal-cancel').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mo = this.closest('.modal-overlay');
            if (mo) mo.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (mo) {
        mo.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

});

/* Funzioni globali per modali usate inline */
function openModal(id)  { var m = document.getElementById(id); if (m) m.classList.add('active'); }
function closeModal(id) { var m = document.getElementById(id); if (m) m.classList.remove('active'); }
