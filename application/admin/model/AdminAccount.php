<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use fast\Random;
use app\admin\library\Sms;

class AdminAccount extends Model
{
    public function add($data)
    {
        $data['create_time'] = time();
        try {
            $this->insert($data);
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function edit($id, $data)
    {
        $data['update_time'] = time();
        try {
            $this->where('admin_id', $id)->update($data);
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }
}