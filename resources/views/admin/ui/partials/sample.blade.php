{{-- Một cột của trang mẫu giao diện. $theme dùng làm hậu tố id để hai cột không trùng id. --}}
<div class="mb-6 flex items-center gap-2 text-[10px] font-medium uppercase tracking-label text-fg-muted">
    <span class="h-1 w-1 rounded-full bg-vermilion"></span>{{ $caption }}
</div>

<div class="space-y-10">
    <section>
        <x-page-header title="Tiêu đề trang" eyebrow="Nhãn nhỏ">
            <x-slot:actions>
                <x-button variant="secondary" type="button">Phụ</x-button>
                <x-button type="button">Chính</x-button>
            </x-slot:actions>
        </x-page-header>
    </section>

    <section>
        <div class="mb-3 text-[10px] font-medium uppercase tracking-label text-fg-muted">Nút · 6 biến thể</div>
        <div class="flex flex-wrap items-center gap-3">
            <x-button variant="primary" type="button">Primary</x-button>
            <x-button variant="secondary" type="button">Secondary</x-button>
            <x-button variant="light" type="button">Light</x-button>
            <x-button variant="outline-light" type="button">Outline light</x-button>
            <x-button variant="link" type="button">Link</x-button>
            <x-button variant="link-danger" type="button">Link danger</x-button>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="ui_normal" id="ui_normal_{{ $theme }}" label="Ô nhập thường" placeholder="Nhập nội dung" hint="Dòng gợi ý." />
        <x-form.input name="ui_error" id="ui_error_{{ $theme }}" label="Ô nhập có lỗi" value="abc" />
        <x-form.input name="ui_readonly" id="ui_readonly_{{ $theme }}" label="Ô chỉ đọc" value="59X1-123.45" readonly />
        <x-form.select name="ui_select" id="ui_select_{{ $theme }}" label="Ô chọn" placeholder="Tất cả"
                       :options="['tay-ga' => 'Tay ga', 'xe-so' => 'Xe số', 'con-tay' => 'Côn tay']" />
    </section>

    <section>
        <x-table>
            <x-slot:head>
                <th>Biển số</th>
                <th>Cửa hàng</th>
                <th>Trạng thái</th>
            </x-slot:head>
            <tr>
                <td class="tabular-nums">59X1-123.45</td>
                <td class="text-fg-soft">CH Quận 1</td>
                <td><x-status-badge tone="success">Sẵn sàng</x-status-badge></td>
            </tr>
            <tr>
                <td class="tabular-nums">59X2-678.90</td>
                <td class="text-fg-soft">CH Quận 3</td>
                <td><x-status-badge tone="info">Đang cho thuê</x-status-badge></td>
            </tr>
            <tr>
                <td class="tabular-nums">59X3-111.22</td>
                <td class="text-fg-soft">CH Bình Thạnh</td>
                <td><x-status-badge tone="warning">Bảo dưỡng</x-status-badge></td>
            </tr>
        </x-table>
    </section>

    <section class="grid gap-4 sm:grid-cols-2">
        <x-stat-card label="Xe sẵn sàng" value="24" index="01">
            <x-slot:foot>Thẻ thường</x-slot:foot>
        </x-stat-card>
        <x-stat-card label="Doanh thu hôm nay" value="3.250.000 ₫" index="02" accent>
            <x-slot:foot>Thẻ accent</x-slot:foot>
        </x-stat-card>
    </section>

    <section>
        <div class="mb-3 text-[10px] font-medium uppercase tracking-label text-fg-muted">Nhãn trạng thái · 5 tông</div>
        <div class="flex flex-wrap gap-5">
            <x-status-badge tone="success">Success</x-status-badge>
            <x-status-badge tone="warning">Warning</x-status-badge>
            <x-status-badge tone="danger">Danger</x-status-badge>
            <x-status-badge tone="info">Info</x-status-badge>
            <x-status-badge tone="neutral">Neutral</x-status-badge>
        </div>
    </section>

    <section>
        <div class="mb-3 text-[10px] font-medium uppercase tracking-label text-fg-muted">Thông báo</div>
        @include('layouts.partials.flash-message')
    </section>

    <section>
        <div class="mb-3 text-[10px] font-medium uppercase tracking-label text-fg-muted">Thẻ xe · có ảnh / chưa có ảnh</div>
        <div class="grid gap-6 sm:grid-cols-2">
            @foreach ($cards as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" />
            @endforeach
        </div>
    </section>
</div>
