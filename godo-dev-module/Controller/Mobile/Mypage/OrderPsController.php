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

namespace Controller\Mobile\Mypage;

use App;
use Request;
use Exception;

/**
 * Class OrderPsController
 *
 * @package Bundle\Controller\Mobile\Mypage
 * @author  Conan Kim <kmakugo@gmail.com>
 */
class OrderPsController extends \Bundle\Controller\Mobile\Mypage\OrderPsController
{
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

            $order->changeEstimatedDeliveryDates($scheduledDeliverySno, $estimatedDeliveryDate, $updateFollowings, $isFreshDelivery);
          
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
