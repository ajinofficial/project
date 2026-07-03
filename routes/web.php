<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/vendor-dashboard', [VendorDashboardController::class, 'index'])->middleware('menu:vendor_dashboard')->name('vendor.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('menu:dashboard')->name('dashboard');
    Route::get('/clients', [ClientController::class, 'index'])->middleware('menu:clients')->name('clients.index');
    Route::get('/setup', [SetupController::class, 'index'])->middleware('menu:setup')->name('setup.index');
    Route::put('/setup', [SetupController::class, 'update'])->middleware('menu:setup')->name('setup.update');
    Route::get('/role-permissions', [RolePermissionController::class, 'index'])->middleware('menu:role_permissions')->name('role-permissions.index');
    Route::put('/role-permissions', [RolePermissionController::class, 'update'])->middleware('menu:role_permissions')->name('role-permissions.update');
    Route::get('/users', [UserController::class, 'index'])->middleware('menu:users')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('menu:users')->name('users.store');
    Route::post('/users/{user}/delete', [UserController::class, 'destroy'])->middleware('menu:users')->name('users.delete');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('menu:users')->name('users.destroy');
    Route::post('/products/{product}/stock', [ProductController::class, 'adjustStock'])->middleware('menu:inventory')->name('products.stock');
    Route::resource('products', ProductController::class)->except(['show'])->middleware('menu:inventory');
    Route::get('/suppliers', [OperationsController::class, 'suppliers'])->middleware('menu:suppliers')->name('suppliers.index');
    Route::post('/suppliers', [OperationsController::class, 'storeSupplier'])->middleware('menu:suppliers')->name('suppliers.store');
    Route::get('/customers', [OperationsController::class, 'customers'])->middleware('menu:customers')->name('customers.index');
    Route::post('/customers', [OperationsController::class, 'storeCustomer'])->middleware('menu:customers')->name('customers.store');
    Route::get('/purchases', [OperationsController::class, 'purchases'])->middleware('menu:purchases')->name('purchases.index');
    Route::post('/purchases', [OperationsController::class, 'storePurchase'])->middleware('menu:purchases')->name('purchases.store');
    Route::get('/sales', [OperationsController::class, 'sales'])->middleware('menu:billing')->name('sales.index');
    Route::post('/sales', [OperationsController::class, 'storeSale'])->middleware('menu:billing')->name('sales.store');
    Route::get('/returns', [OperationsController::class, 'returns'])->middleware('menu:returns')->name('returns.index');
    Route::post('/returns', [OperationsController::class, 'storeReturn'])->middleware('menu:returns')->name('returns.store');
    Route::get('/reports', [OperationsController::class, 'reports'])->middleware('menu:reports')->name('reports.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
