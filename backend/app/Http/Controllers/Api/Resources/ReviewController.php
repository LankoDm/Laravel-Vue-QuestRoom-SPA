<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use \Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    /**
     * Inject the ReviewService.
     */
    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Display approved reviews for a specific room.
     */
    public function index(string $roomId): JsonResponse
    {
        $reviews = $this->reviewService->getApprovedByRoom($roomId);

        return response()->json($reviews);
    }

    /**
     * Store a newly created review by an authenticated user.
     */
    public function store(ReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->createReview($request->validated(), $request->user());

        return response()->json([
            'message' => 'Дякуємо за відгук!',
            'review' => $review
        ], 201);
    }

    /**
     * Store a guest review via signed token.
     */
    public function storeGuest(ReviewRequest $request, \App\Models\Booking $booking): JsonResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Посилання недійсне або його термін дії минув.');
        }

        $review = $this->reviewService->createGuestReview($request->validated(), $booking);

        return response()->json([
            'message' => 'Дякуємо за ваш відгук! Він з\'явиться на сайті після схвалення.',
            'review' => $review
        ], 201);
    }

    /**
     * Approve a specific review.
     */
    public function approve(string $id): JsonResponse
    {
        $review = $this->reviewService->approveReview($id);

        return response()->json([
            'message' => 'Відгук успішно схвалено та опубліковано!',
            'review' => $review
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->reviewService->deleteReview($id);

        return response()->json([
            'message' => 'Неприйнятний відгук видалено.'
        ]);
    }

    /**
     * Display all reviews for the manager dashboard.
     */
    public function manageIndex(Request $request): JsonResponse
    {
        $reviews = $this->reviewService->getAllForManagement($request);

        return response()->json($reviews);
    }
}