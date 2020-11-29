<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'is_directory', 'level', 'path'];

    protected $casts = [
        'is_directory' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        self::creating(function(Category $category){
            if(is_null($category->parent_id)){
                $category->level = 0;
                $category->path = '-';
            }else{
                $category->level = $category->parent->level + 1;
                $category->path = $category->parent->path . $category->parent->id . '-';
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class,'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getParentIdsAttribute()
    {
        return array_filter(explode('-',$this->path));
    }

    public function getAncestorsAttribute()
    {
        return Category::query()->whereIn('id',$this->parend_ids)->orderBy('level')->get();
    }

    public function getFullNameAttribute()
    {
        return $this->ancestors->pluck('name')->push($this->name)->explode(' - ');
    }
}
