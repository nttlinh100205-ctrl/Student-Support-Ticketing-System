
@extends('layouts.auth')
@section('title','Quên mật khẩu')
@section('content')<h2>Quên mật khẩu</h2>
<p>Khôi phục quyền truy cập tài khoản UniSupport.</p>
@if(session('status'))<x-alert>{{ session('status') }}</x-alert>
@endif<form class="uni-form" method="POST" action="{{ route('password.email') }}">
@csrf<label>Email<input type="email" name="email" value="{{ old('email',request('email')) }}" required autocomplete="email">
</label>
<x-field-error name="email"/>
<button type="submit" class="uni-button">Gửi liên kết đặt lại mật khẩu</button>
</form>
<p>
<a href="/login">Quay lại đăng nhập</a>
</p>
@endsection
