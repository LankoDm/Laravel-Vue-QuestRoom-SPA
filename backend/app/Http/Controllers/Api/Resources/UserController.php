<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->getFilteredUsers(
            $request->input('email'),
            $request->input('per_page', 20)
        );

        return response()->json($users);
    }

    /**
     * Update a user's role (Admin/Manager only).
     */
    public function updateRole(UserRequest $request, string $id): JsonResponse
    {
        $user = $this->userService->updateRole($id, $request->validated('role'));

        return response()->json([
            'message' => 'Роль успішно оновлено',
            'user' => $user
        ]);
    }

    /**
     * Update the authenticated user's profile information.
     */
    public function updateProfile(UserRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'message' => 'Профіль успішно оновлено',
            'user' => $user
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->userService->updatePassword($request->user(), $validated['password']);

        return response()->json(['message' => 'Пароль успішно змінено']);
    }
}