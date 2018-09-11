<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Patientinmember extends Backend
{
    
    /**
     * PatientInMember模型对象
     */
    protected $model = null;

    protected $noNeedRight = ['index','add','setcycle', 'setcalltime'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PatientInMember');

    }

    /**
     * 查看
     */
    public function index($ids = 0)
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

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add($memberId = 0)
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['member_id'] = $memberId;
                $params['birth_time'] = strtotime($params['birth_time']);
                try
                {

                    $result = $this->model->save($params);
                    if ($result !== false)
                    {
                        $this->success('操作成功','/admin/user/Member/detail/ids/'.$memberId);
                    }
                    else
                    {
                        $this->error($this->model->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assignconfig('memberId',$memberId);
        $this->systemDic = dc(['RELATION_TYPE']);
        $this->view->assign("systemDic",$this->systemDic);
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

    /**
     * 设置回访周期
     */
    public function setCycle()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post();
            if ($params)
            {
                $row = $this->model->get($params['id']);
                if (!$row)
                    $this->error(__('No Results were found'));
                try
                {
                    $result = $row->save($params);
                    if ($result !== false)
                    {
                        $this->success('设置成功');
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
    }
    /**
     * 设置短信发送时间点
     */
    public function setCallTime()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post();
            if ($params)
            {
                $row = $this->model->get($params['id']);
                if (!$row)
                    $this->error(__('No Results were found'));
                try
                {
                    $result = $row->save($params);
                    if ($result !== false)
                    {
                        $this->success('设置成功');
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
    }
}
