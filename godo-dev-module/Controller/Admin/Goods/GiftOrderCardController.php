<?php

namespace Controller\Admin\Goods;

use App;

/**
* 선물하기 카드 설정 
*
* @author webnmobile
*/
class GiftOrderCardController extends \Controller\Admin\Controller
{
	public function index()
	{
		$this->callMenu("goods", "giftOrder", "card");
		
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		$cfg = $giftOrder->getCfg();
		$this->setData("cfg", $cfg);
		
		$list = $giftOrder->getCards();
		$this->setData("list", $list);
	}
}