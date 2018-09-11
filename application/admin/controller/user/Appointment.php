<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

use think\Session;
use think\Controller;
use think\Request;

/**
 * 预约详情表
 *
 * @icon fa fa-circle-o
 */
class Appointment extends Backend
{

    /**
     * Appointment模型对象
     */
    protected $model = null;

    /**
     * 数据字典
     */
    protected $systemDic = [];

    /**
     * 预约间隔
     */
    protected $intervalTime = 5;

    /**
     * @var 作息时间
     */
    protected $restTime = ['start'=>9,'end'=>19,'rest'=>[]];

    protected $noNeedRight = ['appointmentoccupy','occupycancel','getappointmentevents','appointmentconfirm','appointmentcancel', 'addandconfirm','getappointmenttime'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Appointment');
        //预约间隔，index,add中用到
        $this->view->assign('intervalTime',$this->intervalTime);
    }

    /**
     * 查看
     */
    public function index()
    {
        //获取门店下有医生站权限的用户组
        $authGroups =model('AuthGroup')
            ->where(['hos_id'=>['in',[$this->auth->hos_id,0]],'status' => 0])
            ->field('id,rules')
            ->select();
        $authGroupIds = [];
        if (!empty($authGroups)){
            foreach ($authGroups as $key => $val){
                if (in_array(130,explode(',',$val['rules']))){
                    array_push($authGroupIds,$val['id']);
                }
            }
        }
        //获取医生
        $where = [
            'status' => 1,
            'hos_id' => $this->auth->hos_id,
            'group_id' => ['in',$authGroupIds]
        ];
        //如果是超级管理员
        if ($this->auth->group_id == 1)
        {
            unset($where['hos_id'],$where['group_id']);
        }
        $doctor = model('Admin')
            ->where($where)
            ->column('username','id');
        $activeDoctor = ['id'=>0, 'username'=>''];
        if (!empty($doctor)){
            $doctorId = !empty($doctor[$this->auth->id]) ? $this->auth->id : key($doctor);
            $activeDoctor = [
                'id'=>$doctorId,
                'username'=>$doctor[$doctorId]
            ];
        }
        //数据字典
        $this->systemDic = dc(['PROJECT_TYPE']);
//        $this->view->assign('systemDic',$this->systemDic);
        //当前医生id
        $this->view->assign('activeDoctor',$activeDoctor);
        $this->view->assign('doctors',$doctor);
        $this->assignconfig('project_type',$this->systemDic['PROJECT_TYPE']);

        //获取尚未确认的预约
        $where = [
            'is_occupy'=>0,
            'doctor_id'=>$this->auth->id,
            'status'=>1,
            'start_time'=>['<',time()-5*60],
        ];
        $unConfirmNum = $this->model
            ->where($where)
            ->count();
        $this->view->assign('unConfirmNum',$unConfirmNum);
        return $this->view->fetch();
    }

    /**
     * 添加
     * 1.根据手机号检查是否为会员
     *      1.1不是添加会员
     * 2.根据手机号与姓名检查患者表中是否有记录
     *      2.1有更新
     *      2.2无插入
     */
    public function add($appointTime = 0,$doctorId = 0,$patientInMemberId = 0, $tag = 0)
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                //获取预约医生信息
                $doctorInfo = model('Admin')->get($params['doctor_id']);
                if(empty($doctorInfo)){
                    $this->error('当前预约医生无所属门店');
                }
                $params['admin_name'] = $this->auth->username;
                $params['admin_id'] = $this->auth->id;
                $params['hos_id'] = $doctorInfo['hos_id'];
                $params['start_time'] = strtotime($params['day']." ".$params['start_time']);
                $params['end_time'] = strtotime($params['day']." ".$params['end_time']);
                $params['day'] = strtotime($params['day']);

                try
                {
                    //检查该预约时间段，是否有效
                    $isEffective = $this->checkAppointmentTime($params);
                    if ($isEffective['success'] ===false)
                    {
                        $this->error($isEffective['msg'], '');
                    }

                    //检查是否设置签约费
                    if ($tag == 1){
                        $registerFee = model('HosFee')
                            ->get(['fee_id'=>1,'status' => 1]);
                        if (empty($registerFee)){
                            $this->error('未设置挂号费');
                        }
                    }

                    //是否已有该会员，无则添加会员
                    $this->MemberModel = model('Member');
                    $memberId = $this->MemberModel->addMember($params);
                    if ($memberId['code'] == 2)
                    {
                        $this->error($memberId['msg']);
                    }
                    $params['member_id'] = $memberId['data']['id'];

                    //根据病人名称检查会员下是否已绑定该病人
                    $PatientInMemberId = model('PatientInMember')->addPatient($params);
                    $params['patient_in_member_id'] = $PatientInMemberId['data']['id'];

                    //添加患者记录
                    $params['doctor_appointment_id'] = $params['doctor_id'];
                    $params['doctor_appointment_name'] = $params['doctor_name'];
                    $params['appointment_time'] = $params['start_time'];

                    $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
                    $params['name_pinyin'] = $pinyin->permalink($params['name'],'');

                    //添加患者来访记录
                    $this->PatientVisitRecordModel = model('PatientVisitRecord');
                    $patientAdd = $this->PatientVisitRecordModel
                        ->allowField(true)
                        ->save($params);
                    $params['patient_visit_record_id'] = $this->PatientVisitRecordModel->id;
//                    //根据姓名和手机号查询患者表中是否已插入，有则更新，无则插入
//                    $params['doctor_appointment_id'] = $params['doctor_id'];
//                    $params['doctor_appointment_name'] = $params['doctor_name'];
//                    $params['appointment_time'] = $params['start_time'];
//                    $patientId = model('PatientVisitRecord')
//                        ->patientIsExist($params);
//                    $params['patient_visit_record_id'] = $patientId['id'];

                    //添加预约
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false)
                    {

                        //更新会员常用医生及门店
                        $this->MemberModel->setCommonDoctor($params['member_id']);

                        //添加会员挂号记录
                        $member = ['member_id'=>$params['member_id']];
                        $content = '添加会员预约';
                        \app\admin\model\MemberOperateLog::record($member,$content);
                        if ($tag == 1){
                            $this->appointmentConfirm($this->model->id,$tag);
                        }
                        $this->success('操作成功','',['tag'=>$tag]);
                    }
                    else
                    {
                        $this->error($this->model->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        else
        {

            //获取门店下有医生站权限的用户组
            $authGroups =model('AuthGroup')
                ->where(['hos_id'=>['in',[$this->auth->hos_id,0]],'status' => 0])
                ->field('id,rules')
                ->select();
            $authGroupIds = [];
            if (!empty($authGroups)){
                foreach ($authGroups as $key => $val){
                    if (in_array(130,explode(',',$val['rules']))){
                        array_push($authGroupIds,$val['id']);
                    }
                }
            }
            //获取医生
            $where = [
                'status' => 1,
                'hos_id' => $this->auth->hos_id,
                'group_id' => ['in',$authGroupIds]
            ];
            //如果是超级管理员
            if ($this->auth->group_id == 1)
            {
                unset($where['hos_id']);
                unset($where['group_id']);
            }
            $doctor = model('Admin')
                ->where($where)
                ->column('username','id');
            $activeDoctor = ['id'=>0, 'username'=>''];
            if (!empty($doctor)){
                $doctorId = !empty($doctor[$this->auth->id]) ? $this->auth->id : key($doctor);
                $activeDoctor = [
                    'id'=>$doctorId,
                    'username'=>$doctor[$doctorId]
                ];
            }
            $this->view->assign("activeDoctor", $activeDoctor);
            $this->view->assign("doctor", $doctor);
            //获取数据字典
            $this->systemDic =  dc(['PROJECT_TYPE']);
            $this->view->assign("systemDic", $this->systemDic);

            //获取有效预约时间(只有从预约表里选择，$appointTime会传值)
            $appointTimeArr = $this->getAppointmentTime($appointTime,$doctorId);

            //获取医生预约时间间隔
//            $appointInterval = model('AdminAccount')
//                ->where(['admin_id'=>$doctorId])
//                ->value('appoint_interval');
//            if (!empty($appointInterval)){//默认时间间隔5分钟
//                $this->intervalTime = $appointInterval;
//            }
            $this->assignconfig('appointTimeArr', $appointTimeArr);



//            $appointTime = !empty($appointTime) ? $appointTime : (ceil(time()/($this->intervalTime*60)))*($this->intervalTime*60);
//            $appointTimeArr = [
//                'start_time'=> $appointTime,
//                'end_time' =>$appointTime+$this->intervalTime*60,
//            ];
            //如果是从表格预约，设置预约时间
//            if(!empty($appointTime) && !empty($doctorId)){
//                //获取当前时间格内最后预约时间
//                $whereCellLast = [
//                    'end_time'=>[['>',$appointTime],['<',$appointTime+30*60]],
//                ];
//                $cellLastAppointmentTime = $this->model
//                    ->where($whereCellLast)
//                    ->order('end_time','desc')
//                    ->value('end_time');
//                if (!empty($cellLastAppointmentTime)){
//                     $appointTime = $cellLastAppointmentTime;
//                }
//                $appointTimeArr['start_time'] = (ceil($appointTime/($this->intervalTime*60)))*($this->intervalTime*60);
//                $appointTimeArr['end_time'] = $appointTimeArr['start_time']+$this->intervalTime*60;
//            }
            $this->view->assign("appointTime", $appointTimeArr);

            //从患者详情中跳转，添加患者信息
            $this->assignconfig('tag',$tag);//$tag 挂号新增标识符
            $patientInfo = [
                'member_id' =>'',
                'member_name'=>'',
                'telphone' =>'',
                'patient_name' =>'',
                'patient_in_member_id' =>'',
                'gender' =>1,
            ];
            if (!empty($patientInMemberId)){
                $patientInfo = model('Member')
                    ->alias('m')
                    ->join('wa_patient_in_member p','m.id = p.member_id and p.id = '.$patientInMemberId)
                    ->field('m.id as member_id,m.name as member_name,m.telphone,p.name as patient_name,p.id as patient_in_member_id,p.gender')
                    ->find();
            }
            $this->view->assign("patientInfo", $patientInfo);
        }
        return $this->view->fetch();
    }


    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
//        $admin = Session::get('admin');
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                //获取预约医生信息
                $doctorInfo = model('Admin')->get($params['doctor_id']);
                if(empty($doctorInfo)){
                    $this->error('当前预约医生无所属门店');
                }
                $params['hos_id'] = $doctorInfo['hos_id'];
                $params['id'] = $ids;
                $params['start_time'] = strtotime($params['day']." ".$params['start_time']);
                $params['end_time'] = strtotime($params['day']." ".$params['end_time']);
                $params['day'] = strtotime($params['day']);
                $params['status'] = 1;

                foreach ($params as $k => &$v)
                {
                    $v = is_array($v) ? implode(',', $v) : $v;
                }
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    //检查该预约时间段，是否有效
                    $isEffective = $this->checkAppointmentTime($params);
                    if ($isEffective['success'] ===false)
                    {
                        $this->error($isEffective['msg'], '');
                    }
                    //是否已有该会员，无则添加会员
                    $this->MemberModel = model('Member');
                    $memberId = $this->MemberModel->addMember($params);
                    if ($memberId['code'] == 2)
                    {
                        $this->error($memberId['msg']);
                    }
                    $params['member_id'] = $memberId['data']['id'];

                    //根据病人名称检查会员下是否已绑定该病人
                    $PatientInMemberId = model('PatientInMember')->addPatient($params);
                    $params['patient_in_member_id'] = $PatientInMemberId['data']['id'];

                    //更新患者表
                    $params['doctor_appointment_id'] = $params['doctor_id'];
                    $params['doctor_appointment_name'] = $params['doctor_name'];
                    $params['appointment_time'] = $params['start_time'];
                    $params['patient_visit_record_id'] = $row->patient_visit_record_id;

                    $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
                    $params['name_pinyin'] = $pinyin->permalink($params['name'],'');

                    $patientUpdate = model('PatientVisitRecord')
                        ->allowField(true)
                        ->save($params,['id'=>$params['patient_visit_record_id']]);
                    //根据姓名和手机号查询患者表中是否已插入，有则更新，无则插入
//                    $params['doctor_appointment_id'] = $params['doctor_id'];
//                    $params['doctor_appointment_name'] = $params['doctor_name'];
//                    $params['appointment_time'] = $params['start_time'];
//                    $patientId = model('PatientVisitRecord')
//                        ->patientIsExist($params);
//                    $params['patient_visit_record_id'] = $patientId['id'];

                    //添加预约
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false)
                    {
                        //更新会员常用医生及门店
                        $this->MemberModel->setCommonDoctor($params['member_id']);

                        //添加会员挂号记录
                        $member = ['member_id'=>$params['member_id']];
                        $content = '修改会员预约';
                        \app\admin\model\MemberOperateLog::record($member,$content);

                        $this->success();
                    }
                    else
                    {
                        $this->error($row->getError());
                    }
                }
                catch (think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取门店下有医生站权限的用户组
        $authGroups =model('AuthGroup')
            ->where(['hos_id'=>['in',[$this->auth->hos_id,0]],'status' => 0])
            ->field('id,rules')
            ->select();
        $authGroupIds = [];
        if (!empty($authGroups)){
            foreach ($authGroups as $key => $val){
                if (in_array(130,explode(',',$val['rules']))){
                    array_push($authGroupIds,$val['id']);
                }
            }
        }
        //获取医生
        $where = [
            'status' => 1,
            'hos_id' => $this->auth->hos_id,
            'group_id' => ['in',$authGroupIds]
        ];
        //如果是超级管理员
        if ($this->auth->group_id == 1)
        {
            unset($where['hos_id']);
            unset($where['group_id']);
        }
        $doctor = model('Admin')
            ->where($where)
            ->column('username','id');
        $this->view->assign("doctor", $doctor);
        //获取数据字典
        $this->systemDic =  dc(['PROJECT_TYPE']);
        $this->view->assign("systemDic", $this->systemDic);
        //获取会员手机号
        $row['telphone'] = model('Member')->where('id',$row['member_id'])->value('telphone');
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
 * 软删除
 */
    public function appointmentCancel($ids = "")
    {
        if ($ids)
        {
//            $admin = Session::get('admin');
            $row = $this->model->get($ids);
            $params = $row->toArray();
            $chargeInfoId = '';

            //判断预约取消时间是否在24小时内，是则收取挂号费
            if((time()+24*60*60-$params['start_time'])>=0 && $row['charge_info_id'] <= 0){
                $chargeInfoId = $this->getRegisterFee($params);
            }
//            $params['charge_info_id'] = $chargeInfoId;
            $update = [
                'status' => 0,
                'charge_info_id'=>$chargeInfoId,
                'updatetime' => time(),
            ];
            //更新预约收费id
            $row->save($update);

            //更新来访收费id
            $res = model('PatientVisitRecord')
                ->where('id', $row['patient_visit_record_id'])
                ->update($update);

            //获取尚未确认的预约
            $where = [
                'is_occupy'=>0,
                'doctor_id'=>$this->auth->id,
                'status'=>1,
                'start_time'=>['<',time()-5*60],
            ];
            $unConfirmNum = $this->model
                ->where($where)
                ->count();

            if ($res)
            {
                $this->success('操作成功','',['unConfirmNum'=>$unConfirmNum]);
            }
            else
            {
                $this->error("占用时间删除失败");
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    /**
     * 确认预约
     */
    public function appointmentConfirm($ids = 0,$tag = 0)
    {
        if ($ids)
        {
            $row = $this->model->get(['id'=>$ids]);
            $row->save(['status' => 2, 'updatetime' => time()]);
            $params = $row->toArray();
            //新增挂号
            //挂号费
            $chargeInfoId = $this->getRegisterFee($params);
            $params['charge_info_id'] = $chargeInfoId;
            $params['register_time'] = time();
            unset($params['id']);

            model('Register')
                ->allowField(true)
                ->save($params);

            $update = [
                'charge_info_id'=>$chargeInfoId,
                'updatetime' => time(),
            ];

            //更新预约收费id
            $row->save($update);

            //更新患者来访记录
            $patientVisitRecord = [
                'charge_info_id'=>$chargeInfoId,
                'updatetime' => time(),
                'register_time' => time(),
            ];
            model('PatientVisitRecord')
                ->where('id', $row['patient_visit_record_id'])
                ->update($patientVisitRecord);

            //获取尚未确认的预约
            $where = [
                'is_occupy'=>0,
                'doctor_id'=>$this->auth->id,
                'status'=>1,
                'start_time'=>['<',time()-5*60],
            ];

            //更新尚未确认的预约数量
            $unConfirmNum = $this->model
                ->where($where)
                ->count();
            $this->success('操作成功','',['unConfirmNum'=>$unConfirmNum,'tag'=>$tag]);
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 获取预约事件
     */
    public function getAppointmentEvents()
    {
        if ($this->request->isAjax()) {
            $ret = ['code'=>0, 'msg'=>'', 'data'=>[],];
            $businessHoursItem = ['dow'=>[0,1,2,3,4,5,6], 'start'=>'09:00', 'end'=> '18:00',];
            $businessHours = [];
            //当前用户信息
//            $admin = Session::get('admin')->toArray();
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $params = $this->request->post();
            $params['hos_id'] = $this->auth->hos_id;
            $where =[
                'hos_id' => $this->auth->hos_id,
                'status' => ['in',[1,2]],
                'day' => ['between',[$params['start'],$params['end']]],
            ];
            //如果是超级管理员
            if ($this->auth->group_id == 1)
            {
                unset($where['hos_id']);
            }
            if (!empty($params['doctor_id'])){
                //获取预约医生信息
                $doctorInfo = model('Admin')->get($params['doctor_id']);
                if(empty($doctorInfo)){
                    $this->error('当前预约医生无所属门店');
                }
                $params['hos_id'] = $doctorInfo['hos_id'];
                //获取当前医生本周工作时间
                $restDay = model('HosStaffRest')
                    ->where([
                        'admin_id' => $params['doctor_id'],
                        'rest_day' => [['>=',date('Y-m-d',$params['start'])], ['<',date('Y-m-d',$params['end'])]],
                    ])
                    ->column('rest_day');
                if (!empty($restDay)){
                    foreach ($restDay as $key=>$val){
                        $w = date("w",strtotime($val));
                        unset($businessHoursItem['dow'][$w]);
                    }
                }
                $where['doctor_id'] = $params['doctor_id'];
            }
            //是否处于工作时间
            $this->HosRestModel = model('HosRest');
            $condArr = [
                'hos_id'=>$params['hos_id'],
            ];
            $count = $this->HosRestModel
                ->where(['hos_id'=>$params['hos_id'],])
                ->count();
            if (empty($count)){//没有设置作息时间则取默认时间
                $condArr['hos_id'] = 0;
            }
            $restTime = $this->HosRestModel
                ->where($condArr)
                ->select();
            if (!empty($restTime)){
                foreach ($restTime as $key=>$val){
                    $businessHoursItem['start'] = $val['start_time'];
                    $businessHoursItem['end'] = $val['end_time'];
                    $businessHoursItem['dow'] = array_values($businessHoursItem['dow']);
                    $businessHours[] = $businessHoursItem;
                }
            }else{
                $businessHoursItem['dow'] = array_values($businessHoursItem['dow']);
                $businessHours[] = $businessHoursItem;
            }
            $ret['data']['businessHours'] = $businessHours;
            $appointmentInfo = $this->model
                ->where($where)
                ->select();
            if (!empty($appointmentInfo)){
                foreach ($appointmentInfo as $key => &$val){
                    //获取会员手机号
                    $val['telphone'] = model('Member')->where('id',$val['member_id'])->value('telphone');
                }
            }
            $ret['data']['events'] = $appointmentInfo;
            return $ret;
        }else{
            $this->error("非法请求");
        }
    }

    /**
     * 添加预约时间占用记录
     *
     */
    public function appointmentOccupy()
    {
//        $admin = Session::get('admin');
        if ($this->request->isPost())
        {
            $params = $this->request->post();
            if ($params)
            {
                //获取预约医生信息
                $doctorInfo = model('Admin')->get($params['doctor_id']);
                if(empty($doctorInfo)){
                    $this->error('当前预约医生无所属门店');
                }
                $params['hos_id'] = $doctorInfo['hos_id'];
                $params['day'] = strtotime(date('Y-m-d',$params['start_time']));
                $params['is_occupy'] = 1;
                $params['status'] = 1;
                try
                {
                    //判断是否已被占用
                    $where1 = [
                        'status' => ['in',[1,2]],
                        'doctor_id' => $params['doctor_id'],
                    ];
                    $where2 = '(start_time >= '.$params['start_time'].' and start_time <'.$params['end_time'].') 
                             or (end_time > '.$params['start_time'].' and end_time<='.$params['end_time'].')
                             or (end_time > '.$params['end_time'].' and start_time<'.$params['start_time'].')';
                    $row = $this->model
                        ->where($where1)
                        ->where($where2)
                        ->find();
                    if($row){
                        $this->error("该时间段已有预约或已被占用");
                    }
                    //添加预约
                    $result = $this->model
                        ->allowField(true)
                        ->save($params);
                    if ($result !== false)
                    {
                        return json_encode(['id'=>$this->model->id]);
                    }
                    else
                    {
                        $this->error($this->model->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    /**
     * 删除预约时间占用记录
     */
    public function occupyCancel()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post();
            if ($params)
            {
                $row = $this->model->get(['id'=>$params['id']]);
                $res = $row->save(['status' => -1, 'updatetime' => time()]);
                if ($res)
                {
                    $this->success("取消成功");
                }
                else
                {
                    $this->error("占用时间删除失败");
                }
            }
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

    }

    /**
     * 检查预约时间是否有效
     */
    public function checkAppointmentTime($params)
    {

        //预约开始时间不能为过去时间
        if($params['start_time']<time()){
            return ['success'=>false,'msg'=>"不可预约过去时间"];
        }
        //预约开始时间是否小于预约结束时间
        //预约开始时间不能为过去时间
        if($params['start_time']>=$params['end_time']){
            return ['success'=>false,'msg'=>"预约开始时间不能大于预约结束时间"];
        }
        //检查当前预约的医生，该时间段是否被占用
        //1.该时间段是否在休息日 2.是否下班 3.是否已占用 4.预约人数是否已满
        $isRest = model('HosStaffRest')
            ->where([
                "admin_id" => $params['doctor_id'],
               "rest_day" => date("Y-m-d",$params['day'])
            ])
            ->find();
        if($isRest){
            return ['success'=>false,'msg'=>"该预约人今天休息"];
        }
        //判断预约时间间隔是否与该医生的设置一致

        //当前预约的时间间隔
        $nowAppointInterval = (intval($params['end_time'])-intval($params['start_time']))/60;

        //获取医生预约时间间隔
        $appointInterval = model('AdminAccount')
            ->where(['admin_id'=> $params['doctor_id']])
            ->value('appoint_interval');
        $appointInterval = !empty($appointInterval) ? $appointInterval : $this->intervalTime;
        if ($nowAppointInterval != $appointInterval){//默认时间间隔5分钟
            return ['success'=>false,'msg'=>"预约时间间隔与该预约医生的设置不一致"];
        }
        //是否处于工作时间
        //1.如果医院没有设置自己的工作作息时间，获取默认作息时间
        $this->HosRestModel = model('HosRest');
        $condArr = [
            'hos_id'=>$params['hos_id'],
            'start_time' => ['<=',date('H:i',$params['start_time'])],
            'end_time' => ['>=',date('H:i',$params['start_time'])],
        ];
         $count = $this->HosRestModel
                ->where(['hos_id'=>$params['hos_id'],])
                ->count();
           if (empty($count)){
               $condArr['hos_id'] = 0;
           }

        $restTime = $this->HosRestModel
            ->where($condArr)
            ->find();
        if (empty($restTime)){
            return ['success'=>false,'msg'=>"预约医生已下班"];
        }
//        if(!empty($restTime)){
//            foreach ($restTime as $key => $val)
//            {
//                $val['start_time'] = strtotime(date('Y-m-d',$params['start_time'])." ".$val['start_time']);
//                $val['end_time'] = strtotime(date('Y-m-d',$params['start_time'])." ".$val['end_time']);
//                $iSstartIn = $params['start_time']>$val['start_time'] && $params['start_time']<$val['end_time'];
//                $isEndIn = $params['end_time']>$val['start_time'] && $params['end_time']<$val['end_time'];
//                if($iSstartIn || $isEndIn)
//                {
//                    return ['success'=>false,'msg'=>"预约医生已下班"];
//                    break;
//                }
//            }
//
//        }
        //公用条件
        $where1 = [
            'status' => ['in',[1,2]],
            'doctor_id' => $params['doctor_id'],
        ];
        $where2 = '(start_time >= '.$params['start_time'].' and start_time <'.$params['end_time'].') 
                 or (end_time > '.$params['start_time'].' and end_time<='.$params['end_time'].')
                 or (end_time > '.$params['end_time'].' and start_time<'.$params['start_time'].')';

        if(!empty($params['id'])){
            $where1['id'] = ['<>',$params['id']];
        }
        //是否已占用(包括占用，以及改时间已有预约)
        $isRest = $this->model
            ->where($where1)
            ->where($where2)
            ->find();
        if($isRest){
            return ['success'=>false,'msg'=>"该时间段已占用或已有预约"];
        }

        //该时间段预约数量是否过多（默认最多6个）
        $isBeyond  = $this->model
            ->where('is_occupy',0)
            ->where($where1)
            ->where($where2)
            ->count();
        if($isBeyond>6){
            return ['success'=>false,'msg'=>"预约人数已满"];
        }
        return ['success'=>true];
    }

    /**
     * 收取挂号费
     */
    public function getRegisterFee($params)
    {
//        $admin = Session::get('admin');
        //获取收费项目管理中的挂号费用
        $registerFee = model('HosFee')
            ->get(['fee_id'=>1,'status' => 1]);
        if(!empty($registerFee))
        {
            //添加挂号费费信息
            $params['admin_input_id'] = $this->auth->id;
            $params['admin_input_name'] = $this->auth->username;
            $params['should_pay'] = $registerFee['price'];
            $params['status'] = 0;
            $params['fee_id'] = $registerFee['fee_id'];
            $params['hos_fee_id'] = $registerFee['id'];
            $params['hos_fee_name'] = $registerFee['fee_name'];
            unset($params['id']);
            $chargeInfoSave =   model('ChargeInfo')
                ->addFee($params);
            $params['charge_info_id'] = $chargeInfoSave['data']['id'];
            if(!$chargeInfoSave)
            {
                $this->error('消费信息添加失败');
            }
            return $chargeInfoSave['data']['id'];
        }else{
            $this->error('未设置挂号费');
        }
    }

    /**
     *获取时间间隔
     */
//    public function getIntervalTime($doctorId = 0)
//    {
//        //获取医生预约时间间隔
//        $appointInterval = model('AdminAccount')
//            ->where(['admin_id'=>$doctorId])
//            ->value('appoint_interval');
//        if (!empty($appointInterval)){//默认时间间隔5分钟
//            $intervalTime = $appointInterval;
//        }else{
//            $intervalTime = $this->intervalTime;
//        }
//        // return $intervalTime;
//        $appointTime = !empty($appointTime) ? $appointTime : (ceil(time()/($intervalTime*60)))*($intervalTime*60);
//        $appointTimeArr = [
//            'start_time'=> date('H:i',$appointTime),
//            'end_time' => date('H:i',$appointTime+$intervalTime*60),
//        ];
//        return $appointTimeArr;
//    }

    /**
     * 计算医生预约有效时间段
     */
    public function getAppointmentTime($startTime = 0,$doctorId = 0, $intervalTime = 0)
    {
        if (empty($intervalTime)){//获取预约时间间隔
            $intervalTime = model('AdminAccount')
                ->where(['admin_id'=>$doctorId])
                ->value('appoint_interval');
            $intervalTime = !empty($intervalTime) ? $intervalTime : $this->intervalTime;
        }
        $startTime = !empty($startTime) ? (ceil($startTime/($intervalTime*60)))*($intervalTime*60) : (ceil(time()/($intervalTime*60)))*($intervalTime*60);
        $endTime = $startTime + $intervalTime*60;

        //预约时间
        $appointTimeArr = [
            'start_time'=> $startTime,
            'end_time' => $endTime,
            'interval_time' => $intervalTime
        ];
        //获取最近非休息日时间
        $restDay = model('HosStaffRest')
            ->where([
                "admin_id" => $doctorId,
                "rest_day" => date("Y-m-d",$startTime)
            ])
            ->find();
        if (!empty($restDay)){
            //获取上班时间
            $condArr = [
                'hos_id'=>$this->auth->hos_id,
            ];
            $hosRest = model('HosRest')
                ->where($condArr)
                ->find();
            if (empty($hosRest)){
                $condArr['hos_id'] = 0;
                $hosRest = model('HosRest')
                    ->where($condArr)
                    ->find();
            }
            $dateStr = date("Y-m-d",$startTime + 24*60*60).' '.$hosRest['start_time'];
            $startTime = strtotime($dateStr);
            $appointTimeArr = $this->getAppointmentTime($startTime ,$doctorId);
        }
        //获取最近有效预约时间
        $where =  '( end_time >  '.$startTime.' and end_time < '.$endTime.') 
                or ( start_time >'.$startTime.' and start_time < '.$endTime.') 
                or ( start_time <='.$startTime.' and end_time >= '.$endTime.')' ;

        $appointment = $this->model
            ->where('doctor_id',$doctorId)
            ->where($where)
            ->find();
        if (!empty($appointment)){
            if ($appointment['end_time'] > $startTime){
                $startTime =  ceil($appointment['end_time']/($intervalTime*60))*($intervalTime*60);
            }else{
                $startTime = $endTime;
            }
            $appointTimeArr = $this->getAppointmentTime($startTime ,$doctorId);
        }
        return $appointTimeArr;
    }

}
