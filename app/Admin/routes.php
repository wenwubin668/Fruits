<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    //商品管理
    $router->resource('/goods', 'GoodsController');
    //订单管理
    $router->resource('/order', 'OrderController');

    //编辑器图片上传
    $router->post('/uploadFile','UploadController@uploadImg');
});
