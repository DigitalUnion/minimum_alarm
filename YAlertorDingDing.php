<?php
namespace minimum_alarm;

require_once 'YAlertor.php';

/**
 * 钉钉企业号报警
 * 如果配置中 $config['dd_robot_token'] 有值，那么首先使用方式一报警
 * 如果配置中没有 $config['dd_robot_token'] 而有 $config['dd_chatid']，那么使用方式二报警
 * 如果上面两个都没有，那么使用方式三报警
 * 
 * 方式一 使用机器人报警。此方法已测试通过
 * 构造函数中的 config 必须包含：$config['dd_robot_token']
 * 
 * 
 * 方式二 使用指定（消息群） chatid 报警。此方法已测试通过
 * 构造函数中的 config 必须包含：$config['dd_corpid'], $config['dd_corpsecret']
 * 构造函数中的 config 必须包含：$config['dd_chatid'] string, 接收群id
 * 
 * 
 * 方式三 使用淘宝企业消息报警。测试未通过
 * 构造函数中的 config 必须包含：$config['dd_corpid'], $config['dd_corpsecret']
 * 构造函数中的 config 必须包含：$config['to_dd_uids'] array，接收者的钉钉id号，钉钉限制最多 20 个
 * 
 * 构造函数中的 config 可以包含：$config['dd_agentid'], $config['to_dd_pids'] array，接收部门的钉钉id号，钉钉限制最多 20 个
 */
class YAlertorDingDing extends YAlertor
{
    const URL_TOKEN = "https://oapi.dingtalk.com/gettoken?corpid=%s&corpsecret=%s";
    const URL_ALARM = "https://oapi.dingtalk.com/chat/send?access_token=%s";

    /**
     * 发送消息。
     * @access public
     * @param string $message 消息体
     * @param string $title 标题
     * @return bool
     */
    public function sendAlarm( $message, $title )
    {
        $this->m_last_err = '';
        if( isset($this->m_config['dd_robot_token']) && 0 < strlen($this->m_config['dd_robot_token']) )
        {
            return $this->sendAlarmToRobot( $message, $title );
        }
        else if( isset($this->m_config['dd_chatid']) && 0 < strlen($this->m_config['dd_chatid']) )
        {
            return $this->sendAlarmToRobot( $message, $title );
        }
        else
        {
            return $this->sendAlarmToUsers( $message, $title );
        }
    }

    public function sendAlarmToRobot( $message, $title )
    {
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=' . $this->m_config['dd_robot_token'];
        echo 'url = ' . $url . "\n";

        $data = array( 'msgtype' => 'markdown',
            'markdown' => array('title' => $title, 'text' => $message)
        );

        $res_ary = $this->postJson($url, $data);
        print_r($res_ary);
    }

    public function sendAlarmToUsers( $message, $title )
    {
        $token = $this->getToken();
        if( 1 > strlen($token) )
        {
            return false; 
        }

        $agent_id = $this->m_config['dd_agentid'] ?? 1;

        $data = array( 'method' => 'dingtalk.corp.message.corpconversation.asyncsend', // 'API接口名称',
            'session' => $token,
            'timestamp' => date('Y-m-d H:i:s', time()),
            'format' => 'json',
            'simplify' => true,
            'v' => '2.0',
            'msgtype' => 'text',
            'agent_id' => $agent_id,
            'userid_list' => $this->m_config['to_dd_uids'],
            'dept_id_list' => $this->m_config['to_dd_pids'] ?? array(),
            'msgcontent ' => array( 'message_url' => 'http://dingtalk.com', 
                'body' => array( 'title' => $title, 'content' => $message )
            ),
        );

        $res_ary = $this->postJson('https://eco.taobao.com/router/rest', $data);
        print_r($res_ary);

        echo "ding_open_errcode = ", $res_ary['ding_open_errcode'] , "\n";
        if( $res_ary['ding_open_errcode'] )
        {
            $this->m_last_err = 'YAlertorDingDing sendAlarm Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['error_msg'];
            return false;
        }

        // 因是异步发送，返回此任务的 id 在 $res_ary['task_id']，后面可通过此 id 查看消息阅读状态

        return true;
    }

    /**
     * 发送消息。钉钉消息只能发到一个群里。一般的步骤是可以选择多个人创建一个群，然后把这个群信息保存到自己的数据库，
     * 下次有同样的接收者时，从数据库里把 群ID 检索出来，然后调用这个 sendAlarm 发送。
     * 建群的代码可以参看：createGroup()
     * @access public
     * @param string $message 消息体
     * @param string $chatid 由于钉钉消息的特殊性，消息只能发到一个群里
     * @return bool
     */
    public function sendAlarmToOneChat( $message, $title )
    {
        $token = $this->getToken();
        if( 1 > strlen($token) )
        {
            return false; 
        }

        $url = sprintf(YAlertorDingDing::URL_ALARM, $token);

        $data = array( 'chatid' => $this->m_config['dd_chatid'],
            'msgtype' => 'action_card',
            'action_card' => array( 'title' => $title, 'markdown' => $message )
        );

        $res_ary = $this->postJson($url, $data);
        if( $res_ary['errcode'] )
        {
            $this->m_last_err = 'YAlertorDingDing sendAlarm Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return false;
        }

        return true;
    }

    /**
     * 创建群
     * @access public
     * @param string $owner_user_id 群主的 userid
     * @param array $user_id_list 群成员 userid 数组
     * @return string 返回的 chatid，空字符串 '' 表示创建失败 
     */
    public function createGroup( $owner_user_id, $user_id_list )
    {
        $token = $this->getToken();
        if( 1 > strlen($token) )
        {
            return ''; 
        }

        $url = sprintf( 'https://oapi.dingtalk.com/chat/create?access_token=%s', $token );

        $data = array( 'name' => '报警通知群',
            'owner' => $owner_user_id,
            'useridlist' => $user_id_list
        );

        $res_ary = $this->postJson( $url, $data );
        if( $res_ary['errcode'] )
        {
            $this->m_last_err = 'YAlertorDingDing createGroup Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return '';
        }

        return $res_ary['chatid'];
    }

    protected function getToken()
    {
        $url = sprintf(YAlertorDingDing::URL_TOKEN, $this->m_config['dd_corpid'], $this->m_config['dd_corpsecret']);
        $res = file_get_contents($url);
        $res_ary = json_decode($res, true);
        if( 0 < intval($res_ary['errcode']) || !isset($res_ary['access_token']) )
        {
            $this->m_last_err = 'YAlertorDingDing getToken Fail! errcode = ' . $res_ary['errcode'] . ' errmsg = ' . $res_ary['errmsg'];
            return '';
        }

        return $res_ary['access_token'];
    }
}

?>