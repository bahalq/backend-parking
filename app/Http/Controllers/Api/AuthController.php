<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.invalid_credentials')],
            ]);
        }

        // Revoke old tokens and issue new one
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        $redirectHint = $user->role === 'Admin' ? 'admin' : ($user->role === 'Staff' ? 'staff' : null);

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        if ($user->role === 'Staff') {
            $userData['ground_id'] = $user->ground_id;
            $userData['ground_name'] = $user->ground?->name ?? null;
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.login_success'),
            'token' => $token,
            'user' => $userData,
            'redirect_hint' => $redirectHint,
        ]);
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password) || $user->role !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_credentials'),
                'errors' => [
                    'email' => [__('messages.invalid_credentials')],
                ],
            ], 403);
        }

        // Revoke old tokens and issue new one
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('messages.login_success'),
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'redirect_hint' => 'admin',
        ]);
    }

    public function staffLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password) || $user->role !== 'Staff') {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_credentials'),
                'errors' => [
                    'email' => [__('messages.invalid_credentials')],
                ],
            ], 403);
        }

        // Revoke old tokens and issue new one
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('messages.login_success'),
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'ground_id' => $user->ground_id,
                'ground_name' => $user->ground?->name ?? null,
            ],
            'redirect_hint' => 'staff',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => __('messages.logout_success')]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
            'cin' => $user->cin,
        ];

        if ($user->role === 'Staff') {
            $userData['ground_id'] = $user->ground_id;
            $userData['ground_name'] = $user->ground?->name ?? null;
        }

        return response()->json([
            'success' => true,
            'user' => $userData,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Staff Management (Admin-only)
    |--------------------------------------------------------------------------
    */

    public function createStaff(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'ground_id' => 'required|integer|exists:parking_zones,id',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Staff',
            'ground_id' => $request->ground_id,
        ]);

        // Send welcome email with credentials
        try {
            $loginUrl = rtrim(config('app.frontend_url', ''), '/') . '/#/staff/login';

            Mail::html(
                view('emails.staff_welcome', [
                    'firstName' => $user->first_name,
                    'email' => $user->email,
                    'password' => $request->password,
                    'groundName' => $user->ground?->name ?? 'N/A',
                    'loginUrl' => $loginUrl,
                ])->render(),
                function ($message) use ($user) {
                    $message
                        ->to($user->email)
                        ->subject(__('emails.staff_welcome_subject'));
                }
            );
        } catch (\Throwable $mailError) {
            \Illuminate\Support\Facades\Log::error('Staff welcome email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $mailError->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.staff_created'),
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'ground_id' => $user->ground_id,
                'ground_name' => $user->ground?->name ?? null,
            ],
        ], 201);
    }

    public function listStaff(Request $request)
    {
        $staff = User::where('role', 'Staff')
            ->with('ground:id,name')
            ->orderByDesc('created_at')
            ->paginate(15);

        $staff->getCollection()->transform(fn($u) => [
            'id' => $u->id,
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
            'email' => $u->email,
            'ground_id' => $u->ground_id,
            'ground_name' => $u->ground?->name ?? null,
            'created_at' => $u->created_at,
        ]);

        return response()->json(['success' => true, 'staff' => $staff]);
    }

    public function deleteStaff(Request $request, $id)
    {
        if ((int) $id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.cannot_delete_own_account'),
            ], 400);
        }

        $staff = User::where('role', 'Staff')->find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => __('messages.staff_not_found'),
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.staff_deleted'),
        ]);
    }

    public function groundsList()
    {
        $grounds = \App\Models\ParkingZone::select('id', 'name')->orderBy('name')->get();

        return response()->json(['success' => true, 'grounds' => $grounds]);
    }
}
