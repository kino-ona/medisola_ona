<?php

namespace Component\Subscription;

use App;
use Request;
use Component\Member\Member;
use Component\Order\Order;
use Exception;

class SubscriptionPgInicis extends \Component\Subscription\SubscriptionPg
{
    /* PG 설정 추출 */
    public function getPgCfg()
    {
        $cfg = $this->getCfg();
        if ($cfg['useMode'] != 'real') {
            $cfg['mid'] = 'INIBillTst';
            $cfg['signKey'] = "SU5JTElURV9UUklQTEVERVNfS0VZU1RS";
            $cfg['lightKey'] = "b09LVzhuTGZVaEY1WmJoQnZzdXpRdz09";
        }

        $SignatureUtil = App::load("\Component\Subscription\Inicis\INIStdPayUtil");
        $cfg['timestamp'] = $SignatureUtil->getTimestamp();
        $cfg['mKey'] = hash('sha256', $cfg['signKey']);
        $cfg['modulePath'] = dirname(__FILE__) . "/../../../subscription_module/inicis";
        $cfg['pg_gate'] = "subscription/inicis/pg_gate.html";
        return $cfg;
    }

    public function getPgSign($uid, $price = 0, $timestamp = 0)
    {
        $data = [];
        $cfg = $this->getPgCfg();
        $timestamp = $timestamp ? $timestamp : $cfg['timestamp'];
        if ($this->isMobile)
            $params = $cfg['mid'] . $uid . $timestamp . $cfg['lightKey'];
        else
            $params = "oid=" . $uid . "&price=" . $price . "&timestamp=" . $timestamp;

        return hash("sha256", $params);
    }

    //--- 카드사 코드
    public function getPgCards()
    {
        $pgCards = array(
            '01' => '하나(외환)카드',
            '03' => '롯데카드',
            '04' => '현대카드',
            '06' => '국민카드',
            '11' => 'BC카드',
            '12' => '삼성카드',
            '13' => '(구)LG카드',
            '14' => '신한카드',
            '15' => '한미카드',
            '16' => 'NH카드',
            '17' => '하나SK카드',
            '21' => '해외비자카드',
            '22' => '해외마스터카드',
            '23' => '해외JCB카드',
            '24' => '해외아멕스카드',
            '25' => '해외다이너스카드',
            '98' => '페이코(포인트 100% 사용)',
        );

        return $pgCards;
    }

    //--- 은행 코드
    public function getPgBanks()
    {
        $pgBanks = array(
            '02' => '한국산업은행',
            '03' => '기업은행',
            '04' => '국민은행',
            '05' => '하나은행(구외환)',
            '07' => '수협중앙회',
            '11' => '농협중앙회',
            '12' => '단위농협',
            '16' => '축협중앙회',
            '20' => '우리은행',
            '21' => '신한은행',
            '23' => '제일은행',
            '25' => '하나은행',
            '26' => '신한은행',
            '27' => '한국씨티은행',
            '31' => '대구은행',
            '32' => '부산은행',
            '34' => '광주은행',
            '35' => '제주은행',
            '37' => '전북은행',
            '38' => '강원은행',
            '39' => '경남은행',
            '41' => '비씨카드',
            '53' => '씨티은행',
            '54' => '홍콩상하이은행',
            '71' => '우체국',
            '81' => '하나은행',
            '83' => '평화은행',
            '87' => '신세계',
            '88' => '신한은행',
            '98' => '페이코(포인트 100% 사용)',
        );

        return $pgBanks;
    }

    public function pay($idx = null, $chkStamp = true, $isManual = false)
    {
        if (!$idx)
            return false;

        $scheduleIdx = $idx;
        $obj = App::load(\Component\Subscription\Subscription::class);
        if (!$info = $obj->getSubscription($idx))
            return false;

        if (!$isManual && ($info['isStop'] || !$info['items'] || !$info['idx_card'] || $info['isPayed']))
            return false;

        $card = $obj->setCard($info['idx_card'])->get();
        if (!$card['payKey'])
            return false;

        /* 결제일 유효성 검사 */
        if ($chkStamp) {
            $stamp = strtotime(date("Ymd"));
            if ($stamp != $info['schedule_stamp'])
                return false;
        }

        $server = Request::server()->toArray();
        $memNo = \Session::get("member.memNo");
        if (gd_is_login() && $memNo == $info['memNo']) {
            $order = App::load(\Component\Order\Order::class);
        } else {
            $order = App::load(\Component\Order\OrderAdmin::class);
        }

        $cart = new \Component\Subscription\Cart();

        $cfg = $this->getPgCfg();

        $orderData = $cartSno = [];
        if ($orderNo = $info['orderNo']) {
            $row = $this->db->fetch("SELECT COUNT(*) as cnt FROM " . DB_ORDER . " WHERE orderNo='{$orderNo}'");
            if ($row['cnt'] > 0) {
                $orderData = $order->getOrderView($info['orderNo']);
                $status = substr($orderData['orderStatus'], 0, 1);
                if ($status != 'f')
                    unset($orderData);
            }
        }

        if (empty($orderData)) {
            /* 주문서 생성 */
            /* 임시장바구니에 데이터 넣기 */
            $items = $info['items'];
            $sql = "DELETE FROM wm_subscription_cart3 WHERE memNo='{$info['memNo']}' AND isTemp='1'";
            $this->db->query($sql);

            foreach ($items as $it) {
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
                                   isTemp='1'";
                if ($this->db->query($sql)) {
                    $cartSno[] = $this->db->insert_id();
                }
            }

            /* 현재 회차 */
            try {
                $postValue = $order->setOrderDataValidation($info, true);
            } catch (Exception $e) {
                return false;
            }

            $postValue['settleKind'] = 'pc';
            unset($postValue['items']);
            $address = str_replace(' ', '', $postValue['receiverAddress'] . $postValue['receiverAddressSub']);

            $cart->deliveryFree = $postValue['deliveryFree'];
            try {
                $this->db->begin_tran();
                $cnt = $obj->getSubscriptionCnt($postValue['uid']);
                $discount = $cfg['discount'][$cnt];
                if (empty($discount) && $cnt > 0)
                    $discount = $cfg['discount'][count($cfg['discount']) - 1];

                $cartInfo = $cart->getCartList($cartSno, $address, $postValue, true, $postValue['memNo'], $discount);

                // ===== 정기배송 상품의 firstDelivery 자동 계산 (공통 메서드 사용) =====
                $obj->calculateFirstDeliveryForCart($cartInfo);

                $orderPrice = $cart->setOrderSettlePayCalculation($postValue);
                if ($orderPrice['settlePrice'] < 0)
                    return false;

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
            }

            // 결제금액이 0원인 경우 전액할인 수단으로 강제 변경 및 주문 채널을 shop 으로 고정
            if ($orderPrice['settlePrice'] == 0) {
                $postValue['settleKind'] = Order::SETTLE_KIND_ZERO;
                $postValue['orderChannelFl'] = 'shop';
            }

            /*
             * 주문정보 발송 시점을 트랜잭션 종료 후 진행하기 위한 로직 추가
             */

            // 주문 저장하기 (트랜젝션)
            $result = \DB::transaction(function () use ($order, $cartInfo, $postValue, $orderPrice, $cart) {
                return $order->saveOrderInfo($cartInfo, $postValue, $orderPrice);
            });


            if ($result) {
                $orderNo = $order->orderNo;

                if ($obj->updateScheduleOrder($postValue['uid'], $postValue['schedule_stamp'], $orderNo, $scheduleIdx)) {
                    if (gd_is_login() && $memNo == $info['memNo']) {
                        $orderData = $order->getOrderView2($orderNo);
                    } else {
                        $orderData = $order->getOrderView($orderNo);
                    }
                }
            }
        }

        $isPayed = 0;
        if (substr($orderData['orderStatus'], 0, 1) == 'f' && $orderData['settlePrice'] > 0) {
            // 결제 시도 상태이고  결제금액이 0이상인 경우 PG 결제 처리 
            if (!$orderData['goods'])
                return false;

            $arrOrderGoodsSno = [];
            foreach ($orderData['goods'] as $li) {
                $arrOrderGoodsSno['sno'][] = $li['sno'];
            }

            $inipay = App::load(\Component\Subscription\Inicis\INIpay41Lib::class);

            $settleprice = (integer)$orderData['settlePrice'];
            //$settleprice = 1000;
            $inipay->m_inipayHome = dirname(__FILE__) . "/../../../subscription_module/inicis";   // INIpay Home (절대경로로 적절히 수정)
            $inipay->m_keyPw = "1111";        // 키패스워드(상점아이디에 따라 변경)
            $inipay->m_type = "reqrealbill";       // 고정 (절대 수정금지)
            $inipay->m_pgId = "INIpayBill";       // 고정 (절대 수정금지)
            $inipay->m_payMethod = "Card";           // 고정 (절대 수정금지)
            $inipay->m_billtype = "Card";              // 고정 (절대 수정금지)
            $inipay->m_subPgIp = "203.238.3.10";       // 고정 (절대 수정금지)
            $inipay->m_debug = "true";        // 로그모드("true"로 설정하면 상세한 로그가 생성됨)
            $inipay->m_mid = $cfg['mid'];         // 상점아이디
            $inipay->m_billKey = $card['payKey'];        // billkey 입력
            $inipay->m_goodName = iconv("UTF-8", "EUC-KR", $orderData['orderGoodsNm']);       // 상품명 (최대 40자)
            $inipay->m_currency = "WON";       // 화폐단위
            $inipay->m_price = $settleprice;        // 가격
            $inipay->m_buyerName = iconv("UTF-8", "EUC-KR", $orderData['orderName']);       // 구매자 (최대 15자)
            $inipay->m_buyerTel = str_replace("-", "", $orderData['orderCellPhone']);       // 구매자이동전화
            $inipay->m_buyerEmail = $orderData['orderEmail'];       // 구매자이메일
            $inipay->m_cardQuota = "00";       // 할부기간
            $inipay->m_quotaInterest = "0";      // 무이자 할부 여부 (1:YES, 0:NO)
            $inipay->m_url = $cfg['domain'];    // 상점 인터넷 주소
            $inipay->m_cardPass = "";       // 키드 비번(앞 2자리)
            $inipay->m_regNumber = "";       // 주민 번호 및 사업자 번호 입력
            $inipay->m_authentification = "01"; //( 신용카드 빌링 관련 공인 인증서로 인증을 받은 경우 고정값 "01"로 세팅)
            $inipay->m_oid = $orderNo;        //주문번호
            $inipay->m_merchantreserved1 = $MerchantReserved1;  // Tax : 부가세 , TaxFree : 면세 (예 : Tax=10&TaxFree=10)
            $inipay->m_merchantreserved2 = $MerchantReserved2;  // 예비2
            $inipay->m_merchantreserved3 = $MerchantReserved3;  // 예비3
            $inipay->startAction();
            $tid = $inipay->m_tid;

            $settlelog = '';
            $settlelog .= '====================================================' . PHP_EOL;
            $settlelog .= 'PG명 : 이니시스 정기결제' . PHP_EOL;
            $settlelog .= "거래번호 : " . $tid . chr(10);
            $settlelog .= "결과코드 : " . $inipay->m_resultCode . PHP_EOL;


            if ($inipay->m_resultCode == "00") {
                $settlelog .= "처리결과 : 성공" . PHP_EOL;
                $bool = true;

                $arrOrderGoodsSno['changeStatus'] = "p1";

                if (gd_is_login() && $memNo == $info['memNo']) {
                    $order->statusChangeCodeP($orderNo, $arrOrderGoodsSno); // 입금확인으로 변경

                } else {
                    $sql = "UPDATE " . DB_ORDER . " SET orderStatus='p1',paymentDt=sysdate() WHERE orderNo='{$orderNo}'";
                    $this->db->query($sql);

                    $sql = "UPDATE " . DB_ORDER_GOODS . " SET orderStatus='p1',paymentDt=sysdate() WHERE orderNo='{$orderNo}'";;
                    $this->db->query($sql);

                    $sql = "UPDATE " . DB_ORDER_INFO . " SET isSubscription='1' WHERE orderNo='{$orderNo}'";;
                    $this->db->query($sql);

                    $order->sendOrderInfo("INCASH", "all", $orderNo);

                }
                $isPayed = 1;
            } else {
                $settlelog .= "처리결과 : 실패" . chr(10);
                $sql = "UPDATE " . DB_ORDER . " SET orderStatus='f3' WHERE orderNo='{$orderNo}'";
                $this->db->query($sql);
                $sql = "UPDATE " . DB_ORDER_GOODS . " SET orderStatus='f3' WHERE orderNo='{$orderNo}'";;
                $this->db->query($sql);

                /* 처리 실패했을경우 실 결제가 되는 경우도 있는데 이런 경우는 결제 취소 처리 */
                if ($tid) {
                    $inipay->m_inipayHome = dirname(__FILE__) . "/../../../subscription_module/inicis";   // INIpay Home (절대경로로 적절히 수정)
                    $inipay->m_keyPw = "1111";        // 키패스워드(상점아이디에 따라 변경)
                    $inipay->m_type = "cancel";       // 고정 (절대 수정금지)
                    $inipay->m_pgId = "INIpayBill";       // 고정 (절대 수정금지)
                    $inipay->m_subPgIp = "203.238.3.10";       // 고정 (절대 수정금지)
                    $inipay->m_debug = "true";        // 로그모드("true"로 설정하면 상세한 로그가 생성됨)
                    $inipay->m_mid = $cfg['mid'];         // 상점아이디
                    $inipay->m_tid = $tid;
                    $inipay->m_merchantreserved = $MerchantReserved1;  // Tax : 부가세 , TaxFree : 면세 (예 :

                    $inipay->startAction();
                    $settlelog = "";
                    $settlelog .= '====================================================' . PHP_EOL;
                    $settlelog .= 'PG명 : 이니시스 정기결제 취소' . PHP_EOL;

                    $settlelog .= "결과코드 : " . $inipay->m_resultCode . PHP_EOL;
                    $settlelog .= "결과내용 : " . iconv("EUC-KR", "UTF-8", $inipay->m_resultMsg) . PHP_EOL;
                    $settlelog .= "취소날짜 : " . $inipay->m_pgCancelDate . PHP_EOL;
                    $settlelog .= "취소시각 : " . $inipay->m_pgCancelTime . PHP_EOL;
                    $settlelog .= '====================================================' . PHP_EOL;
                }

                $isPayed = 0;
            }

            $settlelog .= "결과내용 : " . iconv("EUC-KR", "UTF-8", strip_tags($inipay->m_resultMsg)) . PHP_EOL;
            $settlelog .= "승인번호 : " . $inipay->m_authCode . PHP_EOL;
            $settlelog .= "승인날짜 : " . $inipay->m_pgAuthDate . PHP_EOL;
            $settlelog .= "승인시각 : " . $inipay->m_pgAuthTime . PHP_EOL;
            $settlelog .= "부분취소가능여부 : " . $inipay->m_prtcCode . PHP_EOL;
            $settlelog .= '====================================================' . PHP_EOL;
            $this->db->query("UPDATE wm_subscription_schedule_list SET orderNo='{$orderNo}', isPayed='{$isPayed}', tid='{$tid}' WHERE idx='{$scheduleIdx}'");

            $sql = "UPDATE " . DB_ORDER . "
                            SET
                                pgName='sub',
                                pgResultCode='" . $inipay->m_resultCode . "',
                                pgTid='" . $tid . "',
                                pgAppNo='" . $inipay->m_authCode . "',
                                pgAppDt='" . $inipay->m_pgAuthDate . "',
                                orderPGLog='" . $settlelog . "'
                     WHERE orderNo='{$orderNo}'";
            $this->db->query($sql);
        } else {
            if (empty($orderPrice['settlePrice']) && $orderData['settleKind'] == Order::SETTLE_KIND_ZERO) { // 전액 결제인 경우 처리 
                $isPayed = 1;
                $this->db->query("UPDATE wm_subscription_schedule_list SET orderNo='{$orderNo}', isPayed='{$isPayed}' WHERE idx='{$scheduleIdx}'");
            }
        }

        if ($isPayed) {
            /* 정기결제 장바구니 상품 삭제 */
            if ($cartSno)
                $cart->cartDelete($cartSno);

            return true;
        }
    }

    public function cancel($orderNo = null, $isApplyRefund = true, $msg = null, $partData = null)
    {
        if (!$orderNo)
            return false;

        if (!$msg)
            $msg = "관리자 취소";

        $order = App::load(\Component\Order\Order::class);
        $cfg = $this->getCfg();

        $inipay = App::load(\Component\Subscription\Inicis\INIpay41Lib::class);
        $orderReorderCalculation = App::load(\Component\Order\ReOrderCalculation::class);
        $od = [];
        if ($tmp = $this->db->fetch("SELECT * FROM " . DB_ORDER . " WHERE orderNo='{$orderNo}'"))
            $od = $tmp;

        if (!$od)
            return false;

        $tid = $od['pgTid'];

        $inipay->m_inipayHome = dirname(__FILE__) . "/../../../subscription_module/inicis";   // INIpay Home (절대경로로 적절히 수정)
        $inipay->m_keyPw = "1111";        // 키패스워드(상점아이디에 따라 변경)
        $inipay->m_type = "cancel";       // 고정 (절대 수정금지)
        $inipay->m_pgId = "INIpayBill";       // 고정 (절대 수정금지)
        $inipay->m_subPgIp = "203.238.3.10";       // 고정 (절대 수정금지)
        $inipay->m_debug = "true";        // 로그모드("true"로 설정하면 상세한 로그가 생성됨)
        $inipay->m_mid = $cfg['mid'];         // 상점아이디
        $inipay->m_tid = $tid;
        $inipay->m_merchantreserved = $MerchantReserved1;  // Tax : 부가세 , TaxFree : 면세 (예 :

        // 부분 취소 세팅 START
        if(!empty($partData)) {
            $inipay->m_price = $partialData['price']; // 취소할 금액
            $inipay->m_tax = $partialData['tax']; // 부가세 (없으면 0)
            $inipay->m_confirm_price = $partialData['confirmPrice']; // 부분취소 후 남은금액
        }
        // 부분 취소 세팅 END

        $inipay->startAction();
        $settlelog = "";
        $settlelog .= '====================================================' . PHP_EOL;
        $settlelog .= 'PG명 : 이니시스 정기결제 취소' . PHP_EOL;

        $settlelog .= "결과코드 : " . $inipay->m_resultCode . PHP_EOL;
        $settlelog .= "결과내용 : " . iconv("EUC-KR", "UTF-8", $inipay->m_resultMsg) . PHP_EOL;
        $settlelog .= "취소날짜 : " . $inipay->m_pgCancelDate . PHP_EOL;
        $settlelog .= "취소시각 : " . $inipay->m_pgCancelTime . PHP_EOL;
        if ($inipay->m_resultCode == "00") {
            $bool = true;

            if ($isApplyRefund) {
                if ($list = $this->db->query_fetch("SELECT sno, orderStatus FROM " . DB_ORDER_GOODS . " WHERE orderNo='{$orderNo}'")) {
                    $arrData = ['mode' => 'refund', 'orderNo' => $orderNo, "orderChannelFl" => "shop"];
                    foreach ($list as $li) {
                        $arrData['refund']['statusCheck'][$li['sno']] = $li['sno'];
                        $arrData['refund']['statusMode'][$li['sno']] = $li['orderStatus'];
                        $arrData['refund']['goodsType'][$li['sno']] = 'goods';
                    }
                    $return = \DB::transaction(function () use ($orderReorderCalculation, $arrData) {
                        return $orderReorderCalculation->setBackRefundOrderGoods($arrData, 'refund');
                    });

                    $this->cancelHandler($orderNo);
                } // endif
            } // endif 
        }

        $settlelog .= '====================================================' . PHP_EOL;
        $settlelog = $this->db->escape($settlelog);
        $sql = "UPDATE " . DB_ORDER . " SET orderPGLog=concat(ifnull(orderPGLog,''),'" . $settlelog . "') WHERE orderNo='{$orderNo}'";
        $this->db->query($sql);

        return true;
    }

    public function cancelHandler($orderNo)
    {
        if (!$orderNo)
            return false;


        $orderModel = \App::load("\\Component\\Order\\Order");
        $orderReorderCalculation = new \Component\Order\ReOrderCalculation();

        $this->db->strWhere = "orderNo = '" . $orderNo . "'";

        $query = $this->db->query_complete();

        $sql = "SELECT " . array_shift($query) . " FROM " . DB_ORDER . implode(' ', $query);
        $order = $this->db->query_fetch($sql);


        if (!$order)
            return false;

        $orderInfo = $order;

        $this->db->strField = "sno, orderNo, orderStatus, goodsCnt, divisionUseMileage, divisionUseDeposit, (taxSupplyGoodsPrice + taxVatGoodsPrice) as goodsPrice";
        $this->db->strWhere = "orderNo = '" . $orderNo . "'";

        $query = $this->db->query_complete();

        $sql = "SELECT " . array_shift($query) . " FROM " . DB_ORDER_GOODS . implode(' ', $query);
        $list = $this->db->query_fetch($sql);

        if (!$list) return false;

        $arrOrderData = $orderModel->getOrderData($orderNo);

//        $refundMethod = ($order['settleKind']== 'gb')? "기타환불" : "PG환불";
        $refundMethod = "기타환불";

        $params = [];
        $params['orderNo'] = $orderNo;
        $params['info']['refundMethod'] = $refundMethod;
        $params['info']['refundGoodsUseDeposit'] = $arrOrderData['useDeposit'];
        $params['info']['refundGoodsUseMileage'] = $arrOrderData['useMileage'];
        $params['info']['handleReason'] = '기타';
        $params['info']['handleDetailReason'] = '정기구독 취소 건';

        if ($order['settleKind'] == 'gb') {
            $params['info']['completeMileagePrice'] = $order['settlePrice'];
        }

        $totalSettle = $order['settlePrice'];

        foreach ($list as $k => $li) {
            if ($k == count($list) - 1) {
                $li['settleEach'] = $totalSettle;
            } else {
                $rate = $li['goodsPrice'] / $order['totalGoodsPrice'];
                $li['settleEach'] = round($totalSettle * $rate);
                $totalSettle -= $li['settleEach'];
            }

            $list[$k] = $li;

            $params['refund']['statusCheck'][$li['sno']] = $li['sno'];
            $params['refund']['statusMode'][$li['sno']] = $li['orderStatus'];
            $params['refund']['goodsType'][$li['sno']] = 'goods';
            $params['refund']['goodsOriginCnt'][$li['sno']] = $li['goodsCnt'];
            $params['refund']['goodsCnt'][$li['sno']] = $li['goodsCnt'];

            $params['refund']['handleReason'] = '기타';
            if ($order['settleKind'] != 'gb') {
//                $params['refund']['refundMethod']= 'PG환불';
                $params['refund']['refundMethod'] = '기타환불';
            }

            $params['refund']['handleDetailReason'] = '정기구독 취소 건';
            $param['handler'] = 'admin';
        }

        //환불 접수 처리
        $orderReorderCalculation->setBackRefundOrderGoods($params, 'refund');

        // 환붙 데이터 백업
        $orderReorderCalculation->setBackupOrderOriginalData($orderNo, 'r', false);

        //환불 완료 처리
        $sql = "SELECT * FROM " . DB_ORDER_HANDLE . " WHERE orderNo = ? AND handleMode ='r' AND handleCompleteFl = 'n' ORDER BY sno DESC LIMIT 0, 1";
        $handle = $this->db->query_fetch($sql, ["i", $orderNo], false);

        if ($handle) {
            $sql = "SELECT deliverySno FROM " . DB_ORDER_DELIVERY . " WHERE orderNo = ?";
            $odList = $this->db->query_fetch($sql, ["i", $orderNo]);
            $params = [];
            $params['handleSno'] = $handle['sno'];
            $params['isAll'] = 1;
            $params['orderNo'] = $orderNo;
            $params['totalRealPayedPrice'] = $order['settlePrice'];
            $params['refundGoodsPrice'] = $order['totalGoodsPrice'];
            $params['refundAliveGoodsPriceSum'] = 0;
            $params['refundAliveGoodsCount'] = 0;
            $params['refundGoodsDcSum'] = 0;
            $params['refundGoodsCouponMileageOrg'] = 0;
            $params['refundGoodsCouponMileageMin'] = 0;
            $params['refundGoodsCouponMileageMax'] = 0;
            $params['refundOrderCouponMileageOrg'] = 0;
            $params['refundOrderCouponMileageMin'] = 0;
            $params['refundOrderCouponMileageMax'] = 0;
            $params['refundGroupMileageOrg'] = 0;
            $params['refundGroupMileageMin'] = 0;
            $params['refundGroupMileageMax'] = 0;
            $params['refundGoodsDcPriceSumMin'] = 0;
            $params['refundGoodsDcPriceOrg'] = 0;
            $params['refundGoodsDcPriceMax'] = 0;
            $params['refundGoodsDcPriceMaxOrg'] = 0;
            $params['refundMemberAddDcPriceOrg'] = 0;
            $params['refundMemberAddDcPriceMax'] = 0;
            $params['refundMemberAddDcPriceMaxOrg'] = 0;
            $params['refundMemberOverlapDcPriceOrg'] = 0;
            $params['refundMemberOverlapDcPriceMax'] = 0;
            $params['refundMemberOverlapDcPriceMaxOrg'] = 0;
            $params['refundEnuriDcPriceOrg'] = 0;
            $params['refundEnuriDcPriceMax'] = 0;
            $params['refundEnuriDcPriceMaxOrg'] = 0;
            $params['refundGoodsCouponDcPriceOrg'] = 0;
            $params['refundGoodsCouponDcPriceMax'] = 0;
            $params['refundGoodsCouponDcPriceMaxOrg'] = 0;
            $params['refundOrderCouponDcPriceOrg'] = 0;
            $params['refundOrderCouponDcPriceMax'] = 0;
            $params['refundOrderCouponDcPriceMaxOrg'] = 0;
            $params['refundAliveDeliveryPriceSum'] = 0;
            $params['refundDeliveryCouponDcPriceOrg'] = 0;
            $params['refundDeliveryCouponDcPriceMax'] = 0;
            $params['refundDeliveryCouponDcPriceMaxOrg'] = 0;
            $params['refundDepositPriceOrg'] = $arrOrderData['useDeposit'];
            $params['refundDepositPriceTotal'] = $arrOrderData['useDeposit'];
            $params['refundDepositPriceMax'] = 0;
            $params['refundDepositPriceMaxOrg'] = 0;
            $params['refundMileagePriceOrg'] = $arrOrderData['useMileage'];
            $params['refundMileagePriceTotal'] = $arrOrderData['useMileage'];
            $params['refundMileagePriceMax'] = $arrOrderData['useMileage'];
            $params['refundMileagePriceMaxOrg'] = $arrOrderData['useMileage'];
            $params['aAliveDeliverySno'] = [];

            if ($odList) {
                foreach ($odList as $o) {
                    $params['aAliveDeliverySno'][] = $o['deliverySno'];
                }
            }

            $params['refundGoodsCouponMileageFlag'] = 'F';
            $params['refundOrderCouponMileageFlag'] = 'F';
            $params['refundGroupMileageFlag'] = 'F';
            $params['refundGoodsDcPriceFlag'] = 'F';
            $params['refundMemberAddDcPriceFlag'] = 'F';
            $params['refundMemberOverlapDcPriceFlag'] = 'F';
            $params['refundEnuriDcPriceFlag'] = 'F';
            $params['refundGoodsCouponDcPriceFlag'] = 'F';
            $params['refundOrderCouponDcPriceFlag'] = 'F';

            foreach ($list as $k => $li) {
                $params['refund'][$li['sno']] = [
                    'sno' => $li['sno'],
                    'returnStock' => 'y',
                    'originGiveMileage' => 0,
                    'refundGiveMileage' => 0,
                    'refundGoodsPrice' => $li['goodsPrice'] * $li['goodsCnt'],
                ];
            }

            $params['check']['totalSettlePrice'] = $order['settlePrice'];
            $params['check']['totalRefundCharge'] = 0;
            $params['check']['totalDeliveryCharge'] = $order['totalDeliveryCharge'];
            $params['check']['totalRefundPrice'] = $order['settlePrice'];
            $params['check']['totalDeliveryInsuranceFee'] = 0;
            $params['check']['totalGiveMileage'] = 0;
            $params['tmp']['refundMinusMileage'] = 'y';
            $params['lessRefundPrice'] = 0;
            $params['refundPriceSum'] = $order['settlePrice'];
            $params['refundGoodsPriceSum'] = 0;
            $params['refundDeliveryPriceSum'] = 0;
            $params['etcGoodsSettlePrice'] = 0;
            $params['etcDeliverySettlePrice'] = 0;
            $params['etcRefundAddPaymentPrice'] = 0;
            $params['etcRefundGoodsAddPaymentPrice'] = 0;
            $params['etcRefundDeliveryAddPaymentPrice'] = 0;
            $params['info']['refundMethod'] = $refundMethod;
            $params['info']['completePgPrice'] = $order['settlePrice'];
            $params['info']['refundGoodsUseDeposit'] = $arrOrderData['useDeposit'];
            $params['info']['refundGoodsUseMileage'] = $arrOrderData['useMileage'];
            $params['info']['handleReason'] = '기타';
            $params['info']['handleDetailReason'] = '구독서비스 신청 해지';
            if ($order['settleKind'] == 'gb') {
                $params['info']['completeMileagePrice'] = $order['settlePrice'];
                $params['info']['completePgPrice'] = 0;
            }
            $params['returnStockFl'] = 'y';

            $sql = "SELECT memberCouponNo FROM " . DB_ORDER_COUPON . " WHERE orderNo =?";
            $clist = $this->db->query_fetch($sql, ["i", $orderNo]);

            if ($clist) {
                foreach ($clist as $c) {
                    $params['returnCoupon'][$c['memberCouponNo']] = 'y';
                }
            }

            $arrData = [
                'changeStatus' => 'r3',
            ];
            foreach ($list as $li) {
                $arrData['sno'][] = $li['sno'];
            }

            $order = App::load(\Component\Order\Order::class);
            //$order = new \Component\Order\Order();;

            $order->statusChangeCodeR($orderNo, $arrData, true, null, true);
            $orderReorderCalculation->restoreRefundCoupon($params);
            $orderReorderCalculation->restoreRefundUseMileage($params, $arrOrderData, ['restoreMileageSnos' => $arrData['sno']]);
            $orderReorderCalculation->restoreRefundUseDeposit($params, $arrOrderData, ['restoreDepositSnos' => $arrData['sno']]);
            $end = $order->getOrderListForClaim($orderNo);

            $sql = "UPDATE " . DB_ORDER_HANDLE . " SET
        handleDt = '" . date("Y-m-d H:i:s") . "',
        handleDetailReason = '" . $params['info']['handleDetailReason'] . "',
        handleReason = '" . $params['info']['handleReason'] . "',
        refundMethod = '기타환불',
        handleCompleteFl = 'y',
        refundUseDeposit = '" . $arrOrderData['useDeposit'] . "',
        refundUseMileage = '" . $arrOrderData['useMileage'] . "',
        modDt = now()
        WHERE
        orderNo = " . $orderNo;
            $this->db->query($sql);

            return true;
        }
    }

}