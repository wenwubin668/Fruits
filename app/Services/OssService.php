<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/10/31
 * Time: 4:18 PM
 */

namespace App\Services;

use OSS\OssClient;
use OSS\Core\OssException;

class OssService extends Service
{
    protected static $instance;

    private $ossClient;
    protected $bucketName;

    public function __construct()
    {
        try {
            $this->ossClient = new OssClient(env('REDIS_ACCESS_KEY'),env('REDIS_ACCESS_KEY_SECRET'),env('REDIS_ENDPOINT'));
        } catch (OssException $e) {
            printf($e->getMessage() . "\n");
        }
        $this->bucketName = env('REDIS_BUCKET_NAME');
    }

    public function upload($fileName,$url){
        try {
            $this->ossClient->uploadFile($this->bucketName,$fileName,$url);
        } catch (OssException $e) {
            return $e->getMessage();
        }
        return true;
    }

}