<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroundController;
use App\Http\Controllers\Api\TerrainController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\DashboardController; // Import the new controller

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1');
Route::post('/staff/login', [AuthController::class, 'staffLogin'])->middleware('throttle:5,1');

// Grounds & Activities (public browsing)
Route::get('/grounds', [GroundController::class, 'index']);
Route::get('/grounds/{id}', [GroundController::class, 'show']);
Route::get('/activities', [GroundController::class, 'activities']);

// Terrains (public)
Route::get('/terrains', [TerrainController::class, 'index']);
Route::get('/terrains/by-activity', [TerrainController::class, 'byActivity']);
Route::get('/terrains/availability', [TerrainController::class, 'availability']);
Route::get('/terrains/month-availability', [TerrainController::class, 'monthAvailability']);

// Bookings (public: create + verify)
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:10,1');
Route::get('/bookings/verify', [BookingController::class, 'verify']);
Route::post('/bookings/confirm', [BookingController::class, 'confirmByCode']);
Route::post('/bookings/resend', [BookingController::class, 'resendCode']);

// Feedbacks (public)
Route::get('/feedbacks', [FeedbackController::class, 'index']);
Route::post('/feedbacks', [FeedbackController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Admin-only Routes (protected by AdminMiddleware)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('admin')->group(function () {

        // Grounds
        Route::post('/grounds', [GroundController::class, 'store']);
        Route::delete('/grounds/{id}', [GroundController::class, 'destroy']);

        // Terrains
        Route::post('/terrains', [TerrainController::class, 'store']);
        Route::patch('/terrains/{id}', [TerrainController::class, 'update']);
        Route::delete('/terrains/{id}', [TerrainController::class, 'destroy']);

        // Bookings
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
        Route::get('/clients', [BookingController::class, 'clients']);

        // Staff management
        Route::post('/staff', [AuthController::class, 'createStaff']);
        Route::get('/staff', [AuthController::class, 'listStaff']);
        Route::delete('/staff/{id}', [AuthController::class, 'deleteStaff']);
        Route::get('/grounds-list', [AuthController::class, 'groundsList']);

        // Admin Dashboard Stats
        Route::get('/stats', [DashboardController::class, 'adminStats']);
    });

    /*
    |--------------------------------------------------------------------------
    | Staff-only Routes (protected by StaffMiddleware)
    |--------------------------------------------------------------------------
    */
    Route::prefix('staff')->middleware('staff')->group(function () {

        // Dashboard
        Route::get('/dashboard', [BookingController::class, 'staffDashboard']);

        // Bookings list
        Route::get('/bookings', [BookingController::class, 'staffBookings']);

        // Single booking detail
        Route::get('/booking/{id}', [BookingController::class, 'staffBookingDetail']);

        // Verify ticket
        Route::get('/verify', [BookingController::class, 'staffVerify']);

        // Staff Dashboard Stats
        Route::get('/stats', [DashboardController::class, 'staffStats']);
    });
});
