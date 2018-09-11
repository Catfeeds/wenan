<?php
namespace app\admin\controller\doctor;

use app\common\controller\Backend;
use app\admin\library\Sms;

/**
 * 收费项目管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Appointment extends Backend
{
    protected $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Appointment');

        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_id' => $this->auth->group_id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            list($whereFunc, $sort, $order, $offset, $limit, $where) = $this->buildparams();
            //print_r($where);
            if (!empty($where)) {
                foreach ($where as $k => $v) {
                    if ($v[0] == 'start_time') {
                        $op = $v[1];
                        if ($op == '<=') {
                            $where[$k][0] = 'end_time';
                        } elseif ($op == 'BETWEEN') {
                            if (is_array($v[2])) {
                                unset($where[$k]);
                                array_push($where, ['start_time', '>=', $v[2][0]], ['end_time', '<=', $v[2][1]]);
                            }
                        }
                    } elseif ($v[0] == 'hos_name') {
                        $where[$k][0] = 'hos_id';
                    } elseif ($v[0] == 'doctor_name') {
                        $where[$k][0] = 'doctor_id';
                    } elseif ($v[0] == 'name') {
                        $where[$k][1] = 'like';
                        $where[$k][2] = '%' . $v[2] . '%';
                    }
                }
            } else {
                $month = date('Y-m');
            }
            if (isset($month)) {
                $startTime = strtotime($month);
                $t = date('t', strtotime($month));
                $endTime = strtotime($month . '-' . $t . ' 23:59:59');
                array_push($where, ['start_time', '>=', $startTime], ['end_time', '<=', $endTime]);
            }
            array_push($where, ['is_occupy', '=', 0]);
            array_push($where, ['status', '<>', -1]);
            //print_r($where);
            $whereFunc = function($query) use ($where) {
                foreach ($where as $k => $v) {
                    if (is_array($v)) {
                        call_user_func_array([$query, 'where'], $v);
                    } else {
                        $query->where($v);
                    }
                }
            };
            $sort = 'end_time';
            $order = 'DESC';
            //print_r($where);
            if ($this->auth->group_id == 1) {
                $total = $this->model
                    ->where($whereFunc)
                    ->order($sort, $order)
                    ->count();

                $list = $this->model
                    ->where($whereFunc)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            } elseif ($this->auth->group_id == 2) {
                $adminM = model('Admin');
                $adminIds = $adminM->where('hos_id', $this->auth->hos_id)->column('id');
                $total = $this->model
                    ->where($whereFunc)
                    ->whereIn('doctor_id', $adminIds)
                    ->order($sort, $order)
                    ->count();

                $list = $this->model
                    ->where($whereFunc)
                    ->whereIn('doctor_id', $adminIds)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            } else {
                $total = $this->model
                    ->where($whereFunc)
                    ->where('doctor_id', $this->auth->id)
                    ->order($sort, $order)
                    ->count();

                $list = $this->model
                    ->where($whereFunc)
                    ->where('doctor_id', $this->auth->id)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }

            if (!empty($list)) {
                $hospitalM = model('Hospital');
                $memberM = model('Member');
                $time = time();
                $medicalList = dc('MEDICAL_STATUS');
                foreach ($list as $k => &$v) {
                    $v['hos_name'] = $hospitalM
                        ->where('id', $v['hos_id'])
                        ->value('hos_name');
                    $memberInfo = $memberM ->where('id', $v['member_id'])->find();
                    $v['member_name'] = $memberInfo['name'];
                    $v['member_status'] = $memberInfo['status'];
                    if ($v['status'] == 1 && $v['start_time'] < $time) {
                        $v['allowSure'] = 1;
                    } else {
                        $v['allowSure'] = 0;
                    }
                    $end_time = $v['end_time'];
                    $medicalStatus = $v['status'];
                    if ($v['status'] == 2 && $end_time < time()) {
                        $medicalStatus = 3;
                    }
                    $v['medical_status'] = isset($medicalList[$medicalStatus]) ? $medicalList[$medicalStatus] : '';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        if ($this->auth->group_id == 1) {
            $hospitalM = model('Hospital');
            $hosList = $hospitalM->column('id, hos_name');
            $this->assignconfig("hosList", $hosList);
            $this->assignconfig("adminList", []);
        } elseif ($this->auth->group_id == 2) {
            $adminM = model('Admin');
            $adminList = $adminM->where('hos_id', $this->auth->hos_id)->where('status', 1)->column('id, username');
            $this->assignconfig("hosList", []);
            $this->assignconfig("adminList", $adminList);
        } else {
            $this->assignconfig("hosList", []);
            $this->assignconfig("adminList", []);
        }
        $this->assignconfig("monthStart", date('Y-m-01 00:00'));
        $t = date('t');
        $this->assignconfig("monthEnd", date('Y-m-' . $t) . ' 23:59');

        $this->assignconfig("todayStart", date('Y-m-d 00:00'));
        $this->assignconfig("todayEnd", date('Y-m-d 23:59'));

        $w = date('N');
        $weekStart = date('Y-m-d', strtotime((1 - $w) . ' days')) . ' 00:00';
        $weekEnd = date('Y-m-d', strtotime((7 - $w) . ' days')) . ' 23:59';
        $this->assignconfig("weekStart", $weekStart);
        $this->assignconfig("weekEnd", $weekEnd);

        return $this->view->fetch();
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
     * 编辑
     */
    public function setup()
    {
        $adminId = $this->auth->id;
        $interval = model('AdminAccount')->where('admin_id', $adminId)->value('appoint_interval');
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $data = ['admin_id' => $adminId];
            $data['appoint_interval'] = $this->request->post("appoint_interval");
            if ($interval === null) {
                $res = model('AdminAccount')->add($data);
            } else {
                $res = model('AdminAccount')->edit($adminId, $data);
            }
            if ($res['code'] == 1) {
                //清空当前时间以后的所有预约且短信通知会员
                $time = time();
                $list = $this->model
                    ->where('doctor_id', $adminId)
                    ->whereIn('status', [1, 2])
                    ->where('start_time', '>=', $time)
                    ->column('id, telphone, start_time');
                //print_r($list);exit;
                if (!empty($list)) {
                    $this->model->whereIn('id', array_keys($list))->update(['status' => 0, 'updatetime' => $time]);
                    foreach ($list as $k => $v) {
                        if (!empty($v['telphone'])) {
                            $content = '您' . date('Y-m-d H:i', $v['start_time']) . '的预约已被取消，请重新预约';
                            if (!Sms::send($v['telphone'], $content, '', 3)) {
                                $this->error('短信发送失败');
                            }
                        }
                    }
                }
                $this->success();
            } else {
                $this->error($res['msg']);
            }
        }
        $this->view->assign('interval', $interval);

        return $this->fetch('setup');
    }
}
