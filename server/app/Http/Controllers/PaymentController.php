<?php

namespace App\Http\Controllers;

use App\Events\PaymentUpdated;
use App\Http\Requests\StoreOrderRequest;
use App\Models\AdminNotification;
use App\Models\Cart;
use App\Models\Materials;
use App\Models\OrderItem;
use App\Models\OrderItemCustomization;
use App\Models\OrderLogs;
use App\Models\OrderPayment;
use App\Models\Orders;
use App\Models\PaymentMethod;
use App\Models\Products;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Traits\HandleAttachments;
use App\Traits\OrderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        // 1️⃣ Validate common checks first
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method_code' => 'nullable|string|exists:payment_methods,code',
        ]);

        $paymentMethodCode = $validated['payment_method_code'] ?? 'gcash';
        $isCash = $paymentMethodCode === 'cash';

        // 2️⃣ Conditional Validation
        if ($isCash) {
            if (!Auth::user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only admins can record cash payments.'], 403);
            }
            
            $cashValidated = $request->validate([
                'amount' => 'required|numeric|min:1',
            ]);
            $validated = array_merge($validated, $cashValidated);
        } else {
            // Online payments (GCash, Maya, etc.) require an attachment
            $onlineValidated = $request->validate([
                'payment_attachment' => 'required|file|mimes:jpg,jpeg,png|max:51200',
            ]);
            $validated = array_merge($validated, $onlineValidated);
        }

        $order = Orders::findOrFail($validated['order_id']);
        $attachmentURL = null;

        // 3️⃣ Handle File Upload (if not cash)
        if (!$isCash && $request->hasFile('payment_attachment')) {
            $attachmentURL = $this->uploadToS3(
                root: 'payment',
                sub: Auth::user()->id,
                file: $request->file('payment_attachment')
            );
        }

        // 4️⃣ Get Payment Method ID
        $paymentMethodID = PaymentMethod::where('code', $paymentMethodCode)->value('id');

        // 5️⃣ Determine Status & Amount
        $amount = $isCash ? $validated['amount'] : 0;
        
        $status = OrderPayment::IN_REVIEW;
        if ($isCash) {
            $status = OrderPayment::PARTIALLY_PAID;

            // Calculate total paid so far
            $totalPaid = OrderPayment::where('order_id', $order->id)->sum('amount_applied');

            // Add any pocket costs from order item customizations
            $pocketCostsTotal = OrderItemCustomization::whereHas('orderItem', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })->sum('pocket_costs');

            $effectiveTotalPrice = $order->total_price + $pocketCostsTotal;

            // Check if fully paid (existing + current) against the effective total
            if (($totalPaid + $amount) >= $effectiveTotalPrice) {
                $status = OrderPayment::FULLY_PAID;
            }
        }

        // 6️⃣ Create Payment Record
        $orderPayment = $this->paymentService->createAndLoadOrderPayment(
            $paymentMethodID, 
            $order->id, 
            Auth::user()->id, 
            $attachmentURL,
            $amount,
            $status
        );

        $orderPayment->load(['users']);
        $order->load(['items.product']);
        $productNames = [];
        $colors = [];
        foreach ($order->items as $item) {
            if ($item->product) {
                $productNames[] = $item->product->name;
            }
            if ($item->color) {
                $colors[] = ucfirst($item->color);
            }
        }
        $uniqueProducts = implode(', ', array_unique($productNames)) ?: 'N/A';
        $uniqueColors = implode(', ', array_unique($colors));
        $productDisplay = $uniqueColors ? "{$uniqueProducts} ({$uniqueColors})" : $uniqueProducts;

        $displayAmount = $amount > 0 ? $amount : $order->total_price;

        // Notify
        $message = sprintf(
            "💰 Payment Received (%s)!\n\n" .
                "Order No: %s\n" .
                "Customer: %s\n" .
                "Product: %s\n" .
                "Amount: ₱%s\n",

            strtoupper($paymentMethodCode),
            $order->order_number,
            $orderPayment->users->name ?? 'Guest',
            $productDisplay,
            number_format($displayAmount, 2)
        );

        $this->notificationService->notifyAdmin(AdminNotification::ORDER_NOTIFICATION_TYPE, $message);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully!',
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

    public function updateOrderDiscount(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only admins can update order discounts.'], 403);
        }

        $validated = $request->validate([
            'order_id' => 'required|numeric|exists:orders,id',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        $order = Orders::findOrFail($validated['order_id']);
        
        $discount = \App\Models\Discount::updateOrCreate(
            ['order_id' => $order->id],
            ['amount' => $validated['discount_amount']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Order discount updated successfully',
            'discount' => $discount
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

        $order = DB::transaction(function () use ($validated, $request) {
            
            // 1. Calculate the overall total price for the entire order
            $overallTotalPrice = 0;
            foreach ($validated['products'] as $product) {
                $overallTotalPrice += $product['total_price'];
            }

            $user = User::findOrFail(Auth::id());
            $promptCredit = $user->prompt_credit;

            $overallTotalPrice += $promptCredit;

            // 2. Create the single Order record
            $orderNumber = $this->generateOrderNumber();
            $order = Orders::create([
                'order_number' => $orderNumber,
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'design_type' => $validated['design_type'],
                'order_option' => $validated['order_option'],
                'total_price' => $overallTotalPrice,
                'user_id' => Auth::id(),
                'prompt_credit' => $promptCredit,
            ]);

            // 3. Process the single payment for the entire order
            if ($request->hasFile('payment_attachment')) {
                $this->paymentService->processPayment($order->id, $request->file('payment_attachment'));
            }

            // 4. Create OrderItems for each product
            foreach ($validated['products'] as $index => $product) {
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'color' => $product['product_color'],
                    'product_unit_price' => $product['product_unit_price'],
                    'total_quantity' => $product['total_quantity'],
                    'total_price' => $product['total_price'],
                    'solo_quantity' => $validated['solo_quantity'] ?? null,
                    'fabric_type_id' => $product['fabric_type_id'] ?? null,
                    'selected_styles' => isset($product['selected_styles']) ? json_decode($product['selected_styles'], true) : null,
                ]);

                if (isset($product['customizations']) && !empty($product['customizations'])) {
                    $customItems = json_decode($product['customizations'], true);
                    if ($customItems) {
                        // Support both backward-compatible single object or array of objects
                        if (!isset($customItems[0]) && !empty($customItems)) {
                            $customItems = [$customItems];
                        }
                        
                        foreach ($customItems as $customFields) {
                            OrderItemCustomization::create([
                                'order_item_id' => $orderItem->id,
                                'jersey_number' => $customFields['jersey_number'] ?? null,
                                'jersey_name' => $customFields['jersey_name'] ?? null,
                                'pocket_count' => $customFields['pocket_count'] ?? null,
                                'pocket_costs' => $customFields['pocket_costs'] ?? null,
                                'additional_instruction' => $customFields['additional_instruction'] ?? null,
                            ]);
                        }
                    }
                }

                // Handle design URLs per item
                if (array_key_exists('own_design_url', $product) && !empty($product['own_design_url'])) {
                    $orderItem->own_design_url = $product['own_design_url'];
                    $orderItem->save();
                } else {
                    $productModel = Products::with('designs')->find($product['product_id']);
                    if ($productModel && $productModel->designs && $productModel->designs->count() > 0) {
                        $firstDesign = $productModel->designs->first();
                        if ($firstDesign && isset($firstDesign->image_url)) {
                            $orderItem->business_design_url = $firstDesign->image_url;
                            $orderItem->save();
                        }
                    }
                }

                // Handle sizes attached to the item
                $decodedSizes = json_decode($product['sizes'], true);
                if (!empty($decodedSizes) && $product['total_quantity'] > 0) {
                    $orderItem->sizes()->attach($decodedSizes['id'], ['quantity' => $product['total_quantity']]);
                }
    
                // Deduct material stock per item
                if (!empty($product['fabric_type_id'])) {
                    $fabric = Materials::lockForUpdate()->find($product['fabric_type_id']);
                    
                    if ($fabric) {
                        $deduction = 0;

                        if ($fabric->unit == 'rolls') {
                            $baseFabricQuantityUsed = 0;
                            $sizeName = isset($decodedSizes['name']) ? $decodedSizes['name'] : null;
                            switch ($sizeName) {
                                case 'XXS': $baseFabricQuantityUsed = 0.003125; break;
                                case 'XS': $baseFabricQuantityUsed = 0.00625; break;
                                case 'S': $baseFabricQuantityUsed = 0.0125; break;
                                case 'M': $baseFabricQuantityUsed = 0.025; break;
                                case 'L': $baseFabricQuantityUsed = 0.05; break;
                                case 'XL': $baseFabricQuantityUsed = 0.1; break;
                                case 'XXL': $baseFabricQuantityUsed = 0.2; break;
                                default: $baseFabricQuantityUsed = 0.003125;
                            }
                            $deduction = (float) ($baseFabricQuantityUsed * (float) $product['total_quantity']);
                        } else {
                            $fabricUsedPerUnit = (float) $fabric->products()
                                ->where('products.id', $product['product_id'])
                                ->value('fabric_quantity');
                            $deduction = $product['total_quantity'] * $fabricUsedPerUnit;
                        }

                        $deduction = max(0, min($deduction, $fabric->quantity));
                        $fabric->decrement('quantity', $deduction);
        
                        OrderLogs::create([
                            'user_id' => Auth::id(),
                            'order_id' => $order->id, // Logs still reference the main order
                            'material_name' => $fabric->name,
                            'unit' => $fabric->unit,
                            'total_quantity_used' => (float) $deduction,
                        ]);
                    }
                }
            }
            
            // 5. Delete picked cart items
            $decodedCartIds = json_decode($validated['selected_cart_ids'], true);
            if (!empty($decodedCartIds)) {
                Cart::whereIn('id', $decodedCartIds)->delete();
            }

            // Remove the remaining credit of the user
            $user = User::findOrFail(Auth::id());
            $user->prompt_credit = 0;
            $user->save();
    
            return $order;
        });
    
        $this->notificationService->notifyUserOrder($order, Auth::id(), Orders::PENDING);

        // Summarise the order for the admin notification
        $totalItemsCount = 0;
        $productNames = [];
        foreach ($validated['products'] as $p) {
            $totalItemsCount += (int) $p['total_quantity'];
            $productModel = Products::find($p['product_id']);
            if ($productModel) {
                $productNames[] = $productModel->name;
            }
        }
        
        $uniqueProductsString = implode(', ', array_unique($productNames));

        $message = sprintf(
            "🆕 New Order Placed!\n\n" .
                "Order No: %s\n" .
                "Customer: %s\n" .
                "Products: %s\n" .
                "Total Items: %d pcs\n" .
                "Total Price: ₱%s\n",
            $order->order_number,
            Auth::user()->name ?? 'Guest',
            $uniqueProductsString ?: 'N/A',
            $totalItemsCount,
            number_format($order->total_price, 2)
        );

        $this->notificationService->notifyAdmin(AdminNotification::ORDER_NOTIFICATION_TYPE, $message);
        $this->paymentService->sendOrderConfirmationEmail($order);

        return response()->json([
            'message' => 'Order placed successfully',
            'order_id' => $order->id,
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

            // Add any pocket costs from order item customizations
            $pocketCostsTotal = OrderItemCustomization::whereHas('orderItem', function ($q) use ($payment) {
                $q->where('order_id', $payment->order_id);
            })->sum('pocket_costs');

            $effectiveTotalPrice = $orderTotalPrice + $pocketCostsTotal;

            // Determine status
            if ($projectedTotal >= $effectiveTotalPrice) {
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

    public function declinePayment($paymentID, Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only admins can decline payments.'], 403);
        }

        $validated = $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $payment = OrderPayment::with('orders')->findOrFail($paymentID);

        $payment->update([
            'status' => OrderPayment::DECLINED,
            'remarks' => $validated['remarks'],
        ]);

        broadcast(new PaymentUpdated($payment));
        $this->notificationService->notifyUserOrder($payment->orders, $payment->user_id, OrderPayment::PAYMENT_UPDATED);

        return response()->json([
            'success' => true,
            'message' => 'Payment declined successfully.',
            'payment' => $payment->fresh(['payment_methods'])
        ]);
    }

    public function reuploadPayment($paymentID, Request $request)
    {
        $validated = $request->validate([
            'payment_attachment' => 'required|file|mimes:jpg,jpeg,png|max:51200',
        ]);

        $payment = OrderPayment::with('payment_attachments', 'orders')->findOrFail($paymentID);

        // Upload the new attachment
        $attachmentURL = $this->uploadToS3(
            root: 'payment',
            sub: Auth::user()->id,
            file: $request->file('payment_attachment')
        );

        // Update the attachment record
        if ($payment->payment_attachments) {
            $payment->payment_attachments->update([
                'url' => $attachmentURL,
            ]);
        } else {
            $payment->payment_attachments()->create([
                'url' => $attachmentURL,
            ]);
        }

        // Reset payment status and remarks
        $payment->update([
            'status' => OrderPayment::IN_REVIEW,
            'remarks' => null,
        ]);

        broadcast(new PaymentUpdated($payment));
        // Notify admin about re-upload
        $message = sprintf(
            "🔄 Payment Re-uploaded!\n\n" .
                "Order No: %s\n" .
                "Payment Number: %s\n" .
                "Amount Applied: ₱%s\n",
            $payment->orders->order_number,
            $payment->payment_number,
            number_format($payment->amount_applied > 0 ? $payment->amount_applied : 0, 2)
        );

        $this->notificationService->notifyAdmin(AdminNotification::ORDER_NOTIFICATION_TYPE, $message);

        return response()->json([
            'success' => true,
            'message' => 'Payment attachment re-uploaded successfully.',
            'payment' => $payment->fresh(['payment_methods', 'payment_attachments'])
        ]);
    }
}
