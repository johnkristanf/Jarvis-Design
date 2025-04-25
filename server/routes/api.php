<?php

use App\Http\Controllers\DesignsController;
use App\Http\Controllers\MaterialsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/get/pre_made/designs/{sort?}/{categories?}', [DesignsController::class, 'getPreMadeDesigns']);

Route::get('/get/all/designs', [DesignsController::class, 'getAllDesigns']);
Route::get('/get/all/colors', [DesignsController::class, 'getAllColors']);
Route::get('/get/all/sizes', [DesignsController::class, 'getAllSizes']);


Route::middleware(['auth:sanctum'])->group(function () {

    // PROTECTED USER ROUTE
    Route::get('/user/data', [UserController::class, 'user']);


    // PROTECTED DESIGNS ROUTE
    Route::get('/get/made/designs', [DesignsController::class, 'getUploadedDesigns']);
    Route::get('/uploaded/designs', [DesignsController::class, 'getUploadedDesigns']);
    Route::get('/get/design/categories', [DesignsController::class, 'getDesignCategories']);

    Route::post('/upload/design', [DesignsController::class, 'uploadDesign']);
    Route::post('/add/pre_made/design', [DesignsController::class, 'addPreMadeDesigns']);
    Route::post('/attach/design/material', [DesignsController::class, 'attachDesignMaterial']);

    Route::put('/update/uploaded/design', [DesignsController::class, 'updateUploadedDesigns']);


    // PAYMONGO PAYMENT ROUTE
    Route::post('/paymongo/create-qr-source', [PaymentController::class, 'createQrPhSource']);


    // PROTECTED ORDERS ROUTE 
    Route::get('/get/orders', [PaymentController::class, 'getAllOrders']);
    Route::get('/get/order/status', [PaymentController::class, 'getAllOrderStatus']);
    Route::put('/update/order/status', [PaymentController::class, 'updateOrderStatus']);

    Route::get('/get/order/notifications', [PaymentController::class, 'getAllOrderNotifications']);


    // PROTECTED NOTIFICATION ROUTE
    Route::put('/notification/read', [PaymentController::class, 'updateNotificationAsRead']);
    Route::put('/all/notification/read', [PaymentController::class, 'updateNotificationAsReadAll']);


    // PROTECTED MATERIALS ROUTE
    Route::post('/add/material', [MaterialsController::class, 'store']);
    Route::get('/get/material/categories', [MaterialsController::class, 'getMaterialCategory']);
    Route::get('/get/materials', [MaterialsController::class, 'get']);
    Route::get('/get/grouped/materials', [MaterialsController::class, 'getGroupedMaterials']);

});

// TEST AUTOMATED DEDUCTION UPON ORDER
Route::get('/test/order', [PaymentController::class, 'testOrder']);


// PAYMONGO PAYMENT ROUTE
Route::get('/trigger-curl', [PaymentController::class, 'triggerCreateTestPaymongoResource']);
Route::post('/paymongo/webhook', [PaymentController::class, 'handleWebhook']);
