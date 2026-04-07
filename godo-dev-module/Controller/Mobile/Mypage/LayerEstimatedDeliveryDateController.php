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
use Framework\Debug\Exception\AlertBackException;

/**
 * Class LayerEstimatedDeliveryDateController
 *
 * @package Bundle\Controller\Mobile\Mypage
 * @author  Conan Kim <kmakugo@gmail.com>
 */
class LayerEstimatedDeliveryDateController extends \Bundle\Controller\Mobile\Controller
{
  /**
   * {@inheritdoc}
   */
  public function index()
  {
    try {
      $order = App::load('\\Component\\Order\\Order');
      $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');

      $getValue =  Request::get()->xss()->toArray();

      $scheduledDeliveryData = $order->fetchScheduledDeliveriesBySno($getValue['scheduledDeliverySno'], true);
      // $scheduledDeliveryData = $order->fetchScheduledDeliveriesBySno('45583', true); // for testing

      $holidays = $orderAdmin->fetchComingHolidays();

      foreach($holidays as $key => $holiday) {
        // $holiday['datestamp'] is the exact date of the holiday
        // $key is the next holiday date
        // The exact holiday is unavailable in Fresh delivery 
        // And the next holiday date is in Normal delivery
        if ($getValue['isFreshDelivery']) {
          $holidays[$key]['date'] = date('Y-m-d', $holiday['datestamp']);
        } else {
          $holidays[$key]['date'] = date('Y-m-d', $key);
        }
      }

      $this->setData('scheduledDelivery', $scheduledDeliveryData[0]);
      $this->setData('followingScheduledDeliveries', array_slice($scheduledDeliveryData, 1));
      $this->setData('holidays', $holidays);
      $this->setData('isFreshDelivery', $getValue['isFreshDelivery']);

    } catch (Exception $e) {
      throw new AlertBackException($e->getMessage());
    }
  }
}
