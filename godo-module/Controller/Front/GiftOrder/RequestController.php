<?php

namespace Controller\Front\GiftOrder;

use App;
use Request;
use Session;
use Exception;

/**
* 선물 조르기 요청 페이지 
*
* @author webnmobile
*/
class RequestController extends \Controller\Front\Controller
{
	public function index()
	{
		try {
			$goodsNo = Request::get()->get("goodsNo");
			if (!$goodsNo) {
				throw new Exception("잘못된 접근입니다.");
			}
						
			$goodsCnt = Request::get()->get("goodsCnt", 1);
			
			$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
			$info = $giftOrder->getGiftRequestInfo($goodsNo, $goodsCnt);
			
			if (!$info['goods']) {
				throw new Exception("존재하지 않는 상품입니다.");
			}
			
			$this->setData($info);
			$this->setData("goodsNo", $goodsNo);
			$this->setData("goodsCnt", $goodsCnt);
		} catch (Exception $e) {
			return $this->js("alert('".$e->getMessage() ."');parent.wmLayer.close();");
			exit;
		}
	}
}