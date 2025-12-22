<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{

    public function index()
    {
        $cartItems = Cart::with([
            'user',
            'product',
            'fabric_types',
            'size',
            'product.designs' // Include the designs of the product
        ])
            ->where('user_id', Auth::id())
            ->get();

        // Transform designs' image_url into a s3 readable temp url
        $cartItems->transform(function ($item) {
            if ($item->product && $item->product->designs) {
                $item->product->designs->transform(function ($design) {
                    if ($design->image_url) {
                        // Generates a temporary URL valid for 60 minutes
                        $design->temp_url = Storage::disk('s3')->temporaryUrl(
                            $design->image_url,
                            now()->addMinutes(60)
                        );
                    } else {
                        $design->temp_url = null;
                    }
                    return $design;
                });
            }
            return $item;
        });

        return response()->json($cartItems);
    }

    
    public function store(StoreCartRequest $request)
    {
        $validated = $request->validated();

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

    public function count()
    {
        $count = Cart::where('user_id', Auth::id())->count();

        return response()->json([
            'count' => $count,
        ]);
    }
}
