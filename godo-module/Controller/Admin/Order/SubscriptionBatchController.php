<?php

namespace Controller\Admin\Order;

use App;
use Request;

class SubscriptionBatchController extends \Controller\Admin\Controller
{
    public function index()
    {
        $this->callMenu("order", "subscription", "batch");

        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            $get = Request::get()->toArray();
            $this->setData("search", $get);
            $db = App::load('DB');

            $cfg = $obj->getCfg();
            $smsDays = $cfg['smsDays'] ? $cfg['smsDays'] : 0;

            if (($get['date'] && $get['mode'] && $get['mode'] != 'batch_auto_extend') || $get['mode'] == 'batch_auto_extend') {
                $list = $obj->setMode($get['mode'] . "_list")
                    ->setDate($get['date'])
                    ->get();

                if ($list) {
                    foreach ($list as $k => $li) {
                        $idx = $li['idx'];
                        $info = $obj->getSubscription($idx);
                        $info['sms_stamp'] = $info['schedule_stamp'] - (60 * 60 * 24 * $smsDays);

                        $row = $db->fetch("SELECT memId, memNm FROM " . DB_MEMBER . " WHERE memNo='{$info['memNo']}'");
                        $info['memId'] = $row['memId'];
                        $info['memNm'] = $row['memNm'];
                        if ($items = $info['items']) {
                            foreach ($items as $k2 => $it) {
                                $g = $db->fetch("SELECT goodsNm FROM " . DB_GOODS . " WHERE goodsNo='{$it['goodsNo']}'");
                                $it['goodsNm'] = $g['goodsNm'];

                                $items[$k2] = $it;
                            }
                            $info['items'] = $items;
                        }

                        $list[$k] = $info;
                    }

                    $this->setData("list", $list);
                }
            }

            $this->setData('wmSubscription', true);
        }
    }
}