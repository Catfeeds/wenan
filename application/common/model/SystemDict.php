<?php
namespace app\common\model;

use think\Model;
use think\Db;

class SystemDict extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';

    protected $table = 'wa_system_dict';

    public function dict_data()
    {
        return $this->hasMany("SystemDictData", "dict_id", "id");
    }

    public function saveDict($data)
    {
        $validate = validate('Dict');
        $dict['dict_name'] = trim($data['dict_name']);
        $dict['dict_value'] = trim($data['dict_value']);
        $dict['create_time'] = time();
        $res = $validate->check($dict);
        if (!$res) {
            return ['code' => 0, 'msg' => $validate->getError()];
        }
        Db::startTrans();
        try {
            $id = $this->insertGetId($dict);
            if (!empty($data['dict_data'])) {
                foreach ($data['dict_data'] as $item) {
                    $item['dict_id'] = $id;
                    $this->dict_data()->insert($item);
                }
            }
            Db::commit();
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function updateDict($data)
    {
        $dict = $this->where("id", $data['id'])->find()->data;
        $dict['dict_name'] = trim($data['dict_name']);
        $dict['dict_value'] = trim($data['dict_value']);
        Db::startTrans();
        try {
            $this->dict_data()->where("dict_id", $dict['id'])->delete();
            $this->where("id", $data['id'])->update($dict);
            if (!empty($data['dict_data'])) {
                foreach ($data['dict_data'] as $item) {
                    $item['dict_id'] = $dict['id'];
                    $this->dict_data()->insert($item);
                }
            }
            Db::commit();
            return ['code' => 1, 'msg' => ''];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function deleteDict($id)
    {
        Db::startTrans();
        try {
            //$dict = $this->where("id", $id)->find();
            //$dict->dict_data()->where("dict_id", $dict['id'])->delete();
            //$dict->delete();
            $this->whereIn("id", $id)->delete();
            Db::name('SystemDictData')->whereIn("dict_id", $id)->delete();
            Db::commit();
            return ['code' => 0, 'msg' => ''];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    public function getOne($where = '', $order = '')
    {
        if (isset($where['id'])) {
            $dict = $this->where('id', $where['id'])->order($order)->find();
            $dict['dict_data'] = $this->dict_data()->where(["dict_id" => $dict['id']])->select();
            return $dict;
        } else {
            return $this->where($where)->order($order)->limit(1)->select();
        }
    }

    public function getList($where = '', $order = '', $group = '')
    {
        // TODO: Implement getList() method.
    }
}
