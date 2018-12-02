<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/11/4
 * Time: 7:56 PM
 */

namespace App\Common;


class CommonConf
{
    const FRUITS_GOOD_TYPE_XIAN = 1;//鲜果
    const FRUITS_GOOD_TYPE_GAN = 2;//干果
    static $goodsType = [
        self::FRUITS_GOOD_TYPE_XIAN=>'鲜果',
        self::FRUITS_GOOD_TYPE_GAN=>'干果',
    ];

    //1:初始状态 2：订单支付成功 3：已发货 4：订单完成
    const ORDER_INIT_STATUS = 1;//初始订单状态
    const ORDER_PAY_SUCCESS_STATUS = 2;//订单支付成功
    const ORDER_SEND_GOODS_STATUS = 3;//已发货
    const ORDER_FINALE_SUCCESS_STATUS = 4;//订单完成
    static $orderType = [
        self::ORDER_INIT_STATUS=>'初始状态',
        self::ORDER_PAY_SUCCESS_STATUS=>'支付成功',
        self::ORDER_SEND_GOODS_STATUS=>'已发货',
        self::ORDER_FINALE_SUCCESS_STATUS=>'订单完成',
    ];



}