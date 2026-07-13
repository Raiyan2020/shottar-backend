<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

class notificationsController extends Controller
{

    //index dataTable
    public function index(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.notifications.index');
    }
    //create
    public function create()
    {
        return view('dashboard.admin.notifications.create');
    }
    //store
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);
        //sent notification to all users   FirebaseService
        $firebase = new FirebaseService();
        $send =$firebase->sendNotificationToTopic('announcements', $request->title, $request->body);
        Notification::create([
            'title' => $request->title,
            'body' => $request->body,
            'type' => 'all',
        ]);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification sent successfully.');
    }

}
