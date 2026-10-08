
@extends('layouts.app')

@section('title','Tổng quan – UniSupport')

@section('content')
@include('partials.dashboard-content', ['roleLabel'=>'Cán bộ', 'intro'=>'Ưu tiên đúng công việc, phản hồi kịp thời cho sinh viên.'])

@endsection
