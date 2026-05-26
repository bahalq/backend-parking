<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\ParkingZone;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * GET /api/feedbacks — Return all feedbacks ordered by latest with ground name if exists.
     */
    public function index()
    {
        $feedbacks = Feedback::with(['ground:id,name'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'rating' => $f->rating,
                    'message' => $f->message,
                    'name' => $f->name,
                    'ground_id' => $f->ground_id,
                    'ground_name' => $f->ground?->name,
                    'created_at' => $f->created_at,
                ];
            });

        return response()->json(['success' => true, 'feedbacks' => $feedbacks]);
    }

    /**
     * POST /api/feedbacks — Store a new feedback (no auth required).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|min:10',
            'name' => 'nullable|string|max:255',
            'ground_id' => 'nullable|exists:parking_zones,id',
        ]);

        // Set default name if not provided
        if (empty($validated['name'])) {
            $validated['name'] = 'Anonyme';
        }

        $feedback = Feedback::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.feedback_submitted'),
            'feedback' => [
                'id' => $feedback->id,
                'rating' => $feedback->rating,
                'message' => $feedback->message,
                'name' => $feedback->name,
                'ground_id' => $feedback->ground_id,
                'ground_name' => $feedback->ground?->name,
                'created_at' => $feedback->created_at,
            ],
        ], 201);
    }
}
