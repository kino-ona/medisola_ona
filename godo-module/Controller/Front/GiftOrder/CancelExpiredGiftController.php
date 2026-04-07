<?php

namespace Controller\Front\GiftOrder;

use App;

/**
* 만료 선물 자동 취소 
*
* @author webnmobile
*/
class CancelExpiredGiftController extends \Controller\Front\Controller
{
	public function index()
	{
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		$giftOrder->cancelExpiredGiftOrder();
		exit;
	}
}