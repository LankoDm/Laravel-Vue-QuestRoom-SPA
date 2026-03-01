<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'message',
        'rating',
        'is_approved',
    ];

    protected $casts = [
      'rating' => 'integer',
      'is_approved' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
