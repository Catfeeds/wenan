<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

use think\Session;
use think\Controller;
use think\Request;

/**
 * 挂号详情表
 *
 * @icon fa fa-circle-o
 */
class Register extends Backend
{
    
    /**
     * Register模型对象
     */
    protected $model = null;

    protected $systemDic = [];

    protected $noNeedLogin = [];

    protected $noNeedRight = ['getremainderregister','getmember','getdoctor'];

    protected $stage = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Register');
        $this->systemDic = dc(['DEPARTMENT']);
        $this->stage =  [
            1=>'morning',
            2=>'afternoon',
            3=>'evening',
        ];
    }

    /**
     * 查看
     */
    public function index()
    {
        //当前用户信息
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

            $whereExt = [
                'status' => ['in','1,2'],
                'hos_id' => $this->auth->hos_id
            ];
            //如果是超级管理员
            if ($this->auth->group_id == 1)
            {
                unset($whereExt['hos_id']);
            }
            $total = $this->model
                ->where($where)
                ->where($whereExt)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($whereExt)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 必须将结果集转换为数组
//            $list = collection($list)->toArray();
//            if(!empty($list))
//            {
//                foreach ($list as $key => &$val) {
//                    $val['register_time'] = date('Y-m-d',$val['register_time']).' '.__($this->stage[$val['stage']]);
//                }
//            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        else
        {
            $this->assignconfig('treatment_department',  $this->systemDic['DEPARTMENT']);
            //获取医生
            $where = [
                'status' => 1,
                'hos_id' => $this->auth->hos_id
            ];

            //如果是超级管理员
            if ($this->auth->group_id == 1)
            {
                unset($where['hos_id']);
            }

            $doctors = model('admin')
                ->where($where)
                ->column('username','id');
            $this->assignconfig('doctors',  $doctors);
        }
        return $this->view->fetch();
    }

    /**
     * 添加挂号
     * 1.检查是否有会员
     *      1.1无，插入会员
     * 2.根据手机号与患者名查看患者表是否有记录
     *      2.1有更新
     *      2.2无插入
     * 3.添加付费信息
     *
     */
    public function add()
    {

        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['hos_id'] = $this->auth->hos_id;
                $params['register_time'] = strtotime($params['register_time']);
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
                    //检查是否可以挂号
                    $isEffective = $this->checkRegister($params);
                    if ($isEffective['success'] ===false)
                    {
                        $this->error($isEffective['msg'], '');
                    }
                    //根据手机号判断是否已有该会员，无添加会员
                    $this->MemberModel = model('Member');
                    $memberId = $this->MemberModel
                        ->addMember($params);
                    if ($memberId['code'] == 2)
                    {
                        $this->error($memberId['msg']);
                    }
                    $params['member_id'] = $memberId['data']['id'];


                    //患者表插入数据
                    $params['doctor_register_id'] = $params['doctor_id'];
                    $params['doctor_register_name'] = $params['doctor_name'];

                    $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
                    $params['name_pinyin'] = $pinyin->permalink($params['name'],'');

                    $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
                    $params['name_pinyin'] = $pinyin->permalink($params['name'],'');

                    $this->PatientVisitRecordModel = model('PatientVisitRecord');
                    $patientId = $this->PatientVisitRecordModel
                        ->allowField(true)
                        ->save($params);
                    $params['patient_visit_record_id'] = $this->PatientVisitRecordModel->id;
                    //根据姓名和手机号查询患者表中是否已插入，有则更新，无则插入
//                    $params['doctor_register_id'] = $params['doctor_id'];
//                    $params['doctor_register_name'] = $params['doctor_name'];
//                    $patientId = model('PatientVisitRecord')
//                        ->patientIsExist($params);
//                    $params['patient_visit_record_id'] = $patientId['id'];

                    //获取挂号费
                    $registerFee = model('HosFee')
                        ->get(['fee_id'=>1,'status' => 1]);
                    if(!empty($registerFee))
                    {
                        //添加挂号付费信息
                        $this->ChargeInfoModel = model('ChargeInfo');
                        $chargeInfo = [
                            'hos_id'  =>  $this->auth->hos_id,
                            'admin_input_id'  => $this->auth->id,
                            'admin_input_name'  =>  $this->auth->username,
                            'patient_visit_record_id'  =>  $params['patient_visit_record_id'],
                            'name'  =>  $params['name'],
                            'member_id'  =>  $params['member_id'],
                            'fee_id'  =>  $registerFee['fee_id'],
                            'hos_fee_id'=> $registerFee['id'],
                            'hos_fee_name'=>$registerFee['fee_name'],
                            'doctor_name'  =>  $params['doctor_name'],
                            'should_pay'  => $registerFee['price'],
                            'already_paid'  => 0,
                            'patient_in_member_id' => $params['patient_in_member_id'],
                        ];
                        $chargeInfoSave =  $this->ChargeInfoModel
                            ->addFee($chargeInfo);
                        $params['charge_info_id'] =$chargeInfoSave['data']['id'];
                        if(!$chargeInfoSave)
                        {
                            $this->error('消费信息添加失败');
                        }
                    }

                    //添加挂号记录
                    $result = $this->model
                        ->allowField(true)
                        ->save($params);
                    if ($result !== false)
                    {
                        //更新会员常用医生及门店
                        $this->MemberModel->setCommonDoctor($params['member_id']);

                        //添加会员挂号记录
                        $member = ['member_id'=>$params['member_id']];
                        $content = '添加会员挂号';
                        \app\admin\model\MemberOperateLog::record($member,$content);

                        $this->success();
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
            $treatmentDepartment = [];//科室
            $doctor = [];//当前科室内医生
            //当前用户所在门店科室
            $departids = model('HosDepart')
                ->where('hos_id', $this->auth->hos_id)
                ->column('depart_id');
            if (!empty($departids)){
                foreach ($departids as $v)
                {
                    $treatmentDepartment[$v] = $this->systemDic['DEPARTMENT'][$v];
                }
                //当前科室内医生
                $doctor = model('Admin')
                    ->where([
                        'status' => 1 ,
                        'hos_id' => $this->auth->hos_id,
                        'depart_id' => $departids[0]
                    ])
                    ->column('username','id');
            }
            $this->view->assign('treatmentDepartment',$treatmentDepartment);
            $this->view->assign('doctor',$doctor);

            //当前时间段
            $nowStage = $this->getRestStage(date("H:i"));
//            $this->view->assign('nowStage',$nowStage);
            //获取剩余挂号数
            $condWhere = [
                'doctor_id'=>key($doctor),
                'register_time'=>date('Y-m-d'),
                'stage'=>$nowStage,
            ];

            $remainderRegister = $this->getRemainderRegister($condWhere);
            $this->view->assign('remainderRegister',$remainderRegister['data']);
        }
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

        //重构数据
        $row = $row->toArray();
        $row['gender'] = $row['gender'] == 1?'男':'女';
        $row['treatment_department'] = !empty($row['treatment_department']) && $this->systemDic['DEPARTMENT'] ? $this->systemDic['DEPARTMENT'][$row['treatment_department']] : '';

        //获取挂号费用
        if(!empty($row['charge_info_id']) && $row['charge_info_id'] > 0){
            $row['chargeInfo'] = model('ChargeInfo')->get($row['charge_info_id']);
        }else{
            $row['chargeInfo'] = '';
        }
        //获取会员手机号
        $row['telphone'] = model('Member')->where('id',$row['member_id'])->value('telphone');

        $this->view->assign("stage",$this->stage);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 取消
     */
    public function cancel($ids = "")
    {
        if ($ids)
        {
            $row = $this->model->get(['id'=>$ids]);
            $row->save(['status' => 0, 'updatetime' => time()]);
             $res = model('PatientVisitRecord')
                ->where('id', $row['patient_visit_record_id'])
                ->update(['status' => 0, 'updatetime' => time()]);
            if ($res) {
                $this->success("取消成功");
            } else {
                $this->error("取消失败");
            }
        }
        $this->error();
    }

    //获取科室下所有医生
    public function getDoctor()
    {
        if ($this->request->isAjax()) {
            //当前用户信息
//            $admin = Session::get('admin')->toArray();
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $depatId = $this->request->request('depart_id');
            if (empty($depatId)) {
                $this->error('非法操作！');
            }
            $doctors = model('admin')
                ->where('status = 1 and hos_id = ' .$this->auth->hos_id.' and depart_id = '.$depatId)
                ->column('username','id');
            if (empty($doctors)) {
                $doctors = [];
            }
            $data = build_select('row[doctor_id]', $doctors, null, ['class'=>'form-control selectpicker', 'id' => 'c-doctor_id', 'data-rule'=>'required']);
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }

    //获取剩余挂号
    public function getRemainderRegister($params = [])
    {
        $res = ['code'=> 0, 'data'=>0, 'msg'=>'无剩余挂号数'];
        //当前用户信息
//        $admin = Session::get('admin')->toArray();
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $params = $this->request->request();
        }
        $doctorId = $params['doctor_id'];
        $registerTime = $params['register_time'];
        $stage = $params['stage'];

//        $treatmentDepartment = $params['treatment_department'];
        if (empty($doctorId) || empty($registerTime)) {
            $res['msg'] = '挂号时间或挂号医生不能为空！';
            return $res;
        }

        //检测挂号时间是否在七天内
        $currentDay = date('Y-m-d');
        if(strtotime($registerTime)<strtotime($currentDay) || strtotime($registerTime)> strtotime("$currentDay + 7day" )){
            $res['msg'] = '挂号时间只能选择自'.$currentDay.'之后的七天内';
            return $res;
        }
        //是否处于休息日
        $isRest = model('HosStaffRest')
        ->where([
            'admin_id' => $params['doctor_id'],
            'rest_day' => $registerTime
        ])
        ->find();
        if ($isRest){
            $res['msg'] = '该医生当前日期休息';
            return $res;
        }
        if ($stage>0){
            //检查是否挂号过去的时间
            $nowStageHour = model('HosRest')
                ->where('hos_id = ' .$this->auth->hos_id.' and  type = '.$stage)
                ->value('end_time');
            if (empty($nowStageHour)){//未设置作息时间，则获取默认作息时间
//                $res['msg'] = '未设置作息时间';
//                return $res;
                $nowStageHour = model('HosRest')
                    ->where('hos_id = 0  and  type = '.$stage)
                    ->value('end_time');
            }
            if ($registerTime == $currentDay && $nowStageHour <date('H:i'))
            {
                $res['msg'] = '不可挂号过去的时间';
                return $res;
            }
            //获取医生总挂号数
            $this->DoctorRegisterModel = model('DoctorRegister');

            $DoctorRegisterCondArr = [
                'admin_id' => $doctorId,
                'work_day' => $registerTime
            ];
            $tatal =  $this->DoctorRegisterModel
                ->where($DoctorRegisterCondArr)
                ->sum($this->stage[$stage]);

            //获取锁定挂号数
            $lockNum =  $this->DoctorRegisterModel
                ->where($DoctorRegisterCondArr)
                ->sum($this->stage[$stage].'_lock');

            //获取当前医生当前时间已占用的挂号
            $alreadyUseNumCondArr = [
                'hos_id '=> $this->auth->hos_id,
                'status' => ['in',[1,2]],
                'register_time' => strtotime($registerTime),
                'stage' => $stage ,
                'doctor_id' => $doctorId
            ];
            $alreadyUseNum = $this->model
                ->where($alreadyUseNumCondArr)
                ->count();
            $data = $tatal - $alreadyUseNum - $lockNum;
            if ($data>0){
                $res['code'] = 1;
                $res['data'] = $data;
            }
            return $res;
        }
         return $res;
    }

    /**
     * 获取作息时间阶段
     */
    public function getRestStage($hour)
    {
        //当前用户信息
//        $admin = Session::get('admin')->toArray();
        $stage = model('HosRest')
            ->where('hos_id = ' .$this->auth->hos_id.' and  start_time <= "'.$hour.'" and end_time >= "'.$hour.'"')
            ->value('type');
        return $stage;
    }

    /**
     * 检查挂号是否有效
     */
    public function checkRegister($params)
    {
        if ($params['remainder_register']<=0){
            return ['success'=>false,'msg'=>"无剩余挂号数"];
        }
        return ['success'=>true];
    }

    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');

        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 10);
        $filter = json_decode($filter, TRUE);
        $op = json_decode($op, TRUE);
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch)
        {
            if (!empty($this->model))
            {
                $class = get_class($this->model);
                $name = basename(str_replace('\\', '/', $class));
                $tableName = $this->model->getQuery()->getTable($name) . ".";
            }
            $sort = stripos($sort, ".") === false ? $tableName . $sort : $sort;
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            $where[] = [$this->dataLimitField, 'in', $adminIds];
        }
        if ($search)
        {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v)
            {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        foreach ($filter as $k => $v)
        {
            //挂号时间转换为时间戳
            if ($k == 'register_time'){
                $v = strtotime($v);
            }

            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false)
            {
                $k = $tableName . $k;
            }
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym)
            {
                case '=':
                case '!=':
                    $where[] = [$k, $sym, (string) $v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '')
                    {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    }
                    else if ($arr[1] === '')
                    {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function($query) use ($where) {
            foreach ($where as $k => $v)
            {
                if (is_array($v))
                {
                    call_user_func_array([$query, 'where'], $v);
                }
                else
                {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }
}
