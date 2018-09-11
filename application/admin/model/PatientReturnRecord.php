<?php

namespace app\admin\model;

use think\Model;

class PatientReturnRecord extends Model
{
    // 表名
    protected $name = 'patient_return_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
    * 关联Member模型
    */
//    public function member()
//    {
////        return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->setEagerlyType(0);
//        return $this->belongsTo('Member', 'member_id', 'id');
//    }
}
