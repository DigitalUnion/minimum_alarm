<?php
namespace minimum_alarm;

abstract class YAlertor
{
    const ALARM_LEVEL_LOW = 0;
    const ALARM_LEVEL_MIDDLE = 1;
    const ALARM_LEVEL_HIGH = 2;

    public function __construct( $config, $alarm_level = YAlertor::ALARM_LEVEL_LOW )
	{
        $this->m_config = $config;
        $this->m_last_err = '';
        $this->setAlarmLevel( $alarm_level );
    }

    public function __destruct()
    {
    }

    abstract public function sendAlarm( $message, $title );

    public function getLastError(){ return $this->m_last_err; }

    public function getAlarmLevel(){ return $this->m_alarm_level; }

    public function setAlarmLevel( $alarm_level ){ $this->m_alarm_level = $alarm_level; }
    
    /**
     * 以 POST 方式发送数据到指定 URL 地址，并获取返回值
     * @access protected
     * @param string $url
     * @param array $data 需要提交的数据
     * @return array 把网站返回的 json 数据转为数组返回
     */
    protected function postJson( $url, $data)
    {
        // $post_data = http_build_query($data);    // “Content-type: application/x-www-form-urlencodedrn”
        $post_data = json_encode($data, JSON_UNESCAPED_UNICODE);

        $opts = array (
            'http' => array (
            'method' => 'POST',
            'header' => "Content-type: application/json\r\n" . 'Content-Length: ' . strlen($post_data) . "\r\n",
            'content' => $post_data
            )
        );

        $context = stream_context_create($opts);
        $res = file_get_contents($url, false, $context);
        return json_decode($res, true);
    }

    protected $m_config;
    protected $m_last_err;
    protected $m_alarm_level;
}


?>