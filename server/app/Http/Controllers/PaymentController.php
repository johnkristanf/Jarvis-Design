<?php

namespace App\Http\Controllers;

use App\Events\PaymentSucessful;
use App\Jobs\SendOrderConfirmation;
use App\Models\Designs;
use App\Models\Materials;
use App\Models\Notifications;
use App\Models\OrderLogs;
use App\Models\Orders;
use App\Models\OrderType as ModelsOrderType;
use App\Models\UploadedDesign;
use App\OrderType;
use App\Service\PaymentService;
use App\Traits\HandleAttachments;
use App\Traits\OrderTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    use HandleAttachments, OrderTrait;

    protected $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService;
    }

    // THIS WEBHOOK HANDLES THE WHOLE PAYMENT PROCESS FROM CANCEL TO SUCCESS PAY
    // YOU MUST INCLUDE THE INSERTION OF ORDERS HERE IF PAYMENT WAY SUCCESSFULL

    public function handleWebhook(Request $request)
    {
        $event = $request->all();

        Log::info('Event Data: ', [
            'event' => $event['data'],
        ]);

        // Log::info('Meta Data: ', [
        //     'metadata' => $event['data']['attributes']['metadata']
        // ]);

        Log::info('Proccess Types: ', [
            'type' => $event['data']['attributes']['type'],
        ]);

        $eventType = $event['data']['attributes']['type'];

        if ($eventType === 'source.chargeable') {
            Log::info('SHESSHH NI GANAAA PARRR');
        }

        if ($eventType === 'payment.paid') {

            // HANDLE HERE THE GENERATION OF ORDER ID BASED ON THE FRONT END VUE SA FORMAT: ORD-12..xxxx

            $orderId = 'ORD-' . now()->timestamp . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $payment = $event['data'];
            $paymentAttributes = $payment['attributes'];

            // Check if the payment method was 'qr_ph'
            if ($paymentAttributes['payment_method'] === 'qr_ph') {
                $paymentId = $payment['id'];
                $amount = $paymentAttributes['amount'];
                $currency = $paymentAttributes['currency'];
                $status = $paymentAttributes['status'];

                Log::info('QR Ph Payment Received:', [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $status,
                ]);

                // GET THE:
                // 'design_id' => (string) $designID,
                // 'total_price' => (string) $totalPrice,
                // 'order_option' => (string) $orderOption,
                // 'order_type' => (string) $order_type,
                // 'quantity' => (string) $quantity,
                // 'color' => (string) $color,
                // 'size' => (string) $size,

                // INSIDE THE ATTACHED META DATA

                // THEN PROCEDD TO INSERT IT IN THE ORDER TABLE

                // Update your database based on the successful QR Ph payment
                // Find the associated order and update its status, record the
                // payment ID, amount, etc.
                // Example:
                // $order = Order::where('payment_reference', $someReference)->first();
                // if ($order) {
                //     $order->update(['payment_status' => 'paid', 'paymongo_payment_id' => $paymentId, 'total_amount_cents' => $amount]);
                // }

                return response()->json(['status' => 'ok'], 200);
            }
        }
        // Handle other webhook events if needed (e.g., payment.failed)

        return response()->json(['status' => 'ok'], 200);
    }

    public function createQrPhSource(Request $request)
    {
        try {
            $designID = $request->input('design_id');
            $totalPrice = $request->input('total_price');
            $orderOption = $request->input('order_option');
            $orderType = $request->input('order_type');

            $quantity = $request->input('quantity');
            $color = $request->input('color');
            $size = $request->input('size');

            $secretKey = config('services.paymongo.secret_key');
            $authHeader = 'Basic ' . base64_encode($secretKey . ':');

            // ADD META DATA IN THE requestPaymentIntent SERVICE TO ADD THE (COLORS, SIZES, ETC...)
            $paymentIntentID = $this->paymentService->requestPaymentIntent($designID, $totalPrice, $orderOption, $orderType, $quantity, $color, $size, $authHeader);

            $paymentMethodID = $this->paymentService->requestPaymentMethod($authHeader);

            $attachPaymentIntentResponseData = $this->paymentService->requestAttachPaymentIntentRequest($paymentIntentID, $paymentMethodID, $authHeader);

            if (isset($attachPaymentIntentResponseData)) {

                Log::info('paymentIntentResponseData: ', [
                    'actions' => $attachPaymentIntentResponseData['data']['attributes']['next_action'],
                ]);

                $action = $attachPaymentIntentResponseData['data']['attributes']['next_action'];
                $codeObj = $action['code'];
                $codeID = $codeObj['id'];
                $amount = $codeObj['amount'];
                $businessNameLabel = $codeObj['label'];
                $qrcodeSrc = $codeObj['image_url'];

                return response()->json([
                    'code_id' => $codeID,
                    'amount' => $amount,
                    'business_name' => $businessNameLabel,
                    'qrcode_img_src' => $qrcodeSrc,
                ], 200);
            } else {
                Log::error('Failed to generate QR Code: Incomplete response', [$attachPaymentIntentResponseData]);

                return response()->json(['error' => 'Failed to generate QR Code. Incomplete response from PayMongo.'], 500);
            }
        } catch (Exception $e) {
            Log::error('Error in Generating QR Code: ', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Failed to generate QR Code. An unexpected error occurred.'], 500);
        }
    }

    public function triggerCreateTestPaymongoResource()
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => 'Basic c2tfdGVzdF84Szc0NWYzek5ONEhUbXNZc1pBQUdrWjc6',
            'content-type' => 'application/json',

        ])->post('https://api.paymongo.com/v1/sources', [
            'data' => [
                'attributes' => [
                    'amount' => 10000,
                    'redirect' => [
                        'success' => 'http://localhost:3000/payment/success',
                        'failed' => 'http://localhost:3000/payment/failed',
                    ],
                    'type' => 'gcash',
                    'currency' => 'PHP',
                    'metadata' => [
                        'product_id' => '123',
                        'user' => 'Kristan',
                        'hell_miccc_hello_test' => 'hello miccc testtinggg...',
                    ],
                ],
            ],
        ]);

        // Log or return the response
        return response()->json($response->json());
    }

    public function getAllOrders(Request $request)
    {
        $limit = $request->get('limit', 10);
        $orders = $this->paymentService->allOrders($limit);

        return response()->json($orders, 200);
    }

    public function getAllOrderStatus()
    {
        $orders = $this->paymentService->allOrderStatus();

        return response()->json($orders, 200);
    }

    public function updateOrderStatus(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $updatedOrderID = $this->paymentService->updateStatus($validated['order_id'], $validated['status']);

        return response()->json([
            'msg' => 'Order Status Updated Successfully',
            'orderID' => $updatedOrderID,
        ], 200);
    }

    public function setOrderDate(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'status' => 'required|string',
            'action_date' => 'required|date',
        ]);

        Log::info('order date data: ', [
            'validated' => $validated,
        ]);

        $order = Orders::where('id', $validated['order_id'])->update([
            'delivery_date' => $validated['action_date'],
            'status' => $validated['status'],
        ]);


        // INSERT NOTIFICATION

        return response()->json([
            'message' => 'Order action date updated successfully.',
            'order' => $order,
        ]);
    }

    public function getAllOrderNotifications()
    {
        $orderNotifications = $this->paymentService->allOrdersNotifications();

        return response()->json($orderNotifications, 200);
    }

    public function updateNotificationAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'required|numeric',
        ]);

        $updatedNotificationID = $this->paymentService->updateNotification($validated['notification_id']);

        return response()->json([
            'msg' => 'Notification Read Update Successfully',
            'notifID' => $updatedNotificationID,
        ], 200);
    }

    public function updateNotificationAsReadAll()
    {
        $this->paymentService->updateAllNotificationsAsRead();

        return response()->json([
            'msg' => 'Notification Read All Successfully',
        ], 200);
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'color' => 'required|string',
            'phone_number' => 'required|string',
            'product_unit_price' => 'required|numeric',
            'product_id' => 'required|numeric',
            'address' => 'required|string',
            'design_type' => 'required|in:own-design,business-design,ai-generation',
            'order_option' => 'required|string',
            'total_quantity' => 'required|numeric|min:1',
            'total_price' => 'required|numeric|min:1',
            'fabric_type_id' => 'nullable|numeric',
            'solo_quantity' => 'nullable|numeric',
            'sizes' => 'nullable|array',
            'sizes.*' => 'nullable|numeric|min:0',
            'own_design_file' => 'nullable|file',
            'business_design_url' => 'nullable|string',
            'payment_attachment' => 'required|file',

        ]);

        Log::info('Order Data: ', [$validated]);

        // UPLOAD THE PAYMENT ATTACHMENT TO S3
        $attachmentURL = $this->uploadToS3(
            root: 'payment',
            sub: Auth::id(),
            file: $request->file('payment_attachment')
        );

        DB::transaction(function () use ($validated, $request, $attachmentURL, &$order) {
            // Step 1: Create order first, without the design URL yet
            $order = Orders::create([
                'order_number' => $this->generateOrderNumber(),
                'color' => $validated['color'],
                'product_unit_price' => $validated['product_unit_price'],
                'product_id' => $validated['product_id'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'design_type' => $validated['design_type'],
                'order_option' => $validated['order_option'],
                'total_quantity' => $validated['total_quantity'],
                'total_price' => $validated['total_price'],
                'solo_quantity' => $validated['solo_quantity'] ?? null,
                'business_design_url' => $validated['business_design_url'] ?? null,
                'attachment_url' => $attachmentURL,
                'user_id' => Auth::user()->id,
            ]);

            // Step 2: Handle own-design file upload (after Order is created)
            if ($request->hasFile('own_design_file')) {
                $attachmentURL = $this->uploadToS3(
                    root: 'orders',
                    sub: $order->id,
                    file: $request->file('own_design_file')
                );

                // Step 3: Update the order with the uploaded file's URL
                $order->update([
                    'own_design_url' => $attachmentURL,
                ]);
            }

            // Step 4: Handle sizes (many-to-many pivot with quantity)
            if (! empty($validated['sizes'])) {
                foreach ($validated['sizes'] as $sizeId => $qty) {
                    if ($qty > 0) {
                        $order->sizes()->attach($sizeId, ['quantity' => $qty]);
                    }
                }
            }

            // Step 5: Deduct total quantity ordered in materials table
            if (isset($validated['fabric_type_id']) && $validated['fabric_type_id']) {

                $fabric = Materials::findOrFail($validated['fabric_type_id']);

                $totalOrderedQuantity = (int) $validated['total_quantity'];
                $fabricUsedPerUnit = (float) $fabric->products()->pluck('fabric_quantity')->first();

                $totalQuantityDeduction = $totalOrderedQuantity * $fabricUsedPerUnit;

                Log::info('FABRIC DATA: ', [
                    'fabric_type_id' => $validated['fabric_type_id'],
                    'totalDeduction' => $totalQuantityDeduction,
                    'fabric' => $fabric,
                ]);

                if ($fabric->quantity >= $totalQuantityDeduction) {
                    $fabric->decrement('quantity', $totalQuantityDeduction);
                } else {
                    // Handle insufficient stock (throw exception or return error)
                    throw new Exception('Not enough material in stock.');
                }

                // LOG ORDER
                OrderLogs::create([
                    'user_id' => Auth::user()->id,
                    'order_id' => $order->id,
                    'material_name' => $fabric->name,
                    'unit' => $fabric->unit,
                    'total_quantity_used' => $totalQuantityDeduction,
                ]);
            }


            // NOTFICATION
            $this->paymentService->notifyUser($order->order_number, Auth::user()->id);

            // Email User Order
            $this->paymentService->sendOrderConfirmationEmail($order);
        });

        return response()->json(['message' => 'Order placed successfully', 'order_id' => $order->id]);
    }

    // ACTING AS A PAYMENT SUCCESS WEBHOOK
    public function OLDplaceOrder(Request $request)
    {

        $validated = $request->validate([
            'order_type' => 'required|string',
            'design_id' => 'required|integer',
            'total_price' => 'required|numeric',
            'order_option' => 'required|string',
            'quantity' => 'required|integer',
            'color_id' => 'required|integer',
            'size_id' => 'required|integer',
        ]);

        // Assign validated fields to variables
        $orderType = $validated['order_type'];
        $designID = $validated['design_id'];
        $totalPrice = $validated['total_price'];
        $orderOption = $validated['order_option'];
        $quantity = $validated['quantity'];
        $colorId = $validated['color_id'];
        $sizeId = $validated['size_id'];

        // Generate Order ID
        $orderId = 'ORD-' . now()->timestamp . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Lookup order type ID
        $orderTypeID = ModelsOrderType::where('name', $orderType)->value('id');

        // Get user ID
        $userId = Auth::user()->id ?? $request->user_id ?? null;

        // Determine design source based on order type
        $preMade = OrderType::PRE_MADE;
        $uploaded = OrderType::UPLOADED;

        $design = match ($orderType) {
            $preMade->value => Designs::with('materials')->find($designID),
            $uploaded->value => UploadedDesign::with('materials')->find($designID),
            default => null,
        };

        if (! $design) {
            Log::error('Error in finding design', ['error' => 'Design not found']);

            return response()->json(['error' => 'Design not found.'], 404);
        }

        // Create the order
        $order = Orders::create([
            'order_id' => $orderId,
            'option' => $orderOption,
            'paid_amount' => $totalPrice,
            'quantity' => $quantity,
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'type_id' => $orderTypeID,
            'design_id' => $designID,
            'status_id' => 1, // "pending"
            'user_id' => $userId,
        ]);

        // Broadcast event to refresh materials
        $paymentMessage = 'Payment success from Paymongo!';
        broadcast(new PaymentSucessful($paymentMessage));

        // AUTOMATED DEDUCTION HERE USING THE FETCHED DESIGN:
        foreach ($design->materials as $material) {

            $materialName = $material->name;
            $materialUnit = $material->unit;
            $usedQty = $material->pivot->quantity_used;
            $totalMaterialQuantityUsed = $quantity * $usedQty;

            Log::info('Material: ' . $materialName);
            Log::info('Used per unit: ' . $usedQty);
            Log::info('Total Deduct: ' . $totalMaterialQuantityUsed);

            // Now update the material's quantity
            $material->quantity = max(0, $material->quantity - $totalMaterialQuantityUsed);
            $material->save();

            OrderLogs::create([
                'user_id' => $userId,
                'order_id' => $order->id,
                'material_name' => $materialName,
                'unit' => $materialUnit,
                'total_quantity_used' => $totalMaterialQuantityUsed,
            ]);
        }

        return response()->json([
            'message' => 'Place Order Successfully',
            'order' => $order,
        ]);
    }

    public function handleAPIOrder(Request $request)
    {

        // ALL THIS STATIC DATA MUST BE REPLACE BY THE PAYMONGO WEBHOOK METADATA

        $orderId = 'ORD-' . now()->timestamp . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $orderType = 'pre-made';
        $orderTypeID = ModelsOrderType::where('name', '=', $orderType)->value('id');

        $designID = 1;

        $totalPrice = 70000;
        $orderOption = 'delivery';

        $quantity = 10;
        $colorId = 1;
        $sizeId = 2;
        $userId = 2; // Sample customer account

        // THE FECTHING OF DESIGN WHETHER IN UPLOADED OR PRE MADE DESIGN IS
        // BASED ON ORDER TYPE "uploaded" OR "pre-made"

        $preMade = OrderType::PRE_MADE;
        $uploaded = OrderType::UPLOADED;

        $design = match ($orderType) {
            $preMade->value => Designs::with('materials')->find($designID),
            $uploaded->value => UploadedDesign::with('materials')->find($designID),
            default => null,
        };

        if (! $design) {
            Log::error('Error in finding design', ['error' => 'Design not found'], 404);
        }

        // Create the order
        $order = Orders::create([
            'order_id' => $orderId,
            'option' => $orderOption,
            'paid_amount' => $totalPrice,
            'quantity' => $quantity,
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'type_id' => $orderTypeID, // if type_id refers to enum value
            'design_id' => $designID,
            'status_id' => 1, // default "pending" or however it's set
            'user_id' => $userId,
        ]);

        // BROADCAST EVENT TO REFETCH THE MATERIALS DYNAMICALLY
        $paymentMessage = 'Payment success from Paymongo!';
        broadcast(new PaymentSucessful($paymentMessage));

        // AUTOMATED DEDUCTION HERE USING THE FETCHED DESIGN:
        foreach ($design->materials as $material) {

            $materialName = $material->name;
            $materialUnit = $material->unit;
            $usedQty = $material->pivot->quantity_used;
            $totalMaterialQuantityUsed = $quantity * $usedQty;

            Log::info('Material: ' . $materialName);
            Log::info('Used per unit: ' . $usedQty);
            Log::info('Total Deduct: ' . $totalMaterialQuantityUsed);

            // Now update the material's quantity
            $material->quantity = max(0, $material->quantity - $totalMaterialQuantityUsed);
            $material->save();

            OrderLogs::create([
                'user_id' => $userId,
                'order_id' => $order->id,
                'material_name' => $materialName,
                'unit' => $materialUnit,
                'total_quantity_used' => $totalMaterialQuantityUsed,
            ]);
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ]);
    }

    public function getOrderLogs()
    {
        $orderLogs = OrderLogs::select('*') // or explicitly: select('id', 'order_id', 'message', etc.)
            ->with([
                'users' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();

        return response()->json($orderLogs);
    }
}
