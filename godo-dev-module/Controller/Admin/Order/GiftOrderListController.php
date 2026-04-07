<?php

namespace Controller\Admin\Order;

use App;
use Request;

/**
* 선물하기 주문목록 
* 
* @author webnmobile
*/ 
class GiftOrderListController extends \Controller\Admin\Controller
{
	public function index()
	{
		$this->callMenu("order", "order", "gift_order_list");
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		$in = Request::get()->all();

		$result = $giftOrder->getList($in['page'], $in['search_view_list'], $in);

		$getDays = $giftOrder ->getDays();
		//if(Request::getRemoteAddress()=='112.145.36.156'){
		foreach($result['list'] as $key => $value){
			$stamp = strtotime($value['regDt']) + (60 * 60 * 24 * $getDays['expireDays']); // + (60 * 60 * 24 * $getDays['expireDays'])
			$date = date("Y-m-d", $stamp);
			$result['list'][$key]['deadline'] = $date;
		}
		//}
		
		$this->setData($result);
		$this->setData("search", $in);
		
	}
}