<?php

/**
 * This is restricted software, not for public distribution.
 */

namespace Component\Order;

use App;
use Exception;

/**
 * OrderAdminNew class customizations
 * @author Conan Kim <kmakugo@gmail.com>
 */
class OrderAdminNew extends \Bundle\Component\Order\OrderAdminNew
{
    /**
     * API 및 관리자 화면에서 주문 상태 변경 시 1회차 배송 상태 동기화
     */
    public function updateStatusUnconditionalPreprocess($orderNo, $goodsData, $statusMode, $changeStatus, $reason = false, $bundleFl = false, $mode = null, $useVisit = null, $autoProcess = false, $isFront = false)
    {
        // 부모 클래스의 기본 로직 실행
        $result = parent::updateStatusUnconditionalPreprocess($orderNo, $goodsData, $statusMode, $changeStatus, $reason, $bundleFl, $mode, $useVisit, $autoProcess, $isFront);
        
        // 배송중(d1) 상태로 변경 시 1회차 배송 상태 동기화
        if ($result === true && $changeStatus == 'd1' && !empty($goodsData)) {
            try {
                $orderGoodsSnos = array_column($goodsData, 'sno');
                if (!empty($orderGoodsSnos)) {
                    $order = App::load('\\Component\\Order\\Order');
                    $order->syncDeliveryStatusOfFirstRoundDelivery($orderGoodsSnos);
                }
            } catch (Exception $e) {
                // 에러가 발생해도 주문 상태 변경은 계속 진행
                // \Logger::channel('scheduledDelivery')->error('1회차 배송 상태 동기화 실패', [
                //     'orderNo' => $orderNo,
                //     'changeStatus' => $changeStatus,
                //     'error' => $e->getMessage()
                // ]);
            }
        }
        
        return $result;
    }
}