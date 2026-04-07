<?php

namespace Component\Subscription;

use App;
use Request;
use Session;

class SubscriptionClient
{
    private $db;
    private $obj;
    private $cfg;

    public function __construct()
    {
        $this->db = App::load('DB');
        $this->obj = App::load(\Component\Subscription\Subscription::class);
        $this->cfg = $this->obj->getCfg();
    }

    public function getScheduleList($limit = 10, $memNo = null)
    {
        $get = Request::get()->toArray();
        $cfg = $this->cfg;

        $cancelEa = $cfg['cancelEa'] ? $cfg['cancelEa'] : 0;

        $list = $q = [];
        $conds = "";
        if (empty($memNo))
            $memNo = Session::get("member.memNo");

        if ($memNo) {
            $status = $this->obj->getOrderStatusList();
            if ($get['wDate'][0]) {
                $sstamp = strtotime($get['wDate'][0]);
                $q[] = "a.regStamp >= {$sstamp}";
            }

            if ($get['wDate'][1]) {
                $estamp = strtotime($get['wDate'][1]) + (60 * 60 * 24);
                $q[] = "a.regStamp < {$estamp}";
            }

            if ($q)
                $conds = " AND " . implode(" AND ", $q);

            $page = gd_isset($get['page'], 1);
            $total = $amount = 0;
            $offset = ($page - 1) * $limit;

            $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_apply WHERE memNo='{$memNo}'";
            $row = $this->db->fetch($sql);
            $amount = $row['cnt'];

            $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_apply WHERE memNo='{$memNo}'{$conds}";
            $row = $this->db->fetch($sql);
            $total = $row['cnt'];

            $field = "a.*, CASE WHEN a.autoExtend = '0' AND EXISTS (SELECT 1 FROM wm_subscription_schedule_list AS ss WHERE ss.uid = a.uid AND ss.isStop = 1 AND ss.schedule_stamp > UNIX_TIMESTAMP()) OR NOT EXISTS ( SELECT 1 FROM wm_subscription_schedule_list ss WHERE ss.uid = a.uid AND ss.schedule_stamp > UNIX_TIMESTAMP() AND ss.isStop = 0) THEN 1 ELSE 0 END AS isStopFl";


            $sql = "SELECT " . $field . " FROM wm_subscription_apply AS a 
                WHERE memNo = '{$memNo}'{$conds} ORDER BY a.regStamp desc LIMIT {$offset},{$limit}";


            if ($tmp = $this->db->query_fetch($sql)) {
                foreach ($tmp as $t) {
                    $card = [];
                    if ($t['idx_card'])
                        $card = $this->obj->setCard($t['idx_card'])->get();

                    $sql = "SELECT COUNT(*) as cnt, g.goodsNm FROM wm_subscription_apply_items AS a 
                                    INNER JOIN " . DB_GOODS . " AS g ON a.goodsNo = g.goodsNo 
                                WHERE a.uid='{$t['uid']}' ORDER BY a.idx";
                    $ro = $this->db->fetch($sql);
                    $t['goodsNm'] = $ro['goodsNm'];
                    if ($ro['cnt'] > 1) $t['goodsNm'] .= "외 " . ($ro['cnt'] - 1) . "건";

                    $t['period'] = explode("_", $t['period']);
                    $t['cardNm'] = $card['cardNm'];

                    $orderCnt = 0;
                    if ($schedule_list = $this->obj->getScheduleListByUid($t['uid'])) {
                        foreach ($schedule_list as $k => $v) {
                            if ($v['order']) {
                                $v['orderStatusStr'] = $status[$v['order']['orderStatus']];

                                $s = substr($v['order']['orderStatus'], 0, 1);
                                if (in_array($s, ['p', 'g', 'd', 's']))
                                    $orderCnt++;

                                $schedule_list[$k] = $v;
                            }
                        }
                    }

                    $t['schedule_list'] = $schedule_list;
                    $cancelPossible = true;

                    if ($cancelEa > 0 && $orderCnt < $cancelEa)
                        $cancelPossible = false;

                    $t['cancelPossible'] = $cancelPossible;
                    $list[] = $t;
                }
            }

            $obj = App::load(\Component\Page\Page::class, $page, $total, $amount, $limit);
            $obj->setUrl(http_build_query($get));
            $pagination = $obj->getPage();
        }

        return ['list' => $list, "page" => $obj, 'pagination' => $pagination, 'total' => $total];
    }
}