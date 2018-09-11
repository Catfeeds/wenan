<?php
namespace app\admin\model;

use think\Model;
use think\Db;
use fast\Random;
use app\admin\library\Sms;

class Hospital extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function addHospital($data)
    {
        $validate = validate('Hospital');
        //print_r($data);
        $res = $validate->check($data);
        if (!$res) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        $hospital = $this->where("hos_name", $data['hos_name'])->find();
        if (!empty($hospital)) {
            return ['code' => 0, 'msg' => '已有同名医馆存在'];
        }
        $member = Db::name('Admin')->where("phone", $data['phone'])->find();
        //print_r($member);exit;
        if (!empty($member)) {
            return ['code' => 0, 'msg' => '手机号已存在'];
        }
        $sms = Db::name('AdminSms')->where("phone", $data['phone'])->where("type", 2)->order('id', 'desc')->find();
        if (empty($sms)) {
            return ['code' => 0, 'msg' => '短信未发送'];
        } elseif ($data['captcha'] != $sms['captcha']) {
            return ['code' => 0, 'msg' => '验证码错误'];
        } elseif ($sms['create_time'] < time() - 900) {
            return ['code' => 0, 'error_msg' => '已超过15分钟有效期，请重新发送验证码'];
        }
        $hospital = [
            'hos_name' => $data['hos_name'],
            'admin_phone' => $data['phone'],
            'create_time' => time(),
        ];
        Db::startTrans();
        try {
            $id = $this->insertGetId($hospital);
            $member = [
                'username' => $data['hos_name'],
                'group_id' => 2,
                'avatar' => '/assets/img/avatar.png',
                'phone' => $data['phone'],
                'hos_id' => $id,
                'createtime' => time(),
                'status' => 1,
            ];
            $member['salt'] = Random::alnum();
            $password = mt_rand(100000, 999999);
            $member['password'] = md5(md5($password) . $member['salt']);
            Db::name('Admin')->insert($member);
            if (!empty($data['depart'])) {
                $hosdepartM = model('HosDepart');
                foreach ($data['depart'] as $v) {
                    $hosDepartD = [
                        'hos_id' => $id,
                        'depart_id' => $v,
                        'create_time' => time(),
                    ];
                    $hosdepartM->insert($hosDepartD);
                }
            }

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

    public function editHospAdmin($id, $data)
    {
        $row = $this->get(['id' => $id]);
        if (!$row) {
            $this->error('医馆不存在');
        }
        $hospital = $this->where("id", "<>", $id)->where("hos_name", $data['hos_name'])->find();
        if (!empty($hospital)) {
            return ['code' => 0, 'msg' => '已有同名医馆存在'];
        }
        if (!empty($data['phone'])) {
            if ($row['admin_phone'] == $data['phone']) {
                $this->error('新旧管理员手机号一样');
            }
            $member = Db::name('Admin')->where("phone", $data['phone'])->find();
            //print_r($member);exit;
            if (!empty($member) && $member['hos_id'] != $id) {
                return ['code' => 0, 'msg' => '新管理员不属于当前医馆'];
            }
            $sms = Db::name('AdminSms')->where("phone", $data['phone'])->where("type", 2)->order('id', 'desc')->find();
            if (empty($sms) || $sms['status'] == 1) {
                return ['code' => 0, 'msg' => '短信未发送'];
            } elseif ($data['captcha'] != $sms['captcha']) {
                return ['code' => 0, 'msg' => '验证码错误'];
            } elseif ($sms['create_time'] < time() - 900) {
                return ['code' => 0, 'error_msg' => '已超过15分钟有效期，请重新发送验证码'];
            }
        }
        Db::startTrans();
        try {
            //修改医馆信息
            $params = [
                'hos_name'     => $data['hos_name'],
                'update_time'  => time(),
            ];
            if (!empty($data['phone'])) {
                $params['admin_phone'] = $data['phone'];
            }
            $this->where('id', $id)->update($params);

            //修改医馆科室
            $hosdepartM = model('HosDepart');
            $oldDepart = $hosdepartM->where('hos_id', $id)->column('id, depart_id');
            //print_r($oldDepart);Db::rollback();
            $newDepart = empty($data['depart']) ? [] : $data['depart'];
            $delIds = array_diff($oldDepart, $newDepart);
            $newIds = array_diff($newDepart, $oldDepart);
            //print_r($delIds);print_r($newIds);Db::rollback();
            if (!empty($delIds)) {
                foreach ($delIds as $v) {
                    $adminInDepart = Db::name('Admin')->where('hos_id', $id)->where("depart_id", $v)->find();
                    if (!empty($adminInDepart)) {
                        Db::rollback();
                        return ['code' => 0, 'msg' => dc('DEPARTMENT', $v) . '下还有人员，不能删除'];
                    }
                }
                $delIds = array_keys($delIds);
                $hosdepartM->whereIn('id', $delIds)->delete();
            }
            Db::name('AdminSms')->where("id", $sms['id'])->update(['status' => 1]);
            if (!empty($newIds)) {
                foreach ($newIds as $v) {
                    $hosDepartD = [
                        'hos_id' => $id,
                        'depart_id' => $v,
                        'create_time' => time(),
                    ];
                    $hosdepartM->insert($hosDepartD);
                }
            }

            if (!empty($data['phone'])) {
                //彻底删除旧的的医馆管理员
                //Db::name('Admin')->where('phone', $row['admin_phone'])->update(['status' => -1, 'updatetime' => time()]);
                Db::name('Admin')->where('phone', $row['admin_phone'])->delete();

                //处理新的医馆管理员
                if (!empty($member)) {
                    Db::name('Admin')->where('id', $member['id'])->update(['group_id' => 2, 'updatetime' => time()]);

                    $password = '';
                    $content = '您已被设置为' . $data['hos_name'] . '的新管理员';
                    $smsType = 3;
                } else {
                    $member = [
                        'username' => $data['hos_name'],
                        'group_id' => 2,
                        'avatar' => '/assets/img/avatar.png',
                        'phone' => $data['phone'],
                        'hos_id' => $id,
                        'createtime' => time(),
                        'status' => 1,
                    ];
                    $member['salt'] = Random::alnum();
                    $password = mt_rand(100000, 999999);
                    $member['password'] = md5(md5($password) . $member['salt']);
                    Db::name('Admin')->insert($member);

                    $content = '您的登录密码是' . $password . ', 请登录后立刻修改';
                    $smsType = 1;
                }
                if (!Sms::send($data['phone'], $content, $password, $smsType)) {
                    Db::rollback();
                    return ['code' => 0, 'msg' => '短信发送失败'];
                }
            }
            Db::commit();
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }
}
