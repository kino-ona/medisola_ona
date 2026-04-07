<?php

namespace Controller\Front\GiftOrder;

use App;

/**
* 배송주소 입력시간 SMS만료 안내 
*
* @author webnmobile
*/
class SendExpireSmsController extends \Controller\Front\Controller
{
	public function index()
	{
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		$giftOrder->sendExpireSms();
		exit;
	}
}