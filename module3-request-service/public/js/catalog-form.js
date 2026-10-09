(() => {
    const select = document.querySelector('#support_type_id'), host = document.querySelector('#catalog-form-fields');
    if (!select || !host) return;
    const status = document.querySelector('#catalog-form-status');
    const old = JSON.parse(document.querySelector('#catalog-form-old').textContent);
    const errors = JSON.parse(document.querySelector('#catalog-form-errors').textContent);
    let sequence = 0;
    async function load() {
        const current = ++sequence;
        host.replaceChildren();
        if (!select.value) {status.textContent = 'Chọn loại hỗ trợ để tải biểu mẫu.'; return;}
        select.setCustomValidity('Đang tải biểu mẫu. Vui lòng chờ.');
        status.textContent = 'Đang tải biểu mẫu…';
        try {
            const response = await fetch('/request-forms/' + encodeURIComponent(select.value), {headers: {Accept: 'application/json'}, signal: AbortSignal.timeout(25000)});
            if (!response.ok) throw new Error();
            const {data} = await response.json();
            if (current !== sequence) return;
            for (const field of data.fields) {
                const wrap = document.createElement('div'), label = document.createElement('label');
                const input = document.createElement(field.field_type === 'textarea' ? 'textarea' : field.field_type === 'select' ? 'select' : 'input');
                input.name = (field.field_type === 'file' ? 'form_files' : 'form_values') + '[' + field.field_key + ']';
                input.id = 'catalog-' + field.field_key; label.htmlFor = input.id;
                label.textContent = field.label + (field.is_required ? ' *' : '');
                input.required = !!field.is_required;
                if (input.tagName === 'INPUT') input.type = ['number','date','file'].includes(field.field_type) ? field.field_type : 'text';
                if (field.field_type === 'number') input.step = 'any';
                if (field.field_type === 'select') {
                    for (const value of ['', ...(field.options || [])]) {const option = document.createElement('option');option.value = value;option.textContent = value || 'Chọn một giá trị';input.append(option);}
                }
                if (field.field_type === 'file') {
                    input.accept = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.gif';
                    input.addEventListener('change', () => input.setCustomValidity(input.files[0]?.size > 10 * 1024 * 1024 ? 'Tệp tối đa 10 MB.' : ''));
                } else input.value = old[field.field_key] ?? '';
                wrap.className = 'uni-form-field'; wrap.append(label, input);
                const messages = errors['form_values.' + field.field_key] || errors['form_files.' + field.field_key];
                if (messages) {const p = document.createElement('p');p.className = 'uni-field-error';p.textContent = messages.join(' ');wrap.append(p);}
                host.append(wrap);
            }
            select.setCustomValidity('');
            status.textContent = data.fields.length ? 'Các mục có dấu * là bắt buộc. Tài liệu tối đa 10 MB/tệp.' : 'Loại hỗ trợ này không yêu cầu thông tin bổ sung.';
        } catch (error) {
            if (current !== sequence) return;
            select.setCustomValidity('Chưa tải được biểu mẫu. Vui lòng thử lại.');
            status.textContent = 'Chưa tải được biểu mẫu. ';
            const retry = document.createElement('button');retry.type = 'button';retry.className = 'uni-button secondary';retry.textContent = 'Thử lại';retry.onclick = load;status.append(retry);
        }
    }
    select.addEventListener('change', load);
    document.querySelector('#department_id').addEventListener('change', () => {++sequence;host.replaceChildren();select.setCustomValidity('');status.textContent = 'Chọn loại hỗ trợ để tải biểu mẫu.';});
    load();
})();
