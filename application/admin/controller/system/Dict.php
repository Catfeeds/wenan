<?php
namespace app\admin\controller\system;

use app\common\controller\Backend;

/**
 * 人员管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Dict extends Backend
{
    protected $dictModel;

    public function _initialize()
    {
        parent::_initialize();
        $this->dictModel = model('SystemDict');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->dictModel
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->dictModel
                ->where($where)
                ->field('*')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
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
        if ($this->request->isPost())
        {
            $data['dict_name'] = input('dict_name');
            $data['dict_value'] = input('dict_value');
            $data['dict_data'] = input('dict_data/a');
            $res = $this->dictModel->saveDict($data);
            if ($res['code'] == 1) {
                $this->success("数据保存成功!", '');
            } else {
                $this->error($res['msg']);
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()) {
            $data['dict_name'] = input('dict_name');
            $data['dict_value'] = input('dict_value');
            $data['dict_data'] = input('dict_data/a');
            $data['id'] = input('id');
            $res = $this->dictModel->updateDict($data);
            if ($res['code'] == 1) {
                $this->success("数据保存成功!", '');
            } else {
                $this->error($res['msg']);
            }
        } else {
            $vo = $this->dictModel->getOne(['id' => $ids]);
            $this->assign("vo", $vo);
            $dictColumn = isset($vo['dict_data']) ? count($vo['dict_data']) : 0;
            $config = $this->view->config;
            $config['dictColumn'] = $dictColumn;
            $this->view->assign('config', $config);
            return $this->fetch('');
        }
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $res = $this->dictModel->deleteDict($ids);
        if ($res['code']) {
            $this->error($res['msg']);
        } else {
            $this->success("删除成功!", '');
        }
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
