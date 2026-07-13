<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqsResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{

    public function __invoke()
    {
        $faqs = Faq::where('status', 1)->get();

        return sendResponse(FaqsResource::collection($faqs));


    }
}
