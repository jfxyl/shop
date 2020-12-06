<?php

namespace App\Console\Commands\Cron;

use App\Jobs\RefundCrowdfundingOrders;
use App\Models\CrowdfundingProduct;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinishCrowdfunding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:finish-crowdfunding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束众筹';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        CrowdfundingProduct::query()
            ->where('end_at','<=',Carbon::now())
            ->where('status',CrowdfundingProduct::STATUS_FUNDING)
            ->get()
            ->each(function(CrowdfundingProduct $crowdfunding){
                if($crowdfunding->target_amount > $crowdfunding->total_amount){
                    // 众筹失败
                    $this->crowdfundingFailed($crowdfunding);
                }else{
                    // 众筹成功
                    $this->crowdfundingSucceed($crowdfunding);
                }
            });
    }

    protected function crowdfundingFailed($crowdfunding)
    {
        $crowdfunding->update([
            'status' => CrowdfundingProduct::STATUS_FAIL
        ]);
        dispatch(new RefundCrowdfundingOrders($crowdfunding));

    }

    protected function crowdfundingSucceed($crowdfunding)
    {
        $crowdfunding->update([
            'status' => CrowdfundingProduct::STATUS_SUCCESS,
        ]);
    }
}
