<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Thuê xe máy') — Rental GPS</title>
    <meta name="description" content="@yield('description', 'Thuê xe máy tại TP.HCM, đặt nhanh, nhận xe tại cửa hàng, đội xe được theo dõi bằng GPS.')">

    {{-- bật hiệu ứng hiện dần trước khi trang vẽ, để không bị nháy --}}
    <script>document.documentElement.classList.add('has-reveal');</script>
    <style>[x-cloak]{display:none !important}</style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/views/layouts/customer.page.js'])
    @stack('styles')
</head>
<body class="bg-ink font-sans font-light text-bone antialiased selection:bg-vermilion selection:text-white">
    @include('layouts.partials.customer-header')

    <main>
        @yield('content')
    </main>

    @include('layouts.partials.customer-footer')

    @stack('scripts')
</body>
</html>