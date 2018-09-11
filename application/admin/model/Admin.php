<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use fast\Random;
use app\admin\library\Sms;

class Admin extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

    public function addMember($data)
    {
        $validate = validate('Admin');
        //print_r($data);
        $res = $validate->check($data);
        if (!$res) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        $member = $this->where("phone", $data['phone'])->find();
        //print_r($member);exit;
        if (!empty($member)) {
            return ['code' => 0, 'msg' => '手机号已存在'];
        }
        $data['salt'] = Random::alnum();
        $password = mt_rand(100000, 999999);
        $data['password'] = md5(md5($password) . $data['salt']);
        $data['avatar'] = '/assets/img/avatar.png';
        $data['createtime'] = time();
        Db::startTrans();
        try {
            $this->insertGetId($data);

            $content = '您的登录密码是' . $password . ', 请登录后立刻修改';
            if (!Sms::send($data['phone'], $content, $password, 1)) {
                Db::rollback();
                return ['code' => 0, 'msg' => '注册短信发送失败'];
            }
            Db::commit();
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function del($id)
    {
        //$id = 39;
        if (empty($id)) {
            return ['code' => 0, 'msg' => '用户不存在'];
        }
        $member = $this->get($id);
        if (empty($member)) {
            return ['code' => 0, 'msg' => '用户不存在'];
        }
        if ($member->logintime > 0) {
            return ['code' => 0, 'msg' => '已登陆过用户不能删除，只能禁用'];
        }
        //if ($this->where('id', $id)->update(['status' => -1, 'updatetime' => time()])) {
        //彻底删除
        if ($this->where('id', $id)->delete()) {
            return ['code' => 1, 'msg' => ''];
        } else {
            return ['code' => 0, 'msg' => '删除失败'];
        }
    }

    public function forbidden($id)
    {
        if (empty($id)) {
            return ['code' => 0, 'msg' => '用户不存在'];
        }
        $member = $this->get($id);
        if (empty($member)) {
            return ['code' => 0, 'msg' => '用户不存在'];
        }
        if ($this->where('id', $id)->update(['status' => 2, 'updatetime' => time()])) {
            return ['code' => 1, 'msg' => ''];
        } else {
            return ['code' => 0, 'msg' => '禁用失败'];
        }
    }

    public function startus($id)
    {
        if (empty($id)) {
            return ['code' => 0, 'msg' => '用户不存在'];
        }
        $member = $this->get($id);
        if (empty($member)) {
            return ['code' => 0, 'msg' => '用户不存在'];
        }
        if ($this->where('id', $id)->update(['status' => 1, 'updatetime' => time()])) {
            return ['code' => 1, 'msg' => ''];
        } else {
            return ['code' => 0, 'msg' => '启用失败'];
        }
    }
}
