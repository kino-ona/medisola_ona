<?php

namespace Controller\Admin\Order;

use Request;
use App;

class OrderChangePsController extends \Bundle\Controller\Admin\Order\OrderChangePsController
{
    public function pre()
    {
        $post = Request::post()->toArray();

        if ($post['orderNo'] && in_array($post['mode'], ['refund_complete_new']) && $post['info']['refundMethod'] == '복합환불') {

            $db = App::load('DB');

            // 부분 환불 check START
            $partData = null; // 파라미터에 추가할 부분 환불 데이터
            $order = App::load("\Component\Order\Order");

            $orderData = $order->getOrderData($post['orderNo']); // 주문데이터

            $partFl = false;
            $refundCnt = count($post['refund']); // 현재 환불하는 상품 총 개수
            if ($refundCnt < $orderData['orderGoodsCnt'] && $orderData['settlePrice'] > 0 && $post['totalRealPayedPrice'] > 0) { // 총 개수보다 적은 개수는 부분 환불로 진행. 단, 결제 금액이 0보다 클 때.
                $partFl = true; // 부분 환불 처리 여부
                $refundPrice = 0;
                $row = $db->fetch("select sum(refundPrice) as total from wm_subscription_refund where orderNo='{$orderData['orderNo']}'");
                if (!empty($row['total'])) {
                    $refundPrice = $row['total'];
                }

                // 부분 환불 시 필요한 데이터
                $partData['price'] = $post['totalRealPayedPrice']; // 환불 금액(환불 시 관리자가 입력한 환불하려는 금액)
                $partData['tax'] = floor($post['totalRealPayedPrice'] / 11); // 부가세
                $partData['confirmPrice'] = $orderData['settlePrice'] - ($refundPrice + $post['totalRealPayedPrice']); // 현재 상품 외 나머지 환불 가능한 금액
            }
            // 부분 환불 check END

            $od = $db->fetch("SELECT * FROM " . DB_ORDER . " WHERE orderNo='{$post['orderNo']}'");

            if ($od) {
                //if ($od['orderStatus'] == 'r1' && $od['pgName'] == 'sub') {
                if ($od['pgName'] == 'sub') {
                    $subObj = App::load(\Component\Subscription\Subscription::class);
                    $orderReorderCalculation = new \Component\Order\ReOrderCalculation();

                    $cancle_return = $subObj->cancel($post['orderNo'], false, $post['info']['handleDetailReason'], $partData); // 파라미터에 $partData 추가

                    if ($cancle_return == true) {
                        \DB::transaction(
                            function () use ($orderReorderCalculation, $paycoConfig) {
                                $orderReorderCalculation->setRefundCompleteOrderGoodsNew(Request::post()->toArray());
                            }
                        );

                        // 부분 환불 이력 추가 START
                        if ($partFl) {
                            $db->query("insert into wm_subscription_refund set orderNo='{$post['orderNo']}', refundPrice='{$post['totalRealPayedPrice']}'");
                        }
                        // 부분 환불 이력 추가 END

                        echo "<script>alert('정기결제 주문건에 대한 환불처리가 완료되었습니다.');parent.opener.location.reload();parent.close();</script>";
                    }
                }
            }

        }

        if ($post['orderNo'] && in_array($post['mode'], ['cancel', 'refund']) && $post['refund']['refundMethod'] == '복합환불') {

            $cnt = count($post['refund']['statusCheck']);
            $db = App::load('DB');
            $tmp2 = $db->fetch("SELECT COUNT(*) as cnt FROM " . DB_ORDER_GOODS . " WHERE orderNo='{$post['orderNo']}'");
            $cnt2 = $tmp2['cnt'];

            $od = $db->fetch("SELECT * FROM " . DB_ORDER . " WHERE orderNo='{$post['orderNo']}'");
            if ($cnt == $cnt2 && $od) {
                $orderStatus = substr($od['orderStatus'], 0, 1);
                if ($orderStatus != 'r' && $od['pgName'] == 'sub') {
                    $subObj = App::load(\Component\Subscription\Subscription::class);
                    $subObj->cancel($post['orderNo'], false);
                }
            }
        }
        /* 튜닝 END */
    }
}