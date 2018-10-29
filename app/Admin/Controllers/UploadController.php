<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/10/29
 * Time: 4:46 PM
 */

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function index(Request $request){
        $ck = $request->get('CKEditorFuncNum');
        if ($request->hasFile('upload')) {     //upload为ckeditor默认的file提交ID
            $file = $request->file('upload');   //从请求数据内容中取出图片的内容
            $allowed_extensions = ["png", "jpg", "gif"]; //允许的图片后缀
            if ($file->getClientOriginalExtension() && !in_array($file->getClientOriginalExtension(), $allowed_extensions)) {
                return '图片后缀只支持png,jpg,gif,请检查！';
            }
            $destinationPath = 'images/'.date('Ymd');  //图片存放路径
            $extension = $file->getClientOriginalExtension();  //获得文件后缀
            $fileName = md5(time()) . '.' . $extension;  //创建图片名字
            $result = $file->move($destinationPath, $fileName); //存储图片到路径
            $url = 'http://img.ffbin.com/' .$destinationPath. $fileName; //输出图片网站中浏览路径
            //echo $url;
            echo "<script>window.parent.CKEDITOR.tools.callFunction($ck, '$url', '');</script>";
        }
    }
}