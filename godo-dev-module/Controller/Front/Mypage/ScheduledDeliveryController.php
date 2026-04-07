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

namespace Controller\Front\Mypage;

use App;
use Globals;
use Exception;
use Request;
use Component\Board\Board;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\StringUtils;

/**
 * 마이페이지 > 회차배송관리
 *
 * @package Bundle\Controller\Front\Mypage
 * @author Conan Kim <kmakugo@gmail.com>
 */
class ScheduledDeliveryController extends \Bundle\Controller\Front\Controller
{
  public function index()
  {
    try {
      $locale = Globals::get('gGlobal.locale');
      // 날짜 픽커를 위한 스크립트와 스타일 호출
      $this->addCss([
        'plugins/bootstrap-datetimepicker.min.css',
        'plugins/bootstrap-datetimepicker-standalone.css',
      ]);
      $this->addScript([
        'moment/moment.js',
        'moment/locale/' . $locale . '.js',
        'jquery/datetimepicker/bootstrap-datetimepicker.min.js',
      ]);

      if (\Component\Order\OrderMultiShipping::isUseMultiShipping() === true) {
        $this->setData('isUseMultiShipping', true);
      }

      // 모듈 설정
      $order = App::load('\\Component\\Order\\Order');
      $delivery = App::load(\Component\Delivery\Delivery::class);
      $this->setData('eachOrderStatus', $order->getEachOrderStatus(\Session::get('member.memNo'), null, 30));

      // 기본 조회 일자
      $startDate = date('Y-m-d', strtotime('-30 days')); // 최근 30일
      $endDate = date('Y-m-d');
      $wDate = Request::get()->get('wDate', [$startDate, $endDate]);
      foreach ($wDate as $searchDateKey => $searchDateValue) {
        $wDate[$searchDateKey] = StringUtils::xssClean($searchDateValue);

        //추가적으로 날짜인지 확인하기
        if (!preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $wDate[$searchDateKey])) {
          $wDate[$searchDateKey] = date('Y-m-d');
        }
      }

      if (DateTimeUtils::intervalDay($wDate[0], $wDate[1]) > 365) {
        throw new AlertBackException(__('1년이상 기간으로 검색하실 수 없습니다.'));
      }

      // 사용자 반품/교환/환불 신청 사용여부
      if (gd_is_plus_shop(PLUSSHOP_CODE_USEREXCHANGE) === true) {
        $orderBasic = gd_policy('order.basic');
        $this->setData('userHandleFl', gd_isset($orderBasic['userHandleFl'], 'y') === 'y');
      }

      // 주문 리스트 정보
      $this->setData('startDate', gd_isset($wDate[0]));
      $this->setData('endDate', gd_isset($wDate[1]));
      $this->setData('wDate', gd_isset($wDate));

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

      $this->setData('scheduledDeliveryList', gd_isset($scheduledDeliveryList));

      // 페이지 재설정
      $page = App::load('\\Component\\Page\\Page');
      $this->setData('page', gd_isset($page));
      $this->setData('total', $page->recode['total']);
      $this->setData('goodsReviewId', Board::BASIC_GOODS_REIVEW_ID);
      $this->setData('mode', 'list');
    } catch (AlertBackException $e) {
      throw new AlertBackException($e->getMessage());
    } catch (Exception $e) {
      throw new AlertRedirectException($e->getMessage(), null, null, URI_HOME);
    }
  }
}
