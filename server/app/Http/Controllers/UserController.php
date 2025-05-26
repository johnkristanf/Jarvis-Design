<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'email' => 'required|string|email',
            'password' => 'required|min:8'
        ]);

        Log::info("User Data: ", [
            'data' => $validatedData
        ]);

        $createdUserID = $this->userService->registerUser($validatedData);

        if(!$createdUserID !== -1){
            return response()->json([
                'msg' => 'Account Created Successfully',
                'accountID' => $createdUserID
            ], 201);
        }

        return response()->json([
            'msg' => 'Failed to Create Account',
        ], 500);
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $authenticatedUser = Auth::user();
            $authenticatedUser = User::where('id', $authenticatedUser->id)
                ->select('id', 'name', 'username', 'role_id')
                ->with(['role' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->first();

            return response()->json($authenticatedUser, 200);
        }

        return response()->json(['msg' => 'Invalid Username or Password'], 401);
    }

    public function user()
    {
        $authenticatedUser = Auth::user();
        $authenticatedUser = User::where('id', $authenticatedUser->id)
            ->select('id', 'name', 'username', 'role_id')
            ->with(['role' => function ($query) {
                $query->select('id', 'name');
            }])
            ->first();

        return response()->json($authenticatedUser, 200);
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([ 'success' => true ]);
    }
}
