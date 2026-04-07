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
class ViewController extends \Controller\Front\Controller
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
			
			if(!is_object($this->db)){
				$this->db = \App::load(\DB::class);
				$receive = $this->db->fetch("SELECT receiverAddress, receiverAddressSub FROM es_orderInfo WHERE orderNo = '{$orderNo}'");
			
				if( ($receive['receiverAddress'] != '' && $receive['receiverAddressSub'] != '') && ($receive['receiverAddress'] !='-' && $receive['receiverAddressSub'] !='-') ){
					$this->setData('yesReceive' , 1);
				}
				
			}

            $orderInfo = $this->db->fetch("SELECT * From es_orderGoods WHERE orderNo = '{$orderNo}'");
            $this->setData('orderInfo' , $orderInfo);


            $agree = $this->db->fetch("SELECT giftAgree FROM es_orderInfo WHERE orderNo = '{$orderNo}'");
            $this->setData('agree' , $agree['giftAgree']);


            $sql = "SELECT beforeStatus , handleMode , handleCompleteFl FROM es_orderHandle WHERE orderNo = '{$info['orderNo']}'";
			$handleCompleteFl = $this->db->fetch($sql);
			// 환불 또는 취소시 경고창 출력
			if($handleCompleteFl['handleCompleteFl'] == 'y' && ($handleCompleteFl['handleMode'] == 'c' || $handleCompleteFl['handleMode'] == 'r' ) ){
				$this->setData('handleCompleteFl', 1);
			}
       
			$this->setData($info);
			
			$this->addScript(["slider/slick/slick.js"]);
		} catch (AlertOnlyException $e) {
			$this->js("alert('".$e->getMessage() . "');self.close();");
		}
	}
}