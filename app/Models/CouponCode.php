<?php

namespace App\Models;

use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CouponCode extends Model
{
    use DefaultDatetimeFormat;

    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    public static $typeMap = [
        self::TYPE_FIXED   => '固定金额',
        self::TYPE_PERCENT => '比例',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'total',
        'used',
        'min_amount',
        'not_before',
        'not_after',
        'enabled',
    ];
    protected $casts = [
        'enabled' => 'boolean',
    ];
    // 指明这两个字段是日期类型
    protected $dates = ['not_before', 'not_after'];

    protected $appends = ['description'];

    public static function findAvailableCode($len = 16)
    {
        do{
            $code = strtoupper(Str::random($len));
        }while(self::query()->where('code',$code)->exists());
        return $code;
    }

    public function getDescriptionAttribute()
    {
        $description = '';

        if($this->min_amount > 0){
            $description .= "满{$this->min_amount}";
        }

        if($this->type == self::TYPE_FIXED){
            $description .= "减{$this->value}";
        }else{
            $description .= "优惠{$this->value}%";
        }

        return $description;
    }
}
