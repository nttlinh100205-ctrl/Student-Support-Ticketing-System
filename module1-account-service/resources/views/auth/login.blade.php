
@extends('layouts.auth')
@section('title','Đăng nhập')
@section('content')<span class="uni-eyebrow">CHÀO MỪNG BẠN TRỞ LẠI</span>
<h2>Đăng nhập UniSupport</h2>
<p>Sử dụng tài khoản được cấp để tiếp tục.</p>
@if(session('status'))<x-alert>{{ session('status') }}</x-alert>
@endif<form id="loginForm" class="uni-form" data-auth="login">
<label for="email">Email hoặc mã số sinh viên<input id="email" name="email" autocomplete="username" required placeholder="Nhập email hoặc MSSV">
</label>
<label for="password">Mật khẩu<div class="uni-password-wrap">
<input id="password" name="password" type="password" autocomplete="current-password" required>
<button type="button" data-password-toggle="password" aria-label="Hiện mật khẩu">
<i class="fa-regular fa-eye">
</i>
</button>
</div>
</label>
<div class="uni-auth-options">
<label>
<input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập</label>
<a href="{{ route('password.request') }}">Quên mật khẩu?</a>
</div>
<button class="uni-button" type="submit">Đăng nhập <i class="fa-solid fa-arrow-right">
</i>
</button>
<div id="message" role="status" aria-live="polite">
</div>
</form>
<p class="uni-auth-switch">Bạn chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký sinh viên</a>
</p>
@endsection
@section('scripts')<script src="/js/auth-forms.js" defer>
</script>
@endsection
