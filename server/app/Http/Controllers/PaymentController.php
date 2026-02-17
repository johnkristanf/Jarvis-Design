<?php

namespace App\Http\Controllers;

use App\Events\PaymentUpdated;
use App\Http\Requests\StoreOrderRequest;
use App\Models\AdminNotification;
use App\Models\Cart;
use App\Models\Materials;
use App\Models\OrderLogs;
use App\Models\OrderPayment;
use App\Models\Orders;
use App\Models\PaymentMethod;
use App\Models\Products;
use App\Models\User;
use App\Service\PaymentService;
use App\Service\NotificationService;
use App\Traits\HandleAttachments;
use App\Traits\OrderTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use HandleAttachments, OrderTrait;

    protected $paymentService;
    protected $notificationService;

    public function __construct()
    {
        $this->paymentService = new PaymentService;
        $this->notificationService = new NotificationService;
    }

    public function getAllOrders(Request $request)
    {
        $search = $request->input('search');

        $limit = $request->get('limit', 5);
        $orders = $this->paymentService->allOrders($limit, $search);

        return response()->json($orders, 200);
    }


    public function store(Request $request)
    {
        // ✅ Validate incoming form data
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_attachment' => 'required|file|mimes:jpg,jpeg,png|max:2048', // adjust rules
        ]);

        $order = Orders::findOrFail($validated['order_id']);
        $attachmentURL = $this->uploadToS3(
            root: 'payment',
            sub: Auth::user()->id,
            file: $request->file('payment_attachment')
        );

        $paymentMethodID = PaymentMethod::where('code', PaymentMethod::GCASH)->value('id') ?? 0;

        $orderPayment = $this->paymentService->createAndLoadOrderPayment($paymentMethodID, $order->id, Auth::user()->id, $attachmentURL);


        $orderPayment->load(['users']);
        $message = sprintf(
            "💰 Payment Received!\n\n" .
                "Order No: %s\n" .
                "Customer: %s\n" .
                "Product: %s (%s)\n",

            $order->order_number,
            $orderPayment->users->name ?? 'Guest',
            $order->product->name ?? 'N/A',
            ucfirst($order->color),
        );

        $this->notificationService->notifyAdmin(AdminNotification::ORDER_NOTIFICATION_TYPE, $message);

        return response()->json([
            'success' => true,
            'message' => 'Payment uploaded successfully!',
            'data' => $orderPayment
        ]);
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

        $order = DB::transaction(function () use ($validated) {
            $order = Orders::findOrFail($validated['order_id']);

            $order->update([
                'delivery_date' => $validated['action_date'],
                'status' => $validated['status'],
            ]);

            // INSERT NOTIFICATION
            $notifyStatus = $order->order_option === 'delivery'
                ? Orders::FOR_DELIVERY
                : Orders::FOR_PICKUP;

            $this->notificationService->notifyUserOrder($order, $order->user_id, $notifyStatus);

            return $order;
        });

        return response()->json([
            'message' => 'Order action date updated successfully.',
            'order' => $order,
        ]);
    }



    public function placeOrder(StoreOrderRequest $request)
    {
        $validated = $request->validated();
    
        $orders = DB::transaction(function () use ($validated, $request) {
    
            $createdOrders = [];
    
            foreach ($validated['products'] as $index => $product) {
    
                $orderNumber = $this->generateOrderNumber();
                
                $order = Orders::create([
                    'order_number' => $orderNumber,
                    'phone_number' => $validated['phone_number'],
                    'address' => $validated['address'],
                    'design_type' => $validated['design_type'],
                    'order_option' => $validated['order_option'],
    
                    // product-specific
                    'color' => $product['product_color'],
                    'product_id' => $product['product_id'],
                    'product_unit_price' => $product['product_unit_price'],
                    'total_quantity' => $product['total_quantity'],
                    'total_price' => $product['total_price'],
    
                    'solo_quantity' => $validated['solo_quantity'] ?? null,
                    'user_id' => Auth::id(),
                ]);

                // If the user didn't upload an own design, set the business design url to the first design of the product
                if (array_key_exists('own_design_url', $product) && !is_null($product['own_design_url']) && $product['own_design_url'] !== '') {
                    $order->own_design_url = $product['own_design_url'];
                    $order->save();
                } else {
                    $productModel = Products::with('designs')->find($product['product_id']);
                    if ($productModel && $productModel->designs && $productModel->designs->count() > 0) {
                        $firstDesign = $productModel->designs->first();
                        if ($firstDesign && isset($firstDesign->image_url)) {
                            $order->business_design_url = $firstDesign->image_url;
                            $order->save();
                        }
                    }
                }


                $decodedCartIds = json_decode($validated['selected_cart_ids']);
                $decodedSizes = json_decode($product['sizes'], true);

                if(!empty($decodedCartIds)){
                    foreach ($decodedCartIds as $cartId) {
                        $cart = Cart::find($cartId);
                        if ($cart) {
                            $cart->delete();
                        }
                    }
                }

                if (! empty($decodedSizes)) {
                    if ($product['total_quantity'] > 0) {
                        $order->sizes()->attach($decodedSizes['id'], ['quantity' => $product['total_quantity']]);
                    }
                }
    
                // 2️⃣ Upload per-product payment attachment
                if ($paymentFile = $request->file("products.$index.payment_attachment")) {
                    $paymentPath = $this->uploadToS3(
                        root: 'orders/payments',
                        sub: $order->id,
                        file: $paymentFile
                    );
    
                    $order->update([
                        'payment_attachment_path' => $paymentPath,
                    ]);
                }
    
    
                // 4️⃣ Deduct material stock (per product)
                if (! empty($product['fabric_type_id'])) {
    
                    $fabric = Materials::lockForUpdate()->findOrFail($product['fabric_type_id']);

                    $deduction = 0;

                    // If the fabric's unit is roll, the decimal computation happens
                    if($fabric && $fabric->unit == 'rolls'){
                        
                        $baseFabricQuantityUsed = 0;
                        $deduction = 0;

                        $sizeName = isset($decodedSizes['name']) ? $decodedSizes['name'] : null;
                        switch ($sizeName) {
                            case 'XXS':
                                $baseFabricQuantityUsed = 0.003125;
                                break;
                            case 'XS':
                                $baseFabricQuantityUsed = 0.00625;
                                break;
                            case 'S':
                                $baseFabricQuantityUsed = 0.0125;
                                break;
                            case 'M':
                                $baseFabricQuantityUsed = 0.025;
                                break;
                            case 'L':
                                $baseFabricQuantityUsed = 0.05;
                                break;
                            case 'XL':
                                $baseFabricQuantityUsed = 0.1;
                                break;
                            case 'XXL':
                                $baseFabricQuantityUsed = 0.2;
                                break;
                            default:
                                $baseFabricQuantityUsed = 0.003125; // Default to XXS value
                        }

                        $deduction = (float) ($baseFabricQuantityUsed * (float) $product['total_quantity']);

                        Log::info("decodedSizes: ", [$decodedSizes]);
                    
                    } else {
                        $fabricUsedPerUnit = (float) $fabric->products()
                            ->where('products.id', $product['product_id'])
                            ->value('fabric_quantity');
        
                        $deduction = $product['total_quantity'] * $fabricUsedPerUnit;
                    }

                    // if ($fabric->quantity < $deduction) {
                    //     throw new \Exception("Not enough material stock for {$fabric->name}");
                    // }

                    // Cap deduction to the available quantity (min 0)
                    $deduction = max(0, min($deduction, $fabric->quantity));
                    
                    $fabric->decrement('quantity', $deduction);
    
                    OrderLogs::create([
                        'user_id' => Auth::id(),
                        'order_id' => $order->id,
                        'material_name' => $fabric->name,
                        'unit' => $fabric->unit,
                        'total_quantity_used' => (float) $deduction,
                    ]);
                }

                // Process order payment
                if (isset($product['payment_attachment'])) {
                    $paymentAttachmentFile = $product['payment_attachment'];
                    $this->paymentService->processPayment($order->id, $paymentAttachmentFile);
                }

                $createdOrders[] = $order;
            }
    
            return $createdOrders;
        });
    
        foreach ($orders as $order) {

            $this->notificationService->notifyUserOrder($order, Auth::user()->id, Orders::PENDING);

            $message = sprintf(
                "🆕 New Order Placed!\n\n" .
                    "Order No: %s\n" .
                    "Customer: %s\n" .
                    "Product: %s (%s)\n" .
                    "Quantity: %d pcs\n" .
                    "Total Price: ₱%s\n",
                $order->order_number,
                Auth::user()->name ?? 'Guest',
                $order->product->name ?? 'N/A',
                ucfirst($order->color),
                $order->total_quantity,
                number_format($order->total_price, 2),
            );

            $this->notificationService->notifyAdmin(AdminNotification::ORDER_NOTIFICATION_TYPE, $message);

            $this->paymentService->sendOrderConfirmationEmail($order);

        }

        // Remove the remaining credit of the user
        $user = User::findOrFail(Auth::id());
        $user->prompt_credit = 0;
        $user->save();

    
        return response()->json([
            'message' => 'Order placed successfully',
            'items' => count($orders),
        ]);
    }
    


    // public function copyPlaceOrder(StoreOrderRequest $request)
    // {
    //     $validated = $request->validated();
    //     $order = DB::transaction(function () use ($validated, $request) {
    //         // Step 1: Create order first, without the design URL yet
    //         $order = Orders::create([
    //             'order_number' => $this->generateOrderNumber(),
    //             'color' => $validated['color'],
    //             'product_unit_price' => $validated['product_unit_price'],
    //             'product_id' => $validated['product_id'],
    //             'phone_number' => $validated['phone_number'],
    //             'address' => $validated['address'],
    //             'design_type' => $validated['design_type'],
    //             'order_option' => $validated['order_option'],
    //             'total_quantity' => $validated['total_quantity'],
    //             'total_price' => $validated['total_price'],
    //             'solo_quantity' => $validated['solo_quantity'] ?? null,
    //             'business_design_url' => $validated['business_design_url'] ?? null,
    //             'user_id' => Auth::user()->id,
    //         ]);

    //         // Step 2: Handle own-design file upload (after Order is created)
    //         if ($request->hasFile('own_design_file')) {
    //             $attachmentURL = $this->uploadToS3(
    //                 root: 'orders',
    //                 sub: $order->id,
    //                 file: $request->file('own_design_file')
    //             );

    //             // Step 3: Update the order with the uploaded file's URL
    //             $order->update([
    //                 'own_design_url' => $attachmentURL,
    //             ]);
    //         }

    //         // Step 4: Handle sizes (many-to-many pivot with quantity)
    //         if (! empty($validated['sizes'])) {
    //             foreach ($validated['sizes'] as $sizeId => $qty) {
    //                 if ($qty > 0) {
    //                     $order->sizes()->attach($sizeId, ['quantity' => $qty]);
    //                 }
    //             }
    //         }

    //         // Step 5: Deduct total quantity ordered in materials table
    //         if (isset($validated['fabric_type_id']) && $validated['fabric_type_id']) {

    //             $fabric = Materials::findOrFail($validated['fabric_type_id']);

    //             $totalOrderedQuantity = (int) $validated['total_quantity'];
    //             $fabricUsedPerUnit = (float) $fabric->products()->pluck('fabric_quantity')->first();

    //             $totalQuantityDeduction = $totalOrderedQuantity * $fabricUsedPerUnit;

    //             if ($fabric->quantity >= $totalQuantityDeduction) {
    //                 $fabric->decrement('quantity', $totalQuantityDeduction);
    //             } else {
    //                 // Handle insufficient stock (throw exception or return error)
    //                 throw new Exception('Not enough material in stock.');
    //             }

    //             // LOG ORDER
    //             OrderLogs::create([
    //                 'user_id' => Auth::user()->id,
    //                 'order_id' => $order->id,
    //                 'material_name' => $fabric->name,
    //                 'unit' => $fabric->unit,
    //                 'total_quantity_used' => $totalQuantityDeduction,
    //             ]);
    //         }

    //         return $order;
    //     });

    //     // BACKGROUND QUEUE JOBS

    //     // Process payment
    //     if (isset($validated['payment_attachment'])) {
    //         $this->paymentService->processPayment($order->id, $request->file('payment_attachment'));
    //     }

    //     // User order notification
    //     $this->notificationService->notifyUserOrder($order, Auth::user()->id, Orders::PENDING);

    //     // Notify admin
    //     $message = sprintf(
    //         "🆕 New Order Placed!\n\n" .
    //             "Order No: %s\n" .
    //             "Customer: %s\n" .
    //             "Product: %s (%s)\n" .
    //             "Quantity: %d pcs\n" .
    //             "Total Price: ₱%s\n",

    //         $order->order_number,
    //         Auth::user()->name ?? 'Guest',
    //         $order->product->name ?? 'N/A',
    //         ucfirst($order->color),
    //         $order->total_quantity,
    //         number_format($order->total_price, 2),
    //     );

    //     $this->notificationService->notifyAdmin(AdminNotification::ORDER_NOTIFICATION_TYPE, $message);

    //     // Email user order
    //     $this->paymentService->sendOrderConfirmationEmail($order);

    //     return response()->json(['message' => 'Order placed successfully', 'order_id' => $order->id]);
    // }

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

    public function getPaymentMethods()
    {
        // Fetch all payment methods using model
        $methods = PaymentMethod::select('id', 'code', 'name')->get();
        return response()->json($methods);
    }

    public function paymentsByOrderID($orderID)
    {
        $payments = OrderPayment::with([
            'payment_methods:id,code,name',
            'payment_attachments:id,order_payment_id,url',
            'orders:id,total_price'
        ])
            ->where('order_id', $orderID)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info("payments: ", [$payments]);
        return response()->json($payments);
    }


    public function recordPayment($paymentID, Request $request)
    {
        $validated = $request->validate([
            'amount_applied' => 'sometimes|numeric',
            'payment_method_id' => 'sometimes|exists:payment_methods,id'
        ]);

        $payment = OrderPayment::with([
            'orders:id,total_price,status'
        ])->findOrFail($paymentID);

        $updateData = [];
        $recalculateStatus = false;

        // Update payment method if provided
        if (isset($validated['payment_method_id'])) {
            $updateData['payment_method_id'] = $validated['payment_method_id'];
        }

        // Update amount if provided
        if (isset($validated['amount_applied'])) {
            $updateData['amount_applied'] = $validated['amount_applied'];
            $recalculateStatus = true;
        }

        // Recalculate status only if amount changed
        if ($recalculateStatus) {
            // Calculate current total paid amount on a specific order
            $currentTotalAmount = OrderPayment::where('order_id', $payment->order_id)
                ->sum('amount_applied');

            // Subtract current payment amount and add new amount to get the projected total
            $projectedTotal = $currentTotalAmount - $payment->amount_applied + $validated['amount_applied'];
            $orderTotalPrice = $payment->orders->total_price;

            // Determine status
            if ($projectedTotal >= $orderTotalPrice) {
                $newStatus = OrderPayment::FULLY_PAID;
            } elseif ($projectedTotal > 0) {
                $newStatus = OrderPayment::PARTIALLY_PAID;
            } else {
                $newStatus = OrderPayment::IN_REVIEW;
            }

            $updateData['status'] = $newStatus;
        }

        // Update the payment
        if (!empty($updateData)) {
            $payment->update($updateData);

            broadcast(new PaymentUpdated($payment));
            $this->notificationService->notifyUserOrder($payment->orders, $payment->user_id, OrderPayment::PAYMENT_UPDATED);
        }

        // Calculate current total for response
        $currentTotalAmount = OrderPayment::where('order_id', $payment->order_id)
            ->sum('amount_applied');

        return response()->json([
            'success' => true,
            'payment' => $payment->fresh(['payment_methods']),
            'total_applied' => $currentTotalAmount,
            'order_total' => $payment->orders->total_price
        ]);
    }
}
