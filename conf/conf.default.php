<?php
/**
 * 主配置文件
 *
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author gaoxiang <gaoxiang@xiaohe.com>
 */
return [
    /**
     * 是否开启监测数据库sql
     * @var false=不监测 true=监测
     * @descript 开启监测db，监测日志记录至 /tmp/log/ 目录下execute_sql.log文件
     */
    'LISTEN_DB' => false,

    /**
     * 是否开启token验证
     * @var false=不开启 true=开启
     * @descript 默认不开启
     */
    'IS_TOKEN' => false,

    /**
     * 是否开始签名验证
     * @var false=不开启 true=开启
     * @descript 默认开启
     */
    'IS_SIGN'  => true,

    /**
     * 平台授权码
     * @var 平台key => 安全码
     * @descript 开启签名验证时生效
     */
    'AUTH_PLATFORM'     => [
        '09d5d86558be11e7a7544439c44fda44' => 'ZnbuN#EzSn8uiRzPzHyw1jnnJqMHob7Y+0m%,w5taF4k0gUf4M(V.$DkATFkCTf3'
    ],
];
