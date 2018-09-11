<?php
namespace app\admin\library;

use think\Db;

class Sms
{
    public static function send($phone, $content, $captcha = '', $type = 0)
    {
        if (empty($phone) || empty($content)) {
            return false;
        }
        $post_data = [];
        $post_data['phoneNum'] = $phone;       
        $post_data['messageText'] = "【问安】" . $content;
        $post_data['fun'] = $type;
        // $url = 'http://101.37.27.156:7080/sms/sendSimpleSms';//短信转发(测试用)
//        $url = 'http://10.29.113.176:7082/sms/sendSimpleSms';//短信转发(生产产用)
        $url = \think\Env::get('SMS_URL', 'http://101.37.27.156:7080/sms/sendSimpleSms');
        $res = self::httpRequest($url, http_build_query($post_data));
        // $res = true;//关闭短信
        if (!$res) {
            return false;
        }
        $data = [
            'phone'       => $phone,
            'captcha'     => $captcha,
            'content'     => $content,
            'type'        => $type,
            'create_time' => time(),
        ];
        Db::name('AdminSms')->insert($data);
        return true;
    }

    public static function httpRequest($url, $data = null)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            curl_close($curl);
            $result = preg_split("/[,\r\n]/",$output);

            if ($result[1] == 0) {
                //return "curl success";
                return true;
            } else {
                //return "curl error".$result[1];
                return false;
            }
        } elseif (function_exists('file_get_contents')) {
            $output = file_get_contents($url.$data);
            $result = preg_split("/[,\r\n]/", $output);

            if ($result[1] == 0) {
                //return "success";
                return true;
            } else {
                //return "error".$result[1];
                return false;
            }
        } else {
            return false;
        }
    }
}
