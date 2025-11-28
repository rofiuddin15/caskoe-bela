<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
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

            return response()->json($employee, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $employee->load(['user.roles', 'branch']);
        return response()->json($employee);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $employee->user_id,
            'password' => 'sometimes|string|min:8',
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

            return response()->json($employee);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
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
