<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $fillable = ['user_id', 'business_name', 'contact_name', 'phone', 'notes'];
}
