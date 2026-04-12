<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\Admin\ActivationCodeController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\ClientExcelSearchController;
use App\Http\Controllers\Api\ClientFileCompareController;
use App\Http\Controllers\Api\SavedComparisonController;
use App\Http\Controllers\Api\Admin\MappingController;
use App\Http\Controllers\Api\Admin\SupplierController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'is_active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/password', [PasswordController::class, 'update']);

    Route::middleware('role:client')->group(function () {
        Route::post('/activate', [ActivationController::class, 'activate']);
    });

    Route::middleware(['role:client', 'subscription_valid'])->group(function () {
        Route::get('/search', [SearchController::class, 'index']);
        Route::post('/search/from-excel', ClientExcelSearchController::class);
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy']);

        Route::post('/compare-files', ClientFileCompareController::class);
        Route::get('/saved-comparisons', [SavedComparisonController::class, 'index']);
        Route::post('/saved-comparisons', [SavedComparisonController::class, 'store']);
        Route::delete('/saved-comparisons/{saved_comparison}', [SavedComparisonController::class, 'destroy']);
    });

    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/users', [UserAdminController::class, 'index']);
        Route::post('/users', [UserAdminController::class, 'store']);
        Route::put('/users/{user}', [UserAdminController::class, 'update']);
        Route::delete('/users/{user}', [UserAdminController::class, 'destroy']);

        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

        Route::post('/uploads', [UploadController::class, 'store']);
        Route::get('/uploads/{upload}', [UploadController::class, 'show']);

        Route::get('/mapping', [MappingController::class, 'index']);
        Route::get('/mapping/products/search', [MappingController::class, 'productSearch']);
        Route::post('/mapping/{unmatched_product}/link', [MappingController::class, 'link']);
        Route::post('/mapping/{unmatched_product}/create', [MappingController::class, 'create']);
        Route::post('/mapping/{unmatched_product}/ignore', [MappingController::class, 'ignore']);
        Route::post('/mapping/bulk-link', [MappingController::class, 'bulkLink']);
        Route::post('/mapping/bulk-ignore', [MappingController::class, 'bulkIgnore']);

        Route::get('/activation-codes', [ActivationCodeController::class, 'index']);
        Route::post('/activation-codes', [ActivationCodeController::class, 'store']);

        Route::get('/analytics/most-searched', [AnalyticsController::class, 'mostSearched']);
        Route::get('/analytics/search-by-user', [AnalyticsController::class, 'searchVolumeByUser']);
        Route::get('/analytics/popular-products', [AnalyticsController::class, 'popularProducts']);
        Route::get('/analytics/searches-no-results', [AnalyticsController::class, 'searchesNoResults']);
        Route::get('/analytics/searches-no-offers', [AnalyticsController::class, 'searchesNoOffers']);
        Route::get('/analytics/comparisons-by-user', [AnalyticsController::class, 'comparisonsByUser']);
    });
});
