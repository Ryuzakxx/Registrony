/**
 * REGISTRONY DEL LABORATORIONY
 * Unica fonte di verità per tutta la logica JS.
 * NON aggiungere altri listener su sidebar/overlay/menuToggle altrove.
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ================================================================
       SIDEBAR MOBILE/TABLET
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
    if (moreBtn) moreBtn.addEventListener('click', function (e) {
        e.preventDefault();
        toggleSidebar();
    });
    if (overlay) overlay.addEventListener('click',  closeSidebar);

    /* Chiudi sidebar con Escape o swipe su link nav */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeSidebar(); closeDropdown(); }
    });

    /* Chiudi automaticamente quando si naviga (click su un link sidebar) */
    if (sidebar) {
        sidebar.querySelectorAll('.sidebar-nav a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 1024) closeSidebar();
            });
        });
    }

    /* Chiudi su resize a desktop */
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) closeSidebar();
    });

    /* Swipe-to-close sidebar (touch) */
    var swipeStartX = 0;
    var swipeStartY = 0;
    if (sidebar) {
        sidebar.addEventListener('touchstart', function (e) {
            swipeStartX = e.touches[0].clientX;
            swipeStartY = e.touches[0].clientY;
        }, { passive: true });
        sidebar.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - swipeStartX;
            var dy = Math.abs(e.changedTouches[0].clientY - swipeStartY);
            /* Swipe sinistra > 60px, e orizzontale più che verticale */
            if (dx < -60 && dy < 80) closeSidebar();
        }, { passive: true });
    }

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
        if (dropOpen && userDropdown &&
            !userDropdown.contains(e.target) &&
            e.target !== userTrigger) {
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
       MODAL — apertura/chiusura
       ================================================================ */
    document.querySelectorAll('[data-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            var modal = document.getElementById(this.getAttribute('data-modal'));
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    function closeModal(idOrEl) {
        var mo = typeof idOrEl === 'string'
            ? document.getElementById(idOrEl)
            : idOrEl;
        if (!mo) return;
        mo.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-close, .modal-cancel').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mo = this.closest('.modal-overlay');
            if (mo) closeModal(mo);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (mo) {
        mo.addEventListener('click', function (e) {
            if (e.target === this) closeModal(this);
        });
    });

    /* ================================================================
       MODAL: bottom-sheet swipe-to-close su mobile/tablet
       ================================================================ */
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        var modal = overlay.querySelector('.modal');
        if (!modal) return;
        var startY = 0;
        modal.addEventListener('touchstart', function (e) {
            startY = e.touches[0].clientY;
        }, { passive: true });
        modal.addEventListener('touchend', function (e) {
            var dy = e.changedTouches[0].clientY - startY;
            if (dy > 80 && modal.scrollTop === 0) closeModal(overlay);
        }, { passive: true });
    });

    /* ================================================================
       BOTTOM NAV: evidenzia la voce attiva correttamente
       ================================================================ */
    var currentPath = window.location.pathname;
    document.querySelectorAll('.mobile-bottom-nav a').forEach(function (link) {
        if (link.id === 'mobileMoreBtn') return; /* skip il toggle menu */
        var href = link.getAttribute('href');
        if (!href || href === '#') return;
        /* Confronta il path normalizzato */
        try {
            var linkPath = new URL(href, window.location.origin).pathname;
            if (currentPath === linkPath ||
                (linkPath !== '/' && currentPath.startsWith(linkPath.replace(/\/[^/]+\.php$/, '')))) {
                link.classList.add('active');
            }
        } catch (err) { /* ignora URL non parsabili */ }
    });

});

/* ================================================================
   FUNZIONI GLOBALI per modali usate inline (onclick=...)
   ================================================================ */
function openModal(id)  {
    var m = document.getElementById(id);
    if (m) { m.classList.add('active'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    var m = document.getElementById(id);
    if (m) { m.classList.remove('active'); document.body.style.overflow = ''; }
}
