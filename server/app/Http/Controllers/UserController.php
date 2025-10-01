<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use App\Models\Roles;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            'password' => 'required|min:8',
        ]);

        Log::info('User Data: ', [
            'data' => $validatedData,
        ]);

        $createdUserID = $this->userService->registerUser($validatedData);

        if ($createdUserID) {
            if (! empty($validatedData['email'])) {
                // EMAIL VERIFICATION LINK
                Mail::to($validatedData['email'])->send(new EmailVerification(emailTo: $validatedData['email']));
            } else {
                Log::warning('Attempted to send email without a valid recipient.');
            }

            return response()->json([
                'msg' => 'Account Created Successfully',
                'email' => $validatedData['email'],
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
            ->select('id', 'name', 'email', 'username', 'role_id')
            ->with(['role' => function ($query) {
                $query->select('id', 'name');
            }])
            ->first(); 

        return response()->json($authenticatedUser, 200);
    }


    public function admin()
    {
        $admin = User::where('role_id', Roles::ADMIN_ROLE_ID)
            ->select('id', 'name', 'email')
            ->first(); 

        return response()->json($admin, 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'username' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }
}
