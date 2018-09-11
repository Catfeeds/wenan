<?php

namespace app\admin\model;

use think\Model;

class Register extends Model
{
    // 表名
    protected $name = 'register';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [

    ];

    /**
     * @auth 郭庆波
     * 软删除
     * @return
     */
//    public function softdelete($id)
//    {
//        if (empty($id)) {
//            return ['code' => 0, 'msg' => 'id为空'];
//        }
////        $member = $this->get($id);
////        if (empty($member)) {
////            return ['code' => 0, 'msg' => '不存在'];
////        }
//        if ($this->where('id', $id)->update(['status' => -1, 'updatetime' => time()])) {
//            return ['code' => 1, 'msg' => '删除成功'];
//        } else {
//            return ['code' => 0, 'msg' => '删除失败'];
//        }
//    }

}
