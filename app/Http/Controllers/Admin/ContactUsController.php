<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ContactUsDataTable;
use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function index(ContactUsDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.contact-us.index');
    }


    public function destroy($id)
    {
        $contactUs = ContactUs::findOrFail($id);
        $contactUs->delete();
        return response()->json('success');

    }

}
