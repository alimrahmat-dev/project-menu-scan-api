<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\TableController as AdminTableController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Endpoint publik (dipakai pelanggan setelah scan QR)
    |--------------------------------------------------------------------------
    */
    Route::get('menu', [MenuController::class, 'index']);
    Route::get('menu/{menu:slug}', [MenuController::class, 'show']);
    Route::get('demo-table', [TableController::class, 'demo']);
    Route::get('tables/{qrToken}', [TableController::class, 'show']);

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
        Route::post('orders/{order:order_number}/pay', [OrderController::class, 'pay']);
        Route::post('orders/{order:order_number}/mock-pay', [OrderController::class, 'mockPay']);
        Route::post('orders/{order:order_number}/cancel', [OrderController::class, 'cancel']);
    });

    Route::get('orders/{order:order_number}', [OrderController::class, 'show']);

    Route::post('payments/midtrans/notification', [PaymentController::class, 'midtransNotification'])
        ->middleware('throttle:120,1');

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);

            Route::get('dashboard', DashboardController::class);

            Route::apiResource('categories', CategoryController::class)->except(['show']);
            Route::apiResource('menus', AdminMenuController::class)->except(['show']);

            Route::post('tables/{table}/regenerate', [AdminTableController::class, 'regenerate']);
            Route::apiResource('tables', AdminTableController::class)->except(['show']);

            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{order}', [AdminOrderController::class, 'show']);
            Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('orders/{order}/mark-paid', [AdminOrderController::class, 'markPaid']);

            Route::post('uploads', [UploadController::class, 'store']);
        });
    });
});
