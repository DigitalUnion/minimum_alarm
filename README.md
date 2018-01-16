# minimum_alarm
一个支持多种消息平台的告警组件。

* 包含的告警平台如下：
  * 微信企业消息
  * 微信公众号中的服务号
  * 钉钉企业消息
  * 邮件
  * 阿里短信平台


## YACenter
主功能入口类

通过 generateAlertor 成员函数生成平台中的相应的报警组件
通过 sendAlarm 成员函数，根据报警级别，分别通过各个组件发送告警信息，根据返回值可以知道哪个消息平台发送失败了


## 开发环境需要安装 composer
```
$ sudo apt install composer
```

## 使用方法
1. 建立工程目录，到工程目录下，编写： composer.json
```
{
    "require": {
        "yyq/minimum_alarm": ">=1.0.0"
    }
}
```

2. 执行命令安装组件
```shell
$ composer install
更新可以执行：
$ composer update
```
如果报错缺：php_xmlrpc  
可以安装：$ sudo apt install php7.0-xmlrpc  
如果被墙，可以按下面的命令使用 composer 国内镜像：  
```shell
$ composer config repo.packagist composer https://packagist.phpcomposer.com
$ composer clearcache
$ composer install
```


3. 编写 test.php
``` php
<?php
require 'vendor/autoload.php';

use minimum_alarm\YACenter;
use minimum_alarm\YAlertor;

// 声明 消息中心 对象
$a_center = new YACenter;

// 生成 email 报警器（已测试通过）
// config 必须包含：$config['mail_host'], $config['mail_username'], $config['mail_password']
// config 可以包含：$config['mail_host_port'], $config['use_ssl']
$a_center->generateAlertor( YACenter::ALERTOR_TYPE_EMAIL, 
    array( 'mail_host' => 'XXXXXXX', 'mail_username' => 'XXXXXX', 'mail_password' => 'XXXXXX', 
        'to_emails' => array('XXXX@sina.com', 'XXXX@qq.com'), 'use_ssl' => 'tls' )
);

// 生成 微信服务号 报警器
// 参数说明：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1481187827_i0l21
// config 必须包含：$config['wx_appid'], $config['wx_appsecret'], $config['to_wx_uids']
$a_center->generateAlertor( YACenter::ALERTOR_TYPE_WX_PUBLIC, 
    array('wx_appid' => 'XXXXXXX', 'wx_appsecret' => 'XXXXXX', 
        'to_wx_uids' => array('XXXXXXXXX', 'XXXXXXXX') ) 
);

// 生成 微信企业 报警器（已测试通过）
// 参数说明：http://qydev.weixin.qq.com/wiki/index.php?title=消息类型及数据格式
// config 必须包含：$config['wx_corpid'], $config['wx_corpsecret'], $config['to_wx_euids']
// config 可以包含：$config['wx_agentid']
$a_center->generateAlertor( YACenter::ALERTOR_TYPE_WX_ENTERPRISE, 
    array('wx_corpid' => 'XXXXXXX', 'wx_corpsecret' => 'XXXXXX', 
        'to_wx_euids' => array('XXXXXXXXX', 'XXXXXXXX') ), 
    YAlertor::ALARM_LEVEL_MIDDLE 
);

// 生成 钉钉企业 报警器（已测试通过）
// 参数说明：https://open-doc.dingtalk.com/docs/doc.htm?spm=a219a.7629140.0.0.5pJaYW&treeId=385&articleId=107549&docType=1
/*
 * 方式一 使用机器人报警
 * 参数说明：https://open-doc.dingtalk.com/docs/doc.htm?spm=a219a.7629140.0.0.hOYIU2&treeId=257&articleId=105735&docType=1#s0
 * config 必须包含：$config['dd_robot_token'], 如果有此参数配置，那么优先使用此方式报警；否则会采用方式二
 * 
 * 方式二 使用指定（消息群） chatid 报警
 * 参数说明：https://open-doc.dingtalk.com/docs/doc.htm?spm=a219a.7629140.0.0.xTrowb&treeId=374&articleId=104977&docType=1#s4
 * config 必须包含：$config['dd_corpid'], $config['dd_corpsecret'], $config['dd_chatid']
 */
$a_center->generateAlertor( YACenter::ALERTOR_TYPE_DINGDING, 
    array('dd_corpid' => 'XXXXXXX', 'dd_corpsecret' => 'XXXXXX', 
        'dd_chatid' => 'chatXXXXXXXXXXXXXXXX' ), 
    YAlertor::ALARM_LEVEL_MIDDLE 
);

// 生成 阿里短信平台 报警器
// 参数说明：https://yq.aliyun.com/articles/59928
// config 必须包含：$config['ali_sign_name'], $config['ali_template_code'], $config['ali_param_name'], $config['ali_appkey'], $config['ali_appsecret'], $config['to_mobiles']
$a_center->generateAlertor( YACenter::ALERTOR_TYPE_ALI_SMS, 
    array('ali_sign_name' => 'XXXXXXX', 'ali_template_code' => 'XXXXXX', 'ali_param_name' => 'XXXXXXX', 
        'ali_appkey' => 'XXXXXX', 'ali_appsecret' => 'XXXXXXX', 
        'to_mobiles' => array('12345678901', '12345678902') ), 
    YAlertor::ALARM_LEVEL_HIGH 
);

// 发送消息
$err_msgs = $a_center->sendAlarm(YAlertor::ALARM_LEVEL_HIGH, '测试消息，请忽略');
if( 1 > count($err_msgs) )
{
    echo '发送成功';
}
else
{
    print_r( $err_msgs );
}

?>
```


## 文件说明
* YACenter.php 主调用接口类实现文件
* YAlertor.php 告警对象抽象类定义文件
* YAlertorEmail.php 邮件告警类实现文件
* YAlertorWXPublic.php 微信服务号告警类实现文件
* YAlertorWXEnterprise.php 微信企业号告警类实现文件
* YAlertorDingDing.php 钉钉企业号告警类实现文件
* YAlertorALiSMS.php 阿里短信平台告警类实现文件

## 问题与解决

如果运行时报：Call to undefined function curl_init() 错误，那是因为没有安装：php-curl
可以执行下面的命令进行安装
$ sudo apt install php-curl 