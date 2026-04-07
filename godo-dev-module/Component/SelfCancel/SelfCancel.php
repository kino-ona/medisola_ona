<?php

namespace Component\SelfCancel;

use App;
use Exception;

/**
* 셀프취소 관련 
*
* @package Component\SelfCancel
* @author webnmobile
*/
class SelfCancel
{
	private $db;
	
	public function __construct()
	{
		$this->db = App::load(\DB::class);
	}
	
	/**
	* 셀프취소 설정
	*
	* @return Array
	*/
	public function getCfg()
	{
		$sql = "SELECT * FROM wm_selfCancelSet";
		$row = $this->db->fetch($sql);
		if ($row) {
			$row['orderStatus'] = $row['orderStatus']?explode("||", $row['orderStatus']):[];
		}
		
		return $row;
	}
	
	/**
	* 셀프취소처리 
	*
	* @param Integer $orderNo 주문번호
	*
	* @return Boolean 
	*/
	public function cancel($orderNo = null)
	{
		if (!$orderNo)
			return false;
	
		$orderReorderCalculation = new \Component\Order\ReOrderCalculation();
		
		$sql = "SELECT * FROM " . DB_ORDER . " WHERE orderNo = ?";
		$order = $this->db->query_fetch($sql, ["i", $orderNo], false);
		if (!$order)
			return false;
				
		$orderInfo = $order;
		
		//$sql = "SELECT sno, orderStatus, goodsCnt, divisionUseMileage, divisionUseDeposit, (taxSupplyGoodsPrice + taxVatGoodsPrice) as goodsPrice FROM " . DB_ORDER_GOODS . " WHERE orderNo= ?";
		$sql = "SELECT sno, orderStatus, goodsCnt, divisionUseMileage, divisionUseDeposit, (taxSupplyGoodsPrice + taxVatGoodsPrice) as goodsPrice,optionPrice,optionTextPrice,goodsPrice as ori_goodsPrice FROM " . DB_ORDER_GOODS . " WHERE orderNo= ?";
		$list = $this->db->query_fetch($sql, ["i", $orderNo]);
		if (!$list)
			return false;
	
		$refundMethod = ($order['settleKind'] == 'gb')?"기타환불":"PG환불";
		
		$params = [];
		$params['orderNo'] = $orderNo;
		$params['info']['refundMethod'] = $refundMethod;
		$params['info']['refundGoodsUseDeposit'] = $order['useDeposit'];
		$params['info']['refundGoodsUseMileage'] = $order['useMileage'];
		$params['info']['handleReason'] = '기타';
		$params['info']['handleDetailReason'] = '사용자 셀프취소';
	
		if ($order['settleKind'] == 'gb') {
			$params['info']['completeMileagePrice'] = $order['settlePrice'];
		}
		
		$totalSettle = $order['settlePrice'];
			
		 foreach ($list as $k=> $li) {
			 if ($k == count($list) - 1) {
				$li['settleEach'] = $totalSettle;
			 } else {
				$rate = $li['goodsPrice'] / $order['totalGoodsPrice'];
				$li['settleEach'] = round($totalSettle * $rate);
				$totalSettle -= $li['settleEach'];
			 }
			 
			 $list[$k] = $li;
			 
			$params['refund']['statusCheck'][$li['sno']] = $li['sno'];
            $params['refund']['statusMode'][$li['sno']] = $li['orderStatus'];
            $params['refund']['goodsType'][$li['sno']] = 'goods';
			$params['refund']['goodsOriginCnt'][$li['sno']] =  $li['goodsCnt'];
			$params['refund']['goodsCnt'][$li['sno']] =  $li['goodsCnt'];
			
			$params['refund']['handleReason'] = '기타';	
			if ($order['settleKind'] != 'gb') {	
				$params['refund']['refundMethod'] = 'PG환불';
			}
			
			$params['refund']['handleDetailReason'] = '사용자 셀프취소';
			$param['handler'] = 'admin';
         }					 
	
		/* 환불 접수 처리 */
		$orderReorderCalculation->setBackRefundOrderGoods($params, 'refund');

		/* 카카오 페이 처리 START */
		if ($order['settleKind'] == 'pk') {
			$sql = "SELECT * FROM " . DB_ORDER_HANDLE . " WHERE orderNo = ? AND handleMode='r' AND handleCompleteFl='n' ORDER BY sno DESC";
			$olist = $this->db->query_fetch($sql, ["i", $orderNo]);
			if ($olist) {
				 foreach ($list as $k=> $li) {
				$o = $olist[$k];
					if($o) {
						$param = [
							'refundPrice = ?',
							'refundUseMileage = ?',
							'refundUseDeposit = ?',
						];
						
						$bind = [
							'iiii',
							$li['settleEach'],
							$li['divisionUseMileage'],
							$li['divisionUseDeposit'],
							$o['sno'],
						];
					
						$this->db->set_update_db(DB_ORDER_HANDLE, $param, "sno = ?", $bind);
					}
				}
			}
		}
		/* 카카오페이 처리 END */
		
		
		/* 환불 완료 처리 */
		$sql = "SELECT * FROM " . DB_ORDER_HANDLE . " WHERE orderNo = ? AND handleMode='r' AND handleCompleteFl='n' ORDER BY sno DESC LIMIT 0, 1";
		$handle = $this->db->query_fetch($sql, ["i", $orderNo], false);
		
		if ($handle) {
			$sql = "SELECT deliverySno FROM " . DB_ORDER_DELIVERY . " WHERE orderNo = ?";
			$odList = $this->db->query_fetch($sql, ["i", $orderNo]);
			$params = [];
			$params['handleSno'] = $handle['sno'];
			$params['isAll'] = 1;
			$params['orderNo'] = $orderNo;
			
			
			$excludeStatus = ['r3', 'e1', 'e2', 'e3', 'e4', 'e5', 'c1', 'c2', 'c3', 'c4'];
			$orderAdmin = new \Component\Order\OrderAdmin();
			$getData = $orderAdmin->getRefundOrderView($orderNo,null,$handle['sno'],'r',$excludeStatus,null,1);
			$refundData=$getData['refundData'];

			$params['totalRealPayedPrice'] = $refundData['totalRealPayedPrice'];
			$params['refundGoodsPrice'] = $refundData['refundGoodsPriceSum'];
			$params['refundAliveGoodsPriceSum']= $refundData['refundAliveGoodsPriceSum'];
			$params['refundAliveGoodsCount'] = $refundData['refundAliveGoodsCount'];
			
			$params['refundGoodsDcSum']	=$refundData['refundGoodsDcPriceSum'];
			$params['refundGoodsCouponMileageOrg']	=$refundData['refundGoodsCouponMileage'];
			$params['refundGoodsCouponMileageMin']	=$refundData['refundMinGoodsCouponMileage'];
			$params['refundGoodsCouponMileageMax']	=$refundData['refundGoodsCouponMileage'];
			$params['refundOrderCouponMileageOrg']	=$refundData['refundOrderCouponMileage'];
			$params['refundOrderCouponMileageMin']	=$refundData['refundMinOrderCouponMileage'];
			$params['refundOrderCouponMileageMax']	=$refundData['refundOrderCouponMileage'];
			$params['refundGroupMileageOrg']	=$refundData['refundGroupMileage'];
			$params['refundGroupMileageMin']	=$refundData['refundMinGroupMileage'];
			$params['refundGroupMileageMax']	=$refundData['refundGroupMileage'];
			$params['refundGoodsDcPriceSumMin']	=$refundData['refundGoodsDcPriceSumMin'];
			$params['refundGoodsDcPriceOrg']	=$refundData['refundGoodsDcPrice'];
			$params['refundGoodsDcPrice']	=$refundData['refundGoodsDcPrice'];
			$params['refundGoodsDcPriceMax']	=$refundData['refundGoodsDcPriceMax'];
			$params['refundGoodsDcPriceMaxOrg']	=$refundData['refundGoodsDcPriceMax'];
			$params['refundMemberAddDcPrice']=$refundData['refundMemberAddDcPrice'];
			$params['refundMemberAddDcPriceOrg']	=$refundData['refundMemberAddDcPrice'];
			$params['refundMemberAddDcPriceMax']	=$refundData['refundMemberAddDcPriceMax'];
			$params['refundMemberAddDcPriceMaxOrg']	=$refundData['refundMemberAddDcPriceMax'];
			$params['refundMemberOverlapDcPriceOrg']	=$refundData['refundMemberOverlapDcPrice'];
			$params['refundMemberOverlapDcPriceMax']	=$refundData['refundMemberOverlapDcPriceMax'];
			$params['refundMemberOverlapDcPriceMaxOrg']	=$refundData['refundMemberOverlapDcPriceMax'];
			$params['refundEnuriDcPriceOrg']	=$refundData['refundEnuriDcPrice'];
			$params['refundEnuriDcPriceMax']	=$refundData['refundEnuriDcPriceMax'];
			$params['refundEnuriDcPriceMaxOrg']	=$refundData['refundEnuriDcPriceMax'];
			$params['refundGoodsCouponDcPrice']	=$refundData['refundGoodsCouponDcPrice'];
			$params['refundGoodsCouponDcPriceOrg']	=$refundData['refundGoodsCouponDcPrice'];
			$params['refundGoodsCouponDcPriceMax']	=$refundData['refundGoodsCouponDcPriceMax'];
			$params['refundGoodsCouponDcPriceMaxOrg']	=$refundData['refundGoodsCouponDcPriceMax'];

			$params['refundOrderCouponDcPrice']=$refundData['refundOrderCouponDcPrice'];
			$params['refundOrderCouponDcPriceOrg'] = $refundData['refundOrderCouponDcPrice'];
			$params['refundOrderCouponDcPriceMax'] = $refundData['refundOrderCouponDcPriceMax'];
			$params['refundOrderCouponDcPriceMaxOrg'] = $refundData['refundOrderCouponDcPriceMax'];
			$params['refundAliveDeliveryPriceSum'] = $refundData['refundAliveDeliveryPriceSum'];
			$params['refundDeliveryCouponDcPriceOrg'] = $refundData['refundDeliveryCouponDcPrice'];
			$params['refundDeliveryCouponDcPriceMax'] = $refundData['refundDeliveryCouponDcPrice'];
			$params['refundDeliveryCouponDcPriceMaxOrg'] = $refundData['refundDeliveryCouponDcPrice'];
			$params['refundDepositPriceOrg'] = $refundData['refundDepositPrice'];
			$params['refundDepositPriceTotal'] = $refundData['refundDepositPriceTotal'];
			$params['refundDepositPriceMax'] = $refundData['refundDepositPriceMax'];
			$params['refundDepositPriceMaxOrg']= $refundData['refundDepositPriceMax'];
			$params['refundMileagePriceOrg'] = $refundData['refundMileagePrice'];
			$params['refundMileagePriceTotal'] = $refundData['refundMileagePriceTotal'];
			$params['refundMileagePriceMax'] = $refundData['refundMileagePriceMax'];
			$params['refundMileagePriceMaxOrg'] = $refundData['refundMileagePriceMax'];
			$params['aAliveDeliverySno'] = [];
		
			foreach ($getData['refundData']['aAliveDeliverySno'] as $k => $v) {
				$params['aAliveDeliverySno'][]=$v;	
				
			}
			
			$params['refundGoodsCouponMileageFlag']= 'F';
			$params['refundOrderCouponMileageFlag']	= 'F';
			//$params['refundGroupMileageFlag'] = 'F';
			if($getData['refundData']['refundGroupMileage']<1)
				$params['refundGroupMileageFlag'] = 'F';

			if($getData['refundData']['refundGroupMileageNow']>=1){
				$params['refundGroupMileageFlag'] = 'T';
				$params['refundGroupMileage']=$getData['refundData']['refundGroupMileageNow'];

			}
			
			if($refundData['refundGoodsDcPrice'] < 1 || $refundData['refundGoodsDcPriceNow']==0){//2021.11.29변경
				$params['refundGoodsDcPriceFlag'] = 'F';
			}else{
				$params['refundGoodsDcPriceFlag'] = 'T';
			}

			if ($refundData['refundMemberAddDcPriceNow'] > 0)
				$params['refundMemberAddDcPriceFlag'] = 'T';
			else
				$params['refundMemberAddDcPriceFlag'] = 'F';

			if ($refundData['refundMemberOverlapDcPriceNow'] > 0) 
				$params['refundMemberOverlapDcPriceFlag'] = 'T';
			else
				$params['refundMemberOverlapDcPriceFlag'] = 'F';

			if ($refundData['refundEnuriDcPriceNow'] > 0)
				$params['refundEnuriDcPriceFlag']	= 'T';
			else
				$params['refundEnuriDcPriceFlag']	= 'F';

			if ($refundData['refundGoodsCouponDcPriceNow'] > 0)
				$params['refundGoodsCouponDcPriceFlag']	= 'T';
			else
				$params['refundGoodsCouponDcPriceFlag']	= 'F';

			if ($refundData['refundOrderCouponDcPriceNow'] > 0)
				$params['refundOrderCouponDcPriceFlag'] = 'T';
			else
				$params['refundOrderCouponDcPriceFlag'] = 'F';
				

			foreach ($list as $k=> $li) {
				$params['refund'][$handle['sno']] = [
					'sno' => $li['sno'],
					'returnStock' => 'n',
					'originGiveMileage' => 0,
					'refundGiveMileage' => 0,
					'refundGoodsPrice' =>  (int)($li['ori_goodsPrice'] + $li['optionPrice']+$li['optionTextPrice']) * $li['goodsCnt'],
				];
			}
			
			foreach ($getData['refundData']['aDeliveryAmount'] as $orderDeliverySno => $aVal) {

				$params['refundDeliveryCharge_'.$orderDeliverySno.'Max']=$aVal['iAmount'];
				$params['refundDeliveryCharge_'.$orderDeliverySno.'Coupon']	=$aVal['iCoupon'];
				$params['refundDeliveryCharge_'.$orderDeliverySno]	=$aVal['iAmount'];

			}
			
			$params['check']['totalSettlePrice'] = $order['settlePrice'];
			$params['check']['totalRefundCharge'] = 0;
			$params['check']['totalDeliveryCharge'] = $order['totalDeliveryCharge'];
			$params['check']['totalRefundPrice'] = $refundData['totalRefundPrice'];
			
			

			$params['check']['totalDeliveryInsuranceFee'] = 0;
			$params['check']['totalGiveMileage'] = 0;
			$params['tmp']['refundMinusMileage'] = 'y';
			
			$memNo = \Session::get("member.memNo");
			$memInfo = $this->db->fetch("select mileage from ".DB_MEMBER." where memNo='$memNo'");
			$params['tmp']['memberMileage'] = $memInfo['mileage'];
			
			
			$params['lessRefundPrice'] = $refundData['totalRefundPrice'];
			$params['refundPriceSum'] = $order['settlePrice'];
			$params['refundGoodsPriceSum'] = 0;
			$params['refundDeliveryPriceSum'] = 0;
			$params['etcGoodsSettlePrice'] = 0;
			$params['etcDeliverySettlePrice'] = 0;
			$params['etcRefundAddPaymentPrice'] = 0;
			$params['etcRefundGoodsAddPaymentPrice'] = 0;
			$params['etcRefundDeliveryAddPaymentPrice'] = 0;
			$params['info']['refundMethod'] = $refundMethod;
			$params['info']['completePgPrice'] = $order['settlePrice'];
			$params['info']['refundGoodsUseDeposit'] = $order['useDeposit'];
			$params['info']['refundGoodsUseMileage'] = $order['useMileage'];
			$params['info']['handleReason'] = '기타';
			$params['info']['handleDetailReason'] = '사용자 셀프취소';
			if ($order['settleKind'] == 'gb') {
				$params['info']['completeMileagePrice'] = $order['settlePrice'];
				$params['info']['completePgPrice'] = 0;				
			}
			$params['returnStockFl'] = 'y';
			
			$sql = "SELECT memberCouponNo FROM " . DB_ORDER_COUPON . " WHERE orderNo = ?";
			$clist = $this->db->query_fetch($sql, ["i", $orderNo]);
			if ($clist) {
				foreach ($clist as $c) {
					$params['returnCoupon'][$c['memberCouponNo']] = 'y';
				}
			}
		
			$arrData = [
				'changeStatus' => 'r3',
			];
			 foreach ($list as $li) {
				$arrData['sno'][]= $li['sno'];
			 }
			 
			 $order = App::load(\Component\Order\Order::class);
			try {
				$orderReorderCalculation = new \Component\Order\ReOrderCalculation();
				$orderReorderCalculation->setRefundCompleteOrderGoodsNew($params, true);
				
				//2021-01-15 웹앤모바일 튜닝 셀프취소 시 사은품 재고 복구
                // 주문에 지급된 사은품 번호를 얻는다
                $sql = "SELECT sno , giftNo , giveCnt , selectCnt , minusStockFl ,minusRestoreStockFl from es_orderGift where orderNo = '{$orderNo}'";
                $tmp = $this->db->query_fetch($sql);


                foreach($tmp as $key=>$val){
                    if($val['minusRestoreStockFl'] == 'y'){
                        break;
                    }

                    if($val['giveCnt'] == 0){
                        $giveCnt = $val['selectCnt'];

                    }else{
                        $giveCnt = $val['giveCnt'];
                    }

                    // 사은품이 상품인지 일반 사은품인지 얻는다
                    $sql = "SELECT stockFl , stockCnt , isGoodsGift , goodsNo from es_gift where giftNo = ".$val['giftNo']."";
                    $tmp2 = $this->db->fetch($sql);

                    // 상품 사은품인 경우
                    if($tmp2['isGoodsGift'] == 'y'){
						$sql = "SELECT stockFl , totalStock from es_goods where goodsNo = ".$tmp2['goodsNo']."";
						$tmp3 = $this->db->fetch($sql);

						//상품의 재고가 제한없음이 아닌경우 상품의 재고를 복구해준다
						if($tmp3['stockFl'] == 'y'){
							$tmp3['totalStock'] += $giveCnt;
							$sql = "UPDATE es_goods SET totalStock = ".$tmp3['totalStock']." where goodsNo= ".$tmp2['goodsNo']."";
							$this->db->fetch($sql);

						}

                        // 사은품 재고가 제한없음이 아닌 경우  사은품 재고를 복구해준다
                        if($tmp2['stockFl'] = 'y'){
                            $tmp2['stockCnt'] += $giveCnt;
                            $sql = "UPDATE es_gift SET stockCnt = ".$tmp2['stockCnt']." where giftNo = ".$val['giftNo']." ";
                            $this->db->fetch($sql);
                        }
                    //일반 사은품인 경우
                    }else{
                        // 사은품 재고가 제한없음이 아닌 경우  사은품 재고를 복구해준다
                        if($tmp2['stockFl'] = 'y'){
                            $tmp2['stockCnt'] += $giveCnt;
                            $sql = "UPDATE es_gift SET stockCnt = ".$tmp2['stockCnt']." where giftNo = ".$val['giftNo']." ";
                            $this->db->fetch($sql);

                        }
                    }

                    $sql = "UPDATE es_orderGift SET minusRestoreStockFl = 'y' where sno = ".$val['sno']." ";
                    $this->db->fetch($sql);
                }

                //2021-01-15 웹앤모바일 튜닝 셀프취소 시 사은품 재고 복구
				
			} catch (Exception $e) {
				if ($e->getMessage()  == '환불을 실패하였습니다.[주문서 정보 저장 실패]') {
					$order->statusChangeCodeR($orderNo, $arrData, true, null, true);
					$orderReorderCalculation->restoreRefundCoupon($params);
					$orderReorderCalculation->restoreRefundUseMileage($params, $orderInfo,['restoreMileageSnos' => $arrData['sno']]);
					$orderReorderCalculation->restoreRefundUseDeposit($params, $orderInfo, ['restoreDepositSnos' => $arrData['sno']]);
					return true;
				} else {
					
				}
			} 
			
			$order->statusChangeCodeR($orderNo, $arrData, true, null, true);
			$orderReorderCalculation->restoreRefundCoupon($params);
			$orderReorderCalculation->restoreRefundUseMileage($params, $orderInfo,['restoreMileageSnos' => $arrData['sno']]);
			$orderReorderCalculation->restoreRefundUseDeposit($params, $orderInfo, ['restoreDepositSnos' => $arrData['sno']]);
			
			$param = [
				'handleDt = ?',
			];
			
			$bind = [
				'si',
				date("Y-m-d H:i:s"),
				$handle['sno'],
			];
			
			$this->db->set_update_db(DB_ORDER_HANDLE, $param, "sno = ?", $bind);
			
			return true;
		}
		/* 환불 완료 처리 END */
	}
}