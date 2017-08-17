<?php
/**
 * 数据库连接池
 *
 * User: 高翔
 * Date: 2017/7/13
 * Time: 13:21
 */
use Phalcon\Config\Adapter\Ini as ConfigIni;

$config = new ConfigIni(ROOT. 'conf'. DIRECTORY_SEPARATOR .'db.ini');

//系统中心主库配置
$di->setShared('business_master', function() use ($config) {
    return connectDb($config, 'business', 'master');
});

//系统中心从库配置
$di->setShared('business_slave', function() use ($config) {
    return connectDb($config, 'business', 'slave');
});

//财务中心主库配置
$di->setShared('finance_master', function() use ($config) {
    return connectDb($config, 'finance', 'master');
});

//财务中心从库配置
$di->setShared('finance_slave', function() use ($config) {
    return connectDb($config, 'finance', 'slave');
});

//用户中心主库配置
$di->setShared('member_master', function() use ($config) {
    return connectDb($config, 'member', 'master');
});

//用户中心从库配置
$di->setShared('member_slave', function() use ($config) {
    return connectDb($config, 'member', 'slave');
});

//系统中心主库配置
$di->setShared('system_master', function() use ($config) {
    return connectDb($config, 'system', 'master');
});

//系统中心从库配置
$di->setShared('system_slave', function() use ($config) {
    return connectDb($config, 'system', 'slave');
});

function connectDb($db_config, $db_name, $db_type)
{
    $db_name   = sprintf('%s_center', $db_name);

    if ($db_type == 'master') {
        $db_node   = $db_name.'_master';

        $db_config = $db_config->$db_node;
    } else {
        $db_node   = $db_name.'_slave';
        $db_config = $db_config->$db_node;
    }

    $events_manager = new Phalcon\Events\Manager();


    //Listen all the database events
    if (C('LISTEN_DB')) {
        $profiler = new Phalcon\Db\Profiler();
        $events_manager->attach('db', function($event, $connection) use ($profiler) {
            //一条语句查询之前事件，profiler开始记录sql语句
            if ($event->getType() == 'beforeQuery') {
                $profiler->startProfile($connection->getSQLStatement());
            }
            //一条语句查询结束，结束本次记录，记录结果会保存在profiler对象中
            if ($event->getType() == 'afterQuery') {
                $profiler->stopProfile();

                $profile = $profiler -> getLastProfile();
                $sql = $profile->getSQLStatement();
                $executeTime = $profile->getTotalElapsedSeconds();
                DLOG('执行时间:'.$executeTime.' SQL语句:'.$sql, 'INFO', 'execute_sql.log');
            }
        });
    }

    $connection     = new Phalcon\Db\Adapter\Pdo\Mysql(
        array(
            'host'      =>  $db_config->host,
            'port'      =>  $db_config->port,
            'username'  =>  $db_config->username,
            'password'  =>  $db_config->password,
            'dbname'    =>  $db_config->dbname,
        )
    );

    $connection->setEventsManager($events_manager);

    return $connection;
}
