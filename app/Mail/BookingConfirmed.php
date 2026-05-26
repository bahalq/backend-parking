<?php

namespace App\Mail;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $verifyUrl,
        public string $locale = 'fr',
    ) {
    }

    public function envelope(): Envelope
    {
        // Set locale for translation
        app()->setLocale($this->locale);

        return new Envelope(
            subject: __('emails.booking_subject'),
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['terrain.ground', 'client']);

        $bookingDate = $this->booking->date
            ? Carbon::parse($this->booking->date)->locale($this->locale)->translatedFormat('l d F Y')
            : null;

        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($this->verifyUrl);

        return new Content(
            view: 'emails.booking_confirmed',
            with: [
                'clientName' => trim(($this->booking->client?->first_name ?? '') . ' ' . ($this->booking->client?->last_name ?? '')) ?: 'Client',
                'groundName' => $this->booking->terrain?->ground?->name ?? 'Terrain',
                'terrainName' => $this->booking->terrain?->name ?? 'Pitch',
                'bookingDate' => $bookingDate ?? $this->booking->date,
                'timeSlot' => substr((string) $this->booking->start_time, 0, 5) . ' - ' . substr((string) $this->booking->end_time, 0, 5),
                'totalPrice' => $this->booking->total_price,
                'reference' => $this->booking->reference,
                'qrImageUrl' => $qrImageUrl,
                'locale' => $this->locale,
            ],
        );
    }
}
