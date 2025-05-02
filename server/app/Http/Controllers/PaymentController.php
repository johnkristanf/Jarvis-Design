<?php

namespace App\Http\Controllers;

use App\Events\PaymentSucessful;
use App\Models\Designs;
use App\Models\OrderLogs;
use App\Models\Orders;
use App\Models\OrderType as ModelsOrderType;
use App\Models\UploadedDesign;
use App\OrderType;
use App\Service\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }


    // THIS WEBHOOK HANDLES THE WHOLE PAYMENT PROCESS FROM CANCEL TO SUCCESS PAY
    // YOU MUST INCLUDE THE INSERTION OF ORDERS HERE IF PAYMENT WAY SUCCESSFULL

    public function handleWebhook(Request $request)
    {
        $event = $request->all();

        Log::info('Event Data: ', [
            'event' => $event['data']
        ]);

        // Log::info('Meta Data: ', [
        //     'metadata' => $event['data']['attributes']['metadata']
        // ]);



        Log::info('Proccess Types: ', [
            'type' => $event['data']['attributes']['type']
        ]);

        $eventType = $event['data']['attributes']['type'];

        if ($eventType === 'source.chargeable') {
            Log::info("SHESSHH NI GANAAA PARRR");
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
            $authHeader = "Basic " . base64_encode($secretKey . ":");


            // ADD META DATA IN THE requestPaymentIntent SERVICE TO ADD THE (COLORS, SIZES, ETC...)
            $paymentIntentID = $this->paymentService->requestPaymentIntent($designID, $totalPrice, $orderOption, $orderType, $quantity, $color, $size, $authHeader);

            $paymentMethodID = $this->paymentService->requestPaymentMethod($authHeader);

            $attachPaymentIntentResponseData = $this->paymentService->requestAttachPaymentIntentRequest($paymentIntentID, $paymentMethodID, $authHeader);


            if (isset($attachPaymentIntentResponseData)) {

                Log::info("paymentIntentResponseData: ", [
                    'actions' => $attachPaymentIntentResponseData['data']['attributes']['next_action']
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
                Log::error("Failed to generate QR Code: Incomplete response", $attachPaymentIntentResponseData);
                return response()->json(['error' => 'Failed to generate QR Code. Incomplete response from PayMongo.'], 500);
            }
        } catch (Exception $e) {
            Log::error("Error in Generating QR Code: ", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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
                        'product_id' => "123",
                        'user' => 'Kristan',
                        'hell_miccc_hello_test' => 'hello miccc testtinggg...'
                    ],
                ],
            ],
        ]);

        // Log or return the response
        return response()->json($response->json());
    }

    public function getAllOrders()
    {
        $orders = $this->paymentService->allOrders();
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
            'status_id' => 'required|numeric',
        ]);

        $updatedOrderID = $this->paymentService->updateStatus($validated['order_id'], $validated['status_id']);

        return response()->json([
            'msg' => 'Order Status Updated Successfully',
            'orderID' => $updatedOrderID
        ], 200);
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
            'notifID' => $updatedNotificationID
        ], 200);
    }

    public function updateNotificationAsReadAll()
    {
        $this->paymentService->updateAllNotificationsAsRead();

        return response()->json([
            'msg' => 'Notification Read All Successfully',
        ], 200);
    }



    // ACTING AS A PAYMENT SUCCESS WEBHOOK
    public function testOrder(Request $request)
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


        if (!$design) {
            Log::error('Error in finding design', ['error' => 'Design not found'], 404);
        }


        // Create the order
        $order = Orders::create([
            'order_id'     => $orderId,
            'option'       => $orderOption,
            'paid_amount'  => $totalPrice,
            'quantity'     => $quantity,
            'color_id'     => $colorId,
            'size_id'      => $sizeId,
            'type_id'      => $orderTypeID, // if type_id refers to enum value
            'design_id'    => $designID,
            'status_id'    => 1, // default "pending" or however it's set
            'user_id'      => $userId,
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
            'order' => $order
        ]);
    }


    public function getOrderLogs()
    {
        $orderLogs = OrderLogs::with([
            'users' => function ($query) {
                $query->select('id', 'name');
            },

            'orders' => function ($query) {
                $query->select('id', 'order_id');
            },
        ])->get();

        return response()->json($orderLogs);
    }
    
}


