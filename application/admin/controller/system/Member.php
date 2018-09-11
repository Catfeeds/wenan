<?php
namespace app\admin\controller\system;

use app\admin\model\AuthGroup;
use app\admin\model\Hospital;
use app\common\controller\Backend;

/**
 * 人员管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Member extends Backend
{
    protected $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');

        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        $group_id = $this->auth->group_id;
        if ($group_id == 1) {
            $hosList = Hospital::column('id, hos_name');
            $groupNameList = AuthGroup::where('id', '<>', 1)->column('id, hos_id, name');
            $groupName = [];
            if (!empty($groupNameList)) {
                foreach ($groupNameList as $v) {
                    if (isset($hosList[$v['hos_id']])) {
                        $groupName[$v['id']] = $hosList[$v['hos_id']] . '--' . $v['name'];
                    } else {
                        $groupName[$v['id']] = $v['name'];
                    }
                }
            }
            //print_r($hosList);print_r($groupName);
        } else {
            $hos_id = $this->auth->hos_id;
            $hosList = Hospital::where('id', $hos_id)->column('id, hos_name');
            $groupName = AuthGroup::where('hos_id', $hos_id)->column('id, name');
            if (empty($groupName)) {
                $groupName = [2 => '管理员'];
            } else {
                $groupName[2] = '管理员';
            }
        }
        $this->view->assign('groupdata', $groupName);
        $this->assignconfig("groupdata", $groupName);
        $this->view->assign('hosList', $hosList);
        if ($this->request->isAjax()) {
            $group_id = $this->auth->group_id;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //$sort = 'logintime';
            if ($group_id != 1) {
                $hos_id = $this->auth->hos_id;
                $total = $this->model
                    ->where($where)
                    ->where('hos_id', $hos_id)
                    ->where('status', '<>', -1)
                    //->order($sort, $order)
                    ->order('id', 'desc')
                    ->count();

                $list = $this->model
                    ->where($where)
                    ->where('hos_id', $hos_id)
                    ->where('status', '<>', -1)
                    //->order($sort, $order)
                    ->order('id', 'desc')
                    ->limit($offset, $limit)
                    ->select();
            } else {
                $total = $this->model
                    ->where($where)
                    ->where('id', '<>', 1)
                    ->where('status', '<>', -1)
                    //->order($sort, $order)
                    ->order('id', 'desc')
                    ->count();

                $list = $this->model
                    ->where($where)
                    ->where('id', '<>', 1)
                    ->where('status', '<>', -1)
                    //->order($sort, $order)
                    ->order('id', 'desc')
                    ->limit($offset, $limit)
                    ->select();
            }
            $departList = dc('DEPARTMENT');
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    $list[$k]['old_group_id'] = $v['group_id'];
                    if ($group_id == 1) {
                        $list[$k]['group_id'] = isset($groupNameList[$v['group_id']]) ? $groupNameList[$v['group_id']]['name'] : '';
                    } else {
                        $list[$k]['group_id'] = isset($groupName[$v['group_id']]) ? $groupName[$v['group_id']] : '';
                    }
                    $list[$k]['hos_name'] = isset($hosList[$v['hos_id']]) ? $hosList[$v['hos_id']] : '';
                    $list[$k]['depart_name'] = isset($departList[$v['depart_id']]) ? $departList[$v['depart_id']] : '';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $params = $this->request->post("row/a");
            if ($params) {
                $res = $this->model->addMember($params);
                if ($res['code'] == 1) {
                    $this->success();
                } else {
                    $this->error($res['msg']);
                }
            }
            $this->error();
        }
        $departMent = dc('DEPARTMENT');
        $group_id = $this->auth->group_id;
        $hos_id = $this->auth->hos_id;
        if ($group_id == 1) {
            $hosList = Hospital::column('id, hos_name');
            if (!empty($hosList)) {
                $hos_id = key($hosList);
            }
        } else {
            $hosList = Hospital::where('id', $hos_id)->column('id, hos_name');
        }
        $groupName = AuthGroup::where('hos_id', $hos_id)->where('status', '<>', -1)->column('id, name');
        $this->view->assign('groupdata', $groupName);
        $this->assignconfig("groupdata", $groupName);
        $this->view->assign('hosList', $hosList);

        $departs = [];
        if (!empty($hosList)) {
            $departList = model('HosDepart')->where('hos_id', $hos_id)->column('depart_id');
            if (!empty($departList)) {
                foreach ($departList as $v) {
                    if (isset($departMent[$v])) {
                        $departs[$v] = $departMent[$v];
                    }
                }
            }
        }
        $this->view->assign('departs', $departs);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $res = $this->model->del($ids);
            if ($res['code']) {
                $this->success("删除成功!", '');
            } else {
                $this->error($res['msg']);
            }
        }
        $this->error();
    }

    /**
     * 禁用
     */
    public function forbidden($ids = "")
    {
        if ($ids)
        {
            $res = $this->model->forbidden($ids);
            if ($res['code']) {
                $this->success("禁用成功!", '');
            } else {
                $this->error($res['msg']);
            }
        }
        $this->error();
    }

    /**
     * 启用
     */
    public function startus($ids = "")
    {
        if ($ids) {
            $res = $this->model->startus($ids);
            if ($res['code']) {
                $this->success("启用成功!", '');
            } else {
                $this->error($res['msg']);
            }
        }
        $this->error();
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
