<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function store(StoreCartRequest $request)
    {
        // Validate the request data
        $validated = $request->validated();

        // Store data into Cart model using ::create
        $cart = Cart::create([
            'user_id' => Auth::id(),
            'product_id' => $validated['product_id'],
            'fabric_type_id' => $validated['fabric_type_id'] ?? null,
            'size_id' => $validated['size_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product stored in cart successfully!',
            'cart_item' => $cart,
        ]);
    }

    /**
     * Fetch count of cart items for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function count()
    {
        $count = Cart::where('user_id', Auth::id())->count();

        return response()->json([
            'count' => $count,
        ]);
    }
}
