@if(! config('account.fake'))
<nav style="padding:12px;background:#172b4d;color:white;display:flex;gap:18px;align-items:center;flex-wrap:wrap">
<a style="color:white" href="{{ rtrim(config('account.url'), '/') }}">Cổng hỗ trợ sinh viên</a>
<span>{{ request()->attributes->get('account_user')['full_name'] ?? '' }}</span>
<form method="POST" action="{{ route('account.logout') }}" style="margin-left:auto">@csrf<button type="submit">Đăng xuất</button></form>
</nav>
<script>
window.AccountUser = @json(request()->attributes->get('account_user'));
window.AccountHeaders = () => ({'Accept': 'application/json', 'Authorization': 'Bearer ' + @json(session('account_token'))});
const accountFetch = window.fetch.bind(window);
window.fetch = (input, options = {}) => {
    const url = new URL(input instanceof Request ? input.url : input, location.href);
    if (url.origin === location.origin && url.pathname.startsWith('/api/')) {
        const headers = new Headers(input instanceof Request ? input.headers : undefined);
        new Headers(options.headers).forEach((value, key) => headers.set(key, value));
        Object.entries(window.AccountHeaders()).forEach(([key, value]) => headers.set(key, value));
        options = {...options, headers};
    }
    return accountFetch(input, options);
};
</script>
@endif
