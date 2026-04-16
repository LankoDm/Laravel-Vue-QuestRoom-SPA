<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Payments\PaymentController;
use App\Http\Controllers\Api\Payments\StripeWebhookController;
use App\Http\Controllers\Api\Resources\BookingController;
use App\Http\Controllers\Api\Resources\ReviewController;
use App\Http\Controllers\Api\Resources\RoomController;
use App\Http\Controllers\Api\Resources\UserController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/rooms/{room}/reviews', [ReviewController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::post('/bookings/hold', [BookingController::class, 'holdSlot']);
Route::post('/bookings/release', [BookingController::class, 'releaseSlot']);
Route::post('/bookings/{booking}/pay', [PaymentController::class, 'createCheckoutSession']);
Route::post('/bookings/{booking}/review', [ReviewController::class, 'storeGuest'])->name('guest.review.store')->middleware('signed');
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
Route::get('/bookings/{booking}/ticket', [BookingController::class, 'downloadTicket'])->name('ticket.download')->middleware('signed');
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);
    Route::get('/user/bookings', [BookingController::class, 'myBookings']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::middleware([CheckManager::class])->group(function () {
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::patch('/bookings/{id}/confirm', [BookingController::class, 'bookingConfirmation']);
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'bookingCancellation']);
        Route::patch('/bookings/{id}/finish', [BookingController::class, 'bookingFinish']);
        Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
        Route::get('/reviews', [ReviewController::class, 'manageIndex']);
    });

    Route::middleware([CheckAdmin::class])->group(function () {
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);

        Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
        Route::patch('/bookings/{booking}/note', [BookingController::class, 'updateNote']);
        Route::get('/admin/stats', [DashboardController::class, 'stats']);
        Route::get('/admin/report/pdf', [DashboardController::class, 'downloadPdfReport']);
    });
});
