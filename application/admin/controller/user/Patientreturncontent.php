<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Patientreturncontent extends Backend
{
    
    /**
     * PatientReturnContent模型对象
     */
    protected $model = null;

    protected $noNeedRight = ['getreturncontent','del'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PatientReturnContent');

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    //根据关键字获取获取会员
    public function getReturnContent()
    {
        if ($this->request->isAjax()) {
            //当前用户信息
//            $admin = Session::get('admin')->toArray();
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $keyword = $this->request->request('keyword');
            if (empty($keyword)) {
                return [];
            }
            $contentlist = $this->model
               ->where(['hos_id'=>$this->auth->hos_id])
                ->field('id,content as label')
                ->order('createtime','desc')
                ->limit(10)
                ->select();
            if (empty($contentlist))
            {
                $contentlist[0]['id'] = 0;
                $contentlist[0]['label'] = '暂无内容';
            }
            return $contentlist;
        }
        $this->error('非法请求！');
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds))
            {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds)->delete($ids);
            }
            else
            {
                $count = $this->model->destroy($ids);
            }
            if ($count)
            {
                $this->success();
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
