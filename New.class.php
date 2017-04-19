<?php

/**
 * 数据库操作类
 * @author  wanglin
 *
 * Modified: HuaChun.Xiang@qsyj
 * Email: 15516026@qq.com
 * DateTime: 2017/3/13 14:39
 * DESC: $_SERVER['ENV']为APACHE配置里，我新加的环境变量，用于标志当前PHP运行在哪个环境（比如测试？正式？），
 * 根据此值来自动选择应该连接哪个数据库。目的是简化发布流程（发布时不用修改数据库配置），并减少因为发布时漏改数据库配置的问题。
 * <IfModule env_module>
 * SetEnv ENV prod
 * </IfModule>
 */
class  Mydb
{

    public $storeId = '2,3,4,5,13,14,15,16,17,18,19,21,22,23,24,26';

    //public $storeId  = '2,3,4,5,24,26';
    public function __construct()
    {
        if (isset($_SERVER['ENV']) && $_SERVER['ENV'] == 'prod'){// 正式服
            $this->db = @new mysqli('10.45.180.222', 'qslb', 'a6b9f54175705ab8', 'qslb_prod3', '3306');
        }
        else{// 测试服
            $this->db 	= @new mysqli('127.0.0.1', 'root', '12345abc', 'salt_qslb', '3306');
        }
        if (mysqli_connect_errno()) {
            $result = array(
                'response' => '404', //数据库连接错误
            );
            $result = json_encode($result);
            $result = str_replace(array("\/"), "/", $result);
            exit($result);
        }
        $this->db->set_charset('utf8');
    }

    /**
     * 释放连接
     */

    private function unDB(&$_result, &$_db)
    {
        if (is_object($_result)) {
            $_result->free();
            $_result = null;
        }
        if (is_object($_db)) {
            $_db->close();
            $_db = null;
        }
    }

    /**
     * 统计总数
     */
    public function Total($tName, $condition = '')
    {
        $param_array = func_get_args();
        $param_num = count($param_array);
        switch ($param_num) {
            case 1:
                if (!is_string($tName)) exit($this->getError(__FUNCTION__, __LINE__));
                if (substr_count($tName, ' ')) {
                    if (preg_match('/\s+as\s+total\s+/Usi', $tName, $arr)) {
                        $sql = $tName;
                    } else {
                        exit($this->getError(__FUNCTION__, __LINE__, 'SQL must \'as total\''));
                    }
                    $sql = $tName;
                } else {
                    $sql = "SELECT COUNT(*) as total FROM {$tName}";
                }
                break;
            case 2:
                if (!is_string($tName) || !is_string($condition)) exit($this->getError(__FUNCTION__, __LINE__));
                $sql = "SELECT COUNT(*) as total FROM {$tName} WHERE " . $condition;
                break;
            default:
                exit($this->getError(__FUNCTION__, __LINE__));
                break;
        }

        if (!is_string($tName)) exit($this->getError(__FUNCTION__, __LINE__));
        $result = $this->GetRow($sql);
        $this->printSQLError($this->db);
        return $result['total'];
        $this->unDB($result, $this->db);
    }

    /**
     * 获取单条数据
     */
    public function GetRow($tName, $fields = "*", $condition = '')
    {
        $param_array = func_get_args();
        $param_num = count($param_array);
        switch ($param_num) {
            case 1:
                if (!is_string($tName)) exit($this->getError(__FUNCTION__, __LINE__));
                $sql = $tName;
                break;
            case 3:
                if (!is_string($tName) || !is_string($condition) || !is_string($fields)) exit($this->getError(__FUNCTION__, __LINE__));
                $sql = "SELECT {$fields} FROM {$tName} WHERE {$condition} LIMIT 1";
                break;
            default:
                exit($this->getError(__FUNCTION__, __LINE__));
                break;
        }
        $result = $this->db->query($sql);
        $this->printSQLError($this->db);
        return $result->fetch_assoc();
        $this->unDB($result, $this->db);
    }

    /**
     * 获取多条数据
     */
    public function FetchAll($tName, $fields = '*', $condition = '', $order = '', $limit = '')
    {

        $param_array = func_get_args();
        $param_num = count($param_array);
        $space_count = substr_count($tName, ' ');
        $sql = '';
        if ($param_num == 1 && $space_count > 0) {
            $sql = $tName;
        } else {
            if (!is_string($tName) || !is_string($fields) || !is_string($condition) || !is_string($order) || (!is_string($limit) && !is_int($limit))) exit($this->getError(__FUNCTION__, __LINE__));
            $fields = ($fields == '*' || $fields == '') ? '*' : $fields;
            $condition = $condition == '' ? '' : " WHERE " . $condition;
            $order = empty($order) ? '' : " ORDER BY " . $order;
            $limit = empty($limit) ? '' : " LIMIT " . $limit;
            $sql = "SELECT {$fields} FROM {$tName} {$condition} {$order} {$limit}";
        }
        if (empty($sql)) exit($this->getError(__FUNCTION__, __LINE__));
        $result = $this->db->query($sql);
        $this->printSQLError($this->db);
        $obj = array();
        while (!!$objects = $result->fetch_assoc()) {
            $obj[] = $objects;
        }
        return $obj;
        $this->unDB($result, $this->db);
    }


    /**
     * 输出错误
     */
    private function printSQLError($reserr)
    {
        if ($reserr->errno) {
            $result = array(
                'response' => '404', //数据库操作错误
            );
            $result = json_encode($result);
            $result = str_replace(array("\/"), "/", $result);
            exit($result);
        }
    }

    /**
     * 格式数组
     */
    private function formatArr($field, $isField = TRUE)
    {
        if (!is_array($field)) exit($this->getError(__FUNCTION__, __LINE__));
        if ($isField) {
            foreach ($field as $v) {
                $fields .= '`' . $v . '`,';
            }
        } else {
            foreach ($field as $v) {
                $fields .= '\'' . $v . '\'' . ',';
            }
        }
        $fields = rtrim($fields, ',');
        return $fields;
    }


    /**
     * 获取错误
     */
    private function getError($fun, $line, $other = "")
    {
        $result = array(
            'response' => '412',
        );
        $result = json_encode($result);
        $result = str_replace(array("\/"), "/", $result);
        exit($result);
    }

    public function checkout_time($storeid, $create_at)
    {
        //南阳
        if ($storeid == 2 && ((strtotime($create_at)) < strtotime('2016-12-15 23:59:59'))) {
            return true;
        }
        //广元
        if ($storeid == 3 && ((strtotime($create_at)) < strtotime('2016-12-07 23:59:59'))) {
            return true;
        }
        //临汾
        if ($storeid == 4 && ((strtotime($create_at)) < strtotime('2017-01-01 23:59:59'))) {
            return true;
        }
        //晋城
        if ($storeid == 5 && ((strtotime($create_at)) < strtotime('2016-12-23 23:59:59'))) {
            return true;
        }
        //长治
        if ($storeid == 13 && ((strtotime($create_at)) < strtotime('2016-12-04 23:59:59'))) {
            return true;
        }
        //运城
        if ($storeid == 14 && ((strtotime($create_at)) < strtotime('2016-11-26 23:59:59'))) {
            return true;
        }
        //吕梁
        if ($storeid == 15 && ((strtotime($create_at)) < strtotime('2016-10-22 23:59:59'))) {
            return true;
        }
        //阳泉
        if ($storeid == 16 && ((strtotime($create_at)) < strtotime('2016-08-30 23:59:59'))) {
            return true;
        }
        //朔州
        if ($storeid == 17 && ((strtotime($create_at)) < strtotime('2016-08-17 23:59:59'))) {
            return true;
        }
        //忻州
        if ($storeid == 18 && ((strtotime($create_at)) < strtotime('2016-08-23 23:59:59'))) {
            return true;
        }
        //晋中
        if ($storeid == 19 && ((strtotime($create_at)) < strtotime('2016-08-27 23:59:59'))) {
            return true;
        }
        //永济
        if ($storeid == 21 && ((strtotime($create_at)) < strtotime('2016-11-28 23:59:59'))) {
            return true;
        }
        //大同
        if ($storeid == 22 && ((strtotime($create_at)) < strtotime('2016-09-26 23:59:59'))) {
            return true;
        }
        //侯马
        if ($storeid == 23 && ((strtotime($create_at)) < strtotime('2016-10-31 23:59:59'))) {
            return true;
        }
        //唐河
        if ($storeid == 24 && ((strtotime($create_at)) < strtotime('2016-12-19 23:59:59'))) {
            return true;
        }
        //太原
        /* if($storeid==25 && ((strtotime($create_at))<strtotime('2016-10-12 23:59:59'))){
         return true;
        } */
        //高平
        if ($storeid == 26 && ((strtotime($create_at)) < strtotime('2016-12-25 23:59:59'))) {
            return true;
        }

        return false;
    }

    public function getorderItem($id)
    {
        $order_increment_id1 = $this->GetRow('sales_flat_order', 'increment_id', "entity_id='" . $id . "'");
        return $order_increment_id1['increment_id'];
    }

    public function getproductItem($id)
    {
        $productInfo = $this->GetRow('catalog_product_entity_varchar', "*", "entity_id='" . $id . "' AND attribute_id=153");
        if (count($productInfo)) {
            return $productInfo['value'];
        } else {
            return 0;
        }
    }

    public function getproductSupply($id, $website_id)
    {
        $arr = array();
        $productInfo = $this->GetRow('cargo_owner', "*", "product_id = '{$id}' AND website_id='{$website_id}'");
        if (count($productInfo)) {
            $arr['name'] = $productInfo['owner_name'];
            $arr['code'] = $productInfo['owner_id'];
        } else {
            $arr['name'] = '';
            $arr['code'] = '';
        }
        return $arr;
    }

    /*
     * storeid--wesiteid
     */
    public $website_arr = array(
        2 => 8,
        3 => 6,
        4 => 11,
        5 => 10,
        7 => 9,
        9 => 5,
        10 => 7,
        11 => 14,
        12 => 15,
        13 => 16,
        14 => 17,
        15 => 18,
        16 => 19,
        17 => 20,
        18 => 21,
        19 => 22,
        20 => 23,
        21 => 24,
        22 => 25,
        23 => 26,
        24 => 27,
        25 => 28,
        26 => 29,
        27 => 30,
        28 => 31,
        29 => 32,
        30 => 33,
        31 => 34,
    );

    public function getproducname($id)
    {
        $productInfo = $this->GetRow('catalog_product_entity_varchar', "*", " entity_id='" . $id . "' AND attribute_id=71 ");
        return $productInfo['value'];
    }

    public function gethourseinfo($websiteId)
    {
        return $this->GetRow("oms_k3cloud", "*", "oms_website_id='" . $websiteId . "'");
    }

    public function checkshipping($id)
    {
        $returnStock = $this->FetchAll("sales_flat_shipment", "*", "order_id='" . $id . "'");
        if (count($returnStock) > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    public function checkMyself($entity_id, $store_id)
    {
        //查询是否自营：
        /* 先查询： store_id=站点store_id AND entity_id = product_id AND attribute_id=157
        VALUE =1 表示自营
        VALUE = 0 非自营
        没有查出数据，查询另外的sql：
        再查询：store_id=0 AND entity_id = product_id AND attribute_id=157
        VALUE =1 表示自营
        VALUE = 0 非自营
        没有查出数据===》自营 */
        $result = $this->FetchAll("catalog_product_entity_int", "value", " entity_id='" . $entity_id . "' AND store_id='" . $store_id . "' AND attribute_id = '157' ");
        if (count($result) == 1) {
            if ($result[0]['value'] == 1) {
                //自营
                return 0;
            } else {
                //非自营
                return 1;
            }
        } else {
            $result1 = $this->FetchAll("catalog_product_entity_int", "value", " entity_id='" . $entity_id . "' AND store_id='0' AND attribute_id = '157' ");
            if (count($result1) == 1) {
                if ($result1[0]['value'] == 1) {
                    //自营
                    return 0;
                } else {
                    //非自营
                    return 1;
                }
            } else {
                //自营
                return 0;
            }
        }
    }
}
