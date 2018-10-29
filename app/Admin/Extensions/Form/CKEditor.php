<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/10/29
 * Time: 4:26 PM
 */

namespace App\Admin\Extensions\Form;


use Encore\Admin\Form\Field;

class CKEditor extends Field
{
    public static $js = [
        '/packages/ckeditor/ckeditor.js',
        '/packages/ckeditor/adapters/jquery.js',
    ];

    protected $view = 'admin.ckeditor';

    public function render()
    {
        $this->script = "$('textarea.{$this->getElementClassString()}').ckeditor();";
        return parent::render();
    }
}