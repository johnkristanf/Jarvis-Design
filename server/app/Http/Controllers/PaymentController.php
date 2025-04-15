<?php

namespace App\Http\Controllers;

use App\Service\PaymentService;
use Exception;
use Illuminate\Http\Request;
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

        Log::info('Proccess Types: ', [
            'type' => $event['data']['attributes']['type']
        ]);

        if ($event['data']['attributes']['type'] === 'payment.paid') {
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

                return response('Webhook received and processed', 200);
            }
        }
        // Handle other webhook events if needed (e.g., payment.failed)

        return response('Webhook received', 200);
    }


    public function createQrPhSource(Request $request)
    {
        try {
                $amount = $request->input('amount'); 
                $secretKey = config('services.paymongo.secret_key');
                $authHeader = "Basic " . base64_encode($secretKey . ":");


                $paymentIntentID = $this->paymentService->requestPaymentIntent($amount, $authHeader);

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
}
