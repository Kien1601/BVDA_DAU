@extends('layouts.admin')

@section('title', 'Sửa nhân viên')

@section('content')
    <x-page-header :title="'Sửa nhân viên: ' . $staff->name" />

    @error('role')
        <div class="mb-4 max-w-xl rounded border border-vermilion bg-card px-4 py-3 text-sm text-vermilion">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="max-w-xl rounded-lg border border-edge-soft bg-card p-6">
        @method('PUT')
        @include('admin.staff-management.partials.staff-form')
    </form>
@endsection