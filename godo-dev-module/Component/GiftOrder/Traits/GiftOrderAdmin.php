<?php

namespace Component\GiftOrder\Traits;

use App;
use Request;

/**
* 선물하기 주문관리 
*
* @author webnmobile
*/
trait GiftOrderAdmin
{
	/**
	* 선물하기 주문목록 
	*
	* @param Integer $page 페이지 번호
	* @param Integer $limit 페이지당 레코드 수 
	* @param Array $search 검색처리 파라미터 
	* 
	* @return Array
	*/
	//선물하기 자동취소 됐을경우(환불) 선물하기 주문관리 목록에 남도록 'r' 추가
	public function getList($page = 1, $limit = 500, $search = [])
	{
		$page = gd_isset($page, 1);
		$limit = gd_isset($limit, 500);

		$status = $this->getOrderStatusList();
		
		$arrWhere = $bind = [];
		$sql = "SELECT COUNT(*) as cnt FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo= o.orderNo  
					WHERE oi.isGiftOrder='1' AND SUBSTR(o.orderStatus, 1, 1) IN ('o', 'p','g', 'd', 's', 'r')";
		$row = $this->db->fetch($sql);
		$amount = gd_isset($row['cnt'], 0);

		/* 검색 처리 S */
		if ($search['treatDate'][0]) {
			$sdate = date("Y-m-d H:i:s", strtotime($search['treatDate'][0]));
			$arrWhere[] = "oi.regDt >= ?";
			$this->db->bind_param_push($bind, "s", $sdate);
		}
		
		if ($search['treatDate'][1]) {
			$edate = date("Y-m-d H:i:s", strtotime($search['treatDate'][1]) + 60 * 60 * 24);
			$arrWhere[] = "oi.regDt < ?";
			$this->db->bind_param_push($bind, "s", $edate);
		}
		
		if ($search['orderStatus']) {
			$inWhere = [];
			foreach ($search['orderStatus'] as $orderStatus) {
				$inWhere[] = "?";
				$this->db->bind_param_push($bind, "s", $orderStatus);
			}
			
			$arrWhere[] = "o.orderStatus IN (".implode(',', $inWhere).")";
		}
		
		if ($search['smsSent']) {
			$arrWhere[] = "oi.giftSmsStamp > 0";
		}
		
		if ($search['addressUpdate']) {
			$arrWhere[] = "oi.giftUpdateStamp > 0";
		}
		
		if ($search['sopt'] && $search['skey']) {
			switch ($search['sopt']) {
				case "all" : 
					$fields = "CONCAT(oi.orderNo,oi.orderName,oi.receiverName,m.memNm,oi.orderCellPhone,oi.receiverCellPhone)";
					break;
				case "name" : 
					$fields = "CONCAT(oi.orderName,oi.receiverName)";
					break;
					break;
				case "phone" : 
					$fields = "CONCAT(oi.orderCellPhone,oi.receiverCellPhone)";
					break;
				default : 
					$fields = $search['sopt'];
			}
			
			$arrWhere[] = $fields . " LIKE ?";
			$this->db->bind_param_push($bind, "s", "%".trim($search['skey'])."%");
		}
		
		if($search['delivery_address_set'] == 'all'){
			
		}elseif($search['delivery_address_set'] == 'entered'){
			$arrWhere[] = " oi.receiverAddress != ''";
		}elseif($search['delivery_address_set'] == 'unentered'){
			$arrWhere[] = " oi.receiverAddress = ''";
		}

		$conds = $arrWhere?" AND ".implode(" AND ", $arrWhere):"";
		/* 검색 처리 E */
		
		$sql = "SELECT COUNT(*) as cnt FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo= o.orderNo  
						LEFT JOIN " . DB_MEMBER . " AS m ON o.memNo = m.memNo 
					WHERE oi.isGiftOrder='1' AND SUBSTR(o.orderStatus, 1, 1) IN ('o', 'p','g', 'd', 's' , 'r'){$conds}";
		$row = $this->db->query_fetch($sql, $bind, false);
		$total = gd_isset($row['cnt'], 0);

		$offset = ($page - 1) * $limit;
		$this->db->bind_param_push($bind, "i", $offset);
		$this->db->bind_param_push($bind, "i", $limit);
		$sql = "SELECT oi.*, o.orderStatus, SUBSTR(o.orderStatus, 1, 1) as o, o.memNo, m.memNm, m.memId FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo= o.orderNo  
						LEFT JOIN " . DB_MEMBER . " AS m ON o.memNo = m.memNo 
					WHERE oi.isGiftOrder='1' AND SUBSTR(o.orderStatus, 1, 1) IN ('o', 'p','g', 'd', 's' , 'r'){$conds} ORDER BY oi.regDt DESC LIMIT ?, ?";
		$list = $this->db->query_fetch($sql, $bind);

		if ($list) {
			foreach ($list as $k => $v) {
				$sql = "SELECT goodsNm, COUNT(sno) as cnt FROM " . DB_ORDER_GOODS . " WHERE orderNo = ? AND SUBSTR(orderStatus, 1, 1) IN ('o', 'p','g','d','s' , 'r') LIMIT 0, 1";
				$row = $this->db->query_fetch($sql, ["s", $v['orderNo']], false);
				$goodsNm = $row['goodsNm'];
				if ($row['cnt'] > 1) $goodsNm = $goodsNm . "외 " .($row['cnt'] - 1)."건";
				$v['goodsNm'] = $goodsNm;
				
				$orderStatus = substr($v['orderStatus'], 0, 1);
				$v['orderStatus2'] = $orderStatus;
				$v['orderStatusStr'] = $status[$v['orderStatus']];
				$v['giftUrl'] = $this->getGiftUrl($v['orderNo']);
				
				$list[$k] = $v;
			}
		}
		
		$list = gd_isset($list, []);
		$pageObj = App::load(\Component\Page\Page::class, $page, $total, $amount, $limit);
		$pageObj->setUrl(http_build_query(Request::get()->all()));
		$pagination = $pageObj->getPage();
		
		$result = [
			'total' => $total, 
			'amount' => $amount, 
			'list' => $list,
			'pagination' => $pagination,
		];
		
		return $result;
	}
		public function getListTmp($page = 1, $limit = 500, $search = [])
	{		
		$page = gd_isset($page, 1);
		$limit = gd_isset($limit, 500);
	
		$status = $this->getOrderStatusList();
		
		$arrWhere = $bind = [];
		$sql = "SELECT COUNT(*) as cnt FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN wm_order AS o ON oi.orderNo= o.orderNo  
					WHERE oi.isGiftOrder='1' AND SUBSTR(o.orderStatus, 1, 1) IN ('o', 'p','g', 'd', 's', 'r')";
		$row = $this->db->fetch($sql);
		$amount = gd_isset($row['cnt'], 0);

		/* 검색 처리 S */
		if ($search['treatDate'][0]) {
			$sdate = date("Y-m-d H:i:s", strtotime($search['treatDate'][0]));
			$arrWhere[] = "oi.regDt >= ?";
			$this->db->bind_param_push($bind, "s", $sdate);
		}
		
		if ($search['treatDate'][1]) {
			$edate = date("Y-m-d H:i:s", strtotime($search['treatDate'][1]) + 60 * 60 * 24);
			$arrWhere[] = "oi.regDt < ?";
			$this->db->bind_param_push($bind, "s", $edate);
		}
		
		if ($search['orderStatus']) {
			$inWhere = [];
			foreach ($search['orderStatus'] as $orderStatus) {
				$inWhere[] = "?";
				$this->db->bind_param_push($bind, "s", $orderStatus);
			}
			
			$arrWhere[] = "o.orderStatus IN (".implode(',', $inWhere).")";
		}
		
		if ($search['smsSent']) {
			$arrWhere[] = "oi.giftSmsStamp > 0";
		}
		
		if ($search['addressUpdate']) {
			$arrWhere[] = "oi.giftUpdateStamp > 0";
		}
		
		if ($search['sopt'] && $search['skey']) {
			switch ($search['sopt']) {
				case "all" : 
					$fields = "CONCAT(oi.orderNo,oi.orderName,oi.receiverName,m.memNm,oi.orderCellPhone,oi.receiverCellPhone)";
					break;
				case "name" : 
					$fields = "CONCAT(oi.orderName,oi.receiverName)";
					break;
					break;
				case "phone" : 
					$fields = "CONCAT(oi.orderCellPhone,oi.receiverCellPhone)";
					break;
				default : 
					$fields = $search['sopt'];
			}
			
			$arrWhere[] = $fields . " LIKE ?";
			$this->db->bind_param_push($bind, "s", "%".trim($search['skey'])."%");
		}
		
		$conds = $arrWhere?" AND ".implode(" AND ", $arrWhere):"";
		/* 검색 처리 E */
		
		$sql = "SELECT COUNT(*) as cnt FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN wm_order AS o ON oi.orderNo= o.orderNo  
						LEFT JOIN " . DB_MEMBER . " AS m ON o.memNo = m.memNo 
					WHERE oi.isGiftOrder='1' AND SUBSTR(o.orderStatus, 1, 1) IN ('o', 'p','g', 'd', 's', 'r'){$conds}";
		$row = $this->db->query_fetch($sql, $bind, false);
		$total = gd_isset($row['cnt'], 0);

		$offset = ($page - 1) * $limit;
		$this->db->bind_param_push($bind, "i", $offset);
		$this->db->bind_param_push($bind, "i", $limit);
		$sql = "SELECT oi.*, o.orderStatus, SUBSTR(o.orderStatus, 1, 1) as o, o.memNo, m.memNm, m.memId FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN wm_order AS o ON oi.orderNo= o.orderNo  
						LEFT JOIN " . DB_MEMBER . " AS m ON o.memNo = m.memNo 
					WHERE oi.isGiftOrder='1' AND SUBSTR(o.orderStatus, 1, 1) IN ('o', 'p','g', 'd', 's', 'r'){$conds} ORDER BY oi.regDt DESC LIMIT ?, ?";
		$list = $this->db->query_fetch($sql, $bind);

		if ($list) {
			foreach ($list as $k => $v) {
				$sql = "SELECT goodsNm, COUNT(sno) as cnt FROM wm_orderGoods WHERE orderNo = ? AND SUBSTR(orderStatus, 1, 1) IN ('o', 'p','g','d','s', 'r') LIMIT 0, 1";
				$row = $this->db->query_fetch($sql, ["s", $v['orderNo']], false);
				$goodsNm = $row['goodsNm'];
				if ($row['cnt'] > 1) $goodsNm = $goodsNm . "외 " .($row['cnt'] - 1)."건";
				$v['goodsNm'] = $goodsNm;
				
				$orderStatus = substr($v['orderStatus'], 0, 1);
				$v['orderStatus2'] = $orderStatus;
				$v['orderStatusStr'] = $status[$v['orderStatus']];
				$v['giftUrl'] = $this->getGiftUrl($v['orderNo']);
				
				$list[$k] = $v;
			}
		}
		
		$list = gd_isset($list, []);
		$pageObj = App::load(\Component\Page\Page::class, $page, $total, $amount, $limit);
		$pageObj->setUrl(http_build_query(Request::get()->all()));
		$pagination = $pageObj->getPage();
		
		$result = [
			'total' => $total, 
			'amount' => $amount, 
			'list' => $list,
			'pagination' => $pagination,
		];
		
		return $result;
	}
	/**
	* 주문상태 목록 
	*
	* @return Array
	*/
	public function getOrderStatusList()
	{
		$status = [];
		$list = gd_policy('order.status');

		if ($list) {
			foreach ($list as $_list) {
				foreach ($_list as $k => $v) {
					if (strlen($k) == 2) {
						$status[$k] = $v['user'];
					}
				}
			}
		}
		
		return $status;
	}
}