<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rpc\LoginRequest;
use App\Http\Resources\Rpc\LoginResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login untuk RPC System
     *
     * @param LoginRequest $request
     * @return JsonResponse|LoginResource
     */
    public function login(LoginRequest $request): JsonResponse|LoginResource
    {
        $email = $request->email;
        $password = $request->password;

        // Cari user berdasarkan email
        $user = User::where('email', $email)->first();

        // Validasi user dan password
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Validasi penting: Cek apakah user memiliki event yang diotorisasi
        $authorizedEvents = $user->events()->get();
        if ($authorizedEvents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke event manapun. Silakan hubungi administrator.',
            ], 403);
        }

        // Generate token menggunakan Sanctum
        $token = $user->createToken('rpc-token')->plainTextToken;

        // Return user data dengan token
        return new LoginResource($user, $token);
    }
}
