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
class Member extends Backend
{
    
    /**
     * Member模型对象
     */
    protected $model = null;

    protected $systemDic = [];

    protected $noNeedRight = ['checktelphone','getmember'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Member');
        $this->systemDic = dc(['CARD_TYPE','RELATION_TYPE']);
        $this->view->assign("systemDic",$this->systemDic);
    }

    /**
     * 查看
     */
    public function index()
    {
        //获取当前账户信息
//        $admin = Session::get('admin')->toArray();
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
                ->where('status > 0')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('status > 0')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            // 必须将结果集转换为数组
            $list = collection($list)->toArray();
            if(!empty($list))
            {
                $this->PatientInMemberModel = model('PatientInMember');
                foreach ($list as $key => &$val)
                {
                    //获取绑定医生数
                    $val['patient_num'] = $this->PatientInMemberModel
                        ->where('member_id',$val['id'])
                        ->count();
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        else
        {
            //会员卡类型
            $this->assignconfig('card_type',  $this->systemDic['CARD_TYPE']);
            //获取医生
            $doctors = model('admin')
                ->where([
                    'status' => 1,
//                    'hos_id' => $admin['hos_id']
                ])
                ->column('username','id');
            $this->assignconfig('doctors',  $doctors);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        //获取当前账户信息
//        $admin = Session::get('admin')->toArray();
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['birth_time'] = strtotime($params['birth_time']);
                $params['open_time'] = strtotime($params['open_time']);
                $params['hos_id'] = $this->auth->hos_id;
                foreach ($params as $k => &$v)
                {
                    $v = is_array($v) ? implode(',', $v) : $v;
                }
                if ($this->dataLimit)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }

                    //是否已有该会员，无则添加会员
                    $params['open_time'] = time();
                    $params['open_member'] = 1;
                    //新增会员标识符
                    $params['add_member'] = 1;
                    $memberId = $this->model->addMember($params);
                    if ($memberId['code'] == 3)
                    {
                        $this->error($memberId['msg']);
                    }
                    $params['member_id'] = $memberId['data']['id'];

                    //检查新增会员时，是否充值,充值则添加充值记录
                    if($params['balance'] > 0 && $params['pay_way'] > 0)
                    {
                        //添加会员充值记录
                        $member = ['member_id'=>$params['member_id']];
                        $content = '充值：'.$params["balance"].'元';
                        \app\admin\model\MemberOperateLog::record($member,$content);
                    }

                    //根据姓名和手机号查询患者表中是否已插入，有则更新，无则插入
//                    $patientId = model('PatientVisitRecord')
//                        ->patientIsExist($params);
//                    if ($patientId['msg'] == 'add' || $patientId['msg'] == 'update')
//                    {
//                        $this->success();
//                    }
                    $this->success();
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
            //门店名称
            $hosName = model('Hospital')
                ->where('id',$this->auth->hos_id)
                ->value('hos_name');
            $this->view->assign("hosName", $hosName);

            //会员卡号
            $cardNumber = $this->model->buildUniqidNo();
            $this->view->assign('card_number',$cardNumber);

        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);

        //更改日志内容
        $oldArr = $row->toArray();

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
                //检查手机号是否重复
                $checkMember = $this->model->where(['id' => ['<>',$ids],'telphone' => $params['telphone']])->count();
                if (!empty($checkMember)){
                    $this->error('该会员手机号，已被注册');
                }
                $params['birth_time'] = strtotime($params['birth_time']);
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
                    $result = $row->save($params);
                    if ($result !== false)
                    {
                        //更改日志内容
//                        $oldArr = $row->toArray();
                        $newArr = $params;
                        $content = $this->array_diff_constent($oldArr,$newArr);
                        if(!empty($content)){
                            $member = ['member_id'=>$ids];
                            \app\admin\model\MemberOperateLog::record($member,$content);
                        }
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
            $this->error(__('未作任何修改', ''));
        }
        else
        {
            //门店名称
            $row['hosName'] = model('Hospital')
                ->where('id',$row['hos_id'])
                ->value('hos_name');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
        {
            $this->error(__('No Results were found'));
        }
        else
        {
            //重构数据
            $row = $row->toArray();
            $this->HospitalModel = model('Hospital');

            //获取创建门店名
            $row['hos_name'] = $this->HospitalModel
                ->where('id',$row['hos_id'])
                ->value('hos_name');

            //绑定患者信息
            $row['patient_in_member'] = model('PatientInMember')
                ->where(['member_id'=>$row['id']])
                ->select();
            //最后消费时间
//            $row['lastConsumptionTime'] = model('MemberConsumption')
//                ->where('member_id',$ids)
//                ->order('createtime','desc')
//                ->limit(1)
//                ->value('createtime');

            //常用门店
//            $commonHos = model('MemberConsumption')
//                ->field('hos_id,count(*) as num')
//                ->group('hos_id')
//                ->where('member_id',$ids)
//                ->order('num','desc')
//                ->find();
            //常用门店名
            $row['commonHosName'] =  $this->HospitalModel
                ->where('id',$row['common_hos_id'])
                ->value('hos_name');
            //总消费记录总数
            $row['consumptionCount'] = model('ChargeInfo')
                ->where('member_id',$ids)
                ->count();

            //会员总操作次数操作
            $this->MemberOperateLogModel = model('MemberOperateLog');
            $row['operateCount'] =  $this->MemberOperateLogModel
                ->where('member_id',$ids)
                ->count();

            //最近操作者
            $operatorId =  $this->MemberOperateLogModel
                ->where('member_id',$ids)
                ->order('createtime','desc')
                ->limit(1)
                ->value('admin_id');

            //最近操作者姓名
            $row['lastOperatorName'] = model('Admin')
                ->where('id',$operatorId)
                ->value('username');

            $row['gender'] = $row['gender'] == 1?'男':'女';
            $row['age'] =$row['birth_time'] ? (date('Y')-date('Y',$row['birth_time'])+1).'岁' : '';
        }
        $this->view->assign("row", $row);
        $this->assignconfig('memberId',$ids);
        return $this->view->fetch();
    }

    /**
     * 禁用/启用切换
     */
    public function forbidden($ids = "")
    {
        //更新数据
        $update = [
            'updatetime' => time()
        ];
        if ($ids)
        {
            $row = $this->model->get(['id' => $ids]);
            if ($row['status'] == 1){
                $update['status'] = 2;
            }
            if ($row['status'] == 2){
                $update['status'] = 1;
            }

            if ($row->save($update)) {
                return ['code' => 1, 'msg' => '操作成功'];
            } else {
                return ['code' => 0, 'msg' => '操作失败'];
            }
        }
        $this->error();
    }

    /**
     * 会员修改字段对比
     * @param array $oldArr
     * @param array $newArr
     * @return string
     */
    function array_diff_constent($oldArr=[], $newArr=[])
    {
        $constent = '';
        if(is_array($newArr))
        {
            foreach ($newArr as $key => $val)
            {
                if($val != $oldArr[$key])
                {
                    switch ($key){
                        case 'gender';
                            $gender = ['1'=>'男','2'=>'女'];
                            $val = $gender[$val];
                            $oldArr[$key] = $gender[$oldArr[$key]];
                        break;
                        case 'card_type';
                            $card_type = $this->systemDic['CARD_TYPE'];
                            $val = $card_type[$val];
                            $oldArr[$key] = $card_type[$oldArr[$key]];
                            break;
                        case 'birth_time';
                            $val = date('Y-m-d',$val);
                            $oldArr[$key] = date('Y-m-d',$oldArr[$key]);
                            break;
                    }
                    $constent .= __($key).':' . $oldArr[$key] . '>>' . $val . ',';
                }
            }
        }
        return $constent;
    }

    /**
     * 付款（充值）
     */
    public function pay($ids = NULL)
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post();
            if ($params)
            {
                $row = $this->model->get($params['id']);
                if ($row){

                    try
                    {
                        $updata = [];
                        $updata['balance'] = intval($params["already_paid"])+intval($row['balance']);
                        $updata['last_consumption_time'] = time();
                        if (empty($row['open_member']))
                        {
                            $updata['open_member'] = 1;
                            $updata['open_time'] = 1;
                        }
                        $result = $row->save($updata);
                        if ($result !== false)
                        {
                            //添加会员充值记录
                            $member = ['member_id'=>$row['id']];
                            $content = '充值：'.$params["already_paid"].'元';
                            \app\admin\model\MemberOperateLog::record($member,$content);

                            //添加会员充值消费记录
                            $consumptionInfo = [
                                'hos_id' => $this->auth->hos_id,
                                'admin_input_id' => $this->auth->id,
                                'admin_input_name' => $this->auth->username,
                                'admin_collect_id' => $this->auth->id,
                                'admin_collect_name' => $this->auth->username,
                                'patient_in_member_id' => 0,
                                'name' => $row['name'],
                                'member_id' => $row['id'],
                                'fee_id'=>3,
                                'hos_fee_name' => '会员充值',
                                'pay_way' => $params['pay_way'],
                                'should_pay' => $params["already_paid"],
                                'already_paid' => $params["already_paid"],
                                'status' => 1,//已付
                            ];
                            model('ChargeInfo')
                                ->addFee($consumptionInfo);
                            $this->success("充值成功");
                        }
                        else
                        {
                            $this->error("充值失败");
                        }
                    }
                    catch (think\exception\PDOException $e)
                    {
                        $this->error($e->getMessage());
                    }
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    //根据关键字获取获取会员
    public function getMember()
    {
        if ($this->request->isAjax()) {
            //当前用户信息
//            $admin = Session::get('admin')->toArray();
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $keyword = $this->request->request('keyword');
            if (empty($keyword)) {
                return [];
            }
            $members = $this->model
                ->alias('m')
                ->join('wa_patient_in_member p','m.status = 1 and m.id = p.member_id and (m.name like "%'.$keyword.'%" or m.telphone like "%'.$keyword.'%")')
                ->field('m.id,m.telphone,p.name as label,p.id as patientInMemberId,p.gender')
                ->order('m.createtime','desc')
                ->limit(10)
                ->select();
            if (empty($members))
            {
                $members[0]['id'] = 0;
                $members[0]['label'] = '暂无患者';
                $members[0]['telphone'] = '';
                $members[0]['gender'] = 1;
            }
            return $members;
        }
        $this->error('非法请求！');
    }
}

