<?php
namespace app\admin\validate;

class AuthGroup extends \think\Validate
{
    protected $rule = [
        'hos_id|医馆' => 'require',
        'name|身份名称' => 'require',
    ];
}