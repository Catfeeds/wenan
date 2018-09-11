<?php

namespace app\admin\controller\system;

use app\common\controller\Backend;
use PHPExcel, PHPExcel_Writer_Excel5;

/**
 * 收费项目管理
 *
 * @icon fa fa-list
 * @remark 规则通常对应一个控制器的方法,同时左侧的菜单栏数据也从规则中体现,通常建议通过控制台进行生成规则节点
 */
class Performance extends Backend
{
    protected $model;
    protected $noNeedRight = ['export'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ChargeInfo');

        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_id' => $this->auth->group_id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $result = $this->Lists();
            return json($result);
        }

        if ($this->auth->group_id == 1) {
            $hospitalM = model('Hospital');
            $hosList = $hospitalM->column('id, hos_name');
            $this->assignconfig("hosList", $hosList);
            $this->assignconfig("adminList", []);
        } else {
            $adminM = model('Admin');
            $adminList = $adminM->where('hos_id', $this->auth->hos_id)->where('status', 1)->column('id, username');
            $this->assignconfig("hosList", []);
            $this->assignconfig("adminList", $adminList);
        }/* else {
            $this->assignconfig("hosList", []);
            $this->assignconfig("adminList", []);
        }*/
        $this->assignconfig("statusList", [2 => '未支付', 3 => '已支付']);
        $this->assignconfig("month", date('Y-m'));

        return $this->view->fetch();
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    public function Lists()
    {
        $adminM = model('Admin');
        list($whereFunc, $sort, $order, $offset, $limit, $where) = $this->buildparams();
        //print_r($where);
        $export = $this->request->get("export", 0);
        $filename = '绩效';
        if (!empty($where)) {
            foreach ($where as $k => $v) {
                if ($v[0] == 'createtime') {
                    $month = $v[2];
                    unset($where[$k]);
                    if (1 == $export) {
                        $filename .= '-销售时间-' . $month;
                    }
                } elseif ($v[0] == 'hos_name') {
                    unset($where[$k]);
                } elseif ($v[0] == 'status') {
                    $where[$k][2] = $status = $v[2] == 3 ? 1 : 0;
                } elseif ($v[0] == 'admin_name') {
                    $where[$k][0] = 'admin_input_id';
                    if (1 == $export) {
                        $userName = $adminM
                            ->where('id', $where[$k][2])
                            ->value('username');
                        $filename .= '-操作者-' . $userName;
                    }
                }
            }
        } else {
            $month = date('Y-m');
            if (1 == $export) {
                $filename .= '-销售时间-' . $month;
            }
        }
        if (isset($month) && date('Y-m', strtotime($month)) == $month) {
            $startTime = strtotime($month);
            $t = date('t', strtotime($month));
            $endTime = strtotime($month . '-' . $t . ' 23:59:59');
            array_push($where, ['createtime', '>=', $startTime], ['createtime', '<=', $endTime]);
        }
        array_push($where, ['newfee', '=', 1]);
        $whereFunc = function($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        $sort = 'createtime';
        $order = 'ASC';
        //print_r($where);
        //admin看到所有门店的所有人员的绩效，门店人员有权限查看员工绩效页面的看到整个门店的所有人员的绩效，by 2018-6-11
        if ($this->auth->group_id == 1) {
            $total = $this->model
                ->where($whereFunc)
                ->order($sort, $order)
                ->count();

            if (1 == $export) {
                $list = $this->model
                    ->where($whereFunc)
                    ->order($sort, $order)
                    ->select();
            } else {
                $list = $this->model
                    ->where($whereFunc)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }

            if (isset($status)) {
                if ($status == 1) {
                    $payedAmount = $this->model
                        ->where($whereFunc)
                        ->sum('should_pay');
                } else {
                    $payedAmount = 0;
                }
            } else {
                $payedAmount = $this->model
                    ->where($whereFunc)
                    ->where('status', 1)
                    ->sum('should_pay');
            }
        } else {
            $adminIds = $adminM->where('hos_id', $this->auth->hos_id)->column('id');
            $total = $this->model
                ->where($whereFunc)
                ->whereIn('admin_input_id', $adminIds)
                ->order($sort, $order)
                ->count();

            if (1 == $export) {
                $list = $this->model
                    ->where($whereFunc)
                    ->whereIn('admin_input_id', $adminIds)
                    ->order($sort, $order)
                    ->select();
            } else {
                $list = $this->model
                    ->where($whereFunc)
                    ->whereIn('admin_input_id', $adminIds)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }

            if (isset($status)) {
                if ($status == 1) {
                    $payedAmount = $this->model
                        ->where($whereFunc)
                        ->whereIn('admin_input_id', $adminIds)
                        ->sum('should_pay');
                } else {
                    $payedAmount = 0;
                }
            } else {
                $payedAmount = $this->model
                    ->where($whereFunc)
                    ->whereIn('admin_input_id', $adminIds)
                    ->where('status', 1)
                    ->sum('should_pay');
            }
        }/* else {
            $total = $this->model
                ->where($whereFunc)
                ->where('admin_input_id', $this->auth->id)
                ->order($sort, $order)
                ->count();

            if (1 == $export) {
                $list = $this->model
                    ->where($whereFunc)
                    ->where('admin_input_id', $this->auth->id)
                    ->order($sort, $order)
                    ->select();
            } else {
                $list = $this->model
                    ->where($whereFunc)
                    ->where('admin_input_id', $this->auth->id)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }

            if (isset($status)) {
                if ($status == 1) {
                    $payedAmount = $this->model
                        ->where($whereFunc)
                        ->where('admin_input_id', $this->auth->id)
                        ->sum('should_pay');
                } else {
                    $payedAmount = 0;
                }
            } else {
                $payedAmount = $this->model
                    ->where($whereFunc)
                    ->where('admin_input_id', $this->auth->id)
                    ->where('status', 1)
                    ->sum('should_pay');
            }

            if (1 == $export) {
                $filename .= '-操作者-' . $this->auth->username;
            }
        }*/

        if (!empty($list)) {
            $feeType = dc('FEE_TYPE');
            $hosFeeM = model('HosFee');
            $memberM = model('Member');
            $hospitalM = model('Hospital');
            foreach ($list as $k => &$v) {
                $v['fee_id'] = isset($feeType[$v['fee_id']]) ? $feeType[$v['fee_id']] : '';

                $feeName = $hosFeeM
                    ->where('id', $v['hos_fee_id'])
                    ->value('fee_name');
                $v['hos_fee_name'] = $feeName;

                $memberInfo = $memberM->get(['id' => $v['member_id']]);
                $v['member_name'] = $memberInfo['name'];
                $v['telphone'] = $memberInfo['telphone'];

                $adminInfo = $adminM->get(['id' => $v['admin_input_id']]);
                $v['admin_name'] = $adminInfo['username'];
                $v['admin_hos_id'] = $adminInfo['hos_id'];

                if (!empty($v['admin_hos_id'])) {
                    $hosName = $hospitalM
                        ->where('id', $v['admin_hos_id'])
                        ->value('hos_name');
                    $v['hos_name'] = $hosName;
                } else {
                    $v['hos_name'] = '';
                }
            }
        }
        $result = array("total" => $total, "rows" => $list, 'payedAmount' => $payedAmount, 'filename' => $filename);
        return $result;
    }

    /**
     * 绩效导出
     */
    public function export($result = [])
    {
        if (empty($result)) {
            $result = $this->Lists();
        }
        $list = $result['rows'];
        $excel = new PHPExcel();

        //Excel表格式,这里简略写了8列
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O');
        //表头数组
        $tableheader = ['会员名', '会员手机号', '销售时间', '金额', '支付状态', '费用类型', '费用名称', '操作者医馆', '操作者'];
        //填充表头信息
        foreach ($tableheader as $k => $v) {
            $excel->getActiveSheet()->setCellValue("$letter[$k]1", $v);
        }
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);

        $items = [];
        foreach ($list as $v) {
            //表格数组
            $item = [
                $v['member_name'],
                $v['telphone'],
                date('Y-m-d H:i:s', $v['createtime']),
                $v['should_pay'],
                $v['status'] == 1 ? '已支付' : '未支付',
                $v['fee_id'],
                $v['hos_fee_name'],
                $v['hos_name'],
                $v['admin_name'],
            ];
            $items[] = $item;
        }
        if (!empty($items)) {
            //填充表格信息
            for ($i = 2; $i <= count($items) + 1; $i++) {
                $j = 0;
                foreach ($items[$i - 2] as $key => $value) {
                    $excel->getActiveSheet()->setCellValue("$letter[$j]$i", $value);
                    $j++;
                }
            }
            //合并单元格,值为''
            $excel->getActiveSheet()->mergeCells("A" . $i . ":" . $letter[$j - 1] . $i);
            $excel->getActiveSheet()->setCellValue("A$i", '已支付金额总计：' . $result['payedAmount']);
        } else {
            $excel->getActiveSheet()->mergeCells("A2:" . $letter[count($tableheader) - 1] . '2');
            $excel->getActiveSheet()->setCellValue("A2", '已支付金额总计：' . $result['payedAmount']);
        }
        $filename = $result['filename'] . '.xls';
        //创建Excel输入对象
        $write = new PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$filename.'"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }
}
