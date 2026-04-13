<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'difficulty',
        'image_path',
        'min_players',
        'max_players',
        'weekday_price',
        'weekend_price',
        'duration_minutes',
        'is_active',
        'slug',
        'age',
        'hint_phrase',
        'genre',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'weekend_price' => 'integer',
        'weekday_price' => 'integer',
        'max_players' => 'integer',
        'min_players' => 'integer',
    ];

    public  function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getPriceTotal(Carbon $date)
    {
        if($date->isWeekend()){
            return $this->weekend_price;
        }
        return $this->weekday_price;
    }

    public function getRouteKeyName(){
        return 'slug';
    }
}
