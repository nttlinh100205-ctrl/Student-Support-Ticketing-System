
@extends('layouts.auth')
@section('title','Đăng ký sinh viên')
@section('content')<span class="uni-eyebrow">BẮT ĐẦU CÙNG UNISUPPORT</span>
<h2>Tạo tài khoản sinh viên</h2>
<p>Thông tin của bạn giúp cán bộ hỗ trợ chính xác hơn.</p>
<form id="registerForm" class="uni-form" data-auth="register">
@foreach(['name'=>'Họ và tên','student_code'=>'Mã số sinh viên','email'=>'Email','phone'=>'Số điện thoại'] as $name=>$label)<label for="{{ $name }}">{{ $label }}<input id="{{ $name }}" name="{{ $name }}" type="{{ $name==='email'?'email':'text' }}" @required($name!=='phone') maxlength="{{ $name==='student_code'?30:255 }}">
</label>
@endforeach<label>Mật khẩu<input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
</label>
<label>Nhập lại mật khẩu<input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
</label>
<button class="uni-button" type="submit">Tạo tài khoản</button>
<div id="message" role="status" aria-live="polite">
</div>
</form>
<p class="uni-auth-switch">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a>
</p>
@endsection
@section('scripts')<script src="/js/auth-forms.js" defer>
</script>
@endsection
