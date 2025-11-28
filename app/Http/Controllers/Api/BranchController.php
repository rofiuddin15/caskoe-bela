<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Http\Resources\BranchResource;

class BranchController extends Controller
{
    /**
     * Daftar semua cabang
     *
     * Menampilkan daftar cabang dengan pagination. Mendukung filter berdasarkan status aktif dan pencarian.
     *
     * Query parameters:
     * - is_active: Filter berdasarkan status aktif (0/1)
     * - search: Cari berdasarkan nama, kode, atau alamat
     * - per_page: Jumlah data per halaman (default: 15)
     */
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $branches = $query->with('employees')->paginate($request->get('per_page', 15));

        return response()->json($branches);
    }

    /**
     * Buat cabang baru
     *
     * Membuat cabang baru dengan data yang disediakan.
     *
     * Request body harus berisi:
     * - name (required): Nama cabang
     * - code (required): Kode unik cabang
     * - address: Alamat cabang
     * - phone: Nomor telepon
     * - email: Email cabang
     * - opening_time: Jam buka (format HH:mm)
     * - closing_time: Jam tutup (format HH:mm)
     * - is_active: Status aktif (boolean)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);

        return new BranchResource($branch);
    }

    /**
     * Detail cabang
     *
     * Menampilkan detail cabang termasuk karyawan dan user yang terkait.
     */
    public function show(Branch $branch)
    {
        $branch->load(['employees.user', 'users']);
        return new BranchResource($branch);
    }

    /**
     * Update cabang
     *
     * Memperbarui data cabang yang sudah ada.
     *
     * Semua field bersifat opsional:
     * - name: Nama cabang
     * - code: Kode unik cabang
     * - address: Alamat cabang
     * - phone: Nomor telepon
     * - email: Email cabang
     * - opening_time: Jam buka (format HH:mm)
     * - closing_time: Jam tutup (format HH:mm)
     * - is_active: Status aktif (boolean)
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return new BranchResource($branch);
    }

    /**
     * Hapus cabang
     *
     * Menghapus cabang dari database.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }
}
