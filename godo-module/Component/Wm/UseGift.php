<?php
namespace Component\Wm;

use Request;
use App;

class UseGift
{
	private $db;
	
	public function __construct()
	{
		$this->db = App::load(\DB::class);
	}
	
	/*
	* 선물하기 사용여부
	*
	*
	*/
	public function getUseGift($goodsNo)
	{
		$this->db->strField = "useGift";
		$this->db->strWhere = "goodsNo = '{$goodsNo}'";
		$query = $this->db->query_complete();
		$sql = "SELECT ".array_shift($query)." FROM es_goods".implode(' ',$query);
		//gd_debug($sql);
		return $this->db->fetch($sql);
	
	}
	
	/*
	* 배너 사용여부
	*
	*
	*/
	public function getBannerUse()
	{
		$this->db->strField = "useBannerPc, useBannerMobile";
		$query= $this->db->query_complete();
		$sql = "SELECT".array_shift($query)."FROM wm_giftSet";
		$useBanner = $this->db->fetch($sql);
		return $useBanner;
	}
	
	/*
	* 관리자 주문서페이지 선물하기 여부
	*
	*
	*/
	public function getGiftUse($orderNo)
	{
		$this->db->strField = "isGiftOrder";
		$this->db->strWhere = "orderNo = {$orderNo}";
		$query = $this->db->query_complete();
		$sql ="SELECT".array_shift($query)."FROM es_orderInfo".implode(' ', $query);
		$data = $this->db->fetch($sql);
		return $data;
	}
	
	/*
	* 선물하기 사용설정(goods에 있는거 말고)
	* goodsView
	*
	*/
	public function GiftUseSet($goodsNo)
	{
		$this->db->strField = "isUse ,useRange";
		$query = $this->db->query_complete();
		$sql = "SELECT".array_shift($query)."FROM wm_giftSet";
		$isUse = $this->db->fetch($sql);
		$goodsNo['isUse'] = $isUse['isUse'];
		$goodsNo['useRange'] = $isUse['useRange'];
		
		$this->db->strField = "useGift";
		$this->db->strWhere = "goodsNo = {$goodsNo['goodsNo']}";
		$querys = $this->db->query_complete();
		$strSQL = "SELECT".array_shift($querys)."FROM es_goods".implode(' ', $querys);
		$useGift = $this->db->fetch($strSQL);
		$goodsNo['useGift'] = $useGift['useGift'];
		
		return $goodsNo;
	}
	
	/*
	* 선물하기 사용설정(goods에 있는거 말고)
	* cart
	*
	*/
	public function GiftUseSetCart($goodsNo = null)
	{
		$this->db->strField = "isUse, useRange";
		$query = $this->db->query_complete();
		$sql = "SELECT".array_shift($query)."FROM wm_giftSet";
		$isUse = $this->db->fetch($sql);
		return $isUse;
	}

    /* 2023-03-23 웹앤모바일 추가
    * 선물하기 보내는 사람이 주소 입력 했는지 체크
    *
    *
    */
    public function getAddGiftAddress($orderNo)
    {
        $this->db->strField = "addGiftAddress, giftUpdateStamp";
        $this->db->strWhere = "orderNo = {$orderNo}";
        $query = $this->db->query_complete();
        $sql ="SELECT".array_shift($query)."FROM es_orderInfo".implode(' ', $query);
        $data = $this->db->fetch($sql);
        return $data;
    }

    /* 2023-03-23 웹앤모바일 추가
    * 개인정보 수집 동의 업데이트
    *
    *
    */
    public function updateGiftAgree($orderNo)
    {
        $param = [
            'giftAgree = ?',
        ];

        $bind = [
            'is',
            1,
            $orderNo,
        ];

        $this->db->set_update_db(DB_ORDER_INFO, $param, "orderNo = ?", $bind);
    }

    /*2023-03-23 웹앤모바일 추가
     * orderStatus 가져오기
     *
     *
     */
    public function checkOrderStatus($orderNo)
    {
        $this->db->strField = "orderStatus";
        $this->db->strWhere = "orderNo = {$orderNo}";
        $query = $this->db->query_complete();
        $sql ="SELECT".array_shift($query)."FROM es_order".implode(' ', $query);
        $data = $this->db->fetch($sql);
        return $data;
    }
}