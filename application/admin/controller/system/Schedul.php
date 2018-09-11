<?php
namespace app\admin\controller\system;

use app\admin\model\AuthGroup;
use app\admin\model\Hospital;
use app\common\controller\Backend;
use app\admin\library\Sms;
use think\Db;

/**
 * 人员管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Schedul extends Backend
{
    protected $model;
    protected $groupList;
    protected $hosList;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('HosStaffRest');
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
            $member = model('Admin')->where('hos_id', $hos_id)->where('status', 1)->order('id', 'asc')->column('id, username');
            if (empty($member)) {
                $result = array("total" => 0, "rows" => []);
                return json($result);
            }
            $memberIds = array_keys($member);
            //print_r($member);exit;
            $date = $this->request->get("date", '');
            if (!empty($date) && date('Y-m', strtotime($date)) != $date) {
                $this->error('日期格式非法！');
            }
            if (empty($date)) {
                $year = date('Y');
                $money = date('m');
                $dayt = date('t');
            } else {
                $dates = explode('-', $date);
                $year = $dates[0];
                $money = $dates[1];
                $dayt = date('t', strtotime($date));
            }
            $list = $this->model
                ->whereIn('admin_id', $memberIds)
                ->where('year', $year)
                ->where('money', $money)
                ->select();
            $staffRest = [];
            if (!empty($list)) {
                foreach ($list as $v) {
                    $day = intval($v['day']);
                    $staffRest[$v['admin_id']][$day] = $v['id'];
                }
            }
            $list = [];
            $setAttr = $this->auth->check('system/schedul/set');
            $currentDay = date('Y-m-d');
            foreach ($member as $k => $v) {
                $column = ['username' => $v];
                for ($i = 1; $i <= $dayt; $i++) {
                    $column['_username_data'] = ['admin_id' => $k];
                    $day = $year . '-' . $money . '-' . str_pad($i, 2, "0", STR_PAD_LEFT);
                    if (isset($staffRest[$k][$i])) {
                        $column['day_' . $i] = '休';
                        if ($setAttr && $day >= $currentDay) {
                            $column['_day_' . $i . '_title'] = '取消休息日';
                            $column['_day_' . $i . '_data'] = ['staff_rest_id' => $staffRest[$k][$i], 'day' => $day];
                        }
                    } else {
                        $column['day_' . $i] = '';
                        if ($setAttr && $day >= $currentDay) {
                            $column['_day_' . $i . '_title'] = '设置为休息日';
                            $column['_day_' . $i . '_data'] = ['staff_rest_id' => '', 'day' => $day];
                        }
                    }
                }
                $list[] = $column;
            }
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
    public function edit($ids = NULL)
    {
        if (empty($ids)) {
            $this->error('非法请求！');
        }
        $hosRest =  Db::name('HosRest')
            ->where('hos_id', $ids)
            ->order('type', 'asc')
            ->column('id, type, start_time, end_time, create_time, update_time', 'type');
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $startTime = $this->request->post("start_time/a");
            $endTime = $this->request->post("end_time/a");
            if (empty($startTime[1]) || empty($endTime[1]) || empty($startTime[2]) || empty($endTime[2]) || empty($startTime[3]) || empty($endTime[3])) {
                $this->error('非法操作！');
            }
            $timeName = [
                1 => '上午',
                2 => '下午',
                3 => '晚上'
            ];
            foreach ($startTime as $k => $v) {
                $start = str_replace(":", "", $v);
                $end = str_replace(":", "", $endTime[$k]);
                if ($start > $end) {
                    //$this->error('开始时间不能早于结束时间！');
                    $this->error($timeName[$k] . '的开始时间不能早于' . $timeName[$k] . '的结束时间！');
                }
                if (!isset($lastEnd)) {
                    $lastEnd = $end;
                } elseif ($start < $lastEnd) {
                    $this->error($timeName[$k] . '的开始时间不能早于' . $timeName[$k - 1] . '的结束时间！');
                } else {
                    $lastEnd = $end;
                }
            }
            $time = time();
            if (!empty($hosRest)) {
                foreach ($hosRest as $v) {
                    if (empty($v['update_time'])) {
                        if ($time - $v['create_time'] < 48 * 3600) {
                            $this->error('48小时内不能再次设置作息时间！');
                        }
                    } else {
                        if ($time - $v['update_time'] < 48 * 3600) {
                            $this->error('48小时内不能再次设置作息时间！');
                        }
                    }
                }
            }
            Db::startTrans();
            try {
                $adminList = Db::name('Admin')->where('hos_id', $ids)->column('id');
                if (!empty($adminList)) {
                    //清空该医馆第二日及以后的所有预约及挂号信息
                    $nextDay = date("Y-m-d", strtotime("+1 day"));
                    $appointList = Db::name('Appointment')->whereIn('doctor_id', $adminList)->where('day', '>=', strtotime($nextDay))->where('status', '<>', -1)->column('id, patient_visit_record_id');
                    //print_r($appointList);exit;
                    if (!empty($appointList)) {
                        Db::name('Appointment')->whereIn('id', array_keys($appointList))->update(['status' => -1, 'updatetime' => $time]);
                        Db::name('PatientVisitRecord')->whereIn('id', $appointList)->update(['status' => -1, 'updatetime' => $time]);
                    }
                    $regiList = Db::name('Register')->whereIn('doctor_id', $adminList)->where('register_time', '>=', strtotime($nextDay))->where('status', '<>', -1)->column('id, patient_visit_record_id');
                    //print_r($regiList);exit;
                    if (!empty($regiList)) {
                        Db::name('Register')->whereIn('id', array_keys($regiList))->update(['status' => -1, 'updatetime' => $time]);
                        Db::name('PatientVisitRecord')->whereIn('id', $regiList)->update(['status' => -1, 'updatetime' => $time]);
                    }
                }
                foreach ($startTime as $k => $v) {
                    if (isset($hosRest[$k])) {
                        Db::name('HosRest')->where('id', $hosRest[$k]['id'])->update(['start_time' => $v, 'end_time' => $endTime[$k], 'update_time' => $time]);
                    } else {
                        $data = [
                            'hos_id' => $ids,
                            'type' => $k,
                            'start_time' => $v,
                            'end_time' => $endTime[$k],
                            'create_time' => $time
                        ];
                        Db::name('HosRest')->insert($data);
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
        }
        if (empty($hosRest)){ //未设置作息时间，则获取默认作息时间
            $hosRest =  Db::name('HosRest')
                ->where('hos_id', 0)
                ->order('type', 'asc')
                ->column('id, type, start_time, end_time, create_time, update_time', 'type');
        }
        $this->view->assign('hosRest', $hosRest);
        $timeList = [];
        for ($i = 0; $i < 24; $i++) {
            $time = str_pad($i, 2, "0", STR_PAD_LEFT);
            foreach (['00', '30'] as $v) {
                $key = $time . ':' . $v;
                $timeList[$key] = $key;
            }
        }
        $this->view->assign('timeList', $timeList);

        return $this->fetch('new_edit');
    }

    /**
     * 设置医生的排班
     */
    public function set()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $staffRestId = $this->request->request('staff_rest_id');
            $day = $this->request->request('day');
            $adminId = $this->request->request('admin_id');
            if (empty($staffRestId)) {
                if (empty($adminId) || empty($day)) {
                    $this->error('非法操作！');
                }
                if (date('Y-m-d', strtotime($day)) != $day) {
                    $this->error('非法日期！');
                }
                if (date('Y-m-d') > $day) {
                    $this->error('当天以前日期不能操作！');
                }

                Db::startTrans();
                try {
                    $time = time();
                    //清空该医生当天的所有预约及挂号信息
                    $appointList = Db::name('Appointment')->where('doctor_id', $adminId)->where('day', strtotime($day))->where('status', '<>', -1)->column('id, patient_visit_record_id, member_id');
                    //print_r($appointList);exit;
                    if (!empty($appointList)) {
                        $patientIds = $memberIds = [];
                        foreach ($appointList as $v) {
                            $patientIds[] = $v['patient_visit_record_id'];
                            $memberIds[] = $v['member_id'];
                        }
                        Db::name('Appointment')->whereIn('id', array_keys($appointList))->update(['status' => -1, 'updatetime' => $time]);
                        Db::name('PatientVisitRecord')->whereIn('id', $patientIds)->update(['status' => -1, 'updatetime' => $time]);
                        $memberPhone = Db::name('Member')->whereIn('id', $memberIds)->column('telphone');
                        //print_r($memberPhone);exit;
                        if (!empty($memberPhone)) {
                            $content = '您' . $day . '在问安的预约，因为医生当日休息已被取消，请重新预约';
                            foreach ($memberPhone as $phone) {
                                Sms::send($phone, $content, '', 3);
                            }
                        }
                    }
                    $regiList = Db::name('Register')->where('doctor_id', $adminId)->where('register_time', '>=', strtotime($day))->where('register_time', '<=', strtotime($day . ' 23:59:59'))->where('status', '<>', -1)->column('id, patient_visit_record_id');
                    //print_r(array_keys($regiList));exit;
                    if (!empty($regiList)) {
                        Db::name('Register')->whereIn('id', array_keys($regiList))->update(['status' => -1, 'updatetime' => $time]);
                        Db::name('PatientVisitRecord')->whereIn('id', $regiList)->update(['status' => -1, 'updatetime' => $time]);
                    }
                    $data = [
                        'admin_id' => $adminId,
                        'rest_day' => $day,
                        'year' => substr($day, 0, 4),
                        'money' => substr($day, 5, 2),
                        'day' => substr($day, 8, 2),
                        'create_time' => $time
                    ];
                    $this->model->insert($data);
                    Db::commit();
                    $error = '';
                } catch (\Exception $e) {
                    Db::rollback();
                    $error = $e->getMessage();
                }
                if (empty($error)) {
                    $this->success('设置成功！');
                } else {
                    $this->error('设置失败！');
                }
            } else {
                if ($this->model->where('id', $staffRestId)->delete()) {
                    $this->success('取消成功！');
                } else {
                    $this->error('取消失败！');
                }
            }
        }
        $this->error('非法请求！');
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
}
