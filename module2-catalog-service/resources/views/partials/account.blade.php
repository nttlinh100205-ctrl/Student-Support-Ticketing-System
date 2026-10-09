@if(! config('account.fake'))
@include('partials.suite-header')
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
