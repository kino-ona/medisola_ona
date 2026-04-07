<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 */
namespace Component\Database;

use Component\Database\Traits\DBTableFieldGiftOrder;

/**
 * DB Table 기본 Field 클래스 - DB 테이블의 기본 필드를 설정한 클래스 이며, prepare query 생성시 필요한 기본 필드 정보임
 * @package Component\Database
 * @static  tableConfig
 */
class DBTableField extends \Bundle\Component\Database\DBTableField
{
	use DBTableFieldGiftOrder;


    public static function tableOurMenu()
    {
        $arrField = [
            ['val' => 'id', 'typ' => 'i', 'def' => null], // 일련번호
            ['val' => 'name', 'typ' => 's', 'def' => ''], // 테스트 번호
            ['val' => 'title', 'typ' => 's', 'def' => ''], // 테스트 아이디
            ['val' => 'description', 'typ' => 's', 'def' => ''], // 테스트 아이디
            ['val' => 'imageUrlWeb', 'typ' => 's', 'def' => ''], // 테스트 아이디
            ['val' => 'imageUrlMob', 'typ' => 's', 'def' => ''], // 테스트 아이디
            ['val' => 'link', 'typ' => 's', 'def' => ''], // 테스트 아이디
            ['val' => 'tags', 'typ' => 's', 'def' => ''], // 테스트 아이디
            ['val' => 'sortPosition', 'typ' => 'i', 'def' => 0], // 테스트 아이디
            ['val' => 'displayChannel', 'typ' => 's', 'def' => 'ALL'], // 테스트 아이디
        ];
        return $arrField;
    }

    public static function tableGoods($conf = null)
    {
        $arrField = parent::tableGoods($conf);
        $arrField[] = ["val" => "useEarlyDelivery", "typ" => "i", "def" => 0]; // 새벽배송 사용여부
        $arrField[] = ["val" => "earlyDeliveryUrl	", "typ" => "s", "def" => '']; // 새벽배송조회 URL
        $arrField[] = ["val" => "useGoodsViewIcon	", "typ" => "i", "def" => 0]; // 상품상세 아이콘 설정여부
        $arrField[] = ["val" => "useGoodsViewBanner	", "typ" => "i", "def" => 0]; // 상품상세 배너 설정여부
		$arrField[] = ["val" => "useGift", "typ" => "i", "def" => 0]; // 선물하기 사용여부
		$arrField[] = ["val" => "useFirst", "typ" => "i", "def" => 0]; // 첫배송 사용여부
        $arrField[] = ['val' => 'isSubscription', 'typ' => 'i', 'def' => 0, 'name' => '정기결제상품여부'];
        $arrField[] = ['val' => 'linkedSubscriptionGoodsNo', 'typ' => 'i', 'def' => 0, 'name' => '연결된 정기결제 상품번호'];
        return $arrField;
    }
	
	/**
     * [주문] orderGoods 필드 기본값
     *
     * @author artherot
     * @return array order_goods 테이블 필드 정보
     */
    public static function tableOrderGoods()
    {
		$arrField = parent::tableOrderGoods();
		$arrField[] = ["val" => "firstDelivery" , "typ" => 'i' , "def" => 0]; // 첫 배송일
		$arrField[] = ["val" => "isComponentGoods" , "typ" => 'i' , "def" => 0]; // 골라담기 구성상품 여부
		$arrField[] = ["val" => "addedGoodsPrice" , "typ" => 'i' , "def" => 0]; // 골라담기 구성상품 추가가격(금액)
		return $arrField;
	}
	
	/**
     * [주문] orderInfo 필드 기본값
     *
     * @author artherot
     * @return array order_info 테이블 필드 정보
     */
    public static function tableOrderInfo()
    {
        $arrField = parent::tableOrderInfo();
        $arrField[] = ["val" => "isGiftOrder", 'typ' => 'i', 'def' => 0]; // 선물하기 주문여부
        $arrField[] = ["val" => "cardType", 'typ' => 's', 'def' => '']; // 선물하기 카드 유형
        $arrField[] = ["val" => "cardImage", 'typ' => 's', 'def' => '']; // 선물하기 카드 이미지
        $arrField[] = ["val" => "giftMessage", 'typ' => 's', 'def' => '']; // 선물하기 메세지
        $arrField[] = ["val" => "giftUpdateStamp", 'typ' => 'i', 'def' => 0]; // 선물하기 배송지주소 업데이트 일시
        $arrField[] = ["val" => "giftSmsStamp", 'typ' => 'i', 'def' => 0]; // 선물하기 SMS 전송일시
        $arrField[] = ["val" => "giftExpireSmsStamp", 'typ' => 'i', 'def' => 0]; // 만료알림 SMS 전송일시
        $arrField[] = ["val" => "idxGiftRequest", 'typ' => 'i', 'def' => 0]; // 선물요청 IDX
        $arrField[] = ["val" => "addGiftAddress", 'typ' => 'i', 'def' => 0]; // 선물하기 배송지 추가여부
        $arrField[] = ["val" => "giftAgree", 'typ' => 'i', 'def' => 0]; // 선물하기 개인정보 수집동의 여부
        $arrField[] = ['val' => 'isSubscription', 'typ' => 'i', 'def' => 0, 'name' => '정기결제 주문여부 체크'];
        return $arrField;
    }
	
	/**
     * [주문] cart 필드 기본값
     *
     * @author artherot
     * @return array cart 테이블 필드 정보
     */
    public static function tableCart()
    {
        $arrField = parent::tableCart();
        $arrField[] = ['val' => 'cartType', 'typ' => 's', 'def' => 'cart'];
        $arrField[] = ['val' => 'tmpCartSno', 'typ'=> 'i', 'def' =>0 ]; // 장바구니 번호
        $arrField[] = ['val' => 'firstDelivery', 'typ'=> 'i', 'def' =>0 ]; // 첫배송일
        $arrField[] = ['val' => 'componentGoodsNo', 'typ' => 's', 'def' => null]; // 골라담기 상품 sno 정보 (json_encode(sno))
        $arrField[] = ['val' => 'addGoodsPrices', 'typ' => 's', 'def' => null]; // 추가(골라담기) 상품 금액 정보 (json_encode(sno))
        return $arrField;
    }
	
	public static function tableCartWrite()
    {
        $arrField = parent::tableCartWrite();
        $arrField[] = ['val' => 'tmpCartSno', 'typ'=> 'i', 'def' =>0 ]; // 장바구니 번호
        $arrField[] = ['val' => 'cartType', 'typ' => 's', 'def' => 'cart'];
		$arrField[] = ['val' => 'firstDelivery', 'typ'=> 'i', 'def' =>0 ]; // 첫배송일
        return $arrField;
    }
	
	/**
     * [주문] wish 필드 기본값
     *
     * @author artherot
     * @return array wish 테이블 필드 정보
     */
    public static function tableWish()
    {
		$arrField = parent::tableWish();
        $arrField[] = ['val' => 'tmpCartSno', 'typ'=> 'i', 'def' =>0 ]; // 장바구니 번호
        $arrField[] = ['val' => 'cartType', 'typ' => 's', 'def' => 'cart'];
		$arrField[] = ['val' => 'firstDelivery', 'typ'=> 'i', 'def' =>0 ]; // 첫배송일
        return $arrField;
	}
	
	/**
     * 배송휴무일 설정
     *
     */
    public static function tableWmDeliveryHoliday2()
    {
        $arrField = [
            ['val' => 'datestamp', 'typ' => 'i', 'def' => 0], // 휴무일
            ['val' => 'memo', 'typ' => 's', 'def' => ''], // 배송메모
        ];
        
        return $arrField;
    }

    public static function tableAddGoods($conf = null)
    {
        // 부모 method 상속
        $arrField = parent::tableAddGoods($conf);
        
        // 추가 필드
        $arrField[] = ['val' => 'tags', 'typ' => 's', 'def' => null, 'name' => '태그']; // 골라 담기 기능에서 구성 상품 필터를 위한 테그들, 콤마로 구분
        $arrField[] = ['val' => 'limitCnt', 'typ' => 'i', 'def' => '0', 'name' => '최대 선택 수량']; // 골라 담기시 최대 선택할 수 있는 갯수, 0: 무제한, 0 이상시 제한
        $arrField[] = ['val' => 'goodsSummary', 'typ' => 's', 'def' => null, 'name' => '상품 한 줄 설명']; // 상품 한 줄 설명
        $arrField[] = ['val' => 'subImageNm', 'typ' => 's', 'def' => null,  'name' => '추가 이미지 명']; // 추가 이미지 주로 상품 상세 리스팅에 사용될 예정
        $arrField[] = ['val' => 'subImageRealNm', 'typ' => 's', 'def' => null,  'name' => '추가 이미지 명']; // 추가 이미지 주로 상품 상세 리스팅에 사용될 예정
        $arrField[] = ['val' => 'premiumMultiplier', 'typ' => 'i', 'def' => '1', 'name' => '프리미엄 배수']; // 초과 수량당 추가 가격 배수 (기본 1배)

        return $arrField;
    }

    public static function tableMember()
    {
        $arrField = parent::tableMember();
        $arrField[] = ['val' => 'joinedVia', 'typ' => 's', 'def' => null, 'name' => '가입 경로'];
        return $arrField;
    }

    // 회차배송 회차 테이블
    public static function tableScheduledDelivery()
    {
        $arrField = [
            ['val' => 'sno', 'typ' => 'i', 'def' => null], // 일련번호
            ['val' => 'orderNo', 'typ' => 's', 'def' => null], // 주문번호
            ['val' => 'orderGoodsSno', 'typ' => 'i', 'def' => null, 'name' => '주문상품 일련번호'], // 주문상품 일련번호 (goodsType == goods)
            ['val' => 'orderDeliverySno', 'typ' => 'i', 'def' => null], // 주문배송테이블(orderDelivery) sno
            ['val' => 'scmNo', 'typ' => 'i', 'def' => DEFAULT_CODE_SCMNO], // 공급사 고유 번호
            ['val' => 'round', 'typ' => 'i', 'def' => null], // 회차 수
            ['val' => 'totalRound', 'typ' => 'i', 'def' => null], // 총 회차 수
            ['val' => 'deliveryStatus', 'typ' => 's', 'def' => 'p1'], // 회차 배송 상태
            ['val' => 'invoiceCompanySno', 'typ' => 'i', 'def' => null], // 배송 업체 sno
            ['val' => 'estimatedDeliveryDt', 'typ' => 's', 'def' => null],   // 배송예정일
            ['val' => 'invoiceNo', 'typ' => 's', 'def' => null], // 송장 번호
            ['val' => 'invoiceDt', 'typ' => 's', 'def' => null], // 송장번호 입력일자
            ['val' => 'deliveryDt', 'typ' => 's', 'def' => null], // 배송 일자
            ['val' => 'deliveryCompleteDt', 'typ' => 's', 'def' => null], // 배송완료 일자
            ['val' => 'finishDt', 'typ' => 's', 'def' => null],// 배송확정 일자
            ['val' => 'deliveryLog', 'typ' => 's', 'def' => null], // 배송 관련 로그
            ['val' => 'regDt', 'typ' => 's', 'def' => null], // 등록일
            ['val' => 'modDt', 'typ' => 's', 'def' => null], // 수정일
        ];

        return $arrField;
    }
    
    // 회차배송 회차별 상품 테이블
    public static function tableScheduledDeliveryGoods() {
        $arrField = [
            ['val' => 'sno', 'typ' => 'i', 'def' => null], // 일련번호
            ['val' => 'orderNo', 'typ' => 's', 'def' => null], // 주문번호
            ['val' => 'scheduledDeliverySno', 'typ' => 'i', 'def' => null], // 회차 배송 회차 번호
            ['val' => 'orderGoodsSno', 'typ' => 'i', 'def' => null, 'name' => '주문상품 일련번호'], // 주문상품 일련번호 (goodsType == goods, addGoods)
            ['val' => 'goodsNo', 'typ' => 'i', 'def' => null], // 상품번호
            ['val' => 'goodsCd', 'typ' => 's', 'def' => null], // 상품 코드
            ['val' => 'goodsNm', 'typ' => 's', 'def' => null], // 상품명
            ['val' => 'goodsCnt', 'typ' => 'i', 'def' => null], // 수량
            ['val' => 'goodsPrice', 'typ' => 'i', 'def' => null], // 가격
            ['val' => 'regDt', 'typ' => 's', 'def' => null], // 등록일
            ['val' => 'modDt', 'typ' => 's', 'def' => null], // 수정일
        ];

        return $arrField;
    }

    // 회차배송 배송 상태 관련 로그 테이블
    public static function tableLogDelivery()
    {
        $arrField = [
            ['val' => 'managerId', 'typ' => 's', 'def' => null], // 관리자 아이디
            ['val' => 'managerNo', 'typ' => 'i', 'def' => 0], // 관리자 고유번호
            ['val' => 'managerIp', 'typ' => 's', 'def' => null], // 관리자 접속 아이피
            ['val' => 'orderNo', 'typ' => 's', 'def' => null], // 주문 번호
            ['val' => 'orderGoodsSno', 'typ' => 'i', 'def' => 0], // 주문 상품 일련번호
            ['val' => 'round', 'typ' => 'i', 'def' => null], // 회차
            ['val' => 'goodsSno', 'typ' => 'i', 'def' => 0], // 주문 상품 번호
            ['val' => 'logCode01', 'typ' => 's', 'def' => null], // 로그 코드 1
            ['val' => 'logCode02', 'typ' => 's', 'def' => null], // 로그 코드 2
            ['val' => 'logDesc', 'typ' => 's', 'def' => null], // 로그 내용
        ];

        return $arrField;
    }

    /**
     * es_commonContent 필드 기본값 override
     * - 상품 공통정보 노출위치 필드 추가
     *
     * @return array 테이블 필드 정보
     */
    public static function tableCommonContent()
    {
        $arrField = parent::tableCommonContent();
        $arrField[] = ['val' => 'commonPositionType', 'typ' => 's', 'def' => 'bottom']; // 노출위치 (top/bottom)
        return $arrField;
    }
}