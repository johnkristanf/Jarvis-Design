<?php

namespace App\Service;

use Illuminate\Support\Facades\Log;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Exception; 

class PaymentService
{

    protected $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }


    public function requestPaymentIntent($designID, $totalPrice, $order_type, $quantity, $color, $size, $authHeader)
    {
        try {
                $paymentMethodsAllowed = ["qrph", "card", "dob", "paymaya", "billease", "gcash", "grab_pay"];
                $captureType = "automatic";
                $currency = 'PHP';

                Log::info("Payment Intent Meta Data: ", [
                    'designID' => $designID,
                    'totalPrice' => $totalPrice,
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

                Log::info("paymentIntentID: " . $paymentIntentID);

                return $paymentIntentID;

        } catch (RequestException $e) {
                Log::error("Error creating payment intent (RequestException):", [
                    'message' => $e->getMessage(),
                    'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                    'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
                ]);

                throw new Exception("Failed to create payment intent due to an API error."); // Re-throw

        } catch (ConnectException $e) {

                Log::error("Error creating payment intent (ConnectException):", [
                    'message' => $e->getMessage(),
                ]);

                throw new Exception("Failed to connect to the PayMongo API."); // Re-throw

        } catch (Exception $e) {

                Log::error("Error creating payment intent (General Exception):", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new Exception("An unexpected error occurred while creating the payment intent."); // Re-throw
        }
        
    }


    public function requestPaymentMethod(string $authHeader)
    {
        try{
            $billingName = "Jarvis Designs";
            $billingEmail = "johnkristan01@gmail.com";
            $paymentType = "qrph";

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

            Log::info("paymentMethodId: " . $paymentMethodId);

            return $paymentMethodId;

        } catch (RequestException $e) {
            Log::error("Error attaching payment intent  (RequestException):", [
                'message' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            throw new Exception("Failed to attach payment intent  due to an API error.");

        } catch (ConnectException $e) {
            Log::error("Error attaching payment intent  (ConnectException):", [
                'message' => $e->getMessage(),
            ]);
            throw new Exception("Failed to connect to the PayMongo API.");
        } catch (Exception $e) {
            Log::error("Error attaching payment intent  (General Exception):", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("An unexpected error occurred while attaching the payment intent .");
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

            Log::info("paymentIntentResponseData: ", [
                'response' => $attachPaymentIntentResponseData
            ]);


            return $attachPaymentIntentResponseData;


        } catch (RequestException $e) {
            Log::error("Error attaching payment intent (RequestException):", [
                'message' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            throw new Exception("Failed to attach payment intent  due to an API error.");

        } catch (ConnectException $e) {
            Log::error("Error attaching payment intent (ConnectException):", [
                'message' => $e->getMessage(),
            ]);
            throw new Exception("Failed to connect to the PayMongo API.");
            
        } catch (Exception $e) {
            Log::error("Error attaching payment intent (General Exception):", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception("An unexpected error occurred while attaching the payment intent .");
        }
    }
}
