<?php
namespace app\admin\model;

use think\Model;
use think\Db;

class AuthGroup extends Model
{
    // 表名
    protected $name = 'auth_group';

    public function addGroup($data)
    {
        $validate = validate('AuthGroup');
        $res = $validate->check($data);
        if (!$res) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        $group = $this->where(['hos_id' => $data['hos_id'], 'name' => $data['name'], 'status' => 0])->find();
        if (!empty($group)) {
            return ['code' => 0, 'msg' => '已存在同名身份'];
        }
        $data['create_time'] = time();
        try {
            $this->insert($data);
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function del($id)
    {
        //$id = 39;
        if (empty($id)) {
            return ['code' => 0, 'msg' => '用户组不存在'];
        }
        $group = $this->get($id);
        if (empty($group)) {
            return ['code' => 0, 'msg' => '用户组不存在'];
        }
        $admin = Db::name('Admin')->where('group_id', $group['id'])->where('status', 1)->find();
        if (!empty($admin)) {
            return ['code' => 0, 'msg' => '用户组下还有用户，不能删除'];
        }
        if ($this->where('id', $group['id'])->update(['status' => -1, 'update_time' => time()])) {
            return ['code' => 1, 'msg' => ''];
        } else {
            return ['code' => 0, 'msg' => '删除失败'];
        }
    }
}
