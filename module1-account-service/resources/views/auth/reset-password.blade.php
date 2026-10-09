
@extends('layouts.auth')
@section('title','Đặt lại mật khẩu')
@section('content')<h2>Đặt lại mật khẩu</h2>
<p>Khôi phục quyền truy cập tài khoản UniSupport.</p>
@if(session('status'))<x-alert>{{ session('status') }}</x-alert>
@endif<form class="uni-form" method="POST" action="{{ route('password.update') }}">
@csrf<label>Email<input type="email" name="email" value="{{ old('email',request('email')) }}" required autocomplete="email">
</label>
<x-field-error name="email"/>
<input type="hidden" name="token" value="{{ $token }}">
<label>Mật khẩu mới<input type="password" name="password" minlength="8" required autocomplete="new-password">
</label>
<x-field-error name="password"/>
<label>Xác nhận mật khẩu<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
</label>
<button type="submit" class="uni-button">Lưu mật khẩu mới</button>
</form>
<p>
<a href="/login">Quay lại đăng nhập</a>
</p>
@endsection
