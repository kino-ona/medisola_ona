<?php

namespace Controller\Front\Subscription;

use App;
use Request;
use Session;

class AjaxController extends \Controller\Front\Controller
{
    public function index()
    {
        $get = Request::get()->toArray();
        $post = Request::post()->toArray();
        $in = array_merge($get, $post);
        $db = App::load('DB');

        $cart = \App::load("\\Component\\Subscription\\Cart");
        $obj = App::load(\Component\Subscription\Subscription::class);


        switch ($in['mode']) {
            case 'goodsBenefitCheck':
                if($in['optionSno']) {
                    $cartIdx = $cart->setTempCart($in);
                    $this->json($cartIdx);
                }
                break;
            case "getSchedule" :

                $cart = App::load(\Component\Subscription\Cart::class);
                $cartInfo = $cart->getCartList($in['cartSno']);
                $obj = App::load(\Component\Subscription\Subscription::class);
                $cfg = $obj->getCfg();
                $this->setData("subCfg", $cfg);

                $list = $obj->setCartInfo($cartInfo)
                    ->setPrice($cart->totalGoodsPrice)
                    ->setTotalDeliveryCharge($cart->totalDeliveryCharge)
                    ->setMode("getScheduleList")
                    ->setPeriod($in['period'])
                    ->setScheduleEa($in['ea'])
                    ->get();
                if ($list) {
                    foreach ($list as $k => $v) {
                        $v['date'] = date("Y.m.d", $v['stamp']);
                        $v['delivery_date'] = date("Y.m.d", $v['delivery_stamp']);
                        $list[$k] = $v;
                    }
                }
                echo json_encode($list);
                break;
            case "chkCardPassword" :
                if ($in['idx_card'] && $in['password']) {

                    $card = $obj->setCard($in['idx_card'])
                        ->setMode("getCardInfo")
                        ->get();

                    echo password_verify($in['password'], $card['password']);
                }

                break;
            case "chkCardPasswordJson" :
                if ($in['idx_card'] && $in['password']) {

                    $card = $obj->setCard($in['idx_card'])
                        ->setMode("getCardInfo")
                        ->get();

                    $this->json(['ok' => password_verify($in['password'], $card['password'])]);
                }

                break;
            case "chkCardExists" :
                if ($memNo = Session::get("member.memNo")) {
                    if ($card = $obj->getCards($memNo))
                        echo 1;
                }
                break;
            case "changeAutoExtend" :
                if ($in['uid']) {
                    $chk = $in['chk'] ? 1 : 0;
                    $sql = "UPDATE wm_subscription_apply SET autoExtend='{$chk}' WHERE uid='{$in['uid']}'";
                    echo $db->query($sql);
                }
                break;
            case "changeCard" :
                if ($in['idx_card'] && $in['uid']) {
                    $sql = "UPDATE wm_subscription_apply SET idx_card='{$in['idx_card']}' WHERE uid='{$in['uid']}'";
                    echo $db->query($sql);
                }
                break;
            case "changeSchedule" :
                if ($in['idx'] && $in['date']) {
                    $today = strtotime(date("Ymd"));
                    $stamp = strtotime($in['date']);

                    if ($stamp <= $today)
                        $result = 2;
                    else {
                        $sql = "UPDATE wm_subscription_schedule_list SET schedule_stamp='{$stamp}' WHERE idx='{$in['idx']}'";
                        $result = $db->query($sql);
                    }

                    // 다음 스케줄이 있을 경우 주기에 따라 날짜를 변경 START
                    if($result) {

                        $row = $db->fetch("select * from wm_subscription_schedule_list where idx='{$in['idx']}'");
                        if (!empty($row)) {

                            //$rows = $db->query_fetch("select * from wm_subscription_schedule_list where uid='{$row['uid']}' and schedule_stamp > '{$stamp}' and isStop=0 order by schedule_stamp asc, idx asc");
							$rows = $db->query_fetch("select * from wm_subscription_schedule_list where uid='{$row['uid']}' and idx > '{$in['idx']}' order by idx asc");
                            if (!empty($rows)) {

                                $hList = $obj->getHolidayList();
                                $applyInfo = $db->fetch("select * from wm_subscription_apply where uid='{$row['uid']}'");
                                $period = $applyInfo['period'];

                                if (!empty($applyInfo)) {

                                    $extraStamp = $stamp;
                                    $arrPeriod = explode("_", $period);
                                    $strPeriod = '+' . $arrPeriod[0] . ' ' . $arrPeriod[1];

                                    foreach ($rows as $val) {
                                        $extraStamp = strtotime($strPeriod, $extraStamp);

                                        $schedule_stamp = $extraStamp;

                                        // 제외 날짜 목록: 공휴일 + 공휴일 다음날
                                        $excludeStamps = [];
                                        if (!empty($hList)) {
                                            foreach ($hList as $h) {
                                                $excludeStamps[$h['stamp']] = true;
                                                $excludeStamps[$h['stamp'] + 60 * 60 * 24] = true;
                                            }
                                        }

                                        // 일, 월, 공휴일, 공휴일 다음날 제외
                                        $safetyLimit = 30;
                                        while ($safetyLimit-- > 0) {
                                            $yoil = date("w", $schedule_stamp);
                                            if ($yoil == 0) {
                                                $schedule_stamp += 60 * 60 * 24 * 2;
                                            } else if ($yoil == 1) {
                                                $schedule_stamp += 60 * 60 * 24;
                                            } else if (isset($excludeStamps[$schedule_stamp])) {
                                                $schedule_stamp += 60 * 60 * 24;
                                            } else {
                                                break;
                                            }
                                        }

                                        $sql = "update wm_subscription_schedule_list set schedule_stamp='{$schedule_stamp}' WHERE idx='{$val['idx']}'";
                                        $db->query($sql);
                                    }
                                }

                            }

                        }

                    }
                    // 다음 스케줄이 있을 경우 주기에 따라 날짜를 변경 END

                    echo $result;
                }
                break;
        }
        exit;
    }
}