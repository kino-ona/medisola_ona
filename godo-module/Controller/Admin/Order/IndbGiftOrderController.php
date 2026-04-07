<?php

namespace Controller\Admin\Order;

use App;
use Request;
use Framework\Debug\Exception\AlertOnlyException;

/**
* 선물하기 DB 처리관련 
*
* @author webnmobile
*/
class IndbGiftOrderController extends \Controller\Admin\Controller
{
	public function index()
	{
		try {
			$in = Request::request()->all();
			$db = App::load(\DB::class);
			
			$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
			switch ($in['mode']) {
				/* SMS로 URL 전송 */
				case "send_sms" : 
					if (empty($in['orderNo']))
						throw new AlertOnlyException("잘못된 접근입니다.");
					
					if (!$giftOrder->sendGiftSms($in['orderNo'], true)) {
						throw new AlertOnlyException("전송에 실패하였습니다.");
					}
					
					return $this->layer("전송하였습니다.");
					break;
			}
		} catch (AlertOnlyException $e) {
			throw $e;
		}
		exit;
	}
}