<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'comment',
        'payment_method',
        'payment_status',
        'start_time',
        'end_time',
        'players_count',
        'total_price',
        'status',
        'admin_note'
    ];

    protected $casts = [
      'players_count' => 'integer',
      'total_price' => 'integer',
      'start_time' => 'datetime',
      'end_time' => 'datetime',
    ];

    public function payment():HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

}
