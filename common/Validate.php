<?php
/**
 * 共用验证
 *
 * PHP versions 5.3
 *
 * @copyright Copyright 2012-2016, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @package api
 * @subpackage api/libs
 * @date 2016-03-21 10:13
 * @author wangjunjie <wangjunjie@xiaohe.com>
 */
class Validate {
    /**
     * 被验证数据
     *
     * @var array
     */
    private static $data  = array();

    /**
     * 被验证的字段
     *
     * @var string
     */
    private static $field = '';

    /**
     * 验证规则
     *
     * @var array
     */
    private static $rule  = array();

    /**
     * 正则表达式
     *
     * @var array
     */
    private static $patterns = array(
        'email'            => "/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", //邮箱
        'url'              => "/((^http)|(^https)|(^ftp)):\/\/([A-Za-z0-9]+\.[A-Za-z0-9]+)+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/", //URL
        'english'          => "/^[a-zA-Z]*$/", //英文
        'chinese'          => "/^[\x{4e00}-\x{9fa5}]+$/u", //中文
        'tel'              => "/(^[0-9]{3,4}\-[0-9]{7,8}$)|(^[0-9]{7,8}$)|(^\([0-9]{3,4}\)[0-9]{3,8}$)|(^0{0,1}13[0-9]{9}$)|(13\d{9}$)|(15[0135-9]\d{8}$)|(18[267]\d{8}$)/", //固话
        'mobile'           => "/^1[0-9]{10}$/", //手机
        'id_card'          => "/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/", //身份证号
        'money'            => "/^(0|[1-9]\d*)(\.\d{1,2})?$/", //金额
        'pos_int'          => "/^[1-9][0-9]*$/", //正整数
        'non_negative_int' => "/^(0|[1-9]\d*)*$/", //非负整数
        'year'             => "/^(?!0000)[0-9]{4}$/", //年
        'date'             => "/^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$/", //日期
        'ip'               => "/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d))))$/", //IP
    );

    /**
     * 验证一组数据
     *
     * ----------------------------------------------------------
     * @access public
     * ----------------------------------------------------------
     * @param  array $data  数据
     * @param  array $rules 验证规则
     * @param  string $args 公共参数
     * ----------------------------------------------------------
     * @return array
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 10:13
     */
    public static function data($data, $rules, $args){
        extract($args);
        self::$data = $data;
        foreach ($rules as $field => $rule) {
            self::$field = $field;
            foreach ($rule as $key => $val) {
                self::$rule = $val;
                $method = 'vd' . ucfirst($val['method']);
                if (!self::$method()) {
                    dataReturn(false, self::$rule['msg'], null);
                }
            }
        }
        return true;
    }

    /**
     * 验证令牌
     *
     * ----------------------------------------------------------
     * @access public
     * ----------------------------------------------------------
     * @param array $data 请求数据
     * @param array $token 令牌配置信息
     * @param array $args 公共参数
     * ----------------------------------------------------------
     * @return void or true
     * ----------------------------------------------------------
     * @author biguangfu <biguangfu@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-04-26 15:51
     * ----------------------------------------------------------
     */
//    public static function token($data, $token, $args) {
//        extract($args);
//        $condition = array(
//            'packey'    => $data['keys']['packey'],
//            'token_key' => isset($data['keys']['token_key']) ? addslashes(trim($data['keys']['token_key'])) : '',
//            'token_val' => isset($data['keys']['token_val']) ? addslashes(trim($data['keys']['token_val'])) : '',
//            //'now'       => time(),
//        );
//        $error_code = new Phalcon\Config(require sprintf('%stipmsg.php', CONFIG_PATH));
//        if (empty($condition['packey']) || empty($condition['token_key']) || empty($condition['token_val'])) {
//            dataReturn(false, 'API_COMM_007', null);
//        }
//        $model      = new \System\Token();
//        $token_data = $model->getToken($condition);
//        if (empty($token_data)) {
//            dataReturn(false, 'API_COMM_007', null);
//        }
//        if ($token_data['expires'] < time()) {
//            dataReturn(false, 'API_COMM_008', null);
//        }
//        return true;
//    }

    /**
     * 验证某个字段
     *
     * ----------------------------------------------------------
     * @access public
     * ----------------------------------------------------------
     * @param  string $method 方法
     * @param  string $field  字段
     * @param  mixed  $data   数据
     * @param  mixed  $rule   规则
     * ----------------------------------------------------------
     * @return boolean true成功，false失败
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-08-10 14:54
     */
    public static function field($method, $field, $data, $rule = null){
        self::$data  = array($field => $data);
        self::$field = $field;
        self::$rule  = $rule;
        $method = 'vd' . ucfirst($method);
        return self::$method();
    }

    /**
     * 验证验证码
     *
     * ----------------------------------------------------------
     * @access public
     * ----------------------------------------------------------
     * @param  string $phone 手机号
     * @param  string $code  验证码
     * @param  array  $args  公共参数
     * ----------------------------------------------------------
     * @return boolean true成功，false失败
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-08-10 14:54
     */
//    public static function verifyCode($phone, $code, $args){
//        $data_type = getArrVal($args, 'data_type', 'json');
//        $model  = \Component::gmo('Business', 'MerchantSmsConsume');
//        $result = $model->findFirst(array(
//            'columns'    => 'phone, verify_code, expired, created',
//            'conditions' => "phone='{$phone}' AND verify_code='{$code}'",
//        ));
//        if (empty($result)) {
//            dataReturn(false, 'API_COMM_016', null, $data_type);
//        }
//        $result = $result->toArray();
//        //判断验证码是否过期
//        if (floor((time() - strtotime($result['created']))%86400/60) > $result['expired']) {
//            dataReturn(false, 'API_COMM_017', null, $data_type);
//        }
//        return true;
//    }

    /**
     * 获取字段值
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return mixed
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 13:02
     */
    private static function getVal(){
        $val = '';
        if (isset(self::$data[self::$field])) {
            $val = self::$data[self::$field];
        }
        return $val;
    }

    /**
     * 设置验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 10:44
     */
    private static function vdIsset(){
        return isset(self::$data[self::$field]);
    }

    /**
     * 空验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 10:44
     */
    private static function vdEmpty(){
        $val = self::getVal();
        return (boolean)(is_array($val) ? $val : strlen($val));
    }

    /**
     * 长度验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 11:52
     */
    private static function vdLength(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        $len = strlen($val);
        $res = true;
        if (isset(self::$rule['min'])) {
            $res = $res && ($len>=self::$rule['min']);
        }
        if (isset(self::$rule['max'])) {
            $res = $res && ($len<=self::$rule['max']);
        }
        if (isset(self::$rule['equ'])) {
            $res = $res && ($len==self::$rule['equ']);
        }
        return $res;
    }

    /**
     * 邮箱验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdEmail(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['email'], $val);
    }

    /**
     * URL验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdUrl(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['url'], $val);
    }

    /**
     * 英文验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdEnglish(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['english'], $val);
    }

    /**
     * 中文验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdChinese(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['chinese'], $val);
    }

    /**
     * 固话验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdTel(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['tel'], $val);
    }

    /**
     * 手机验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdMobile(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['mobile'], $val);
    }

    /**
     * 电话验证(固话/手机)
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdPhone(){
        return (self::vdTel() || self::vdMobile());
    }

    /**
     * 身份证号验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdIdCard(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['id_card'], $val);
    }

    /**
     * 金额验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdMoney(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['money'], $val);
    }

    /**
     * 正整数验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdPosInt(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['pos_int'], $val);
    }

    /**
     * 非负整数验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-10-10 21:01
     */
    private static function vdNonNegativeInt(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['non_negative_int'], $val);
    }

    /**
     * 年份验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-06-08 14:26
     */
    private static function vdYear(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['year'], $val);
    }

    /**
     * 日期验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-06-09 15:40
     */
    private static function vdDate(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['date'], $val);
    }

    /**
     * ip验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-08-23 19:34
     */
    private static function vdIp(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$patterns['ip'], $val);
    }

    /**
     * 自定义正则验证
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-03-21 16:00
     */
    private static function vdRegExp(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return preg_match(self::$rule['pattern'], $val);
    }

    /**
     * 验证字段值是否在一个数组中
     *
     * ----------------------------------------------------------
     * @access private
     * ----------------------------------------------------------
     * @return boolean
     * ----------------------------------------------------------
     * @author wangjunjie <wangjunjie@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2016-05-12 11:01
     */
    private static function vdInArray(){
        $val = self::getVal();
        if (!strlen($val)) {
            return true;
        }
        return in_array($val, self::$rule['arr']);
    }
}
