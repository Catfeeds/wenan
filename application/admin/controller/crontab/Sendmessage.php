<?php

namespace app\admin\controller\crontab;

use think\Controller;
use app\admin\library\Sms;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Sendmessage extends Controller
{

    /**
     * PatientReturnRecord模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PatientReturnRecord');

    }

    public function index()
    {
        return  'test success';
    }

    /**
     * 预设回访时间到了之后发送短信
     */
    public function atReturnTimeSendMessage()
    {
        $data = [];
        //获取回访今日要发送的短信
        $nowTime = strtotime(date('Y-m-d H:00'));
        $patientList = $this->model
            ->alias('r')
            ->join('wa_member m','m.id = r.member_id and r.return_time = '.$nowTime.' and r.is_send = 0 and m.telphone !="" ')
           ->join('wa_patient_in_member p','r.patient_in_member_id = p.id')
            ->field('r.id,r.return_time,r.content,m.telphone,m.name as member_name,p.name as patient_name')
            ->select();
//        $patientList = $this->model->where([]);
        if (!empty($patientList)){
            foreach ($patientList as $key => $val){
                //获取病人短信发送时间点
                if ($nowTime == $val['return_time']){
                    $res = $this->model
                        ->where(['id' => $val['id'],'is_send' => 0])
                        ->update(['is_send' => 1]);
                    if ($res && !empty($val['telphone'])){
                        $content = '尊敬的'.$val['member_name'].',问安将在' . date('Y-m-d H:00',$val['return_time']) . '对您会员名下的'.$val['patient_name'].'进行回访,回访内容：'.$val['content'];
                        $sendRes = Sms::send($val['telphone'], $content, '', 3);//发送成功更新，回访表为已发送
                        if ($sendRes){
                            $data[] = '发送会员'.$val['member_name'].'名下的'.$val['patient_name'].'的回访短信成功';
                        }
                    }
                }
            }
        }
//        return json_encode($data,JSON_UNESCAPED_UNICODE);
        return 'run success';
    }
}
