<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Notifications', ['notifications' => $request->user()->adminNotifications()->paginate(30)]);
    }

    public function menu(Request $request): JsonResponse
    {
        return response()->json($request->user()->adminNotifications()->limit(8)->get());
    }

    public function read(Request $request, AdminNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->read_at ??= now();
        $notification->save();

        return str_starts_with($notification->url, '/') || str_starts_with($notification->url, config('app.url'))
            ? redirect($notification->url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->adminNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
