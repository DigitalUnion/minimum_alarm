<?php
namespace minimum_alarm;
require_once 'vendor/autoload.php';
require_once 'YAlertor.php';


/**
 * 微信公众号报警。根据 OpenID 列表群发。订阅号不可用，服务号认证后可用。
 * 构造函数中的 config 必须包含：$config['mail_host'], $config['mail_username'], $config['mail_password']
 * 构造函数中的 config 必须包含：$config['to_emails'] 数组，接收者的 邮件地址
 * 
 * 构造函数中的 config 可以包含：$config['mail_host_port'] int 默认为 25, $config['use_ssl'] string 默认为空
 */
class YAlertorEmail extends YAlertor
{
    /**
     * 发送消息
     * @access public
     * @param string $message 邮件内容
     * @param string $title 邮件标题
     * @return bool
     */
    public function sendAlarm( $message, $title )
    {
        $this->m_last_err = '';
        $host_port = $this->m_config['mail_host_port'] ?? 25;

        // Create the Transport
        $transport = (new \Swift_SmtpTransport($this->m_config['mail_host'], $host_port, $this->m_config['use_ssl']))
            ->setUsername($this->m_config['mail_username'])
            ->setPassword($this->m_config['mail_password'])
        ;

        // Create the Mailer using your created Transport
        $mailer = new \Swift_Mailer($transport);

        // Create a message
        $message = (new \Swift_Message($title))
            ->setFrom( [$this->m_config['mail_username']] )
            ->setTo( $this->m_config['to_emails'] )
            ->setBody( $message )
        ;

        // Send the message
        $result = $mailer->send($message);

        if( 1 > intval($result) )
        {
            $this->m_last_err = 'YAlertorEmail sendAlarm Fail!';
            return false;
        }

        return true;
    }

    public function sendAlarm_UseMySendMail( $message, $title )
    {
        print_r($this->m_config);
        $host_port = 25;
        if( isset($this->m_config['mail_host_port']) )
        {
            $host_port = intval($this->m_config['mail_host_port']);
        }

        $use_ssl = false;
        if( isset($this->m_config['use_ssl']) )
        {
            $use_ssl = $this->m_config['use_ssl'];
        }

        include_once("./lib/MySendMail.php");
        $mail = new \MySendMail();
        //$mail->setServer("smtp.163.com", "baiyun5051@163.com", "75775897", 465, true); //到服务器的SSL连接
        //如果不需要到服务器的SSL连接，这样设置服务器：$mail->setServer("smtp.126.com", "XXX@126.com", "XXX")
        $mail->setServer( $this->m_config['mail_host'], $this->m_config['mail_username'], $this->m_config['mail_password'], $host_port, $use_ssl ); //到服务器的SSL连接
        $mail->setFrom( $this->m_config['mail_username'] );
        foreach( $this->m_config['to_emails'] as $to )
        {
            echo $to . "\n";
            $mail->setReceiver( $to );
        }
        
        echo $title, $message, "\n";
        $mail->setMail( $title, $message );
        if( !$mail->sendMail() )
        {
            $this->m_last_err = 'YAlertorEmail sendAlarm Fail! message = ' . $message;
            return false;
        }

        return true;
    }
}

?>