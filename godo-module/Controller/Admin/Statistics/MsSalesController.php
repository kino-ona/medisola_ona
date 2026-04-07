<?php

/**
 * 
 */

namespace Controller\Admin\Statistics;

use Component\Order\OrderSalesStatistics;
use Component\Mall\Mall;
use Component\Member\Manager;
use DateTime;
use Exception;
use Framework\Utility\NumberUtils;
use Request;
use Session;

/**
 * [관리자 모드] 매출분석 > 매출통계 페이지
 *
 * @package Bundle\Controller\Admin\Statistics
 * @author  su
 */
class MsSalesController extends \Controller\Admin\Controller
{
  public function index()
  {
    try {
      // 메뉴 설정
      $this->callMenu('statistics', 'medisola', 'sales');

      // $mall = new Mall();
      // $searchMallList = $mall->getStatisticsMallList();
      // $this->setData('searchMallList', $searchMallList);

      // $searchMall = Request::get()->get('mallFl');
      // if (!$searchMall) {
      //   $searchMall = 'all';
      // }
      // $searchDevice = Request::get()->get('searchDevice');
      // if (!$searchDevice) {
      //   $searchDevice = 'all';
      // }
      $searchPeriod = Request::get()->get('searchPeriod');
      $searchDate = Request::get()->get('searchDate');
      $tabName = Request::get()->get('tabName');
      $splitFirstOrder = Request::get()->get('splitFirstOrder');

      if (!$tabName) {
        $tabName = 'member';
      }

      // 기본값 설정: 이번 달 1일 ~ 말일 (날짜 입력란에 값이 없을 때만)
      if (!$searchDate[0]) {
        $searchDate[0] = date('Ym01');
      }

      if (!$searchDate[1]) {
        $searchDate[1] = date('Ymt');
      }

      // 최대 기간 제한 (2년)
      $sDate = new DateTime($searchDate[0]);
      $eDate = new DateTime($searchDate[1]);
      $dateDiff = date_diff($sDate, $eDate);
      if ($dateDiff->days > 730) {
        $searchDate[0] = date('Ymd', strtotime($searchDate[1] . ' -2 years'));
        $searchPeriod = null;
      }

      // $checked['searchMall'][$searchMall] = 'checked="checked"';
      $checked['searchPeriod'][$searchPeriod] = 'checked="checked"';
      // $checked['searchDevice'][$searchDevice] = 'selected="selected"';
      $checked['splitFirstOrder'] = ($splitFirstOrder == 'y') ? 'checked="checked"' : '';
      $active['searchPeriod'][$searchPeriod] = 'active';
      $this->setData('searchDate', $searchDate);
      $this->setData('checked', $checked);
      $this->setData('active', $active);

      // 모듈 호출
      $orderSalesStatistics = new OrderSalesStatistics();
      $order['orderYMD'] = $searchDate;
      // $order['mallSno'] = $searchMall;
      // if (Manager::isProvider()) {
      //   $order['scmNo'] = Session::get('manager.scmNo');
      // }
      // $order['searchDevice'] = $searchDevice;
      $order['splitFirstOrder'] = ($splitFirstOrder == 'y') ? true : false;

      $data = null;

      switch($tabName) {
        case 'member':
          $data = $orderSalesStatistics->fetchSalesByMemberType($order);
          break;
        case 'week':
          $data = $orderSalesStatistics->fetchSalesByWeekType($order);
          break;
        case 'goods':
          $data = $orderSalesStatistics->fetchSalesByGoods($order);
          break;
      }
      

      // // 월별 매출 통계 데이터 테이블 생성
      // $returnOrderStatistics = [];
      // $daySales = []; // 일별 최대/최소 매출
      // $deviceSales = []; // 디바이스별 매출
      // $i = 0;
      // foreach ($data as $key => $val) {
      //     $orderDayOrderSalesPrice = 0;
      //     $orderDayGoodsPrice = 0;
      //     $orderDayGoodsDcPrice = 0;
      //     $orderDayGoodsTotal = 0;
      //     $orderDayDeliveryPrice = 0;
      //     $orderDayDeliveryDcPrice = 0;
      //     $orderDayDeliveryTotal = 0;
      //     $orderDayTotalPrice = 0;
      //     $orderDayRefundGoodsPrice = 0;
      //     $orderDayRefundDeliveryPrice = 0;
      //     $orderDayRefundFeePrice = 0;
      //     $orderDayRefundTotal = 0;
      //     $returnOrderStatistics[$i]['_extraData']['className']['row'] = ['day-border'];
      //     $returnOrderStatistics[$i]['_extraData']['rowSpan']['paymentDate'] = 4;
      //     $returnOrderStatistics[$i]['paymentDate'] = substr($key,0,4) . '-' . substr($key,4,2);
      //     foreach ($val as $deviceKey => $deviceVal) {
      //         $returnOrderStatistics[$i]['_extraData']['className']['column']['orderSalesPrice'] = ['sales-price', 'bold'];
      //         $returnOrderStatistics[$i]['_extraData']['className']['column']['goodsTotal'] = ['goods-price'];
      //         $returnOrderStatistics[$i]['_extraData']['className']['column']['totalPrice'] = ['total-price'];
      //         $returnOrderStatistics[$i]['_extraData']['className']['column']['refundTotal'] = ['refund-price'];
      //         if ($deviceKey == 'write') {
      //             $device = '수기주문';
      //         } else {
      //             $device = $deviceKey;
      //         }
      //         $returnOrderStatistics[$i]['orderDevice'] = $device;
      //         $returnOrderStatistics[$i]['goodsPrice'] = NumberUtils::moneyFormat($deviceVal['goodsPrice']);
      //         $returnOrderStatistics[$i]['goodsDcPrice'] = NumberUtils::moneyFormat($deviceVal['goodsDcPrice']);
      //         $returnOrderStatistics[$i]['goodsTotal'] = NumberUtils::moneyFormat($deviceVal['goodsTotal']);
      //         $returnOrderStatistics[$i]['deliveryPrice'] = NumberUtils::moneyFormat($deviceVal['deliveryPrice']);
      //         $returnOrderStatistics[$i]['deliveryDcPrice'] = NumberUtils::moneyFormat($deviceVal['deliveryDcPrice']);
      //         $returnOrderStatistics[$i]['deliveryTotal'] = NumberUtils::moneyFormat($deviceVal['deliveryTotal']);
      //         $returnOrderStatistics[$i]['totalPrice'] = NumberUtils::moneyFormat($deviceVal['goodsTotal'] + $deviceVal['deliveryTotal']);
      //         $returnOrderStatistics[$i]['refundGoodsPrice'] = NumberUtils::moneyFormat($deviceVal['refundGoodsPrice']);
      //         $returnOrderStatistics[$i]['refundDeliveryPrice'] = NumberUtils::moneyFormat($deviceVal['refundDeliveryPrice']);
      //         $returnOrderStatistics[$i]['refundFeePrice'] = NumberUtils::moneyFormat($deviceVal['refundFeePrice']);
      //         $returnOrderStatistics[$i]['refundTotal'] = NumberUtils::moneyFormat($deviceVal['refundTotal']);
      //         $returnOrderStatistics[$i]['orderSalesPrice'] = NumberUtils::moneyFormat($deviceVal['goodsTotal'] + $deviceVal['deliveryTotal'] - $deviceVal['refundTotal']);

      //         $orderDayOrderSalesPrice += $deviceVal['goodsTotal'] + $deviceVal['deliveryTotal'] - $deviceVal['refundTotal'];
      //         $orderDayGoodsPrice += $deviceVal['goodsPrice'];
      //         $orderDayGoodsDcPrice += $deviceVal['goodsDcPrice'];
      //         $orderDayGoodsTotal += $deviceVal['goodsTotal'];
      //         $orderDayDeliveryPrice += $deviceVal['deliveryPrice'];
      //         $orderDayDeliveryDcPrice += $deviceVal['deliveryDcPrice'];
      //         $orderDayDeliveryTotal += $deviceVal['deliveryTotal'];
      //         $orderDayTotalPrice += $deviceVal['goodsTotal'] + $deviceVal['deliveryTotal'];
      //         $orderDayRefundGoodsPrice += $deviceVal['refundGoodsPrice'];
      //         $orderDayRefundDeliveryPrice += $deviceVal['refundDeliveryPrice'];
      //         $orderDayRefundFeePrice += $deviceVal['refundFeePrice'];
      //         $orderDayRefundTotal += $deviceVal['refundTotal'];
      //         $i++;

      //         // 디바이스별 매출 합계
      //         $deviceSales[$deviceKey]['sales'] += $deviceVal['goodsTotal'] + $deviceVal['deliveryTotal']; // 판매
      //         $deviceSales[$deviceKey]['refund'] += $deviceVal['refundTotal']; // 환불

      //         // 일별 최대/최소 매출을 위한 합계
      //         $daySales[$key]['goods'] += $deviceVal['goodsTotal']; // 상품 판매
      //         $daySales[$key]['delivery'] += $deviceVal['deliveryTotal']; // 배송비 판매
      //         $daySales[$key]['refund'] += $deviceVal['refundTotal']; // 환불
      //     }
      //     $returnOrderStatistics[$i]['_extraData']['className']['row'] = ['bold', 'day-price'];
      //     $returnOrderStatistics[$i]['_extraData']['className']['column']['orderSalesPrice'] = ['sales-price'];
      //     $returnOrderStatistics[$i]['_extraData']['className']['column']['goodsTotal'] = ['goods-price'];
      //     $returnOrderStatistics[$i]['_extraData']['className']['column']['totalPrice'] = ['total-price'];
      //     $returnOrderStatistics[$i]['_extraData']['className']['column']['refundTotal'] = ['refund-price'];
      //     $returnOrderStatistics[$i]['orderDevice'] = '총액';
      //     $returnOrderStatistics[$i]['goodsPrice'] = NumberUtils::moneyFormat($orderDayGoodsPrice);
      //     $returnOrderStatistics[$i]['goodsDcPrice'] = NumberUtils::moneyFormat($orderDayGoodsDcPrice);
      //     $returnOrderStatistics[$i]['goodsTotal'] = NumberUtils::moneyFormat($orderDayGoodsTotal);
      //     $returnOrderStatistics[$i]['deliveryPrice'] = NumberUtils::moneyFormat($orderDayDeliveryPrice);
      //     $returnOrderStatistics[$i]['deliveryDcPrice'] = NumberUtils::moneyFormat($orderDayDeliveryDcPrice);
      //     $returnOrderStatistics[$i]['deliveryTotal'] = NumberUtils::moneyFormat($orderDayDeliveryTotal);
      //     $returnOrderStatistics[$i]['totalPrice'] = NumberUtils::moneyFormat($orderDayTotalPrice);
      //     $returnOrderStatistics[$i]['refundGoodsPrice'] = NumberUtils::moneyFormat($orderDayRefundGoodsPrice);
      //     $returnOrderStatistics[$i]['refundDeliveryPrice'] = NumberUtils::moneyFormat($orderDayRefundDeliveryPrice);
      //     $returnOrderStatistics[$i]['refundFeePrice'] = NumberUtils::moneyFormat($orderDayRefundFeePrice);
      //     $returnOrderStatistics[$i]['refundTotal'] = NumberUtils::moneyFormat($orderDayRefundTotal);
      //     $returnOrderStatistics[$i]['orderSalesPrice'] = NumberUtils::moneyFormat($orderDayOrderSalesPrice);
      //     $i++;
      // }

      // $daySalesTotal['min']['price'] = 0;
      // $daySalesTotal['max']['price'] = 0;
      // foreach ($daySales as $key => $val) {
      //     $sales = $val['goods'] + $val['delivery'];
      //     $refund = $val['refund'];
      //     $salesTotal = $val['goods'] + $val['delivery'] - $val['refund'];
      //     if ($daySalesTotal['min']['price'] >= $salesTotal) {
      //         $daySalesTotal['min']['price'] = $salesTotal;
      //         $daySalesTotal['min']['date'] = substr($key,0,4) . '-' . substr($key,4,2);
      //     }
      //     if ($daySalesTotal['max']['price'] <= $salesTotal) {
      //         $daySalesTotal['max']['price'] = $salesTotal;
      //         $daySalesTotal['max']['date'] = substr($key,0,4) . '-' . substr($key,4,2);
      //     }
      //     $daySalesTotal['all']['sales'] += $sales;
      //     $daySalesTotal['all']['refund'] += $refund;
      // }

      // echo '<pre>' . var_export($returnOrderStatistics, true) . '</pre>';
      //  exit();
      // 총 합계
      // $this->setData('deviceSales', gd_isset($deviceSales));

      // 총 최대/최소
      // $this->setData('daySalesTotal', gd_isset($daySalesTotal));

      $count = count($data);
      if ($count > 20) {
        $displayLimit = 20;
      } else if ($count == 0) {
        $displayLimit = 5;
      } else {
        $displayLimit = $count;
      }

      $this->setData('data', $data);
      $this->setData('count', $count);
      $this->setData('displayLimit', $displayLimit);
      $this->setData('tabName', $tabName);

      $this->getView()->setPageName('statistics/ms_sales.php');

      $this->addScript(
        [
          'backbone/backbone-min.js',
          'tui/code-snippet.min.js',
          'tui.grid/grid.min.js',
        ]
      );
      $this->addCss(
        [
          'tui.grid/grid.css',
        ]
      );

      // 쿼리스트링
      $queryString = Request::getQueryString();
      $queryString = preg_replace('/&?tabName=[^&]*/', '', $queryString);
      $queryString = ltrim($queryString, '&');
      $queryString = ltrim($queryString, '?');

      
      if ($queryString) {
        $queryString = '?' . $queryString . '&';
      } else {
        $queryString = '?';
      }
      
      $this->setData('queryString', $queryString);
    } catch (Exception $e) {
      throw new AlertBackException($e->getMessage());
    }
  }
}
