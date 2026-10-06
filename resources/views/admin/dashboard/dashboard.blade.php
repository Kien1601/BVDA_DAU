@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @include('admin.dashboard.partials.stat-cards')
    @include('admin.dashboard.partials.recent-orders')
@endsection