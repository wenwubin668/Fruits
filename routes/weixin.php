<?php

Route::namespace('Weixin')->group(function() {

    //入口
    Route::get('index' , 'WxServerController@index')->name('WeChatIndex');
    //页面授权
    Route::any('callback' , 'WxServerController@callBack')->name('cardCallBack');



    //公号-空间站
    Route::any('check' , 'WxServerController@check');

    //图片
    Route::get('marry' , 'MarryController@photoMap');



    //卡片管理
    Route::get('cardlist' , 'CardController@list')->name('CardList');
    //添加卡片
    Route::any('cardaction' , 'CardController@action')->name('CardAction');
    //账单列表
    Route::get('amountlist' , 'CardController@amountList')->name('CardAmountList');
    //详情
    Route::get('info' , 'CardController@info')->name('CardInfo');


});