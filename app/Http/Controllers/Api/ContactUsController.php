<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\ContactUsRequest;

class ContactUsController extends Controller
{
        public function __invoke(ContactUsRequest $request)
        {

            $data = $request->validated();
            $data['user_id'] = auth()->user()->id;

            $contact = ContactUs::create($data);
            $lang = $request->header('lang');
            $massage = $lang == 'ar' ? 'تم إنشاء رسالة اتصل بنا بنجاح' : 'Contact Us Created Successfully';

            return sendResponse($contact, $massage);

        }
}
