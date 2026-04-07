<?php

namespace Component\Subscription;

use Component\Subscription\SubscriptionTrait;
use App;
use Request;

class SubscriptionAdmin
{
    private $db;
    private $obj;
    use SubscriptionTrait;

    public $applyFl = false;

    public function __construct()
    {
        $this->db = App::load('DB');
        $this->obj = App::load(\Component\Subscription\Subscription::class);
        $this->setCfg();

        $this->applyFl = true;
    }


    public function getApplyList($limit = 30)
    {
        $get = Request::get()->toArray();

        $list = $q = [];
        $conds = "";
        $page = gd_isset($get['page'], 1);
        $limit = gd_isset($limit, 30);
        $offset = ($page - 1) * $limit;

        if ($get['searchDate'][0]) {
            $stamp = strtotime($get['searchDate'][0]);
            $q[] = "a.regStamp >= {$stamp}";
        }
        if ($get['searchDate'][1]) {
            $stamp = strtotime($get['searchDate'][1]) + (60 * 60 * 24);
            $q[] = "a.regStamp < {$stamp}";
        }

        if ($get['autoExtend'])
            $q[] = "a.autoExtend='1'";



        if ($get['sopt'] && $get['skey']) {
            $fields = "";
            switch ($get['sopt']) {
                case "all":
                    $fields = "CONCAT(a.uid,a.orderName,a.receiverName,m.memNm,a.orderPhone, a.orderCellPhone,a.receiverPhone,a.receiverCellPhone)";
                    break;
                case "name" :
                    $fields = "CONCAT(a.orderName,a.receiverName, m.memNm)";
                    break;
                case "mobile" :
                    $fields = "CONCAT(a.orderPhone,a.orderCellPhone,a.receiverPhone,a.receiverCellPhone)";
                    break;
                default :
                    $fields = $get['sopt'];
            }

            $q[] = $fields . " LIKE '%{$get['skey']}%'";
        }

        if ($q)
            $conds = " WHERE " . implode(" AND ", $q);


        $total = $amount = 0;
        $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_apply";
        $row = $this->db->fetch($sql);
        $amount = $row['cnt'];

        $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_apply AS a
                        LEFT JOIN " . DB_MEMBER . " AS m ON a.memNo = m.memNo
                        {$conds}
                        ORDER BY a.regStamp desc";
        $row = $this->db->fetch($sql);
        $total = $row['cnt'];

        $addField = "CASE WHEN a.autoExtend = '0' AND EXISTS (SELECT 1 FROM wm_subscription_schedule_list AS ss WHERE ss.uid = a.uid AND ss.isStop = 1 AND ss.schedule_stamp > UNIX_TIMESTAMP()) OR NOT EXISTS ( SELECT 1 FROM wm_subscription_schedule_list ss WHERE ss.uid = a.uid AND ss.schedule_stamp > UNIX_TIMESTAMP() AND ss.isStop = 0) THEN 1 ELSE 0 END AS isStopFl";

        $sql = "SELECT a.*,  m.memId, m.memNm, " . $addField . " FROM wm_subscription_apply AS a
                        LEFT JOIN " . DB_MEMBER . " AS m ON a.memNo = m.memNo
                        {$conds}
                        ORDER BY a.regStamp desc LIMIT {$offset},{$limit}";

        if ($tmp = $this->db->query_fetch($sql)) {
            foreach ($tmp as $t) {
                $card = $this->obj->setCard($t['idx_card'])->get();
                $t['cardNm'] = $card['cardNm'];
                $totalPrice = 0;
                if ($items = $this->getApplyItems($t['uid'])) {
                    foreach ($items as $it) {
                        $totalPrice += $it['totalPrice'];
                    }
                }
                $t['items'] = $items;

                $t['totalPrice'] = $totalPrice;

                $scheduleSummary = $this->obj->getScheduleSummaryByUid($t['uid']);
                $t['scheduleSummary'] = $scheduleSummary;

                $list[] = $t;
            }
        }
        
        $obj = App::load(\Component\Page\Page::class, $page, $total, $amount, $limit);
        $obj->setUrl(http_build_query($get));
        $pagination = $obj->getPage();

        return ['list' => $list, "page" => $obj, 'pagination' => $pagination];
    }

    public function getApplyItems($uid = null)
    {
        $list = [];
        if ($uid) {
            $sql = "SELECT a.*, g.goodsNm, g.goodsPrice, go.optionValue1, go.optionValue2, go.optionValue3, go.optionValue4, go.optionValue5, go.optionPrice FROM wm_subscription_apply_items AS a 
                                LEFT JOIN " . DB_GOODS . " AS g ON a.goodsNo = g.goodsNo
                                LEFT JOIN " . DB_GOODS_OPTION . " AS go ON go.sno = a.optionSno  
                            WHERE a.uid='{$uid}'";
            if ($tmp = $this->db->query_fetch($sql)) {
                foreach ($tmp as $t) {
                    $addPrice = 0;
                    $optionText = [];
                    if ($t['optionText']) {
                        if ($opList = json_decode($t['optionText'], true)) {
                            foreach ($opList as $sno => $text) {
                                $row = $this->db->fetch("SELECT optionName, addPrice FROM " . DB_GOODS_OPTION_TEXT . " WHERE sno='{$sno}'");
                                if ($row['optionName']) {
                                    $addPrice += $row['addPrice'];
                                    $optionText[] = ['optionName' => $row['optionName'], 'addPrice' => $row['addPrice'], 'value' => $text];
                                }
                            }
                        }
                    }
                    $t['price']['goodsPrice'] = $t['goodsPrice'];
                    $t['optionText'] = $optionText;
                    $totalPriceEach = $t['goodsPrice'] + $t['optionPrice'] + $addPrice;
                    $t['totalPriceEach'] = $totalPriceEach;
                    $t['totalPrice'] = $totalPriceEach * $t['goodsCnt'];
                    $list[] = $t;
                }
            }
        }

        return $list;
    }

    public function orderList($limit = 30)
    {
        $get = Request::get()->toArray();

        $page = gd_isset($get['page'], 1);
        $total = $amount = 0;
        $offset = ($page - 1) * $limit;

        $list = $q = [];

        $conds = "";

        if ($get['searchDate'][0]) {
            $sstamp = strtotime($get['searchDate'][0]);
            $date = date("Y-m-d H:i:s", $sstamp);
            $q[] = "oi.regDt >= '{$date}'";
        }

        if ($get['searchDate'][1]) {
            $estamp = strtotime($get['searchDate'][1]) + (60 * 60 * 24);
            $date = date("Y-m-d H:i:s", $estamp);
            $q[] = "oi.regDt < '{$date}'";
        }

        if ($get['orderStatus']) {
            $q2 = [];
            foreach ($get['orderStatus'] as $s) {
                $q2[] = "'{$s}'";
            }

            if ($q2)
                $q[] = "o.orderStatus IN (" . implode(",", $q2) . ")";
        }

        if ($get['sopt'] && $get['skey']) {
            $fields = "";
            switch ($get['sopt']) {
                case "all" :
                    $fields = "CONCAT(m.phone, m.cellPhone, oi.orderNo, oi.orderPhone, oi.orderCellPhone, oi.receiverPhone, oi.receiverCellPhone, m.memNm, oi.orderName, oi.receiverName)";
                    break;
                case "name" :
                    $fields = "CONCAT(m.memNm, oi.orderName, oi.receiverName)";
                    break;
                case "mobile":
                    $fields = "CONCAT(m.phone, m.cellPhone, oi.orderPhone, oi.orderCellPhone, oi.receiverPhone, oi.receiverCellPhone)";
                    break;
                default :
                    $fields = $get['sopt'];
                    break;
            }
            $q[] = "{$fields} LIKE '%{$get['skey']}%'";
        }

        if ($q)
            $conds = " AND " . implode(" AND ", $q);

        $sql = "SELECT COUNT(*) as cnt FROM " . DB_ORDER_INFO . " WHERE isSubscription='1'";
        $row = $this->db->fetch($sql);
        $amount = $row['cnt'];

        $sql = "SELECT COUNT(*) as cnt FROM " . DB_ORDER_INFO . " AS oi 
                       INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo 
                       INNER JOIN " . DB_ORDER_GOODS . " AS og ON oi.orderNo = og.orderNo 
                       LEFT JOIN " . DB_MEMBER . " AS m ON o.memNo = m.memNo 
                    WHERE oi.isSubscription='1'{$conds}";
        $row = $this->db->fetch($sql);
        $total = $row['cnt'];

        $sql = "SELECT DISTINCT(oi.orderNo) FROM " . DB_ORDER_INFO . " AS oi 
                       INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo 
                       INNER JOIN " . DB_ORDER_GOODS . " AS og ON oi.orderNo = og.orderNo 
                       LEFT JOIN " . DB_MEMBER . " AS m ON o.memNo = m.memNo 
                    WHERE oi.isSubscription='1'{$conds} ORDER BY oi.regDt desc LIMIT {$offset},{$limit}";

        if ($tmp = $this->db->query_fetch($sql)) {
            $order = App::load(\Component\Order\Order::class);
            foreach ($tmp as $t) {
                $sql = "SELECT * FROM " . DB_ORDER_INFO . " AS oi 
                       INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo WHERE oi.orderNo='{$t['orderNo']}'";
                $t = [];
                if ($tmp = $this->db->fetch($sql))
                    $t = $tmp;

                $list[] = $t;
            }
        }

        $obj = App::load(\Component\Page\Page::class, $page, $total, $amount, $limit);
        $obj->setUrl(http_build_query($get));
        $pagination = $obj->getPage();

        return ['list' => $list, "page" => $obj, 'pagination' => $pagination];
    }

    public function getListByUid($uid = null, $limit = 10)
    {
        $list = [];
        $get = Request::get()->toArray();
        $page = gd_isset($get['page'], 1);
        $total = $amount = 0;
        $offset = ($page - 1) * $limit;
        if ($uid) {
            $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_schedule_list AS a 
                                INNER JOIN " . DB_ORDER_INFO . " AS o ON a.orderNo = o.orderNo 
                            WHERE a.uid='{$uid}' AND a.orderNo <> ''";
            $row = $this->db->fetch($sql);
            $amount = $total = $row['cnt'];

            $sql = "SELECT a.orderNo FROM wm_subscription_schedule_list AS a 
                                INNER JOIN " . DB_ORDER_INFO . " AS o ON a.orderNo = o.orderNo 
                            WHERE a.uid='{$uid}' AND a.orderNo <> '' ORDER BY a.schedule_stamp desc LIMIT {$offset}, {$limit}";
            if ($tmp = $this->db->query_fetch($sql)) {
                $order = App::load(\Component\Order\Order::class);
                foreach ($tmp as $t) {
                    $sql = "SELECT * FROM " . DB_ORDER_INFO . " AS oi 
                       INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo WHERE oi.orderNo='{$t['orderNo']}'";
                    $t = [];
                    if ($tmp = $this->db->fetch($sql))
                        $t = $tmp;

                    $list[] = $t;
                }
            }
        }

        $obj = App::load(\Component\Page\Page::class, $page, $total, $amount, $limit);
        $obj->setUrl(http_build_query($get));
        $pagination = $obj->getPage();

        return ['list' => $list, "page" => $obj, 'pagination' => $pagination];
    }
}