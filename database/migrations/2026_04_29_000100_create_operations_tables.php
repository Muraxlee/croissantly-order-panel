<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('staff_code')->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('kitchen_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('station')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('cooking_instructions')->nullable();
            $table->text('packing_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->decimal('low_stock_threshold', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'cooking', 'packed', 'completed', 'cancelled'])->default('pending')->index();
            $table->dateTime('required_at')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('cooking_started_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->text('cooking_instructions')->nullable();
            $table->text('packing_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->boolean('is_off')->default(false);
            $table->boolean('has_break')->default(false);
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'slot_date']);
        });

        Schema::create('employee_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_slot_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('minutes')->default(30);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('employee_breaks');
        Schema::dropIfExists('employee_slots');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('products');
        Schema::dropIfExists('kitchen_profiles');
        Schema::dropIfExists('employee_profiles');
        Schema::dropIfExists('client_profiles');
    }
};
