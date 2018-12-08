<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/12/8
 * Time: 5:46 PM
 */

namespace App\Http\Controllers\Weixin;


use App\Http\Controllers\Controller;

class MarryController extends Controller
{
    public function photoMap(){
        return view('weixin.photomap');
    }
}