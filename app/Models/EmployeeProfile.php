<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $fillable = ['user_id', 'staff_code', 'phone', 'hourly_rate'];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }
}
