<?php

namespace App\Jobs;

use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->order->payment_method !== 'installment'
        || !$this->order->paid_at
        || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING){
            return;
        }

        if(!$installment = Installment::query()->where('order_id',$this->order->id)->first()){
            return;
        }
        foreach($installment->items as $item){
            if(!$item->paid_at || in_array($item->status,[InstallmentItem::REFUND_STATUS_PROCESSING,InstallmentItem::REFUND_STATUS_SUCCESS])){
                continue;
            }
            try{
                $this->refundInstallmentItem($item);
            }catch (\Exception $e){
                \Log::warning('分期退款失败'.$e->getMessage(),[
                    'installment_item_id' => $item->id
                ]);
                continue;
            }
        }
        $installment->refreshRefundStatus();
    }

    public function refundInstallmentItem(InstallmentItem $item){
        $refundNo = $this->order->refund_no.'_'.$item->sequence;
        switch ($item->payment_method){
            case 'alipay':
                $ret = app('alipay')->refund([
                    'trade_no'       => $item->payment_no, // 使用支付宝交易号来退款
                    'refund_amount'  => $item->base, // 退款金额，单位元，只退回本金
                    'out_request_no' => $refundNo, // 退款订单号
                ]);
                if ($ret->sub_code) {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
                    ]);
                } else {
                    // 将订单的退款状态标记为退款成功并保存退款订单号
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            case 'wechat':
                break;
            default:
                // 原则上不可能出现，这个只是为了代码健壮性
                throw new InternalException('未知订单支付方式：'.$item->payment_method);
                break;
        }
    }
}
