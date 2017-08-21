<?php
/**
 * Token Model
 *
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author gaoxiang <gaoxiang@xiaohe.com>
 */
use Phalcon\Mvc\Model;

class TokenModel extends Model
{
    public function initialize()
    {
        $this->setSource('sc_tokens');     //模型对应的表名
        $this->setReadConnectionService('system_slave');     //从库
        $this->setWriteConnectionService('system_master');   //主库
    }

    /*
     * 验证用户token是否过期
     *
     * @param string $member_id 用户ID
     * @param string $token     token
     *
     * @return bool true=可用 false=过期
     * */
    final public function verifyToken($member_id = '', $token = '')
    {
        if ($member_id == '' || $token == '') {
            return false;
        }

        $token_info = $this->findFirst(array(
            'columns'    => 'token_key, token_val, created, expires',
            'conditions' => 'token_key = :token_key: AND token_val = :token_val:',
            'bind'       => [
                'token_key' => $member_id,
                'token_val' => $token
            ],
        ));

        if (empty($token_info)) {
            return false;
        }

        $exceed_time = $token_info['created'] + $token_info['expires'];

        if ($exceed_time < time()) {
            return false;
        }

        return true;
    }

}
