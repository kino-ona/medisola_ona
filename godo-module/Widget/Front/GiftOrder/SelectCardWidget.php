<?php

namespace Widget\Front\GiftOrder;

use App;

/**
* 선물하기 주문서 카드 및 받는분 정보 입력 
*
* @package Widget\Front\GiftOrder
* @author webnmobile
*/
class SelectCardWidget extends \Widget\Front\Widget
{
	public function index()
	{
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		$cards = $giftOrder->getCards();
		$this->setData("cards", $cards);
		
	}
}