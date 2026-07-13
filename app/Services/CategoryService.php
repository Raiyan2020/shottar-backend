<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryService
{
    use ImageTrait;

    protected $repo;

    public function __construct(CategoryRepository $repo)
    {
        $this->repo = $repo;

    }

    public function create(Request $request): Category
    {
        $data = $request->validated();

        if ($request->has('image')) {
            $data['image'] = $this->uploadImage('admin', $request->image);
        }

        $data['name_ar'] = $request->name_ar ?: $request->name_en;

        return $this->repo->create($data);
    }

    public function update(Category $category, Request $request)
    {
        $data = $request->validated();
        $data['name_ar'] = $request->name_ar ?: $request->name_en;

        return $this->repo->update($category, $data);
    }

    public function delete($id)
    {
        return $this->repo->deleteById($id);
    }

    public function getFiltersByCategory($categoryId, $lang = 'ar')
    {
        $category = $this->repo->getCategoryWithActiveFilters($categoryId);

        if (!$category || !$category->filterGroup) {
            return null;
        }

        $filters = $category->filterGroup->filters->map(function ($filter) use ($lang) {
            return [
                'id' => $filter->id,
                'name' => $lang == 'en' ? $filter->name_en : $filter->name_ar,
            ];
        });

        return $filters->toArray();
    }
}
