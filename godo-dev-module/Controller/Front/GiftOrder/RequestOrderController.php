<?php

namespace Controller\Front\GiftOrder;

use App;
use Request;
use Exception;

/**
* 선물 요청하기 주문 처리 
*
* @author webnmobile
*/
class RequestOrderController extends \Controller\Front\Controller
{
	public function index()
	{
		try {
			$idx = Request::get()->get('idx');
			if (!$idx) {
				return new Exception("잘못된 접근입니다.");
			}
			
			$cartGift = App::load(\Component\GiftOrder\CartGift::class);
			$db = App::load(\DB::class);
			
			$sql = "SELECT * FROM wm_giftRequest WHERE idx = ?";
			$info = $db->query_fetch($sql, ["i", $idx], false);
			
			if (!$info || !$info['goodsNo'])
				return new Exception("신청정보가 존재하지 않습니다.");
			
			if ($info['orderNo']) {
				$sql = "SELECT orderStatus FROM " . DB_ORDER . " WHERE orderNo = ?";
				$row = $db->query_fetch($sql, ["s", $info['orderNo']], false);
				if ($row && in_array(substr($row['orderStatus'], 0, 1), ["o", "p", "g","d","s"])) {
					throw new Exception("이미 선물하셨습니다.");
				}				
			}
			
			$sql = "SELECT sno FROM " . DB_GOODS_OPTION . " WHERE goodsNo = ? AND optionSellFl='y'  ORDER BY optionNo ASC LIMIT 0, 1";
			$row = $db->query_fetch($sql, ["i", $info['goodsNo']], false);
			
			$sql = "SELECT goodsPrice FROM es_goods WHERE goodsNo = '{$info['goodsNo']}'";
			$goodsPrice = $db->fetch($sql);
			
			if (!$row)
				throw new Exception("신청 상품이 존재하지 않습니다.");
			
			$arrData = [
				'cartMode' => 'd',
				'goodsNo' => $info['goodsNo'],
				'goodsCnt' => gd_isset($info['goodsCnt'], 1),
				'goodsPrice' => $goodsPrice['goodsPrice'],
				'optionSno' => $row['sno'],
			];
			$cartSno = $cartGift->saveGoodsToCart($arrData);
			
			$url = "../gift_order/order.php?cartIdx=[{$cartSno}]&idx_request=".$idx;
			//$this->js("location.href ='".$url."'");
			header("Location: {$url}");
		} catch (Exception $e) {
			return $this->js("alert('".$e->getMessage()."');self.close();");
		}
		exit;
	}
}