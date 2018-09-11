<?php
namespace app\admin\controller\system;

use app\common\controller\Backend;
use fast\Random;
use think\Db;
use app\admin\library\Sms;

/**
 * 医馆管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Hospital extends Backend
{
    protected $hospitalModel;
    protected $noNeedRight = ['sms', 'gethosdep', 'gethosdepdoctor', 'gethosdoctor'];

    public function _initialize()
    {
        parent::_initialize();
        $this->hospitalModel = model('Hospital');
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->hospitalModel
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->hospitalModel
                ->where($where)
                ->field('*')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            if (!empty($list)) {
                $hosdepartM = model('HosDepart');
                $adminM = model('Admin');
                $departs = dc('DEPARTMENT');
                //print_r($departs);
                foreach ($list as $k => $v) {
                    $list[$k]['departs'] = '';
                    $departList = $hosdepartM->where('hos_id', $v['id'])->column('depart_id');
                    //print_r($departList);
                    if (!empty($departList)) {
                        foreach ($departList as $val) {
                            if (isset($departs[$val])) {
                                $list[$k]['departs'] .= $departs[$val] . '|';
                            }
                        }
                        $list[$k]['departs'] = trim($list[$k]['departs'], '|');
                    }
                    $list[$k]['account'] = $adminM->where("hos_id", $v['id'])->count();
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
        if ($this->request->isPost())
        {
            $data = input('row/a', null, 'trim');
            $data['depart'] = input('depart/a');
            $data['captcha'] = input('captcha', null, 'trim');
            //var_dump($data);exit;
            $res = $this->hospitalModel->addHospital($data);
            if ($res['code'] == 1) {
                $this->success();
            } else {
                $this->error($res['msg']);
            }
        }
        $departList = dc('DEPARTMENT');

        $this->assign('departList', $departList);
        return $this->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->hospitalModel->get(['id' => $ids]);
        if (!$row) {
            $this->error('医馆不存在');
        }
        if ($this->request->isPost()) {
            $this->request->filter(['strip_tags', 'trim']);
            $data = $this->request->post("row/a");
            $data['depart'] = $this->request->post("depart/a");
            $data['captcha'] = $this->request->post("captcha");
            //var_dump($data);exit;
            $res = $this->hospitalModel->editHospAdmin($ids, $data);
            if ($res['code'] == 1) {
                $this->success();
            } else {
                $this->error($res['msg']);
            }
        }
        $departids = Db::name('HosDepart')->where('hos_id', $row['id'])->column('depart_id');
        $this->assign("row", $row);
        $this->assign("departids", $departids);

        $departList = dc('DEPARTMENT');
        $this->assign('departList', $departList);

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
     * 发送短信
     * @internal
     */
    public function sms()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $phone = $this->request->request('phone');
            $action = $this->request->request('action');
            $hosId = $this->request->request('hos_id');
            if (!preg_match("/^1[3-9]\d{9}$/", $phone)) {
                $this->error('请输入正确的手机号码！');
            }
            if ('add' == $action) {
                $member = Db::name('Admin')->where("phone", $phone)->find();
                if (!empty($member)) {
                    $this->error('手机号已注册');
                }
            }
            if ('edit' == $action) {
                $row = $this->hospitalModel->get(['id' => $hosId]);
                if (!$row) {
                    $this->error('医馆不存在');
                }
                if ($row['admin_phone'] == $phone) {
                    $this->error('新旧管理员手机号一样');
                }
                $member = Db::name('Admin')->where("phone", $phone)->find();
                if (!empty($member) && $member['hos_id'] != $hosId) {
                    $this->error('新管理员不属于当前医馆');
                }
            }
            $time = time();
            /*$sms = Db::name('AdminSms')->where("phone", $phone)->where("type", 2)->order('id', 'desc')->find();
            if (!empty($sms) && $time - $sms['create_time'] < 60) {
                $this->error('1分钟内不能重复发送短信');
            }*/
            $smsCount = Db::name('AdminSms')->where("phone", $phone)->where("type", 2)->where("create_time", '>', $time - 300)->count();
            if ($smsCount >= 5) {
                $this->error('5分钟内最多发5次短信');
            }
            $captcha = mt_rand(100000, 999999);
            $content = '验证码是' . $captcha . ', 请保管好验证码';
            if (!Sms::send($phone, $content, $captcha, 2)) {
                $this->error('短信发送失败');
            }
            $this->success();
        }
        $this->error('非法请求！');
    }

    /**
     * 获取医馆科室
     */
    public function getHosDep()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $hosId = $this->request->request('hos_id');
            if (empty($hosId)) {
                $this->error('非法操作！');
            }
            $departids = Db::name('HosDepart')->where('hos_id', $hosId)->column('depart_id');
            if (empty($departids)) {
                $this->error('该医馆没有科室！');
            }
            $departs = [];
            $departMent = dc('DEPARTMENT');
            foreach ($departids as $v) {
                if (isset($departMent[$v])) {
                    $departs[$v] = $departMent[$v];
                }
            }
            $groupName = Db::name('AuthGroup')->where('hos_id', $hosId)->where('status', '<>', -1)->column('id, name');
            if (empty($groupName)) {
                $this->error('该医馆没有权限角色！');
            }
            $data = [];
            $data['depart_html'] = build_select('row[depart_id]', $departs, null, ['class'=>'form-control selectpicker', 'id' => 'depart_id', 'data-rule'=>'required']);
            $data['group_html'] = build_select('row[group_id]', $groupName, null, ['class'=>'form-control selectpicker', 'id' => 'group_id', 'data-rule'=>'required']);
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }

    /**
     * 获取医馆科室医生
     */
    public function getHosDepDoctor()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $hosId = $this->request->request('hos_id');
            $departId = $this->request->request('depart_id');
            if (empty($hosId) || empty($departId)) {
                $this->error('非法操作！');
            }
            $doctorList = model('Admin')->where('hos_id', $hosId)->where('depart_id', $departId)->where('status', 1)->column('id, username');
            $data = build_select('doctor_id', $doctorList, null, ['class'=>'form-control selectpicker', 'id' => 'doctor_id', 'data-rule'=>'required']);
            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }

    /**
     * 获取医馆所有人员
     */
    public function getHosDoctor()
    {
        if ($this->request->isAjax()) {
            //设置过滤方法
            $this->request->filter(['strip_tags', 'trim']);
            $hosId = $this->request->request('hos_id');
            if (empty($hosId)) {
                $this->error('非法操作！');
            }
            $doctorList = model('Admin')->where('hos_id', $hosId)->where('status', 1)->column('id, username');
            $data = '<option value="">选择</option>';
            if (!empty($doctorList)) {
                foreach ($doctorList as $k => $v) {
                    $data .= '<option value="' . $k . '">' . $v . '</option>';
                }
            }

            $this->success('', null, $data);
        }
        $this->error('非法请求！');
    }
}
