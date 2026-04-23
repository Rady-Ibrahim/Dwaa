<?php

use App\Http\Controllers\Web\Admin\DashboardActivationCodesController;
use App\Http\Controllers\Web\Admin\DashboardMappingWebController;
use App\Http\Controllers\Web\Admin\DashboardSuppliersController;
use App\Http\Controllers\Web\Admin\DashboardAnalyticsController;
use App\Http\Controllers\Web\Admin\DashboardUploadsController;
use App\Http\Controllers\Web\Admin\DashboardUsersController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\WebAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('client.login');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::get('/admin/login', [WebAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [WebAuthController::class, 'login'])->name('admin.login.submit');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    Route::middleware(['role:admin'])->prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/products', [DashboardController::class, 'products'])->name('dashboard.products');
        Route::get('/suppliers', [DashboardController::class, 'suppliers'])->name('dashboard.suppliers');
        Route::get('/suppliers/{supplier}/edit', [DashboardSuppliersController::class, 'edit'])->name('dashboard.suppliers.edit');
        Route::post('/suppliers', [DashboardSuppliersController::class, 'store'])->name('dashboard.suppliers.store');
        Route::put('/suppliers/{supplier}', [DashboardSuppliersController::class, 'update'])->name('dashboard.suppliers.update');
        Route::delete('/suppliers/{supplier}', [DashboardSuppliersController::class, 'destroy'])->name('dashboard.suppliers.destroy');

        Route::get('/users', [DashboardController::class, 'users'])->name('dashboard.users');
        Route::post('/users', [DashboardUsersController::class, 'store'])->name('dashboard.users.store');
        Route::put('/users/{user}', [DashboardUsersController::class, 'update'])->name('dashboard.users.update');
        Route::get('/users/{user}/password', [DashboardUsersController::class, 'editPassword'])->name('dashboard.users.password.edit');
        Route::put('/users/{user}/password', [DashboardUsersController::class, 'updatePassword'])->name('dashboard.users.password.update');
        Route::delete('/users/{user}', [DashboardUsersController::class, 'destroy'])->name('dashboard.users.destroy');

        Route::get('/activation-codes', [DashboardActivationCodesController::class, 'index'])->name('dashboard.activation-codes');
        Route::post('/activation-codes', [DashboardActivationCodesController::class, 'store'])->name('dashboard.activation-codes.store');
        Route::put('/activation-codes/{activationCode}', [DashboardActivationCodesController::class, 'update'])->name('dashboard.activation-codes.update');

        Route::get('/uploads', [DashboardController::class, 'uploads'])->name('dashboard.uploads');
        Route::post('/uploads', [DashboardUploadsController::class, 'store'])->name('dashboard.uploads.store');

        Route::get('/analytics', DashboardAnalyticsController::class)->name('dashboard.analytics');

        Route::get('/mapping', [DashboardController::class, 'mapping'])->name('dashboard.mapping');
        Route::get('/mapping/products/search', [DashboardMappingWebController::class, 'searchProducts'])->name('dashboard.mapping.search');
        Route::post('/mapping/{unmatched_product}/link', [DashboardMappingWebController::class, 'link'])->name('dashboard.mapping.link');
        Route::post('/mapping/{unmatched_product}/create', [DashboardMappingWebController::class, 'createProduct'])->name('dashboard.mapping.create');
        Route::post('/mapping/{unmatched_product}/ignore', [DashboardMappingWebController::class, 'ignore'])->name('dashboard.mapping.ignore');

        Route::get('/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
        Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('dashboard.settings.update');
    });
});
// Client Routes
Route::view('/login', 'client.login')->name('client.login');
Route::redirect('/client/login', '/login');

Route::prefix('client')->group(function () {
    Route::redirect('/', '/client/search');
    Route::view('/search', 'client.search')->name('client.search');
    Route::view('/compare', 'client.compare')->name('client.compare');
    Route::view('/compare-platform', 'client.compare-platform')->name('client.compare-platform');
    Route::view('/favorites', 'client.favorites')->name('client.favorites');
    Route::view('/saved-comparisons', 'client.saved-comparisons')->name('client.saved-comparisons');
    Route::get('/saved-comparisons/{savedComparison}', function ($savedComparison) {
        return view('client.saved-comparison-show', ['savedComparisonId' => $savedComparison]);
    })->name('client.saved-comparisons.show');
Route::view('/products', 'client.products')->name('client.products');
    Route::view('/password', 'client.password')->name('client.password');
    Route::view('/activate', 'client.activate')->name('client.activate');

    Route::post('/logout', function (Request $request) {
        return redirect()->route('client.login')->withCookie(cookie()->forget('client_token'));
    })->name('client.logout');
});
