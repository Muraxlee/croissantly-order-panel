<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmployeeSlot;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Support\StaffPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        return view('admin.index', [
            'counts' => $this->counts(),
        ]);
    }

    public function orders(Request $request)
    {
        $this->authorizeAdmin();

        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $orders = Order::with(['client.clientProfile', 'items'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_address', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('clientProfile', function ($query) use ($search) {
                                    $query->where('business_name', 'like', "%{$search}%")
                                        ->orWhere('contact_name', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('items', function ($query) use ($search) {
                            $query->where('product_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->get();

        return view('admin.orders', [
            'orders' => $orders,
            'clients' => User::where('role', 'client')
                ->where('is_active', true)
                ->where('username', 'not like', 'walkin-%')
                ->with('clientProfile')
                ->orderBy('name')
                ->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function accounts()
    {
        $this->authorizeAdmin();

        return view('admin.accounts', [
            'clients' => User::where('role', 'client')->where('username', 'not like', 'walkin-%')->with('clientProfile')->orderBy('name')->get(),
            'employees' => User::where('role', 'employee')->with('employeeProfile')->orderBy('name')->get(),
            'kitchenUsers' => User::where('role', 'kitchen')->with('kitchenProfile')->orderBy('name')->get(),
        ]);
    }

    public function slots(Request $request)
    {
        $this->authorizeAdmin();

        $dateInput = $request->query('date');
        $monthInput = $request->query('month');
        $seedDate = $monthInput
            ? Carbon::parse($monthInput.'-01')
            : ($dateInput ? Carbon::parse($dateInput) : ($request->query('start') ? Carbon::parse($request->query('start')) : today()));

        $monthStart = $seedDate->copy()->startOfMonth();
        $monthEnd = $seedDate->copy()->endOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $selectedDate = $dateInput ? Carbon::parse($dateInput)->startOfDay() : (today()->between($monthStart, $monthEnd) ? today() : $monthStart->copy());

        if ($selectedDate->lt($monthStart) || $selectedDate->gt($monthEnd)) {
            $selectedDate = $monthStart->copy();
        }

        [$periodStart, $periodEnd] = StaffPeriod::resolve($selectedDate->toDateString());
        $nextPlanStart = StaffPeriod::nextUncreatedStart();
        $nextPlanEnd = StaffPeriod::endFor($nextPlanStart);

        $employees = User::where('role', 'employee')
            ->with('employeeProfile')
            ->orderBy('name')
            ->get();

        $slots = EmployeeSlot::with('employee.employeeProfile')
            ->whereBetween('slot_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('slot_date')
            ->orderBy(User::select('name')->whereColumn('users.id', 'employee_slots.employee_id'))
            ->get();

        $slotsByDate = $slots->groupBy(fn ($slot) => $slot->slot_date->toDateString());

        return view('admin.slots', [
            'employees' => $employees,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'days' => StaffPeriod::days($periodStart),
            'calendarWeeks' => collect(iterator_to_array(\Carbon\CarbonPeriod::create($calendarStart, $calendarEnd)))->chunk(7),
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'selectedDate' => $selectedDate,
            'slotsByDate' => $slotsByDate,
            'selectedDateSlots' => $slotsByDate->get($selectedDate->toDateString(), collect()),
            'previousMonth' => $monthStart->copy()->subMonth(),
            'nextMonth' => $monthStart->copy()->addMonth(),
            'nextPlanStart' => $nextPlanStart,
            'nextPlanEnd' => $nextPlanEnd,
            ...StaffPeriod::navigation($periodStart),
        ]);
    }

    public function employeeActualTime(Request $request, User $employee)
    {
        $this->authorizeAdmin();
        abort_unless($employee->isEmployee(), 404);

        [$periodStart, $periodEnd] = StaffPeriod::resolve($request->query('start'));

        return view('admin.employee-actual', [
            'employee' => $employee->load('employeeProfile'),
            'slots' => EmployeeSlot::where('employee_id', $employee->id)
                ->whereBetween('slot_date', [$periodStart, $periodEnd])
                ->orderBy('slot_date')
                ->get(),
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            ...StaffPeriod::navigation($periodStart),
        ]);
    }

    public function updateEmployeeProfile(Request $request, User $employee)
    {
        $this->authorizeAdmin();
        abort_unless($employee->isEmployee(), 404);

        $data = $request->validate([
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:9999'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        DB::table('employee_profiles')->updateOrInsert(
            ['user_id' => $employee->id],
            [
                'staff_code' => strtoupper($employee->username),
                'phone' => $data['phone'] ?? null,
                'hourly_rate' => $data['hourly_rate'],
                'created_at' => $employee->employeeProfile?->created_at ?? now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('status', 'Employee cost updated.');
    }

    public function timesheets(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $requestedStart = $request->query('start_date', $request->query('start'));
        $periodStart = $requestedStart
            ? Carbon::parse($requestedStart)->startOfDay()
            : StaffPeriod::currentStart();
        $periodEnd = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : StaffPeriod::endFor($periodStart);

        if ($periodEnd->lt($periodStart)) {
            $periodEnd = $periodStart->copy()->endOfDay();
        }

        $rangeDays = $periodStart->diffInDays($periodEnd) + 1;
        $employeeId = $request->query('employee_id');
        $search = trim((string) $request->query('q', ''));

        $slots = EmployeeSlot::with('employee.employeeProfile')
            ->whereBetween('slot_date', [$periodStart, $periodEnd])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('employeeProfile', function ($query) use ($search) {
                                    $query->where('staff_code', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->orderBy('slot_date')
            ->orderBy(User::select('name')->whereColumn('users.id', 'employee_slots.employee_id'))
            ->get();

        return view('admin.timesheets', [
            'slots' => $slots,
            'employees' => User::where('role', 'employee')->orderBy('name')->get(),
            'selectedEmployeeId' => $employeeId,
            'search' => $search,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'previousPeriodStart' => $periodStart->copy()->subDays($rangeDays),
            'previousPeriodEnd' => $periodEnd->copy()->subDays($rangeDays),
            'nextPeriodStart' => $periodStart->copy()->addDays($rangeDays),
            'nextPeriodEnd' => $periodEnd->copy()->addDays($rangeDays),
        ]);
    }

    public function menu(Request $request)
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->query('q', ''));

        return view('admin.menu', [
            'products' => Product::when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('price', 'like', "%{$search}%");
                });
            })->orderBy('name')->get(),
            'search' => $search,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['client', 'employee', 'kitchen'])],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'station' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'role' => $data['role'],
                'password' => $data['password'],
            ]);

            if ($user->role === 'client') {
                DB::table('client_profiles')->insert([
                    'user_id' => $user->id,
                    'business_name' => $data['business_name'] ?? null,
                    'contact_name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($user->role === 'employee') {
                DB::table('employee_profiles')->insert([
                    'user_id' => $user->id,
                    'staff_code' => strtoupper($data['username']),
                    'phone' => $data['phone'] ?? null,
                    'hourly_rate' => $data['hourly_rate'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($user->role === 'kitchen') {
                DB::table('kitchen_profiles')->insert([
                    'user_id' => $user->id,
                    'station' => $data['station'] ?? 'Kitchen',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'account.created',
                'target_type' => 'user',
                'target_id' => $user->id,
                'notes' => "Created {$user->role} login {$user->username}",
            ]);
        });

        return back()->with('status', 'Account created.');
    }

    public function updateAccount(Request $request, User $user)
    {
        $this->authorizeAdmin();
        abort_if($user->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'station' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($user, $data, $request) {
            $user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'is_active' => $request->boolean('is_active'),
                ...(! empty($data['password']) ? ['password' => $data['password']] : []),
            ]);

            if ($user->role === 'client') {
                DB::table('client_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'business_name' => $data['business_name'] ?? null,
                        'contact_name' => $data['name'],
                        'phone' => $data['phone'] ?? null,
                        'created_at' => $user->clientProfile?->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            }

            if ($user->role === 'employee') {
                DB::table('employee_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'staff_code' => strtoupper($data['username']),
                        'phone' => $data['phone'] ?? null,
                        'hourly_rate' => $data['hourly_rate'] ?? 0,
                        'created_at' => $user->employeeProfile?->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            }

            if ($user->role === 'kitchen') {
                DB::table('kitchen_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'station' => $data['station'] ?? 'Kitchen',
                        'created_at' => $user->kitchenProfile?->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            }
        });

        return back()->with('status', 'Account updated.');
    }

    public function destroyAccount(User $user)
    {
        $this->authorizeAdmin();
        abort_if($user->isAdmin(), 403);

        $hasLinkedRecords = match ($user->role) {
            'client' => Order::where('client_id', $user->id)->exists(),
            'employee' => EmployeeSlot::where('employee_id', $user->id)->exists(),
            default => false,
        };

        if ($hasLinkedRecords) {
            $user->update(['is_active' => false]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'account.deactivated',
                'target_type' => 'user',
                'target_id' => $user->id,
                'notes' => "Deactivated {$user->role} login {$user->username}; linked records were kept.",
            ]);

            return back()->with('status', 'Account has linked records, so it was deactivated instead of deleting order or staff history.');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'account.deleted',
            'target_type' => 'user',
            'target_id' => $user->id,
            'notes' => "Deleted {$user->role} login {$user->username}",
        ]);

        $user->delete();

        return back()->with('status', 'Account deleted.');
    }

    public function storeProduct(Request $request)
    {
        $this->authorizeAdmin();

        Product::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Product added.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $this->authorizeAdmin();

        $product->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]));

        return back()->with('status', 'Product updated.');
    }

    public function destroyProduct(Product $product)
    {
        $this->authorizeAdmin();

        $product->delete();

        return back()->with('status', 'Product removed.');
    }

    public function storeOrder(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'client_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'client')],
            'customer_name' => ['nullable', 'required_without:client_id', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'required_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->map(fn ($quantity, $productId) => ['product_id' => (int) $productId, 'quantity' => (int) $quantity])
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Add at least one item to the order.']);
        }

        DB::transaction(function () use ($data, $items) {
            $client = null;

            if (! empty($data['client_id'])) {
                $client = User::where('role', 'client')
                    ->where('is_active', true)
                    ->where('username', 'not like', 'walkin-%')
                    ->with('clientProfile')
                    ->findOrFail($data['client_id']);
            } else {
                $client = User::create([
                    'name' => $data['customer_name'],
                    'username' => 'walkin-'.Str::lower(Str::random(10)),
                    'role' => 'client',
                    'is_active' => false,
                    'password' => Str::password(16),
                ]);

                DB::table('client_profiles')->insert([
                    'user_id' => $client->id,
                    'contact_name' => $data['customer_name'],
                    'phone' => $data['customer_phone'] ?? null,
                    'notes' => $data['customer_address'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $customerName = $data['customer_name']
                ?? $client->clientProfile?->contact_name
                ?? $client->name;
            $customerPhone = $data['customer_phone']
                ?? $client->clientProfile?->phone;
            $customerAddress = $data['customer_address']
                ?? $client->clientProfile?->notes;

            $order = Order::create([
                'order_number' => 'CR'.now()->format('ymdHis').random_int(10, 99),
                'client_id' => $client->id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'required_at' => $data['required_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            $this->syncOrderItems($order, $items->all());

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'note' => ! empty($data['client_id']) ? 'Admin created order for client account.' : 'Admin created walk-in order.',
            ]);
        });

        return back()->with('status', 'Order created.');
    }

    public function storeSlot(Request $request)
    {
        $this->authorizeAdmin();

        $isOff = $request->boolean('is_off');

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('users', 'id')->where('role', 'employee')],
            'slot_date' => ['required', 'date'],
            'starts_at' => [$isOff ? 'nullable' : 'required', 'date_format:H:i'],
            'ends_at' => [$isOff ? 'nullable' : 'required', 'date_format:H:i'],
            'is_off' => ['nullable', 'boolean'],
            'has_break' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $slotDate = Carbon::parse($data['slot_date'])->startOfDay();

        EmployeeSlot::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'slot_date' => $slotDate->toDateString()],
            [
                'starts_at' => $isOff ? null : ($data['starts_at'] ?? null),
                'ends_at' => $isOff ? null : ($data['ends_at'] ?? null),
                'is_off' => $isOff,
                'has_break' => $isOff ? false : $request->boolean('has_break'),
                'break_minutes' => (! $isOff && $request->boolean('has_break')) ? 30 : 0,
                'notes' => $data['notes'] ?? null,
            ],
        );

        return redirect()
            ->route('admin.slots.index', [
                'month' => $slotDate->format('Y-m'),
                'date' => $slotDate->toDateString(),
            ])
            ->with('status', 'Employee schedule updated for '.$slotDate->format('D d M').'.');
    }

    public function updateSlotActualTime(Request $request, EmployeeSlot $slot)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'actual_starts_at' => ['required', 'date_format:H:i'],
            'actual_ends_at' => ['required', 'date_format:H:i'],
            'has_actual_break' => ['nullable', 'boolean'],
        ]);

        $slot->update([
            'actual_starts_at' => $data['actual_starts_at'],
            'actual_ends_at' => $data['actual_ends_at'],
            'actual_break_minutes' => $request->boolean('has_actual_break') ? 30 : 0,
            'completed_at' => now(),
        ]);

        return back()->with('status', 'Actual work time updated.');
    }

    public function approveOrder(Order $order)
    {
        $this->authorizeAdmin();

        if ($order->status === 'pending') {
            $order->update(['status' => 'approved', 'approved_at' => now()]);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'approved',
                'note' => 'Admin approved the order.',
            ]);
        }

        return back()->with('status', 'Order approved.');
    }

    public function rejectOrder(Order $order)
    {
        $this->authorizeAdmin();

        if ($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'cancelled',
                'note' => 'Admin rejected the order.',
            ]);
        }

        return back()->with('status', 'Order rejected.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function syncOrderItems(Order $order, array $items): void
    {
        $order->items()->delete();
        $total = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $total += (float) $product->price * $item['quantity'];

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
            ]);
        }

        $order->update(['total' => $total]);
    }

    private function counts(): array
    {
        return [
            'pending' => Order::where('status', 'pending')->count(),
            'approved' => Order::where('status', 'approved')->count(),
            'cooking' => Order::where('status', 'cooking')->count(),
            'completed' => Order::whereDate('updated_at', today())->where('status', 'completed')->count(),
            'menuItems' => Product::where('is_active', true)->count(),
            'staffToday' => EmployeeSlot::whereDate('slot_date', today())->where('is_off', false)->count(),
        ];
    }
}
