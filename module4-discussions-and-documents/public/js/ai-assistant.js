(() => {
    if (document.getElementById('unisupport-assistant')) return;
    const source = document.currentScript.src;
    const endpoint = document.currentScript.dataset.endpoint;
    let history = [], busy = false;
    const host = document.createElement('div');
    host.id = 'unisupport-assistant';
    document.body.append(host);
    const root = host.attachShadow({mode: 'open'});
    const icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H5l-3 3V11.5A7.5 7.5 0 0 1 9.5 4H12"/><path d="m18 2 1.2 3.8L23 7l-3.8 1.2L18 12l-1.2-3.8L13 7l3.8-1.2L18 2Z"/><path d="M7 12h7m-7 3h4"/></svg>';
    root.innerHTML = `
        <link rel="stylesheet" href="${new URL('../css/ai-assistant.css', source).href}">
        <button class="launcher" type="button" aria-label="Mở hỗ trợ UniSupport" aria-expanded="false" aria-controls="assistant-panel">${icon}<span>Hỗ trợ</span><span class="launcher-dot"></span></button>
        <section id="assistant-panel" class="panel" role="dialog" aria-label="Hỗ trợ UniSupport" hidden>
            <header><span class="avatar">${icon}</span><div class="identity"><strong>Hỗ trợ UniSupport</strong><span>Người bạn đồng hành học tập</span></div><button class="close icon-button" type="button" aria-label="Đóng trợ lý"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></header>
            <nav class="support-channels" aria-label="Kênh hỗ trợ"><button type="button" class="channel-active" aria-current="true">Tư vấn AI</button></nav>
            <div class="toolbar"><span class="connection"><i></i>Tư vấn AI</span><button class="reset" type="button">Bắt đầu lại</button></div>
            <div class="conversation" role="log" aria-live="polite" aria-relevant="additions text" tabindex="0">
                <div class="welcome"><div class="welcome-icon">${icon}</div><span class="eyebrow">XIN CHÀO BẠN</span><h2>Mình có thể giúp gì?</h2><p>Hướng dẫn sử dụng cổng hỗ trợ,<br>chuẩn bị nội dung và theo dõi yêu cầu.</p></div>
                <div class="suggestions"><button type="button">📝 Cách tạo yêu cầu mới</button><button type="button">🧭 Chọn phòng ban phù hợp</button><button type="button">💬 Viết nội dung yêu cầu rõ ràng</button><button type="button">⭐ Khi nào có thể đánh giá?</button></div>
                <div class="messages"></div>
            </div>
            <form><label for="assistant-input" class="sr-only">Câu hỏi của bạn</label><div class="compose"><textarea id="assistant-input" rows="2" maxlength="2000" placeholder="Bạn cần hỗ trợ điều gì?" required></textarea><button class="send" type="submit" aria-label="Gửi câu hỏi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 7-7 7 7M12 5v15"/></svg></button></div><p class="status" role="status"></p></form>
        </section>`;
    const $ = selector => root.querySelector(selector);
    const panel = $('.panel'), launcher = $('.launcher'), input = $('textarea');
    if (endpoint) {
        const adminLink = document.createElement('a');adminLink.className = 'contact-admin';
        adminLink.href = new URL('/support-chat', endpoint).href;
        adminLink.textContent = window.AccountUser?.role === 'admin' ? 'Hộp thư hỗ trợ' : 'Liên hệ nhân viên';
        adminLink.setAttribute('aria-label', 'Liên hệ nhân viên hỗ trợ');
        $('.support-channels').append(adminLink);
    }
    function toggle(open) {panel.hidden = !open;launcher.setAttribute('aria-expanded', String(open));if(open)input.focus();else launcher.focus();}
    $('.channel-active').onclick = () => input.focus();
    launcher.onclick = () => toggle(panel.hidden);
    $('.close').onclick = () => toggle(false);
    root.addEventListener('keydown', event => {if(event.key === 'Escape' && !panel.hidden){event.preventDefault();toggle(false);}});
    input.addEventListener('input', () => {$('.status').textContent = '';});
    input.addEventListener('keydown', event => {if(event.key === 'Enter' && !event.shiftKey && !event.isComposing){event.preventDefault();$('form').requestSubmit();}});
    root.querySelectorAll('.suggestions button').forEach(button => button.onclick = () => {input.value = button.textContent.replace(/^\S+\s/, '');input.dispatchEvent(new Event('input'));input.focus();});
    function message(role, content) {const item=document.createElement('div');item.className='message '+role;const label=document.createElement('strong');label.textContent=role==='user'?'Bạn':'UniSupport AI';const body=document.createElement('p');body.textContent=content;item.append(label,body);$('.messages').append(item);$('.conversation').scrollTop=$('.conversation').scrollHeight;return item;}
    $('.reset').onclick = () => {if(busy)return;history=[];input.value = '';input.dispatchEvent(new Event('input'));$('.messages').replaceChildren();$('.welcome').hidden=false;$('.suggestions').hidden=false;input.focus();};
    $('form').onsubmit = async event => {
        event.preventDefault();const content=input.value.trim();if(!content||busy)return;
        let token='';try{token=window.AccountHeaders?.().Authorization||('Bearer '+(localStorage.getItem('access_token')||''));}catch(e){}
        if(!token.replace(/^Bearer\s*/i,'').trim()){$('.status').textContent='Vui lòng đăng nhập để sử dụng trợ lý AI.';return;}
        if(!endpoint){$('.status').textContent='Chưa cấu hình địa chỉ dịch vụ AI.';return;}
        busy=true;$('.send').disabled=true;$('.reset').disabled=true;input.disabled=true;$('.status').textContent='';
        $('.welcome').hidden=true;$('.suggestions').hidden=true;
        const userMessage=message('user',content),loading=message('assistant','Đang soạn câu trả lời…');loading.classList.add('loading');
        const messages=[...history,{role:'user',content}].slice(-10);
        try {
            const response=await fetch(endpoint,{method:'POST',credentials:'omit',headers:{'Content-Type':'application/json',Accept:'application/json',Authorization:token},body:JSON.stringify({messages}),signal:AbortSignal.timeout(40000)});
            const result=await response.json();
            if(!response.ok)throw new Error(response.status===401?'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.':result.message||'Chưa nhận được phản hồi. Vui lòng thử lại.');
            if(typeof result.data?.reply!=='string')throw new Error('Phản hồi không hợp lệ. Vui lòng thử lại.');
            loading.remove();message('assistant',result.data.reply);history=[...messages,{role:'assistant',content:result.data.reply.slice(0,4000)}].slice(-9);input.value='';input.dispatchEvent(new Event('input'));
        }catch(error){loading.remove();userMessage.remove();$('.status').textContent=error.name==='TimeoutError'?'Kết nối đang chậm. Bạn có thể thử gửi lại.':error.message==='Failed to fetch'?'Không kết nối được dịch vụ AI. Kiểm tra module 3 đang chạy rồi thử lại.':error.message;}
        finally{busy=false;$('.send').disabled=false;$('.reset').disabled=false;input.disabled=false;if(!panel.hidden)input.focus();}
    };
    const theme = () => host.setAttribute('data-theme', document.documentElement.dataset.theme || 'light');
    new MutationObserver(theme).observe(document.documentElement, {attributes:true,attributeFilter:['data-theme']});theme();
})();
