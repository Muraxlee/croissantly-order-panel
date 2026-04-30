<?php

namespace Database\Seeders;

use App\Models\EmployeeSlot;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            'admin' => \App\Models\User::updateOrCreate(
                ['username' => 'admin'],
                ['name' => 'Croissantly Admin', 'email' => 'admin@croissantly.local', 'role' => 'admin', 'is_active' => true, 'password' => Hash::make('admin123')]
            ),
            'client' => \App\Models\User::updateOrCreate(
                ['username' => 'client-cafe'],
                ['name' => 'Corner Cafe', 'email' => 'client@croissantly.local', 'role' => 'client', 'is_active' => true, 'password' => Hash::make('client123')]
            ),
            'kitchen' => \App\Models\User::updateOrCreate(
                ['username' => 'kitchen'],
                ['name' => 'Kitchen Team', 'email' => 'kitchen@croissantly.local', 'role' => 'kitchen', 'is_active' => true, 'password' => Hash::make('kitchen123')]
            ),
            'eva' => \App\Models\User::updateOrCreate(
                ['username' => 'eva'],
                ['name' => 'Eva', 'email' => 'eva@croissantly.local', 'role' => 'employee', 'is_active' => true, 'password' => Hash::make('eva123')]
            ),
            'josue' => \App\Models\User::updateOrCreate(
                ['username' => 'josue'],
                ['name' => 'Josue', 'email' => 'josue@croissantly.local', 'role' => 'employee', 'is_active' => true, 'password' => Hash::make('josue123')]
            ),
            'vanessa' => \App\Models\User::updateOrCreate(
                ['username' => 'vanessa'],
                ['name' => 'Vanessa', 'email' => 'vanessa@croissantly.local', 'role' => 'employee', 'is_active' => true, 'password' => Hash::make('vanessa123')]
            ),
        ];

        DB::table('client_profiles')->updateOrInsert(
            ['user_id' => $users['client']->id],
            ['business_name' => 'Corner Cafe', 'contact_name' => 'Corner Cafe', 'phone' => '07000 000000', 'updated_at' => now(), 'created_at' => now()]
        );

        foreach (['eva', 'josue', 'vanessa'] as $code) {
            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $users[$code]->id],
                ['staff_code' => strtoupper($code), 'phone' => null, 'hourly_rate' => 12.50, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        DB::table('kitchen_profiles')->updateOrInsert(
            ['user_id' => $users['kitchen']->id],
            ['station' => 'Main kitchen', 'updated_at' => now(), 'created_at' => now()]
        );

        collect([
            ['name' => 'Classic Croissant', 'price' => 2.80, 'description' => 'Butter croissant'],
            ['name' => 'Pain au Chocolat', 'price' => 3.10, 'description' => 'Chocolate pastry'],
            ['name' => 'Almond Croissant', 'price' => 3.90, 'description' => 'Filled almond croissant'],
            ['name' => 'Pistachio Roll', 'price' => 4.20, 'description' => 'Pistachio cream roll'],
        ])->each(fn ($product) => Product::updateOrCreate(
            ['name' => $product['name']],
            [...$product, 'cooking_instructions' => null, 'packing_instructions' => null, 'is_active' => true],
        ));

        Order::where('order_number', 'CR-DEMO-001')->delete();

        $weekStart = now()->startOfWeek();
        $slotRows = [
            ['eva', 0, '08:30', '13:30', false, false, 0],
            ['eva', 1, '08:30', '13:30', false, false, 0],
            ['eva', 2, '08:30', '13:30', false, false, 0],
            ['eva', 3, '08:30', '13:30', false, true, 30],
            ['eva', 4, null, null, true, false, 0],
            ['josue', 3, '04:00', '12:00', false, true, 30],
            ['josue', 4, '04:00', '12:00', false, true, 30],
            ['vanessa', 2, '07:00', '12:00', false, false, 0],
            ['vanessa', 3, '07:00', '12:00', false, false, 0],
            ['vanessa', 4, '08:00', '14:30', false, true, 30],
            ['vanessa', 5, '08:00', '14:30', false, true, 30],
        ];

        foreach ($slotRows as [$userKey, $dayOffset, $start, $end, $off, $hasBreak, $breakMinutes]) {
            EmployeeSlot::updateOrCreate(
                ['employee_id' => $users[$userKey]->id, 'slot_date' => $weekStart->copy()->addDays($dayOffset)->toDateString()],
                ['starts_at' => $start, 'ends_at' => $end, 'is_off' => $off, 'has_break' => $hasBreak, 'break_minutes' => $breakMinutes]
            );
        }

        foreach ([35, 34, 32, 29, 28, 27, 22, 21, 14, 13, 7, 6] as $daysAgo) {
            EmployeeSlot::updateOrCreate(
                ['employee_id' => $users['vanessa']->id, 'slot_date' => now()->subDays($daysAgo)->toDateString()],
                ['starts_at' => '07:30', 'ends_at' => $daysAgo % 2 === 0 ? '15:00' : '12:00', 'is_off' => false, 'has_break' => $daysAgo % 2 === 0, 'break_minutes' => $daysAgo % 2 === 0 ? 30 : 0]
            );
        }
    }
}
