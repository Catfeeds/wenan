<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Patientreturnrecord extends Backend
{
    
    /**
     * PatientReturnRecord模型对象
     */
    protected $model = null;

    protected $noNeedRight = ['index','add','edit','atreturntimesendmessage'];

    protected $noNeedLogin = ['atreturntimesendmessage'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PatientReturnRecord');

    }

    /**
     * 查看
     */
    public function index($patientInMemberId = 0, $chooseBtn = 0, $patientId = 0)
    {
        $restDay = '';
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('patient_in_member_id',$patientInMemberId)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('patient_in_member_id',$patientInMemberId)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            if ($list) {
                foreach ($list as $key => $value) {
                    $list[$key]['key'] = $key+1;
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        //获取绑定病人的信息
        if (!empty($patientInMemberId)){
            $patientInMemberInfo = model('PatientInMember')->get($patientInMemberId);
            $this->assignconfig('patientInMemberInfo',$patientInMemberInfo);
        }

        //获取患者回访信息
        if (!empty($patientId)){
            $patienInfo = model('PatientVisitRecord')->get($patientId);
            $this->assignconfig('patientInfo',$patienInfo);
        }

        //获取会员信息
        $memberId = !empty($patientInMemberInfo) ? $patientInMemberInfo['member_id'] : $patienInfo['member_id'];
        $memberInfo = model('Member')->get($memberId);
        $this->assignconfig('memberInfo',$memberInfo);

        //距离下次回访天数
        $where = [
            'next_time'=>['>=',strtotime(date('Y-m-d'))],
            'patient_in_member_id'=>$patientInMemberId,
        ];
        $nextTime = $this->model
            ->where($where)
            ->order('return_time', 'asc')
            ->value('next_time');
        if (!empty($nextTime)){
            $restDay = ceil(($nextTime-strtotime(date('Y-m-d')))/24/3600);
        }
        $this->assign('restDay',$restDay);
        $this->assign('chooseBtn',$chooseBtn);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add($patientInMemberId = 0, $chooseBtn = 0, $patientId = 0)
    {
        if ($this->request->isPost())
        {
            //获取患者信息
            $patientInMemberInfo = model('PatientInMember')->get($patientInMemberId);
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['return_time'] = strtotime($params['return_time']);
                $params['member_id'] = $patientInMemberInfo['member_id'];
                $params['patient_in_member_id'] = $patientInMemberId;
                $params['admin_id'] = $this->auth->id;
                $params['admin_name'] = $this->auth->username;
                if (!empty($patientInMemberInfo['return_cycle'])){
                    $params['next_time'] = intval($patientInMemberInfo['return_cycle'])*24*3600+$params['return_time'];
                }
                try
                {
                    $result = $this->model->save($params);
                    if ($result !== false)
                    {
                        //添加回访内容到回访内容搜索表
                        $contentData = [
                            'hos_id'=>$this->auth->hos_id,
                            'content'=>$params['content'],
                            'createtime'=>time(),
                            'updatetime'=>time()
                        ];
                        $is_exist = model('PatientReturnContent')->where('content',$params['content'])->find();
                        if (!$is_exist) {
                            model('PatientReturnContent')
                            ->insert($contentData);
                        }
                        
                        //添加会员操作记录
                        $member = ['member_id' => $patientInMemberInfo['member_id']];
                        $content = '添加'.$patientInMemberInfo['name'].'的回访记录';
                        \app\admin\model\MemberOperateLog::record($member, $content);

                        //返回回访列表
                        $callTime = date('Y-m-d H:00',$params['return_time']);
                        $msg = '操作成功,将于'.$callTime.'将回访短信发送至至该会员';
                        $ajaxJumpUrl = '/admin/user/patientreturnrecord/index/patientInMemberId/'.$patientInMemberId.'/chooseBtn/'.$chooseBtn.'/patientId/'.$patientId;
                        $this->success($msg,$ajaxJumpUrl);

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

        //获取绑定病人的信息
        if (!empty($patientInMemberId)){
            $patientInMemberInfo = model('PatientInMember')->get($patientInMemberId);
            $this->assign('patientInMemberInfo',$patientInMemberInfo);
        }

        $this->view->assign('patientId',$patientId);
        $this->view->assign('chooseBtn',$chooseBtn);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
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
            //获取患者信息
            $patientInMemberInfo = model('PatientInMember')->get($row['patient_in_member_id']);
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['return_time'] = strtotime($params['return_time']);
                if (!empty($patientInMemberInfo['return_cycle'])){
                    $params['next_time'] = intval($patientInMemberInfo['return_cycle'])*24*3600+$params['return_time'];
                }
                //如果回访时间修改，则重新发短信
                if($row['return_time'] != $params['return_time']){
                    $params['is_send'] = 0;
                }
                try
                {
                    $result = $row->save($params);
                    if ($result !== false)
                    {
                        //添加回访内容到回访内容搜索表
                        $contentData = [
                            'hos_id'=>$this->auth->hos_id,
                            'content'=>$params['content'],
                            'createtime'=>time(),
                            'updatetime'=>time()
                        ];
                        model('PatientReturnContent')
                            ->insert($contentData);

                        //添加会员操作记录
                        $patientName = model('PatientInMember')->where('id',$row['patient_in_member_id'])->value('name');
                        $member = ['member_id' => $row['member_id']];
                        $content = '修改'.$patientName.'的回访记录';
                        \app\admin\model\MemberOperateLog::record($member, $content);

                        //返回回访列表
                        $callTime = date('Y-m-d H:00',$params['return_time']);
                        $msg = '操作成功,将于'.$callTime.'将回访短信发送至至该会员';
                        $ajaxJumpUrl = '/admin/user/patientreturnrecord/index/patientInMemberId/'.$row['patient_in_member_id'].'/chooseBtn/1';
                        $this->success($msg,$ajaxJumpUrl);
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
