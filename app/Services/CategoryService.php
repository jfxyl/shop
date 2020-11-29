<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    public function getCategoryTree($parentId = null,$categories = null)
    {
        if(is_null($categories)){
            $categories = Category::all();
        }

        return $categories->where('parent_id',$parentId)
            ->map(function(Category $category)use($categories){
                $data = ['id' => $category->id,'name' => $category->name];
                if(!$category->is_directory){
                    return $data;
                }
                $data['children'] = $this->getCategoryTree($category->id,$categories);
                return $data;
            });
    }
}
