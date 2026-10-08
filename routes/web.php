<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsumableProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalonSettingController;
use App\Http\Controllers\SalonVisitController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceVariantController;
use App\Http\Controllers\StaffDebtController;
use App\Http\Controllers\StaffRepaymentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBookingController::class, 'index'])->name('home');
Route::get('/reserver', [PublicBookingController::class, 'create'])->name('booking.create');
Route::post('/reserver', [PublicBookingController::class, 'store'])->middleware('throttle:bookings')->name('booking.store');
Route::get('/reservation-recue', [PublicBookingController::class, 'received'])->name('booking.received');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/parametres', [SalonSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/parametres', [SalonSettingController::class, 'update'])->name('settings.update');
    Route::view('/pos', 'pos.index')->middleware('can:create,App\Models\SalonVisit')->name('pos.index');
    Route::get('/visits', [SalonVisitController::class, 'index'])->name('visits.index');
    Route::get('/visits/{visit}', [SalonVisitController::class, 'show'])->name('visits.show');
    Route::get('/visits/{visit}/receipt', [SalonVisitController::class, 'receipt'])->name('visits.receipt');
    Route::get('/visits/{visit}/receipt/80mm', [SalonVisitController::class, 'thermalReceipt'])->name('visits.receipt.thermal');
    Route::post('/visits/{visit}/payments', [VisitPaymentController::class, 'store'])->name('visits.payments.store');
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('products.movements.index');
    Route::get('/products/{product}/movements/{type}/create', [StockMovementController::class, 'create'])->whereIn('type', ['supply', 'consume'])->name('products.movements.create');
    Route::post('/products/{product}/movements/{type}', [StockMovementController::class, 'store'])->whereIn('type', ['supply', 'consume'])->name('products.movements.store');
    Route::resource('reservations', ReservationController::class)->only(['index', 'show', 'update']);
    Route::resource('products', ConsumableProductController::class)->except('destroy');
    Route::resource('customers', CustomerController::class);
    Route::resource('employees', EmployeeController::class)->except('show');
    Route::resource('staff-debts', StaffDebtController::class)->parameters(['staff-debts' => 'debt'])->only(['index', 'create', 'store', 'show']);
    Route::post('/staff-debts/{debt}/repayments', [StaffRepaymentController::class, 'store'])->name('staff-debts.repayments.store');
    Route::resource('categories', ServiceCategoryController::class)->except('show');
    Route::resource('services', ServiceController::class);
    Route::resource('services.variants', ServiceVariantController::class)->except(['index', 'show'])->scoped();
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::resource('roles', RoleController::class)->except(['show', 'destroy']);
    Route::view('/account', 'auth.account')->name('account');
    Route::put('/account/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});
