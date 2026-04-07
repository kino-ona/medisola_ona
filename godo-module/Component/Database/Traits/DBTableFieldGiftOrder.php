<?php

namespace Component\Database\Traits;

/**
* 선물하기 DB 테이블 관련 
*
* @author webnmobile
*/
trait DBTableFieldGiftOrder
{
	/**
	* 선물요청하기 
	*
	*/
	public static function tableWmGiftRequest()
	{
		$arrField = [
			['val' => 'goodsNo', 'typ' => 'i', 'def' => '선물상품번호'],
			['val' => 'goodsCnt', 'typ' => 'i', 'def' => '구매수량'],
			['val' => 'orderCellPhone', 'typ' => 's', 'def' => '선물요청 휴대전화	'],
			['val' => 'receiverName', 'typ' => 's', 'def' => '선물받는분'],
			['val' => 'receiverCellPhone', 'typ' => 's', 'def' => '선물받는분'],
			['val' => 'receiverZonecode', 'typ' => 's', 'def' => '받는분 우편번호'],
			['val' => 'receiverAddress', 'typ' => 's', 'def' => '받는분 주소'],
			['val' => 'receiverAddressSub', 'typ' => 's', 'def' => '받는분 나머지 주소'],
			['val' => 'memNo', 'typ' => 'i', 'def' => '회원번호'],
			['val' => 'orderNo', 'typ' => 's', 'def' => '주문번호'],
			['val' => 'smsStamp', 'typ' => 'i', 'def' => 'SMS 전송일시'],
		];
		
		return $arrField;
	}
}