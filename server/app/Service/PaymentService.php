<?php

namespace App\Service;

use App\Jobs\SendOrderConfirmation;
use App\Models\Notifications;
use App\Models\Orders;
use App\Models\OrderStatus;
use App\Traits\HandleAttachments;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentService
{
    use HandleAttachments;

    protected $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client;
    }

    public function requestPaymentIntent($designID, $totalPrice, $orderOption, $order_type, $quantity, $color, $size, $authHeader)
    {
        try {
            $paymentMethodsAllowed = ['qrph', 'card', 'dob', 'paymaya', 'billease', 'gcash', 'grab_pay'];
            $captureType = 'automatic';
            $currency = 'PHP';

            Log::info('Payment Intent Meta Data: ', [
                'designID' => $designID,
                'totalPrice' => $totalPrice,
                'orderOption' => $orderOption,
                'order_type' => $order_type,
                'quantity' => $quantity,
                'color' => $color,
                'size' => $size,
            ]);

            $intentRequest = $this->client->request('POST', 'https://api.paymongo.com/v1/payment_intents', [
                'body' => json_encode([
                    'data' => [
                        'attributes' => [
                            'amount' => $totalPrice,
                            'payment_method_allowed' => $paymentMethodsAllowed,
                            'payment_method_options' => [
                                'card' => [
                                    'request_three_d_secure' => 'any',
                                ],
                            ],
                            'currency' => $currency,
                            'capture_type' => $captureType,
                            'metadata' => [

                                // order_type IS USE FOR CONDITIONING WHERE TO SELECT A DESIGN
                                // WETHER ON uploaded_designs or designs table
                                'design_id' => (string) $designID,
                                'total_price' => (string) $totalPrice,
                                'order_option' => (string) $orderOption,
                                'order_type' => (string) $order_type,
                                'quantity' => (string) $quantity,
                                'color' => (string) $color,
                                'size' => (string) $size,

                            ],
                        ],
                    ],
                ]),

                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => $authHeader,
                    'content-type' => 'application/json',
                ],

            ]);

            $intentResponseData = json_decode($intentRequest->getBody());
            $paymentIntentID = $intentResponseData->data->id;

            Log::info('paymentIntentID: ' . $paymentIntentID);

            return $paymentIntentID;
        } catch (RequestException $e) {
            Log::error('Error creating payment intent (RequestException):', [
                'message' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);

            throw new Exception('Failed to create payment intent due to an API error.'); // Re-throw
        } catch (ConnectException $e) {

            Log::error('Error creating payment intent (ConnectException):', [
                'message' => $e->getMessage(),
            ]);

            throw new Exception('Failed to connect to the PayMongo API.'); // Re-throw
        } catch (Exception $e) {

            Log::error('Error creating payment intent (General Exception):', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('An unexpected error occurred while creating the payment intent.'); // Re-throw
        }
    }

    public function requestPaymentMethod(string $authHeader)
    {
        try {
            $billingName = 'Jarvis Designs';
            $billingEmail = 'geraldvillaceran101@gmail.com';
            $paymentType = 'qrph';

            $paymentMethodData = [
                'data' => [
                    'attributes' => [
                        'billing' => [
                            'name' => $billingName,
                            'email' => $billingEmail,
                        ],
                        'type' => $paymentType,
                    ],
                ],
            ];

            $paymentMethodRequest = $this->client->request('POST', 'https://api.paymongo.com/v1/payment_methods', [
                'body' => json_encode($paymentMethodData),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                    'authorization' => $authHeader,
                ],

            ]);

            $paymentMethodResponseData = json_decode($paymentMethodRequest->getBody());
            $paymentMethodId = $paymentMethodResponseData->data->id;

            Log::info('paymentMethodId: ' . $paymentMethodId);

            return $paymentMethodId;
        } catch (RequestException $e) {
            Log::error('Error attaching payment intent  (RequestException):', [
                'message' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            throw new Exception('Failed to attach payment intent  due to an API error.');
        } catch (ConnectException $e) {
            Log::error('Error attaching payment intent  (ConnectException):', [
                'message' => $e->getMessage(),
            ]);
            throw new Exception('Failed to connect to the PayMongo API.');
        } catch (Exception $e) {
            Log::error('Error attaching payment intent  (General Exception):', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('An unexpected error occurred while attaching the payment intent .');
        }
    }

    public function requestAttachPaymentIntentRequest(string $paymentIntentID, string $paymentMethodId, string $authHeader)
    {
        try {
            $attachPaymentIntentRequest = $this->client->request('POST', "https://api.paymongo.com/v1/payment_intents/{$paymentIntentID}/attach", [
                'body' => json_encode([
                    'data' => [
                        'attributes' => [
                            'payment_method' => $paymentMethodId,
                        ],
                    ],
                ]),

                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => $authHeader,
                    'content-type' => 'application/json',
                ],

            ]);

            $returnAsAssociativeArray = true;
            $attachPaymentIntentResponseData = json_decode($attachPaymentIntentRequest->getBody(), $returnAsAssociativeArray);

            Log::info('paymentIntentResponseData: ', [
                'response' => $attachPaymentIntentResponseData,
            ]);

            return $attachPaymentIntentResponseData;
        } catch (RequestException $e) {
            Log::error('Error attaching payment intent (RequestException):', [
                'message' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            throw new Exception('Failed to attach payment intent  due to an API error.');
        } catch (ConnectException $e) {
            Log::error('Error attaching payment intent (ConnectException):', [
                'message' => $e->getMessage(),
            ]);
            throw new Exception('Failed to connect to the PayMongo API.');
        } catch (Exception $e) {
            Log::error('Error attaching payment intent (General Exception):', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('An unexpected error occurred while attaching the payment intent .');
        }
    }

    public function allOrders($limit)
    {
        try {

            $query = Orders::with(['user:id,name,email', 'sizes'])
                ->select([
                    'id',
                    'order_number',
                    'product_unit_price',
                    'color',
                    'phone_number',
                    'address',
                    'design_type',
                    'order_option',
                    'total_quantity',
                    'total_price',
                    'solo_quantity',
                    'own_design_url',
                    'business_design_url',
                    'status',
                    'delivery_date',
                    'user_id',
                    'created_at',
                ]);

            $authenticatedUser = Auth::user();
            if (! $authenticatedUser->isAdmin()) {
                $query->where('user_id', '=', $authenticatedUser->id);
            }

            $orders = $query->latest()->paginate($limit);
            return $this->transformOrderDesignToS3Temp($orders);
            
        } catch (QueryException $e) {
            Log::error('Database Query Failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve all orders.',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function allOrderStatus()
    {
        try {

            $orderStatus = OrderStatus::select('id', 'name')->get();

            return $orderStatus;
        } catch (QueryException $e) {
            Log::error('Database Query Failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve preferred designs.',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus($orderID, $status)
    {
        try {

            $order = Orders::findOrFail($orderID);
            $order->status = $status;
            $order->save();

            Notifications::create([
                'order_id' => $order->id,
                'status' => $status,
                'user_id' => $order->user_id,
            ]);

            return $order->id;
        } catch (QueryException $e) {
            Log::error('Database Query Failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to update order status.',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function allOrdersNotifications()
    {
        try {

            $notifications = DB::table('notifications')
                ->select(
                    'id',
                    'order_id',
                    'created_at',
                    'status',
                    'is_read'
                )
                ->where('user_id', '=', Auth::user()->id)
                ->orderByDesc('notifications.created_at')
                ->get();

            return $notifications;
        } catch (QueryException $e) {
            Log::error('Database Query Failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch order notifications.',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateNotification($notificationID)
    {
        try {

            Notifications::where('id', $notificationID)->update([
                'is_read' => true,
            ]);

            return $notificationID;
        } catch (QueryException $e) {
            Log::error('Database Query Failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to update order status.',
                'message' => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAllNotificationsAsRead()
    {
        try {
            Notifications::where('is_read', false)->update([
                'is_read' => true,
            ]);

            return 'success';
        } catch (QueryException $e) {
            Log::error('Database Query Failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to update notifications.',
                'message' => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function sendOrderConfirmationEmail(Orders $orders)
    {
        $orders->load(['user']);
        SendOrderConfirmation::dispatch($orders)->afterCommit();
    }


    public function notifyUser($orderNumber, $userID)
    {
        Notifications::create([
            'order_id' => $orderNumber,
            'user_id' => $userID,
            'status' => 'pending',
        ]);
    }
}
