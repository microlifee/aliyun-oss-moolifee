<?php
/**
 * Modified: HuaChun.Xiang@qsyj
 * Email: 15516026@qq.com
 * DateTime: 2017/3/13 14:39
 * DESC: $_SERVER['ENV']为APACHE配置里，我新加的环境变量，用于标志当前PHP运行在哪个环境（比如测试？正式？），
 * 根据此值来自动选择应该连接哪个数据库。目的是简化发布流程（发布时不用修改数据库配置），并减少因为发布时漏改数据库配置的问题。
 * <IfModule env_module>
 * SetEnv ENV prod
 * </IfModule>
 */
class Db
{
    /**
     * 构造函数 链接数据库
     * @return boolean;
     */
    function __construct()
    {
        $con = mysql_connect("rm-wz90k41fq7zyz3sgjo.mysql.rds.aliyuncs.com", "aptrans_user", "Qslb12345");
        if (!$con) {
            die('Could not connect: ' . mysql_error());
        }
        mysql_set_charset("utf8");

        if (isset($_SERVER['ENV']) && $_SERVER['ENV'] == 'prod'){// 正式服
            mysql_select_db("aptrans_db", $con);
        }
        else{// 测试服
            mysql_select_db("testdb", $con);
        }
        return $con;
    }

    /**
     * 插入记录的操作
     * @param array $array
     * @param string $table
     * @return boolean
     */
    function insert($array, $table)
    {
        $keys = join(',', array_keys($array));
        $values = "'" . join("','", array_values($array)) . "'";
        $sql = "insert {$table}({$keys}) VALUES ({$values})";
        $res = mysql_query($sql);
        if ($res) {
            return mysql_insert_id();
        } else {
            return false;
        }
    }

    /**
     * MYSQL更新操作
     * @param array $array
     * @param string $table
     * @param string $where
     * @return number|boolean
     */
    function update($array, $table, $where = null)
    {
        $sets = '';
        foreach ($array as $key => $val) {
            $sets .= $key . "='" . $val . "',";
        }
        $sets = rtrim($sets, ','); //去掉SQL里的最后一个逗号
        $where = $where == null ? '' : ' WHERE ' . $where;
        $sql = "UPDATE {$table} SET {$sets} {$where}";
        $res = mysql_query($sql);
        if ($res) {
            return mysql_affected_rows();
        } else {
            return false;
        }
    }

    /**
     * 删除记录的操作
     * @param string $table
     * @param string $where
     * @return number|boolean
     */
    function delete($table, $where = null)
    {
        $where = $where == null ? '' : ' WHERE ' . $where;
        $sql = "DELETE FROM {$table}{$where}";
        $res = mysql_query($sql);
        if ($res) {
            return mysql_affected_rows();
        } else {
            return false;
        }
    }

    /**
     * 查询一条记录
     * @param string $sql
     * @param string $result_type
     * @return boolean
     */
    function fetchOne($sql, $result_type = MYSQL_ASSOC)
    {
        $result = mysql_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            return mysql_fetch_array($result, $result_type);
        } else {
            return false;
        }
    }

    /**
     * 得到表中的所有记录
     * @param string $sql
     * @param string $result_type
     * @return boolean
     */
    function fetchAll($sql, $result_type = MYSQL_ASSOC)
    {
        $result = mysql_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result, $result_type)) {
                $rows[] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }

    /**
     * 取得结果集中的记录的条数
     * @param string $sql
     * @return number|boolean
     */
    function getTotalRows($sql)
    {
        $result = mysql_query($sql);
        if ($result) {
            return mysql_num_rows($result);
        } else {
            return false;
        }

    }

    /**
     * 释放结果集
     * @param resource $result
     * @return boolean
     */
    function freeResult($result)
    {
        return mysql_free_result($result);
    }

    /**
     * 断开MYSQL
     * @param resource $link
     * @return boolean
     */
    function close($link = null)
    {
        return mysql_close($link);
    }

}
