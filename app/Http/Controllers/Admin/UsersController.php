<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\AdminsDataTable;
use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    //index datatable

    public function index(UsersDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.users.index');
    }
    //edit
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('dashboard.admin.users.edit', compact('user'));
    }
    //update
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());
        session()->flash('success', __('messages.updated successfully.'));
        return redirect()->route('admin.users.index');
    }
    //destroy
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json('success');

    }

}
