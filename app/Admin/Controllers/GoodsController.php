<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GoodsModel;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Log;

class GoodsController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('商品列表')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        $info = $this->form()->edit($id)->model()->attributesToArray();
        return $content
            ->header($info['title'])
            ->description($info['desc'])
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GoodsModel);

        $grid->actions(function ($actions) {
            $actions->disableDelete();//关闭删除按钮
            //$actions->disableEdit();//关闭编辑按钮
            $actions->disableView();//关闭预览按钮
        });

        $grid->id('ID')->sortable();
        $grid->title('标题');
        $grid->pre_price('原价');
        $grid->price('现价');

        $grid->column('num','库存')->display(function ($num){
            $str = "<span style='%s'>$num</span>";
            if($num >= 0 && $num < 10){
                $color = 'color:red';
            }else{
                $color = 'color:green';
            }
            return sprintf($str,$color);
        })->sortable();
//        $grid->status('状态')->using([1=>'正常',2=>'下架']);
        $grid->column('status','状态')->display(function ($status){
            $att = [1=>'正常',2=>'下架'];
            $str = "<span style='%s'>$att[$status]</span>";
            if($status == 2){
                $color = 'color:red';
            }else{
                $color = 'color:green';
            }
            return sprintf($str,$color);
        });
        $grid->desc('简介');
        $grid->created_at('创建时间');
        $grid->updated_at('更新时间');


        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(GoodsModel::findOrFail($id));

        $show->id('ID');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new GoodsModel);
        $form->text('title', '标题');
        $form->currency('pre_price','原价')->symbol('¥');
        $form->currency('price','现价')->symbol('¥');
        $form->number('num','库存');
        $form->radio('status','状态')->options([1=>'正常',2=>'下架']);
        $form->multipleImage('img','图片')->removable()->uniqueName();
        $form->text('desc','简介');
        $form->textarea('intro','描述')->rows(5);
        $form->wang_editor('content','内容');
        return $form;
    }
}
