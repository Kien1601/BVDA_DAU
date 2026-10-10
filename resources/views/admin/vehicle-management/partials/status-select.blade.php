@if ($vehicle->status === \App\Enums\VehicleStatus::Rented)
    {{-- C2.5: không gửi trường status lên, vì trạng thái này do đơn thuê quyết định --}}
    <div>
        <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Trạng thái</div>
        <div class="mt-2.5">
            <x-status-badge tone="info">Đang cho thuê</x-status-badge>
        </div>
        <p class="mt-2 text-xs text-fg-muted">Trạng thái do đơn thuê quyết định, không đổi thủ công được.</p>
    </div>
@else
    <x-form.select name="status" label="Trạng thái"
        :value="$vehicle->status?->value ?? 'available'"
        :options="collect(\App\Enums\VehicleStatus::manual())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
@endif