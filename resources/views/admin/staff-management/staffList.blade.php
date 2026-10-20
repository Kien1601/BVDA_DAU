@extends('layouts.admin')

@section('title', 'Nhân viên')

@section('content')
    <x-page-header title="Nhân viên" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.staff.index') }}" class="flex gap-2">
            <x-text-input name="q" :value="$q" placeholder="Tên, email hoặc số điện thoại" class="w-72" />
            <x-button type="submit">Tìm</x-button>
        </form>
        <a href="{{ route('admin.staff.create') }}" class="rounded bg-solid px-4 py-2 text-sm text-on-solid hover:bg-vermilion">Thêm nhân viên</a>
    </div>

    @error('staff')
        <div class="mb-4 rounded border border-vermilion bg-card px-4 py-3 text-sm text-vermilion">{{ $message }}</div>
    @enderror

    <div class="overflow-x-auto rounded-lg border border-edge-soft bg-card">
        <table class="w-full text-sm">
            <thead class="border-b border-edge-soft text-left text-fg-muted">
                <tr>
                    <th class="px-4 py-3">Họ tên</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Số điện thoại</th>
                    <th class="px-4 py-3">Vai trò</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-edge-soft">
                @forelse ($staffs as $s)
                    @php($role = \App\Services\StaffService::roleOf($s))
                    <tr>
                        <td class="px-4 py-3 text-fg">
                            {{ $s->name }}
                            @if ($s->is(auth()->user()))
                                <span class="text-xs text-fg-muted">(bạn)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-fg-soft">{{ $s->email }}</td>
                        <td class="px-4 py-3 text-fg-soft">{{ $s->phone ?? '—' }}</td>
                        <td class="px-4 py-3 text-fg">{{ $roles[$role] ?? $role }}</td>
                        <td class="px-4 py-3 {{ $s->is_active ? 'text-fg' : 'text-vermilion' }}">{{ $s->is_active ? 'Đang hoạt động' : 'Đã khóa' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.staff.edit', $s) }}" class="text-fg-soft hover:text-vermilion">Sửa</a>
                            @unless ($s->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.staff.toggle-lock', $s) }}" class="ml-3 inline">
                                    @csrf
                                    <button type="submit" class="text-fg-soft hover:text-vermilion">{{ $s->is_active ? 'Khóa' : 'Mở khóa' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-fg-soft">Không tìm thấy nhân viên.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $staffs->links() }}</div>
@endsection