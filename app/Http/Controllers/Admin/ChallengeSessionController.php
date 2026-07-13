<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ChallengeSessionDataTable;
use App\Http\Controllers\Controller;
use App\Models\ChallengeUserSession;
use Illuminate\Http\Request;

class ChallengeSessionController extends Controller
{
    //index
    public function index(ChallengeSessionDataTable $dataTable)
    {

        return $dataTable->render('dashboard.admin.challenges.sessions.index');
    }
    //show
    public function show($id)
    {
        $session = ChallengeUserSession::with([
            'answers.answers',
            'answers.question',
            'user'
        ])
            ->findOrFail($id);
//        return $session;

        return view('dashboard.admin.challenges.sessions.show', compact('session'));
    }

}
