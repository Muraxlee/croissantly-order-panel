<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'client' => redirect()->route('client.place'),
            'employee' => redirect()->route('employee.dashboard'),
            'kitchen' => redirect()->route('kitchen.dashboard'),
            default => abort(403),
        };
    }
}
