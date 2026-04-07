<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

namespace Controller\Front\Mypage;

use Request;
use Framework\Utility\StringUtils;

/**
 * 마이페이지 > 주문배송/조회
 *
 * @package Bundle\Controller\Front\Mypage
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>/**
 */
class OrderListController extends \Bundle\Controller\Front\Mypage\OrderListController
{
	public function index()
	{
		// 기본 조회 일자 변경 ///////////////////////////////////////
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
		Request::get()->set('wDate', $wDate);
		// 기본 조회 일자 변경 ///////////////////////////////////////

		parent::index();

		/* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */
		$useGift = \App::load('\\Component\\Wm\\UseGift');
		$orderData = $this->getData('orderData');

		$firstDelivery = \App::load(\Component\Wm\FirstDelivery::class);

		foreach ($orderData as $key => $val) {
			foreach ($val as $key2 => $val2) {
				$isGiftOrder = $useGift->getGiftUse($val['orderNo']);
				$orderData[$key]['goods'][0]['isGiftOrder'] = $isGiftOrder['isGiftOrder'];
			}
		}
		/* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */

		foreach ($orderData as $key => $value) {
			$filteredGoodsCnt = 0;
			foreach ($value['goods'] as $key_1 => $goods) {
				$firstDate = $firstDelivery->getFirstDeliveryOrder($goods['sno']);
				if ($firstDate) {
					$firstDate = substr($firstDate, 0, 10);
					$firstDate = substr($firstDate, 5, 5);
					$firstDate = str_replace('-', '월 ', $firstDate);
					$firstDate .= '일';
					
					$orderData[$key]['goods'][$key_1]['firstDate'] = $firstDate;
				}

				if ($goods["isComponentGoods"] != true) {
					$filteredGoodsCnt++;
				}
			}
			$orderData[$key]['orderGoodsCnt'] = $filteredGoodsCnt;
		}
		$this->setData('orderData', gd_isset($orderData));
	}
}
