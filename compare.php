<?php
require_once 'New.class.php';
require_once 'db.class.php';
$mydb = new Mydb();
$storeId = $mydb->storeId;
$centetime = date('Y-m-d', strtotime('-2day')) . ' 23:59:59';
$datestring = date('Y-m-d', (strtotime($centetime) - 8 * 3600));
//销售收款
$receive = $mydb->FetchAll("SELECT h.`add_bill` as amount,h.`entity_name` as tiaojian1,
                h.`status` as tiaojian2,h.`comment` as tiaojian3,h.`parent_id`,h.`created_at` AS wancheng,h.`comment`,h.`entity_name`,
                h.`status`,o.* FROM `sales_flat_order_status_history` AS h LEFT JOIN `sales_flat_order` AS o ON h.parent_id = o.entity_id 
                WHERE o.store_id in(" . $storeId . ") AND CONVERT(h.created_at,DATE)>'" . $datestring . "' AND h.`comment` = '确认收款'");
foreach ($receive as $kreceive => $vreceive) {
    if (!$mydb->checkshipping($vreceive['parent_id'])) {
        continue;
    }
    if ($mydb->checkout_time($vreceive['store_id'], $vreceive['wancheng'])) {
        continue;
    }
    if (($vreceive['tiaojian1'] == 'order' && $vreceive['tiaojian2'] == 'complete_order' && $vreceive['tiaojian3'] == '确认收款')) {
        //获取时间
        $created_at = date('Y-m-d H:i:s', (strtotime($vreceive['wancheng']) + 8 * 3600));
        $refunded = $vreceive['subtotal_refunded'];
        $discount = $vreceive['rewardpoints_discount'];
        if ($vreceive['amount'] > 0) {
            $amount = $vreceive['amount'];
        } else {
            $amount = $vreceive['grand_total'] - $vreceive['subtotal_refunded'];
        }
        $websiteId2 = isset($mydb->website_arr[$vreceive['store_id']]) ? $mydb->website_arr[$vreceive['store_id']] : 0;
        if (!$websiteId2) {
            continue;
        }
        $hourceinfo1 = $mydb->gethourseinfo($websiteId2);
        if (count($hourceinfo1) && !empty($hourceinfo1['website_id']) && !empty($hourceinfo1['warehouse_id'])) {
            $k3_website_id1 = $hourceinfo1['website_id'];
            $warehouse_id1 = $hourceinfo1['warehouse_id'];
            $customer1 = $hourceinfo1['customer'];
        } else {
            $k3_website_id1 = '';
            $warehouse_id1 = '';
            $customer1 = '';
        }


        //将$refunded,$discount,$amount拆分成自营；非自营（多货主）单据。$vreceive['parent_id']
        $recieve_item = $mydb->FetchAll("SELECT NAME,product_id,order_id,amount_refunded,rewardpoints_discount,
                    row_total,product_id FROM `sales_flat_order_item` WHERE order_id = '{$vreceive['parent_id']}'");
        $refunded_no = 0;
        $discount_no = 0;
        $amount_no = 0;
        foreach ($recieve_item as $k => $item_no) {
            //自营商品不处理
            if (!$mydb->checkMyself($item_no['product_id'], $vreceive['store_id'])) {
                continue;
            }
            $refunded_no += $item_no['amount_refunded'];
            $discount_no += isset($item_no['rewardpoints_discount']) ? $item_no['rewardpoints_discount'] : 0;
            $amount_no += ($item_no['amount_refunded']);
            //非自营 type=1
            $productItem = $mydb->getproductItem($item_no['product_id']);
            if (empty($productItem)) {
                continue;
            }
            $supply = $mydb->getproductSupply($item_no['product_id'], $websiteId2);
            if (($item_no['row_total']) > 0) {
                $order_id_increment3 = $vreceive['increment_id'];
                $k3Db = new Db();
                $receive_sql_no = "SELECT * FROM `AR_RECEIVEBILL` WHERE productItem ='{$productItem}' AND type=1 AND orderId ='" . $order_id_increment3 . "' LIMIT 1";
                $receiveK3_no = $k3Db->fetchAll($receive_sql_no);
                if (empty($receiveK3_no[0]['id'])) {
                    $product_arr3_no = array(
                        'websiteId' => $websiteId2,
                        'orderId' => $order_id_increment3,
                        'refunded' => $item_no['amount_refunded'],
                        'discount' => $item_no['rewardpoints_discount'],
                        'amount' => ($item_no['row_total']) - $item_no['amount_refunded'],
                        'synctime' => $created_at,
                        'k3_website_id' => $k3_website_id1,
                        'warehouse_id' => $warehouse_id1,
                        'customer' => $customer1,
                        'type' => 1,
                        'productItem' => $productItem,
                        'productName' => $item_no['name'],
                        'supplyname' => $supply['name'],
                        'supplycode' => $supply['code'],
                    );
                    $k3Db = new Db();
                    $k3Db->insert($product_arr3_no, "AR_RECEIVEBILL");
                }
            }
        }
        $refunded -= $refunded_no;
        $discount -= $discount_no;
        $amount -= $amount_no;
        //自营 type=0
        if ($amount > 0) {
            $order_id_increment3 = $vreceive['increment_id'];
            $k3Db = new Db();
            $receive_sql = "SELECT * FROM `AR_RECEIVEBILL` WHERE type=0 AND orderId ='" . $order_id_increment3 . "' LIMIT 1";
            $receiveK3 = $k3Db->fetchAll($receive_sql);
            if (empty($receiveK3[0]['id'])) {
                $product_arr3 = array(
                    'websiteId' => $websiteId2,
                    'orderId' => $order_id_increment3,
                    'refunded' => $refunded,
                    'discount' => $discount,
                    'amount' => $amount,
                    'synctime' => $created_at,
                    'k3_website_id' => $k3_website_id1,
                    'warehouse_id' => $warehouse_id1,
                    'customer' => $customer1,
                    'type' => 0,
                );
                $k3Db = new Db();
                $k3Db->insert($product_arr3, "AR_RECEIVEBILL");
            }
        }
    }
}
