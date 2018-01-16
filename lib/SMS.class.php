<?php
/**
 * 短信类
 *
 * @version     1.0
 * @since       2016/9/6
 * @author      jizhilei
 */

class SMS {
    // 短信配置
    const SMS_SIGN = '【潇湘馆】';
    //const SEND_ADDR = 'http://101.200.193.233:20010/MsgService/SendMsg';
    const SEND_ADDR = 'http://api.llxnapp.com/index.php?m=wsgi&c=sms&a=send';
    const LOGIN_ID = 'maikukeji';
    const PASSWD = '1a2b3c4d5f';

    // 获取发送信息的请求url
    private function _getSendSMSUrl ($phone, $content) {
        if (1 !== preg_match('/^1[3-9]\d{8}\d$/i', $phone)) {
            return false;
        }
        if (empty($content)) {
            return false;
        }
        $content = self::SMS_SIGN . $content;
        $queryParams = array(
            'loginId' => self::LOGIN_ID,
            'pwd' => md5(self::PASSWD),
            'phone' => $phone,
//            'businessMsgId' => 1001, // 暂定
            'content' => $content
        );
        // 请求地址
        $reqUrl = self::SEND_ADDR . '&' . urldecode(http_build_query($queryParams));
        return $reqUrl;
    }

    // 请求发送短信接口
    private function _reqSendSMSApi ($reqUrl = '') {
        if (!$reqUrl) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $reqUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $repContent = curl_exec($ch);
        curl_close($ch);
        return $repContent;
    }

    // 解析响应的信息
    private function _parseSMSRepConn ($repContent) {
        if (!$repContent) {
            return false;
        }
        $repSet = json_decode($repContent, true);
        if (!isset($repSet['Status']) || (1 != $repSet['Status'])) {
            return false;
        }
        return true;
    }

    // 发送短信
    public function send ($phone, $content) {
    	

        $api = $this->_getSendSMSUrl($phone, $content);
        $response = $this->_reqSendSMSApi($api);
        $result = $this->_parseSMSRepConn($response);
        return $result;
    }
}