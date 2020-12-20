<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()->where('user_id',$request->user()->id)->paginate(10);

        return view('installments.index',['installments' => $installments]);
    }

    public function show(Installment $installment)
    {
        $this->authorize('own', $installment);

        $items = $installment->items()->orderBy('sequence')->get();

        return view('installments.show',[
            'installment' => $installment,
            'items' => $items,
            'nextItem' => $items->where('paid_at',null)->first()
        ]);
    }

    public function payByAlipay(Installment $installment)
    {
        if($installment->order->closed){
            throw new InvalidRequestException('商品订单已关闭');
        }
        if($installment->status === Installment::STATUS_FINISHED){
            throw new InvalidRequestException('分期已结束');
        }
        if(!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()){
            throw new InvalidRequestException('分期已结束');
        }

        return app('alipay')->web([
            'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject'      => '支付 Laravel Shop 的分期订单：'.$installment->no,
            'notify_url'   => ngrok_url('installments.alipay.notify'),
            'return_url'   => route('installments.alipay.return'),
        ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();
        if(!in_array($data->trade_status,['TRADE_SUCCESS','TRADE_FINISHED'])){
            return app('alipay')->success();
        }
        list($no,$sequence) = explode('_',$data->out_trade_no);
        if(!$installment = Installment::query()->where('no',$no)->first()){
            return 'fail';
        }
        if(!$item = $installment->items()->where('sequence',$sequence)->first()){
            return 'fail';
        }
        if($item->paid_at){
            return app('alipay')->success();
        }
        \DB::transaction(function()use($data,$no,$installment,$item){
            $item->update([
                'paid_at' => Carbon::now(),
                'payment_no' => $data->trade_no,
                'payment_method' => 'alipay',
            ]);

            if($item->sequence == 0){
                $installment->update(['status'=>Installment::STATUS_REPAYING]);
                $installment->order->update([
                    'paid_at' => Carbon::now(),
                    'payment_method' => 'installment',
                    'payment_no' => $no
                ]);
                event(new OrderPaid($installment->order));
            }
            if($item->sequence === $installment->count - 1){
                $installment->update(['status' => Installment::STATUS_FINISHED]);
            }
        });
        return app('alipay')->success();
    }
}
