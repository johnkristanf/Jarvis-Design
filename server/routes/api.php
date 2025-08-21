<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DesignsController;
use App\Http\Controllers\MaterialsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// THIS DESIGN IS FOR THE CUSTOMER SIDE
Route::get('/get/pre_made/designs/{sort?}/{categories?}', [DesignsController::class, 'getPreMadeDesigns']);

Route::get('/get/all/colors', [DesignsController::class, 'getAllColors']);
Route::get('/get/all/sizes', [DesignsController::class, 'getAllSizes']);
Route::get('/get/design/categories', [DesignsController::class, 'getDesignCategories']);
Route::get('/get/fabric/types', [DesignsController::class, 'getFabricTypes']);

// BUSINESS PRODUCT DESIGNS
Route::get('/get/bussiness_designs/{product_id}', [DesignsController::class, 'getProductBusinessDesign']);

Route::middleware(['auth:sanctum'])->group(function () {

    // PROTECTED USER ROUTE
    Route::get('/user/data', [UserController::class, 'user']);
    Route::post('/update/profile', [UserController::class, 'update']);

    // PROTECTED DESIGNS ROUTE
    Route::get('/uploaded/designs', [DesignsController::class, 'getUploadedDesigns']);

    Route::get('/uploaded/{designID}/design', [DesignsController::class, 'getUploadedDesignByID']);

    Route::post('/upload/design', [DesignsController::class, 'uploadDesign']);
    Route::post('/add/pre_made/design', [DesignsController::class, 'addPreMadeDesigns']);

    Route::post('/attach/design/material', [DesignsController::class, 'attachDesignMaterial']);
    Route::put('/update/uploaded/design', [DesignsController::class, 'updateUploadedDesigns']);

    // PROTECTED PRODUCT ROUTE
    Route::post('/add/product', [DesignsController::class, 'addProduct']);
    Route::post('/add/product/design', [DesignsController::class, 'addProductDesign']);
    Route::get('/get/all/products', [DesignsController::class, 'getAllProducts']);

    // THIS DESIGNS IS FOR THE ADMIN SIDE THAT SEE ALL DESIGN WITHOUT FILTER
    Route::get('/get/all/designs', [DesignsController::class, 'getAllDesigns']);

    // PAYMONGO PAYMENT ROUTE
    Route::post('/paymongo/create-qr-source', [PaymentController::class, 'createQrPhSource']);

    // PROTECTED ORDERS ROUTE
    Route::get('/get/orders', [PaymentController::class, 'getAllOrders']);
    Route::get('/get/order/status', [PaymentController::class, 'getAllOrderStatus']);
    Route::put('/update/order/status', [PaymentController::class, 'updateOrderStatus']);
    Route::post('/set/order/date', [PaymentController::class, 'setOrderDate']);

    Route::get('/get/order/logs', [PaymentController::class, 'getOrderLogs']);
    Route::get('/get/order/notifications', [PaymentController::class, 'getAllOrderNotifications']);

    // PROTECTED NOTIFICATION ROUTE
    Route::put('/notification/read', [PaymentController::class, 'updateNotificationAsRead']);
    Route::put('/all/notification/read', [PaymentController::class, 'updateNotificationAsReadAll']);

    // PROTECTED MATERIALS ROUTE
    Route::post('/add/material', [MaterialsController::class, 'store']);
    Route::post('/edit/material', [MaterialsController::class, 'edit']);
    Route::get('/get/material/categories', [MaterialsController::class, 'getMaterialCategory']);
    Route::get('/get/materials', [MaterialsController::class, 'get']);
    Route::get('/get/grouped/materials', [MaterialsController::class, 'getGroupedMaterials']);

    // PROTECTED ORDER ROUTE
    // Route::post('/place/order', [PaymentController::class, 'OLDplaceOrder']);
    Route::post('/place/order', [PaymentController::class, 'placeOrder']);

    // PROTECTED MESSAGE ROUTE
    Route::post('/send/chat', [ChatController::class, 'sendChat']);
    Route::get('/get/convo/{userID}', [ChatController::class, 'getConversationByUserID']);
    Route::get('/get/all/convo', [ChatController::class, 'getAllConversation']);

});

// TEST AUTOMATED DEDUCTION UPON ORDER
Route::get('/test/order', [PaymentController::class, 'testOrder']);

// PAYMONGO PAYMENT ROUTE
Route::get('/trigger-curl', [PaymentController::class, 'triggerCreateTestPaymongoResource']);
Route::post('/paymongo/webhook', [PaymentController::class, 'handleWebhook']);
