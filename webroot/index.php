<?php
/**
 * 入口文件
 * User: 高翔
 * Date: 2017/7/12
 * Time: 19:16
 */
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;

try {
    $di = new FactoryDefault();

    // 常量
    define('ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

    // 公共函数文件
    require ROOT . 'common/function.php';

    // 数据库连接池
    require ROOT . 'conf/dbpool.php';

    $loader = new Phalcon\Loader();
    $loader->registerDirs([
        // 控制器层
        ROOT . 'app/Controller/',
        // 业务逻辑层
        ROOT . 'app/Logic/',
        // 模型层
        ROOT . 'app/Model/',
        // 公共函数文件夹
        ROOT . 'common/',
        // 第三方插件文件夹
        // 配置文件夹
        ROOT . 'conf'
    ])->register();

    if (empty($_REQUEST)) {
        dataReturn(false, 'API_COMM_404');
    }

    //阻止应用重复请求接口
    try {
        $redis = connRedis();
        $redis->select(5);

        $cache_key = md5(json_encode($_REQUEST));
        if ($redis->exists($cache_key)) {
            for ($i=0; $i<20; $i++) {
                if (!$redis->exists($cache_key)) {
                    break;
                }

                $cacheData = $redis->get($cache_key);
                if (!empty($cacheData) && ($cacheData != 'none')) {
                    if ($redis->ttl($cache_key) < 0) {
                        $redis->delete($cache_key);
                    }
                    exit($cacheData);
                }
                usleep(100000);
            }
        } else {
            $redis->setex($cache_key, 10, 'none');
        }
    } catch (\Exception $ex) {
        DLOG($ex->getMessage(), 'ERROR', 'redis.log');
    }

    $app = new Micro();

    $uri_arr    = explode("/", trim($_SERVER['REQUEST_URI']));
    $action     = ucfirst($uri_arr[count($uri_arr) - 2]);
    $func       = $uri_arr[count($uri_arr) - 1];
    $controller = $action . 'Controller';

    //  对象内的方法
    $myController = new $controller();
    $app->post("/$action/$func", [$myController, $func]);

    /**
     * 示例 增加一个get访问方法
     * @code $app->get("/Index/test", [new IndexController(), 'test']);
     */

    // 404
    $app->notFound(function () use ($app) {
    //    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
        dataReturn(false, 'API_COMM_404');
    });

    $app->handle();
} catch (\Exception $e) {
    // 异常处理
    // echo "PhalconException: ", $e->getMessage();
    $err_msg = sprintf('File:%s Line:%s Info:%s', $e->getFile(), $e->getLine(), $e->getMessage());
    DLOG($err_msg, 'ERROR', 'exception.log');
    dataReturn(false, 'API_COMM_004', $e->getMessage());
}
