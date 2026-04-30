<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\OrderDocketController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders.index');
    Route::post('/admin/orders', [AdminController::class, 'storeOrder'])->name('admin.orders.store');
    Route::get('/admin/accounts', [AdminController::class, 'accounts'])->name('admin.accounts.index');
    Route::get('/admin/slots', [AdminController::class, 'slots'])->name('admin.slots.index');
    Route::get('/admin/employees/{employee}/actual-time', [AdminController::class, 'employeeActualTime'])->name('admin.employees.actual-time');
    Route::patch('/admin/employees/{employee}/profile', [AdminController::class, 'updateEmployeeProfile'])->name('admin.employees.profile');
    Route::get('/admin/timesheets', [AdminController::class, 'timesheets'])->name('admin.timesheets.index');
    Route::get('/admin/menu', [AdminController::class, 'menu'])->name('admin.menu.index');
    Route::post('/admin/accounts', [AdminController::class, 'storeAccount'])->name('admin.accounts.store');
    Route::put('/admin/accounts/{user}', [AdminController::class, 'updateAccount'])->name('admin.accounts.update');
    Route::delete('/admin/accounts/{user}', [AdminController::class, 'destroyAccount'])->name('admin.accounts.destroy');
    Route::post('/admin/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::put('/admin/products/{product}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [AdminController::class, 'destroyProduct'])->name('admin.products.destroy');
    Route::post('/admin/slots', [AdminController::class, 'storeSlot'])->name('admin.slots.store');
    Route::patch('/admin/slots/{slot}/actual-time', [AdminController::class, 'updateSlotActualTime'])->name('admin.slots.actual-time');
    Route::post('/admin/orders/{order}/approve', [AdminController::class, 'approveOrder'])->name('admin.orders.approve');
    Route::post('/admin/orders/{order}/reject', [AdminController::class, 'rejectOrder'])->name('admin.orders.reject');
    Route::get('/orders/{order}/docket', OrderDocketController::class)->name('orders.docket');

    Route::get('/client', [ClientController::class, 'index'])->name('client.dashboard');
    Route::get('/client/place-order', [ClientController::class, 'place'])->name('client.place');
    Route::get('/client/orders', [ClientController::class, 'orders'])->name('client.orders.index');
    Route::post('/client/orders', [ClientController::class, 'storeOrder'])->name('client.orders.store');
    Route::put('/client/orders/{order}', [ClientController::class, 'updateOrder'])->name('client.orders.update');

    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.dashboard');
    Route::post('/kitchen/orders/start-all', [KitchenController::class, 'startAll'])->name('kitchen.orders.start-all');
    Route::post('/kitchen/orders/{order}/approve', [KitchenController::class, 'approve'])->name('kitchen.orders.approve');
    Route::post('/kitchen/orders/{order}/reject', [KitchenController::class, 'reject'])->name('kitchen.orders.reject');
    Route::post('/kitchen/orders/{order}/start', [KitchenController::class, 'start'])->name('kitchen.orders.start');
    Route::post('/kitchen/orders/{order}/packed', [KitchenController::class, 'markPacked'])->name('kitchen.orders.packed');
    Route::post('/kitchen/orders/{order}/complete', [KitchenController::class, 'complete'])->name('kitchen.orders.complete');

    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.dashboard');
});
