<?php

namespace Tests\Feature;

use App\Models\EmployeeSlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\StaffPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_dashboards_redirect_to_the_right_surface(): void
    {
        foreach ([
            'admin' => '/admin',
            'client' => '/client/place-order',
            'employee' => '/employee',
            'kitchen' => '/kitchen',
        ] as $role => $path) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/dashboard')->assertRedirect($path);
        }
    }

    public function test_admin_can_create_client_employee_and_kitchen_accounts(): void
    {
        $admin = $this->user('admin');

        foreach (['client', 'employee', 'kitchen'] as $role) {
            $this->actingAs($admin)->post(route('admin.accounts.store'), [
                'name' => ucfirst($role).' User',
                'username' => $role.'-new',
                'password' => 'secret123',
                'role' => $role,
                'email' => $role.'@example.test',
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('users', ['username' => $role.'-new', 'role' => $role]);
        }
    }

    public function test_client_can_edit_until_kitchen_starts_cooking(): void
    {
        $client = $this->user('client');
        $kitchen = $this->user('kitchen');
        $product = Product::create(['name' => 'Classic Croissant', 'price' => 2.50, 'cooking_instructions' => 'Bake gently.']);

        $this->actingAs($client)->post(route('client.orders.store'), [
            'items' => [$product->id => 4],
            'required_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $order = Order::first();

        $this->actingAs($client)->put(route('client.orders.update', $order), [
            'items' => [$product->id => 6],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'quantity' => 6]);

        $order->update(['status' => 'approved']);
        $this->actingAs($kitchen)->post(route('kitchen.orders.start', $order))->assertSessionHasNoErrors();

        $this->actingAs($client)->put(route('client.orders.update', $order->fresh()), [
            'items' => [$product->id => 9],
        ])->assertSessionHasErrors();

        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'quantity' => 6]);
        $this->assertNotNull($order->fresh()->locked_at);
    }

    public function test_admin_can_create_order_for_existing_client_account(): void
    {
        $admin = $this->user('admin');
        $client = $this->user('client');
        $product = Product::create(['name' => 'Classic Croissant', 'price' => 2.50]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Customer account')
            ->assertSee($client->name);

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'client_id' => $client->id,
                'required_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'notes' => 'Counter staff entered this order.',
                'items' => [$product->id => 2],
            ])
            ->assertSessionHasNoErrors();

        $order = Order::where('client_id', $client->id)->first();

        $this->assertNotNull($order);
        $this->assertSame($client->name, $order->customer_name);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Classic Croissant',
            'quantity' => 2,
        ]);

        $this->actingAs($client)
            ->get(route('client.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Classic Croissant');
    }

    public function test_kitchen_dashboard_groups_items_by_product_and_client(): void
    {
        $client = $this->user('client');
        $kitchen = $this->user('kitchen');
        $product = Product::create([
            'name' => 'Almond Croissant',
            'price' => 4,
            'cooking_instructions' => 'Finish with almond cream.',
            'packing_instructions' => 'Use tall pastry box.',
        ]);

        $order = Order::create(['order_number' => 'CR-TEST', 'client_id' => $client->id, 'status' => 'approved']);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 12,
            'unit_price' => $product->price,
            'cooking_instructions' => $product->cooking_instructions,
            'packing_instructions' => $product->packing_instructions,
        ]);

        $this->actingAs($kitchen)
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('Almond Croissant')
            ->assertSee('12')
            ->assertSee($client->name);
    }

    public function test_admin_and_kitchen_can_print_delivery_docket_without_prices_vat_or_extra_notes(): void
    {
        $client = $this->user('client');
        $admin = $this->user('admin');
        $kitchen = $this->user('kitchen');
        DB::table('client_profiles')
            ->where('user_id', $client->id)
            ->update(['business_name' => 'Corner Cafe Ltd', 'phone' => '+353 1 555 0101']);
        $product = Product::create([
            'name' => 'Raspberry Danish',
            'price' => 7.45,
            'cooking_instructions' => 'Keep chilled.',
            'packing_instructions' => 'Pack flat.',
        ]);

        $order = Order::create([
            'order_number' => 'CR-DOCKET',
            'client_id' => $client->id,
            'customer_name' => 'Corner Cafe',
            'customer_phone' => '07000 111222',
            'customer_address' => '10 High Street',
            'status' => 'packed',
            'notes' => 'Leave with front desk.',
            'required_at' => now()->addDay(),
            'total' => 14.90,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => $product->price,
            'cooking_instructions' => $product->cooking_instructions,
            'packing_instructions' => $product->packing_instructions,
        ]);

        foreach ([$admin, $kitchen] as $user) {
            $this->actingAs($user)
                ->get(route('orders.docket', $order))
                ->assertOk()
                ->assertSee('<title>CR-DOCKET</title>', false)
                ->assertSee('CR-DOCKET')
                ->assertSee('CROISSANTLY BAKERY')
                ->assertSee('105 Patrick St')
                ->assertSee('+353 89 438 2027')
                ->assertSee('croissantlybakery.ie')
                ->assertSee('Corner Cafe')
                ->assertSee('Corner Cafe Ltd')
                ->assertDontSee('Customer details')
                ->assertSee('Customer name')
                ->assertSee('Business name')
                ->assertSee('Phone')
                ->assertSee('Email')
                ->assertSee('07000 111222')
                ->assertSee($client->email)
                ->assertSee('Order date')
                ->assertSee($order->created_at->format('d M Y, H:i'))
                ->assertSee('Delivery date')
                ->assertSee('2')
                ->assertSee('Total')
                ->assertSee('2 pcs')
                ->assertSee('Raspberry Danish')
                ->assertSee('Checked by:')
                ->assertSee('Delivered by:')
                ->assertSee('Received by:')
                ->assertDontSee('Items inside')
                ->assertDontSee('Delivery docket')
                ->assertDontSee('Packed')
                ->assertDontSee('Order notes')
                ->assertDontSee('No notes')
                ->assertDontSee('Kitchen / packing note')
                ->assertDontSee('Pack flat.')
                ->assertDontSee('Keep chilled.')
                ->assertDontSee('Leave with front desk.')
                ->assertDontSee('&pound;', false)
                ->assertDontSee('£')
                ->assertDontSee('VAT')
                ->assertDontSee('Order total')
                ->assertDontSee('Line total')
                ->assertDontSee('7.45')
                ->assertDontSee('14.90');
        }
    }

    public function test_client_and_employee_cannot_open_delivery_docket(): void
    {
        $client = $this->user('client');
        $employee = $this->user('employee', 'eva');
        $order = Order::create(['order_number' => 'CR-PRIVATE-DOCKET', 'client_id' => $client->id, 'status' => 'approved']);

        $this->actingAs($client)
            ->get(route('orders.docket', $order))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('orders.docket', $order))
            ->assertForbidden();
    }

    public function test_kitchen_can_approve_pending_orders(): void
    {
        $client = $this->user('client');
        $kitchen = $this->user('kitchen');
        $product = Product::create(['name' => 'Classic Croissant', 'price' => 2.50]);
        $order = Order::create(['order_number' => 'CR-PENDING', 'client_id' => $client->id, 'status' => 'pending']);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => $product->price,
        ]);

        $this->actingAs($kitchen)
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('CR-PENDING')
            ->assertSee('3x Classic Croissant')
            ->assertSee('Products: 3x Classic Croissant')
            ->assertSee('Docket')
            ->assertSee('Approve');

        $this->actingAs($kitchen)
            ->post(route('kitchen.orders.approve', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $kitchen->id,
            'status' => 'approved',
            'note' => 'Kitchen approved the order.',
        ]);
    }

    public function test_admin_and_kitchen_can_reject_pending_orders_with_history(): void
    {
        $client = $this->user('client');
        $admin = $this->user('admin');
        $kitchen = $this->user('kitchen');
        $adminOrder = Order::create(['order_number' => 'CR-ADMIN-REJECT', 'client_id' => $client->id, 'status' => 'pending']);
        $kitchenOrder = Order::create(['order_number' => 'CR-KITCHEN-REJECT', 'client_id' => $client->id, 'status' => 'pending']);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Docket')
            ->assertSee('Reject')
            ->assertSee('Rejected');

        $this->actingAs($admin)
            ->post(route('admin.orders.reject', $adminOrder))
            ->assertSessionHasNoErrors();

        $this->actingAs($kitchen)
            ->post(route('kitchen.orders.reject', $kitchenOrder))
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $adminOrder->fresh()->status);
        $this->assertSame('cancelled', $kitchenOrder->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $adminOrder->id,
            'user_id' => $admin->id,
            'status' => 'cancelled',
            'note' => 'Admin rejected the order.',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $kitchenOrder->id,
            'user_id' => $kitchen->id,
            'status' => 'cancelled',
            'note' => 'Kitchen rejected the order.',
        ]);
    }

    public function test_client_cannot_use_kitchen_approve_route(): void
    {
        $client = $this->user('client');
        $order = Order::create(['order_number' => 'CR-FORBID', 'client_id' => $client->id, 'status' => 'pending']);

        $this->actingAs($client)
            ->post(route('kitchen.orders.approve', $order))
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_employee_sees_own_slots_and_admin_updates_actual_time(): void
    {
        $vanessa = $this->user('employee', 'vanessa');
        $eva = $this->user('employee', 'eva');
        $periodStart = StaffPeriod::currentStart();

        EmployeeSlot::create([
            'employee_id' => $vanessa->id,
            'slot_date' => $periodStart->copy()->addDay(),
            'starts_at' => '08:00',
            'ends_at' => '14:30',
            'has_break' => true,
            'break_minutes' => 30,
        ]);
        EmployeeSlot::create([
            'employee_id' => $eva->id,
            'slot_date' => $periodStart->copy()->addDay(),
            'starts_at' => '04:00',
            'ends_at' => '12:00',
        ]);

        $slot = EmployeeSlot::where('employee_id', $vanessa->id)->first();
        $this->assertSame(6.0, $slot->hours());

        $this->actingAs($vanessa)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Vanessa timesheet')
            ->assertSee('08:00-14:30')
            ->assertDontSee('04:00');

        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->patch(route('admin.slots.actual-time', $slot), [
                'actual_starts_at' => '08:15',
                'actual_ends_at' => '14:45',
                'has_actual_break' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(6.0, $slot->fresh()->actualHours());
    }

    public function test_admin_staff_calendar_uses_monday_to_sunday_week_periods(): void
    {
        $admin = $this->user('admin');
        $employee = $this->user('employee', 'eva');
        $periodStart = StaffPeriod::currentStart();
        $periodEnd = StaffPeriod::endFor($periodStart);

        $this->assertSame(1, $periodStart->dayOfWeek);
        $this->assertSame(0, $periodEnd->dayOfWeek);

        EmployeeSlot::create([
            'employee_id' => $employee->id,
            'slot_date' => $periodStart->toDateString(),
            'starts_at' => '08:00',
            'ends_at' => '13:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.slots.index'))
            ->assertOk()
            ->assertSee($periodStart->format('j'))
            ->assertSee($periodEnd->format('j'))
            ->assertSee('Staff calendar')
            ->assertSee('Schedule staff shift')
            ->assertSee('Pending actual')
            ->assertSee('08:00-13:00');
    }

    public function test_create_plan_writes_only_the_selected_day(): void
    {
        $admin = $this->user('admin');
        $employee = $this->user('employee', 'josue');
        $slotDate = StaffPeriod::currentStart()->copy()->addDays(3);
        $periodStart = StaffPeriod::startFor($slotDate);
        $periodEnd = StaffPeriod::endFor($periodStart);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), [
                'employee_id' => $employee->id,
                'slot_date' => $slotDate->toDateString(),
                'starts_at' => '09:00',
                'ends_at' => '15:00',
                'has_break' => '1',
            ])
            ->assertRedirect(route('admin.slots.index', [
                'month' => $slotDate->format('Y-m'),
                'date' => $slotDate->toDateString(),
            ]));

        $this->assertSame(1, EmployeeSlot::where('employee_id', $employee->id)
            ->whereBetween('slot_date', [$periodStart, $periodEnd])
            ->count());

        $this->assertDatabaseHas('employee_slots', [
            'employee_id' => $employee->id,
            'slot_date' => $slotDate->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '15:00',
            'break_minutes' => 30,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.slots.index', ['date' => $slotDate->toDateString()]))
            ->assertOk()
            ->assertSee('09:00-15:00')
            ->assertSee('Save actual')
            ->assertSee('name="actual_starts_at"', false);
    }

    public function test_admin_timesheets_can_filter_by_start_end_date_and_employee(): void
    {
        $admin = $this->user('admin');
        $employee = $this->user('employee', 'eva');
        $slotDate = StaffPeriod::currentStart()->copy()->addWeek()->addDays(2);
        $rangeStart = $slotDate->copy()->subDay();
        $rangeEnd = $slotDate->copy()->addDay();

        DB::table('employee_profiles')
            ->where('user_id', $employee->id)
            ->update(['hourly_rate' => 12.50]);

        EmployeeSlot::create([
            'employee_id' => $employee->id,
            'slot_date' => $slotDate->toDateString(),
            'starts_at' => '08:30',
            'ends_at' => '13:30',
            'actual_starts_at' => '08:30',
            'actual_ends_at' => '13:30',
            'actual_break_minutes' => 30,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.timesheets.index', [
                'start_date' => $rangeStart->toDateString(),
                'end_date' => $rangeEnd->toDateString(),
                'employee_id' => $employee->id,
            ]))
            ->assertOk()
            ->assertSee('Start date')
            ->assertSee('End date')
            ->assertSee('value="'.$rangeStart->toDateString().'"', false)
            ->assertSee('value="'.$rangeEnd->toDateString().'"', false)
            ->assertSee($rangeStart->format('D d M'))
            ->assertSee($rangeEnd->format('D d M'))
            ->assertSee($slotDate->format('d M Y'))
            ->assertSee('08:30-13:30')
            ->assertSee('&pound;56.25', false);
    }

    public function test_admin_can_update_and_remove_menu_items(): void
    {
        $admin = $this->user('admin');
        $product = Product::create(['name' => 'Chocolate Croissant', 'price' => 3.50]);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'Chocolate Roll',
                'price' => 4.25,
                'description' => 'Updated item',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Chocolate Roll']);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    private function user(string $role, ?string $username = null): User
    {
        $name = $username ? ucfirst($username) : ucfirst($role).' User';

        $user = User::create([
            'name' => $name,
            'username' => $username ?? $role.'-'.str()->random(6),
            'email' => ($username ?? $role.str()->random(6)).'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        if ($role === 'employee') {
            DB::table('employee_profiles')->insert(['user_id' => $user->id, 'staff_code' => strtoupper($user->username), 'created_at' => now(), 'updated_at' => now()]);
        }

        if ($role === 'client') {
            DB::table('client_profiles')->insert(['user_id' => $user->id, 'contact_name' => $user->name, 'created_at' => now(), 'updated_at' => now()]);
        }

        if ($role === 'kitchen') {
            DB::table('kitchen_profiles')->insert(['user_id' => $user->id, 'station' => 'Test station', 'created_at' => now(), 'updated_at' => now()]);
        }

        return $user;
    }
}
