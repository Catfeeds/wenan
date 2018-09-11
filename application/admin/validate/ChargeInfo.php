<?php
namespace app\admin\validate;

class ChargeInfo extends \think\Validate
{
    protected $rule = [
        'member_id|会员id' => 'require',
        'fee_id|费用类型' => 'require',
        'hos_fee_id|费用名称' => 'require',
        'should_pay|价格' => 'require|should_pay',
        'admin_input_id|操作者id' => 'require',
    ];
    protected $message = [
        'should_pay.should_pay' => '价格必须是数字，小数点后最多两位',
    ];

    protected function should_pay($value, $rule)
    {
        if (preg_match("/^[0-9]+(\.[0-9]{1,2})?$/", $value)) {
            return true;
        } else {
            return false;
        }
    }
}