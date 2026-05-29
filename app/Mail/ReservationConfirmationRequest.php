<?php

namespace App\Mail;

use App\Models\ParkingReservation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmationRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ParkingReservation $reservation,
        public string $userLocale = 'en',
    ) {
    }

    public function envelope(): Envelope
    {
        app()->setLocale($this->userLocale);

        return new Envelope(
            subject: __('emails.booking_confirmation_request_subject'),
        );
    }

    public function content(): Content
    {
        $this->reservation->loadMissing(['parkingSpot.parkingZone', 'driver']);

        $bookingDate = $this->reservation->date
            ? Carbon::parse($this->reservation->date)->locale($this->userLocale)->translatedFormat('l d F Y')
            : null;

        $expiryTime = Carbon::parse($this->reservation->created_at)->addMinutes(30);
        $expiryFormatted = $expiryTime->locale($this->userLocale)->translatedFormat('H:i - l d F Y');

        return new Content(
            view: 'emails.booking_confirmation_request',
            with: [
                'clientName' => trim(
                    ($this->reservation->driver?->first_name ?? '') . ' ' .
                    ($this->reservation->driver?->last_name ?? '')
                ) ?: 'Driver',
                'groundName' => $this->reservation->parkingSpot?->parkingZone?->name ?? 'Parking Zone',
                'terrainName' => $this->reservation->parkingSpot?->name ?? 'Spot',
                'bookingDate' => $bookingDate ?? $this->reservation->date,
                'timeSlot' => substr((string) $this->reservation->start_time, 0, 5) . ' - ' . substr((string) $this->reservation->end_time, 0, 5),
                'confirmationCode' => $this->reservation->verification_code,
                'expiryFormatted' => $expiryFormatted,
                'locale' => $this->userLocale,
            ],
        );
    }
}
