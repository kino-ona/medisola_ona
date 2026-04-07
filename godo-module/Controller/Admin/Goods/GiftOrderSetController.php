<?php

namespace Controller\Admin\Goods;

use App;
use Request;
/**
* 선물하기 사용설정 
* 
* @package Component\Admin\Goods
* @author webnmobile
*/
class GiftOrderSetController extends \Controller\Admin\Controller
{
	public function index()
	{
		$this->callMenu("goods", "giftOrder", "config");
		
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		
		$this->setData($giftOrder->getCfg());
	}
}