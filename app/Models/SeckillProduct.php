<?php

namespace App\Models;

use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;

class SeckillProduct extends Model
{
    use DefaultDatetimeFormat;
    protected $fillable = ['start_at','end_at'];
    protected $dates = ['start_at','end_at'];

    public $timestamps = false;

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function getIsBeforeStartAttribute(){
        return Carbon::now()->lt($this->start_at);
    }

    public function getIsAfterEndAttribute(){
        return Carbon::now()->gt($this->end_at);
    }
}
