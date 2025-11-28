<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Http\Resources\SupplierResource;

class SupplierController extends Controller
{
    /**
     * Daftar semua supplier
     *
     * Menampilkan daftar supplier/pemasok bahan baku.
     *
     * @authenticated
     * @queryParam search string Cari berdasarkan nama, email, atau telepon. Example: supplier A
     * @queryParam is_active integer Filter supplier aktif (0/1). Example: 1
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $suppliers = $query->get();

        return SupplierResource::collection($suppliers);
    }

    /**
     * Buat supplier baru
     *
     * Membuat data supplier/pemasok baru.
     *
     * @authenticated
     * @bodyParam name string required Nama supplier. Example: PT Kopi Nusantara
     * @bodyParam email string Email supplier. Example: info@kopinusantara.com
     * @bodyParam phone string Nomor telepon. Example: 021-9876543
     * @bodyParam address string Alamat supplier. Example: Jl. Raya Bogor No. 100
     * @bodyParam contact_person string Nama kontak person. Example: Budi Santoso
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        return new SupplierResource($supplier);
    }

    /**
     * Detail supplier
     *
     * Menampilkan detail supplier termasuk riwayat purchase order.
     *
     * @authenticated
     * @urlParam id integer required ID supplier. Example: 1
     */
    public function show(Supplier $supplier)
    {
        $supplier->load('purchaseOrders');
        return new SupplierResource($supplier);
    }

    /**
     * Update supplier
     *
     * Memperbarui data supplier.
     *
     * @authenticated
     * @urlParam id integer required ID supplier. Example: 1
     * @bodyParam name string Nama supplier. Example: PT Kopi Indonesia
     * @bodyParam email string Email. Example: info@kopiindonesia.com
     * @bodyParam phone string Nomor telepon. Example: 021-1234567
     * @bodyParam address string Alamat. Example: Jl. Sudirman No. 50
     * @bodyParam contact_person string Nama kontak person. Example: Ahmad
     * @bodyParam is_active boolean Status aktif. Example: true
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return new SupplierResource($supplier);
    }

    /**
     * Hapus supplier
     *
     * Menghapus data supplier dari database.
     *
     * @authenticated
     * @urlParam id integer required ID supplier. Example: 1
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully'
        ]);
    }
}
