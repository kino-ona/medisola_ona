<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2025, Medisola.
 * @link https://weare.medisola.co.kr
 */

namespace Controller\Front\Mypage;

use App;
use Exception;
use Request;
use Component\Traits\SendDeliveryNotification;

/**
 * Class OrderPsController
 *
 * @package Bundle\Controller\Front\Mypage
 * @author  Conan Kim <kmakugo@gmail.com>
 */
class OrderPsController extends \Bundle\Controller\Front\Mypage\OrderPsController
{
  use SendDeliveryNotification;

  /**
   * {@inheritdoc}
   */
  public function index()
  {
    try {

      $postValue = Request::post()->xss()->toArray();
      $order = App::load('\\Component\\Order\\Order');

      switch ($postValue['mode']) {
        case 'changeEstimatedDeliveryDate':
            $scheduledDeliverySno = $postValue['scheduledDeliverySno'];
            $estimatedDeliveryDate = $postValue['estimatedDeliveryDate'];
            $updateFollowings = $postValue['updateFollowings'];
            $isFreshDelivery = $postValue['isFreshDelivery'];

            // 변경 전 기존 날짜 조회
            $oldDeliveries = $order->fetchScheduledDeliveriesBySno($scheduledDeliverySno, false);
            $oldDate = $oldDeliveries[0]['estimatedDeliveryDt'] ?? '';
            $orderNo = $oldDeliveries[0]['orderNo'] ?? '';

            $order->changeEstimatedDeliveryDates($scheduledDeliverySno, $estimatedDeliveryDate, $updateFollowings, $isFreshDelivery);

            // 관리자 메일 발송 (실패해도 사용자 응답에 영향 없음)
            try {
                $this->sendDeliveryDateChangeNotification($orderNo, $oldDate, $estimatedDeliveryDate, $updateFollowings);
            } catch (\Exception $e) {
                // 메일 실패 시 무시
            }

            $this->json(
              [
                'code'    => 200,
                'message' => __('배송예정일자가 성공적으로 변경되었습니다.'),
              ]
            );
          break;
        default:
          parent::index();
          break;
      }
    } catch (Exception $e) {
      if (Request::isAjax()) {
        $this->json(
          [
            'code'    => 0,
            'message' => $e->getMessage(),
          ]
        );
      } else {
        throw $e;
      }
    }
  }

}
