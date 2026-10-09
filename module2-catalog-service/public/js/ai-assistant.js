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
                <div class="ai-view"><div class="welcome"><div class="welcome-icon">${icon}</div><span class="eyebrow">XIN CHÀO BẠN</span><h2>Mình có thể giúp gì?</h2><p>Hướng dẫn sử dụng cổng hỗ trợ,<br>chuẩn bị nội dung và theo dõi yêu cầu.</p></div>
                <div class="suggestions"><button type="button">📝 Cách tạo yêu cầu mới</button><button type="button">🧭 Chọn phòng ban phù hợp</button><button type="button">💬 Viết nội dung yêu cầu rõ ràng</button><button type="button">⭐ Khi nào có thể đánh giá?</button></div>
                <div class="messages"></div></div>
                <div class="human-view" hidden><div class="human-inbox" hidden><label for="human-thread">Cuộc trò chuyện</label><select id="human-thread"></select><div class="inbox-paging"><button type="button" class="inbox-prev">← Trước</button><button type="button" class="inbox-next">Sau →</button></div></div><button type="button" class="older" hidden>Xem tin nhắn trước</button><p class="human-empty">Gửi tin nhắn để trao đổi với admin.</p><div class="human-messages"></div></div>
            </div>
            <form><label for="assistant-input" class="sr-only">Câu hỏi của bạn</label><div class="image-preview" hidden></div><div class="compose"><input type="file" class="chat-image" accept="image/jpeg,image/png,image/webp,image/gif" hidden><button type="button" class="attach-image" aria-label="Chọn ảnh từ máy" title="Chọn ảnh từ máy" hidden>📷</button><textarea id="assistant-input" rows="2" maxlength="2000" placeholder="Bạn cần hỗ trợ điều gì?"></textarea><button class="send" type="submit" aria-label="Gửi câu hỏi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 7-7 7 7M12 5v15"/></svg></button></div><p class="status" role="status"></p></form>
        </section>`;

    const $ = selector => root.querySelector(selector);
    const panel = $('.panel'), launcher = $('.launcher'), input = $('textarea'), aiTab = $('.channel-active');
    const humanTab = document.createElement('button');humanTab.type='button';humanTab.className='contact-admin';humanTab.textContent='Liên hệ nhân viên';$('.support-channels').append(humanTab);
    const humanEndpoint=endpoint ? new URL('/api/support-chat',endpoint).href : '';
    let mode='ai',threadId=null,lastId=0,olderBefore=null,inboxPage=1,isAdmin=false,loadingHuman=false,version=0;
    const seen=new Set(),drafts={ai:'',human:''};let pendingImage=null,previewUrl=null;const imageUrls=new Set();
    function token(){try{return window.AccountHeaders?.().Authorization||('Bearer '+(localStorage.getItem('access_token')||''));}catch(e){return '';}}
    async function api(url,body){
        const authorization=token();if(!authorization.replace(/^Bearer\s*/i,'').trim())throw new Error('Vui lòng đăng nhập để trò chuyện.');
        const response=await fetch(url,{method:body?'POST':'GET',credentials:'omit',headers:{Accept:'application/json',Authorization:authorization,...(body&&!(body instanceof FormData)?{'Content-Type':'application/json'}:{})},...(body?{body:body instanceof FormData?body:JSON.stringify(body)}:{}),signal:AbortSignal.timeout(body instanceof FormData?90000:40000)});
        const result=await response.json();if(!response.ok)throw new Error(response.status===401?'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.':result.message||'Chưa kết nối được. Vui lòng thử lại.');return result.data;
    }
    function error(e){$('.status').textContent=e.name==='TimeoutError'?'Kết nối đang chậm. Hãy thử lại.':e.message==='Failed to fetch'?'Không kết nối được dịch vụ hỗ trợ.':e.message;}
    function scrollDown(){$('.conversation').scrollTop=$('.conversation').scrollHeight;}
    function bubble(role,content,label){const item=document.createElement('div');item.className='message '+role;const title=document.createElement('strong');title.textContent=label;const body=document.createElement('p');body.textContent=content;item.append(title,body);return item;}
    function message(role,content){const item=bubble(role,content,role==='user'?'Bạn':'UniSupport AI');$('.messages').append(item);scrollDown();return item;}
    function clearHuman(){version++;threadId=null;lastId=0;olderBefore=null;seen.clear();for(const url of imageUrls)URL.revokeObjectURL(url);imageUrls.clear();$('.human-messages').replaceChildren();$('.older').hidden=true;}
    async function loadHuman(older=false){
        if(loadingHuman||!humanEndpoint)return;loadingHuman=true;const current=version;
        const query=new URLSearchParams({inbox_page:String(inboxPage)});if(threadId)query.set('thread',threadId);
        if(older&&olderBefore)query.set('before',olderBefore);else if(lastId)query.set('after',lastId);
        try{
            const data=await api(humanEndpoint+'?'+query);if(current!==version)return;
            isAdmin=data.is_admin;threadId=data.thread?.id||null;
            $('.human-inbox').hidden=!isAdmin;
            const select=$('#human-thread');select.replaceChildren();for(const thread of data.threads){const option=new Option(thread.user_name+(thread.needs_reply?' · Chờ trả lời':''),thread.id);select.add(option);}if(threadId)select.value=String(threadId);
            $('.inbox-prev').disabled=inboxPage<=1;$('.inbox-next').disabled=inboxPage>=(data.inbox_last_page||1);
            const box=$('.conversation'),previousHeight=box.scrollHeight,previousTop=box.scrollTop,nearBottom=previousHeight-previousTop-box.clientHeight<70;
            const fragment=document.createDocumentFragment();let added=0;
            for(const item of data.messages){if(seen.has(item.id))continue;seen.add(item.id);added++;lastId=Math.max(lastId,item.id);const own=Boolean(item.from_admin)===isAdmin;const el=bubble(own?'user':'assistant',item.content,item.from_admin?'Admin · '+item.sender_name:(own?'Bạn':item.sender_name));el.dataset.messageId=item.id;
                if(item.image_url){const image=document.createElement('img');image.alt=item.image_name||'Ảnh đính kèm';image.className='chat-uploaded-image';el.append(image);const imageVersion=version;
                    fetch(item.image_url,{headers:{Authorization:token()},credentials:'omit',signal:AbortSignal.timeout(30000)}).then(response=>{if(!response.ok)throw new Error();return response.blob();}).then(blob=>{if(imageVersion!==version&&!el.isConnected)return;const url=URL.createObjectURL(blob);imageUrls.add(url);image.src=url;}).catch(()=>{image.replaceWith(document.createTextNode('Không tải được ảnh. Hãy mở lại cuộc trò chuyện.'));});
                }fragment.append(el);}
            if(older){$('.human-messages').prepend(fragment);box.scrollTop=previousTop+box.scrollHeight-previousHeight;}else{$('.human-messages').append(fragment);if(added&&nearBottom)scrollDown();}
            if(!query.has('after'))olderBefore=data.older_before;$('.older').hidden=!olderBefore;
            $('.human-empty').hidden=seen.size>0;$('.human-empty').textContent=isAdmin?'Chưa có cuộc trò chuyện.':'Gửi tin nhắn để trao đổi với admin.';
            if(mode==='human'){$('.send').disabled=busy||(isAdmin&&!threadId);$('.connection').textContent=isAdmin?'Hộp thư hỗ trợ':'Trao đổi với admin';$('.status').textContent='';}
        }catch(e){if(current===version&&mode==='human')error(e);}finally{loadingHuman=false;if(current!==version&&mode==='human')loadHuman();}
    }
    function changeMode(next){if(busy)return;drafts[mode]=input.value;mode=next;input.value=drafts[mode];$('.status').textContent='';
        aiTab.classList.toggle('channel-active',mode==='ai');humanTab.classList.toggle('channel-active',mode==='human');aiTab.setAttribute('aria-current',String(mode==='ai'));humanTab.setAttribute('aria-current',String(mode==='human'));
        $('.ai-view').hidden=mode!=='ai';$('.human-view').hidden=mode!=='human';$('.reset').textContent=mode==='ai'?'Bắt đầu lại':'Làm mới';$('.connection').textContent=mode==='ai'?'Tư vấn AI':'Đang tải hội thoại…';input.placeholder=mode==='ai'?'Bạn cần hỗ trợ điều gì?':'Nhập tin nhắn cho nhân viên…';input.maxLength=mode==='ai'?2000:4000;$('.attach-image').hidden=mode!=='human';$('.image-preview').hidden=mode!=='human'||!pendingImage;$('.send').disabled=false;
        if(mode==='human')loadHuman();input.focus();
    }
    function toggle(open){panel.hidden=!open;launcher.setAttribute('aria-expanded',String(open));if(open){input.focus();if(mode==='human')loadHuman();}else launcher.focus();}
    aiTab.onclick=()=>changeMode('ai');humanTab.onclick=()=>changeMode('human');launcher.onclick=()=>toggle(panel.hidden);$('.close').onclick=()=>toggle(false);
    root.addEventListener('keydown',e=>{if(e.key==='Escape'&&!panel.hidden){e.preventDefault();toggle(false);}});
    input.addEventListener('input',()=>$('.status').textContent='');input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();$('form').requestSubmit();}});
    root.querySelectorAll('.suggestions button').forEach(button=>button.onclick=()=>{input.value=button.textContent.replace(/^\S+\s/,'');input.focus();});
    $('.reset').onclick=()=>{if(busy)return;if(mode==='human'){loadHuman();return;}history=[];input.value='';drafts.ai='';$('.messages').replaceChildren();$('.welcome').hidden=false;$('.suggestions').hidden=false;$('.status').textContent='';input.focus();};
    $('#human-thread').onchange=()=>{const selected=Number($('#human-thread').value);clearHuman();threadId=selected;loadHuman();};
    $('.inbox-prev').onclick=()=>{if(loadingHuman||busy)return;inboxPage--;clearHuman();loadHuman();};$('.inbox-next').onclick=()=>{if(loadingHuman||busy)return;inboxPage++;clearHuman();loadHuman();};$('.older').onclick=()=>loadHuman(true);
    function clearImage(){pendingImage=null;$('.chat-image').value='';if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;$('.image-preview').replaceChildren();$('.image-preview').hidden=true;}
    $('.attach-image').onclick=()=>$('.chat-image').click();
    $('.chat-image').onchange=()=>{const file=$('.chat-image').files[0];if(!file)return;if(!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)||file.size>5*1024*1024){$('.status').textContent='Chọn ảnh JPG, PNG, WEBP hoặc GIF, tối đa 5 MB.';$('.chat-image').value='';return;}clearImage();pendingImage=file;previewUrl=URL.createObjectURL(file);const img=document.createElement('img');img.src=previewUrl;img.alt='Ảnh sắp gửi';const remove=document.createElement('button');remove.type='button';remove.textContent='Bỏ ảnh';remove.onclick=clearImage;$('.image-preview').append(img,remove);$('.image-preview').hidden=false;$('.status').textContent='';};
    $('form').onsubmit=async event=>{
        event.preventDefault();const content=input.value.trim();if((!content&&!(mode==='human'&&pendingImage))||busy)return;
        busy=true;$('.send').disabled=true;$('.reset').disabled=true;aiTab.disabled=true;humanTab.disabled=true;$('#human-thread').disabled=true;input.disabled=true;$('.attach-image').disabled=true;$('.image-preview').querySelector('button')?.setAttribute('disabled','');$('.status').textContent='';
        let userMessage,loading;
        try{
            if(mode==='human'){
                const payload=new FormData();payload.append('content',content);if(threadId)payload.append('thread_id',threadId);if(pendingImage)payload.append('image',pendingImage);const data=await api(humanEndpoint,payload);clearImage();version++;threadId=data.thread_id;input.value='';drafts.human='';await loadHuman();scrollDown();
            }else{
                $('.welcome').hidden=true;$('.suggestions').hidden=true;userMessage=message('user',content);loading=message('assistant','Đang soạn câu trả lời…');loading.classList.add('loading');
                const messages=[...history,{role:'user',content}].slice(-10),data=await api(endpoint,{messages});if(typeof data?.reply!=='string')throw new Error('Phản hồi không hợp lệ.');
                loading.remove();message('assistant',data.reply);history=[...messages,{role:'assistant',content:data.reply.slice(0,4000)}].slice(-9);input.value='';drafts.ai='';
            }
        }catch(e){loading?.remove();userMessage?.remove();error(e);}finally{busy=false;$('.send').disabled=mode==='human'&&isAdmin&&!threadId;$('.reset').disabled=false;aiTab.disabled=false;humanTab.disabled=false;$('#human-thread').disabled=false;input.disabled=false;$('.attach-image').disabled=false;$('.image-preview').querySelector('button')?.removeAttribute('disabled');if(!panel.hidden)input.focus();}
    };
    setInterval(()=>{if(!panel.hidden&&!document.hidden&&mode==='human'&&!busy)loadHuman();},12000);
    const theme=()=>host.setAttribute('data-theme',document.documentElement.dataset.theme||'light');new MutationObserver(theme).observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});theme();
})();
