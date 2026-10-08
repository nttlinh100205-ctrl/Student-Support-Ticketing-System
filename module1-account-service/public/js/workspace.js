(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const services = JSON.parse($('services').textContent);
    const url = (service, path = '') => services[service].replace(/\/$/, '') + path;
    const token = localStorage.getItem('access_token');
    const labels = {new:'Mới tạo',received:'Đã tiếp nhận',in_progress:'Đang xử lý',waiting_info:'Chờ bổ sung',resolved:'Đã giải quyết',closed:'Đã đóng',cancelled:'Đã hủy'};
    const priorities = {low:'Thấp',normal:'Bình thường',high:'Cao',urgent:'Khẩn cấp'};
    const action = (title, description, href, icon) => ({title, description, href, icon});
    const tickets = url('requests', '/requests');
    const common = [action('Tin tức & tài liệu','Thông báo và tài liệu dành cho bạn.',url('news'), '▤'),action('Hồ sơ cá nhân','Cập nhật thông tin và mật khẩu.','/profile','◉')];
    const roleViews = {
        ADMIN: {label:'Quản trị viên',heading:'Tổng quan hệ thống',description:'Quản lý tập trung, kết nối mọi hoạt động hỗ trợ.',hero:'Một không gian, toàn bộ hoạt động.',detail:'Quản lý tài khoản, tổ chức danh mục và giám sát yêu cầu của tất cả phòng ban.',primary:'Quản lý tài khoản',href:'/admin/users',scope:'Toàn hệ thống',actions:[action('Tài khoản & phân quyền','Vai trò, phòng ban và trạng thái tài khoản.','/admin/users','◉'),action('Toàn bộ yêu cầu','Theo dõi, phân công và điều phối xử lý.',tickets,'▣'),action('Phòng ban','Quản lý các đơn vị tiếp nhận hỗ trợ.',url('catalog','/admin/departments'),'▦'),action('Cán bộ theo phòng','Liên kết tài khoản với phòng ban.',url('catalog','/admin/department-staff'),'↗'),action('Loại hỗ trợ & SLA','Danh mục dịch vụ và thời hạn giải quyết.',url('catalog','/admin/support-types'),'◇'),action('Biểu mẫu hỗ trợ','Thiết lập thông tin sinh viên cần cung cấp.',url('catalog','/admin/support-type-fields'),'▤'),action('Câu hỏi thường gặp','Quản lý hướng dẫn và câu trả lời.',url('catalog','/admin/faqs'),'?'),action('Tin tức & tài liệu','Quản lý nội dung và thông báo.',url('news'),'▤'),action('Báo cáo toàn hệ thống','Hiệu suất hỗ trợ, SLA và đánh giá.',url('reports'),'▥')]},
        STUDENT: {label:'Sinh viên',heading:'Hôm nay bạn cần hỗ trợ gì?',description:'Gửi yêu cầu, theo dõi tiến độ và trao đổi với phòng ban.',hero:'Mỗi thắc mắc đều có nơi giải đáp.',detail:'Từ thủ tục học tập đến đời sống sinh viên, gửi yêu cầu đến đúng phòng ban và theo dõi ngay tại đây.',primary:'+ Tạo yêu cầu mới',href:tickets+'/create',scope:'Yêu cầu của bạn',actions:[action('Gửi yêu cầu hỗ trợ','Chọn phòng ban và mô tả vấn đề của bạn.',tickets+'/create','+'),action('Yêu cầu của tôi','Theo dõi phản hồi, bổ sung và đánh giá.',tickets,'▣'),action('Tra cứu hỗ trợ','Xem thủ tục, loại hỗ trợ và FAQ.',url('catalog','/catalog'),'?'),...common]},
        DEPARTMENT_HEAD: {label:'Trưởng phòng',heading:'Điều phối hỗ trợ của phòng',description:'Theo dõi tiến độ, phân công cán bộ và kiểm soát chất lượng.',hero:'Phân công rõ ràng. Hỗ trợ kịp thời.',detail:'Theo dõi yêu cầu thuộc phòng, phân công người phụ trách và phối hợp giải quyết các trường hợp cần hỗ trợ.',primary:'Điều phối yêu cầu →',href:tickets,scope:'Yêu cầu thuộc phòng',actions:[action('Yêu cầu của phòng','Tiếp nhận và phân công cán bộ phụ trách.',tickets,'▣'),action('Yêu cầu mới','Xem các yêu cầu đang chờ tiếp nhận.',tickets+'?status=new','+'),action('Báo cáo của phòng','Theo dõi hiệu suất xử lý và đánh giá.',url('reports'),'▥'),...common]},
        STAFF: {label:'Cán bộ',heading:'Công việc của bạn',description:'Tập trung xử lý yêu cầu được giao và hỗ trợ sinh viên.',hero:'Một phản hồi, thêm một vấn đề được giải quyết.',detail:'Xem yêu cầu được phân công, trao đổi với sinh viên và cập nhật tiến độ xử lý đến khi hoàn tất.',primary:'Xem việc được giao →',href:tickets,scope:'Yêu cầu được giao',actions:[action('Việc được giao','Xem chi tiết và cập nhật tiến độ xử lý.',tickets,'▣'),action('Đang xử lý','Tiếp tục các yêu cầu đang thực hiện.',tickets+'?status=in_progress','↗'),action('Chờ bổ sung thông tin','Theo dõi các trao đổi với sinh viên.',tickets+'?status=waiting_info','◇'),action('Báo cáo cá nhân','Theo dõi kết quả công việc của bạn.',url('reports'),'▥'),...common]}
    };
    async function api(href, options = {}) {
        let response;
        try {
            response = await fetch(href, {...options, headers:{Accept:'application/json',Authorization:'Bearer '+token,...options.headers},signal:AbortSignal.timeout(30000)});
        } catch (_) {
            throw new Error('Dịch vụ yêu cầu chưa phản hồi. Kiểm tra module 3 ở cổng 8003 rồi bấm Làm mới.');
        }
        if (response.status === 401) { localStorage.removeItem('access_token'); location.replace('/login'); throw new Error('Phiên đăng nhập hết hạn.'); }
        if (!response.ok) throw new Error(response.status === 403 ? 'Bạn không có quyền xem dữ liệu này.' : 'Chưa thể tải dữ liệu. Kiểm tra dịch vụ hỗ trợ rồi bấm Làm mới.');
        return response.json();
    }
    function renderActions(view) {
        const nav = $('navigation');
        view.actions.forEach(item => {
            const link = document.createElement('a'); link.href = item.href; link.className = 'action';
            const icon = document.createElement('span'); icon.className = 'action-icon'; icon.textContent = item.icon;
            const info = document.createElement('div'); const title = document.createElement('h3'); title.textContent = item.title;
            const description = document.createElement('p'); description.textContent = item.description; info.append(title,description); link.append(icon,info); $('actions').append(link);
            const entry = document.createElement('a'); entry.href = item.href; entry.textContent = item.title; nav.append(entry);
        });
        if (!view.actions.some(item => item.href === '/profile')) { const profile = document.createElement('a'); profile.href='/profile'; profile.textContent='Hồ sơ'; nav.append(profile); }
        const exit = document.createElement('a'); exit.href='#'; exit.textContent='Đăng xuất'; exit.addEventListener('click', event => {event.preventDefault(); $('logout').click();}); nav.append(exit);
    }
    let generation = 0;
    async function loadRequests() {
        const current = ++generation;
        $('request-table').hidden = true; $('request-message').hidden = false; $('request-message').textContent = 'Đang tải yêu cầu…'; $('result-count').textContent='';
        const query = new URLSearchParams({q:$('search').value.trim(),status:$('status').value});
        try {
            const result = await api(url('requests','/api/requests?')+query);
            if (current !== generation) return;
            const summary = result.summary;
            if (summary) {
                $('count-all').textContent=summary.total;
                $('count-new').textContent=summary.statuses.new || 0;
                $('count-progress').textContent=summary.statuses.in_progress || 0;
                $('count-resolved').textContent=summary.statuses.resolved || 0;
                $('stats-feedback').textContent='Số liệu chỉ bao gồm yêu cầu bạn được phép xem.';
                $('stats-feedback').classList.remove('error');
            }
            $('request-rows').replaceChildren();
            const items = result.data.slice(0,6);
            $('request-message').hidden = items.length > 0; $('request-table').hidden = !items.length;
            $('request-message').textContent = 'Chưa có yêu cầu phù hợp. Yêu cầu mới sẽ xuất hiện tại đây.';
            $('result-count').textContent = `Hiển thị ${items.length} / ${result.meta.total} yêu cầu`;
            items.forEach(item => {
                const row = document.createElement('tr');
                const name = document.createElement('td'); const title = document.createElement('a'); title.href=tickets+'/'+item.id; title.textContent=item.title; const code=document.createElement('small'); code.textContent=item.code; name.append(title,code);
                const state=document.createElement('td');const badge=document.createElement('span');badge.className='badge '+(Object.hasOwn(labels,item.status)?item.status:'');badge.textContent=labels[item.status] || item.status;state.append(badge);
                const priority=document.createElement('td');priority.textContent=priorities[item.priority] || '—';
                const date=document.createElement('td');date.textContent=item.created_at?new Date(item.created_at).toLocaleDateString('vi-VN'):'—';
                const go=document.createElement('td');const link=document.createElement('a');link.href=title.href;link.textContent='Chi tiết →';go.append(link);row.append(name,state,priority,date,go);$('request-rows').append(row);
            });
        } catch(error) { if(current===generation) {
            $('request-message').textContent=error.message;
            ['all','new','progress','resolved'].forEach(id=>$('count-'+id).textContent='—');
            $('stats-feedback').textContent='Chưa tải được số liệu. Dấu — không phải số 0; hãy kiểm tra dịch vụ và bấm Làm mới.';
            $('stats-feedback').classList.add('error');
        } }
    }
    $('logout').addEventListener('click',async () => {
        try { const response=await fetch('/api/v1/auth/logout',{method:'POST',headers:{Accept:'application/json',Authorization:'Bearer '+token}});if(!response.ok&&response.status!==401)throw new Error();localStorage.removeItem('access_token');localStorage.removeItem('current_user');location.replace('/login'); }
        catch { $('identity-error').hidden=false;$('identity-error').textContent='Chưa thể đăng xuất. Vui lòng thử lại.'; }
    });
    $('search-form').addEventListener('submit',event=>{event.preventDefault();loadRequests();});
    $('status').addEventListener('change',loadRequests);
    $('refresh').addEventListener('click',loadRequests);
    (async () => {
        if(!token){location.replace('/login');return;}
        try {
            const {user}=await api('/api/v1/auth/me');const view=roleViews[user.role];if(!view)throw new Error('Vai trò tài khoản chưa được hỗ trợ.');
            $('user-name').textContent=user.name;$('user-role').textContent=view.label;$('avatar').textContent=user.name.slice(0,1).toUpperCase();
            $('today').textContent=new Date().toLocaleDateString('vi-VN',{weekday:'long',day:'numeric',month:'long'});
            $('role-eyebrow').textContent=view.label.toUpperCase();$('heading').textContent=view.heading;$('description').textContent=view.description;
            $('hero-title').textContent=view.hero;$('hero-description').textContent=view.detail;$('hero-link').href=view.href;
            $('primary-action').textContent=view.primary;$('primary-action').href=view.href;$('scope-label').textContent=view.scope;$('requests-heading').textContent=view.scope;
            $('view-all').href=tickets;document.querySelectorAll('.metric').forEach(link=>link.href=tickets+'?status='+link.dataset.status);
            renderActions(view);$('dashboard').hidden=false;await loadRequests();
        } catch(error){$('identity-error').hidden=false;$('identity-error').textContent=error.message;}
    })();
})();
