{{--
    Nút tròn đổi chế độ sáng/tối. Xử lý click: resources/js/shared/theme/theme.js.
    Icon ẩn/hiện bằng CSS theo html[data-theme] (resources/css/app.css):
    mặt trời khi đang tối (bấm để sang sáng), mặt trăng khi đang sáng.
--}}
<button type="button" data-theme-toggle aria-label="Chế độ tối" aria-pressed="false" title="Đổi chế độ sáng/tối"
    {{ $attributes->merge(['class' => 'grid h-9 w-9 shrink-0 place-items-center rounded-full border border-edge-strong text-fg-soft transition-colors duration-300 hover:border-fg/40 hover:text-fg focus:outline-none focus-visible:ring-2 focus-visible:ring-vermilion/50']) }}>
    <svg class="theme-icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/>
    </svg>
    <svg class="theme-icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true">
        <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/>
    </svg>
</button>
