(() => {
    'use strict';
    const root = document.querySelector('[data-latest-news]');
    if (!root) return;
    const grid = root.querySelector('.uni-news-grid');
    const dialog = root.querySelector('dialog');
    const plainText = value => {
        const template = document.createElement('template');
        template.innerHTML = String(value ?? '');
        return template.content.textContent.trim();
    };
    const timestamp = item => Date.parse(item.published_at || item.created_at || '') || 0;
    const dateLabel = item => timestamp(item)
        ? new Date(timestamp(item)).toLocaleDateString('vi-VN', {day:'2-digit', month:'2-digit', year:'numeric'})
        : 'Thông báo mới';
    dialog.querySelector('button').addEventListener('click', () => dialog.close());
    function read(item) {
        dialog.querySelector('h2').textContent = item.title || 'Thông báo';
        dialog.querySelector('.uni-news-meta').textContent = dateLabel(item) + (item.owner_name ? ' · ' + item.owner_name : '');
        dialog.querySelector('.uni-news-body').textContent = plainText(item.content);
        dialog.showModal();
    }
    async function load() {
        grid.setAttribute('aria-busy', 'true');
        grid.replaceChildren();
        const message = document.createElement('p');
        message.className = 'uni-news-message';
        message.textContent = 'Đang tải tin tức mới…';
        grid.append(message);
        try {
            const headers = window.AccountHeaders ? window.AccountHeaders()
                : {Accept:'application/json', Authorization:'Bearer ' + localStorage.getItem('access_token')};
            const response = await fetch(root.dataset.api, {headers, signal:AbortSignal.timeout(15000)});
            if (!response.ok) throw new Error('Không tải được tin tức');
            const result = await response.json();
            if (!Array.isArray(result.data)) throw new Error('Dữ liệu tin tức không hợp lệ');
            const items = [...result.data].sort((a,b) => timestamp(b) - timestamp(a)).slice(0,3);
            if (!items.length) {
                message.textContent = 'Chưa có tin tức được đăng. Các thông báo mới sẽ xuất hiện tại đây.';
                return;
            }
            grid.replaceChildren();
            items.forEach(item => {
                const card = document.createElement('article');
                card.className = 'uni-news-card';
                const meta = document.createElement('span');
                meta.className = 'uni-news-meta';
                meta.textContent = dateLabel(item) + (item.category ? ' · ' + item.category : '');
                const title = document.createElement('h3');
                title.textContent = item.title || 'Thông báo';
                const excerpt = document.createElement('p');
                excerpt.textContent = plainText(item.content).slice(0,240);
                const button = document.createElement('button');
                button.className = 'uni-button secondary';
                button.type = 'button';
                button.textContent = 'Đọc tin →';
                button.setAttribute('aria-label', 'Đọc tin: ' + (item.title || 'Thông báo'));
                button.addEventListener('click', () => read(item));
                card.append(meta, title, excerpt, button);
                grid.append(card);
            });
        } catch (_) {
            message.textContent = 'Chưa kết nối được bảng tin. Bạn vẫn có thể sử dụng các chức năng hỗ trợ bên dưới. ';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'uni-button secondary';
            retry.textContent = 'Tải lại tin tức';
            retry.addEventListener('click', load);
            message.append(retry);
        } finally {
            grid.setAttribute('aria-busy', 'false');
        }
    }
    load();
})();
