<?php
namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class CategoriesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '商品分类';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category());

        $grid->column('id', 'ID');
        $grid->column('name', '分类名称');
        $grid->column('is_directory', '是否目录')->display(function($value){
            return $value ? '是' : '否';
        });
        $grid->column('level', '层级');
        $grid->column('path', '分类路径');
        $grid->actions(function($actions){
            $actions->disableView();
        });

        return $grid;
    }

    public function edit($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form(true)->edit($id));
    }

    public function form($isEdit = false)
    {
        $form = new Form(new Category);

        $form->text('name','分类名称')->rules('required');

        if($isEdit){
            $form->display('is_directory','是否目录')->with(function($value){
                return $value ? '是' : '否';
            });

            $form->display('parent.name', '父类目');
        }else{
            $form->radio('is_directory','是否目录')
                ->options(['1' => '是','0' => '否'])
                ->default('0')
                ->rules('required');

            $form->select('parent_id','父类目')->ajax('/admin/api/categories');
        }

        return $form;
    }

    // 定义下拉框搜索接口
    public function apiIndex(Request $request)
    {
        // 用户输入的值通过 q 参数获取
        $search = $request->input('q');
        $result = Category::query()
            ->where('is_directory', true)  // 由于这里选择的是父类目，因此需要限定 is_directory 为 true
            ->where('name', 'like', '%'.$search.'%')
            ->paginate();

        $result->setCollection($result->getCollection()->map(function (Category $category) {
            return ['id' => $category->id, 'text' => $category->full_name];
        }));

        return $result;
    }
}
