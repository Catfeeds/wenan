<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\session;
use fast\Random;

class Member extends Model
{
    // 表名
    protected $name = 'member';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * @auth 郭庆波
     * 根据手机号检查会员是否存在，不存在添加会员;存在则更新常用医生,及常用门店
     * @return 返回会员id
     */
    public function addMember($params)
    {
        //是否已有该会员，该会员是否被禁用，无添加会员
        $memberInfo = $this
            ->get(['telphone'=>$params['telphone']]);
        if(empty($memberInfo))
        {
            if(empty($params['card_number'])){
                $params['card_number'] = $this->buildUniqidNo();
            }
//            if (empty($params['card_type']){
//                $params['card_type'] = 1;
//            }
//            Db::startTrans();
            try {
                $memberSave = $this
                    ->allowField(true)
                    ->save($params);
                if($memberSave)
                {
                    //添加会员绑定的病人
                    $params['member_id'] = $this->id;
                    model('PatientInMember')->allowField(true)->save($params);
                    //添加会员新增记录
                    $member = ['member_id'=>$this->id];
                    $content = '新增会员';
                    \app\admin\model\MemberOperateLog::record($member,$content);
                }
//                Db::commit();
                $data = ['id'=>$this->id];
                return ['code' => 1,'msg' => '新增会员','data' => $data];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => 0, 'msg' => $e->getMessage()];
            }
        } else {
            //检查会员下是否已绑定该病人，没绑定则新增绑定的病人
//            $patientInMemberInfo = model('PatientInMember')->get(['member_id'=>$memberInfo['id'],'name'=>$params['name']]);
//            if (empty($patientInMemberInfo)){
//                //添加会员绑定的病人
//                $params['member_id'] = $this->id;
//                model('PatientInMember')->allowField(true)->save($params);
//            }
            if ($memberInfo['status'] != 1)
            {
                return [ 'code' => 2,'msg' => '该会员手机号'.$params['telphone'].'已禁用','data'=>$memberInfo];
            }
            return [ 'code' => 3,'msg' => '该会员手机号'.$params['telphone'].'已注册','data' => $memberInfo];
        }
    }

    /**
     * @auth 郭庆波
     * 生成唯一会员编号
     * @return
     */
    public function buildUniqidNo()
    {
        $admim = session::get('admin');
        $no = Random::numeric(11);
        //检测是否存在
        $info = $this->where(['hos_id'=>$admim['hos_id'],'card_number'=>$no])->find();
        (!empty($info)) && $no = $this->buildUniqidNo();
        return $no;
    }

    /**
     * @auth 郭庆波
     * 禁用
     * @return
     */
//    public function forbidden($id)
//    {
//        if (empty($id)) {
//            return ['code' => 0, 'msg' => '会员id为空'];
//        }
////        $member = $this->get($id);
////        if (empty($member)) {
////            return ['code' => 0, 'msg' => '会员不存在'];
////        }
//        if ($this->where('id', $id)->update(['status' => 2, 'updatetime' => time()])) {
//            return ['code' => 1, 'msg' => '禁用成功'];
//        } else {
//            return ['code' => 0, 'msg' => '禁用失败'];
//        }
//    }

    /**
     * @auth 郭庆波
     * 软删除
     * @return
     */
//    public function softdelete($id)
//    {
//        if (empty($id)) {
//            return ['code' => 0, 'msg' => 'id为空'];
//        }
////        $member = $this->get($id);
////        if (empty($member)) {
////            return ['code' => 0, 'msg' => '不存在'];
////        }
//        if ($this->where('id', $id)->update(['status' => -1, 'updatetime' => time()])) {
//            return ['code' => 1, 'msg' => '删除成功'];
//        } else {
//            return ['code' => 0, 'msg' => '删除失败'];
//        }
//    }
        /**
         * 设置会员常用医生信息
         */
        public function setCommonDoctor($member_id)
        {
            $doctor_id = '';
            $memberUpdate = [];
            //预约医生
            $appointmentDoctor = model('Appointment')
                ->field('doctor_id,doctor_name,max(updatetime) as lasttime,count(*) as num')
                ->where([
                    'member_id' => $member_id
                ])
                ->group('doctor_id')
                ->order('num desc,lasttime desc')
                ->find();
            if (!empty($appointmentDoctor)){
                $doctor_id = $appointmentDoctor['doctor_id'];
            }
            //挂号医生
            $registerDoctor = model('Register')
                ->field('doctor_id,doctor_name,max(updatetime) as lasttime,count(*) as num')
                ->where([
                    'member_id' => $member_id
                ])
                ->group('doctor_id')
                ->order('num desc,lasttime desc')
                ->find();
            if (!empty($registerDoctor))
            {
                $condition1 = $registerDoctor['num'] == $appointmentDoctor['num'] && $registerDoctor['lasttime'] > $appointmentDoctor['lasttime'];
                $condition2 = $registerDoctor['num']>$appointmentDoctor['num'];
                if ($condition1 || $condition2){
                    $doctor_id = $registerDoctor['doctor_id'];
                }
            }
            if (!empty($doctor_id)){
                $commonDoctorInfo = model('admin')->get(['id'=>$doctor_id]);
                $memberUpdate = [
                    'doctor_id' =>  $commonDoctorInfo['id'],
                    'doctor_name' =>  $commonDoctorInfo['username'],
                    'common_hos_id' => $commonDoctorInfo['hos_id'],
                    'updatetime' => time()
                ];
            }
            $this->where(['id'=>$member_id])->update($memberUpdate);
        }
}
