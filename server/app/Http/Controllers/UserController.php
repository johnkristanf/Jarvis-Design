<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use App\Mail\PasswordReset;
use App\Models\Roles;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string',
                'username' => 'required|string',
                'email' => 'required|string|email',
                'address' => 'nullable|string',
                'phone_number' => 'nullable',
                'password' => 'required|min:8',
            ]);

            $this->userService->registerUser($validatedData);

            if (!empty($validatedData['email'])) {
                // EMAIL VERIFICATION LINK
                Mail::to($validatedData['email'])->send(new EmailVerification(emailTo: $validatedData['email']));
            } 

            return response()->json([
                'message' => 'Account Created Successfully',
                'email' => $validatedData['email'],
            ], 201);

        } catch (\Exception $e) {
            // Friendly exception messages for common registration errors
            $statusCode = $e->getCode();
            if (!$statusCode || !is_int($statusCode) || $statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            $message = $e->getMessage();

            // Optional: Customize messages if needed
            if (strpos(strtolower($message), 'username already exists') !== false) {
                $message = 'That username is already taken. Please choose another.';
            } elseif (strpos(strtolower($message), 'email already exists') !== false) {
                $message = 'That email address is already in use. Please use another.';
            } elseif (!$message) {
                $message = 'An error occurred while creating your account. Please try again later.';
            }

            return response()->json([
                'message' => $message,
            ], $statusCode);
        }
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
            ->select('id', 'name', 'email', 'username', 'role_id', 'prompt_limit', 'prompt_credit', 'address', 'phone_number')
            ->with(['role' => function ($query) {
                $query->select('id', 'name');
            }])
            ->first();

        return response()->json($authenticatedUser, 200);
    }


    public function deductPromptLimit()
    {
        return DB::transaction(function () {
            $user = User::lockForUpdate()->find(Auth::id()); // Lock row to prevent race conditions

            if (!$user) {
                return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            if ($user->prompt_limit <= 0) {
                return response()->json(['message' => 'Prompt limit already reached'], Response::HTTP_BAD_REQUEST);
            }

            // Deduct 1 from prompt_limit
            $user->decrement('prompt_limit', 1);

            // Add 5 to existing prompt_credit, not override
            $user->increment('prompt_credit', 5);

            // Reload user to reflect updated values
            $user->refresh();

            return response()->json([
                'message' => 'Prompt deducted successfully. 5 credits added to prompt_credit.',
                'remaining_prompt_limit' => $user->prompt_limit,
                'prompt_credit' => $user->prompt_credit,
            ], 200);
        });
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

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'If an account exists with that email, we have sent a password reset link.',
            ], 200);
        }

        $token = Str::random(64);

        $resetUrl = env('PASSWORD_RESET_URL')
            ?? rtrim(env('CLIENT_APP_URL', env('APP_URL', 'http://localhost')), '/') . '/auth/reset-password';
        $resetUrl .= '?token=' . urlencode($token) . '&email=' . urlencode($user->email);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->send(new PasswordReset($user->email, $resetUrl));

        return response()->json([
            'message' => 'If an account exists with that email, we have sent a password reset link.',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 400);
        }

        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return response()->json(['message' => 'This password reset link has expired.'], 400);
        }

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    }
}
