<?php
namespace minimum_alarm;

require_once 'YAlertor.php';

/**
 * 微信公众号报警。根据 OpenID 列表群发。订阅号不可用，服务号认证后可用。
 * 构造函数中的 config 必须包含：$config['ali_sign_name'], $config['ali_template_code'], $config['ali_param_name'], $config['ali_appkey'], $config['ali_appsecret']
 * 构造函数中的 config 必须包含：$config['to_mobiles'] 数组，接收者的 手机号
 */
class YAlertorALiSMS extends YAlertor
{
    const URL_TOKEN = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s";
    const URL_ALARM = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=%s";

    /**
     * 发送消息
     * @access public
     * @param string $message 消息体
     * @param string $title 无用
     * @return bool
     */
    public function sendAlarm( $message, $title )
    {
        $request_paras = array (
            'ParamString' => '{"' . $this->m_config['ali_param_name'] .'":"' . $message . '"}',
            'RecNum' => implode( '|', $this->m_config['to_mobiles'] ),
            'SignName' => $this->m_config['ali_sign_name'], 
            'TemplateCode' => $this->m_config['ali_template_code']
        );
        
        $request_host = "http://sms.market.alicloudapi.com";
        $request_uri = "/singleSendSms";
        $request_method = "GET";
        $info = "";
        $res_ary = $this->doGet($this->m_config['ali_appkey'], $this->m_config['ali_appsecret'], $request_host, $request_uri, $request_method, $request_paras, $info);
        echo "\nres_ary = "; print_r($res_ary); // API返回值 

        return true;
    }

    protected function doGet($app_key, $app_secret, $request_host, $request_uri, $request_method, $request_paras, &$info) 
    {
        ksort($request_paras);
        $request_header_accept = "application/json;charset=utf-8";
        $content_type = "";
        $headers = array(
                'X-Ca-Key' => $app_key,
                'Accept' => $request_header_accept
                );
        ksort($headers);
        $header_str = "";
        $header_ignore_list = array('X-CA-SIGNATURE', 'X-CA-SIGNATURE-HEADERS', 'ACCEPT', 'CONTENT-MD5', 'CONTENT-TYPE', 'DATE');
        $sig_header = array();
        foreach($headers as $k => $v) {
            if(in_array(strtoupper($k), $header_ignore_list)) {
                continue;
            }
            $header_str .= $k . ':' . $v . "\n";
            array_push($sig_header, $k);
        }
        $url_str = $request_uri;
        $para_array = array();
        foreach($request_paras as $k => $v) {
            array_push($para_array, $k .'='. $v);
        }
        if(!empty($para_array)) {
            $url_str .= '?' . join('&', $para_array);
        }
        $content_md5 = "";
        $date = "";
        $sign_str = "";
        $sign_str .= $request_method ."\n";
        $sign_str .= $request_header_accept."\n";
        $sign_str .= $content_md5."\n";
        $sign_str .= "\n";
        $sign_str .= $date."\n";
        $sign_str .= $header_str;
        $sign_str .= $url_str;
    
        $sign = base64_encode(hash_hmac('sha256', $sign_str, $app_secret, true));
        $headers['X-Ca-Signature'] = $sign;
        $headers['X-Ca-Signature-Headers'] = join(',', $sig_header);
        $request_header = array();
        foreach($headers as $k => $v) {
            array_push($request_header, $k .': ' . $v);
        }
    
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $request_host . $url_str);
        //curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return $ret;
    }
}

?>