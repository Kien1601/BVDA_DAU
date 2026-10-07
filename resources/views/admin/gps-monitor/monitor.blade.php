@extends('layouts.admin')

@section('title', 'Giám sát GPS')

@section('content')
    <div id="gps-monitor"
         class="gps-monitor"
         data-positions-url="{{ route('admin.gps-monitor.positions') }}"
         data-lat="{{ $center['lat'] }}"
         data-lng="{{ $center['lng'] }}">
        @include('admin.gps-monitor.partials.map-panel')
        @include('admin.gps-monitor.partials.vehicle-list-panel')
    </div>
@endsection

@push('scripts')
    @vite('resources/views/admin/gps-monitor/gpsMonitor.page.js')
@endpush