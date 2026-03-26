<?php

namespace App\Jobs;

use App\Events\BookingCreated;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FinishBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $bookingId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $booking = Booking::find($this->bookingId);
        if ($booking && $booking->status === 'confirmed') {
            $booking->status = 'finished';
            $booking->save();
            broadcast(new BookingCreated($booking));
        }
    }
}
