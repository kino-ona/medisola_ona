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
namespace Controller\Front\Order;

/**
 * 주문서 작성
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class OrderController extends \Bundle\Controller\Front\Order\OrderController
{
	public function index()
	{
		parent::index();
		$cartInfo = $this->getData('cartInfo');

		foreach($cartInfo as $key => $value){
			foreach($value as $key_1 => $value_1){
				foreach($value_1 as $key_2 => $value_2){
					if($value_2['firstDelivery'] > 0){
						$firstTime = strtotime($value_2['firstDelivery']);
						$md = date('m-d' , $firstTime);
						$md = str_replace('-' , '월 ', $md);
						$md .= '일';
						/*
						$daily = array('일','월','화','수','목','금','토');
						
						$w = $daily[date('w' , strtotime($value_2['firstDelivery']))];
						$cartInfo[$key][$key_1][$key_2]['firstDelivery'] = $w.'요일';
						*/
						$cartInfo[$key][$key_1][$key_2]['firstDelivery'] = $md;
					}
				}
			}
		}
		$this->setData('cartInfo' , $cartInfo);
		
	}
}