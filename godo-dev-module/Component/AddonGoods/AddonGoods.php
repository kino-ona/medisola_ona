<?php

namespace Component\AddonGoods;

use App;
use Request;
use Session;
use Exception;
use Component\Traits\GoodsInfo;

/**
* 증정상품 관련
*
* @author webnmobile
*/
class AddonGoods
{
	use GoodsInfo;
	
	private $db;
	
	public function __construct()
	{
		$this->db = App::load(\DB::class);
	}
	
	/**
	* 증정상품 추출 
	*
	* @param Integer $goodsNo 상품번호 
	* @return Array
	*/
	public function getList($goodsNo = null)
	{
		if ($goodsNo) {
			$sql = "SELECT * FROM wm_addonGoods WHERE rootGoodsNo = ? ORDER BY listOrder desc, regDt asc";
			$list = $this->db->query_fetch($sql, ["i", $goodsNo]);
			if ($list) {
				foreach ($list as $k => $v) {
					$goods = $this->getGoods($v['goodsNo']);
					if (!$goods)
						continue;
					
					$goods = $goods[0];
					$v['goods'] = gd_isset($goods, []);
					$list[$k] = $v;
				} // endforeach 
			} // endif 
		}
		
		return gd_isset($list, []);
	}
	
	/**
	* 주문상품에 증정품 적용 
	*
	* @param Array $cartSno 장바구니 sno
	* @param String $type 처리 구분, gift, cart, sub 
	*/
	public function apply($cartSno = [], $type = 'cart', $memNo = null)
	{
		/* 장바구니 객체 생성 S */
		$dbTable = "";
		switch ($type) {
			case "cart" : 
				$nsp = "\Component\Cart\Cart";
				$dbTable = DB_CART;
				break;
			case "gift" : 
				$nsp = "\Component\GiftOrder\CartGift";
				$dbTable = "wm_gift";
				break;
			case "sub" : 
				$nsp = "\Component\Subscription\CartSub";
				$dbTable = "wm_subCart";
				break;
			case "subAdmin" : 
				$nsp = "\Component\Subscription\CartSubAdmin";
				$dbTable = "wm_subCartAdmin";
				break;
		}
		
		if ($memNo && $type == 'subAdmin') {
			$cart = new $nsp($memNo);
		} else {
			$cart = new $nsp;
		}
		/* 장바구니 객체 생성 E */
		$cartSnos = [];
		$cartInfo = $cart->getCartGoodsData($cartSno, null, null, false, true);
		if ($cartInfo) {
			foreach ($cartInfo as $values) {
				foreach ($values as $value) {
					foreach ($value as $v) {
						if ($v['isAddOn']) continue;
						
						/* 증정 사용 여부 및 수량 체크 S */
						$sql = "SELECT useAddon, addOnCnt, addOnPrice, addOnOneTime, addOnApplyType FROM " . DB_GOODS . " WHERE goodsNo = ?";
						$row = $this->db->query_fetch($sql, ["i", $v['goodsNo']], false);
						if (!$row['useAddon'] || ($row['useAddon'] && $row['addOnApplyType'] == 'goodsCnt' && $v['goodsCnt'] < $row['addOnCnt']))
							continue;
						/* 증정 사용 여부 및 수량 체크 E */
						
						/* 가격 조건 체크 S */
						$price = ($v['price']['goodsPriceSum'] + $v['price']['optionPriceSum'] + $v['price']['optionTextPriceSum']) - ($v['price']['goodsDcPrice'] + $v['price']['goodsMemberDcPrice'] + $v['price']['goodsMemberOverlapDcPrice'] + $v['price']['goodsCouponGoodsDcPrice']);
						if ($row['addOnApplyType'] == 'price' && $row['addOnPrice'] > $price) {
							continue;
						}
						/* 가격 조건 체크 E */
						
						/* 사은품 추가 S */
						$list = $this->getList($v['goodsNo']);
						if (!$list) continue;
						
						/* 증정품 수량 */
						if ($row['addOnOneTime']) {
							$goodsCnt = 1;
						} else {
							if ($row['addOnApplyType'] == 'price') {
								$goodsCnt = ($row['addOnPrice'] > 0)?floor($price / $row['addOnPrice']):1;
							} else {
								$goodsCnt = ($row['addOnCnt'] > 0)?floor($v['goodsCnt'] / $row['addOnCnt']):1;
							}
						}				
						
						$this->db->set_delete_db($dbTable, "addOnRootSno = ?", ["i", $v['sno']]);
						$cartMode = ($v['directCart'] == 'y')?"d":"";
						
						$arrData = ['cartMode' => $cartMode];

						foreach ($list as $li) {
							$optionSno = gd_isset($li['goods']['option'][0]['sno'], 0);
							if (empty($optionSno)) {
								$sql = "SELECT sno FROM " . DB_GOODS_OPTION . " WHERE goodsNo = ? AND optionSellFl='y' ORDER BY optionNo";
								$op = $this->db->query_fetch($sql, ["i", $li['goodsNo']], false);
								if (!$op) continue;
								
								$optionSno = $op['sno'];
							}
							$li['goodsCnt'] = gd_isset($li['goodsCnt'], 1);
							$arrData['goodsNo'][] = $li['goodsNo'];
							$arrData['optionSno'][] = $optionSno;
							$arrData['goodsCnt'][] = $goodsCnt * $li['goodsCnt'];
							$arrData['optionText'][][89999] = "증정상품";
							$arrData['isAddOn'][] = 1;
							$arrData['addOnRootSno'][] = $v['sno'];
						} // endforeach 
						$r = $cart->saveInfoCart($arrData);
						if ($r) $cartSnos = array_merge($cartSnos, $r);
						/* 사은품 추가 E */
					} // endforeach 
				} // endforeach 
			} // endforeach
		} // endif 
		
		return $cartSnos;
	}
	
	/**
	* 세트상품 장바구니 조정 처리 
	*
	* @param Array $cartInfo 
	* @return Array
	*/
	public function adjustCart($cartInfo = [])
	{
		$cartInfo2 = $tmp = [];
		if ($cartInfo) {
			foreach ($cartInfo as $keys => $values) {
				foreach ($values as $key => $value) {
					foreach ($value as $k => $v) {
						$sno = $v['addOnRootSno']?$v['addOnRootSno']:$v['sno'];
						$isLast = $v['addOnRootSno']?1:0;
						$v['duplicationGoods'] = 'n';
						$tmp[$keys][$key][$sno][$isLast][] = $v;
					} // endforeach
				} // endforeach 
			} // endforeach 
			
			if ($tmp) {
				foreach ($tmp as $keys => $values) {
					foreach ($values as $key => $value) {
						foreach ($value as $k => $_list) {
							ksort($_list, SORT_NUMERIC);
							foreach ($_list as $k1 => $list) {
								foreach ($list as $_k => $v) {
									$cartInfo2[$keys][$key][] = $v;
								} // endforeach 
							}
						} // endforeach 
					} // endforeach 
				} // endforeach 
			} // endif 
		} // endif 

		return $cartInfo2;
	}
	
	/**
	* 장바구니에 추가된 증정상품 삭제 
	*
	* @param String $type 처리 구분, gift, cart, sub 
	*/
	public function clearCart($type = "cart")
	{
		/* 장바구니 객체 생성 S */
		$dbTable = "";
		switch ($type) {
			case "cart" : 
				$nsp = "\Component\Cart\Cart";
				$dbTable = DB_CART;
				break;
			case "gift" : 
				$nsp = "\Component\GiftOrder\CartGift";
				$dbTable = "wm_gift";
				break;
			case "sub" : 
				$nsp = "\Component\Subscription\CartSub";
				$dbTable = "wm_subCart";
				break;
		}
		
		$cart = new $nsp;
		/* 장바구니 객체 생성 E */
		
		$cartInfo = $cart->getCartGoodsData($cartSno, null, null, false, true);	
		if ($cartInfo) {
			foreach ($cartInfo as $values) {
				foreach ($values as  $value) {
					foreach ($value as $v) {
						if ($v['isAddOn']) {
							$this->db->set_delete_db($dbTable, "sno = ?", ["i", $v['sno']]);
						}
					}
				}
			}
		} // endif
	}
}