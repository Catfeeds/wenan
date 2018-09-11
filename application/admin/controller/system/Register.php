<?php
namespace app\admin\controller\system;

//use app\admin\model\AuthGroup;
//use app\admin\model\Hospital;
use app\common\controller\Backend;
use think\Db;

/**
 * 挂号管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Register extends Backend
{
    protected $model;
    protected $groupList;
    protected $hosList;
    protected $noNeedRight = ['getdoctorregister', 'getdoctorlock'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('DoctorRegister');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $group_id = $this->auth->group_id;
            $hos_id = $this->auth->hos_id;
            //list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if ($group_id == 1) {
                $hos_id = $this->request->get("hos_id", '');
                if (empty($hos_id)) {
                    $hospital = Db::name('Hospital')->order('id', 'asc')->find();
                    if (!empty($hospital)) {
                        $hos_id = $hospital['id'];
                    }
                }
            }
            if (empty($hos_id)) {
                $result = array("total" => 0, "rows" => []);
                return json($result);
            }
            $member = model('Admin')->where('hos_id', $hos_id)->where('depart_id', '<>', 0)->where('status', 1)->order('depart_id', 'asc')->order('id', 'asc')->column('id, username, depart_id');
            if (empty($member)) {
                $result = array("total" => 0, "rows" => []);
                return json($result);
            }
            $memberIds = array_keys($member);
            //print_r($member);exit;
            $date = $this->request->get("date", '');
            if (!empty($date) && date('Y-m-d', strtotime($date)) != $date) {
                $this->error('日期格式非法！');
            }
            if (empty($date)) {
                $date = date('Y-m-d');
            }
            $regList = $this->model
                ->whereIn('admin_id', $memberIds)
                ->where('work_day', $date)
                ->column('admin_id, morning, afternoon, evening', 'admin_id');
            //print_r($regList);exit;

            $list = $columns = [];
            $departList = dc('DEPARTMENT');
            foreach ($member as $k => $v) {
                $depart_id = $v['depart_id'];
                if (!isset($columns[$depart_id])) {
                    $columns[$depart_id] = [
                        'name' => $departList[$depart_id],
                        'morning' => 0,
                        'afternoon' => 0,
                        'evening' => 0,
                        '_name_data' => [
                            'ids' => '',
                            'names' => ''
                        ],
                        '_morning_data' => [
                            'regNums' => ''
                        ],
                        '_afternoon_data' => [
                            'regNums' => ''
                        ],
                        '_evening_data' => [
                            'regNums' => ''
                        ],
                    ];
                }
                if (isset($regList[$k])) {
                    $morning = $regList[$k]['morning'];
                    $afternoon = $regList[$k]['afternoon'];
                    $evening = $regList[$k]['evening'];
                } else {
                    $morning = $afternoon = $evening = 0;
                }
                $columns[$depart_id]['morning'] += $morning;
                $columns[$depart_id]['afternoon'] += $afternoon;
                $columns[$depart_id]['evening'] += $evening;

                $columns[$depart_id]['_name_data']['ids'] .= $v['id'] . ',';
                $columns[$depart_id]['_name_data']['names'] .= $v['username'] . ',';

                $columns[$depart_id]['_morning_data']['regNums'] .= $morning . '人,';
                $columns[$depart_id]['_afternoon_data']['regNums'] .= $afternoon . '人,';
                $columns[$depart_id]['_evening_data']['regNums'] .= $evening . '人,';
            }
            foreach ($columns as $v) {
                $v['morning'] .= '人';
                $v['afternoon'] .= '人';
                $v['evening'] .= '人';

                $v['_name_data']['ids'] = trim($v['_name_data']['ids'], ',');
                $v['_name_data']['names'] = trim($v['_name_data']['names'], ',');

                $v['_morning_data']['regNums'] = trim($v['_morning_data']['regNums'], ',');
                $v['_afternoon_data']['regNums'] = trim($v['_afternoon_data']['regNums'], ',');
                $v['_evening_data']['regNums'] = trim($v['_evening_data']['regNums'], ',');

                $list[] = $v;
            }
            //print_r($list);exit;
            $result = array("total" => count($list), "rows" => $list);

            return json($result);
        }
        $group_id = $this->auth->group_id;
        $hos_id = $this->auth->hos_id;
        if ($group_id == 1) {
            $hospital = Db::name('Hospital')->order('id', 'asc')->find();
            if (!empty($hospital)) {
                $hos_id = $hospital['id'];
            }
        }
        if (empty($hos_id)) {
            $this->error('还没创建医馆！');
        }
        $hospital = Db::name('Hospital')->where('id', $hos_id)->find();
        if (empty($hospital)) {
            $this->error('非法请求！');
        }
        $this->assignconfig("hospital", $hospital);
        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_id' => $this->auth->group_id]);

        if ($group_id == 1) {
            $hospList = Db::name('Hospital')->column('id, hos_name');
            $selHtml = '<select class="form-control" name="hos_id" id="hos_id" style="width: 80px;float: left;">';
            foreach ($hospList as $k => $v) {
                $selHtml .= '<option value="' . $k . '">' . $v . '</option>';
            }
            $selHtml .= '</select>';
            $this->assignconfig("selHtml", $selHtml);
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL, $date = '')
    {
        if (empty($ids)) {
            $this->error('非法请求！');
        }
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $adminId = $this->request->post("doctor_id");
            $startDay = $this->request->post("start_day");
            $endDay = $this->request->post("end_day");
            $morning = $this->request->post("morning");
            $afternoon = $this->request->post("afternoon");
            $evening = $this->request->post("evening");

            if (empty($adminId) || empty($startDay) || empty($endDay)) {
                $this->error('非法操作！');
            }
            if (date('Y-m-d', strtotime($startDay)) != $startDay) {
                $this->error('开始日期格式非法！');
            }
            if (date('Y-m-d', strtotime($endDay)) != $endDay) {
                $this->error('结束日期格式非法！');
            }
            if (date('Ymd', strtotime($startDay)) > date('Ymd', strtotime($endDay))) {
                $this->error('开始日期不能早于结束日期！');
            }
            if ($startDay < date('Y-m-d')) {
                $this->error('非法请求，不能修改过去的日期！');
            }
            $doctorReg = $this->model
                ->where('admin_id', $adminId)
                ->where('work_day', '>=', $startDay)
                ->where('work_day', '<=', $endDay)
                ->select();
            if (!empty($doctorReg)) {
                foreach ($doctorReg as $v) {
                    if ($v['morning'] > $morning) {
                        $this->error($v['work_day'] . '上午的挂号数是' . $v['morning'] . '，挂号数只能增加，不能较少！');
                    }
                    if ($v['afternoon'] > $afternoon) {
                        $this->error($v['work_day'] . '下午的挂号数是' . $v['afternoon'] . '，挂号数只能增加，不能较少！');
                    }
                    if ($v['evening'] > $evening) {
                        $this->error($v['work_day'] . '晚上的挂号数是' . $v['evening'] . '，挂号数只能增加，不能较少！');
                    }
                }
            }
            $regList = $this->model
                ->where('admin_id', $adminId)
                ->column('id, work_day', 'work_day');
            //print_r($regList);exit;
            $totalDay = (strtotime($endDay) - strtotime($startDay)) / 86400;
            Db::startTrans();
            try {
                $time = time();
                for ($i = 0; $i <= $totalDay; $i++) {
                    $work_day = date('Y-m-d', strtotime($startDay) + $i * 86400);
                    if (!empty($regList) && isset($regList[$work_day])) {
                        $this->model->where('id', $regList[$work_day])->update(['morning' => $morning, 'afternoon' => $afternoon, 'evening' => $evening, 'update_time' => $time]);
                    } else {
                        $data = [
                            'admin_id' => $adminId,
                            'work_day' => $work_day,
                            'morning' => $morning,
                            'afternoon' => $afternoon,
                            'evening' => $evening,
                            'create_time' => $time
                        ];
                        $this->model->insert($data);
                    }
                }
                Db::commit();
                $error = '';
            } catch (\Exception $e) {
                Db::rollback();
                $error = $e->getMessage();
            }
            if (empty($error)) {
                $this->success('设置成功！');
            } else {
                $this->error($error);
            }
        } else {
            if (!empty($date) && date('Y-m-d', strtotime($date)) != $date) {
                $this->error('日期格式非法！');
            }
            if (empty($date)) {
                $date = date('Y-m-d');
            } elseif ($date < date('Y-m-d')) {
                $this->error('非法请求，不能修改过去的日期！');
            }
            $doctor = model('Admin')->where('id', $ids)->find();
            if (empty($doctor)) {
                $this->error('非法请求！');
            }
            //$doctorList = model('Admin')->where('hos_id', $doctor['hos_id'])->where('depart_id', $doctor['depart_id'])->where('status', 1)->order('id', 'asc')->column('id, username');
            $doctorList = [$ids => $doctor['username']];
            $depList = model('HosDepart')->where('hos_id', $doctor['hos_id'])->column('hos_id, depart_id', 'depart_id');
            //print_r($depList);
            if (!empty($depList)) {
                $departList = dc('DEPARTMENT');
                foreach ($depList as $k => $v) {
                    if (isset($departList[$k]) && $k == $doctor['depart_id']) {
                        $depList[$k] = $departList[$k];
                    } else {
                        unset($depList[$k]);
                    }
                }
            }
            //print_r($depList);
            $regInfo = $this->model
                ->where('admin_id', $ids)
                ->where('work_day', $date)
                ->find();
            if (empty($regInfo)) {
                $regInfo = [
                    'work_day' => $date,
                    'morning' => 0,
                    'afternoon' => 0,
                    'evening' => 0,
                ];
            }
            $regList = [
                'morning' => [],
                'afternoon' => [],
                'evening' => [],
            ];
            foreach ($regList as $k => $v) {
                for ($i = $regInfo[$k]; $i <= 50; $i++) {
                    $regList[$k][$i] = $i;
                }
            }

            $docReg = $this->model
                ->where('admin_id', $ids)
                ->order('work_day', 'asc')
                ->column('work_day');
            $this->assignconfig("specDate", implode($docReg, ','));
            $this->assignconfig("today", date('Y-m-d'));
            $this->assignconfig("work_day", $date);

            $this->view->assign('doctorList', $doctorList);
            $this->view->assign('depList', $depList);
            $this->view->assign('regInfo', $regInfo);
            $this->view->assign('doctor', $doctor);
            $this->view->assign('regList', $regList);

            return $this->fetch('');
        }
    }

    /**
     * 设置医生的挂号锁定
     */
    public function lock($hosId = 0, $date = '', $ids = 0)
    {
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $adminId = $this->request->post("doctor_id");
            $workDay = $this->request->post("work_day");
            $morningLock = $this->request->post("morning_lock");
            $afternoonLock = $this->request->post("afternoon_lock");
            $eveningLock = $this->request->post("evening_lock");

            if (empty($adminId) || empty($workDay)) {
                $this->error('非法操作！');
            }
            if (date('Y-m-d', strtotime($workDay)) != $workDay) {
                $this->error('日期格式非法！');
            }
            if ($workDay < date('Y-m-d')) {
                $this->error('非法请求，不能修改过去的日期！');
            }
            $regInfo = $this->model
                ->where('admin_id', $adminId)
                ->where('work_day', $workDay)
                ->find();
            try {
                $time = time();
                if (empty($regInfo)) {
                    $data = [
                        'admin_id' => $adminId,
                        'work_day' => $workDay,
                        'morning_lock' => $morningLock,
                        'afternoon_lock' => $afternoonLock,
                        'evening_lock' => $eveningLock,
                        'create_time' => $time
                    ];
                    $this->model->insert($data);
                } else {
                    $this->model->where('id', $regInfo['id'])->update(['morning_lock' => $morningLock, 'afternoon_lock' => $afternoonLock, 'evening_lock' => $eveningLock, 'update_time' => $time]);
                }
                $error = '';
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
            if (empty($error)) {
                $this->success('设置成功！');
            } else {
                $this->error($error);
            }
        }
        if (!empty($date) && date('Y-m-d', strtotime($date)) != $date) {
            $this->error('日期格式非法！');
        }
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        if (empty($ids)) {
            $this->error('非法操作！');
        }
        if (!empty($ids)) {
            $doctor = model('Admin')->where('id', $ids)->find();
            if (empty($doctor)) {
                $this->error('非法请求！');
            }
            $hos_id = $doctor['hos_id'];
            //$doctorList = model('Admin')->where('hos_id', $doctor['hos_id'])->where('depart_id', $doctor['depart_id'])->where('status', 1)->order('id', 'asc')->column('id, username');
            $docReg = $this->model
                ->where('admin_id', $doctor['id'])
                ->order('work_day', 'asc')
                ->column('work_day');
            $this->assignconfig("specDate", implode($docReg, ','));
        } else {
            $group_id = $this->auth->group_id;
            $hos_id = $this->auth->hos_id;
            if ($group_id == 1) {
                $hos_id = $hosId;
            }
            if (empty($hos_id)) {
                $this->error('非法请求！');
            }
        }
        $hospital = Db::name('Hospital')->where('id', $hos_id)->find();
        if (empty($hospital)) {
            $this->error('医院不存在！');
        }
        if (empty($ids)) {
            $doctor = model('Admin')->where('hos_id', $hos_id)->where('depart_id', '<>', 0)->where('status', 1)->order('depart_id', 'asc')->order('id', 'asc')->find();
            //$doctorList = model('Admin')->where('hos_id', $hos_id)->where('depart_id', $doctor['depart_id'])->where('status', 1)->order('id', 'asc')->column('id, username');
            $docReg = $this->model
                ->where('admin_id', $doctor['id'])
                ->order('work_day', 'asc')
                ->column('work_day');
            $this->assignconfig("specDate", implode($docReg, ','));
        }
        $regInfo = $this->model
            ->where('admin_id', $doctor['id'])
            ->where('work_day', $date)
            ->find();
        if (empty($regInfo)) {
            //$this->error('医生还没设置当天的挂号信息！');
            echo '医生还没设置当天的挂号信息！';
            exit;
            /*$regInfo = [
                'work_day' => $date,
                'morning_lock' => 0,
                'afternoon_lock' => 0,
                'evening_lock' => 0,
            ];*/
        }
        $regiList = Db::name('Register')->field('stage, count(*) as num')->where('doctor_id', $doctor['id'])->where('register_time', '>=', strtotime($date))->where('register_time', '<=', strtotime($date . ' 23:59:59'))->where('status', '<>', -1)->group('stage')->select();
        //print_r($regInfo);
        $memRegi = [];
        if (!empty($regiList)) {
            foreach ($regiList as $v) {
                $memRegi[$v['stage']] = $v['num'];
            }
        }
        $regInfo['morning_left'] = $regInfo['morning'];
        if (isset($memRegi[1])) {
            $regInfo['morning_left'] -= $memRegi[1];
        }
        $regInfo['afternoon_left'] = $regInfo['afternoon'];
        if (isset($memRegi[2])) {
            $regInfo['afternoon_left'] -= $memRegi[2];
        }
        $regInfo['evening_left'] = $regInfo['evening'];
        if (isset($memRegi[3])) {
            $regInfo['evening_left'] -= $memRegi[3];
        }
        //print_r($regInfo);

        $regMorning = $regAfternoon = $regEvening = [];
        for ($i = 0; $i <= $regInfo['morning_left']; $i++) {
            $regMorning[] = $i;
        }
        for ($i = 0; $i <= $regInfo['afternoon_left']; $i++) {
            $regAfternoon[] = $i;
        }
        for ($i = 0; $i <= $regInfo['evening_left']; $i++) {
            $regEvening[] = $i;
        }
        $doctorList = [$ids => $doctor['username']];
        $depList = model('HosDepart')->where('hos_id', $hos_id)->column('hos_id, depart_id', 'depart_id');
        //print_r($depList);
        if (empty($depList)) {
            $this->error('科室不存在！');
        }
        $departList = dc('DEPARTMENT');
        foreach ($depList as $k => $v) {
            //$depList[$k] = $departList[$k];
            if (isset($departList[$k]) && $k == $doctor['depart_id']) {
                $depList[$k] = $departList[$k];
            } else {
                unset($depList[$k]);
            }
        }

        $this->view->assign('doctorList', $doctorList);
        $this->view->assign('depList', $depList);
        $this->view->assign('regInfo', $regInfo);
        $this->view->assign('doctor', $doctor);
        $this->view->assign('regMorning', $regMorning);
        $this->view->assign('regAfternoon', $regAfternoon);
        $this->view->assign('regEvening', $regEvening);
        $this->view->assign('hospital', $hospital);
        $this->assignconfig("today", date('Y-m-d'));
        $this->assignconfig("work_day", $date);

        return $this->fetch('');
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    /**
     * 获取医生的挂号信息
     */
    public function getDoctorRegister()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $startDay = $this->request->request('startDay');
            $doctorId = $this->request->request('doctor_id');
            $type = $this->request->request('type');
            if (empty($startDay) || empty($doctorId) || empty($type)) {
                $this->error('非法操作！');
            }
            if (date('Y-m-d', strtotime($startDay)) != $startDay) {
                $this->error('日期格式非法！');
            } elseif ($startDay < date('Y-m-d')) {
                $this->error('非法请求，不能修改过去的日期！');
            }
            if ($type == 1) {//获取开始日期的数据
                $regInfo = $this->model
                    ->where('admin_id', $doctorId)
                    ->where('work_day', $startDay)
                    ->find();
                if (empty($regInfo)) {
                    $regInfo = [
                        'morning' => 0,
                        'afternoon' => 0,
                        'evening' => 0,
                    ];
                }
            } elseif ($type == 2) {//判断结束日期
                $endDay = $this->request->request('endDay');
                if (empty($endDay)) {
                    $this->error('非法操作！');
                }
                if (date('Y-m-d', strtotime($endDay)) != $endDay) {
                    $this->error('结束日期格式非法！');
                } elseif ($endDay < date('Y-m-d')) {
                    $this->error('非法请求，不能修改过去的日期！');
                } elseif ($endDay < $startDay) {
                    $this->error('非法请求，结束日期不能早于开始日期！');
                }
                if ($endDay == $startDay) {//开始日期和结束日期一致
                    $regInfo = $this->model
                        ->where('admin_id', $doctorId)
                        ->where('work_day', $startDay)
                        ->find();
                    if (empty($regInfo)) {
                        $regInfo = [
                            'morning' => 0,
                            'afternoon' => 0,
                            'evening' => 0,
                        ];
                    }
                } else {
                    $doctorReg = $this->model
                        ->where('admin_id', $doctorId)
                        ->where('work_day', '>=', $startDay)
                        ->where('work_day', '<=', $endDay)
                        ->select();
                    $sameReg = false;
                    //print_r($doctorReg);
                    if (empty($doctorReg)) {
                        $sameReg = false;
                    } elseif (count($doctorReg) != (((strtotime($endDay) - strtotime($startDay)) / 86400) + 1)) {
                        $sameReg = false;
                    } else {
                        //print_r($doctorReg);
                        $morning = $afternoon = $evening = [];
                        foreach ($doctorReg as $v) {
                            $morning[] = $v['morning'];
                            $afternoon[] = $v['afternoon'];
                            $evening[] = $v['evening'];
                        }
                        //print_r($morning);
                        $morning = array_unique($morning);
                        $afternoon = array_unique($afternoon);
                        $evening = array_unique($evening);
                        if (count($morning) == 1 && count($afternoon) == 1 && count($evening) == 1) {
                            $sameReg = true;
                        }
                    }
                    if ($sameReg) {
                        $regInfo = [
                            'morning' => $morning[0],
                            'afternoon' => $afternoon[0],
                            'evening' => $evening[0],
                        ];
                    } else {
                        $regInfo = [
                            'morning' => 0,
                            'afternoon' => 0,
                            'evening' => 0,
                        ];
                    }
                }
            } else {
                $this->error('非法操作！');
            }
            $regList = [
                'morning' => [],
                'afternoon' => [],
                'evening' => [],
            ];
            foreach ($regList as $k => $v) {
                for ($i = $regInfo[$k]; $i <= 50; $i++) {
                    $regList[$k][$i] = $i;
                }
            }
            $data = [];
            foreach ($regList as $k => $v) {
                $data[$k] = build_select($k, $v, 0, ['class' => 'form-control selectpicker', 'id' => $k, 'data-rule' => 'required']);
            }
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }

    /**
     * 获取医生的挂号锁定信息
     */
    public function getDoctorLock()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $startDay = $this->request->request('startDay');
            $doctorId = $this->request->request('doctor_id');
            if (empty($startDay) || empty($doctorId)) {
                $this->error('非法操作！');
            }
            if (date('Y-m-d', strtotime($startDay)) != $startDay) {
                $this->error('日期格式非法！');
            } elseif ($startDay < date('Y-m-d')) {
                $this->error('非法请求，不能修改过去的日期！');
            }
            $regInfo = $this->model
                ->where('admin_id', $doctorId)
                ->where('work_day', $startDay)
                ->find();
            if (empty($regInfo)) {
                $regInfo = [
                    'morning_lock' => 0,
                    'afternoon_lock' => 0,
                    'evening_lock' => 0,
                ];
            }
            $regiList = Db::name('Register')->field('stage, count(*) as num')->where('doctor_id', $doctorId)->where('register_time', '>=', strtotime($startDay))->where('register_time', '<=', strtotime($startDay . ' 23:59:59'))->where('status', '<>', -1)->group('stage')->select();
            //print_r($regInfo);
            $memRegi = [];
            if (!empty($regiList)) {
                foreach ($regiList as $v) {
                    $memRegi[$v['stage']] = $v['num'];
                }
            }
            $regInfo['morning_left'] = $regInfo['morning'];
            if (isset($memRegi[1])) {
                $regInfo['morning_left'] -= $memRegi[1];
            }
            $regInfo['afternoon_left'] = $regInfo['afternoon'];
            if (isset($memRegi[2])) {
                $regInfo['afternoon_left'] -= $memRegi[2];
            }
            $regInfo['evening_left'] = $regInfo['evening'];
            if (isset($memRegi[3])) {
                $regInfo['evening_left'] -= $memRegi[3];
            }
            //print_r($regInfo);

            $morningHtml = '<select class="form-control selectpicker" name="morning_lock" id="morning_lock">';
            for ($i = 0; $i <= $regInfo['morning_left']; $i++) {
                $morningHtml .= '<option value="' . $i . '" ';
                if ($i < $regInfo['morning_lock']) {
                    $morningHtml .= 'disabled="disabled" ';
                }
                if ($i == $regInfo['morning_lock']) {
                    $morningHtml .= 'selected="selected" ';
                }
                $morningHtml .= '>' . $i . '</option> ';
            }
            $morningHtml .= '</select>';

            $afternoonHtml = '<select class="form-control selectpicker" name="afternoon_lock" id="afternoon_lock">';
            for ($i = 0; $i <= $regInfo['afternoon_left']; $i++) {
                $afternoonHtml .= '<option value="' . $i . '" ';
                if ($i < $regInfo['afternoon_lock']) {
                    $afternoonHtml .= 'disabled="disabled" ';
                }
                if ($i == $regInfo['afternoon_lock']) {
                    $afternoonHtml .= 'selected="selected" ';
                }
                $afternoonHtml .= '>' . $i . '</option> ';
            }
            $afternoonHtml .= '</select>';

            $eveningHtml = '<select class="form-control selectpicker" name="evening_lock" id="evening_lock">';
            for ($i = 0; $i <= $regInfo['evening_left']; $i++) {
                $eveningHtml .= '<option value="' . $i . '" ';
                if ($i < $regInfo['evening_lock']) {
                    $eveningHtml .= 'disabled="disabled" ';
                }
                if ($i == $regInfo['evening_lock']) {
                    $eveningHtml .= 'selected="selected" ';
                }
                $eveningHtml .= '>' . $i . '</option> ';
            }
            $eveningHtml .= '</select>';

            $data = [
                'morning_lock' => $morningHtml,
                'afternoon_lock' => $afternoonHtml,
                'evening_lock' => $eveningHtml,
            ];
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }
}
