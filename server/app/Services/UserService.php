<?php

namespace App\Services;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function registerUser(array $data): int
    {
        try {

            $hashedPassword = Hash::make($data['password']);
            $roleID = Roles::where('name', 'user')->first()->id;

            $createdUserID = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role_id' => $roleID
            ])->id;

            return $createdUserID;

        } catch (\Exception $e) {
            Log::error("Error in registering user: ", [
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return -1;
        }
    }




}
