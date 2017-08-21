<?php
/**
 * 演示文件
 *
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author gaoxiang <gaoxiang@xiaohe.com>
 */
class IndexController extends AppController
{
    /**
     * 测试方法
     * @return array 测试数据
     */
    public function test()
    {
        /**
         * 获取参数中data参数
         */
        $data = getArrVal($this->args, 'data');

        /**
         * 获取参数中keys参数
         */
        $args = getArrVal($this->args, 'keys');

        /**
         * 实例逻辑层类
         */
        $logic  = new IndexLogic();

        $result = $logic->test([
            'uname'  => 'gaox',
            'email'  => 'gaoxiang@xiaohe.com',
            'remark' => '这是一个测试'
        ]);

        dataReturn(true, 'API_COMM_001', array_merge((array)$result, $this->args));
    }
}
