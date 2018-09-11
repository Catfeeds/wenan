<?php
namespace app\common\model;

use think\Model;

abstract class BaseModel extends Model
{
    public abstract function getOne($where = [], $order = '');

    public abstract function getList($where = [], $order = '', $group = '');
}