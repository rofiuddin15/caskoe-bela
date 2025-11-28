<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    /**
     * Daftar semua karyawan
     *
     * Menampilkan daftar karyawan dengan user dan cabang. Mendukung filter dan pencarian.
     *
     * @authenticated
     * @queryParam branch_id integer Filter berdasarkan ID cabang. Example: 1
     * @queryParam is_active integer Filter karyawan aktif (0/1). Example: 1
     * @queryParam search string Cari berdasarkan kode, posisi, nama, atau email. Example: kasir
     * @queryParam per_page integer Jumlah data per halaman. Default: 15. Example: 10
     */
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'branch']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $employees = $query->paginate($request->get('per_page', 15));

        return response()->json($employees);
    }

    /**
     * Buat karyawan baru
     *
     * Membuat user dan employee sekaligus. User otomatis di-assign role sesuai parameter.
     *
     * @authenticated
     * @bodyParam name string required Nama karyawan. Example: John Doe
     * @bodyParam email string required Email unik. Example: john@cafepro.com
     * @bodyParam password string required Password (minimal 8 karakter). Example: password123
     * @bodyParam phone string Nomor telepon. Example: 08123456789
     * @bodyParam address string Alamat karyawan. Example: Jl. Merdeka No. 5
     * @bodyParam branch_id integer ID cabang penempatan. Example: 1
     * @bodyParam employee_code string required Kode karyawan unik. Example: EMP001
     * @bodyParam position string Jabatan. Example: Kasir
     * @bodyParam hire_date date Tanggal masuk kerja. Example: 2025-01-01
     * @bodyParam salary numeric Gaji bulanan. Example: 5000000
     * @bodyParam role string required Role karyawan (owner, admin_pusat, manajer_cabang, kasir, karyawan_dapur). Example: kasir
     *
     * @response 201 {
     *   "id": 1,
     *   "employee_code": "EMP001",
     *   "position": "Kasir",
     *   "user": {...}
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'pin' => 'nullable|string|digits:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'employee_code' => 'required|string|unique:employees,employee_code',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'role' => 'required|string|exists:roles,name',
        ]);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'pin' => $validated['pin'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
            ]);

            // Assign role
            $user->assignRole($validated['role']);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'branch_id' => $validated['branch_id'] ?? null,
                'employee_code' => $validated['employee_code'],
                'position' => $validated['position'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'salary' => $validated['salary'] ?? null,
            ]);

            $employee->load(['user', 'branch']);

            DB::commit();

            return new EmployeeResource($employee);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail karyawan
     *
     * Menampilkan detail karyawan termasuk user, role, dan cabang.
     *
     * @authenticated
     * @urlParam employee integer required ID karyawan. Example: 1
     */
    public function show(Employee $employee)
    {
        $employee->load(['user.roles', 'branch']);
        return new EmployeeResource($employee);
    }

    /**
     * Update karyawan
     *
     * Memperbarui data karyawan dan user terkait. Dapat mengubah role karyawan.
     *
     * @authenticated
     * @urlParam employee integer required ID karyawan. Example: 1
     * @bodyParam name string Nama karyawan. Example: Jane Doe
     * @bodyParam email string Email. Example: jane@cafepro.com
     * @bodyParam password string Password baru (minimal 8 karakter). Example: newpassword123
     * @bodyParam phone string Nomor telepon. Example: 08123456789
     * @bodyParam address string Alamat. Example: Jl. Sudirman No. 10
     * @bodyParam branch_id integer ID cabang. Example: 2
     * @bodyParam employee_code string Kode karyawan. Example: EMP002
     * @bodyParam position string Jabatan. Example: Manajer
     * @bodyParam hire_date date Tanggal masuk. Example: 2025-01-15
     * @bodyParam salary numeric Gaji bulanan. Example: 6000000
     * @bodyParam is_active boolean Status aktif. Example: true
     * @bodyParam role string Role karyawan. Example: manajer_cabang
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $employee->user_id,
            'password' => 'sometimes|string|min:8',
            'pin' => 'nullable|string|digits:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'employee_code' => 'sometimes|string|unique:employees,employee_code,' . $employee->id,
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        DB::beginTransaction();
        try {
            // Update user
            $userUpdate = [];
            if (isset($validated['name'])) $userUpdate['name'] = $validated['name'];
            if (isset($validated['email'])) $userUpdate['email'] = $validated['email'];
            if (isset($validated['password'])) $userUpdate['password'] = Hash::make($validated['password']);
            if (isset($validated['pin'])) $userUpdate['pin'] = $validated['pin'];
            if (isset($validated['phone'])) $userUpdate['phone'] = $validated['phone'];
            if (isset($validated['address'])) $userUpdate['address'] = $validated['address'];
            if (isset($validated['branch_id'])) $userUpdate['branch_id'] = $validated['branch_id'];

            if (!empty($userUpdate)) {
                $employee->user->update($userUpdate);
            }

            // Update role if provided
            if (isset($validated['role'])) {
                $employee->user->syncRoles([$validated['role']]);
            }

            // Update employee
            $employeeUpdate = [];
            if (isset($validated['branch_id'])) $employeeUpdate['branch_id'] = $validated['branch_id'];
            if (isset($validated['employee_code'])) $employeeUpdate['employee_code'] = $validated['employee_code'];
            if (isset($validated['position'])) $employeeUpdate['position'] = $validated['position'];
            if (isset($validated['hire_date'])) $employeeUpdate['hire_date'] = $validated['hire_date'];
            if (isset($validated['salary'])) $employeeUpdate['salary'] = $validated['salary'];
            if (isset($validated['is_active'])) $employeeUpdate['is_active'] = $validated['is_active'];

            if (!empty($employeeUpdate)) {
                $employee->update($employeeUpdate);
            }

            $employee->load(['user.roles', 'branch']);

            DB::commit();

            return new EmployeeResource($employee);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus karyawan
     *
     * Menghapus karyawan dan user terkait dari database.
     *
     * @authenticated
     * @urlParam employee integer required ID karyawan. Example: 1
     *
     * @response 200 {
     *   "message": "Employee deleted successfully"
     * }
     */
    public function destroy(Employee $employee)
    {
        DB::beginTransaction();
        try {
            $user = $employee->user;
            $employee->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Employee deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
