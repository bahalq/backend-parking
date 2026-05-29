<?php

namespace App\Mail;

use App\Models\ParkingReservation;
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
        public ParkingReservation $booking,
        public string $verifyUrl,
        public string $userLocale = 'en',
    ) {
    }

    public function envelope(): Envelope
    {
        app()->setLocale($this->userLocale);

        return new Envelope(
            subject: __('emails.booking_subject'),
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['parkingSpot.parkingZone', 'driver']);

        $bookingDate = $this->booking->date
            ? Carbon::parse($this->booking->date)->locale($this->userLocale)->translatedFormat('l d F Y')
            : null;

        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($this->verifyUrl);

        return new Content(
            view: 'emails.booking_confirmed',
            with: [
                'clientName' => trim(
                    ($this->booking->driver?->first_name ?? '') . ' ' .
                    ($this->booking->driver?->last_name ?? '')
                ) ?: 'Driver',
                'groundName' => $this->booking->parkingSpot?->parkingZone?->name ?? 'Parking Zone',
                'terrainName' => $this->booking->parkingSpot?->name ?? 'Spot',
                'bookingDate' => $bookingDate ?? $this->booking->date,
                'timeSlot' => substr((string) $this->booking->start_time, 0, 5) . ' - ' . substr((string) $this->booking->end_time, 0, 5),
                'totalPrice' => $this->booking->total_price,
                'reference' => $this->booking->reference,
                'qrImageUrl' => $qrImageUrl,
                'locale' => $this->userLocale,
            ],
        );
    }
}
