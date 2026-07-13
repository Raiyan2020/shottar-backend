<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\AdminsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use App\Traits\ImageTrait;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    use ImageTrait;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AdminsDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.admins.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.admin.admins.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AdminRequest $request)
    {
            try {
            if ($request->has('photo')) {
                $image_path = $this->uploadImage('admin', $request->photo);
            }
            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'image' => $image_path ?? null,
                'roles_name' => json_encode(['admin']),
            ]);

            $admin->assignRole('admin');

            session()->flash('success', __('messages.created successfully.'));
            return redirect()->route('admin.admins.index');
        } catch (\Exception $ex) {
            session()->flash('error', __(__('messages.An error occurred while creating the admin. Please try again.')));
            return redirect()->route('admin.admins.create');
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                session()->flash('error', __('messages.The Admin is not exist'));
                return redirect()->route('admin.admins.index');
            }
            return view('dashboard.admin.admins.edit',compact('admin'));
        }catch (\Exception $ex) {
            session()->flash('error', __('messages.There was an error try again'));
            return redirect()->route('admin.admins.index');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAdminRequest $request, $id)
    {
        try {
            $admin = Admin::findorfail($id); // Retrieve the User model instance
            if ($request->has('photo')) {
                $image_path = $this->uploadImage('admin', $request->photo);
            }

            if (!empty($request['password'])) {
                $password = Hash::make($request['password']);
                $admin->update([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => $password,
                    'image' => $image_path ?? $admin->image,
                ]);
            }else{
                $admin->update([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'image' => $image_path ?? $admin->image,
                ]);
            }
            session()->flash('success', __('messages.updated successfully.'));
            return redirect()->route('admin.admins.index');
        } catch (\Exception $ex) {
            session()->flash('error', __('messages.here was an error try again'));
            return redirect()->route('admin.admins.edit', $id);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin)
    {
        $admin->delete();
        return response()->json('success');
    }
}
