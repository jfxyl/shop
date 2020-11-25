<?php

namespace App\Admin\Controllers;

use App\Models\CouponCode;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CouponCodesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '优惠券管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CouponCode());

        $grid->model()->orderBy('created_at', 'desc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '优惠券名称');
        $grid->column('code', '优惠券码');
        $grid->column('description', '描述');
        $grid->column('usage', '总量')->display(function($value){
            return "{$this->used} / {$this->total}";
        });
        $grid->column('not_before', '优惠券开始时间');
        $grid->column('not_after', __('优惠券结束时间'));
        $grid->column('enabled', '是否启用')->display(function($value){
            return $value ? '是' : '否';
        });
        $grid->column('created_at', '创建时间');

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CouponCode());

        $form->text('name', __('Name'));
        $form->text('code', __('Code'));
        $form->text('type', __('Type'));
        $form->decimal('value', __('Value'));
        $form->number('total', __('Total'));
        $form->number('used', __('Used'));
        $form->decimal('min_amount', __('Min amount'));
        $form->datetime('not_before', __('Not before'))->default(date('Y-m-d H:i:s'));
        $form->datetime('not_after', __('Not after'))->default(date('Y-m-d H:i:s'));
        $form->switch('enabled', __('Enabled'));

        return $form;
    }
}
