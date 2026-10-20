<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function __construct(private StaffService $staffs) {}

    public function index(Request $request)
    {
        return view('admin.staff-management.staffList', [
            'staffs' => $this->staffs->search($request->query('q')),
            'q' => $request->query('q'),
            'roles' => StaffService::ROLES,
        ]);
    }

    public function create()
    {
        return view('admin.staff-management.staffCreate', [
            'staff' => null,
            'roles' => StaffService::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        $this->staffs->create($data);

        return redirect()->route('admin.staff.index')->with('success', 'Đã thêm nhân viên.');
    }

    public function edit(User $staff)
    {
        $this->ensureStaff($staff);

        return view('admin.staff-management.staffEdit', [
            'staff' => $staff,
            'roles' => StaffService::ROLES,
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $this->ensureStaff($staff);
        $data = $request->validate($this->rules($staff), $this->messages());
        $this->staffs->update($staff, $data, $request->user());

        return redirect()->route('admin.staff.index')->with('success', 'Đã cập nhật nhân viên.');
    }

    public function toggleLock(Request $request, User $staff)
    {
        $this->ensureStaff($staff);
        $staff = $this->staffs->toggleLock($staff, $request->user());

        return back()->with('success', $staff->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }

    private function rules(?User $staff = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'phone' => ['nullable', 'regex:/^0\d{9}$/', Rule::unique('users', 'phone')->ignore($staff?->id)],
            'role' => ['required', Rule::in(array_keys(StaffService::ROLES))],
            'password' => [$staff ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã có tài khoản.',
            'phone.regex' => 'Số điện thoại gồm 10 số, bắt đầu bằng 0.',
            'phone.unique' => 'Số điện thoại này đã có tài khoản.',
            'role.in' => 'Vai trò không hợp lệ.',
            'password.required' => 'Vui lòng đặt mật khẩu.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'password.min' => 'Mật khẩu ít nhất 8 ký tự.',
        ];
    }

    /** Không cho mở tài khoản khách qua đường dẫn của trang nhân viên */
    private function ensureStaff(User $user): void
    {
        abort_unless(array_key_exists(StaffService::roleOf($user), StaffService::ROLES), 404);
    }
}