<?php

/**
 * This is restricted software, not for public distribution.
 */

namespace Component\Order;

/**
 * 주문 class customizations
 * @author Conan Kim <kmakugo@gmail.com>
 */

use App;

class OrderNew extends \Bundle\Component\Order\OrderNew
{
    public function saveOrderInfo($cartInfo, $orderInfo, $order, $checkSumData = true)
    {
        /**
         * Checks if $componentGoodsNos is not empty and an item's 'goodsType' is 'addGoods'
         * and it's 'addGoodsNo' present in $componentGoodsNos.
         * set isComponentGoods flag to true.
         */
        list($orderInfo, $order, $memberData, $deliveryInfo, $aCartData, $history, $couponInfo, $taxPrice) = parent::saveOrderInfo($cartInfo, $orderInfo, $order, $checkSumData);

        // deserialize componentGoodsNo and addGoodsPrices
        foreach ($aCartData as $key => $cartGoodsData) {
            if ($cartGoodsData['goodsType'] == 'goods') {
                if (gd_isset($cartGoodsData['componentGoodsNo'])) {
                    $componentGoodsNos[$cartGoodsData['goodsNo']] = json_decode($cartGoodsData['componentGoodsNo'], true);
                }
                if (gd_isset($cartGoodsData['addGoodsPrices'])) {
                    $addedGoodsPrices[$cartGoodsData['goodsNo']] = json_decode($cartGoodsData['addGoodsPrices'], true);
                    $addedGoodsPriceIndices[$cartGoodsData['goodsNo']] = $key + 1;
                }

                if (!empty($cartGoodsData['firstDelivery']) && $cartGoodsData['firstDelivery'] !== '0') {
                    $aCartData[$key]['invoiceNo'] = '새벽배송(' . $cartGoodsData['firstDelivery'] . ')';
                }
            }
        }

        // FIXME: 카트에 골라담기 상품이 두 개 이상 있는 경우 isComponentGoods가 제대로 설정되지 않음
        // ex, 2502100959000001
        // FIXME: 알 수 없는 이유로 isComponentGoods가 제대로 설정되지 않음
        // ex, 2502261717555886
        // 현재까진 과거 componentGoodsNo 필드가 없던 시절 (25년 2월 경)에 카트에 담았던 상품에 대해서만 발생하는 것으로 추측
        if (!empty($componentGoodsNos)) {
            foreach ($aCartData as $key => $cartGoodsData) {
                if ($cartGoodsData['goodsType'] == 'addGoods' && gd_isset($cartGoodsData['parentGoodsNo'])) {
                    if (in_array($cartGoodsData['addGoodsNo'], $componentGoodsNos[$cartGoodsData['parentGoodsNo']])) {
                        $aCartData[$key]['isComponentGoods'] = true;
                    }
                    $aCartData[$key]['addedGoodsPrice'] = $addedGoodsPrices[$cartGoodsData['parentGoodsNo']][$key - $addedGoodsPriceIndices[$cartGoodsData['parentGoodsNo']]];
                }
            }
        }
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        return [$orderInfo, $order, $memberData, $deliveryInfo, $aCartData, $history, $couponInfo, $taxPrice];
    }

    protected function setStatusChange($orderNo, $arrData, $autoProcess = false)
    {
        $return = parent::setStatusChange($orderNo, $arrData, $autoProcess);
        if ($return !== false) {
            $requireSync = true;

            if (in_array($arrData['changeStatus'], ['g1', 'p1'])) {
                $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');
                $requireSync = $orderAdmin->trySplitScheduledDeliveries([$orderNo], $arrData['changeStatus']);
            }

            if ($requireSync) {
                try {
                    $orderGoodsSnos = $arrData['sno'];
                    $order = App::load('\\Component\\Order\\Order');
                    $order->syncDeliveryStatusOfFirstRoundDelivery($orderGoodsSnos);
                } catch (Exception $e) {
                    // teams 연동
                    echo "Error has occurred: " . $e->getMessage();
                }
            }
        }
        return $return;
    }

    /**
     * 취소/교환/반품/환불 처리 중 환불일 경우 PG환불 하는 프로세스
     *
     * @param array $bundleData 신청 정보
     *
     * @return string 결과정보
     */
    public function processAutoPgCancel($bundleData, $userHandleSno)
    {
        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            if ($obj->chkSubscriptionOrder($bundleData['orderNo'])) {
                $result = $obj->refundSubscriptionOrder($bundleData, $userHandleSno);
                if (!empty($result['msg'])) {
                    return $result['msg'];
                }
            }
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END

        return parent::processAutoPgCancel($bundleData, $userHandleSno);
    }
}
