<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2018-01-10

namespace minimum_alarm;

require_once 'YAlertorEmail.php';
require_once 'YAlertorWXEnterprise.php';
require_once 'YAlertorWXPublic.php';
require_once 'YAlertorDingDing.php';
require_once 'YAlertorALiSMS.php';


class YACenter
{
    const ALERTOR_TYPE_WX_ENTERPRISE = 0;
    const ALERTOR_TYPE_WX_PUBLIC = 1;
    const ALERTOR_TYPE_DINGDING = 2;
    const ALERTOR_TYPE_EMAIL = 3;
    const ALERTOR_TYPE_ALI_SMS = 4;

    /**
     * 生成报警对象
     * ALERTOR_TYPE_WX_ENTERPRISE 类型的 config 必须包含：$config['wx_corpid'], $config['wx_corpsecret'], $config['to_wx_euids']
     * ALERTOR_TYPE_WX_ENTERPRISE 类型的 config 可以包含：$config['wx_agentid'], 不设置默认为 1
     * 
     * ALERTOR_TYPE_WX_PUBLIC 类型的 config 必须包含：$config['wx_appid'], $config['wx_appsecret'], $config['to_wx_uids']
     * 
     * ALERTOR_TYPE_DINGDING 类型的 config 方式一必须包含：$config['dd_robot_token']
     * ALERTOR_TYPE_DINGDING 类型的 config 方式二必须包含：$config['dd_corpid'], $config['dd_corpsecret'], $config['dd_chatid']
     * 
     * ALERTOR_TYPE_EMAIL 类型的 config 必须包含：$config['mail_host'], $config['mail_username'], $config['mail_password']
     * ALERTOR_TYPE_EMAIL 类型的 config 可以包含：$config['mail_host_port'], $config['use_ssl']
     * 
     * ALERTOR_TYPE_ALI_SMS 类型的 config 必须包含：$config['ali_sign_name'], $config['ali_template_code'], $config['ali_param_name'], $config['ali_appkey'], $config['ali_appsecret'], $config['to_mobiles']
     *
     * 
     * $Alarm_level 可选：YAlertor::ALARM_LEVEL_LOW, YAlertor::ALARM_LEVEL_MIDDLE, YAlertor::ALARM_LEVEL_HIGH
     */
    public function generateAlertor( $Alertor_type, $config, $Alarm_level = YAlertor::ALARM_LEVEL_LOW )
    {
        $alertor = null;
        switch($Alertor_type)
        {
            case YACenter::ALERTOR_TYPE_WX_ENTERPRISE:
                $alertor = new YAlertorWXEnterprise( $config, $Alarm_level );
                break;
            case YACenter::ALERTOR_TYPE_WX_PUBLIC:
                $alertor = new YAlertorWXPublic( $config, $Alarm_level );
                break;
            case YACenter::ALERTOR_TYPE_DINGDING:
                $alertor = new YAlertorDingDing( $config, $Alarm_level );
                break;
            case YACenter::ALERTOR_TYPE_EMAIL:
                $alertor = new YAlertorEmail( $config, $Alarm_level );
                break;
            case YACenter::ALERTOR_TYPE_ALI_SMS:
                $alertor = new YAlertorALiSMS( $config, $Alarm_level );
                break;
        }

        if( $alertor )
        {
            $this->m_alertors[] = $alertor;
        }
    }

    /**
     * 发送消息
     * @access public
     * @param int $alarm_level 告警级别：YAlertor::ALARM_LEVEL_LOW, YAlertor::ALARM_LEVEL_MIDDLE, YAlertor::ALARM_LEVEL_HIGH
     * @param string $message 消息体
     * @param string $title 标题
     * @return array 执行过程中发生的错误，如果没有，说明全部执行成功
     */
    public function sendAlarm( $alarm_level, $message, $title = '告警' )
    {
        $this->m_sendErrors = array();

        foreach( $this->m_alertors as $alertor )
        {
            if( $alertor->getAlarmLevel() <= $alarm_level )
            {
                if( !$alertor->sendAlarm( $message, $title ) )
                {
                    $this->m_sendErrors[] = $alertor->getLastError();
                }
            }
        }

        return $this->m_sendErrors;
    }

    protected $m_alertors;
    protected $m_sendErrors;
}

?>