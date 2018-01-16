<?php
namespace minimum_alarm;
require_once 'YAlertor.php';

/**
 * 微信公众号报警。根据 OpenID 列表群发。订阅号不可用，服务号认证后可用。
 * 构造函数中的 config 必须包含：$config['wx_appid'], $config['wx_appsecret']
 * 构造函数中的 config 必须包含：$config['to_wx_uids'] 数组，服务号里面的接收用户
 */
class YAlertorWXPublic extends YAlertor
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
        $this->m_last_err = '';
        $token = $this->getToken();
        if( 1 > strlen($token) )
        {
            return false;
        }

        $url = sprintf( YAlertorWXPublic::URL_ALARM, $token );

        $data = array( 'touser' => $this->m_config['to_wx_uids'],
            'msgtype' => 'text',
            'text' => array('content' => $message)
        );

        $res_ary = $this->postJson( $url, $data );
        if( $res_ary['errcode'] )
        {
            $this->m_last_err = 'YAlertorWXPublic sendAlarm Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return false;
        }

        return true;
    }

    protected function getToken()
    {
        $url = sprintf(YAlertorWXPublic::URL_TOKEN, $this->m_config['wx_appid'], $this->m_config['wx_appsecret']);
        $res = file_get_contents($url);
        $res_ary = json_decode($res, true);
        if( 0 < intval($res_ary['errcode']) || !isset($res_ary['access_token']) )
        {
            $this->m_last_err = 'YAlertorWXPublic getToken Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return '';
        }

        return $res_ary['access_token'];
    }
}

?>