<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\AdminNotification;
use App\Service\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    protected $notificationService;

    public function __construct() {
        $this->notificationService = new NotificationService;
    }

    public function index()
    {
        $feedbacks = Feedback::with('user:id,name')
            ->where('rating', '>=', 4)
            ->latest()
            ->limit(6)
            ->get();
        return response()->json($feedbacks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'rating' => 'required|numeric|min:1|max:5',
            'message' => 'required|string|min:10',
        ]);

        $feedback = Feedback::create([
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'rating' => $validated['rating'],
            'message' => $validated['message'],
        ]);

        // Notify admin about new feedback
        $userName = Auth::user()->name;
        $message = "New feedback received from {$userName}!\n\n" .
                   "Subject: {$feedback->subject}\n" .
                   "Rating: {$feedback->rating}/5\n" .
                   "Message: {$feedback->message}";

        $this->notificationService->notifyAdmin(
            'feedback', // Custom type or reuse one if applicable
            $message
        );

        return response()->json([
            'msg' => 'Feedback submitted successfully',
            'feedback' => $feedback,
        ], 201);
    }
}
