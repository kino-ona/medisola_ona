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
 * @link      http://www.godo.co.kr
 */

namespace Controller\Mobile\Mypage;

use Request;

/**
 * 마이페이지 > 주문배송/조회
 *
 * @package Bundle\Controller\Mobile\Mypage
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>/**
 */
class OrderListController extends \Bundle\Controller\Mobile\Mypage\OrderListController
{
	public function index()
	{
		// 기본 조회 일자 변경 ///////////////////////////////////////
		if (is_numeric(Request::get()->get('searchPeriod')) === true && Request::get()->get('searchPeriod') >= 0) {
				$selectDate = Request::get()->get('searchPeriod');
		} else {
				$selectDate = 30;
		}
		Request::get()->set('searchPeriod', $selectDate);
		// 기본 조회 일자 변경 ///////////////////////////////////////

		parent::index();

		$firstDelivery = \App::load(\Component\Wm\FirstDelivery::class);

		/* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */
		$useGift = \App::load('\\Component\\Wm\\UseGift');
		$ordersByRegisterDay = $this->getData('ordersByRegisterDay');

		foreach ($ordersByRegisterDay as $key => $orders) {
			foreach ($orders as $key2 => $order) {
				$isGiftOrder = $useGift->getGiftUse($order['orderNo']);
				if ($ordersByRegisterDay[$key][$key2]['goods']) {
					$ordersByRegisterDay[$key][$key2]['goods'][0]['isGiftOrder'] = $isGiftOrder['isGiftOrder'];
				}
			}
		}
		/* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */

		foreach ($ordersByRegisterDay as $key => $value) {
			foreach ($value as $key_1 => $value_1) {
				$filteredGoodsCnt = 0;
				foreach ($value_1['goods'] as $key_2 => $goods) {
					$firstDate = $firstDelivery->getFirstDeliveryOrder($goods['sno']);
					if ($firstDate) {
						$firstDate = substr($firstDate, 0, 10);
						$firstDate = substr($firstDate, 5, 5);
						$firstDate = str_replace('-', '월 ', $firstDate);
						$firstDate .= '일';

						$ordersByRegisterDay[$key][$key_1]['goods'][$key_2]['firstDate'] = $firstDate;
					}

					if ($goods["goodsType"] != 'addGoods' || 
							($goods["goodsType"] == 'addGoods' && $goods["isComponentGoods"] != true && $goods["goodsNm"] != '선택 메뉴 추가 금액')) 
					{
						$filteredGoodsCnt++;
					}
				}
				$ordersByRegisterDay[$key][$key_1]['orderGoodsCnt'] = $filteredGoodsCnt;
			}
		}

		$this->setData('ordersByRegisterDay', $ordersByRegisterDay);
	}
}
