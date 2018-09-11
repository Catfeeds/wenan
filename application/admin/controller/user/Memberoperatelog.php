<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

use think\Controller;
use think\Request;

/**
 * 会员操作记录
 *
 * @icon fa fa-circle-o
 */
class Memberoperatelog extends Backend
{
    
    /**
     * MemberOperateLog模型对象
     */
    protected $model = null;

    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('MemberOperateLog');

    }

    /**
     * 查看
     */
    public function index($ids = NULL)
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
                ->where('member_id',$ids)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where('member_id',$ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            if(!empty($list))
            {
                $this->memberModel = model('Member');
                foreach ($list as $key => &$val)
                {
                    $val['name'] = $this->memberModel
                        ->where('id',$val['member_id'])
                        ->value('name');
//                    $val['content'] = '更新'.$val['name'].'会员资料'.' '. $val['content'];

                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        else
        {
            $this->assignconfig('member_id', $ids);
        }
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

}
