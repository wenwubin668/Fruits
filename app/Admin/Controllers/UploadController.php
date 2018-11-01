<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/10/29
 * Time: 4:46 PM
 */

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Services\OssService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    //wangEditor 上传图片
    public function uploadImg(Request $request)
    {
        $file = $request->file("mypic");
        // dd($file);
        if (!empty($file)) {
            foreach ($file as $key => $value) {
                $len = $key;
            }
            if ($len > 25) {
                return response()->json(['ResultData' => 6, 'info' => '最多可以上传25张图片']);
            }
            $m = 0;
            $k = 0;
            for ($i = 0; $i <= $len; $i++) {
                // $n 表示第几张图片
                $n = $i + 1;
                if ($file[$i]->isValid()) {
                    if (in_array(strtolower($file[$i]->extension()), ['jpeg', 'jpg', 'gif', 'gpeg', 'png'])) {
                        //$picname = $file[$i]->getClientOriginalName();//获取上传原文件名
                        $ext = $file[$i]->getClientOriginalExtension();//获取上传文件的后缀名
                        // 重命名
                        $filename = md5(time()). "." . $ext;
                        $dir = 'images/'.date('Ymd').'/';
                        if ($file[$i]->move($dir, $filename)) {
                            $newFileName = $dir . $filename;
                            //上传到oss
                            $res = OssService::getInstance()->upload($newFileName,$newFileName);
                            if($res){
                                unlink($newFileName);//删除本地图片
                            }
                            $src = '//'.env('REDIS_CNAME').'/'.$newFileName;

                            $m = $m + 1;
                            // return response()->json(['ResultData' => 0, 'info' => '上传成功', 'newFileName' => $newFileName ]);
                        } else {
                            $k = $k + 1;
                            // return response()->json(['ResultData' => 4, 'info' => '上传失败']);
                        }
                        $msg = $m . "张图片上传成功 " . $k . "张图片上传失败<br>";
                        $return[] = ['ResultData' => 0, 'info' => $msg, 'newFileName' => $src];
                    } else {
                        return response()->json(['ResultData' => 3, 'info' => '第' . $n . '张图片后缀名不合法!<br/>' . '只支持jpeg/jpg/png/gif格式']);
                    }
                } else {
                    return response()->json(['ResultData' => 1, 'info' => '第' . $n . '张图片超过最大限制!<br/>' . '图片最大支持2M']);
                }
            }
        } else {
            return response()->json(['ResultData' => 5, 'info' => '请选择文件']);
        }
        return $return;
    }
}