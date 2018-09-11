<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Session;
use think\Controller;
use think\Request;

/**
 * 会员表
 *
 * @icon fa fa-circle-o
 */
class Chargeinfo extends Backend
{
    
    /**
     * Member模型对象
     */
    protected $model = null;

    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ChargeInfo');

        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index($ids = 0)
    {
        //获取会员信息
        $memberInfo = model('Member')->where('id',$ids)->find();
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
                ->where('member_id',$ids)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('member_id',$ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            if (!empty($list))
            {
                //卡内余额
                $this->HospitalModel = model('Hospital');
                foreach ($list as $key => &$val)
                {
                    //门店名称
                    $hosName = $this->HospitalModel
                        ->where('id',$val['hos_id'])
                        ->value('hos_name');
                    $val['hos_name'] =  $hosName;
                    $val['balance'] =  $memberInfo['balance'];
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        else
        {
            $this->systemDic = dc(['FEE_TYPE']);
            $this->assignconfig('fee_type', $this->systemDic['FEE_TYPE']);
//            $this->assignconfig('member_id', $ids);
            $this->assignconfig('memberInfo', $memberInfo);
        }
        return $this->view->fetch();
    }
    /**
     * 添加收费
     */
    public function add($patientInMemberId = 0, $patientId = 0)
    {
        //获取绑定病人的信息
        $patientInMemberInfo = model('PatientInMember')->get($patientInMemberId);
        if (empty($patientInMemberInfo)) {
            $this->error('患者不存在');
        }
        $this->assignconfig('patientInMemberInfo', $patientInMemberInfo);
        $memberId = $patientInMemberInfo['member_id'];
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $params = $this->request->post("row/a");
            if ($params) {
                $params['newfee'] = 1;
                $params['hos_id'] = $this->auth->hos_id;
                $params['admin_input_id'] = $this->auth->id;
                $params['admin_input_name'] = $this->auth->username;
                $params['patient_in_member_id'] = $patientInMemberId;
                $params['name'] = $patientInMemberInfo['name'];
                $params['doctor_name'] = '';
                $params['member_id'] = $memberId;
                $params['createtime'] = time();
                $res = $this->model->addFee($params);
                if ($res['code'] == 1) {
                    // 获取付款信息
                    $chargeInfo = $res['data'];
                    //获取会员卡余额
                    $balance = model('Member')->where('id',$memberId)->value('balance');
                    $chargeInfo['balance'] = $balance;

                    if ($patientId == 0) {
                        //返回会员详情
                        $ajaxJumpUrl = '/admin/user/member/detail/ids/' . $memberId;
                        $this->success('操作成功', $ajaxJumpUrl, $chargeInfo);
                    } elseif ($patientId == -1) {
                        $ajaxJumpUrl = '/admin/doctor/appointment';
                        $this->success('操作成功', $ajaxJumpUrl, $chargeInfo);
                    } else {
                        //返回患者详情
                        $ajaxJumpUrl = '/admin/user/patientvisitrecord/detail/ids/' . $patientId;
                         $this->success('操作成功', $ajaxJumpUrl ,$chargeInfo);
                    }
                } else {
                    $this->error($res['msg']);
                }
            }
            $this->error();
        }
        $feeType = dc('FEE_TYPE');
        if (!empty($feeType)) {
            foreach ($feeType as $k => $v) {
                if ($v == '挂号费') {
                    unset($feeType[$k]);
                }
            }
        }
        $feeId = 0;
        if (!empty($feeType)) {
            foreach ($feeType as $k => $v) {
                $feeId = $k;
                break;
            }
        }
        $hosFee = [];
        if (!empty($feeId)) {
            $hosFee = model('HosFee')->field('id, fee_name, price')->where('fee_id', $feeId)->where('status', 1)->select();
        }
        //print_r($hosFee);
        $hosFeeList = [];
        if (!empty($hosFee)) {
            foreach ($hosFee as $v) {
                $hosFeeList[$v['id']] = $v['fee_name'];
            }
        }
        $price = '';
        $hosFeeNmae = '';
        if (!empty($hosFee)) {
            foreach ($hosFee as $k => $v) {
                $price = $v['price'];
                $hosFeeNmae = $v['fee_name'];
                break;
            }
        }
        $this->view->assign('feeType', $feeType);
        $this->view->assign('member_id', $memberId);
        $this->view->assign('hosFeeList', $hosFeeList);
        $this->view->assign('price', $price);
        $this->view->assign('patientId', $patientId);
        $this->view->assign('hosFeeNmae', $hosFeeNmae);
        $this->assignconfig('patientId', $patientId);

        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error('费用不存在');
        }
        if ($row['status'] == 1) {
            $this->error('费用已支付，不可删除');
        }
        if ($row['admin_input_id'] != $this->auth->id) {
            $this->error('您不能操作别人的记录');
        }
        if ($ids) {
            if ($this->model->where('id', $ids)->delete()) {
                $this->success("删除成功!", '');
            } else {
                $this->error('删除失败');
            }
        }
        $this->error();
    }

    /**
     * 付款
     */
    public function pay($ids = NULL)
    {
        //获取当前账户信息
        if ($this->request->isPost())
        {
            $params = $this->request->post();
            if ($params)
            {
                $row = $this->model->get($params['id']);
                $already_paid = intval($params["already_paid"]);//付款金额
                if ((double)$row['already_paid']>=$row['should_pay'])
                {
                    return ['msg'=>'应付金额为'.$row['should_pay'].'元， 已付'.$row['already_paid'].'元, 无需重复付款'];
                }
                if ((double)$already_paid !== (double)$row['should_pay'])
                {
                    return ['msg'=>'应付金额为'.$row['should_pay'].'元， 实付'.$already_paid.'元, 请一次性付清'];
                }
                $params["admin_collect_id"] = $this->auth->id;
                $params["admin_collect_name"] = $this->auth->username;
//                $params["should_pay"] = 0;
                $params["already_paid"] = $already_paid + intval($row['already_paid']);
                $params["updatetime"] = time();
                $params["status"] = 1;
                try
                {
                    $result = $row->save($params);
                    if ($result !== false)
                    {
                        //更新会员常用门店，常用医生,最后消费时间
                        //常用医生，门店
                        $commonDoc = $this->model
                            ->field('hos_id,admin_input_id,admin_input_name,count(*) as num')
                            ->group('admin_input_id')
                            ->where('member_id',$ids)
                            ->order('num','desc')
                            ->find();
                        $memberUpdate = [
                            'doctor_id'=>$commonDoc['admin_input_id'],
                            'doctor_name'=>$commonDoc['admin_input_name'],
                            'common_hos_id'=>$commonDoc['hos_id'],
                            'last_consumption_time'=>$params["updatetime"]
                        ];
                        model('Member')
                            ->where('id',$row['member_id'])
                            ->update($memberUpdate);

                        //添加会员付款操作记录
                        $member = ['member_id'=>$row['member_id']];
                        $content = '患者-'.$row['name'].'付款：'.$already_paid.'元';

                        //以会员卡付款，更新会员余额
                        $this->MemberModel = model('Member');
                        $balance = $this->MemberModel
                            ->where('id',$row['member_id'])
                            ->value('balance');
                        $nowBalance = $balance - $already_paid;
                        $nowBalanceFormat = $nowBalance < 0?0:$nowBalance;
                        if ($params['pay_way'] == 4)
                        {
                            //以会员卡付款，更新会员余额
                            if ($nowBalance <0)
                            {
                                $content = '患者-'.$row['name'].'应付款：'.$already_paid.'元，会员卡余额不足，会员卡扣款：'.$balance.'元';
                            }
                            else
                            {
                                $content = '患者-'.$row['name'].'会员卡扣款：'.$already_paid.'元';
                            }
                            $this->MemberModel
                                ->where('id',$row['member_id'])
                                ->update(['balance'=> $nowBalanceFormat]);
                        }
                        \app\admin\model\MemberOperateLog::record($member,$content);

//                        //添加会员消费记录
//                        $consumptionInfo = [
//                            'member_id' => $row['member_id'],
//                            'consumption_type' => $row['fee_id'],
//                            'consumption_amount' => $already_paid,
//                            'balance' =>$nowBalanceFormat,
//                        ];
//                        model('MemberConsumption')->record($consumptionInfo);
                        return ['msg'=>'付款成功'];
                    }
                    else
                    {
                        return ['msg'=>'付款失败'];
                    }
                }
                catch (think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }
}

