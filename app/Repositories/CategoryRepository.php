<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository
{
    public function create(array $data)
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data)
    {
        return $category->update($data);
    }

    public function deleteById($id)
    {
        return Category::destroy($id);
    }

    public function find($id)
    {
        return Category::find($id);
    }
    public function getCategoryWithActiveFilters($categoryId)
    {
        return Category::where('id', $categoryId)
            ->where('status', 'active')
            ->with(['filterGroup.filters' => function ($query) {
                $query->where('status', 'active');
            }])
            ->first();
    }
}
