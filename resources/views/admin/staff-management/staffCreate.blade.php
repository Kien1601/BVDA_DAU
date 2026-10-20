@extends('layouts.admin')

@section('title', 'Thêm nhân viên')

@section('content')
    <x-page-header title="Thêm nhân viên" />

    <form method="POST" action="{{ route('admin.staff.store') }}" class="max-w-xl rounded-lg border border-edge-soft bg-card p-6">
        @include('admin.staff-management.partials.staff-form')
    </form>
@endsection