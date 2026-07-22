<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    /** Mark one read and follow its deep link. */
    public function open(string $id)
    {
        $n = auth()->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $url = $n->data['url'] ?? null;

        return $url ? redirect($url) : redirect()->route('notifications.index');
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All caught up — notifications marked as read.');
    }

    public function clear()
    {
        auth()->user()->notifications()->delete();

        return back()->with('success', 'Notifications cleared.');
    }
}
