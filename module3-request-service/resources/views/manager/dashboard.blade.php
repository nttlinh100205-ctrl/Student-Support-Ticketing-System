
@extends('layouts.app')

@section('title','Tổng quan – UniSupport')

@section('content')
@include('partials.dashboard-content', ['roleLabel'=>'Trưởng phòng', 'intro'=>'Điều phối công việc và theo dõi chất lượng phục vụ của phòng.'])

@endsection
