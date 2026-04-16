<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class RequestReviewMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Booking $booking)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Як вам гра? Залиште відгук! | OneaQuests',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
        
        $signedApiUrl = URL::signedRoute('guest.review.store', ['booking' => $this->booking->id], now()->addDays(7));
        
        // Encode the signed API URL to pass it as a query param to frontend
        $frontendLink = $frontendUrl . '/' . $this->booking->room->slug . '?review_token=' . urlencode($signedApiUrl);

        return new Content(
            view: 'emails.request_review',
            with: [
                'frontendLink' => $frontendLink,
                'booking' => $this->booking,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
