<?php
namespace app\common\validate;

class Dict extends \think\Validate
{
    protected $rule = [
        'dict_name|字典名称' => 'require|max:80',
        'dict_value|字典标识' => 'require|max:80',
    ];
    protected $message = [
        'dict_name.require' => '字典名称不能为空',
        'dict_name.max' => '字典名称最大长度为80',
        'dict_value.require' => '字典标识不能为空',
        'dict_value.max' => '字典标识最大长度为80',
    ];
}