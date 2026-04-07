<?php

namespace Component\Subscription;

use App;
use MongoDB\BSON\PackedArray;
use Request;
use Session;
use Component\Subscription\SubscriptionTrait;
use Component\Sms\Sms;
use Component\Sms\SmsMessage;
use Component\Sms\LmsMessage;
use Framework\Security\Otp;
use Component\Member\Util\MemberUtil;
use Component\Goods\Goods;

class Subscription
{
    private $db;
    private $obj;
    private $idxCard;
    use SubscriptionTrait;

    public $applyFl = false;

    public function __construct()
    {
        $this->db = App::load('DB');
        $this->setCfg();

        $this->cfg['pg'] = $this->cfg['pg'] ? $this->cfg['pg'] : "inicis";
        switch ($this->cfg['pg']) {
            case "inicis":
                $this->obj = App::load("\Component\Subscription\SubscriptionPgInicis");
                break;
            case "kcp" :
                $this->obj = App::load("\Component\Subscription\SubscriptionPgKcp");
                break;
        }

        $this->cfg = array_merge($this->cfg, $this->obj->getPgCfg());

        $cfg = $this->getCfg();

        $this->applyFl = true;
    }

    public function getPgInstance()
    {
        return $this->obj;
    }

    /* 비밀번호 입력 키 추출 */
    public function getShuffleChars()
    {
        $chars = range(0, 9);
        shuffle($chars);

        return $chars;
    }

    /* 결제 카드 리스트 */
    public function getCards($memNo = null)
    {
        $list = [];
        $conds = "";
        if ($memNo) {
            $memNo = $this->db->escape($memNo);
            $conds = " WHERE memNo='{$memNo}'";
        }

        if ($tmp = $this->db->query_fetch("SELECT * FROM wm_subscription_cards{$conds} ORDER BY idx desc")) {
            foreach ($tmp as $t) {
                $row = $this->db->fetch("SELECT COUNT(wa.uid) as cnt FROM wm_subscription_apply wa INNER JOIN wm_subscription_schedule_list ws ON ws.uid=wa.uid WHERE wa.idx_card='{$t['idx']}' and (wa.autoExtend='1' or (ws.isStop='0' and ws.isPayed='0'))");
                if ($row['cnt'] > 0)
                    $t['underUse'] = true;

                $list[] = $t;
            }
        }

        return $list;
    }

    /* 결제 카드 선택 */
    public function setCard($idx = null, $mode = "")
    {
        $this->idxCard = $this->db->escape($idx);
        if (empty($mode))
            $mode = "getCardInfo";

        $this->mode = $mode;
        return $this;
    }

    public function setIdx($idx = null)
    {
        $this->idx = $idx;
        return $this;
    }

    public function setBoolean($bool = false)
    {
        $this->boolean = $bool;
        return $this;
    }

    public function setMode($mode = null)
    {
        if ($mode)
            $this->mode = $mode;

        return $this;
    }

    /* 결제카드 삭제 */
    public function delCard($idx = null)
    {
        if (empty($idx))
            $idx = $this->idxCard;

        if (!$idx)
            return false;

        $res = $this->db->query("DELETE FROM wm_subscription_cards WHERE idx='{$idx}'");
        return $res;
    }

    /* 결제 */
    public function pay($idx = null, $chkStamp = true, $isManual = false)
    {
        return $this->obj->pay($idx, $chkStamp, $isManual);
    }

    /* 결제취소 */
    public function cancel($orderNo = null, $isApplyRefund = true, $msg = null)
    {
        return $this->obj->cancel($orderNo, $isApplyRefund, $msg);
    }

    /* 정기결제 신청 정보 */
    public function getSubscription($idx = null)
    {
        $info = [];
        if (!$idx)
            return $info;

        if (!$tmp = $this->db->fetch("SELECT * FROM wm_subscription_schedule_list WHERE idx='{$idx}'"))
            return $info;

        $info = $this->setUid($tmp['uid'])
            ->setMode("subscriptionInfo")
            ->get();

        if (!$info)
            return $info;

        $info = array_merge($info, $tmp);
        return $info;
    }

    /* 정기결제 현재까지 유효 회차 */
    public function getSubscriptionCnt($uid = null)
    {
        if (!$uid)
            return false;

        $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_schedule_list AS a 
                        INNER JOIN " . DB_ORDER . " AS o ON a.orderNo = o.orderNo 
                   WHERE a.uid='{$uid}' AND a.orderNo <> '' AND SUBSTR(o.orderStatus, 1, 1) IN ('p', 'g', 'd', 's')";

        $row = $this->db->fetch($sql);
        return $row['cnt'];
    }

    /* 정기결제 등록 처리 START */
    public function registerSubscription($post = [])
    {
        $memNo = Session::get("member.memNo");
        if ($post && $post['cartSno'] && $memNo) {

            foreach ($post as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $_k => $_v) {
                        $post[$k][$_k] = $this->db->escape($_v);
                    }
                } else {
                    $post[$k] = $this->db->escape($v);
                }
            }

            $sql = "SELECT * FROM wm_subscription_cart3 WHERE idx IN (" . implode(",", $post['cartSno']) . ") ORDER BY idx ";
            if (!$items = $this->db->query_fetch($sql))
                return false;

            $order = App::load(\Component\Order\Order::class);

            $uid = $order->generateOrderNo();

            try {
                $this->db->begin_tran();

                foreach ($items as $it) {
                    foreach ($it as $k => $v)
                        $it[$k] = $this->db->escape($v);

                    $sql = "INSERT INTO wm_subscription_apply_items  
                                 SET 
                                    uid='{$uid}',
                                    goodsNo='{$it['goodsNo']}',
                                    optionSno='{$it['optionSno']}',
                                    goodsCnt='{$it['goodsCnt']}',
                                    addGoodsNo='{$it['addGoodsNo']}',
                                    addGoodsCnt='{$it['addGoodsCnt']}',
                                    optionText='{$it['optionText']}',
                                    deliveryCollectFl='{$it['deliveryCollectFl']}',
                                    deliveryMethodFl='{$it['deliveryMethodFl']}'";
                    $this->db->query($sql);
                } // endforeach

                $sql = "INSERT INTO wm_subscription_apply 
                            SET 
                                uid='{$uid}',
                                memNo='{$memNo}',
                                regStamp='" . time() . "',
                                deliveryEa='{$post['deliveryEa']}', 
                                period='{$post['period']}',
                                autoExtend='1',
                                idx_card='{$post['idx_card']}',
                                orderPhone='{$post['orderPhone']}',
                                orderZonecode='{$post['orderZonecode']}',
                                orderAddress='{$post['orderAddress']}',
                                orderAddressSub='{$post['orderAddressSub']}',
                                orderName='{$post['orderName']}',
                                orderCellPhone='{$post['orderCellPhone']}',
                                orderEmail='{$post['orderEmail']}',
                                receiverName='{$post['receiverName']}',
                                receiverZonecode='{$post['receiverZonecode']}',
                                receiverAddress='{$post['receiverAddress']}',
                                receiverAddressSub='{$post['receiverAddressSub']}',
                                receiverZipcode='{$post['receiverZipcode']}',
                                receiverPhone='{$post['receiverPhone']}',
                                receiverCellPhone='{$post['receiverCellPhone']}',
                                orderMemo='{$post['orderMemo']}',
                                receiptFl='{$post['receiptFl']}',
                                receiverUseSafeNumberFl='{$post['receiverUseSafeNumberFl']}',
                                orderZipcode='{$post['orderZipcode']}',
                                totalMemberDcPrice='{$post['totalMemberDcPrice']}',
                                totalMemberOverlapDcPrice='{$post['totalMemberOverlapDcPrice']}',
                                deliveryFree='{$post['deliveryFree']}',
                                totalDeliveryFreePrice='{$post['totalDeliveryFreePrice']}',
                                totalDeliveryCharge='{$post['totalDeliveryCharge']}',
                                deliveryAreaCharge='{$post['deliveryAreaCharge']}'";
                $this->db->query($sql);

                /* 스케줄 리스트 */
                if ($list = $this->getScheduleList($post['deliveryEa'], 0, $post['period'])) {
                    foreach ($list as $li) {
                        $sql = "INSERT INTO wm_subscription_schedule_list 
                                    SET 
                                        uid='{$uid}',
                                        schedule_stamp='{$li['stamp']}'";
                        $this->db->query($sql);
                    }

                }

                $this->db->commit();

                return $uid;
            } catch (Exception $e) {
                $this->db->rollback();
            }
        }
    }

    public function extendSchedule($uid = null, $deliveryEa = 0, $period = 0)
    {
        if ($uid && $deliveryEa && $period) {
            $sql = "SELECT * FROM wm_subscription_schedule_list WHERE uid='{$uid}' ORDER BY schedule_stamp desc";
            $row = $this->db->fetch($sql);
            $stamp = $row['schedule_stamp'];

            if ($stamp <= time()) {
                if (!$ro = $this->db->fetch("SELECT period FROM wm_subscription_apply WHERE uid='{$uid}'"))
                    return false;

                $dbPeriod = explode("_", $ro['period']);
                $str = "+" . $dbPeriod[0] . " " . $dbPeriod[1];
                $stamp = strtotime($str, $stamp);
                if ($stamp < time())
                    $stamp = strtotime(date('Ymd'));
            } else {
                $periodParts = explode("_", $period);
                $str = "+" . $periodParts[0] . " " . $periodParts[1];
                $stamp = strtotime($str, $stamp);
            }

            if ($list = $this->getScheduleList($deliveryEa, $stamp, $period)) {
                foreach ($list as $li) {
                    $sql = "INSERT INTO wm_subscription_schedule_list
                                SET
                                    uid='{$uid}',
                                    schedule_stamp='{$li['stamp']}'";
                    $this->db->query($sql);
                }
            }

            return true;
        }

    }

    public function updateScheduleOrder($uid = null, $stamp = 0, $orderNo = 0, $scheduleIdx = null)
    {
        if ($orderNo && ($scheduleIdx || ($uid && $stamp))) {
            $orderNo = $this->db->escape($orderNo);

            if ($scheduleIdx) {
                $scheduleIdx = $this->db->escape($scheduleIdx);
                $sql = "UPDATE wm_subscription_schedule_list
                                SET
                                    orderNo='{$orderNo}'
                               WHERE idx='{$scheduleIdx}'";
            } else {
                $uid = $this->db->escape($uid);
                $stamp = $this->db->escape($stamp);
                $sql = "UPDATE wm_subscription_schedule_list
                                SET
                                    orderNo='{$orderNo}'
                               WHERE uid='{$uid}' AND schedule_stamp='{$stamp}'";
            }

            if ($this->db->query($sql)) {
                if ($scheduleIdx) {
                    return $scheduleIdx;
                }
                $sql = "SELECT idx FROM wm_subscription_schedule_list WHERE uid='{$uid}' AND schedule_stamp='{$stamp}'";
                $row = $this->db->fetch($sql);
                return $row['idx'];
            }
        }
    }

    public function rollbackSubscription($uid = null)
    {
        if ($uid) {
            try {

                $this->db->begin_tran();
                $sql = "DELETE FROM wm_subscription_apply WHERE uid='{$uid}'";
                $this->db->query($sql);

                $sql = "DELETE FROM wm_subscription_apply_items WHERE uid='{$uid}'";
                $this->db->query($sql);

                $sql = "DELETE FROM wm_subscription_schedule_list WHERE uid='{$uid}'";
                $this->db->query($sql);
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
            }
        }
    }

    /* 정기결제 등록 처리 END */

    public function setUid($uid)
    {
        $this->uid = $uid;
        return $this;
    }

    public function setGoods($goodsNo = null)
    {
        $this->goodsNo = $goodsNo;
        return $this;
    }

    public function setCartInfo($cartInfo = [])
    {
        $this->cartInfo = $cartInfo;
        return $this;
    }

    public function setTotalDeliveryCharge($totalDeliveryCharge) {
        $this->totalDeliveryCharge = $totalDeliveryCharge;
        return $this;
    }

    public function setScheduleEa($ea = 1)
    {
        $this->scheduleEa = $ea;
        return $this;
    }

    public function setPeriod($period = null)
    {
        $this->period = $period;
        return $this;
    }

    public function setStamp($stamp = 0)
    {
        $this->stamp = $stamp;
        return $this;
    }

    public function setDate($date = null)
    {
        if (empty($date))
            $date = date("Ymd");

        $stamp = strtotime($date);
        $this->stamp = $stamp;
        return $this;
    }

    public function setPrice($price = 0)
    {
        $this->price = $price;
        return $this;
    }

    public function getGoodsCfg($goodsNo = null)
    {
        $req = Request::request()->all();

        $cfg = $this->getCfg();
        if (empty($goodsNo))
            $goodsNo = $this->goodsNo;
        if ($goodsNo) {
            $sql = "SELECT * FROM wm_subscription_goods_config WHERE goodsNo='{$goodsNo}'";
            if ($tmp = $this->db->fetch($sql)) {
                if ($tmp['discount'])
                    $cfg['discount'] = explode(",", $tmp['discount']);
            }
        }

        $discount_amount = [];
        if ($this->price) {
            if ($cfg['discount']) {

                /* 웹앤모바일 튜닝 상품할인 + 정기배송 할인설정 처리 [시작] */
                $goods = new Goods();
                $subCart = \App::load("\\Component\\Subscription\\Cart");
                $memberGroup = \App::load("\\Component\\Member\\MemberGroup");
                $goodsView = $goods->getGoodsView($goodsNo);
                $memberGroupNo = \Session::get('member.groupSno');

                $truncGoods = \Globals::get('gTrunc.goods');

                $goodsPrice = 0;

                if ($req['mode'] == 'goodsBenefitCheck') {

                    foreach ($cfg['discount'] as $key => $val) {

                        $arrData = ['isScheduleList' => true, 'dc' => $val];

                        if (!gd_is_login()) $arrData['isGuest'] = true;

                        $cartList = $subCart->getCartList($req['cartSno'], null, $arrData);

                        $memberDcPrice = 0;
                        $goodsDcPrice = 0;
                        $goodsPrice = 0;

                        foreach ($cartList as $k => $v) {
                            foreach ($v as $k2 => $v2) {
                                foreach ($v2 as $k3 => $v3) {
                                    $memberDcPrice = $v3['price']['memberDcPrice'];
                                    $goodsDcPrice = $v3['price']['goodsDcPrice'];
                                    $goodsPrice = $v3['price']['goodsPrice'];
                                    $optionPrice = $v3['price']['optionPrice'];
                                }
                            }
                        }

                        $totalDiscount = $memberDcPrice + $goodsDcPrice;
                        $totalGoodsPrice = $goodsPrice + $optionPrice;
                        $discount_amount[$key] = gd_number_figure($totalDiscount, $truncGoods['unitPrecision'], $truncGoods['unitRound']);
                    }

                    $cfg['goodsPrice'] = $totalGoodsPrice;
                    $subCart->removeTempCart($req);

                } else {


                    if(!empty($memberGroupNo))
                        $groupData = $memberGroup->getGroup($memberGroupNo);

                    // 옵션가격이 있는 경우 옵션 가격까지 합산해서 계산한다
                    $optionPriceSum = 0;


                    if ($req['optionSno']) {
                        foreach ($req['optionSno'] as $key => $val) {
                            $optionData = $goods->getGoodsOptionInfo($val);
                            $optionPriceSum += $optionData['optionPrice'];
                        }
                    }

                    $this->price = $this->price + $optionPriceSum;

                    $goodsDcPercent = 0;

                    if ($goodsView['goodsDiscountGroup'] == 'group') {

                        // 그룹 별로 설정한 데이터 json decode
                        $memberGroupData = json_decode($goodsView['goodsDiscountGroupMemberInfo'], true);

                        // 그룹 별 할인 데이터 배열 변경처리
                        foreach ($memberGroupData['groupSno'] as $_index => $_value) {
                            $clearMemberGroupData[$_value]['goodsDiscount'] = $memberGroupData['goodsDiscount'][$_index];
                            $clearMemberGroupData[$_value]['goodsDiscountUnit'] = $memberGroupData['goodsDiscountUnit'][$_index];
                        }

                        // 변경된 배열의 키값에 자신의 그룹번호와 일치하는게 있을 때
                        if(!empty($memberGroupNo)){
                            if (array_key_exists($memberGroupNo, $clearMemberGroupData)) {
                                if ($clearMemberGroupData[$memberGroupNo]['goodsDiscountUnit'] == 'percent') {
                                    foreach ($cfg['discount'] as $k => $v) {
                                        $cfg['discount'][$k] += $clearMemberGroupData[$memberGroupNo]['goodsDiscount'];
                                    }
                                    $goodsDcPercent = $clearMemberGroupData[$memberGroupNo]['goodsDiscount'];
                                }
                            }
                        }
                    } else if ($goodsView['goodsDiscountGroup'] == 'member') { // 회원전용 할인인 경우
                        if ($goodsView['goodsDiscountUnit'] == 'percent') {
                            $goodsDcPercent = $goodsView['goodsDiscount'];
                            /*foreach ($cfg['discount'] as $k => $v) {
                                $cfg['discount'][$k] += $goodsView['goodsDiscount'];
                            }*/
                        }
                    } else if ($goodsView['goodsDiscountGroup'] == 'all') { // 전체설정인 경우
                        if ($goodsView['goodsDiscountUnit'] == 'percent') {
                            $goodsDcPercent = $goodsView['goodsDiscount'];
                            /*foreach ($cfg['discount'] as $k => $v) {
                                $cfg['discount'][$k] += $goodsView['goodsDiscount'];
                            }*/
                        }
                    }
                    /* 웹앤모바일 튜닝 상품할인 + 정기배송 할인설정 처리 [종료] */

                    $truncGoods = \Globals::get('gTrunc.goods');

                    foreach ($cfg['discount'] as $k => $v) {

                        $price = $this->price;

                        // 상품 할인 계산
                        // 상품 할인율(정기결제 할인율)
                        $productPercent = floatval($v);

                        // 상품 할인 금액
                        $productDiscount = $price * ($productPercent / 100);

                        // 절사처리
                        $productDiscount = floor($productDiscount / 10) * 10;

                        // 적용 후 금액
                        $afterProductPrice = $price - $productDiscount;

                        // 회원 등급 할인 계산
                        $gradePercent = 0;

                        $exceptBenefit = explode(STR_DIVISION, $goodsView['exceptBenefit']);

                        if (!in_array('add', $exceptBenefit) && (empty($groupData['dcLine']) || floatval($groupData['dcLine']) == 0.00))
                            $gradePercent = ($groupData['dcType'] === 'percent') ? floatval($groupData['dcPercent']) : ($groupData['dcPrice'] / $this->price) * 100;

                        // 회원등급 할인 금액
                        $gradeDiscount = $afterProductPrice * ($gradePercent / 100);

                        // 총 할인 금액
                        $totalDiscount = $productDiscount + $gradeDiscount;

                        /* 고도몰 절사 정책 적용 (truncGoods 구조 사용) */
                        $discount_amount[$k] = gd_number_figure($totalDiscount, $truncGoods['unitPrecision'], $truncGoods['unitRound']);
                    }
                }
            }
        }

        $cfg['discount_amount'] = $discount_amount;

        return $cfg;
    }

    public function getGoodsCfg_bak($goodsNo = null)
    {
        $cfg = $this->getCfg();
        if (empty($goodsNo))
            $goodsNo = $this->goodsNo;
        if ($goodsNo) {
            $sql = "SELECT * FROM wm_subscription_goods_config WHERE goodsNo='{$goodsNo}'";
            if ($tmp = $this->db->fetch($sql)) {
                if ($tmp['discount'])
                    $cfg['discount'] = explode(",", $tmp['discount']);
            }
        }

        $discount_amount = [];
        if ($this->price) {
            if ($cfg['discount']) {
                foreach ($cfg['discount'] as $k => $v) {
                    $discount_amount[$k] = round($this->price * $v / 1000) * 10;
                }
            }
        }
        $cfg['discount_amount'] = $discount_amount;
        return $cfg;
    }

    public function isSubscriptionGoods($goodsNo = null)
    {
        $sql = "SELECT isSubscription FROM " . DB_GOODS . " WHERE goodsNo='{$goodsNo}'";
        $row = $this->db->fetch($sql);

        if ($row['isSubscription'])
            return true;

    }

    /**
     * 정기결제 상품과 연결된 일반 상품 번호 조회 (역방향)
     * @param int $subscriptionGoodsNo 정기결제 상품 번호
     * @return int|null 연결된 일반 상품 번호
     */
    public function getLinkedRegularGoodsNo($subscriptionGoodsNo)
    {
        if (!$subscriptionGoodsNo) {
            return null;
        }

        $arrBind = [];
        $sql = "SELECT goodsNo FROM " . DB_GOODS .
               " WHERE linkedSubscriptionGoodsNo = ? AND delFl='n' LIMIT 1";
        $this->db->bind_param_push($arrBind, 'i', $subscriptionGoodsNo);
        $result = $this->db->query_fetch($sql, $arrBind, false);

        return $result ? $result['goodsNo'] : null;
    }

    public function getScheduleSummaryByUid($uid = null)
    {
        if (!$uid) {
            return ['count' => 0, 'first_stamp' => null, 'last_stamp' => null];
        }

        $uid = $this->db->escape($uid);
        $sql = "SELECT COUNT(*) as cnt,
                       MIN(schedule_stamp) as first_stamp,
                       MAX(schedule_stamp) as last_stamp
                  FROM wm_subscription_schedule_list
                 WHERE uid='{$uid}'";
        $row = $this->db->fetch($sql);

        return [
            'count' => (int)$row['cnt'],
            'first_stamp' => $row['first_stamp'],
            'last_stamp' => $row['last_stamp'],
        ];
    }

    public function getScheduleListByUid($uid = null)
    {
        $list = $cartSno = [];

        if ($uid) {
            $obj = App::load(\Component\Subscription\Subscription::class);
            $order = App::load(\Component\Order\Order::class);
            $cart = App::load(\Component\Subscription\Cart::class);
            $info = $obj->setUid($uid)
                ->setMode("subscriptionInfo")
                ->get();

            $postValue = $order->setOrderDataValidation($info, true);
            $cart->deliveryFree = $postValue['deliveryFree'];
            $postValue['settleKind'] = 'pc';
            unset($postValue['items']);
            $address = str_replace(' ', '', $postValue['receiverAddress'] . $postValue['receiverAddressSub']);

            /* 임시장바구니에 데이터 넣기 */
            $items = $info['items'];
            $sql = "DELETE FROM wm_subscription_cart3 WHERE memNo='{$info['memNo']}' AND isTemp='2'";
            $this->db->query($sql);


            $goods = \App::load("\\Component\\Goods\\Goods");
            foreach ($items as $it) {

                $goodsInfo = $goods->getGoodsInfo($it['goodsNo']);

                $sql = "SELECT delFl FROM " . DB_GOODS . " WHERE goodsNo = '" . $it['goodsNo'] . "'";
                $goodsFl = $this->db->fetch($sql);

                if ($goodsFl['delFl'] == 'n') {
                    $sql = "INSERT INTO wm_subscription_cart3 
                               SET 
                                   memNo='{$info['memNo']}', 
                                   goodsNo='{$it['goodsNo']}',
                                   optionSno='{$it['optionSno']}',
                                   goodsCnt='{$it['goodsCnt']}',
                                   addGoodsNo='{$it['addGoodsNo']}',
                                   addGoodsCnt='{$it['addGoodsCnt']}',
                                   optionText='{$it['optionText']}',
                                   deliveryCollectFl='{$it['deliveryCollectFl']}',
                                   deliveryMethodFl='{$it['deliveryMethodFl']}',
                                   regStamp='" . time() . "', 
                                   isTemp='2'";
                    if ($this->db->query($sql)) {
                        $cartSno[] = $this->db->insert_id();
                    }
                }
            }

            $hList = $this->getHolidayList();
            $cfg = $this->getCfg();

            $deliveryDays = $cfg['deliveryDays'] ? $cfg['deliveryDays'] : 0;
            $smsDays = $cfg['smsDays'] ? $cfg['smsDays'] : 0;

            $sql = "SELECT * FROM wm_subscription_schedule_list WHERE uid='{$uid}' ORDER BY schedule_stamp asc";

            $status = $obj->getOrderStatusList();


            if ($tmp = $this->db->query_fetch($sql)) {

                $no = 0;
                foreach ($tmp as $t) {

                    $cart->totalSettlePrice = $cart->totalGoodsPrice = $cart->totalDeliveryCharge = $cart->totalGoodsDcPrice = $cart->totalSumMemberDcPrice = $cart->totalCouponGoodsDcPrice = 0;
                    $stamp = $t['schedule_stamp'];
                    $delivery_stamp = $stamp + (60 * 60 * 24 * $deliveryDays);

                    // 제외 날짜 목록: 공휴일 + 공휴일 다음날
                    $excludeStamps = [];
                    foreach ($hList as $h) {
                        $excludeStamps[$h['stamp']] = true;
                        $excludeStamps[$h['stamp'] + 60 * 60 * 24] = true;
                    }

                    // 일, 월, 공휴일, 공휴일 다음날 제외
                    $safetyLimit = 30;
                    while ($safetyLimit-- > 0) {
                        $yoil = date("w", $delivery_stamp);
                        if ($yoil == 0) {
                            $delivery_stamp += 60 * 60 * 24 * 2;
                        } else if ($yoil == 1) {
                            $delivery_stamp += 60 * 60 * 24;
                        } else if (isset($excludeStamps[$delivery_stamp])) {
                            $delivery_stamp += 60 * 60 * 24;
                        } else {
                            break;
                        }
                    }

                    $t['delivery_stamp'] = $delivery_stamp;
                    $sms_stamp = $stamp - (60 * 60 * 24 * $smsDays);
                    $t['sms_stamp'] = $sms_stamp;
                    $isCal = true;
                    if ($t['orderNo']) {
                        $row = $this->db->fetch("SELECT COUNT(*) as cnt FROM " . DB_ORDER . " WHERE orderNo='{$t['orderNo']}'");
                        $od = [];

                        if ($row['cnt'] > 0) {

                            $sql = "SELECT * FROM " . DB_ORDER . " WHERE orderNo='{$t['orderNo']}'";
                            if ($tmp = $this->db->fetch($sql)) {
                                $od = $tmp;
                                $s = substr($od['orderStatus'], 0, 1);
                                if (!in_array($s, ['p', 'g', 'd', 's']))
                                    $no--;


                                /* webnmobile tuning 2023-10-10, 정기결제 전체결제취소를 위해서 데이터추가 조회 [시작] */
                                $sql = "SELECT sno, goodsCnt, orderStatus FROM " . DB_ORDER_GOODS . " WHERE orderNo = '{$t['orderNo']}'";
                                $orderGoodsData = $this->db->query_fetch($sql);

                                $od['orderStatusStr'] = $status[$orderGoodsData[0]['orderStatus']];


                                /* webnmobile tuning 2023-10-10, 정기결제 전체결제취소를 위해서 데이터추가 조회 [종료] */
                            }
                        }

                        /* webnmobile tuning 2023-10-10, 정기결제 전체결제취소를 위해서 데이터추가 조회 [시작] */
                        $t['orderSnos'] = $orderGoodsData;
                        /* webnmobile tuning 2023-10-10, 정기결제 전체결제취소를 위해서 데이터추가 조회 [종료] */
                        $t['order'] = $od;

                        $isCal = false;
                    }

                    if ($no >= 0) {
                        $discount = $cfg['discount'][$no] ? $cfg['discount'][$no] : $cfg['discount'][count($cfg['discount']) - 1];
                    } else {
                        $discount = 0;
                    }

                    $t['discount'] = $discount;

                    if ($isCal) {
                        $cartData = $cart->getCartList($cartSno, $address, $postValue, true, $info['memNo'], $discount, true);

                        $orderPrice = $cart->setOrderSettlePayCalculation($postValue);

                        $memberDcPrice = 0;
                        $goodsDcPrice = 0;
                        foreach ($cartData as $k => $v) {
                            foreach ($v as $k2 => $v2) {
                                foreach ($v2 as $k3 => $v3) {
                                    $memberDcPrice = $v3['price']['memberDcPrice'];
                                    $goodsDcPrice = $v3['price']['goodsDcPrice'];

                                    $t['dcInfo']['memberDcPrice'] = $memberDcPrice;
                                    $t['dcInfo']['goodsDcPrice'] = $goodsDcPrice;
                                    $t['dcInfo']['totalDcPrice'] = $memberDcPrice + $goodsDcPrice;
                                }
                            }
                        }

                        $orderPrice['totalDc'] = $orderPrice['totalGoodsDcPrice'] + $orderPrice['totalSumMemberDcPrice'] + $orderPrice['totalCouponGoodsDcPrice'];
                        $t['orderPrice'] = $orderPrice;
                    }

                    $no++;
                    $list[] = $t;
                }
            }

        }

        return $list;
    }

    public function get()
    {

        switch ($this->mode) {
            case "getCardInfo" :
                $info = [];
                if ($this->idxCard) {
                    $sql = "SELECT a.*, b.memNm, b.memId FROM wm_subscription_cards AS a 
                                    LEFT JOIN " . DB_MEMBER . " AS b ON a.memNo = b.memNo 
                                    WHERE a.idx='" . $this->idxCard . "'";
                    if ($tmp = $this->db->fetch($sql)) {
                        $tmp['payKey'] = $this->decrypt($tmp['payKey']);
                        $info = $tmp;
                    }
                }

                return $info;
                break;
            case "getPayKey" :
                if ($this->idxCard) {
                    $sql = "SELECT payKey FROM wm_subscription_cards WHERE idx='" . $this->idxCard . "'";
                    if ($tmp = $this->db->fetch($sql))
                        return $this->decrypt($tmp['payKey']);
                }
                break;
            case "getGoodsCfg" :
                return $this->getGoodsCfg();
                break;
            case "getScheduleList" :
                $truncPolicy = \Globals::get('gTrunc.goods');
                $cfg = $this->getCfg();
                $scheduleEa = $this->scheduleEa ? $this->scheduleEa : $cfg['deliveryEa'][0];
                $period = $this->period ? $this->period : $cfg['period'][0];
                $stamp = $this->stamp ? $this->stamp : 0;
                $list = $priceList = [];

                $goods = App::load("\\Component\\Goods\\Goods");
                $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
                $memberGroup = \App::load("\\Component\\Member\\MemberGroup");
                $subCart = \App::load("\\Component\\Subscription\\Cart");
                $memberGroupNo = \Session::get('member.groupSno');
                $groupData = $memberGroup->getGroup($memberGroupNo);

                if ($this->cartInfo) {
                    foreach ($this->cartInfo as $keys => $values) {
                        foreach ($values as $key => $value) {
                            foreach ($value as $k => $v) {

                                $goodsPrice = $v['price']['goodsPrice'] + $v['price']['optionPrice'];
                                $priceList[] = [
                                    "goodsPrice" => $goodsPrice,
                                    'goodsCnt' => $v['goodsCnt'],
                                    'goodsNo' => $v['goodsNo'],
                                    'exceptBenefit' => $v['exceptBenefit'],
                                    'goodsDcPrice' => $v['price']['goodsDcPrice'],
                                    'memberDcPrice' => $v['price']['memberDcPrice'],
                                    'goodsDiscountFl' => $v['goodsDiscountInfo']['goodsDiscountFl'],
                                    'goodsDiscount' => $v['goodsDiscountInfo']['goodsDiscount'],
                                    'goodsDiscountUnit' => $v['goodsDiscountInfo']['goodsDiscountUnit'],
                                    'fixedGoodsDiscount' => $v['fixedGoodsDiscount']
                                ];
                            }
                        }
                    }
                }

                if ($tmp = $this->getScheduleList($scheduleEa, $stamp, $period)) {

                    foreach ($tmp as $k => $v) {
                        $dc = $cfg['discount'][$k] ? $cfg['discount'][$k] : $cfg['discount'][count($cfg['discount']) - 1];

                        $data = $v;

                        /*if ($priceList) {
                            $discount = 0;

                            foreach ($priceList as $li) {

                                $exceptBenefit = explode(STR_DIVISION, $li['exceptBenefit']);

                                $price = $li['goodsPrice'];
                                $cnt   = $li['goodsCnt'];

//                                $tmp = $subCart->getGoodsDcData2($li['goodsDiscountFl'], $li['goodsDiscount'], $li['goodsDiscountUnit'], $li['goodsCnt'], $li['goodsPrice'], $li['fixedGoodsDiscount'], 'group');

                                // 등급할인 금액 초기화
                                $gradeDiscount = 0;

                                // 상품 개별 할인율 계산
                                // 상품 할인율 = $dc(회차 할인율) + $goodsDcPercent(개별 상품 할인율)
                                $productPercent = floatval($dc + $goodsDcPercent);

                                // 재시작
                                // 정기결제 할인율 먼저 설정
                                $productPercent = $dc;

                                // 상품 할인 금액 (원 단위)
                                $productDiscount = $price * ($productPercent / 100);

                                // 절사
                                $productDiscount = floor($productDiscount / 10) * 10;

                                // 할인 적용된 상품 가격
                                $afterProductPrice = $price - $productDiscount;


                                if (!in_array('add', $exceptBenefit)) {

                                    // 2) 회원 등급 할인 계산
                                    $gradePercent = floatval($groupData['dcPercent']); // 등급 할인율 (%)

                                    // 회원등급 할인 금액
                                    $gradeDiscount = $afterProductPrice * ($gradePercent / 100);
                                }

                                // 최종 할인 금액 합산
                                $totalDiscountForGoods = ($productDiscount + $gradeDiscount) * $cnt;
                                $discount += $totalDiscountForGoods;

                                $truncGoods = \Globals::get('gTrunc.goods');
                                $discount = gd_number_figure($discount, $truncGoods['unitPrecision'], $truncGoods['unitRound']);

                                if(\Request::getRemoteAddress() == '182.216.219.157') {
//                                    $discount = gd_number_figure(($li['goodsDcPrice'] + $li['memberDcPrice']), $truncGoods['unitPrecision'], $truncGoods['unitRound']);
                                }
                            }


                            $data['price'] = $this->price + $this->totalDeliveryCharge;
                            $data['discount'] = $discount;
                            $data['rate'] = $dc;
                        } else {

                            //$discount = round($this->price * $dc / 1000) * 10;
                            $discount = $this->price * $dc / 100;
                            if ($truncPolicy['unitPrecision'] == 0.1) {
                                $discount = $truncPolicy['unitRound']($discount);
                            } elseif ($truncPolicy['unitPrecision'] == 1) {
                                $discount = ($truncPolicy['unitRound']($discount * 0.1) * 10);
                            } elseif ($truncPolicy['unitPrecision'] == 10) {
                                $discount = ($truncPolicy['unitRound']($discount * 0.01) * 100);
                            } elseif ($truncPolicy['unitPrecision'] == 100) {
                                $discount = ($truncPolicy['unitRound']($discount * 0.001) * 1000);
                            } elseif ($truncPolicy['unitPrecision'] == 1000) {
                                $discount = ($truncPolicy['unitRound']($discount * 0.0001) * 10000);
                            }
                            $data['price'] = $this->price;
                            $data['discount'] = $discount;
                            $data['rate'] = $dc;
                        }*/


                        /** =============================================================================
                        * 🚀 webnmobile - 튜닝 2025-12-3, 수, 17:3 🚀
                        * -----------------------------------------------------------------------------
                        * 💡️ [START] 장바구니, 주문페이지 회차별 안내 금액 장바구니에서 계산된 내용으로 처리
                        * ============================================================================= */
                        $arrData = ['isScheduleList' => true, 'dc' => $dc];
                        $cartList = $subCart->getCartList(null, null, $arrData);

                        if(\Request::getRemoteAddress() == '182.216.219.157') {
//                            gd_Debug($cartList);
                        }

                        $memberDcPrice = 0;
                        $goodsDcPrice = 0;

                        foreach ($cartList as $key => $val) {
                            foreach ($val as $key2 => $val2) {
                                foreach ($val2 as $key3 => $val3) {
                                    $memberDcPrice = $val3['price']['memberDcPrice'];
                                    $goodsDcPrice = $val3['price']['goodsDcPrice'];
                                }
                            }
                        }

                        $data['price'] = $this->price + $this->totalDeliveryCharge;
                        $data['discount'] = $memberDcPrice + $goodsDcPrice;
                        /* ============================================================================
                        * 💡 [END] 장바구니, 주문페이지 회차별 안내 금액 장바구니에서 계산된 내용으로 처리
                        =============================================================================== */


                        $list[] = $data;
                    }
                }

                return $list;
                break;
            case "subscriptionInfo" :
                $info = [];
                if ($this->uid) {
                    if ($tmp = $this->db->fetch("SELECT * FROM wm_subscription_apply WHERE uid='" . $this->uid . "'")) {
                        $info = $tmp;
                        $items = [];
                        $sql = "SELECT * FROM wm_subscription_apply_items WHERE uid='" . $this->uid . "'";
                        if ($tmps = $this->db->query_fetch($sql))
                            $items = $tmps;

                        $info['items'] = $items;
                    }
                }
                return $info;
                break;
            /* 일괄처리 START */
            case "batch_pay_list" :
                $list = array();
                if ($this->stamp) {
                    $stamp = strtotime(date("Ymd", $this->stamp));
                    $estamp = $stamp + (60 * 60 * 24);
                    $sql = "SELECT a.*,b.memNo FROM wm_subscription_schedule_list a INNER JOIN wm_subscription_apply b ON a.uid=b.uid  WHERE a.schedule_stamp >= {$stamp} AND a.schedule_stamp < {$estamp} AND a.isPayed='0' AND a.isStop='0'  ORDER BY a.idx";
                    if ($tmp = $this->db->query_fetch($sql))
                        $list = $tmp;
                }

                return $list;
                break;
            case "batch_sms_list" :
                $list = array();
                if ($this->stamp) {
                    $cfg = $this->getCfg();
                    $smsDays = $cfg['smsDays'] ? $cfg['smsDays'] : 0;
                    $stamp = strtotime(date("Ymd", $this->stamp));
                    $stamp += (60 * 60 * 24 * $smsDays);
                    $estamp = $stamp + (60 * 60 * 24);
                    $sql = "SELECT a.*,b.memNo FROM wm_subscription_schedule_list a INNER JOIN wm_subscription_apply b ON a.uid=b.uid WHERE a.schedule_stamp >= {$stamp} AND a.schedule_stamp < {$estamp} AND a.isPayed='0' AND a.isStop='0' AND a.smsStamp='0'  ORDER BY a.idx";
                    if ($tmp = $this->db->query_fetch($sql))
                        $list = $tmp;
                }

                return $list;
                break;
            case "batch_auto_extend_list" :
                $list = [];
                $stamp = strtotime(date("Ymd"));
                $sql = "SELECT uid, deliveryEa, period FROM wm_subscription_apply WHERE autoExtend='1' ORDER BY regStamp asc";
                if ($tmp = $this->db->query_fetch($sql)) {
                    foreach ($tmp as $li) {
                        $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_schedule_list WHERE uid='{$li['uid']}' AND schedule_stamp >= {$stamp} AND isPayed='0' AND isStop='0'";
                        $row = $this->db->fetch($sql);

                        if (empty($row['cnt'])) {
                            $sql = "SELECT * FROM wm_subscription_schedule_list WHERE uid='{$li['uid']}' ORDER BY schedule_stamp desc";
                            $ro = $this->db->fetch($sql);
                            $list[] = $ro;

                        }
                    }
                }
                return $list;
                break;
            /* 일괄처리 END */
        }
    }

    public function pay_procss($idx)
    {
        if ($idx)
            $this->pay($idx, $this->boolean);
    }

    public function sms_procss($idx)
    {
        $cfg = $this->getCfg();
        if ($idx) {
            if (!$info = $this->getSubscription($idx))
                return false;

            $this->updatePayPrice($info['uid']);
            $info = $this->getSubscription($idx);

            $smsTemplate = $cfg['smsTemplate'];
            $info['date'] = date('Y.m.d', $info['schedule_stamp']);
            if ($info['orderCellPhone']) {
                if ($this->sendSms($info['orderCellPhone'], $smsTemplate, $info)) {


                    $sql = "UPDATE wm_subscription_schedule_list SET smsStamp='" . time() . "' WHERE idx='" . $idx . "'";
                    $this->db->query($sql);
                    return true;
                }

            }
        }
    }

    public function process()
    {
        $cfg = $this->getCfg();
        switch ($this->mode) {
            /* 일괄처리 START */
            case "batch_pay" :
                if ($this->idx)
                    return $this->pay($this->idx, $this->boolean);
                break;
            case "batch_sms" :
                if ($this->idx) {
                    if (!$info = $this->getSubscription($this->idx))
                        return false;

                    $this->updatePayPrice($info['uid']);
                    $info = $this->getSubscription($this->idx);

                    $smsTemplate = $cfg['smsTemplate'];
                    $info['date'] = date('Y.m.d', $info['schedule_stamp']);
                    if ($info['orderCellPhone']) {
                        if ($this->sendSms($info['orderCellPhone'], $smsTemplate, $info)) {
                            $sql = "UPDATE wm_subscription_schedule_list SET smsStamp='" . time() . "' WHERE idx='" . $this->idx . "'";
                            $this->db->query($sql);
                            return true;
                        }

                    }
                }
                break;
            case "batch_auto_extend" :
                if ($this->idx) {
                    if (!$info = $this->getSubscription($this->idx))
                        return false;

                    return $this->extendSchedule($info['uid'], 1, $info['period']);
                }
                break;
            /* 일괄처리 END */
        }
    }

    public function updatePayPrice($uid = null)
    {
        if ($uid) {
            if ($list = $this->getScheduleListByUid($uid)) {
                foreach ($list as $li) {
                    $settlePrice = $li['order']['settlePrice'] ? $li['order']['settlePrice'] : $li['orderPrice']['settlePrice'];
                    $sql = "UPDATE wm_subscription_schedule_list SET payPrice='{$settlePrice}' WHERE idx='{$li['idx']}'";
                    $this->db->query($sql);
                }
            }

        }
    }

    /* SMS 전송 처리 */
    public function sendSms($mobile, $contents, $changeCode = array())
    {
        $cfg = $this->getCfg();
        $bool = false;
        $smsPoint = Sms::getPoint();
        if ($smsPoint >= 1) {
            foreach ($changeCode as $k => $v) {
                if (is_numeric($v))
                    $v = number_format($v);

                $contents = str_replace("{{$k}}", "{$v}", $contents);
            }

            $adminSecuritySmsAuthNumber = Otp::getOtp(8);
            $receiver[0]['cellPhone'] = $mobile;
            $smsSender = \App::load('Component\\Sms\\SmsSender');
            $smsSender->setSmsPoint($smsPoint);

            if (mb_strlen($contents, 'euc-kr') > 90) {
                $smsSender->setMessage(new LmsMessage($contents));
            } else {
                $smsSender->setMessage(new SmsMessage($contents));
            }

            $smsSender->validPassword($cfg['smsPass']);
            $smsSender->setSmsType('user');
            $smsSender->setReceiver($receiver);
            $smsSender->setLogData(['disableResend' => false]);
            $smsSender->setContentsMask([$adminSecuritySmsAuthNumber]);
            $smsResult = $smsSender->send();
            $smsResult['smsAuthNumber'] = $adminSecuritySmsAuthNumber;

            if ($smsResult['success'] === 1)
                $bool = true;
        }

        return $bool;
    }

    // 정기결제 주문 존재 여부 확인
    public function chkSubscriptionOrder($orderNo)
    {
        $row = $this->db->fetch("select * from wm_subscription_schedule_list where orderNo='{$orderNo}'");
        if(!empty($row)) {
            return true;
        }

        return false;
    }

    // 회원이 마이페이지에서 주문 전체 환불 신청 시 정기결제 주문인 경우 (/module/Component/Order/Order.php - processAutoPgCancel)
    public function refundSubscriptionOrder($bundleData, $userHandleSno)
    {
        //주문 정보 가져오기
        $order = \App::load('\\Component\\Order\\OrderAdmin');
        $orderGoodsInfo = $order->getOrderGoods($bundleData['orderNo'], null, null, null, null, ['memNo']);

        foreach ($orderGoodsInfo as $key => $value) {
            $orderGoods[$value['sno']] = $value;

            // 이마트 보안취약점 요청사항 (사용자 환불신청시 회원 유효성 검증)
            if ($value['memNo'] != \Session::get('member.memNo')) {
                return false;
            }
        }

        //주문 전체건 환불인지 확인
        $oriGoodsCnt = 0;
        $userHandleGoodsCnt = 0;
        foreach ($orderGoods as $key => $value) {
            $oriGoodsCnt += $value['goodsCnt'];
            if (in_array($key, $bundleData['orderGoodsNo'])) {
                $userHandleGoodsCnt += $bundleData['claimGoodsCnt'][$key];
            }
        }

        //전체 상품인지 확인
        if ($oriGoodsCnt != $userHandleGoodsCnt) {
            return false;
        }

        //주문상태 확인
        foreach ($orderGoods as $key => $value) {
            if (substr($value['orderStatus'], 0, 1) != 'p') {
                return false;
            }
        }

        //자동으로 일단 승인 하기
        foreach ($bundleData['orderGoodsNo'] as $key => $value) {
            $statusCheck[] = $bundleData['orderNo'];
            $statusCheck[] = $value;
            $statusCheck[] = $userHandleSno[$value];
            $statusCheck[] = $bundleData['claimGoodsCnt'][$value];
            $statusCheck[] = $bundleData['claimGoodsCnt'][$value];
            $userHandle2Process['statusCheck'][] = implode(INT_DIVISION, $statusCheck);
            unset($statusCheck);
        }

        $userHandle2Process['mode'] = 'user_claim_handle_accept';
        $userHandle2Process['statusMode'] = 'r';
        $userHandle2Process['adminHandleReason'] = '자동 환불';
        $orderAdmin = \App::load('\\Component\\Order\\OrderAdmin');
        $orderAdmin->approveUserHandle($userHandle2Process, 'y', true);

        // 환불 하려는 상품이 주문 당시 정보와 동일할 때 주문 취소 처리
        $cancelFl = $this->cancel($bundleData['orderNo'], false, $bundleData['userHandleDetailReason']);
        if ($cancelFl) {

            $cnt = 0;
            foreach ($orderGoods as $key => $value) {

                $arrValue = null;
                $arrValue[] = "orderStatus='r3'";

                $sql = "update es_orderGoods set " . implode(',', $arrValue);
                $sql .= " where sno='{$value['sno']}'";
                $result = $this->db->query($sql);
                if ($result) {
                    $cnt++;
                }
            }

            if ($cnt == count($orderGoods)) {
                return ['msg' => 'ok'];
            }
        }

        return false;
    }

    public function setMemberDcPrice($cartData, $postValue) {

        $totalSubSettlePrice = 0;

        foreach($cartData as $key => $val) {
            foreach($val as $key2 => $val2) {
                foreach($val2 as $key3 => $val3) {
                    $totalSubSettlePrice += ($val3['price']['goodsPriceSum'] + $val3['price']['optionPriceSum'] + $val3['addGoodsPriceSum']);
                }
            }
        }

        $estimateRate = 0;

        $member = \App::load("\\Component\\Member\\Member");
        $memberGroup = \App::load("\\Component\\Member\\MemberGroup");

        $memberInfo = $member->getMemberInfo($postValue['memNo']);

        // 회원 주문 수에 대한 추가 할인 내용이 있는지 확인

        $groupInfo = $memberGroup->getGroupViewToArray($memberInfo['groupSno']);


        if ($groupInfo['dcPercent']) {
            $minusPrice = $totalSubSettlePrice * ($groupInfo['dcPercent'] / 100);
        }

        return $minusPrice;
    }

    /**
     * 장바구니 정보에 firstDelivery 자동 계산 (정기배송 상품용)
     *
     * @param array &$cartInfo 장바구니 정보 배열 (참조)
     * @return void
     */
    public function calculateFirstDeliveryForCart(&$cartInfo)
    {
        if (empty($cartInfo)) {
            return;
        }

        $firstDeliveryComponent = \App::load('\\Component\\Wm\\FirstDelivery');

        foreach ($cartInfo as $sKey => &$supplier) {
            foreach ($supplier as $dKey => &$delivery) {
                foreach ($delivery as $gKey => &$item) {
                    // 일반 상품인 경우에만 처리
                    if ($item['goodsType'] == 'goods') {
                        $goodsNo = $item['goodsNo'];

                        // 해당 상품의 firstDelivery 설정 조회
                        $firstData = $firstDeliveryComponent->getFirstDelivery($goodsNo);

                        // yoil이 설정된 상품만 처리 (첫배송 활성화 상품)
                        if (is_array($firstData) && !empty($firstData['yoil'])) {
                            // 다음 배송 가능일 계산 (Component 전달하여 중복 로드 방지)
                            $nextDeliveryDate = $this->calculateNextFirstDeliveryDate($firstData, $firstDeliveryComponent);

                            if ($nextDeliveryDate) {
                                // YYYYMMDD 형식으로 저장
                                $item['firstDelivery'] = date('Ymd', strtotime($nextDeliveryDate));
                            }
                        }
                    }
                }
            }
        }

        unset($supplier, $delivery, $item); // 참조 해제
    }

    /**
     * 다음 배송 가능일 계산 (새벽배송 기준)
     *
     * @param array $firstData wm_firstDelivery 테이블 데이터
     * @param object|null $firstDeliveryComponent FirstDelivery Component 객체 (선택)
     * @return string|null 다음 배송 가능일 (Y-m-d 형식) 또는 null
     */
    private function calculateNextFirstDeliveryDate($firstData, $firstDeliveryComponent = null)
    {
        if (empty($firstData['yoil'])) {
            return null;
        }

        // 1. 공휴일 목록 가져오기 (FirstDelivery Component 활용)
        if (!$firstDeliveryComponent) {
            $firstDeliveryComponent = \App::load('\\Component\\Wm\\FirstDelivery');
        }

        $year = date('Y');
        $currentMonth = date('n');
        $holidayList = $firstDeliveryComponent->holiday($year);

        // 12월인 경우 다음 연도 공휴일도 조회 (연도 경계 문제 해결)
        if ($currentMonth >= 12) {
            $nextYear = $year + 1;
            $nextYearHolidays = $firstDeliveryComponent->holiday($nextYear);
            if ($nextYearHolidays) {
                $holidayList = array_merge($holidayList, $nextYearHolidays);
            }
        }

        // 2. 배송 가능 요일 파싱 (예: "mon,wed,fri,sat,sun")
        $deliveryDays = explode(',', $firstData['yoil']);
        $dayMap = [
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3,
            'thu' => 4, 'fri' => 5, 'sat' => 6
        ];

        $deliveryDayNumbers = [];
        foreach ($deliveryDays as $day) {
            $day = trim($day);
            if (isset($dayMap[$day])) {
                $deliveryDayNumbers[] = $dayMap[$day];
            }
        }

        if (empty($deliveryDayNumbers)) {
            return null;
        }

        // 3. 오늘 요일 기준 대기일 계산 (yoilNextDay 지원)
        $dateW = date('w', time());
        $waitDays = 0;

        if (!empty($firstData['yoilNextDay'])) {
            $first_yoilNextDay = explode(',', $firstData['yoilNextDay']);
            $yoil_NextDay = [];
            foreach ($first_yoilNextDay as $key => $value) {
                if ($key == '6') {
                    $yoil_NextDay[0] = $value;
                } else {
                    $yoil_NextDay[$key + 1] = $value;
                }
            }
            ksort($yoil_NextDay);
            $waitDays = isset($yoil_NextDay[$dateW]) ? (int)$yoil_NextDay[$dateW] : 0;
        }

        // 4. 시작일부터 최대 60일 동안 검색
        $maxSearchDays = 60;
        for ($i = $waitDays; $i < $maxSearchDays; $i++) {
            $checkDate = strtotime("+{$i} days");
            $checkYoil = date('w', $checkDate);
            $checkDateStr = date('Y-m-d', $checkDate);

            if (in_array($checkYoil, $deliveryDayNumbers)) {
                $isHoliday = false;
                if ($holidayList) {
                    foreach ($holidayList as $holidayTimestamp => $holidayData) {
                        if ($checkDate == $holidayTimestamp) {
                            $isHoliday = true;
                            break;
                        }
                    }
                }

                if (!$isHoliday) {
                    return $checkDateStr;
                }
            }
        }

        // 60일 내에 배송 가능일을 찾지 못한 경우 null 반환
        return null;
    }

    public function stopSubscription($uid) {

        // 자동연장 처리 제거
        $updateAutoExtendSql = "UPDATE wm_subscription_apply SET autoExtend = 0 WHERE uid = '" . $uid . "'";
        $this->db->query($updateAutoExtendSql);

        // 모든 스케줄 중단으로 변경
        $stopScheduleSql = "UPDATE wm_subscription_schedule_list SET isStop = 1 WHERE uid = '" . $uid . "' and isPayed='0'";
        $this->db->query($stopScheduleSql);
    }
}