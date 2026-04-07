<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->has('email') && $request->email !== '') {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        $users = $query->latest()->get();
        return response()->json($users);
    }

    public function updateRole(UserRequest $request, string $id)
    {
        $validateData = $request->validated();
        $user = User::findOrFail($id);
        $user->role = $validateData['role'];
        $user->save();
        return response()->json([
            'message' => 'Роль успішно оновлено',
            'user' => $user
        ]);
    }
    public function updateProfile(UserRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $user->update($validated);
        return response()->json([
            'message' => 'Профіль успішно оновлено',
            'user' => $user
        ]);
    }
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);
        return response()->json(['message' => 'Пароль успішно змінено']);
    }
}
