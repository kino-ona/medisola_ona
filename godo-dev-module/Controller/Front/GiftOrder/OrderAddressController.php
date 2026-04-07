<?php

namespace Controller\Front\GiftOrder;

use App;
use Request;
use Framework\Debug\Exception\AlertOnlyException;

/**
* 선물하기 페이지 
*
* @author webnmobile
*/
class OrderAddressController extends \Controller\Front\Controller
{
	public function index()
	{
		try {
			$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
			$orderNo = Request::get()->get("orderNo");
			if (!$orderNo)
				throw new AlertOnlyException("잘못된 접근입니다.");
			
			$info = $giftOrder->getOrder($orderNo);
			if (!$info) {
				throw new AlertOnlyException("존재하지 않는 주문입니다.");
			}

            // 2023-03-24 웹앤모바일 입력기한 추가
            $getDays = $giftOrder->getDays();
            $stamp = strtotime($info['regDt']) + (60 * 60 * 24 * $getDays['expireDays']); // + (60 * 60 * 24 * $getDays['expireDays'])
            $date = date("Y-m-d", $stamp);
            $info['deadline'] = $date;
			$this->setData($info);

		} catch (AlertOnlyException $e) {
			$this->js("alert('".$e->getMessage() . "');self.close();");
		}
	}
}