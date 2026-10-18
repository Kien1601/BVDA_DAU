<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ViolationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Violation;
use App\Services\CustomerService;
use App\Services\ViolationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customers)
    {
    }

    public function index(Request $request)
    {
        $filter = in_array($request->query('filter'), ['locked', 'violating'], true)
            ? $request->query('filter')
            : null;

        return view('admin.customer-management.customerList', [
            'customers' => $this->customers->search($filter, $request->query('q')),
            'counts' => $this->customers->counts(),
            'filter' => $filter,
            'q' => $request->query('q'),
            'warnAt' => CustomerService::WARN_VIOLATIONS,
        ]);
    }

    public function show(User $customer)
    {
        $this->ensureCustomer($customer);

        return view('admin.customer-management.customerDetail', [
            'customer' => $customer,
            'summary' => $this->customers->summary($customer),
            'orders' => $customer->rentalOrders()->with('vehicle')->latest('id')->limit(20)->get(),
            'violations' => $customer->violations()->with(['rentalOrder', 'recorder'])->get(),
            'warnAt' => CustomerService::WARN_VIOLATIONS,
            'manualTypes' => self::manualTypes(),
        ]);
    }

    public function toggleLock(User $customer)
    {
        $this->ensureCustomer($customer);

        $this->customers->setActive($customer, ! $customer->is_active);

        return back()->with('success', $customer->is_active
            ? 'Đã mở khóa tài khoản.'
            : 'Đã khóa tài khoản và đăng xuất khách khỏi mọi thiết bị.');
    }

    public function storeViolation(Request $request, User $customer, ViolationService $violations)
    {
        $this->ensureCustomer($customer);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_map(fn ($t) => $t->value, self::manualTypes()))],
            'rental_order_id' => [
                'nullable', 'integer',
                Rule::exists('rental_orders', 'id')->where('user_id', $customer->id),
            ],
            'description' => ['required', 'string', 'max:500'],
            'fine_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ], [
            'type.required' => 'Chọn loại vi phạm.',
            'rental_order_id.exists' => 'Đơn này không thuộc khách hàng.',
            'description.required' => 'Ghi rõ vi phạm gì.',
            'fine_amount.min' => 'Tiền phạt không được âm.',
            'occurred_at.before_or_equal' => 'Thời điểm vi phạm không được ở tương lai.',
        ]);

        $violations->record($customer, ViolationType::from($data['type']), $data, $request->user());

        return back()->with('success', 'Đã ghi vi phạm.');
    }

    public function destroyViolation(User $customer, Violation $violation, ViolationService $violations)
    {
        $this->ensureCustomer($customer);
        abort_unless($violation->user_id === $customer->id, 404);

        $violations->delete($violation);

        return back()->with('success', 'Đã xóa vi phạm.');
    }

    /** Loại nhân viên được ghi tay; "Trả xe trễ" do hệ thống tự ghi */
    private static function manualTypes(): array
    {
        return array_values(array_filter(
            ViolationType::cases(),
            fn ($type) => $type !== ViolationType::LateReturn,
        ));
    }

    /** Trang này chỉ dành cho khách, gõ id nhân viên vào URL thì ra 404 */
    private function ensureCustomer(User $user): void
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        abort_unless($role === 'customer', 404);
    }
}