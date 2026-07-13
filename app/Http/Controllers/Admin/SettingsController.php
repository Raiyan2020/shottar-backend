<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    //
    public function index()
    {
        // Check if the user is an admin
        $setting = Setting::all();
//        return $setting;
        return view('dashboard.admin.settings.index',compact('setting'));
    }
    //update settings
    public function update(Request $request)
    {
        // Validate the request data
        foreach ($request->except('_token') as $k => $v) {
            $this->update_setting([
                'key_id' => $k,
                'value' => $v
            ], $k);
        }
        return redirect()->back()->with('success',__('messages.updated successfully'));
    }

    public function update_setting($data,$key){
        return Setting::where('key_id',$key)->update($data);
    }

}
