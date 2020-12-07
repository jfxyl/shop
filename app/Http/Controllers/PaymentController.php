<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own',$order);

        if($order->paid_at || $order->closed){
            throw new InvalidRequestException('订单状态不正确');
        }

        return app('alipay')->web([
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_amount,
            'subject' => '支付 疾风夕颜 的订单：'.$order->no,
        ]);
    }

    public function payByInstallment(Order $order, Request $request)
    {
        $this->authorize('own',$order);

        if($order->paid_at || $order->closed){
            throw new InvalidRequestException('订单状态不正确');
        }
        if($order->total_amount < config('app.min_installment_amount')){
            throw new InvalidRequestException('订单金额低于最低分期金额');
        }
        $this->validate($request,[
            'count' => ['required',Rule::in(array_keys(config('app.installment_fee_rate')))]
        ]);
        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();
        $count = $request->input('count');
        $installment = new Installment([
            'total_amount' => $order->total_amount,
            'count'        => $count,
            'fee_rate'     => config('app.installment_fee_rate')[$count],
            'fine_rate'    => config('app.installment_fine_rate'),
        ]);
        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();

        $dueDate = Carbon::tomorrow();

        $base = big_number($order->total_amount)->divide($count)->getValue();
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();

        for($i = 0;$i<$count;$i++){
            if($i === $count - 1){
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1))->getValue();
                $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();
            }
            $installment->items()->create([
                'sequence' => $i,
                'base'     => $base,
                'fee'      => $fee,
                'due_date' => $dueDate,
            ]);
            $dueDate = $dueDate->copy()->addDays(30);
        }
        return $installment;
    }

    // 前端回调页面
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    // 服务器端回调
    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        \Log::debug('Alipay notify', $data->all());

        if(!in_array($data->trade_status,['TRADE_SUCCESS', 'TRADE_FINISHED'])){
            return app('alipay')->success();
        }
        $order = Order::where('no', $data->out_trade_no)->first();
        if (!$order) {
            return 'fail';
        }
        if($order->paid_at){
            return app('alipay')->success();
        }
        $order->update([
            'paid_at' => Carbon::now(),
            'payment_method' => 'alipay',
            'payment_no' => $data->trade_no
        ]);

        $this->afterPaid($order);

        return app('alipay')->success();
    }

    public function afterPaid($order)
    {
        event(new OrderPaid($order));
    }
}
