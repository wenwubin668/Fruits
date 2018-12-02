<?php

Route::namespace('Weixin')->group(function() {
    //公号-空间站
    Route::any('check' , 'WxServerController@check');


    Route::get('index' , 'WxServerController@index');


});