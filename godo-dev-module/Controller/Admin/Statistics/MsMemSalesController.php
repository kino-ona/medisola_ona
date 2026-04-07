<?php

/**
 * 
 */

namespace Controller\Admin\Statistics;

use Framework\Debug\Exception\AlertBackException;
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
class MsMemSalesController extends \Controller\Admin\Controller
{
  public function index()
  {
    try {
      // 메뉴 설정
      $this->callMenu('statistics', 'medisola', 'mem_sales');

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
      $nWeek = Request::get()->get('nWeek');

      if(!$nWeek) {
        $nWeek = 1;
      }

      if (!$tabName) {
        $tabName = 'member';
      }


      if (!$searchDate[0]) {
        $searchDate[0] = date('Ymd', strtotime('-3 months'));
      }

      if (!$searchDate[1]) {
        $eDate = new DateTime();
        $searchDate[1] = $eDate->format('Ymd');
      }

      // $sDate = new DateTime();
      // $eDate = new DateTime('yesterday');

      // if (!$searchDate[0]) {
      //     $searchDate[0] = $sDate->modify('-6 days')->format('Ymd');
      // } else {
      //     $startDate = new DateTime($searchDate[0]);
      //     if ($sDate->format('Ymd') <= $startDate->format('Ymd')) {
      //         $searchDate[0] = $sDate->format('Ymd');
      //     } else {
      //         $searchDate[0] = $startDate->format('Ymd');
      //     }
      // }
      // if (!$searchDate[1]) {
      //     $searchDate[1] = $eDate->format('Ymd');
      // } else {
      //     $endDate = new DateTime($searchDate[1]);
      //     if ($eDate->format('Ymd') <= $endDate->format('Ymd') || (strlen($searchDate[1]) < 10) && $endDate->format('Ym') == $eDate->format('Ym')) {
      //         $searchDate[1] = $eDate->format('Ymd');
      //     } else {
      //         if (strlen($searchDate[1]) == 10) {
      //             $searchDate[1] = $endDate->format('Ymd');
      //         } else {
      //             $date = $endDate->format('d');
      //             $searchDate[1] = $endDate->add(new \DateInterval('P1M'))->modify('-' . $date . ' days')->format('Ymd');
      //         }
      //     }
      // }

      // $sDate = new DateTime($searchDate[0]);
      // $eDate = new DateTime($searchDate[1]);
      // $dateDiff = date_diff($sDate, $eDate);
      // if ($dateDiff->days > 90) {
      //     $sDate = $eDate->modify('-6 day');
      //     $searchDate[0] = $sDate->format('Ymd');
      //     $searchPeriod = 6;
      // }

      // echo '<pre>' . var_export($dateDiff->days, true) . '</pre>';
      // echo '<pre>' . var_export($searchDate, true) . '</pre>';
      
       

      // $checked['searchMall'][$searchMall] = 'checked="checked"';
      $checked['searchPeriod'][$searchPeriod] = 'checked="checked"';
      // $checked['searchDevice'][$searchDevice] = 'selected="selected"';
      $active['searchPeriod'][$searchPeriod] = 'active';
      $this->setData('searchDate', $searchDate);
      $this->setData('checked', $checked);
      $this->setData('active', $active);

      // 모듈 호출
      $orderSalesStatistics = new OrderSalesStatistics();
      $searchData['orderYMD'] = $searchDate;
      $searchData['nWeek'] = $nWeek;
      // $order['mallSno'] = $searchMall;
      // if (Manager::isProvider()) {
      //   $order['scmNo'] = Session::get('manager.scmNo');
      // }
      // $order['searchDevice'] = $searchDevice;

      $data = null;

      switch($tabName) {
        case 'member':
          $data = $orderSalesStatistics->fetchMemSalesByMember($searchData);
          break;
        case 'contact':
          $data = $orderSalesStatistics->fetchMemSalesByContact($searchData);
          break;
      }
      

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
      $this->setData('nWeek', $nWeek);

      $this->getView()->setPageName('statistics/ms_mem_sales.php');

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
