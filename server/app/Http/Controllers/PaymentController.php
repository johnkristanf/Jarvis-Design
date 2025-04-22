<?php

namespace App\Http\Controllers;

use App\Service\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }


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

        if($eventType === 'source.chargeable'){
            Log::info("SHESSHH NI GANAAA PARRR");
        }

        

        if ($eventType === 'payment.paid') {

            // HANDLE HERE THE GENERATION OF ORDER ID BASED ON THE FRONT END VUE SA FORMAT: ORD-12..xxxx

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
                        'hell_miccc_hello_test'=> 'hello miccc testtinggg...'
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
}
