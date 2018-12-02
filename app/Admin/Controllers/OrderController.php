<?php

namespace App\Admin\Controllers;

use App\Common\CommonConf;
use App\Http\Controllers\Controller;
use App\Models\GoodsModel;
use App\Models\OrderModel;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
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
            ->header('订单列表')
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
        $grid = new Grid(new OrderModel());

        $grid->disableCreateButton();//禁用创建按钮
        $grid->disableActions();//禁用行操作列
        $grid->disableRowSelector();//禁用行选择checkbox

        $grid->actions(function ($actions) {
            $actions->disableDelete();//关闭删除按钮
            //$actions->disableEdit();//关闭编辑按钮
            $actions->disableView();//关闭预览按钮
        });
        //筛选条件
//        $grid->model()->where('progress','=',2);

        //过滤不必要的字段
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 在这里添加字段过滤器
            $filter->equal('out_trade_no', '订单编号');
            $filter->equal('transaction_id', '微信订单号');
            $filter->between('created_at', '时间')->datetime();
        });

        $grid->column('uid','用户名/手机号')->display(function ($uid){

            $user = DB::table('sg_user')->where('id', $uid)->first();
            return $user->nickname.' / '.$user->mobile;
        });


        $grid->out_trade_no('订单编号');
        $grid->transaction_id('微信订单号');
        $grid->progress('进度')->editable('select', CommonConf::$orderType);
        $grid->send_no('快递单号')->editable();
        $grid->column('content','订单内容')->display(function ($content){
            return "<span >$content</span>";
        });
        $grid->total_price('订单总价');
        $grid->created_at('创建时间')->sortable();

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
        $form = new Form(new OrderModel());
        $form->text('progress', '订单进度');
        $form->text('send_no', '快递单号');
        return $form;
    }
}
