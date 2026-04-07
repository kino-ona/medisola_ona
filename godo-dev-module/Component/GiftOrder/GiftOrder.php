<?php

namespace Component\GiftOrder;

use App;
use Request;
use Component\GiftOrder\Traits\GiftOrderAdmin; // 선물하기 주문관리
use Component\Traits\SendSms; // SMS 전송 관련 
use Component\Traits\GoodsInfo; // 상품정보 관련 
use Session;
use Exception;
use Component\Sms\SmsAuto; // SMS 전송 관련
use Component\Sms\SmsAutoCode;
/**
* 선물하기 관련 
*
* @author webnmobile
*/
class GiftOrder
{
	use GiftOrderAdmin, SendSms, GoodsInfo;
	
	private $db;
	private $cfg;
	
	public function __construct()
	{
		$this->db = App::load(\DB::class);
		$this->cfg = $this->db->fetch("SELECT * FROM wm_giftSet");
		if ($this->cfg) {
			$this->cfg['cardTypes'] = $this->cfg['cardTypes']?explode(",", $this->cfg['cardTypes']):[]; 
			$this->cfg['orderStatus'] = $this->cfg['orderStatus']?explode(",", $this->cfg['orderStatus']):[];
			$this->cfg['smsTemplate'] = htmlspecialchars_decode(stripslashes(str_replace("\\r\\n", PHP_EOL, $this->cfg['smsTemplate'])));
			$this->cfg['smsTemplate2'] = htmlspecialchars_decode(stripslashes(str_replace("\\r\\n", PHP_EOL, $this->cfg['smsTemplate2'])));
			$this->cfg['smsRequestTemplate'] = htmlspecialchars_decode(stripslashes(str_replace("\\r\\n", PHP_EOL, $this->cfg['smsRequestTemplate'])));
			$this->cfg['smsExpireTemplate1'] = htmlspecialchars_decode(stripslashes(str_replace("\\r\\n", PHP_EOL, $this->cfg['smsExpireTemplate1'])));
			$this->cfg['smsExpireTemplate2'] = htmlspecialchars_decode(stripslashes(str_replace("\\r\\n", PHP_EOL, $this->cfg['smsExpireTemplate2'])));
		}
	}
	
	/**
	* 설정 추출 
	*
	* @return Array
	*/
	public function getCfg()
	{
		return gd_isset($this->cfg, []);
	}
	
	/**
	* 프론트 도메인 
	*
	* @param Boolean $isMobile 모바일 도메인 여부 
	* @return String
	*/ 
	public function getDomain($isMobile = false)
	{
		$server = Request::server()->toArray();
		$domain = str_replace("gdadmin.", "", $server['HTTP_HOST']);
		if ($isMobile) {
			$domain = str_replace(["www.", "m."], ["", ""], $domain);
			$domain = "m.".$domain;
		}
		
		$protocol = (strtolower($server['HTTPS']) == 'on')?"https://":"http://";
		
		return $protocol.$domain;
	}
	
	/**
	* 선물 주문 조회 
	*
	* @param String $orderNo 주문번호
	* @return Array
	*/
	public function getOrder($orderNo = null)
	{
		if ($orderNo) {
			$orderAdmin = App::load(\Component\Order\OrderAdmin::class);
			$order = $orderAdmin->getOrderView($orderNo);
			$conf = $this->getCfg();
			$possible = false;
			if ($order && $order['goods']) {
				$orderStatus = substr($order['orderStatus'], 0, 1);
				if (in_array($orderStatus, ["p", "g"])) {
					$possible = true;
				}
				
				$items = [];
				foreach ($order['goods'] as $values) {
					foreach ($values as $value) {
						foreach ($value as $v) {
							$orderStatus = substr($v['orderStatus'], 0, 1);
							if (in_array($orderStatus, ["o", "p", "g","d","s"])) {
								if ($v['brandCd']) {
									$sql = "SELECT cateNm FROM " . DB_CATEGORY_BRAND . " WHERE cateCd LIKE ?";
									$row = $this->db->query_fetch($sql, ["s", $v['brandCd']], false);
									$v['brandNm'] = $row['cateNm'];
								}
								$items[] = $v;
							}
						}
					}
				}
				unset($order['goods']);
				$order['items'] = $items;
			} // endif 
			
			if ($order) {
				$order['orderAddressPossible'] = $possible;
				$order['receiverCellPhone'] = $order['receiverCellPhone']?implode("", $order['receiverCellPhone']):"";
			}
		} // endif 

		return gd_isset($order, []);
	}
	
	/**
	* 선물하기 카드 이미지 목록 
	*
	* @param String $type 카드 유형 
	* @return Array
	*/
	public function getCards($cardType = null)
	{		
		$list = [];
		$cfg = $this->getCfg();
		if ($cfg['cardTypes']) {
			$path = dirname(__FILE__) . "/../../../data/gift_card/";
			foreach ($cfg['cardTypes'] as $type) {
				$uid = md5($type);
				foreach (glob($path . $uid ."/*") as $f) {
					$pi = pathinfo($f);
					$list[$type][] = [
						'uid' => md5($f),
						'cardType' => $type,
						'filename' => $pi['basename'],
						'imageUrl' => $this->getDomain()."/data/gift_card/{$uid}/".$pi['basename'],
						'imagePath' => $f,
					];
				} // endforeach 
			} // endforeach 
		} // endif 

		if ($cardType) {
			return gd_isset($list[$cardType], []);
		}
		
		return $list;
	}
	
	/**
	* 선물하기 URL 
	*
	* @param String $orderNo 주문번호
	* @return String 
	*/
	public function getGiftUrl($orderNo = null, $url = null)
	{
		if (!$orderNo && !$url)
			return;
		
		$url = $url?$url:$this->getDomain(true) . "/gift_order/view.php?orderNo=".$orderNo;
		
		/* 짧은 URL 변환 S */
		$shortUrl = App::load(\Component\Promotion\ShortUrl::class);
		$id = $shortUrl->getId($url);
		if ($id == -1) {
			if ($shortUrl->addUrl($url)) {
				$id = $shortUrl->getId($url);
			}
		}
		
		if ($id != -1) {
			$sql = "SELECT shortUrl FROM " . DB_SHORT_URL . " WHERE id = ?";
			$row = $this->db->query_fetch($sql, ["s", $id], false);
			$url = $row['shortUrl']?$row['shortUrl']:$url;
		}
		/* 짧은 URL 변환 E */
		
		return $url;
	}

	/**
	* 선물하기 URL SMS 전송 
	*
	* @param String $orderNo 주문번호
	* 
	* @return Boolean
	* @throw Exception
	*/
	public function sendGiftSms($orderNo = null, $isManual = false)
	{
		$cfg = $this->getCfg();


		$sql = "SELECT oi.*, o.orderStatus, oi.addGiftAddress FROM " . DB_ORDER_INFO . " as oi 
						INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo 
					WHERE oi.orderNo = ?";
		$order = $this->db->query_fetch($sql, ['s', $orderNo], false);


		if (!$order || !$order['receiverCellPhone'] || !$order['isGiftOrder'])
			return false;

		if ($order['addGiftAddress']) {
			$cfg['smsTemplate'] = $cfg['smsTemplate2'];			
		}	
		$orderStatus = substr($order['orderStatus'], 0, 1);

		if (!$isManual && (!in_array($orderStatus, $cfg['orderStatus']) || $order['giftSmsStamp'] > 0)) {
			return false;
		}

		$giftUrl = $this->getGiftUrl($orderNo);



		$sql = "SELECT goodsNm, optionSno ,  goodsNo ,COUNT(sno)  as cnt FROM " . DB_ORDER_GOODS . " WHERE orderNo = ? AND SUBSTR(orderStatus, 1, 1) IN ('p','g','d','s') LIMIT 0, 1";
		$row = $this->db->query_fetch($sql, ["s", $orderNo], false);
		$goodsNm = $row['goodsNm'];
		if ($row['cnt'] > 1) $goodsNm = $goodsNm . "외 " .($row['cnt'] - 1)."건";
		
		$message = htmlspecialchars_decode(str_replace("\\n", PHP_EOL, str_replace("\\r\\n", PHP_EOL, $order['giftMessage'])));
		
		$expireStamp = strtotime($order['regDt'])+ (60 * 60 * 24 * $cfg['expireDays']); // + (60 * 60 * 24 * $cfg['expireDays'])
		$expireDate = date("Y.m.d", $expireStamp);

		$sql = "SELECT optionName from es_goods where goodsNo = ".$row['goodsNo']." ";
		$optionNm = $this->db->fetch($sql);


		$sql = "SELECT optionValue1 , optionValue2 , optionValue3, optionValue4, optionValue5 from es_goodsOption where sno = ".$row['optionSno']."";
		$optionValue = $this->db->fetch($sql);

		$optionNm = explode('^|^' , $optionNm['optionName']);

        $option = '';
        foreach($optionNm as $key=>$val){
            $option .= $val . ' : ' . $optionValue['optionValue' . ($key + 1)] .' ';
        }

		$param = [
			'to' => $order['receiverName'],
			'from' => $order['orderName'],
			'message' => $message,
			'goodsNm' => $goodsNm,
			'giftUrl' => $giftUrl,
			'expireDate' => $expireDate,
		];	


        $receiverInfo['scmNo'] = '';
        $receiverInfo['memNo'] = '';
        $receiverInfo['memNm'] = '';
        $receiverInfo['smsFl'] = 'y';
        $receiverInfo['cellPhone'] = $order['receiverCellPhone'];
        $replaceArguments['shopUrl'] = 'medisola.co.kr'; // 필수-도메인세팅
        $replaceArguments['orderNo'] = $order['orderNo']; // 필수-주문번호세팅


 //57번
        $replaceArguments['receiverName'] = $param['to'];
        $replaceArguments['orderName'] = $param['from'];
        $replaceArguments['goodsNm'] = $param['goodsNm'];
        $replaceArguments['giftUrl'] = $param['giftUrl'];
        $replaceArguments['goodsOption'] = $option;
        $replaceArguments['rc_mallNm'] = '메디쏠라';
        $smsAuto = new SmsAuto();

		// 배송지 입력완료
		if ($order['addGiftAddress']) {
			$smsAuto->setSmsType(SmsAutoCode::ORDER);
			$smsAuto->setSmsAutoCodeType('PRESENT_NOADDRESS57');
			$smsAuto->setReceiver($receiverInfo);
			$smsAuto->setReplaceArguments($replaceArguments);
			$result = $smsAuto->autoSend_wmExtend('n', 57);
		}else{
			$smsAuto->setSmsType(SmsAutoCode::ORDER);
			$smsAuto->setSmsAutoCodeType('PRESENT_NOADDRESS58');
			$smsAuto->setReceiver($receiverInfo);
			$smsAuto->setReplaceArguments($replaceArguments);
			$result = $smsAuto->autoSend_wmExtend('n', 58);
		}



        if ($result) {
			$param = [
				'giftSmsStamp = ?',
			];
			
			$bind = [
				'is',
				time(),
				$orderNo,
			];
			
			$this->db->set_update_db(DB_ORDER_INFO, $param, "orderNo = ?", $bind);
		}
		
		return $result;
	}
	
	/**
	* 선물 요청 URL SMS 전송 
	*
	* @param Integer $idx 신청 IDX 
	* @return Boolean
	*/
	public function sendGiftRequestSms($idx = null)
	{
		$cfg = $this->getCfg();
		if (!$idx || !$cfg['smsRequestTemplate'])
			return false;
		
		$url = $this->getDomain(true) . "/gift_order/request_order.php?idx=".$idx;
		
		$url = $this->getGiftUrl(null, $url);
		
		$sql = "SELECT * FROM wm_giftRequest WHERE idx = ?";
		$info = $this->db->query_fetch($sql, ["i", $idx], false);
		if (!$info || !$info['orderCellPhone'])
			return false;
		
		$sql = "SELECT * FROM " . DB_GOODS . " WHERE goodsNo = ?";
		$row = $this->db->query_fetch($sql, ["i", $info['goodsNo']], false);
		if (!$row)
			return false;
		
		$goodsNm = $row['goodsNm'];
		if ($info['goodsCnt'] > 1) {
			$goodsNm .= " X " . number_format($info['goodsCnt'])."개";
		}
		
		$param = [
			'from' => $info['receiverName'],
			'goodsNm' => $goodsNm,
			'giftUrl' => $url,
			'orderCellPhone' => $info['orderCellPhone'],
			'receiverCellPhone' => $info['receiverCellPhone'],
		];



		$result = $this->sendSms($info['orderCellPhone'], $cfg['smsRequestTemplate'], $param);
		
		if ($result) {
			$param = [
				'smsStamp = ?',
			];
			
			$bind = [
				'ii',
				time(),
				$idx,
			];
			
			$this->db->set_update_db("wm_giftRequest", $param, "idx = ?", $bind);
		} // endif 
		
		return $result;
	}
	
	/**
	* 선물 요청하기 정보 
	*
	* @param Integer $idx 신청번호 
	* @return Array
	*/
	public function getRequestInfo($idx = null)
	{
		if ($idx) {
			$sql = "SELECT * FROM wm_giftRequest WHERE idx = ?";
			$info = $this->db->query_fetch($sql, ["i", $idx], false);
		}
		
		return gd_isset($info, []);
	}
	
	/**
	* 선물 요청하기 기본 정보 추출
	*
	* @param Integer $goodsNo 상품번호 
	* @param Integer $goodsCnt 상품수량
	* 
	* @return Array
	*/
	public function getGiftRequestInfo($goodsNo = null, $goodsCnt = 1)
	{
		$info = [];
		if ($goodsNo) {
			$goods = $this->getGoods([$goodsNo]);
			if ($goods) {
				$info['goods'] = $goods[0];
				$info['goods']['goodsCnt'] = $goodsCnt;
			}
			
			if (gd_is_login()) {
				$memNo = Session::get("member.memNo");
				$sql = "SELECT * FROM ". DB_MEMBER . " WHERE memNo = ?";
				$member = $this->db->query_fetch($sql, ["i", $memNo], false);
				$default = [];
				$sql = "SELECT * FROM " . DB_ORDER_SHIPPING_ADDRESS . " WHERE memNo = ? ORDER BY sno";
				$list = $this->db->query_fetch($sql, ["i", $memNo]);
				if ($list) {
					foreach ($list as $v) {
						if ($v['defaultFl'] == 'y') {
							$default = $v;
						}
					}
				}
				
				if (empty($default)) {
					$default = [
						'shippingName' => $member['memNm'],
						'shippingCellPhone' => $member['cellPhone'],
						'shippingZonecode' => $member['zonecode'],
						'shippingAddress' => $member['address'],
						'shippingAddressSub' => $member['addressSub'],
					];
				}
				
				$info['default'] = $default;
				$info['shippingList'] = gd_isset($list, []);
			} // endif 
		} // endif
		
		return $info;
	}
	
	/**
	* 선물 요청 주문인 경우 업데이트 
	*
	* @param String $orderNo 주문번호
	*/
	public function updateGiftRequest($orderNo = null)
	{
		if ($orderNo) {
			$sql = "SELECT idxGiftRequest FROM " . DB_ORDER_INFO . " WHERE orderNo = ?";
			$row = $this->db->query_fetch($sql, ["s", $orderNo], false);
			if ($row['idxGiftRequest']) {
				$param = [
					'orderNo = ?',
				];
				
				$bind = [
					'si',
					$orderNo,
					$row['idxGiftRequest'],
				];
				
				$this->db->set_update_db("wm_giftRequest", $param, "idx = ?", $bind);
			}
		}
	}
	
	/**
	* 배송주소입력시간 만료 안내 SMS 전송 
	*
	*/
	public function sendExpireSms()
	{
		$conf = $this->getCfg();
		if ($conf['expireDays'] > 0 && $conf['expireSmsDays']  > 0 && $conf['expireSmsDays'] < $conf['expireDays'] && $conf['smsExpireTemplate1']) {
			$days = $conf['expireDays'] - $conf['expireSmsDays'];
			$stamp = strtotime(date("Y-m-d"). " 23:59:59")- (60 * 60 * 24 * $days); //- (60 * 60 * 24 * $days)
			
			$date = date("Y-m-d H:i:s", $stamp);
			$sql = "SELECT oi.* FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo 
						WHERE oi.isGiftOrder = 1 AND oi.giftExpireSmsStamp = 0 AND oi.regDt <= ? AND oi.receiverAddress = '' AND SUBSTR(o.orderStatus, 1, 1) IN ('p', 'g') ORDER BY oi.regDt"; 
			$list = $this->db->query_fetch($sql, ["s", $date]);
			if ($list) {
				foreach ($list as $li) {
					$orderNo = $li['orderNo'];
					$giftUrl = $this->getGiftUrl($orderNo);
					$sql = "SELECT goodsNm, COUNT(sno) as cnt FROM " . DB_ORDER_GOODS . " WHERE orderNo = ? AND SUBSTR(orderStatus, 1, 1) IN ('p','g','d','s') LIMIT 0, 1";
					$row = $this->db->query_fetch($sql, ["s", $orderNo], false);
					$goodsNm = $row['goodsNm'];
					if ($row['cnt'] > 1) $goodsNm = $goodsNm . "외 " .($row['cnt'] - 1)."건";
		
					$message = htmlspecialchars_decode(str_replace("\\n", PHP_EOL, str_replace("\\r\\n", PHP_EOL, $li['giftMessage'])));
					
					$expireStamp = strtotime($li['regDt'])+ (60 * 60 * 24 * $conf['expireDays']); // + (60 * 60 * 24 * $conf['expireDays'])
					$expireDate = date("Y.m.d", $expireStamp);
					$param = [
						'to' => $li['receiverName'],
						'from' => $li['orderName'],
						'message' => $message,
						'goodsNm' => $goodsNm,
						'giftUrl' => $giftUrl,
						'expireDate' => $expireDate,
					];



                    $receiverInfo['scmNo'] = '';
                    $receiverInfo['memNo'] = '';
                    $receiverInfo['memNm'] = '';
                    $receiverInfo['smsFl'] = 'y';
                    $receiverInfo['cellPhone'] = $li['receiverCellPhone'];
                    $replaceArguments['shopUrl'] = 'medisola.co.kr'; // 필수-도메인세팅
                    $replaceArguments['orderNo'] = $orderNo; // 필수-주문번호세팅



                    //59번
					// 선물받는분
                    $replaceArguments['receiverName'] = $param['to'];
                    $replaceArguments['orderName'] = $param['from'];
                    $replaceArguments['goodsNm'] = $param['goodsNm'];
                    $replaceArguments['expireDate'] = $param['expireDate'];
                    $replaceArguments['giftUrl'] = $param['giftUrl'];
                    $smsAuto = new SmsAuto();

                    $smsAuto->setSmsType(SmsAutoCode::ORDER);
                    $smsAuto->setSmsAutoCodeType('PRESENT_NOADDRESS59');
                    $smsAuto->setReceiver($receiverInfo);
                    $smsAuto->setReplaceArguments($replaceArguments);
                    $result = $smsAuto->autoSend_wmExtend('n', 59);





                 //   $result = $this->sendSms($li['receiverCellPhone'], $conf['smsExpireTemplate1'], $param);
					// 선물한 사람
					if ($conf['smsExpireTemplate2'] && $li['orderCellPhone']) {

                        $receiverInfo['scmNo'] = '';
                        $receiverInfo['memNo'] = '';
                        $receiverInfo['memNm'] = '';
                        $receiverInfo['smsFl'] = 'y';
                        $receiverInfo['cellPhone'] = $li['orderCellPhone'];
                        $replaceArguments['shopUrl'] = 'medisola.co.kr'; // 필수-도메인세팅
                        $replaceArguments['orderNo'] = $orderNo; // 필수-주문번호세팅



                        //56번
                        $replaceArguments['receiverName'] = $param['to'];
                        $replaceArguments['orderName'] = $param['from'];
                        $replaceArguments['goodsNm'] = $param['goodsNm'];
                        $replaceArguments['expireDate'] = $param['expireDate'];
                        $replaceArguments['giftUrl'] = $param['giftUrl'];
                        $smsAuto = new SmsAuto();

                        $smsAuto->setSmsType(SmsAutoCode::ORDER);
                        $smsAuto->setSmsAutoCodeType('PRESENT_NOADDRESS56');
                        $smsAuto->setReceiver($receiverInfo);
                        $smsAuto->setReplaceArguments($replaceArguments);
                        $result = $smsAuto->autoSend_wmExtend('n', 56);




					//	$this->sendSms($li['orderCellPhone'], $conf['smsExpireTemplate2'], $param);
					}
					
					if ($result) {
						$param = [
							'giftExpireSmsStamp = ?',
						];
						
						$bind = [
							'is',
							time(),
							$orderNo,
						];
						
						$this->db->set_update_db(DB_ORDER_INFO, $param, "orderNo = ?", $bind);
					} // endif 
					
				} // endforeach 
			} // endif 
		}
	}
	
	/**
	* 선물하기 주문 만료취소 처리 
	*
	*/
	public function cancelExpiredGiftOrder()
	{
		$conf = $this->getCfg();
		if ($conf['expireDays'] > 0 && $conf['expireSmsDays']  > 0) {
			$days = $conf['expireDays'];
			$stamp = strtotime(date("Y-m-d"). " 23:59:59")- (60 * 60 * 24 * $days ); //- (60 * 60 * 24 * $days )
			$date = date("Y-m-d H:i:s", $stamp);
			$sql = "SELECT oi.orderNo FROM " . DB_ORDER_INFO . " AS oi 
						INNER JOIN " . DB_ORDER . " AS o ON oi.orderNo = o.orderNo 
						WHERE oi.isGiftOrder = 1 AND oi.regDt <= ? AND oi.receiverAddress = '' AND SUBSTR(o.orderStatus, 1, 1) IN ('p', 'g') ORDER BY oi.regDt"; 
			$list = $this->db->query_fetch($sql, ["s", $date]);
			if ($list) {
				$selfCancel = App::load(\Component\SelfCancel\SelfCancel::class);
				foreach ($list as $li) {
					$orderNo = $li['orderNo'];
					try {
						$selfCancel->cancel($orderNo);
					} catch (Exception $e) {}
				}
			}
		}
	}
	
	/*
	* 기간 가져오기
	*
	*
	*/
	public function getDays()
	{
		$sql = "SELECT expireDays from wm_giftSet";
		return $this->db->fetch($sql);
		
	}
	
	
	/**
	* 사방넷 관련
	* 선물하기 배송지 미입력과 결제완료 되었을 시 p1 -> p2 로 변경
	* @param varchar $orderNo
	*
	*/
	public function GiftStatusChange($orderNo)
	{
		if($orderNo){
			//es_order 에서 해당 주문건 상태값 가져오기
			$this->db->strField = "orderStatus";
			$this->db->strWhere = "orderNo = '{$orderNo}'";
			$query = $this->db->query_complete();
			$sql = "SELECT".array_shift($query)."FROM es_order".implode(' ' ,$query);
			$orderStatus = $this->db->fetch($sql);
			
			// es_orderInfo 에서 해당 주문건이 선물하기 주문건인지와 배송지 입력했는지 확인
			$this->db->strField = "isGiftOrder, receiverAddress";
			$this->db->strWhere = "orderNo = '{$orderNo}'";
			$query = $this->db->query_complete();
			$strSQL  ="SELECT".array_shift($query)."FROM es_orderInfo".implode(' ', $query);
			$giftInfo = $this->db->fetch($strSQL);
			
			// 선물하기 주문과 배송지 미입력, 해당 주문의 상태값이 결제완료일때 p2로 변경
			if( ($giftInfo['isGiftOrder'] == 1) && ($giftInfo['receiverAddress'] == '') && ($orderStatus['orderStatus'] == 'p1')){
				$p2 = 'p2'; 
				$arrBind = [];
				$sql = "UPDATE es_order SET orderStatus = ? WHERE orderNo = ? AND orderStatus = ?";
				$this->db->bind_param_push($arrBind, 's' , $p2);
				$this->db->bind_param_push($arrBind, 's' , $orderNo);
				$this->db->bind_param_push($arrBind, 's' , $orderStatus['orderStatus']);
				$this->db->bind_query($sql, $arrBind);
				
				$arrBinds = [];
				$sql = "UPDATE es_orderGoods SET orderStatus = ? WHERE orderNo = ? AND orderStatus = ?";
				$this->db->bind_param_push($arrBinds, 's' , $p2);
				$this->db->bind_param_push($arrBinds, 's' , $orderNo);
				$this->db->bind_param_push($arrBinds, 's' , $orderStatus['orderStatus']);
				$this->db->bind_query($sql, $arrBinds);
			}
		}
	}
	
	/**
	* 사방넷 관련
	* 선물하기 받는분 배송지 입력 시 상태값 p2 -> p1 로 변경
	* @param varchar $orderNo
	*
	*/
	public function GiftStatusReChange($orderNo)
	{
		if($orderNo){
			$arrBind = [];
			$arrBinds = [];
			$p = 'p1';
			// es_order 상태값 p1으로 변경
			$sql = "UPDATE es_order SET orderStatus = ? WHERE orderNo = ?";
			$this->db->bind_param_push($arrBind, 's', $p);
			$this->db->bind_param_push($arrBind, 's', $orderNo);
			$this->db->bind_query($sql, $arrBind);
			
			// es_orderGoods 상태값 p1으로 변경
			$strSQL = "UPDATE es_orderGoods SET orderStatus = ? WHERE orderNo =?";
			$this->db->bind_param_push($arrBinds, 's', $p);
			$this->db->bind_param_push($arrBinds, 's', $orderNo);
			$this->db->bind_query($strSQL, $arrBinds);
		}
	}
	
}