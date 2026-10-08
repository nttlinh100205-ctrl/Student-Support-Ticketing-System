(() => {
    'use strict';
    const sidebar = document.getElementById('system-sidebar');
    if (!sidebar) return;
    const toggle = document.querySelector('.system-menu-toggle');
    const backdrop = document.querySelector('.system-menu-backdrop');
    const close = sidebar.querySelector('.system-menu-close');
    const mobile = matchMedia('(max-width: 900px)');
    function setOpen(open) {
        document.body.classList.toggle('system-menu-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        if (open) { sidebar.setAttribute('role', 'dialog'); sidebar.setAttribute('aria-modal', 'true'); close.focus(); }
        else { sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal'); }
    }
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    const dismiss = () => { setOpen(false); toggle.focus(); };
    close.addEventListener('click', dismiss);
    backdrop.addEventListener('click', dismiss);
    mobile.addEventListener('change', () => setOpen(false));
    document.addEventListener('keydown', event => {
        if (!document.body.classList.contains('system-menu-open')) return;
        if (event.key === 'Escape') { dismiss(); return; }
        if (event.key !== 'Tab') return;
        const targets = [...sidebar.querySelectorAll('a,button,summary')].filter(el => el.getClientRects().length && !el.closest('[hidden]'));
        const first = targets[0], last = targets[targets.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    function highlight() {
        let selected = null, score = -1;
        sidebar.querySelectorAll('nav a').forEach(link => {
            link.removeAttribute('aria-current');
            if (link.closest('[hidden]')) return;
            const url = new URL(link.href);
            if (url.origin !== location.origin) return;
            const path = url.pathname.replace(/\/$/, '') || '/';
            const current = location.pathname.replace(/\/$/, '') || '/';
            if (current !== path && !(path !== '/' && current.startsWith(path + '/'))) return;
            for (const [key, value] of url.searchParams) {
                if (new URLSearchParams(location.search).get(key) !== value) return;
            }
            const rank = path.length + [...url.searchParams].length * 1000;
            if (rank > score) { selected = link; score = rank; }
        });
        if (selected) {
            selected.setAttribute('aria-current', 'page');
            const group = selected.closest('details');
            if (group) group.open = true;
        }
    }
    function identify(user) {
        const role = String(user.role || '').toLowerCase();
        sidebar.dataset.role = role;
        sidebar.querySelectorAll('[data-roles]').forEach(el => {
            el.hidden = !!el.dataset.roles && !el.dataset.roles.split(' ').includes(role);
        });
        const name = user.full_name || user.name || 'Tài khoản của bạn';
        sidebar.querySelector('#user-name').textContent = name;
        sidebar.querySelector('#avatar').textContent = name.slice(0, 1).toUpperCase();
        sidebar.querySelector('#user-role').textContent = ({admin:'Quản trị viên',student:'Sinh viên',staff:'Cán bộ',department_head:'Trưởng phòng'})[role] || 'Tài khoản';
        highlight();
    }
    document.addEventListener('suite:identity', event => identify(event.detail));
    highlight();
    if (sidebar.dataset.service === 'accounts' && !document.getElementById('services')) {
        const token = localStorage.getItem('access_token');
        if (token) fetch('/api/v1/auth/me', {headers:{Accept:'application/json',Authorization:'Bearer '+token}})
            .then(response => { if (!response.ok) throw new Error(); return response.json(); })
            .then(result => identify(result.user)).catch(() => {});
        document.getElementById('logout').addEventListener('click', async () => {
            try {
                const response = await fetch('/api/v1/auth/logout', {method:'POST',headers:{Accept:'application/json',Authorization:'Bearer '+token}});
                if (!response.ok && response.status !== 401) throw new Error();
                localStorage.removeItem('access_token'); localStorage.removeItem('current_user'); location.replace('/login');
            } catch (_) { const status=sidebar.querySelector('#system-menu-status'); status.hidden=false; status.textContent='Chưa thể đăng xuất. Vui lòng thử lại.'; }
        });
    }
})();
