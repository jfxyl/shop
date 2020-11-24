<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function store(OrderRequest $request,OrderService $orderService){
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'));
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            // 使用 with 方法预加载，避免N + 1问题
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order, Request $request)
    {
        $this->authorize('own',$order);
        return view('orders.show', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function received(Order $order,Request $request)
    {
        $this->authorize('own',$order);

        if($order->ship_status != Order::SHIP_STATUS_DELIVERED){
            throw new InvalidRequestException('订单状态不正确');
        }
        $order->update([
            'ship_status' => Order::SHIP_STATUS_RECEIVED
        ]);
        return $order;
    }

    public function review(Order $order)
    {
        $this->authorize('own',$order);
        if(!$order->paid_at){
            throw new InvalidRequestException('该订单尚未支付，不可评价');
        }
        return view('orders.review',['order' => $order->load(['items.productSku','items.product'])]);
    }

    public function sendReview(Order $order,SendReviewRequest $request)
    {
        $this->authorize('own',$order);
        if(!$order->paid_at){
            throw new InvalidRequestException('该订单尚未支付，不可评价');
        }
        if($order->reviewed){
            throw new InvalidRequestException('该订单已评价');
        }

        $reviews = $request->input('reviews');

        DB::transaction(function()use($order,$reviews){
            foreach($reviews as $review){
                $orderItem = $order->items()->find($review['id']);
                $orderItem->update([
                    'rating' => $review['rating'],
                    'review' => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }
            $order->update([
                'reviewed' => true
            ]);
        });

        event(new OrderReviewed($order));

        return redirect()->back();
    }

    public function applyRefund(Order $order,Request $request)
    {
        $this->authorize('own',$order);

        if(!$order->paid_at){
            throw new InvalidRequestException('订单未支付');
        }
        if($order->refund_status != Order::REFUND_STATUS_PENDING){
            throw new InvalidRequestException('退款状态不正确');
        }

        $extra = $order->extra ? : [];

        $extra['refund_reason'] = $request->input('reason');

        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra' => $extra
        ]);
        return $order;
    }
}
