<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user dan membuat token autentikasi
     *
     * Login menggunakan email dan password untuk mendapatkan Bearer token.
     * Token ini digunakan untuk mengakses endpoint yang dilindungi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "Test Owner",
     *     "email": "owner@test.com"
     *   },
     *   "token": "1|abc123...",
     *   "token_type": "Bearer"
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Load relationships
        $user->load(['roles', 'permissions']);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user dan hapus token
     *
     * Menghapus token autentikasi saat ini. Setelah logout, token tidak dapat digunakan lagi.
     *
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Dapatkan informasi user yang sedang login
     *
     * Mengembalikan data lengkap user yang terautentikasi termasuk role, permission, dan cabang.
     *
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "Test Owner",
     *     "email": "owner@test.com",
     *     "roles": [...],
     *     "permissions": [...],
     *     "branch": {...}
     *   }
     * }
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'permissions', 'branch']);

        return response()->json([
            'user' => $user
        ]);
    }
}
