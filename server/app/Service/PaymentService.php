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


    public function requestPaymentIntent($amount, string $authHeader)
    {
        try {
            $paymentMethodsAllowed = ["qrph", "card", "dob", "paymaya", "billease", "gcash", "grab_pay"];
                $captureType = "automatic";
                $currency = 'PHP';


               

                $intentRequest = $this->client->request('POST', 'https://api.paymongo.com/v1/payment_intents', [
                    'body' => json_encode([
                        'data' => [
                            'attributes' => [
                                'amount' => $amount, 
                                'payment_method_allowed' => $paymentMethodsAllowed,
                                'payment_method_options' => [
                                    'card' => [
                                        'request_three_d_secure' => 'any',
                                    ],
                                ],
                                'currency' => $currency,
                                'capture_type' => $captureType,
                            ],
                        ],
                    ]),

                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => $authHeader,
                        'content-type' => 'application/json',
                    ],

                    // MUST BE SET TO TRUE IF PRODUCTION FOR SECURITY PURPOSE
                    'verify' => false
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

                // MUST BE SET TO TRUE IF PRODUCTION FOR SECURITY PURPOSE
                'verify' => false

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

                // MUST BE SET TO TRUE IF PRODUCTION FOR SECURITY PURPOSE
                'verify' => false

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
