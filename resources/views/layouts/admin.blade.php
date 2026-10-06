<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý') — Rental GPS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="flex min-h-screen">
        @include('layouts.partials.admin-sidebar')

        <div class="flex-1 flex flex-col">
            @include('layouts.partials.admin-topbar')

            <main class="p-6">
                @include('layouts.partials.flash-message')
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>