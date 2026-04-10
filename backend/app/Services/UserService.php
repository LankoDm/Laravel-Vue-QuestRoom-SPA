<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    /**
     * Get users based on search criteria.
     *
     * @param Request $request
     * @return Collection
     */
    public function getFilteredUsers(Request $request): Collection
    {
        $query = User::query();

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        return $query->latest()->get();
    }

    /**
     * Update the role of a specific user.
     *
     * @param string $id
     * @param string $role
     * @return User
     */
    public function updateRole(string $id, string $role): User
    {
        $user = User::findOrFail($id);
        $user->update(['role' => $role]);

        return $user;
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    /**
     * Update the authenticated user's password.
     *
     * @param User $user
     * @param string $newPassword
     * @return void
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword)
        ]);
    }
}