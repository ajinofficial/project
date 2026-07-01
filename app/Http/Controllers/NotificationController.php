<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), ['unread', 'read'], true)
            ? $request->query('filter')
            : 'all';
        $baseQuery = Notification::where('tenant_id', $request->user()->tenant_id);

        return view('notifications.index', [
            'filter' => $filter,
            'allCount' => (clone $baseQuery)->count(),
            'unreadCount' => (clone $baseQuery)->whereNull('read_at')->count(),
            'readCount' => (clone $baseQuery)->whereNotNull('read_at')->count(),
            'notifications' => (clone $baseQuery)
                ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->when($filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
                ->latest()
                ->paginate(15)
                ->appends(['filter' => $filter]),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless((int) $notification->tenant_id === (int) $request->user()->tenant_id, 404);

        $notification->update(['read_at' => now()]);

        return redirect()
            ->route('notifications.index', $request->only('filter'))
            ->with('status', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::where('tenant_id', $request->user()->tenant_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()
            ->route('notifications.index', $request->only('filter'))
            ->with('status', 'All notifications marked as read.');
    }
}
