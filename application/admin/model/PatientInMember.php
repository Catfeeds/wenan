<?php

namespace app\admin\model;

use think\Model;

class PatientInMember extends Model
{
    // 表名
    protected $name = 'patient_in_member';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    //新增会员名下绑定的病人
    public function addPatient($params)
    {
        //是否已有该会员，该会员是否被禁用，无添加会员
        $patientInMemberInfo = $this
            ->get(['name'=>$params['name'],'member_id'=>$params['member_id']]);
        if(empty($patientInMemberInfo))
        {
            try {
                $memberSave = $this
                    ->allowField(true)
                    ->save($params);
                if($memberSave)
                $data = ['id'=>$this->id];
                return ['code' => 1,'msg' => '新增会员绑定的病人','data' => $data];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => 0, 'msg' => $e->getMessage()];
            }
        }
        else
        {
            return [ 'code' => 3,'msg' => '该会员下病人'.$params['name'].'已绑定','data' => $patientInMemberInfo];
        }
    }
}
