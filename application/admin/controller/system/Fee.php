<?php
namespace app\admin\controller\system;

use app\common\controller\Backend;

/**
 * 收费项目管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Fee extends Backend
{
    protected $model;
    protected $noNeedRight = ['getfeeinfo', 'getfeelist'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('HosFee');

        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_id' => $this->auth->group_id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        $feeType = dc('FEE_TYPE');
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'update_time';
            $order = 'desc';
            $total = $this->model
                ->where($where)
                ->where('status', '<>', -1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('status', '<>', -1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            if (!empty($list)) {
                $status = [
                    -1 => '删除',
                    0 => '未启用',
                    1 => '启用',
                    2 => '未启用'
                ];
                foreach ($list as $k => $v) {
                    $list[$k]['fee_id'] = isset($feeType[$v['fee_id']]) ? $feeType[$v['fee_id']] : '';
                    $list[$k]['old_status'] = $v['status'];
                    $list[$k]['status'] = isset($status[$v['status']]) ? $status[$v['status']] : '';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig("feeType", $feeType);

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
                $res = $this->model->addFee($params);
                if ($res['code'] == 1) {
                    $this->success();
                } else {
                    $this->error($res['msg']);
                }
            }
            $this->error();
        }
        $feeType = dc('FEE_TYPE');
        //如果挂号费已经添加，则不允许再次添加挂号费，by 2018-6-11
        $registerFee = $this->model
            ->where('fee_id', 1)
            ->where('status', 1)
            ->find();
        if (! empty($registerFee)) {
            unset($feeType[1]);
        }
        $units = dc('UNIT');
        $unit = [];
        if (!empty($units)) {
            foreach ($units as $v) {
                $unit[$v] = $v;
            }
        }
        $this->view->assign('feeType', $feeType);
        $this->view->assign('unit', $unit);

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error('费用不存在');
        }
        if (!in_array($row['status'], [0, 2])) {
            $this->error('非法操作，当前状态不可编辑');
        }
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $data = $this->request->post("row/a");
            //如果挂号费已经启用，则不允许再次添加挂号费，by 2018-6-12
            $registerFee = $this->model
                ->where('fee_id', 1)
                ->where('status', 1)
                ->find();
            if (! empty($registerFee) && $row['fee_id'] == 1 && $data['status'] == 1) {
                $this->error('已经有一个启用的挂号费');
            }
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
        $feeType = dc('FEE_TYPE');
        $row['fee_id_name'] = isset($feeType[$row['fee_id']]) ? $feeType[$row['fee_id']] : '';
        $this->assign("row", $row);
        $units = dc('UNIT');
        $unit = [];
        if (!empty($units)) {
            foreach ($units as $v) {
                $unit[$v] = $v;
            }
        }
        $this->view->assign('feeType', $feeType);
        $this->view->assign('unit', $unit);

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

    /**
     * 获取医馆科室
     */
    public function getFeeInfo()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $id = $this->request->request('id');
            if (empty($id)) {
                $this->error('非法操作！');
            }
            $row = $this->model->get(['id' => $id]);
            if (!$row) {
                $this->error('该费用不存在！');
            }
            $data = [];
            $data['price'] = $row['price'];
            $data['hosFeeNmae'] = $row['fee_name'];
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }

    /**
     * 获取费用列表
     */
    public function getFeeList()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $feeId = $this->request->request('fee_id');
            if (empty($feeId)) {
                $this->error('非法操作！');
            }
            $hosFee = $this->model->field('id, fee_name, price')->where('fee_id', $feeId)->where('status', 1)->select();
            if (empty($hosFee)) {
                $this->error('该类型还没创建费用！');
            }
            $hosFeeList = [];
            if (!empty($hosFee)) {
                foreach ($hosFee as $v) {
                    $hosFeeList[$v['id']] = $v['fee_name'];
                }
            }
            $price = '';
            $hosFeeNmae = '';
            if (!empty($hosFee)) {
                foreach ($hosFee as $k => $v) {
                    $price = $v['price'];
                    $hosFeeNmae = $v['fee_name'];
                    break;
                }
            }
            $data = [];
            $data['hos_fee_html'] = build_select('row[hos_fee_id]', $hosFeeList, null, ['class' => 'form-control selectpicker', 'id' => 'hos_fee_id']);
            $data['price'] = $price;
            $data['hosFeeNmae'] = $hosFeeNmae;
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }
}
