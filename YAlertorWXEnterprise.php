<?php
namespace minimum_alarm;
require_once 'YAlertor.php';

/**
 * 微信企业号报警
 * 构造函数中的 config 必须包含：$config['wx_corpid']; $config['wx_corpsecret']
 * 构造函数中的 config 必须包含：$config['to_wx_euids'] 数组，企业号里面的接收用户
 * 
 * 构造函数中的 config 可以包含：$config['wx_agentid']; $config['to_wx_epids'] 数组，企业号里面接收部门; $config['to_wx_etags'] 数组，企业号里面的标签
 */
class YAlertorWXEnterprise extends YAlertor
{
    const URL_TOKEN = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=%s&corpsecret=%s";
    const URL_ALARM = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=%s";

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

        $url = sprintf( YAlertorWXEnterprise::URL_ALARM, $token );

        $agent_id = $this->m_config['wx_agentid'] ?? 1;

        $data = array( 'touser' => implode('|', $this->m_config['to_wx_euids']),
            'toparty' => implode('|', $this->m_config['to_wx_epids'] ?? array() ),
            'totag' => implode('|', $this->m_config['to_wx_etags'] ?? array() ),
            'msgtype' => 'text', 
            'agentid' => $agent_id, 
            'safe' => 0,
            'text' => array('content' => $message)
        );

        $res_ary = $this->postJson($url, $data);
        if( 0 < intval($res_ary['errcode']) )
        {
            $this->m_last_err = 'YAlertorWXEnterprise sendAlarm Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return false;
        }

        $invalid_users = $res_ary['invaliduser'] ?? '';
        if( 0 < strlen($invalid_users) )
        {
            $this->m_last_err .= 'YAlertorWXEnterprise sendAlarm OK! invalid_users = ' . $res_ary['invaliduser'] . "\n";
        }

        $invalid_party = $res_ary['invalidparty'] ?? '';
        if( 0 < strlen($invalid_party) )
        {
            $this->m_last_err .= 'YAlertorWXEnterprise sendAlarm OK! invalid_party = ' . $res_ary['invalidparty'] . "\n";
        }

        $invalid_tag = $res_ary['invalidtag'] ?? '';
        if( 0 < strlen($invalid_tag) )
        {
            $this->m_last_err .= 'YAlertorWXEnterprise sendAlarm OK! invalid_tag = ' . $res_ary['invalidtag'] . "\n";
        }

        return true;
    }

    protected function getToken()
    {
        $url = sprintf(YAlertorWXEnterprise::URL_TOKEN, $this->m_config['wx_corpid'], $this->m_config['wx_corpsecret']);
        $res = file_get_contents($url);
        $res_ary = json_decode($res, true);
        if( 0 < intval($res_ary['errcode']) || !isset($res_ary['access_token']) )
        {
            $this->m_last_err = 'YAlertorWXEnterprise getToken Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return '';
        }

        return $res_ary['access_token'];
    }
}

?>