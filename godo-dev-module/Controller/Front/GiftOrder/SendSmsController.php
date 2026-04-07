<?php

namespace Controller\Front\GiftOrder;

use App;

/**
* 선물하기 SMS 일괄 전송 
*
* @author webnmoble
*/
class SendSmsController extends \Controller\Front\Controller
{
	public function index()
	{
		$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
		$db = App::load(\DB::class);
		$cfg = $giftOrder->getCfg();
		if (!$cfg['isUse'] || !$cfg['orderStatus'])
			exit;
		
		$inWhere = $bind = [];
		foreach ($cfg['orderStatus'] as $orderStatus) {
			$inWhere[] = "?";
			$db->bind_param_push($bind, "s", $orderStatus);
		}
		
		$conds = $inWhere?" AND SUBSTR(o.orderStatus, 1, 1) IN (" . implode(',', $inWhere).")":"";
		
		$sql = "SELECT oi.orderNo FROM " . DB_ORDER_INFO . " AS oi 
					INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo 
				WHERE oi.isGiftOrder='1'{$conds} AND oi.giftSmsStamp = 0 ORDER BY oi.regDt";
		$list = $db->query_fetch($sql, $bind);
		if ($list) {
			foreach ($list as $li) {
				$giftOrder->sendGiftSms($li['orderNo']);
			}
			
		}
		exit;
	}
}