<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Validate;
use think\Db;
use app\admin\library\Sms;
use think\Session;
use fast\Random;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login', 'forget', 'sms', 'modifypassword'];
    protected $noNeedRight = ['index', 'logout'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //
        $menulist = $this->auth->getSidebar([
            'dashboard'  => 'hot',
            'addon'       => ['new', 'red', 'badge'],
            'auth/rule'  => 'side',
            'general'    => ['18', 'purple'],
                ], $this->view->site['fixedpage']);


        $this->view->assign('menulist', $menulist);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }

    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        if (!empty($url) && preg_match("/logout/i", $url)) {
            $url = 'index/index';
        }

        if ($this->auth->isLogin())
        {
            $admin = Session::get("admin");
            //print_r($admin);exit;
            if ($admin->edit_password == 0) {
                $url = $this->request->get('url', '/admin/general/profile/modifyPassword');
            }
            $this->error(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost())
        {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];
            $validate = new Validate($rule);
            $result = $validate->check($data);
            if (!$result)
            {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            \app\admin\model\AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result['code'] == 0)
            {
                if ($result['edit_password'] == 0) {
                    $url = $this->request->get('url', '/admin/general/profile/modifyPassword');
                }
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            }
            else
            {
                $this->error($result['error_msg'], $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin())
        {
            $admin = Session::get("admin");
            if ($admin->edit_password == 0) {
                $url = $this->request->get('url', '/admin/general/profile/modifyPassword');
            }
            $this->redirect($url);
        }
        $background = cdnurl("/assets/img/loginbg.jpg");
        $this->view->assign('background', $background);
        \think\Hook::listen("login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'), 'index/login');
    }

    /**
     * 忘记密码
     */
    public function forget()
    {
        $url = 'index/index';
        if ($this->auth->isLogin()) {
            $this->error(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost()) {
            $url = '/admin/index/forget';
            $username = $this->request->post('username');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'captcha'  => 'require',
                '__token__' => 'token',
            ];
            $data = [
                'username'  => $username,
                'captcha'  => $captcha,
                '__token__' => $token,
            ];
            $validate = new Validate($rule);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            $result = $this->auth->forgetLogin($username, $captcha, 0);
            if ($result['code'] == 0) {
                $this->success('验证通过', 'index/modifyPassword', ['url' => 'index/modifyPassword', 'username' => $username]);
            } else {
                $this->error($result['error_msg'], $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = cdnurl("/assets/img/loginbg.jpg");
        $this->view->assign('background', $background);
        return $this->view->fetch();
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
            $noCheckPhoneInSys = $this->request->request('noCheckPhoneInSys');
            //$action = $this->request->request('action');
            if (!preg_match("/^1[3-9]\d{9}$/", $phone)) {
                $this->error('请输入正确的手机号码！');
            }

            if(empty($noCheckPhoneInSys)){
                $member = Db::name('Admin')->where("phone", $phone)->find();
                if (empty($member)) {
                    $this->error('手机号不存在');
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

    /**
     * 修改密码
     */
    public function modifyPassword()
    {
        $url = 'index/index';
        if ($this->auth->isLogin()) {
            $this->error(__("You've logged in, do not login again"), $url);
        }
        $admin = Session::get("forgetadmin");
        if (empty($admin)) {
            $this->error('非法操作', '/', ['token' => $this->request->token()]);
        }
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if (!empty($params)) {
                if ($params['password'] != $params['confirm-password']) {
                    $this->error(__('两次密码不一致', ''));
                }
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
                $params['edit_password'] = 1;

                if ($admin && $admin->edit_password == 0) {
                    $params['logintime'] = time();
                }
                $admin_id = $admin->id;
                model('admin')
                    ->allowField(['salt', 'password', 'edit_password', 'logintime'])
                    ->save($params, ['id' => $admin_id]);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                $admin = model('admin')->get(['id' => $admin_id]);
                Session::set("admin", $admin);
                Session::delete("forgetadmin");
                $url = '/admin/index/index';
                $this->success("修改密码成功!", $url, ['url' => $url, 'id' => $this->auth->id, 'avatar' => $this->auth->avatar]);
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('admin', $admin);
        $background = cdnurl("/assets/img/loginbg.jpg");
        $this->view->assign('background', $background);

        return $this->view->fetch();
    }
}
