<?php

/**
 * Controller文件夹父类
 * User: 高翔
 * Date: 2017/7/13
 * Time: 19:43
 */

class AppController extends \Phalcon\Mvc\Controller
{
    protected $args; // 请求参数

    /*
     * 构造方法
     * */
    public function onConstruct()
    {
        $this->args = $_REQUEST;
        // 入口日志
        DLOG('request:'.json_encode($this->args));

        if (C('IS_SIGN')) {
            $this->verifySign();
        }

        if (C('IS_TOKEN')) {
            $this->verifyToken();
        }    
    }

    /**
     * 验证签名
     *
     */
    protected function verifySign()
    {
        $data = getArrVal($this->args, 'data', []);
        $args = getArrVal($this->args, 'keys', []);
        if (empty($data)) {
            dataReturn(false, 'API_COMM_002');
        }
        if (empty($args)) {
            dataReturn(false, 'API_COMM_002');
        }
        $security_code = C('AUTH_PLATFORM.'.$args['packey']);
        $verify_code = $this->generateSign($data, $security_code);
        if ($verify_code !== $this->args['keys']['data_sign']) {
            dataReturn(false, 'API_COMM_002');
        }
    }

    /**
     * 生成数据签名
     *
     * @param array $param 参数
     * @param string $code 安全码
     *
     * @return string
     *
     * @author gaoxiang <gaoxiang@xiaohe.com>
     */
    private function generateSign($param, $code)
    {
        $param_str = json_encode($param);
        $param_str = $param_str . $code;

        $sign = md5($param_str);
        $sign = sha1($sign);
        $sign = md5($sign);
        $sign = sha1($sign);
        $sign = substr($sign, 4, 32);

        return $sign;
    }

    /**
     * 验证token是否过期
     *
     * @param args keys
     *
     * @return bool
     */
    private function verifyToken()
    {
        $keys = getArrVal($this->args, 'keys');

        $token_model = new TokenModel();
        $result = $token_model->verifyToken($keys['token_key'], $keys['token_val']);

        if (!$result) {
            dataReturn(false, 'API_COMM_003');
        }
    }
}
