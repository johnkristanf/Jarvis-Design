<?php

namespace App\Http\Controllers;

use App\Models\Designs;
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

        $orderId = 'ORD-' . now()->timestamp . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $order_type = OrderType::PRE_MADE;
        $orderTypeID = ModelsOrderType::select('id')
            ->where('name', '=', $order_type)
            ->first();

        $designID = 3;
       

        $totalPrice = 70000;
        $orderOption = 'delivery';

        $quantity = 10;
        $colorId = 1;
        $sizeId = 2;
        $userId = 2; // Sample customer account


        // THE FECTHING OF DESIGN WHETHER IN UPLOADED OR PRE MADE DESIGN IS 
        // BASED ON ORDER TYPE "uploaded" OR "pre-made"

        // $design = match ($order_type) {
        //     OrderType::PRE_MADE => Designs::find($designID),
        //     OrderType::UPLOADED => UploadedDesign::find($designID),
        //     default => null,
        // };
        

        // if (!$design) {
        //     Log::error('Error in finding design', ['error' => 'Design not found'], 404);
        // }

        // AUTOMATED DEDUCTION HERE USING THE fetched designs:
        $design = Designs::with('designs_materials')->find($designID);
        foreach ($design->designs_materials as $material) {
            $usedQty = $material->pivot->quantity_used;
            $totalDeduction = $quantity * $usedQty;
        
            Log::info('Material: ' . $material->name);
            Log::info('Used per unit: ' . $usedQty);
            Log::info('Total Deduct: ' . $totalDeduction);
        
            // Now update the material's quantity
            $material->quantity = max(0, $material->quantity - $totalDeduction);
            $material->save();
        }


        // HERE IS THE LOGIC FOR ALERTING IF THE RESTOCK LEVEL IS SAME AS QUANTITY
        // EITHER SMS OR EMAIL DEPENDS ON THE CLIENT

        
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

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order
        ]);
    }
}
