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
     */
    public function getFilteredUsers(?string $emailSearch = null): Collection
    {
        $query = User::query();

        if ($emailSearch) {
            $query->where('email', 'like', '%' . $emailSearch . '%');
        }

        return $query->latest()->get();
    }

    /**
     * Update the role of a specific user.
     */
    public function updateRole(string $id, string $role): User
    {
        $user = User::findOrFail($id);
        $user->update(['role' => $role]);

        return $user;
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword)
        ]);
    }
}