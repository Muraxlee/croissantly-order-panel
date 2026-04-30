<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_slots', function (Blueprint $table) {
            $table->time('actual_starts_at')->nullable()->after('ends_at');
            $table->time('actual_ends_at')->nullable()->after('actual_starts_at');
            $table->unsignedSmallInteger('actual_break_minutes')->nullable()->after('actual_ends_at');
            $table->timestamp('completed_at')->nullable()->after('actual_break_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('employee_slots', function (Blueprint $table) {
            $table->dropColumn(['actual_starts_at', 'actual_ends_at', 'actual_break_minutes', 'completed_at']);
        });
    }
};
