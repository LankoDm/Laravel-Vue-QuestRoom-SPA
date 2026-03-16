<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->has('email') && $request->email !== '') {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        $users = $query->latest()->paginate(20);
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
}
