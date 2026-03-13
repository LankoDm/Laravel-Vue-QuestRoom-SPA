<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request){
        $validatedData = $request->validated();
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Реєстрація успішна',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(LoginRequest $request){
        $validatedData = $request->validated();
        $user = User::where('email', $validatedData['email'])->first();
        if(!$user || !Hash::check($validatedData['password'], $user->password)){
            return response()->json([
                'message' => 'Невірний email або пароль'
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Ви успішно увійшли',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Ви успішно вийшли з системи'
        ]);
    }
}
