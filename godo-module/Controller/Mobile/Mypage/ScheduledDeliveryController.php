<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ Medisola
 * @link https://weare.medisola.co.kr
 */

namespace Controller\Mobile\Mypage;

use App;
use Globals;
use Request;
use Exception;
use Component\Order\Order;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\StringUtils;

/**
 * 마이페이지 > 회차배송관리
 *
 * @package Bundle\Controller\Front\Mypage
 * @author Conan Kim <kmakugo@gmail.com>
 */
class ScheduledDeliveryController extends \Bundle\Controller\Mobile\Controller
{
  public function index()
  {
    try {
      $locale = Globals::get('gGlobal.locale');

      $this->addScript([
        'moment/moment.js',
        'moment/locale/' . $locale . '.js',
        'jquery/datetimepicker/bootstrap-datetimepicker.min.js',
      ]);

      // 모듈 설정
      $order = new Order();
      $delivery = App::load(\Component\Delivery\Delivery::class);

      // 기간 조회
      $searchDate = [
        '1'   => __('오늘'),
        '7'   => __('최근 %d일', 7),
        '15'  => __('최근 %d일', 15),
        '30'  => __('최근 %d개월', 1),
        '90'  => __('최근 %d개월', 3),
        '180' => __('최근 %d개월', 6),
        '365' => __('최근 %d년', 1),
      ];
      $this->setData('searchDate', $searchDate);

      if (\Component\Order\OrderMultiShipping::isUseMultiShipping() === true) {
        $this->setData('isUseMultiShipping', true);
      }

      if (is_numeric(Request::get()->get('searchPeriod')) === true && Request::get()->get('searchPeriod') >= 0) {
        $selectDate = Request::get()->get('searchPeriod');
      } else {
        $selectDate = 30;
      }
      $startDate = date('Y-m-d', strtotime("-$selectDate days"));
      $endDate = date('Y-m-d', strtotime("now"));
      $wDate = Request::get()->get('wDate', [$startDate, $endDate]);
      foreach ($wDate as $searchDateKey => $searchDateValue) {
        $wDate[$searchDateKey] = StringUtils::xssClean($searchDateValue);
      }

      $this->setData('selectDate', $selectDate);

      $gPageName = __("회차배송관리");

      // 사용자 반품/교환/환불 신청 사용여부에 따라 데이터 가공
      if (gd_is_plus_shop(PLUSSHOP_CODE_USEREXCHANGE) === true) {
        $orderBasic = gd_policy('order.basic');
        $this->setData('userHandleFl', gd_isset($orderBasic['userHandleFl'], 'y') === 'y');
        $this->addScript(['plusReview/gd_plus_review.js?popup=no']);
      }

      $scheduledDeliveryList = $order->fetchScheduledDeliveries(10, $wDate);
      $invoiceCompanies = $delivery->getDeliveryCompany(null, true);

      foreach ($invoiceCompanies as $key => $val) {
        if ($val['deliveryFl'] == 'y') {
          $deliveryCompanyNames[$val['sno']] = $val['companyName'];
        }
      }

      foreach ($scheduledDeliveryList as $key => $scheduledDelivery) {
        $scheduledDeliveryList[$key]['invoiceCompanyName'] = $deliveryCompanyNames[$scheduledDelivery['invoiceCompanySno']];
      }

      $sectionedDeliveryList = [];

      foreach ($scheduledDeliveryList as $key => $scheduledDelivery) {
        $sectionedDeliveryList[$scheduledDelivery['orderNo']][] = $scheduledDelivery;
      }

      $this->setData('sectionedDeliveryList', gd_isset($sectionedDeliveryList));
      $this->setData('pageName', 'list');

      // 상품 옵션가 표시설정 config 불러오기
      $optionPriceConf = gd_policy('goods.display');
      $this->setData('optionPriceFl', gd_isset($optionPriceConf['optionPriceFl'], 'y')); // 상품 옵션가 표시설정

      if (Request::get()->get('listMode') == 'data') {
        $this->getView()->setPageName('mypage/_scheduled_delivery_list');
      }

      // 페이지 재설정
      $page = App::load('\\Component\\Page\\Page');
      $this->setData('page', gd_isset($page));
      $this->setData('total', $page->recode['total']);
      $this->setData('gPageName', $gPageName);
    } catch (Exception $e) {
      throw new AlertRedirectException($e->getMessage(), null, null, URI_HOME);
    }
  }
}
