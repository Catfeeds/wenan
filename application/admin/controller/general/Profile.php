<?php

namespace app\admin\controller\general;

use think\Session;
use app\admin\model\AdminLog;
use app\common\controller\Backend;
use fast\Random;
use think\Db;
use app\admin\library\Sms;

/**
 * 个人配置
 *
 * @icon fa fa-user
 */
class Profile extends Backend
{
    protected $noNeedRight = ['modifypassword','index','modifyphone','phoneisexist','sms'];
    protected $noNeedLogin = ['sms'];
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            $model = model('AdminLog');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $model
                    ->where($where)
                    ->where('admin_id', $this->auth->id)
                    ->order($sort, $order)
                    ->count();

            $list = $model
                    ->where($where)
                    ->where('admin_id', $this->auth->id)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        else
        {
            //获取当前账户信息
            $admin = Session::get('admin')->toArray();

            //获取创建门店名
            $row['hos_name'] = model('Hospital')
                ->where('id',$admin['hos_id'])
                ->value('hos_name');

            //获取账号身分组名
            $row['group_name'] = model('AuthGroup')
                ->where('id',$admin['group_id'])
                ->value('name');

            $this->view->assign("row", $row);
        }
        return $this->view->fetch();
    }

    /**
     * 更新个人信息
     */
    public function update()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            $params = array_filter(array_intersect_key($params, array_flip(array('email', 'nickname', 'password', 'avatar'))));
            unset($v);
            if (isset($params['password']))
            {
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
            }
            if ($params)
            {
                model('admin')->where('id', $this->auth->id)->update($params);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                $admin = Session::get('admin');
                $admin_id = $admin ? $admin->id : 0;
                if($this->auth->id==$admin_id){
                    $admin = model('admin')->get(['id' => $admin_id]);
                    Session::set("admin", $admin);
                }
                $this->success();
            }
            $this->error();
        }
        return;
    }

    /**
     * 修改手机号
     */
    public function modifyPhone()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if (!empty($params))
            {
                //检测手机号是否已被注册
                $checkPhone  = model('admin')->where('phone', $params['phone'])->find();
                if (!empty($checkPhone)){
                    $this->error("该手机号已被注册");
                }
                //检测验证码
                $checkRes = $this->checkCode($params['phone'],$params['captcha']);
                if ($checkRes){
                    $this->error($checkRes['error_msg']);
                }
                model('admin')->where('id', $this->auth->id)->update(['phone' => $params['phone']]);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                $admin = Session::get('admin');
                $admin_id = $admin ? $admin->id : 0;
                if($this->auth->id==$admin_id){
                    $admin = model('admin')->get(['id' => $admin_id]);
                    Session::set("admin", $admin);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function modifyPassword()
    {
        $from = $this->request->get("from");
        $this->view->assign('from', $from);
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if (!empty($params)){
                $admin = Session::get('admin');
                if (empty($from)) {
                    //检测验证码
                    $checkRes = $this->checkCode($admin['phone'], $params['captcha']);
                    if ($checkRes) {
                        $this->error($checkRes['error_msg']);
                    }
                }
                if ($params['password']!==$params['confirm-password']){
                    $this->error(__('两次密码不一致', ''));
                }
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
                $params['edit_password'] = 1;

                if ($admin && $admin->edit_password == 0) {
                    $params['logintime'] = time();
                }
                model('admin')
                    ->allowField(['salt','password','edit_password', 'logintime'])
                    ->save($params,['id'=>$this->auth->id]);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                $admin_id = $admin ? $admin->id : 0;
                if($this->auth->id==$admin_id){
                    $admin = model('admin')->get(['id' => $admin_id]);
                    Session::set("admin", $admin);
                }
                $url = $this->request->get('url', '/admin/index/index');
//                $this->redirect(url('index/index'));
                $this->success("修改密码成功!", $url, ['url' => $url, 'id' => $this->auth->id, 'avatar' => $this->auth->avatar]);
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $dialog = $this->request->get("dialog");
        $this->view->assign('dialog', $dialog);

        return $this->view->fetch();
    }

    /**
     * 检测验证码
     */
    public function checkCode($phone,$captcha)
    {
        $sms = Db::name('AdminSms')->where("phone", $phone)->where("type", 4)->order('id', 'desc')->find();
        if (empty($sms)) {
            return ['code' => 1, 'error_msg' => '短信未发送'];
        } elseif ($captcha != $sms['captcha']) {
            return ['code' => 1, 'error_msg' => '验证码错误'];
        } elseif ($sms['create_time'] < time() - 900) {
            return ['code' => 1, 'error_msg' => '已超过15分钟有效期，请重新发送验证码'];
        }
    }

    /**
     * 检查手机号是否存在
     */
    public function phoneIsExist()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if (!empty($params))
            {
                $adminInfo = model('admin')->where('phone', $params['phone'])->find();
                if (!empty($adminInfo)){
                    return ["error" => "手机号被占用"];
                }
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
            }
            $this->error('手机号不能为空');
        }
        return ["ok" => "有效手机号"];

    }

    /**
     * 发送短信
     */
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
            $checkPhone = $this->request->request('checkPhone');
            //$action = $this->request->request('action');
            if (!preg_match("/^1[3-9]\d{9}$/", $phone)) {
                $this->error('请输入正确的手机号码！');
            }

            if($checkPhone == 1){
                $member = Db::name('Admin')->where("phone", $phone)->find();
                if (!empty($member)) {
                    $this->error('手机号已被注册');
                }
            }
            $time = time();
            /*$sms = Db::name('AdminSms')->where("phone", $phone)->where("type", 4)->order('id', 'desc')->find();
            if (!empty($sms) && $time - $sms['create_time'] < 60) {
                $this->error('1分钟内不能重复发送短信');
            }*/
            $smsCount = Db::name('AdminSms')->where("phone", $phone)->where("type", 4)->where("create_time", '>', $time - 300)->count();
            if ($smsCount >= 5) {
                $this->error('5分钟内最多发5次短信');
            }
            $captcha = mt_rand(100000, 999999);
            $content = '验证码是' . $captcha . ', 请保管好验证码';
            if (!Sms::send($phone, $content, $captcha, 4)) {
                $this->error('短信发送失败');
            }
            $this->success();
        }
        $this->error('非法请求！');
    }
}
