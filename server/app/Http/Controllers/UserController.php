<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }


    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|min:8'
        ]);

        Log::info("User Data: ", [
            'data' => $validatedData
        ]);

        $createdUserID = $this->userService->registerUser($validatedData);

        return response()->json([
            'msg' => 'Account Created Successfully',
            'accountID' => $createdUserID
        ], 201);
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);
 
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return response()->json(['msg' => 'Login Successfully'], 200);
        }
 
        return response()->json(['msg' => 'Invalid Username or Password'], 401);

    }

    public function user()
    {
        $authenticatedUser = Auth::user();
        return response()->json($authenticatedUser, 200);
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent(204);

    }
}
