<?php

use App\Http\Controllers\DesignsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/get/all/designs', [DesignsController::class, 'getAllDesigns']);
Route::get('/get/all/colors', [DesignsController::class, 'getAllColors']);
Route::get('/get/all/sizes', [DesignsController::class, 'getAllSizes']);


Route::get('/trigger-curl', [PaymentController::class, 'triggerCreateTestPaymongoResource']);


Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user/data', [UserController::class, 'user']);

    Route::get('/uploaded/designs', [DesignsController::class, 'getUploadedDesigns']);
    Route::post('/upload/design', [DesignsController::class, 'uploadDesign']);

    Route::put('/update/uploaded/design', [DesignsController::class, 'updateUploadedDesigns']);
    Route::post('/paymongo/create-qr-source', [PaymentController::class, 'createQrPhSource']);
    

    Route::get('/get/orders', [PaymentController::class, 'getAllOrders']);
    Route::get('/get/order/status', [PaymentController::class, 'getAllOrderStatus']);
    Route::put('/update/order/status', [PaymentController::class, 'updateOrderStatus']);

    Route::get('/get/order/notifications', [PaymentController::class, 'getAllOrderNotifications']);
    
    Route::put('/notification/read', [PaymentController::class, 'updateNotificationAsRead']);
    Route::put('/all/notification/read', [PaymentController::class, 'updateNotificationAsReadAll']);

});


Route::post('/paymongo/webhook', [PaymentController::class, 'handleWebhook']);
