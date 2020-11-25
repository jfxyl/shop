<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\CouponCode;
use Faker\Generator as Faker;

$factory->define(CouponCode::class, function (Faker $faker) {
    $type = $faker->randomElement(array_keys(CouponCode::$typeMap));
    $value = $type == CouponCode::TYPE_FIXED ? random_int(1,100) : random_int(10,50);

    if($type == CouponCode::TYPE_FIXED){
        $min_amount = $value + random_int(50,100);
    }else{
        if(random_int(0,100) < 50){
            $min_amount = 0;
        }else{
            $min_amount = random_int(100, 1000);
        }
    }

    return [
        'name' => join(' ',$faker->words),
        'type' => $type,
        'code' => CouponCode::findAvailableCode(16),
        'value' => $value,
        'min_amount' => $min_amount,
        'not_before' => null,
        'not_after' => null,
        'total' => 1000,
        'used' => 0,
        'enabled'    => true,
    ];
});
