{{--
    Bảng danh sách: không khung đậm, chỉ kẻ ngang mảnh giữa các dòng.
    Slot "head" chứa các <th>, slot chính chứa các <tr>.
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-ink/10 bg-white']) }}>
    <table class="w-full text-sm">
        <thead class="border-b border-ink/10 text-left">
            <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:text-[10px] [&>th]:font-medium [&>th]:uppercase [&>th]:tracking-label [&>th]:text-muted">
                {{ $head }}
            </tr>
        </thead>
        <tbody class="divide-y divide-ink/[.06] [&>tr>td]:px-4 [&>tr>td]:py-3 [&>tr]:transition-colors [&>tr:hover]:bg-paper">
            {{ $slot }}
        </tbody>
    </table>
</div>