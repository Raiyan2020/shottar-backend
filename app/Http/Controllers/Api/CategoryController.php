<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //__invoke
    public function __invoke(Request $request)
    {
        $categories = Category::where('status', 'active')->get();

        return sendResponse(CategoryResource::collection($categories));
    }
}
