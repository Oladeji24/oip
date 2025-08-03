<?php
// UserController.php
// Controller for user management: list users, get user details, flag/ban users

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // List all users (admin only)
    public function index(Request $request)
    {
        // Superadmin/admin check
        if (!$request->user() || !in_array($request->user()->role, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $users = User::all(['id', 'name', 'email', 'role', 'created_at', 'status']);
        return response()->json(['success' => true, 'users' => $users]);
    }

    // Get user details
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        return response()->json(['success' => true, 'user' => $user]);
    }

    // Flag or ban a user (admin only)
    public function flag(Request $request, $id)
    {
        // Superadmin/admin check
        if (!$request->user() || !in_array($request->user()->role, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $user->status = $request->input('status', 'flagged');
        $user->save();
        return response()->json(['success' => true, 'message' => 'User status updated']);
    }

    // Update user profile (email, password)
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->save();
        return response()->json(['email' => $user->email]);
    }

    // Promote user to admin (superadmin only)
    public function promote(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $target = User::find($id);
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $target->role = 'admin';
        $target->save();
        // Log action
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'promote',
            'details' => 'Promoted user ID ' . $id . ' to admin',
        ]);
        return response()->json(['success' => true, 'message' => 'User promoted to admin']);
    }

    // Demote admin to user (superadmin only)
    public function demote(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $target = User::find($id);
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $target->role = 'user';
        $target->save();
        // Log action
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'demote',
            'details' => 'Demoted user ID ' . $id . ' to user',
        ]);
        return response()->json(['success' => true, 'message' => 'User demoted to user']);
    }

    // Update user (admin/superadmin)
    public function update(Request $request, $id)
    {
        $auth = $request->user();
        if (!$auth || !in_array($auth->role, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'status' => 'sometimes|string',
            'role' => 'sometimes|string',
            'password' => 'nullable|min:6',
        ]);
        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (isset($data['status'])) $user->status = $data['status'];
        if (isset($data['role']) && $auth->role === 'superadmin') $user->role = $data['role'];
        if (!empty($data['password'])) $user->password = Hash::make($data['password']);
        $user->save();
        // Log action
        \App\Models\AuditLog::create([
            'user_id' => $auth->id,
            'action' => 'update',
            'details' => 'Updated user ID ' . $id,
        ]);
        return response()->json(['success' => true, 'user' => $user]);
    }

    // Impersonate user (superadmin only)
    public function impersonate(Request $request, $id)
    {
        $auth = $request->user();
        if (!$auth || $auth->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $target = User::find($id);
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        // Create a new token for the impersonated user
        $token = $target->createToken('impersonate_token')->plainTextToken;
        \App\Models\AuditLog::create([
            'user_id' => $auth->id,
            'action' => 'impersonate',
            'details' => 'Impersonated user ID ' . $id,
        ]);
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'user' => $target,
        ]);
    }
}
