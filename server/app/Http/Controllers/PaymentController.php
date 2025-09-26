<?php

namespace App\Http\Controllers;

use App\Models\Materials;
use App\Models\OrderLogs;
use App\Models\Orders;
use App\Service\PaymentService;
use App\Traits\HandleAttachments;
use App\Traits\OrderTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use HandleAttachments, OrderTrait;

    protected $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService;
    }

    public function getAllOrders(Request $request)
    {
        $limit = $request->get('limit', 10);
        $orders = $this->paymentService->allOrders($limit);

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

            $this->paymentService->notifyUser($order->id, $order->user_id, $notifyStatus);

            return $order;
        });

        return response()->json([
            'message' => 'Order action date updated successfully.',
            'order' => $order,
        ]);
    }

    public function orderNotificationsPerUser()
    {
        $orderNotifications = $this->paymentService->getNotificationPerUser(userID: Auth::user()->id);
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
            $this->paymentService->notifyUser($order->id, Auth::user()->id, Orders::PENDING);

            // Email User Order
            // $this->paymentService->sendOrderConfirmationEmail($order);
        });

        return response()->json(['message' => 'Order placed successfully', 'order_id' => $order->id]);
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
