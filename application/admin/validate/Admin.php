<?php
namespace app\admin\validate;

class Admin extends \think\Validate
{
    protected $rule = [
        'username|姓名' => 'require|max:15',
        'phone|管理员账号' => 'require|mobile',
        'hos_id|医馆' => 'require',
        'depart_id|科室' => 'require',
        'group_id|权限角色' => 'require|noadmin',
    ];
    protected $message = [
        'username.require' => '姓名不能为空',
        'username.max' => '姓名最大长度为15',
        'phone.require' => '手机号码不能为空',
        'phone.mobile' => '请填写有效的手机号',
        'hos_id.require' => '请选择医馆',
        'depart_id.require' => '请选择科室',
        'group_id.require' => '请选择权限角色',
    ];

    /**
     * 验证数据最大长度
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @return bool
     */
    protected function mobile($value, $rule)
    {
        if (preg_match("/^1[3-9]\d{9}$/", $value)) {
            return true;
        } else {
            return false;
        }
    }

    protected function noadmin($value, $rule)
    {
        if (in_array($value, [1, 2])) {
            return false;
        } else {
            return true;
        }
    }
}