<?php
namespace app\admin\controller\doctor;

use app\common\controller\Backend;
use PHPExcel, PHPExcel_Writer_Excel5;

/**
 * 收费项目管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Workload extends Backend
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
            $time = time();
            //print_r($where);
            if (!empty($where)) {
                foreach ($where as $k => $v) {
                    if ($v[0] == 'end_time') {
                        $op = $v[1];
                        if ($op == '<=') {
                            $endDay = date('Y-m-d', $v[2]);
                            $where[$k][2] = $endDay == date('Y-m-d') ? $time : strtotime($endDay . ' 23:59:59');
                        } elseif ($op == 'BETWEEN') {
                            if (is_array($v[2])) {
                                $endDay = date('Y-m-d', $v[2][1]);
                                $where[$k][2][1] = $endDay == date('Y-m-d') ? $time : strtotime($endDay . ' 23:59:59');
                            }
                        }
                    } elseif ($v[0] == 'hos_name') {
                        $where[$k][0] = 'hos_id';
                    } elseif ($v[0] == 'doctor_name') {
                        $where[$k][0] = 'doctor_id';
                    }
                }
            } else {
                $month = date('Y-m');
            }
            if (isset($month)) {
                $startTime = strtotime($month);
                $t = date('t', strtotime($month));
                $endTime = strtotime($month . '-' . $t . ' 23:59:59');
                if ($endTime > $time) {
                    $endTime = $time;
                }
                array_push($where, ['end_time', '>=', $startTime], ['end_time', '<=', $endTime]);
            }
            array_push($where, ['status', '=', 2], ['is_occupy', '=', 0]);
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
            $order = 'ASC';
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
                foreach ($list as $k => &$v) {
                    $v['hos_name'] = $hospitalM
                        ->where('id', $v['hos_id'])
                        ->value('hos_name');
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
        $this->assignconfig("startDay", date('Y-m-01'));
        $this->assignconfig("endDay", date('Y-m-d'));
        $w = date('N');
        $weekStart = date('Y-m-d', strtotime((1 - $w) . ' days'));
        $weekEnd = date('Y-m-d', strtotime((7 - $w) . ' days'));
        $this->assignconfig("weekStart", $weekStart);
        $this->assignconfig("weekEnd", $weekEnd);
        $t = date('t');
        $this->assignconfig("monthEnd", date('Y-m-' . $t));

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
}
