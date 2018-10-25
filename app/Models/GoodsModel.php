<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsModel extends Model
{
    protected $table = "sg_goods";//要连接的表名称
    public $timestamps = false;//将时间戳设置为false，否则数据表没有对应字段（create_at等字段）就会报错

    public function setImgsAttribute($imgs)
    {
        $this->attributes['imgs'] = json_encode($imgs);
    }

    public function getImgsAttribute($imgs)
    {
        return json_decode($imgs, true);
    }
}
