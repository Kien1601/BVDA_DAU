<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý') — Rental GPS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-page font-sans font-light text-fg antialiased">
    <div class="flex min-h-screen">
        @include('layouts.partials.admin-sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.admin-topbar')

            <main class="flex-1 p-6 lg:p-8">
                @include('layouts.partials.flash-message')
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>