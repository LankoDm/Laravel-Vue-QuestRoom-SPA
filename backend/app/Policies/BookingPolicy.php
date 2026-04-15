<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     * (e.g., in index method)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * (e.g., in show method)
     */
    public function view(?User $user, Booking $booking): bool
    {
        if ($user && ($user->isAdmin() || $user->isManager())) {
            return true;
        }

        if ($booking->user_id !== null) {
            return $user && $user->id === $booking->user_id;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}