<?php

namespace app\admin\model;

use think\Model;

class PatientVisitRecord extends Model
{
    // 表名
    protected $name = 'patient_visit_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    //类型转换
//    protected $type = [
//        'birth_time'  =>  'timestamp:Y-m-d',
//    ];
    
    // 追加属性
    protected $append = [

    ];

    /**
     * 关联Hospital模型
     * @return $this
     *
     */
//    public function hospital()
//    {
//        return $this->belongsTo('Hospital', 'hos_id')->field('id,hos_name');
//    }

    /**
     * 关联Member模型
     */
//    public function member()
//    {
//        return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->setEagerlyType(0);
//        return $this->belongsTo('Member', 'member_id', 'id',[], 'LEFT')->field('id,open_member')->setEagerlyType(0);
//    }
    /**
     * 关联Register模型
     */
//    public function register()
//    {
//        return $this->belongsTo('Register','id','patient_visit_record_id', [],'LEFT')->field('patient_visit_record_id,id,register_time,stage')->setEagerlyType(0);
//    }
    /**
     * 关联Appointment模型
     */
//    public function appointment()
//    {
//        return $this->belongsTo('Appointment','id','patient_visit_record_id', [],'LEFT' )->field('patient_visit_record_id,id,start_time,end_time')->setEagerlyType(0);
//    }
    /**
     * @auth 郭庆波
     * 根据手机号和姓名检查患者表记录是否存在，不存在添加，存在更新
     * @return 返回患者id
     */
    public function patientIsExist($params)
    {
        //根据姓名和手机号查询患者表中是否已插入
        if(!empty($params['name']) && isset($params['telphone']) && !empty($params['member_id']))
        {
            $patientInfo = $this
                ->get(['name' =>$params['name'], 'telphone'=>$params['telphone']]);

            //为空则插入患者表数据
            if(empty($patientInfo))
            {
                try {
                    $patientSave = $this
                        ->allowField(true)
                        ->save($params);
                    return ['msg' => 'add','id' => $this->id];
                } catch (\Exception $e) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => $e->getMessage()];
                }
            }
            else
            {
                try {
                    //患者表中已插入更新数据
                    $patientSave = $this
                        ->allowField(true)
                        ->save($params,['id'=>$patientInfo['id']]);
                    return ['msg' => 'update','id' => $patientInfo['id']];
                } catch (\Exception $e) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => $e->getMessage()];
                }
            }
        }
        else
        {
            return ['code' => 0, 'msg' => '患者名称或手机号或会员id为空'];
        }
    }

}
