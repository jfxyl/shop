<?php

namespace App\Http\Requests;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\UserAddress;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;

class SeckillOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address_id' => [
                'required',
                Rule::exists(UserAddress::class,'id')->where('user_id',$this->user()->id)
            ],
            'sku_id' => [
                'required',
                function($attribute,$value,$fail){
                    $stock = Redis::get('seckill_sku_'.$value);
                    // 如果是 null 代表这个 SKU 不是秒杀商品
                    if (is_null($stock)) {
                        return $fail('该商品不存在');
                    }
                    // 判断库存
                    if ($stock < 1) {
                        return $fail('该商品已售完');
                    }

                    if(!$sku = ProductSku::find($value)){
                        return $fail('该商品不存在');
                    }
                    if ($sku->product->type !== Product::TYPE_SECKILL) {
                        return $fail('该商品不支持秒杀');
                    }
                    if ($sku->product->seckill->is_before_start) {
                        return $fail('秒杀尚未开始');
                    }
                    if ($sku->product->seckill->is_after_end) {
                        return $fail('秒杀已经结束');
                    }
                    if (!$sku->product->on_sale) {
                        return $fail('该商品未上架');
                    }
                    if ($sku->stock < 1) {
                        return $fail('该商品已售完');
                    }

                    if (!$user = \Auth::user()) {
                        throw new AuthenticationException('请先登录');
                    }
                    if (!$user->email_verified_at) {
                        throw new InvalidRequestException('请先验证邮箱');
                    }

                    if($order = Order::query()
                        ->where('user_id',$this->user()->id)
                        ->whereHas('items',function($query)use($value){
                            $query->where('product_sku_id',$value);
                        })
                        ->where(function($query){
                            $query->whereNotNull('paid_at')->orWhere('closed',false);
                        })
                        ->first()
                    ){
                        if ($order->paid_at) {
                            return $fail('你已经抢购了该商品');
                        }

                        return $fail('你已经下单了该商品，请到订单页面支付');
                    }
                }
            ]
        ];
    }
}
