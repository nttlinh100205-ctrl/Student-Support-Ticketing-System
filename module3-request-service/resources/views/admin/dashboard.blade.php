
@extends('layouts.app')

@section('title','Tổng quan – UniSupport')

@section('content')
@include('partials.dashboard-content', ['roleLabel'=>'Quản trị viên', 'intro'=>'Tổng quan hoạt động hỗ trợ và chất lượng phục vụ toàn hệ thống.'])

@endsection
