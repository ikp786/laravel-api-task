<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use DB;

class AuthenticationController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&]/',
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendFailed($validator->errors()->all(), 422);
        }

        $formData = $request->only('name', 'email');
        $formData['password'] = Hash::make($request->password);

        try {
            DB::beginTransaction();
            $user = User::create($formData);
            $user->api_key = $this->generateApiKey($user->id); // Improved API key generation
            $user->save();
            DB::commit();
            return $this->sendSuccess('Registration successful');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the exception details
            \Log::error('Registration failed: ' . $e->getMessage());
            return $this->sendFailed('Registration failed. Please try again.', 400);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendFailed($validator->errors()->all(), 422);
        }

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $token = Auth::user()->createToken('passportToken')->accessToken;
            return $this->sendSuccess('Login successful', $token);
        }
        return $this->sendFailed('Invalid email or password', 401);
    }

    private function generateApiKey($userId)
    {
        return hash('sha256', bin2hex(random_bytes(16)) . $userId);
    }
}
