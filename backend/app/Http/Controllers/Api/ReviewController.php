<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

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
     * Store a newly created review.
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
    public function manageIndex(): JsonResponse
    {
        $reviews = $this->reviewService->getAllForManagement();

        return response()->json($reviews);
    }
}