<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\RawMaterialController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Branch management
    Route::apiResource('branches', BranchController::class);

    // Employee management
    Route::apiResource('employees', EmployeeController::class);

    // Inventory Management
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('raw-materials', RawMaterialController::class);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);

    // Recipe & HPP Management
    Route::apiResource('recipes', RecipeController::class);
    Route::post('recipes/{id}/calculate-cost', [RecipeController::class, 'calculateCost']);

    // Stock Management
    Route::apiResource('stocks', StockController::class);
    Route::post('stocks/adjustment', [StockController::class, 'adjustment']);
    Route::post('stocks/transfer', [StockController::class, 'transfer']);
    Route::get('stocks/low-stock', [StockController::class, 'lowStock']);

    // Menu Management
    Route::apiResource('menu-categories', MenuCategoryController::class);
    Route::apiResource('menus', MenuController::class);

    // POS - Order Management
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/add-item', [OrderController::class, 'addItem']);
    Route::post('orders/{id}/update-status', [OrderController::class, 'updateStatus']);
    Route::post('orders/{id}/payment', [OrderController::class, 'processPayment']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('sales', [ReportController::class, 'sales']);
        Route::get('profit', [ReportController::class, 'profit']);
        Route::get('inventory', [ReportController::class, 'inventory']);
        Route::get('low-stock', [ReportController::class, 'lowStock']);
        Route::get('financial', [ReportController::class, 'financial']);
    });
});
