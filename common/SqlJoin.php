<?php
/**
 * sql語句拼接
 *
 * PHP versions 5.6
 *
 * @copyright Copyright 2012-2016, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @package api
 * @subpackage api/libs
 * @date 2016-05-05 22:24
 * @author gaoxiang <gaoxiang@xiaohe.com>
 */

/*
 * 调用例子
 * $SqlJoin = new SqlJoin();
 * $arrField = ['id', 'merchant_id', 'platform_id', 'name', 'type_id', 'cost'];

 * $where['cost'] = 0;
 * $where['id'] = array('!=', '');

 * $SqlJoin->table('gc_goods')->where($where)->field($arrField)->order('cost desc')->group('id')->limit('0,10')->sql('select');
 * echo $SqlJoin->prevSql();
 * $SqlJoin->table('gc_goods as gd')->join('LEFT JOIN gc_goods_imgs as gm ON gd.id=gm.goods_id')->field('gd.id,gd.merchant_id,gm.id,gm.url')->limit(10)->sql('select');
 */
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class SqlJoin
{
    /**
     * 关键字
     * @var [array]
     */
    protected $options  = [
        'table' => '',
        'field' => '*',
        'where' => '',
        'order' => '',
        'group' => '',
        'join'  => '',
        'limit' => ''
    ];

    protected $chars = ['in', 'not in', 'like'];
    /**
     * 上条拼接sql
     * @var string
     */
    protected $prev_sql = '';

    /**
     * 重置sql关键字
     *
     * ----------------------------------------------------------
     * @param  空
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return 空
     */
    private function resetOption()
    {
        $this->options = [
            'table' => '',
            'field' => '*',
            'where' => '',
            'order' => '',
            'group' => '',
            'join'  => '',
            'limit' => ''
        ];
    }

    /**
     * 返回上一条拼接的sql
     *
     * ----------------------------------------------------------
     * @param  空
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return string
     */
    public function prevSql()
    {
        return $this->prev_sql;
    }

    /**
     * 设置需要查询的表
     *
     * ----------------------------------------------------------
     * @param  string table 表名称
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function table($table = '')
    {
        $this->options['table'] = $table;

        return $this;
    }

    /**
     * 设置排序
     *
     * ----------------------------------------------------------
     * @param  string $order 排序字符串
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function order($order = '')
    {
        $this->options['order'] = $order != '' ? ' order by '.$order : '';

        return $this;
    }

    /**
     * 分组字符串
     *
     * ----------------------------------------------------------
     * @param  string $group 排序字符串
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function group($group = '')
    {
        $this->options['group'] = $group != '' ? ' group by '.$group : '';

        return $this;
    }

    /**
     * 查询几条数据 limit
     *
     * ----------------------------------------------------------
     * @param  string $limit 排序字符串 默认10
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function limit($limit = '10')
    {
        $this->options['limit'] = ' limit '.$limit;

        return $this;
    }

    /**
     * 查询条件
     *
     * ----------------------------------------------------------
     * @param  string or array $where
     * string=查询条件字符串
     * array('字段名字'=>'匹配值'); array('字段名'=>array('表达式'=>'匹配值'))
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function where($where = '')
    {
        if (is_array($where)) {
            $new_where = 'WHERE ';
            foreach ($where as $key => $value) {
                if (is_array($value) && !is_array($value[0])) {
                    if (in_array(strtolower($value[0]), $this->chars) && strtolower($value[0]) == 'in') {
                        $new_where .= "{$key} {$value[0]} ({$value[1]}) AND ";
                    }else if(in_array(strtolower($value[0]), $this->chars) && strtolower($value[0]) == 'like'){
                        $new_where .= "{$key} {$value[0]} '{$value[1]}' AND ";
                    }else{
                        $tmp_arr = explode('.', $value[1]);
                        if (count($tmp_arr) > 1) {
                            $val = $value[1];
                        }else{
                            $val = is_string($value[1])?"'".$value[1]."'":$value[1];
                        }
                        $new_where .= "{$key}".$value[0].$val.' AND ';
                    }
                }

                if (is_array($value) && is_array($value[0])) {
                    foreach ($value as $k => $v) {
                        $new_where .= "{$key}{$v[0]}'{$v[1]}' AND ";
                    }
                }

                if (!is_array($value)){
                    $val = is_string($value) ? "'".$value."'" : $value;
                    $new_where .= "$key".'='.$val.' AND ';
                }

            }
            $new_where = substr($new_where, 0, -5);

            $this->options['where'] = ' '.$new_where.' ';
        } else {
            $this->options['where'] = $where!=''?' WHERE '.$where:'';
        }

        return $this;
    }

    /**
     * 需要查询的字段名字
     *
     * ----------------------------------------------------------
     * @param  string or array $field
     * string=查询字段字符串
     * array('字段名1','字段名2');
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function field($field = '*')
    {
        switch (gettype($field)) {
            case 'string':
                break;
            case 'array':
                $new_field = '';
                foreach ($field as $key => $value) {
                    $new_field .= "`{$value}`,";
                }
                $field = substr($new_field, 0, -1);
                break;
            default:
                $field = '*';
                break;
        }

        $this->options['field'] = $field;

        return $this;
    }

    /**
     * 链接表字符串
     *
     * ----------------------------------------------------------
     * @param  string $join
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return $this
     */
    public function join($join = '')
    {
        if ($join != '') {
            $this->options['join'] .= ' '.$join.' ';
        }
        
        return $this;
    }

    /**
     * 拼接查询语句
     *
     * ----------------------------------------------------------
     * @param  string $type=sql方式（select、update、insert、delete）默认select
     * @param array $data update和insert传入，需要修改或者新增的数组 key=操作字段 value=操作值
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-16 16:00
     * ----------------------------------------------------------
     * @return string $sql
     */
    public function sql($type='select', $data=array())
    {
        $type = strtolower($type);
        $sql = '';
        if ($type == 'select') {
            $sql .= "SELECT {$this->options['field']} FROM {$this->options['table']}{$this->options['join']}{$this->options['where']}{$this->options['group']}{$this->options['order']} {$this->options['limit']}";
            $this->prev_sql = $sql;
        }
        if ($type == 'update') {
            $field = '';
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $field .= "`{$key}`='{$value}', ";
                }else{
                    $field .= "`{$key}`={$value}, ";
                }
            }
            $field = substr($field, 0, -2);
            $sql .= "UPDATE {$this->options['table']} SET {$field} {$this->options['where']}";
        }
        if ($type == 'insert') {
            $field = '';
            $val = '';
            foreach ($data as $key => $value) {
                $field .= "`{$key}`,";
                if (is_string($value)) {
                    $val .= "'{$value}',";
                }else{
                    $val .=  "{$value},";
                }
            }
            $field = substr($field, 0, -1);
            $val = substr($val, 0, -1);
            $sql .= "INSERT INTO {$this->options['table']} ({$field}) VALUES ({$val})";
        }
        if ($type == 'delete') {
            $sql .= "DELETE FROM {$this->options['table']} {$this->options['where']}";
        }

        $sql = preg_replace("/\s+/",' ',$sql);
        $this->prev_sql = $sql;
        $this->resetOption();

        return trim($sql);
    }

    /**
     * 执行sql语句
     *
     * ----------------------------------------------------------
     * @param model  $model 查询model
     * @param string $sql   查询sql 默认空 查询上一条语句
     * @param string $row   all=返回所有 find=返回第一条 默认all
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-21 15:00
     * ----------------------------------------------------------
     * @return array 查询结果
     */
    public function query($model, $sql='', $row='all')
    {
        $sql = $sql==''?$this->prev_sql:$sql;
        $list = new Resultset(null, $model, $model->getReadConnection()->query($sql));
        $list = $list->toArray();
        if ($row == 'find') {
            $list = isset($list[0]) ? $list[0] : '';
        }

        return $list;
    }


    /**
     * 修改方法
     *
     * ----------------------------------------------------------
     * @param model  $model 执行model
     * @param string $sql   修改sql
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-21 15:00
     * ----------------------------------------------------------
     * @return bool
     */
    public function update($model, $sql='')
    {
        if ($sql == '') {
            return false;
        }

        return $model->getWriteConnection()->execute($sql);
    }


    /**
     * 新增方法
     *
     * ----------------------------------------------------------
     * @param model  $model 执行model
     * @param array  $data  新增数组，key=>value
     * ----------------------------------------------------------
     * @author gaoxiang <gaoxiang@xiaohe.com>
     * ----------------------------------------------------------
     * @date 2017-03-21 15:00
     * ----------------------------------------------------------
     * @return bool
     */
    public function insert($model, $data=array())
    {
        foreach ($data as $key => $value) {
            $model->{$key} = $value;
        }
        if (!$model->save()) {
            foreach ($model->getMessages() as $key => $value) {
                $err['message'] = $value->getMessage();
                $err['Field']   = $value->getField();
                $err['type']    = $value->getType();
            }
            DLOG(json_encode($err), 'ERROR', 'exception.log');

            return false;
        }

        return true;
    }

    /**
     * 分布式事务 XA–eXtended Architecture
     *
     * @param array
     *
     * @return bool
     *
     * $args = [
     *     ['db_name' => 'db_name', 'sql' => 'sql'],
     *     ['db_name' => 'db_name', 'sql' => 'sql'],
     *     ...
     * ]
     *
     * @descript 跨库事务执行效率较慢，非严格要求数据统一性慎用跨库事务
     * 执行失败请查看:  SHOW VARIABLES LIKE '%xa%' 查看XA是否开启，表引擎是否是InnoDB
     * 如果未开启执行:  SET innodb_support_xa = ON
     * 表引擎非InnoDB: 不支持事务
     */
    final public function XA($data)
    {
        $config     = new \Phalcon\Config\Adapter\Ini(ROOT. 'conf'. DIRECTORY_SEPARATOR .'db.ini');
        $mysql_pool = []; // connections pool

        // db connections
        foreach ($data as $key => $value) {
            if (!isset($mysql_pool[$value['db_name']])) {
                $xid          = uniqid('');
                $conf_db      = '';
                $join_db_name = $value['db_name'].'_master';
                $conf_db      = $config->$join_db_name;

                // connections
                $mysql_pool[$value['db_name']]['conn'] = new mysqli(
                    $conf_db->host,
                    $conf_db->username,
                    $conf_db->password,
                    $conf_db->dbname,
                    $conf_db->port
                );

                // add xa id
                $mysql_pool[$value['db_name']]['xid'] = $xid;

                if (mysqli_connect_errno()) {
                    throw new Exception(mysqli_connect_error());
                }
            }
        }

        try {
            // xa start
            foreach ($mysql_pool as $key => $pool) {
                $pool['conn']->query("XA START '{$pool['xid']}'");
            }

            // execute sql
            foreach ($data as $key => $value) {
                $exe_res = $mysql_pool[$value['db_name']]['conn']->query($value['sql']);
                if ($exe_res == false) {
                   throw new Exception("{$value['sql']} 执行失败！");
                }
            }

            // xa commit
            foreach ($mysql_pool as $key => $pool) {
                $pool['conn']->query("XA END '{$pool['xid']}'");
                $pool['conn']->query("XA PREPARE '{$pool['xid']}'");
                $pool['conn']->query("XA COMMIT '{$pool['xid']}'");
            }
        } catch (Exception $e) {
            // rollback
            foreach ($mysql_pool as $key => $pool) {
                $pool['conn']->query("XA END '{$pool['xid']}'");
                $pool['conn']->query("XA PREPARE '{$pool['xid']}'");
                $pool['conn']->query("XA ROLLBACK '{$pool['xid']}'");

                // close db connections
                $pool['conn']->close();
            }
            DLOG('XA: '.json_encode($data).' err sql:'.$e->getMessage(), 'ERROR', 'exception.log');

            return false;
        }

        // close db connections
        foreach ($mysql_pool as $key => $pool) {
            $pool['conn']->close();
        }

        return true;
    }
}
