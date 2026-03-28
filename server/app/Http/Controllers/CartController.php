<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Models\Cart;
use App\Traits\HandleAttachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{

    use HandleAttachments;

    public function index()
    {
        $cartItems = Cart::with([
            'user',
            'product',
            'fabric_types',
            'size',
            'product.designs' // Eager load product designs
        ])
        ->where('user_id', Auth::id())
        ->orderByDesc('created_at')
        ->get();


        // Transform designs' image_url into a s3 readable temp url
        $cartItems->transform(function ($item) {
            if ($item->product && $item->product->designs) {
                $item->product->designs->transform(function ($design) {
                    if ($design->image_url) {
                        // Generates a temporary URL valid for 60 minutes
                        $design->business_design_temp_url = Storage::disk('s3')->temporaryUrl(
                            $design->image_url,
                            now()->addMinutes(60)
                        );
                    } else {
                        $design->temp_url = null;
                    }
                    return $design;
                });
            }

            if($item->own_design_url){
                $item->own_design_temp_url = Storage::disk('s3')->temporaryUrl(
                    $item->own_design_url,
                    now()->addMinutes(60)
                );
            }
            return $item;
        });

        Log::info('Validated Cart Request:', [
            'payload' => json_encode($cartItems, JSON_PRETTY_PRINT)
        ]);


        return response()->json($cartItems);
    }

    
    public function store(StoreCartRequest $request)
    {
        $validated = $request->validated();
        Log::info('StoreCartRequest validated:', [
            'payload' => json_encode($validated, JSON_PRETTY_PRINT)
        ]);

        $cart = Cart::create([
            'user_id' => Auth::id(),
            'color' => $validated['color'],
            'product_id' => $validated['product_id'],
            'fabric_type_id' => $validated['fabric_type_id'] ?? null,
            'size_id' => $validated['size_id'] ?? null,
            'quantity' => $validated['quantity'],
            'selected_styles' => isset($validated['selected_styles']) ? json_decode($validated['selected_styles'], true) : null,
            'customizations' => isset($validated['customizations']) ? json_decode($validated['customizations'], true) : null,
        ]);

        // Check if own_design_file was uploaded and store its URL (optional)
        if ($request->hasFile('own_design_file')) {
            $ownDesignS3Key = $this->uploadToS3(
                root: 'carts/designs',
                sub: 'cart-' . $cart->id,
                file: $request->file('own_design_file')
            );
            $cart->own_design_url = $ownDesignS3Key;
            $cart->save();
        } elseif (!empty($validated['own_design_url'])) {
            // A previously saved AI design S3 key was passed directly — no re-upload needed
            $cart->own_design_url = $validated['own_design_url'];
            $cart->save();
        }

        return response()->json([
            'message' => 'Product stored in cart successfully!',
            'cart_item' => $cart,
        ]);
    }

    public function count()
    {
        $count = Cart::where('user_id', Auth::id())->count();

        Log::info("count: ", [$count]);

        return response()->json([
            'count' => $count,
        ]);
    }

    public function destroy($id)
    {
        $cartItem = Cart::find($id);

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found.'
            ], 404);
        }

        // Optionally, check user ownership here if desired:
        if ($cartItem->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Cart item deleted successfully.'
        ]);
    }
}
