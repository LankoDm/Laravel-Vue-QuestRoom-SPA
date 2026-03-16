<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\ReviewRequest;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(string $roomId){
        $reviews = Review::with('user:id,name')
            ->where("room_id", $roomId)
            ->where('is_approved', true)->get();
        return response()->json($reviews);
    }

    public function store(ReviewRequest $request){
        $user = $request->user();
        $alreadyReviewed = Review::where('user_id', $user->id)->where('room_id', $request->room_id)->exists();
        if($alreadyReviewed){
            return response()->json([
                'message' => 'Ви вже залишали відгук на цю кімнату.'
            ], 422);
        }
        $review = Review::create([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
            'message' => $request->message,
            'rating' => $request->rating,
        ]);
        return response()->json([
            'message' => 'Дякуємо за відгук!',
            'review' => $review
        ], 201);
    }

    public function approve(string $id){
        $review = Review::findOrFail($id);
        $review->is_approved = true;
        $review->save();
        return response()->json([
            'message' => 'Відгук успішно схвалено та опубліковано!',
            'review' => $review
        ]);
    }

    public function destroy(string $id){
        $review = Review::findOrFail($id);
        $review->delete();
        return response()->json([
            'message' => 'Неприйнятний відгук видалено.'
        ]);
    }

    public function manageIndex(){
        $reviews = Review::with(['user:id,name', 'room:id,name'])
            ->orderBy('is_approved', 'asc')
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}
