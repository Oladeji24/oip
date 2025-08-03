<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\TransactionLogger;

class AuthController extends Controller
{
    protected $transactionLogger;

    public function __construct(TransactionLogger $transactionLogger)
    {
        $this->transactionLogger = $transactionLogger;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create default wallet for user
        DB::table("user_wallets")->insert([
            "user_id" => $user->id,
            "currency" => "USD",
            "balance" => 0,
            "locked" => 0,
            "created_at" => now(),
            "updated_at" => now()
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->transactionLogger->logAction($user->id, 'registered', [
            'email' => $user->email
        ]);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->transactionLogger->logAction($user->id, 'login', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user (Revoke the token)
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        $this->transactionLogger->logAction($user->id, 'logout', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     */
    public function user(Request $request)
    {
        $user = $request->user();

        // Get user wallets
        $wallets = DB::table("user_wallets")
            ->where("user_id", $user->id)
            ->get();

        return response()->json([
            'user' => $user,
            'wallets' => $wallets
        ]);
    }
}
