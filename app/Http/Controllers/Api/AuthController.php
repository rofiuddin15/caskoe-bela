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
     * Login menggunakan PIN
     *
     * Login cepat untuk kasir menggunakan PIN 6 digit.
     * PIN harus sudah di-set terlebih dahulu untuk user yang bersangkutan.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "user": {
     *     "id": 2,
     *     "name": "Kasir 1",
     *     "email": "kasir@test.com"
     *   },
     *   "token": "2|xyz789...",
     *   "token_type": "Bearer"
     * }
     */
    public function loginWithPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|digits:6',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $query = User::whereNotNull('pin');

        // Jika branch_id diberikan, filter hanya user di cabang tersebut
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Cari user yang PIN-nya cocok
        $users = $query->get();
        $authenticatedUser = null;

        foreach ($users as $user) {
            if (Hash::check($request->pin, $user->pin)) {
                $authenticatedUser = $user;
                break;
            }
        }

        if (!$authenticatedUser) {
            throw ValidationException::withMessages([
                'pin' => ['PIN tidak valid atau tidak ditemukan.'],
            ]);
        }

        // Load relationships
        $authenticatedUser->load(['roles', 'permissions', 'branch']);

        $token = $authenticatedUser->createToken('pin-auth-token')->plainTextToken;

        return response()->json([
            'user' => $authenticatedUser,
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

    /**
     * Set PIN untuk user
     *
     * Membuat PIN 6 digit untuk user yang belum memiliki PIN.
     * PIN akan di-hash sebelum disimpan di database.
     *
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "PIN berhasil di-set",
     *   "has_pin": true
     * }
     */
    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|digits:6',
        ]);

        $user = $request->user();

        if ($user->pin) {
            return response()->json([
                'message' => 'User sudah memiliki PIN. Gunakan endpoint update-pin untuk mengubah PIN.'
            ], 400);
        }

        $user->pin = $request->pin; // Akan di-hash otomatis oleh cast
        $user->save();

        return response()->json([
            'message' => 'PIN berhasil di-set',
            'has_pin' => true,
        ]);
    }

    /**
     * Update PIN user
     *
     * Mengubah PIN yang sudah ada dengan PIN baru.
     * Memerlukan PIN lama untuk verifikasi.
     *
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "PIN berhasil diperbarui"
     * }
     */
    public function updatePin(Request $request)
    {
        $request->validate([
            'old_pin' => 'required|string|digits:6',
            'new_pin' => 'required|string|digits:6|different:old_pin',
        ]);

        $user = $request->user();

        if (!$user->pin) {
            return response()->json([
                'message' => 'User belum memiliki PIN. Gunakan endpoint set-pin terlebih dahulu.'
            ], 400);
        }

        if (!Hash::check($request->old_pin, $user->pin)) {
            throw ValidationException::withMessages([
                'old_pin' => ['PIN lama tidak sesuai.'],
            ]);
        }

        $user->pin = $request->new_pin; // Akan di-hash otomatis oleh cast
        $user->save();

        return response()->json([
            'message' => 'PIN berhasil diperbarui',
        ]);
    }
}
