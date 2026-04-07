<?php

namespace Controller\Admin\Goods;

use App;
use Request;
use Component\Wm\FirstDelivery;
/**
 * 주문페이지 배송정보 입력 페이지
 * 
 */

class FirstDeliveryHolidayController extends \Controller\Admin\Controller
{
	public function index()
	{
		$this->callMenu('goods' , 'firstDelivery' , 'first_delivery_holiday');
		
		$in = \Request::request()->toArray();
		
		$first = App::load(FirstDelivery::class);
		$list = $first->holiday($in['year']);
		$this->setData('list' , $list);
	
		$years = [];

		foreach($list as $key => $value){
			$years[] = date('Y' , $key);
		}
		
		$years = array_unique($years);
		sort($years);
		
		$this->setData('years' , $years);
		$this->setData('selectYear' , $in['year']);
	}
}