<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class TicketService
{
    /**
     * Build and return a downloadable PDF ticket for the booking.
     */
    public function downloadBookingTicket(Booking $booking): Response
    {
        $pdf = Pdf::loadView('emails.booking_confirmed', [
            'booking' => $booking,
            'isPdf' => true,
        ]);

        return $pdf->download("Ticket_Onea_Quests_{$booking->id}.pdf");
    }
}
