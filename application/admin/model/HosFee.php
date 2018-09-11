<?php
namespace app\admin\model;

use think\Model;

class HosFee extends Model
{
    // 表名
    protected $name = 'hos_fee';

    public function addFee($data)
    {
        $validate = validate('HosFee');
        $res = $validate->check($data);
        if (!$res) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        /*$fee = $this->where(['hos_id' => $data['hos_id'], 'fee_name' => $data['fee_name'], 'fee_id' => $data['fee_id']])->find();
        if (!empty($fee)) {
            return ['code' => 0, 'msg' => '已存在同名费用'];
        }*/
        $data['status'] = 0;
        $data['create_time'] = $data['update_time'] = time();
        try {
            $this->insert($data);
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function del($id)
    {
        if (empty($id)) {
            return ['code' => 0, 'msg' => '费用不存在'];
        }
        $fee = $this->get($id);
        if (empty($fee)) {
            return ['code' => 0, 'msg' => '费用不存在'];
        }
        if (!in_array($fee->status, [0, 2])) {
            return ['code' => 0, 'msg' => '非法操作，当前状态不可删除'];
        }
        if ($this->where('id', $id)->update(['status' => -1, 'update_time' => time()])) {
            return ['code' => 1, 'msg' => ''];
        } else {
            return ['code' => 0, 'msg' => '删除失败'];
        }
    }
}
