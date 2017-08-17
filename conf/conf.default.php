<?php
/**
 * 配置文件
 * User: 高翔
 * Date: 2017/7/17
 * Time: 13:13
 */
return [
    /**
     * 是否开启监测数据库sql
     * 0=不检测 1=检测
     * @descript 开启监测db，监测日志记录至 /tmp/log/ 目录下execute_sql.log文件
     */
    'LISTEN_DB' => 0,

    'IMG' => 'http://file.baonahao.com.cn', // 文件服务器地址

    // 教务宝试用配置
    'TRIAL_JWB' => [
        'platform_version_ids' => [
            '466345101a29e6633eb9bc7c7bfb1efa',
            '430b89ceae560f1116bb2221098ef991',
            'ddf8fbbd4a8581420a1ea0d825b6ec72',
            'ab620ff96b9926ab17e37c8b3d85641a'
        ], //教务宝版本id
    ],

    // 授权平台安全码
    'AUTH_PLATFORM'     => [
        // 支付中心授权
        '09d5d86558be11e7a7544439c44fda44' => 'ZnbuN#EzSn8uiRzPzHyw1jnnJqMHob7Y+0m%,w5taF4k0gUf4M(V.$DkATFkCTf3'
    ],

    // MQ
    'MQ' => [
        //好队列
        'good' => [
            'host'      => '192.168.1.10',
            'port'      => '5672',
            'username'  => 'good_queue',
            'password'  => '123456',
            'vhostname' => 'baonahao_good_queue',
            'security'  => 'Ax$PfQz[UfYDGJbT)C+^qa%cgj72l!^t',
        ],
        //坏队列
        'bad' =>  [
            'host'      => '192.168.1.10',
            'port'      => '5672',
            'username'  => 'bad_queue',
            'password'  => '123456',
            'vhostname' => 'baonahao_bad_queue',
            'security'  => 'V]so^p0bfvJp4k2DH9f=,)6Lg$*G~]WF',
        ],
    ],
    /* alipay conf*/
    'ALIPAY_PC' => [
        //合作身份者id，以2088开头的16位纯数字
        'partner'      	      => '2088021225876951',
        //安全检验码，以数字和字母组成的32位字符
        'key'          	      => '7mqvav3p6czkyclsug4yjpjshcm0kjor',
        //签约支付宝账号或卖家支付宝帐户
        'seller_email' 	      => 'cw@xiaohe.com',
        //更新充值支付信息
        'recharge_notify_url' => 'http://'.$_SERVER['HTTP_HOST'].'/Notify/alipay_pc', //充值异步返回地址
        //'recharge_notify_url' => 'http://www.fangzhen.baonahao.com/rs.php?', //充值异步返回地址 研发调试时使用
        //签名方式 不需修改
        'sign_type'    	      => 'MD5',
        //字符编码格式 目前支持 gbk 或 utf-8
        'input_charset'	      => 'utf-8',
        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'transport'    	      => 'http',

        // 前端会跳页面
        'return_url'          => 'http://pay.dev.xiaohe.com/Pay/pay_success',
    ],
];
