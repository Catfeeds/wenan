<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

use think\Controller;
use think\Request;
use think\session;

/**
 * 患者表
 *
 * @icon fa fa-circle-o
 */
class Patientvisitrecord extends Backend
{
    
    /**
     * Patient模型对象
     */
    protected $model = null;

//    protected $relationSearch = true;

    protected $noNeedRight = ['visit'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PatientVisitRecord');
        $this->systemDic = dc(['TREATMENT_TYPE','DEPARTMENT','PROJECT_TYPE','MEDICAL_STATUS']);
        $this->view->assign("systemDic",$this->systemDic);
    }

    /**
     * 查看
     */
    public function index()
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
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            if(!empty($list))
            {
                foreach ($list as $key => &$val) {
                    $val['appointment'] = model('Appointment')->where('patient_visit_record_id',$val['id'])->find();
//                    $val['register'] = model('Register')->where('patient_visit_record_id',$val['id'])->order('createtime','desc')->find();
                    $val['member'] = model('Member')->where('id',$val['member_id'])->find();
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        else
        {
            //获取科室
            $this->assignconfig('treatment_department',  $this->systemDic['DEPARTMENT']);
            //获取医生
            $doctors = model('admin')
                ->where([
                    'status' => 1,
//                    'hos_id' => $admin['hos_id']
                ])
                ->column('username','id');
            $this->assignconfig('doctors',  $doctors);
            //获取门店
            $hospital = model('Hospital')
                ->column('hos_name','id');
            $this->assignconfig('hospital',  $hospital);
            $this->assignconfig('privacy',  $this->auth->check('user/patientvisitrecord/privacy'));
        }
        return $this->view->fetch();
    }
    /**
     * 来访记录
     */
    public function visit($patientInMemberId = 0)
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
        $patientInMemberInfo = model('PatientInMember')->get($patientInMemberId);
        $this->assignconfig('patientInMemberInfo',$patientInMemberInfo);
        //获取科室
        $this->assignconfig('treatment_department',  $this->systemDic['DEPARTMENT']);
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
            $params = $this->request->post("row/a");
            if ($params)
            {
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
                    $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
                    $params['name_pinyin'] = $pinyin->permalink($params['name'],'');
                    $result = $row->save($params);
                    if ($result !== false)
                    {
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

            //挂号详情
            $row['register'] = model('Register')
                ->where('patient_visit_record_id', $ids)
                ->order('register_time','desc')
                ->limit(1)
                ->find();

            //预约详情
            $row['appointment'] = model('Appointment')
                ->where('patient_visit_record_id = '.$ids)
                ->find();

            //会员详情
            $row['member'] = model('Member')
                ->where('id',$row['member_id'])
                ->find();

            //计算就诊状态 0未就诊 1已预约 2候诊中 3看诊中
            if (!empty($row['appointment'])){
                $end_time = $row['appointment']['end_time'];
                if ($row['appointment']['status'] == 2 && $end_time < time()){
                    $row['appointment']['status'] = 3;
                }
                $row['medical_status'] = $row['appointment']['status'];
            }else{
                $row['medical_status'] = 0;
            }

            //消费信息
            $row['chargeInfo'] = model('ChargeInfo')
                ->where(['patient_in_member_id'=>$row['patient_in_member_id']])
               ->order('id','desc')
                ->select();

            //获取会员卡预约
            if (!empty($row['chargeInfo'])){
                $balance = model('Member')->where('id',$row['member_id'])->value('balance');
                foreach ($row['chargeInfo'] as $key => &$val){
                    $val['balance'] = $balance;
                }
            }
            $row['gender'] = $row['gender'] == 1?'男':'女';
            $row['age'] =$row['birth_time'] ? (date('Y')-date('Y',$row['birth_time'])+1).'岁' : '';
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
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
        $initCondArr = ['status' => ['in',[0,1,2]]];
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
        //默认筛选条件
        if (!empty($initCondArr))
        {
            foreach ($initCondArr as $k => $v)
            {
                is_array($v) ? $where[] = [$tableName.$k, $v[0], $v[1]] : $where[] = [$tableName.$k, '=', $v];
            }
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
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false)
            {
                $k = $tableName . $k;
            }
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);

            //挂号或预约医生
            if ($k == $tableName.'doctor_register_id')
            {
                $k = implode('|',[$tableName.'doctor_register_id',$tableName.'doctor_appointment_id']);
            }
            //格式化时间
            if ($k == 'register.register_time')
            {
                $v = strtotime($v);
            }
            //患者姓名或拼音
            if ($k == $tableName.'name')
            {
                $k = implode('|',[$tableName.'name',$tableName.'name_pinyin']);
            }
            //会员手机号获取会员id
            if ($k == 'member.telphone')
            {
                $memberIdArr = model('Member')->where('telphone',trim($v))->column('id');
                if (!empty($memberIdArr)){
                    $v = implode(',',$memberIdArr);
                }else{
                    $v = [0];
                }
                $k = 'member_id';
                $sym = 'IN';

            }
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
    /**
     * 查看会员绑定患者
     */
    public function patientInMember($memberId)
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
                ->with('member,register,appointment')
                ->where($where)
                ->where(['member_id'=>$memberId])
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('member,register,appointment')
                ->where($where)
                ->where(['member_id'=>$memberId])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
//            if(!empty($list))
//            {
//                foreach ($list as $key => &$val) {

//                    $val->member->openMember = $val->member->openMember == 1 ? '是' : '否';

//                    $hos_name = $this->HospitalModel
//                        ->where('id',$val['hos_id'])
//                        ->value('hos_name');
//                    $val['hos_name'] = $hos_name;

//                    $val['gender'] = $val['gender'] !=2 ? '男' : '女';
//                    $val['treatment_department'] = !empty($val['treatment_department']) && $this->systemDic['DEPARTMENT'] ? $this->systemDic['DEPARTMENT'][$val['treatment_department']] : '';
//                    $val['doctor_name'] = !empty($val['doctor_register_name'])?$val['doctor_register_name']:$val['doctor_appointment_name'];
//                    $val['register_time'] = date('Y-m-d');
//                }
//
//            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        else
        {
            //获取科室
            $this->assignconfig('treatment_department',  $this->systemDic['DEPARTMENT']);
            //获取医生
            $doctors = model('admin')
                ->where([
                    'status' => 1,
//                    'hos_id' => $admin['hos_id']
                ])
                ->column('username','id');
            $this->assignconfig('doctors',  $doctors);
            //获取门店
            $hospital = model('Hospital')
                ->column('hos_name','id');
            $this->assignconfig('hospital',  $hospital);
            $this->assignconfig('privacy',  $this->auth->check('user/patientvisitrecord/privacy'));
        }
        return $this->view->fetch();
    }

}
