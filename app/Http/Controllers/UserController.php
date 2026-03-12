<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function getAllUsers(Request $request)
    {
        try {
            $request->validate([
                'search'    => 'nullable|string|max:255',
                'role'      => 'nullable|in:admin,staff',
                'is_active' => 'nullable|in:true,false,0,1',
                'sortBy'    => 'nullable|in:name,email,role,created_at',
                'sortOrder' => 'nullable|in:asc,desc',
                'page'      => 'nullable|integer|min:1',
                'limit'     => 'nullable|integer|min:1|max:100',
            ]);

            $search    = $request->search ?? '';
            $sortBy    = $request->sortBy ?? 'created_at';
            $sortOrder = $request->sortOrder ?? 'desc';
            $page      = $request->page ?? 1;
            $limit     = $request->limit ?? 20;

            $users = User::with('createdBy:id,name')
                ->select('id', 'name', 'email', 'role', 'is_active', 'created_by', 'created_at')
                ->where('name', 'ilike', "%{$search}%")
                ->when($request->role, fn($q) => $q->where('role', $request->role))
                ->when($request->filled('is_active'), fn($q) => $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)))
                ->orderBy($sortBy, $sortOrder)
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully.',
                'users'   => $users->items(),
                'meta'    => [
                    'total' => $users->total(),
                    'page'  => $users->currentPage(),
                    'limit' => $users->perPage(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching users failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
                'message' => 'Failed to fetch users, please try again later.'
            ], 500);
        }
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:100',
            'role'     => 'required|in:admin,staff',
        ], [
            'name.required'     => 'Please enter your name.',
            'name.string'       => 'The name must be text.',
            'name.max'          => 'The name cannot exceed 255 characters.',
            'email.required'    => 'Please provide an email address.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'This email is already in use.',
            'password.required' => 'Please set a password.',
            'password.string'   => 'The password must be text.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.max'      => "Password must be lower than 100 characters.",
            'role.required'     => 'Please select a role.',
            'role.in'           => 'Role must be either admin or staff.',
        ]);

        try {
            $hashPassword = Hash::make($request->password);

            $user = User::create([
                'name'       => $request->name,
                'email'      => $request->email,
                'password'   => $hashPassword,
                'role'       => $request->role,
                'created_by' => auth()->id(),
                'is_active'  => true,
            ]);

            ActivityLog::log('user_created', $user, null, [
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user'    => $user,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('User creation failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user, please try again later.'
            ], 500);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::with('createdBy:id,name')->find($request->user_id);

            return response()->json([
                'success' => true,
                'data'    => $user,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('User creation failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user, please try again later.'
            ], 500);
        }
    }

    public function updateUser(Request $request)
    {
        $request->validate([
            'id'        => 'required|exists:users,id',
            'name'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $request->id,
            'role'      => 'sometimes|in:admin,staff',
            'is_active' => 'sometimes|boolean',
            'password'  => 'nullable|string|min:8',
        ], [
            'id.required'    => 'User ID is required.',
            'id.exists'      => 'User not found.',
            'name.string'    => 'The name must be text.',
            'name.max'       => 'The name cannot exceed 255 characters.',
            'email.email'    => 'Please provide a valid email.',
            'email.unique'   => 'This email is already in use.',
            'role.in'        => 'Role must be either admin or staff.',
            'password.min'   => 'Password must be at least 8 characters.',
        ]);

        try {
            $user = User::findOrFail($request->id);

            $oldValues = $user->only(['name', 'email', 'role', 'is_active']);

            if ($request->has('password')) {
                $request->merge(['password' => Hash::make($request->password)]);
            }

            $user->update($request->only(['name', 'email', 'password', 'role', 'is_active']));

            $newValues = $user->only(['name', 'email', 'role', 'is_active']);

            ActivityLog::log('user_updated', $user, $oldValues, $newValues);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user'    => $user->fresh(),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('User update failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user, please try again later.'
            ], 500);
        }
    }
}
