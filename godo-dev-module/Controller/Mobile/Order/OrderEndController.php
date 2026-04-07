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
namespace Controller\Mobile\Order;

use App;
use Request;
use Component\Wm\FirstDelivery;

/**
 * 주문 완료 페이지
 *
 * @package Bundle\Controller\Mobile\Order
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class OrderEndController extends \Bundle\Controller\Mobile\Order\OrderEndController
{
	public function index()
	{
		parent::index();

		$first = \App::load(FirstDelivery::class);
		$orderInfo = $this->getData('orderInfo');
		$firstFl = false;
		foreach($orderInfo['goods'] as $key => $value){
			$firstDate = $first-> getFirstDeliveryOrder($value['sno']);
			$orderInfo['goods'][$key]['firstDate'] = $firstDate;
			if($firstDate > 0){
				$firstFl = true;
			}
		}
		$orderInfo['firstFl'] = $firstFl;

		$this->setData('orderInfo' , $orderInfo);
		
	}
	public function post()
	{
		//if(\Request::getRemoteAddress()=='182.216.219.157' || \Request::getRemoteAddress()=='112.146.205.124'){
			$orderNo = Request::get()->get("orderNo");
			if ($orderNo) {
				/** 튜닝 - 2023-06-09, 선물하기 주문 장바구니 삭제 */
				$cartGift = App::load(\Component\GiftOrder\CartGift::class);
				$cartGift->setCartRemove($orderNo);
							
				/** 튜닝 - 2023-06-09, 선물 요청 주문 업데이트 */
				$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
				//$giftOrder->updateGiftRequest($orderNo);
				
				$order = App::load(\Component\Order\Order::class);
				$orderItems = $order->getOrderGoods($orderNo);
				$this->setData("orderItems", $orderItems);
				
				// 상태값이 결제완료인 경우 p1 -> p2로 변경 (사방넷 관련)
				//$giftOrder -> GiftStatusChange($orderNo);
				$giftOrder->sendGiftSms($orderNo);	
			}
		//}
	}

}