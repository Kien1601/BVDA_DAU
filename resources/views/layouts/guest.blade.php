<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Rental GPS' }} — Thuê xe & định vị</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/views/layouts/guest.page.js'])
</head>
<body class="bg-ink font-sans font-light text-bone antialiased">
    <div class="relative min-h-screen overflow-hidden">
        {{-- cảnh "thành phố đêm", chất lượng thấp: chỉ là nền phía sau form --}}
        <div id="city-scene" class="scene-host absolute inset-0" data-quality="low" aria-hidden="true"></div>

        {{-- làm tối nhẹ phần giữa để form luôn nổi rõ trên nền cảnh --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_55%_at_50%_50%,rgba(5,7,10,.55),rgba(5,7,10,.15)_70%,transparent)]"></div>

        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3">
                <span class="h-2 w-2 rounded-full bg-vermilion shadow-[0_0_12px_#e0231c]"></span>
                <span class="leading-none">
                    <span class="block text-sm font-medium tracking-[.26em]">RENTAL GPS</span>
                    <span class="mt-1.5 block text-[9px] tracking-[.34em] text-bone-dim">THUÊ XE · ĐỊNH VỊ THỜI GIAN THỰC</span>
                </span>
            </a>

            <div class="w-full max-w-md rounded-xl border border-white/10 bg-paper p-8 text-ink shadow-[0_30px_80px_-20px_rgba(0,0,0,.8)]">
                {{ $slot }}
            </div>

            <p class="mt-8 text-[10px] uppercase tracking-label text-muted">
                © {{ date('Y') }} Rental GPS
            </p>
        </div>
    </div>
</body>
</html>