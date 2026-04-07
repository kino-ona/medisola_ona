<?php

namespace Controller\Front\GiftOrder;

use App;
use Request;
use Exception;

/**
* 선물하기 카드유형 변경에 따른 이미지 추출 
*
* @author webnmobile
*/
class AjaxSelectCardController extends \Controller\Front\Controller
{
	public function index()
	{
		try {
			$in = Request::request()->all();
			if (empty($in['cardType']))
				throw new Exception("카드유형을 선택하세요.");
			
			$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
			$list = $giftOrder->getCards($in['cardType']);
			$this->json([
				'error' => 0,
				'cards' => $list,
			]);
		} catch (Exception $e) {
			$this->json([
				'error' => 1,
				'message' => $e->getMessage(),
			]);
		}
		
		exit;
	}
}