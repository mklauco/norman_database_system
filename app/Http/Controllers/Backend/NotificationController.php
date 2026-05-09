<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        // Middleware is already applied at route level
    }

    /**
     * Convert datetime from Central European Time to UTC for database storage
     */
    private function convertToUtc(string $datetime): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i', $datetime, 'Europe/Prague')->utc();
    }

    /**
     * Convert datetime from UTC to Central European Time for display
     */
    private function convertToCet(Carbon $datetime): string
    {
        return $datetime->setTimezone('Europe/Prague')->format('Y-m-d\TH:i');
    }

    public function index()
    {
        $notifications = Notification::with(['createdBy', 'turnedOffBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('backend.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('backend.notifications.upsert');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'is_active' => 'boolean',
        ]);

        // Convert CET times to UTC for database storage
        $startUtc = $this->convertToUtc($request->start_datetime);
        $endUtc = $this->convertToUtc($request->end_datetime);

        Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'start_datetime' => $startUtc,
            'end_datetime' => $endUtc,
            'is_active' => $request->boolean('is_active', true),
            'created_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('backend.notifications.index')
            ->with('success', 'Notification created successfully.');
    }

    public function edit(Notification $notification)
    {
        return view('backend.notifications.upsert', compact('notification'));
    }

    public function update(Request $request, Notification $notification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'is_active' => 'boolean',
        ]);

        // Convert CET times to UTC for database storage
        $startUtc = $this->convertToUtc($request->start_datetime);
        $endUtc = $this->convertToUtc($request->end_datetime);

        $notification->update([
            'title' => $request->title,
            'message' => $request->message,
            'start_datetime' => $startUtc,
            'end_datetime' => $endUtc,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.notifications.index')
            ->with('success', 'Notification updated successfully.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('backend.notifications.index')
            ->with('success', 'Notification deleted successfully.');
    }

    public function turnOff(Notification $notification)
    {
        $notification->turnOff(Auth::id());

        return redirect()->route('backend.notifications.index')
            ->with('success', 'Notification turned off successfully.');
    }
}
