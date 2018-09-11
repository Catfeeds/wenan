<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Patientinmembercase extends Backend
{

    /**
     * PatientInMemberCase模型对象
     */
    protected $model = null;

    protected $noNeedRight = ['index','add'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PatientInMemberCase');

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index($patientInMemberId = 0, $chooseBtn = 0, $patientId = 0)
    {
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

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //获取绑定病人的信息
        if (!empty($patientInMemberId)){
            $patientInMemberInfo = model('PatientInMember')->get($patientInMemberId);
            $this->assignconfig('patientInMemberInfo',$patientInMemberInfo);
        }
        //获取患者信息
        if (!empty($patientId)){
            $patienInfo = model('PatientVisitRecord')->get($patientId);
            $this->assignconfig('patientInfo',$patienInfo);
        }

        //获取会员信息
        $memberId = !empty($patientInMemberInfo) ? $patientInMemberInfo['member_id'] : $patienInfo['member_id'];
        $memberInfo = model('Member')->get($memberId);
        $this->assignconfig('memberInfo',$memberInfo);

        $this->assignconfig('chooseBtn', $chooseBtn);
        $this->view->assign('chooseBtn', $chooseBtn);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add($patientInMemberId = 0, $chooseBtn = 0, $patientId = 0)
    {
        if ($this->request->isPost())
        {
            $admin =Session::get('admin');
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['admin_id'] = $admin['id'];
                $params['admin_name'] = $admin['username'];
                $params['patient_in_member_id'] = $patientInMemberId;
                try
                {
                    $result = $this->model->save($params);
                    if ($result !== false)
                    {
                        //返回病例列表
                        $ajaxJumpUrl = '/admin/user/patientinmembercase/index/patientInMemberId/'.$patientInMemberId.'/chooseBtn/'.$chooseBtn.'/patientId/'.$patientId;
                        $this->success('操作成功',$ajaxJumpUrl);
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
        //获取患者信息
//        if (!empty($patientId)){
//            $patienInfo = model('PatientVisitRecord')->get($patientId);
//            $this->assign('patientInfo',$patienInfo);
//        }
        $this->assign('patientId',$patientId);
        $this->assign('chooseBtn', $chooseBtn);
        return $this->view->fetch();
    }

}
