@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <x-page-header eyebrow="Tổng quan" title="Dashboard vận hành" />

    @include('admin.dashboard.partials.stat-cards')
    @include('admin.dashboard.partials.recent-orders')
@endsection