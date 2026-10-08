
@extends('layouts.app')

@section('title','Tổng quan – UniSupport')

@section('content')
@include('partials.dashboard-content', ['roleLabel'=>'Sinh viên', 'intro'=>'Theo dõi mọi yêu cầu và nhận hỗ trợ từ các phòng ban.'])

@endsection
