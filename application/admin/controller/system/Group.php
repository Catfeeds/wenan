<?php
namespace app\admin\controller\system;

use app\admin\model\AuthGroup;
use app\admin\model\Hospital;
use app\common\controller\Backend;

/**
 * 权限管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Group extends Backend
{
    protected $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('AuthGroup');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $hos_id = $this->auth->hos_id;
            $total = $this->model
                ->where($where)
                ->where('hos_id', $hos_id)
                ->where('status', '<>', -1)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('hos_id', $hos_id)
                ->where('status', '<>', -1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            if (!empty($list)) {
                $adminList = model('Admin')
                    ->where('hos_id', $hos_id)
                    ->where('status', 1)
                    ->field('group_id, count(*) as num')
                    ->group('group_id')
                    ->select();
                //print_r($adminList);
                if (!empty($adminList)) {
                    $adminGroup = [];
                    foreach ($adminList as $v) {
                        $adminGroup[$v['group_id']] = $v['num'];
                    }
                    //print_r($adminGroup);
                    foreach ($list as $k => $v) {
                        $list[$k]['num'] = isset($adminGroup[$v['id']]) ? $adminGroup[$v['id']] : 0;
                    }
                } else {
                    foreach ($list as $k => $v) {
                        $list[$k]['num'] = 0;
                    }
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
                $params['hos_id'] = $this->auth->hos_id;
                $params['pid'] = 2;
                $res = $this->model->addGroup($params);
                if ($res['code'] == 1) {
                    $this->success();
                } else {
                    $this->error($res['msg']);
                }
            }
            $this->error();
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error('身份不存在');
        }
        $hos_id = $this->auth->hos_id;
        if ($row['hos_id'] != $hos_id) {
            $this->error('非法操作，你无权编辑');
        }
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $data = $this->request->post("row/a");
            try {
                $data['update_time'] = time();
                $this->model->where('id', $row['id'])->update($data);
                $error = '';
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
            if (empty($error)) {
                $this->success();
            } else {
                $this->error($error);
            }
        }
        $this->assign("row", $row);
        $this->assignconfig("group", $row);

        return $this->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
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
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error('费用不存在');
        }
        if ($row['status'] != 1) {
            $this->error('非法操作，当前状态不可禁用');
        }
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $data = $this->request->post("row/a");
            if (empty($data)) {
                $this->error('请选择确认禁用');
            }
            //var_dump($data);exit;
            try {
                $data = [
                    'status' => 2,
                    'update_time' => time(),
                    'disable_time' => time()
                ];
                $this->model->where('id', $row['id'])->update($data);
                $error = '';
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
            if (empty($error)) {
                $this->success();
            } else {
                $this->error($error);
            }
        }
        $this->assign("row", $row);
        $feeType = dc('FEE_TYPE');
        $unit = dc('UNIT');
        $this->view->assign('feeType', $feeType);
        $this->view->assign('unit', $unit);

        return $this->fetch();
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
