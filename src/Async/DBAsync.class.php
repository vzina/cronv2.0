<?php
namespace EasyCron\Async;


use EasyCron\Async\Adapter\Mysql;

class DBAsync
{
    private $db;
    private $db_config;//数据库配置
    private $lastsql = '';//最后一次执行的sql语句
    private $sql_stmt = '';//组装的sql语句
    private $query_type = '';//当前正在执行语句类型

    public function __construct($config = array())
    {
        $this->db_config = [
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'root',
            'password' => '',
            'dbname' => 'test',
            'charset' => 'utf8'
        ];
        $this->db_config = array_merge($this->db_config, $config);
        $this->db = new Mysql($this->db_config);
    }

    public function queryArr($sql)
    {
        $this->lastsql = $sql;
        $result = (yield $this->db->query($sql));
        yield ((0 == $result['r']) ? $result['data'] : false);
    }

    //查询语句，返回单条结果
    //返回值：一维数组
    public function queryOne($sql)
    {
        $sqlArr = preg_split('#\s*LIMIT#i', $sql);
        $sql = $sqlArr[0] . ' LIMIT 1';
        $result = (yield $this->queryArr($sql));
        yield (isset($result[0]) ? $result[0] : '');
    }

    //查询语句，返回所有结果
    //返回值：二维数组
    public function queryAll($sql)
    {
        yield $this->queryArr($sql);
    }

    //执行结果为影响到的行数,只能是insert/delete/update语句
    //返回值：数字，影响到的行数
    public function querySql($sql)
    {
        $result = (yield $this->queryArr($sql));
        yield (isset($result['affected_rows']) ? $result['affected_rows'] : '');
    }

    //查询总条目
    public function count($table, $where = '')
    {
        $sql = 'select count(1) as total from `' . $table . '` ';
        $sql .= !empty($where) ? ' WHERE ' . $where : '';
        $r = (yield $this->queryOne($sql));
        yield (isset($r['total']) ? (int)$r['total'] : 0);
    }

    //插入方法，返回值为影响的行数
    //$idata为键值对数组，如array('name'=>'test','age'=>18);其中键为表字段，值为数值
    public function insert($table, $idata)
    {
        $set = '';
        foreach ($idata as $k => $v) {
            $set .= $k . '=' . $v . ',';
        }
        $set = !empty($set) ? trim($set, ',') : '';
        yield $this->table_insert($table)->setdata($set)->go();
    }

    //删除语句，返回值同上
    //$idata为条件键值对,如array('name'=>'test','age'=>18);其中键为表字段，值为数值.条件之间的关系为and
    public function delete($table, $idata)
    {
        $where = '';
        foreach ($idata as $_k => $_v) {
            $where .= '`' . $_k . '`=' . $_v . ' AND';
        }
        $where = !empty($where) ? trim($where, 'AND') : '1=2';

        yield $this->table_delete($table)->where($where)->go();
    }

    //更新语句，返回值同上
    /* public function update($sql, $data = array()){
        return $this->querySql($sql, $data);
    } */

    public function update($table, $set, $where)
    {
        $set_ = '';
        foreach ($set as $k => $v) {
            $set_ .= $k . '=' . $v . ',';
        }

        $set_ = trim($set_, ',');
        $where_ = '';
        foreach ($where as $_k => $_v) {
            $where_ .= '`' . $_k . '`=' . $_v . ' AND';
        }
        $where_ = !empty($where_) ? trim($where_, 'AND') : '1=2';

        yield $this->table_update($table)->setdata($set_)->where($where_)->go();
    }

    /*
     * 下面是链式操作的一些方法
     * 使用方式类似于   $db->table_select('mytable')->where('id=2')->go();
     * 注意：
     * 链式的第一个方法必须是table_????()
     * 链式的最后一个方法必须是go(),如果在链式中使用了预编译占位符，需要在go($data)传入参数
     */

    //查询链式起点，$table：表名
    public function table_select($table)
    {
        $this->sql_stmt = 'SELECT $field$ FROM `$table$` $where$ $other$';
        $this->sql_stmt = str_replace('$table$', $table, $this->sql_stmt);
        $this->query_type = 'select';
        return $this;
    }

    //更新链式起点，$table：表名
    public function table_update($table)
    {
        $this->sql_stmt = 'UPDATE `$table$` $set$ $where$';
        $this->sql_stmt = str_replace('$table$', $table, $this->sql_stmt);
        $this->query_type = 'update';
        return $this;
    }

    //删除链式起点，$table：表名
    public function table_delete($table)
    {
        $this->sql_stmt = 'DELETE FROM `$table$` $where$';
        $this->sql_stmt = str_replace('$table$', $table, $this->sql_stmt);
        $this->query_type = 'delete';
        return $this;
    }

    //插入链式起点，$table：表名
    public function table_insert($table)
    {
        $this->sql_stmt = 'INSERT INTO `$table$` $set$';
        $this->sql_stmt = str_replace('$table$', $table, $this->sql_stmt);
        $this->query_type = 'insert';
        return $this;
    }

    //链式执行结点，如果链式中使用了预编译占位符，需要在$data参数中传入
    //$data:占位符数据，
    //$multi:true,false 返回数据是多条还是一条，只适用于select查询,默认多条
    //$fetch_type:返回数据集的格式,默认索引
    public function go($multi = true)
    {
        switch ($this->query_type) {
            case 'select':
                $this->sql_stmt = str_replace('$field$', '*', $this->sql_stmt);
                $this->sql_stmt = str_replace(array(
                    '$other$', '$where$'
                ), '', $this->sql_stmt);
                if ($multi) {
                    yield $this->queryAll($this->sql_stmt);
                } else {
                    yield $this->queryOne($this->sql_stmt);
                }
                break;

            case 'insert':
            case 'delete':
            case 'update':
                $this->sql_stmt = str_replace('$set$', '', $this->sql_stmt);
                $this->sql_stmt = str_replace('$where$', ' WHERE 1=2', $this->sql_stmt);
                yield $this->querySql($this->sql_stmt);
                break;
            default:
                break;
        }
    }

    //链式操作的一些方法
    //field(),where(),order(),group(),limit(),setdata()
    public function __call($name, $args)
    {

        switch (strtoupper($name)) {
            case 'FIELD':
                $field = !empty($args[0]) ? $args[0] : '*';
                $this->sql_stmt = str_replace('$field$', $field, $this->sql_stmt);
                break;
            case 'WHERE':
                $where = !empty($args[0]) ? ' WHERE ' . $args[0] : '';
                $this->sql_stmt = str_replace('$where$', $where, $this->sql_stmt);
                break;
            case 'ORDER':
                $order = !empty($args[0]) ? ' ORDER BY ' . $args[0] . ' $other$' : '$other$';
                $this->sql_stmt = str_replace('$other$', $order, $this->sql_stmt);
                break;
            case 'GROUP':
                $group = !empty($args[0]) ? ' GROUP BY ' . $args[0] . ' $other$' : '$other$';
                $this->sql_stmt = str_replace('$other$', $group, $this->sql_stmt);
                break;
            case 'LIMIT':
                $limit = !empty($args) ? ' $other$ LIMIT ' . implode(',', $args) : '$other$';
                $this->sql_stmt = str_replace('$other$', $limit, $this->sql_stmt);
                break;
            case 'SETDATA':
                $set = !empty($args[0]) ? ' SET ' . $args[0] : '';
                $this->sql_stmt = str_replace('$set$', $set, $this->sql_stmt);
                break;
        }
        return $this;
    }

    //获取正在执行的sql语句
    public function getLastSql()
    {
        return $this->lastsql;
    }
}