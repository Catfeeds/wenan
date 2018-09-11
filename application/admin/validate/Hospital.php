<?php
namespace app\admin\validate;

class Hospital extends \think\Validate
{
    protected $rule = [
        'hos_name|医馆名称' => 'require|max:20',
        'phone|管理员账号' => 'require|mobile',
        'captcha|验证码' => 'require',
        'depart|科室' => 'require',
    ];
    protected $message = [
        'hos_name.require' => '医馆名称不能为空',
        'hos_name.max' => '医馆名称最大长度为20',
        'phone.require' => '管理员账号不能为空',
        'phone.mobile' => '请填写有效的手机号',
        'captcha.require' => '验证码不能为空',
        'depart.require' => '请选择科室',
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
}