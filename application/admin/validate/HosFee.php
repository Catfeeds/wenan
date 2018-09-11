<?php
namespace app\admin\validate;

class HosFee extends \think\Validate
{
    protected $rule = [
        'fee_id|费用类型' => 'require',
        'fee_name|名称' => 'require',
        'price|价格' => 'require|price',
        'unit|单位' => 'require',
    ];
    protected $message = [
        'price.price' => '价格必须是数字，小数点后最多两位',
    ];

    protected function price($value, $rule)
    {
        if (preg_match("/^[0-9]+(\.[0-9]{1,2})?$/", $value)) {
            return true;
        } else {
            return false;
        }
    }
}