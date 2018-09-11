<?php

namespace app\admin\model;

use think\Model;
use think\session;
use fast\Random;

class ChargeInfo extends Model
{
    // 表名
    protected $name = 'charge_info';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [

    ];

    public function addFee($data)
    {
        $data = [
            'hos_id' => !empty($data['hos_id']) ? $data['hos_id'] : 0,
            'admin_input_id' => !empty($data['admin_input_id']) ? $data['admin_input_id'] : 0,
            'admin_input_name' => !empty($data['admin_input_name']) ? $data['admin_input_name'] : '',
            'admin_collect_id' => !empty($data['admin_collect_id']) ? $data['admin_collect_id'] : 0,
            'admin_collect_name' => !empty($data['admin_collect_name']) ? $data['admin_collect_name'] : '',
            'patient_in_member_id' => !empty($data['patient_in_member_id']) ? $data['patient_in_member_id'] : 0,
            'name' => !empty($data['name']) ? $data['name'] : '',
            'doctor_name' => !empty($data['doctor_name']) ? $data['doctor_name'] : '',
            'member_id' => !empty($data['member_id']) ? $data['member_id'] : 0,
            'fee_id'=> !empty($data['fee_id']) ? $data['fee_id'] : 0,
            'hos_fee_id'=> !empty($data['hos_fee_id']) ? $data['hos_fee_id'] : 0,
            'hos_fee_name' => !empty($data['hos_fee_name']) ? $data['hos_fee_name'] : '',
            'should_pay'=> !empty($data['should_pay']) ? $data['should_pay'] : 0,
            'pay_way' => !empty($data['pay_way']) ? $data['pay_way'] : 0,
            'already_paid' => !empty($data['already_paid']) ? $data['already_paid'] : 0,
            'serial_number'=> $this->buildUniqidNo(),
            'status' => !empty($data['status']) ? $data['status'] : 0,
            'newfee' => isset($data['newfee']) ? $data['newfee'] : 0,
            'createtime' => !empty($data['createtime']) ? $data['createtime'] : time(),
            'updatetime' => !empty($data['updatetime']) ? $data['updatetime'] : time(),
        ];
        try {
            $id = $this->insertGetId($data);
            $resData = $data;
            $resData['id'] = $id;
            return ['code' => 1, 'msg' => '操作成功','data' => $resData ];
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    /**
     * @auth 郭庆波
     * 生成唯一消费编号
     * @return
     */
    protected function buildUniqidNo()
    {
        $no = Random::numeric(11);
        //检测是否存在
        $info = $this->where(['serial_number'=>$no])->find();
        (!empty($info)) && $no = $this->build_order_no();
        return $no;
    }
}
