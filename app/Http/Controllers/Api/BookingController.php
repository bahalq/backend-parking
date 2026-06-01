<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingReservation;
use App\Models\Driver;
use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use App\Mail\BookingConfirmed;
use App\Mail\ReservationConfirmationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Public API: Submit a booking/reservation checkout.
     * Accepts both frontend formats:
     *   - Legacy: client_first_name, client_last_name, start_time (single)
     *   - New:    first_name, last_name, slots (array of hours)
     */
    public function store(Request $request)
    {
        $request->validate([
            'terrain_id' => 'required|integer|exists:parking_spots,id',
            'date' => 'required|date_format:Y-m-d',
            // Accept either slots array or single start_time
            'slots' => 'nullable|array',
            'start_time' => 'nullable|string',
            // Accept both prefixed and unprefixed field names
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'license_plate' => 'nullable|string|max:20',
            'client_first_name' => 'nullable|string|max:100',
            'client_last_name' => 'nullable|string|max:100',
            'client_email' => 'nullable|email|max:150',
            'client_phone' => 'nullable|string|max:20',
        ]);

        $spot = ParkingSpot::findOrFail($request->terrain_id);

        // Resolve time range from either slots array or start_time string
        if ($request->filled('slots')) {
            $startHour = min($request->slots);
            $endHour = max($request->slots) + 1;
            $hoursCount = count($request->slots);
        } else {
            // Frontend sends a single time string like "08:00"
            $startHour = (int) substr($request->start_time, 0, 2);
            $endHour = $startHour + 1;
            $hoursCount = 1;
        }

        $startTime = sprintf('%02d:00:00', $startHour);
        $endTime = sprintf('%02d:00:00', $endHour);

        // Verify no overlapping bookings exist for this spot
        $overlap = ParkingReservation::where('parking_spot_id', $spot->id)
            ->where('date', $request->date)
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(fn($sub) => $sub->where('start_time', '>=', $startTime)->where('start_time', '<', $endTime))
                  ->orWhere(fn($sub) => $sub->where('end_time', '>', $startTime)->where('end_time', '<=', $endTime))
                  ->orWhere(fn($sub) => $sub->where('start_time', '<=', $startTime)->where('end_time', '>=', $endTime));
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'The selected parking spot is already reserved for this timeframe.',
            ], 422);
        }

        // Resolve client fields (support both prefixed and unprefixed)
        $firstName = $request->input('client_first_name', $request->input('first_name'));
        $lastName = $request->input('client_last_name', $request->input('last_name'));
        $email = $request->input('client_email', $request->input('email'));
        $phone = $request->input('client_phone', $request->input('phone'));
        $licensePlate = $request->input('license_plate', '');

        // Register/Find Driver
        $driver = Driver::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'license_plate' => strtoupper($licensePlate),
            ]
        );

        $totalPrice = $spot->price_per_hour * $hoursCount;
        $reference = 'PRK-' . strtoupper(Str::random(8));
        $verificationCode = sprintf('%06d', mt_rand(100000, 999999));

        $reservation = ParkingReservation::create([
            'parking_spot_id' => $spot->id,
            'driver_id' => $driver->id,
            'date' => $request->date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'total_price' => $totalPrice,
            'status' => 'Pending',
            'reference' => $reference,
            'verification_code' => $verificationCode,
        ]);

        // Temporarily change spot status to Reserved
        $spot->update(['status' => 'Reserved']);

        // Send verification code email to the driver
        if ($email) {
            try {
                $locale = $request->header('Accept-Language', 'en');
                $locale = in_array($locale, ['en', 'fr', 'ar']) ? $locale : 'en';

                Mail::to($email)->send(new ReservationConfirmationRequest($reservation, $locale));

                Log::info('Mail dispatched', [
                    'context' => 'reservation_confirmation_request',
                    'recipient' => $email,
                    'mailer' => config('mail.default'),
                    'queued' => false,
                    'meta' => [
                        'reservation_id' => $reservation->id,
                        'reference' => $reservation->reference,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Mail delivery failed', [
                    'context' => 'reservation_confirmation_request',
                    'recipient' => $email,
                    'mailer' => config('mail.default'),
                    'queued' => false,
                    'error' => $e->getMessage(),
                    'meta' => [
                        'reservation_id' => $reservation->id,
                        'reference' => $reservation->reference,
                    ],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Parking spot reserved successfully. Please verify your reservation.',
            'pending_confirmation' => true,
            'booking_id' => $reservation->reference,
            'booking' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'total_price' => $reservation->total_price,
                'verification_code' => $reservation->verification_code,
            ],
        ], 201);
    }

    /**
     * Public API: Verify and fetch booking status by reference and verification code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'code' => 'required|string',
        ]);

        $reservation = ParkingReservation::with(['parkingSpot.parkingZone', 'driver'])
            ->where('reference', $request->reference)
            ->where('verification_code', $request->code)
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reference or verification code.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'date' => $reservation->date,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'total_price' => $reservation->total_price,
                'status' => $reservation->status,
                'ground_name' => $reservation->parkingSpot->parkingZone->name,
                'terrain_name' => $reservation->parkingSpot->name,
                'client_name' => $reservation->driver->first_name . ' ' . $reservation->driver->last_name,
                'license_plate' => $reservation->driver->license_plate,
            ],
        ]);
    }

    /**
     * Public API: Confirm reservation by typing the verification code.
     */
    public function confirmByCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        // Accept both 'reference' and 'token' field names
        $reference = $request->input('reference', $request->input('token'));

        $reservation = ParkingReservation::where('reference', $reference)
            ->where('verification_code', $request->code)
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Code mismatch.',
            ], 422);
        }

        $reservation->update(['status' => 'Confirmed']);
        $reservation->loadMissing(['parkingSpot.parkingZone', 'driver']);

        // Send confirmation email with QR code
        $driverEmail = $reservation->driver?->email;
        if ($driverEmail) {
            try {
                $locale = $request->header('Accept-Language', 'en');
                $locale = in_array($locale, ['en', 'fr', 'ar']) ? $locale : 'en';

                $verifyUrl = config('app.url') . '/api/bookings/verify/' . $reservation->reference;

                // Re-use the BookingConfirmed mailable (it expects a Booking model,
                // but we adapt by passing the reservation as a dynamic wrapper)
                Mail::to($driverEmail)->send(
                    new BookingConfirmed($reservation, $verifyUrl, $locale)
                );

                Log::info('Confirmation email dispatched', [
                    'context' => 'booking_confirmed',
                    'recipient' => $driverEmail,
                    'reservation_id' => $reservation->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Confirmation email failed', [
                    'context' => 'booking_confirmed',
                    'recipient' => $driverEmail,
                    'error' => $e->getMessage(),
                    'reservation_id' => $reservation->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Parking reservation confirmed successfully.',
        ]);
    }

    /**
     * Public API: Resend the verification code via email.
     */
    public function resendCode(Request $request)
    {
        // Accept both 'reference' and 'token' field names
        $reference = $request->input('reference', $request->input('token'));

        $reservation = ParkingReservation::with(['parkingSpot.parkingZone', 'driver'])
            ->where('reference', $reference)
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found.',
            ], 404);
        }

        if ($reservation->status === 'Confirmed' || $reservation->status === 'Completed') {
            return response()->json([
                'success' => false,
                'message' => 'This reservation is already confirmed.',
            ], 422);
        }

        // Generate a fresh verification code
        $newCode = sprintf('%06d', mt_rand(100000, 999999));
        $reservation->update(['verification_code' => $newCode]);

        // Send the email
        $driverEmail = $reservation->driver?->email;
        if ($driverEmail) {
            try {
                $locale = $request->header('Accept-Language', 'en');
                $locale = in_array($locale, ['en', 'fr', 'ar']) ? $locale : 'en';

                Mail::to($driverEmail)->send(
                    new ReservationConfirmationRequest($reservation, $locale)
                );

                Log::info('Verification code resent', [
                    'context' => 'resend_verification_code',
                    'recipient' => $driverEmail,
                    'reservation_id' => $reservation->id,
                    'new_code' => $newCode,
                ]);
            } catch (\Throwable $e) {
                Log::error('Resend verification email failed', [
                    'context' => 'resend_verification_code',
                    'recipient' => $driverEmail,
                    'error' => $e->getMessage(),
                    'reservation_id' => $reservation->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resend verification email. Please try again.',
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No email address on file for this reservation.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code resent successfully.',
        ]);
    }

    /**
     * Admin-only API: List all bookings.
     */
    public function index(Request $request)
    {
        $query = ParkingReservation::with(['parkingSpot.parkingZone', 'driver']);

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('ground_id')) {
            $query->whereHas('parkingSpot', function ($q) use ($request) {
                $q->where('parking_zone_id', $request->ground_id);
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        // Map for grounds-compatible format in React frontend
        $bookings->getCollection()->transform(function ($b) {
            return [
                'id' => $b->id,
                'reference' => $b->reference,
                'date' => $b->date,
                'start_time' => $b->start_time,
                'end_time' => $b->end_time,
                'total_price' => $b->total_price,
                'status' => $b->status,
                'terrain' => [
                    'name' => $b->parkingSpot->name,
                    'ground' => [
                        'name' => $b->parkingSpot->parkingZone->name,
                    ]
                ],
                'client' => [
                    'first_name' => $b->driver->first_name,
                    'last_name' => $b->driver->last_name,
                    'email' => $b->driver->email,
                    'phone' => $b->driver->phone,
                    'license_plate' => $b->driver->license_plate,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }

    /**
     * Admin/Staff API: Cancel a booking.
     */
    public function cancel(Request $request, $id)
    {
        $reservation = ParkingReservation::with(['parkingSpot.parkingZone', 'driver'])->findOrFail($id);
        $reservation->update(['status' => 'Cancelled']);

        $spot = $reservation->parkingSpot;
        if ($spot && $spot->status === 'Reserved') {
            $spot->update(['status' => 'Available']);
        }

        // Send cancellation notification email
        $driverEmail = $reservation->driver?->email;
        if ($driverEmail) {
            try {
                $locale = $request->header('Accept-Language', 'en');
                $locale = in_array($locale, ['en', 'fr', 'ar']) ? $locale : 'en';

                $spotName = $reservation->parkingSpot?->name ?? 'Spot';
                $zoneName = $reservation->parkingSpot?->parkingZone?->name ?? 'Zone';
                $driverName = trim(
                    ($reservation->driver?->first_name ?? '') . ' ' .
                    ($reservation->driver?->last_name ?? '')
                ) ?: 'Driver';

                Mail::raw(
                    "Dear {$driverName},\n\n" .
                    "Your parking reservation (Ref: {$reservation->reference}) has been cancelled.\n\n" .
                    "Details:\n" .
                    "- Zone: {$zoneName}\n" .
                    "- Spot: {$spotName}\n" .
                    "- Date: {$reservation->date}\n" .
                    "- Time: " . substr((string) $reservation->start_time, 0, 5) . " - " . substr((string) $reservation->end_time, 0, 5) . "\n\n" .
                    "If you did not request this cancellation, please contact support.\n\n" .
                    "— Parkova Team",
                    function ($message) use ($driverEmail) {
                        $message->to($driverEmail)
                            ->subject('Reservation Cancelled — Parkova');
                    }
                );

                Log::info('Cancellation email sent', [
                    'context' => 'booking_cancelled',
                    'recipient' => $driverEmail,
                    'reservation_id' => $reservation->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Cancellation email failed', [
                    'context' => 'booking_cancelled',
                    'recipient' => $driverEmail,
                    'error' => $e->getMessage(),
                    'reservation_id' => $reservation->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Parking reservation cancelled successfully.',
        ]);
    }

    /**
     * Admin-only API: List all drivers.
     */
    public function clients(Request $request)
    {
        $drivers = Driver::orderBy('first_name')->paginate(15);

        return response()->json([
            'success' => true,
            'clients' => $drivers,
        ]);
    }

    /**
     * Staff-only API: Load the staff facility dashboard parameters.
     */
    public function staffDashboard(Request $request)
    {
        $user = $request->user();
        $zoneId = $user->parking_zone_id;

        if (!$zoneId) {
            return response()->json([
                'success' => false,
                'message' => 'Staff is not assigned to any parking zone.',
            ], 403);
        }

        $today = Carbon::today()->toDateString();

        $totalToday = ParkingReservation::whereHas('parkingSpot', fn($q) => $q->where('parking_zone_id', $zoneId))
            ->where('date', $today)
            ->count();

        $pendingToday = ParkingReservation::whereHas('parkingSpot', fn($q) => $q->where('parking_zone_id', $zoneId))
            ->where('date', $today)
            ->where('status', 'Confirmed')
            ->whereNull('confirmed_at')
            ->count();

        $occupiedCount = ParkingSpot::where('parking_zone_id', $zoneId)
            ->where('status', 'Occupied')
            ->count();

        $totalSpots = ParkingSpot::where('parking_zone_id', $zoneId)->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_bookings_today' => $totalToday,
                'pending_checkins' => $pendingToday,
                'occupied_spots' => $occupiedCount,
                'total_spots' => $totalSpots,
            ],
        ]);
    }

    /**
     * Staff-only API: List bookings assigned to the staff's parking zone.
     */
    public function staffBookings(Request $request)
    {
        $user = $request->user();
        $zoneId = $user->parking_zone_id;

        if (!$zoneId) {
            return response()->json([
                'success' => false,
                'message' => 'Staff is not assigned to any parking zone.',
            ], 403);
        }

        $bookings = ParkingReservation::with(['parkingSpot', 'driver'])
            ->whereHas('parkingSpot', fn($q) => $q->where('parking_zone_id', $zoneId))
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        // Map for grounds-compatible format in React frontend
        $bookings->getCollection()->transform(function ($b) {
            return [
                'id' => $b->id,
                'reference' => $b->reference,
                'date' => $b->date,
                'start_time' => $b->start_time,
                'end_time' => $b->end_time,
                'total_price' => $b->total_price,
                'status' => $b->status,
                'terrain' => [
                    'name' => $b->parkingSpot->name,
                ],
                'client' => [
                    'first_name' => $b->driver->first_name,
                    'last_name' => $b->driver->last_name,
                    'phone' => $b->driver->phone,
                    'license_plate' => $b->driver->license_plate,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }

    /**
     * Staff-only API: Load detailed ticket info.
     */
    public function staffBookingDetail(Request $request, $id)
    {
        $user = $request->user();
        $zoneId = $user->parking_zone_id;

        $booking = ParkingReservation::with(['parkingSpot', 'driver'])->findOrFail($id);

        if ($zoneId && $booking->parkingSpot->parking_zone_id !== $zoneId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this parking facility.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'date' => $booking->date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'confirmed_at' => $booking->confirmed_at,
                'terrain' => [
                    'name' => $booking->parkingSpot->name,
                ],
                'client' => [
                    'first_name' => $booking->driver->first_name,
                    'last_name' => $booking->driver->last_name,
                    'phone' => $booking->driver->phone,
                    'email' => $booking->driver->email,
                    'license_plate' => $booking->driver->license_plate,
                ]
            ],
        ]);
    }

    /**
     * Staff-only API: Scan ticket QR or verify reference code to perform check-in.
     */
    public function staffVerify(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $user = $request->user();
        $zoneId = $user->parking_zone_id;

        $reservation = ParkingReservation::with(['parkingSpot.parkingZone', 'parkingSpot.vehicleCategory', 'driver'])
            ->where('reference', $request->reference)
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket reference not found.',
            ], 404);
        }

        if ($zoneId && $reservation->parkingSpot->parking_zone_id !== $zoneId) {
            return response()->json([
                'success' => false,
                'message' => 'This ticket belongs to a different parking facility.',
            ], 403);
        }

        // Perform vehicle check-in (Entry log)
        if ($reservation->status === 'Confirmed' && is_null($reservation->confirmed_at)) {
            $reservation->update([
                'status' => 'Completed',
                'confirmed_at' => Carbon::now(),
            ]);

            // Update spot to Occupied
            $spot = $reservation->parkingSpot;
            $spot->update(['status' => 'Occupied']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket verified and checked-in successfully.',
            'booking' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'date' => $reservation->date,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'total_price' => $reservation->total_price,
                'status' => $reservation->status,
                'confirmed_at' => $reservation->confirmed_at,
                'ground_name' => $reservation->parkingSpot->parkingZone?->name,
                'terrain_name' => $reservation->parkingSpot->name,
                'activity_name' => $reservation->parkingSpot->vehicleCategory?->name,
                'first_name' => $reservation->driver->first_name,
                'last_name' => $reservation->driver->last_name,
                'client_name' => $reservation->driver->first_name . ' ' . $reservation->driver->last_name,
                'email' => $reservation->driver->email,
                'phone' => $reservation->driver->phone,
                'license_plate' => $reservation->driver->license_plate,
            ],
        ]);
    }
}
