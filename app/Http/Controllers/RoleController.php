<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Models\UserRole;
use App\Models\RolePermission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|max:50|unique:roles',
            'role_description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $role = Role::create($request->all());

        return response()->json($role, 201);
    }

    public function show($id)
    {
        $role = Role::with(['permissions', 'users'])->find($id);

        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $request->validate([
            'role_name' => 'sometimes|required|string|max:50|unique:roles,role_name,' . $id . ',role_id',
            'role_description' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $role->update($request->all());

        return response()->json($role);
    }

    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'role_id' => 'required|exists:roles,role_id',
        ]);

        $existingAssignment = UserRole::where('user_id', $request->user_id)
            ->where('role_id', $request->role_id)
            ->first();

        if ($existingAssignment) {
            return response()->json(['error' => 'User already has this role'], 400);
        }

        $userRole = UserRole::create([
            'user_id' => $request->user_id,
            'role_id' => $request->role_id,
            'assigned_by' => $request->user()->user_id,
            'assigned_at' => now(),
        ]);

        return response()->json($userRole, 201);
    }

    public function revokeRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'role_id' => 'required|exists:roles,role_id',
        ]);

        $userRole = UserRole::where('user_id', $request->user_id)
            ->where('role_id', $request->role_id)
            ->first();

        if (!$userRole) {
            return response()->json(['error' => 'User does not have this role'], 400);
        }

        $userRole->delete();

        return response()->json(['message' => 'Role revoked successfully']);
    }

    public function assignPermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,role_id',
            'permission_id' => 'required|exists:permissions,permission_id',
        ]);

        $existingAssignment = RolePermission::where('role_id', $request->role_id)
            ->where('permission_id', $request->permission_id)
            ->first();

        if ($existingAssignment) {
            return response()->json(['error' => 'Role already has this permission'], 400);
        }

        $rolePermission = RolePermission::create([
            'role_id' => $request->role_id,
            'permission_id' => $request->permission_id,
            'assigned_by' => $request->user()->user_id,
            'assigned_at' => now(),
        ]);

        return response()->json($rolePermission, 201);
    }

    public function revokePermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,role_id',
            'permission_id' => 'required|exists:permissions,permission_id',
        ]);

        $rolePermission = RolePermission::where('role_id', $request->role_id)
            ->where('permission_id', $request->permission_id)
            ->first();

        if (!$rolePermission) {
            return response()->json(['error' => 'Role does not have this permission'], 400);
        }

        $rolePermission->delete();

        return response()->json(['message' => 'Permission revoked successfully']);
    }

    public function getUserRoles($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $roles = $user->roles()->with('permissions')->get();

        return response()->json($roles);
    }

    public function getRolePermissions($roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $permissions = $role->permissions()->get();

        return response()->json($permissions);
    }
}
