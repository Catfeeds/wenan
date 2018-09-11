<?php
namespace app\admin\controller\doctor;

use app\admin\model\AuthGroup;
use app\admin\model\Hospital;
use app\common\controller\Backend;
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
                $member = model('Admin')->where('hos_id', $hos_id)->where('status', 1)->order('id', 'asc')->column('id, username');
            } else {
                $member = [$this->auth->id => $this->auth->username];
            }
            //print_r($member);
            if (empty($hos_id)) {
                $result = array("total" => 0, "rows" => []);
                return json($result);
            }
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
            $currentDay = date('Y-m-d');
            foreach ($member as $k => $v) {
                $column = ['username' => $v];
                for ($i = 1; $i <= $dayt; $i++) {
                    $column['_username_data'] = ['admin_id' => $k];
                    $day = $year . '-' . $money . '-' . str_pad($i, 2, "0", STR_PAD_LEFT);
                    if (isset($staffRest[$k][$i])) {
                        $column['day_' . $i] = '休';
                    } else {
                        $column['day_' . $i] = '';
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
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }
}
