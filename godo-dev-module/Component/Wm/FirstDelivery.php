<?php

namespace Component\Wm;

class FirstDelivery
{
	public function __construct()
	{
		$this->db = \App::load(\DB::class);
	}
	
	
	/**
	* 관리자, 상품 첫배송 설정데이터 GET
	*
	* @param Array $data
	* @return Array $data
	*
	*/
	public function getFirstCfgGoodsData($data)
	{
		if(count($data) > 0 ){
			foreach($data as $key => $value){
				$this->db->strField = "g.useFirst , w.yoil , w.yoilNextDay , w.firstCnt";
				$this->db->strJoin = "LEFT JOIN wm_firstDelivery w ON g.goodsNo = w.goodsNo";
				$this->db->strWhere = "g.goodsNo = '{$value['goodsNo']}'";
				$query = $this->db->query_complete();
				$sql = "SELECT".array_shift($query)."FROM ".DB_GOODS." g".implode(' ' , $query);
				$firstData = $this->db->fetch($sql);
				
				if($firstData['yoil'])
					$firstData['yoil'] = explode(',' , $firstData['yoil']);
				
				if($firstData['yoilNextDay'])
					$firstData['yoilNextDay'] = explode(',' , $firstData['yoilNextDay']);

				$data[$key]['firstData'] = $firstData;
			}
		}
		return $data;
	}
	
	/**
	* 공휴일 목록
	*
	* @param varChar $year
	* @return Array $list
	*
	*/
	public function holiday($year = null)
	{
		$where = '';
		// 선택한 년도만 출력
		if($year){
			if($year !='all'){
				$yearS = $year."-01-01";
				$yearE = $year."-12-31";
				$where = "datestamp BETWEEN '".strtotime($yearS)."' AND '".strtotime($yearE)."'";
			}
		}

		$this->db->strOrder = 'datestamp';
		$this->db->strWhere = $where;
		$query = $this->db->query_complete();
		$sql = "SELECT".array_shift($query)."FROM wm_deliveryHoliday2".implode(' ' , $query);

		$tmp = $this->db->query_fetch($sql);
	    $list = [];

	    if ($tmp) {
	        foreach ($tmp as $t) {
	            $list[$t['datestamp']] = $t;
	        }
	    } // endif
	    
	    return $list;
	}
	
	
	/**
	* 상품번호에 맞는 첫배송 데이터 GET
	* @param Integer $goodsNo
	*
	*
	*/
	public function getFirstDelivery($goodsNo)
	{
		if($goodsNo){
			$this->db->strWhere = "goodsNo = '{$goodsNo}'";
			$query = $this->db->query_complete();
			$sql = "SELECT".array_shift($query)."FROM wm_firstDelivery".implode(' ' , $query);
			return $this->db->fetch($sql);
		}
	}
	
	
	/**
	* 장바구니에 담긴 상품들을 확인하여 첫 배송일을 선택한 상품이 하루를 기준으로 해당하지 않으면 해당 상품 장바구니에서 제거
	*
	*
	*
	*/
	public function checkTodayFirstDelivery()
	{
		$day = date('Y-m-d');

		$memNo = \Session::get('member.memNo');
		$siteKey = \Session::get('siteKey');
		
		$where = '';
		$this->db->strField = "sno , regDt , firstDelivery";
		if($memNo){
			$where = "memNo = {$memNo} AND firstDelivery != 0";
		}else{
			$where = "memNo = 0 AND siteKey = {$siteKey} AND firstDelivery != 0";
		}
		$this->db->strWhere = $where;
		$query = $this->db->query_complete();
		$sql = "SELECT".array_shift($query)."FROM ".DB_CART.implode(' ' , $query);
		$list = $this->db->query_fetch($sql);
		
		if(count($list) > 0 ){
			foreach($list as $key => $value){
				$regDt = substr($value['regDt'] , 0 , 10);
				// 당일이 아닌 장바구니 건은 삭제
				if($day != $regDt){
					$sql = "DELETE FROM ".DB_CART." WHERE sno = {$value['sno']}";
					$this->db->fetch($sql);
				}
			}
		}

	}
	
	
	/**
	* 주문완료페이지, 첫 배송일 주문건 체크
	* @param Integer $sno
	* @return varChar $firstDate
	*
	*/
	public function getFirstDeliveryOrder($sno)
	{
		if($sno){
			$this->db->strField = "firstDelivery";
			$this->db->strWhere = "sno = {$sno}";
			$query = $this->db->query_complete();
			$sql = "SELECT".array_shift($query)."FROM ".DB_ORDER_GOODS.implode(' ' , $query);
			$firstDate = $this->db->fetch($sql);
			if($firstDate['firstDelivery'] > 0){
				$daily = array('일','월','화','수','목','금','토');

				$w = $daily[date('w' , strtotime($firstDate['firstDelivery']))];

				$firstDate = date('Y-m-d' , strtotime($firstDate['firstDelivery']));
				$firstDate .="(".$w.")";
			}else{
				$firstDate = 0;
			}
		}

		return $firstDate;
	}


	/**
	* 정기결제 상품의 다음 가능 배송일 자동 계산
	* @param Integer $goodsNo
	* @return Array $result ['date', 'yoil', 'timestamp', 'formatted']
	*
	*/
	public function calculateNextAvailableDeliveryDate($goodsNo)
	{
		if(!$goodsNo){
			return null;
		}

		// 1. 상품 첫 배송 설정 가져오기
		$firstData = $this->getFirstDelivery($goodsNo);
		if(!$firstData || !$firstData['yoil']){
			return null;
		}

		// 2. 공휴일 목록 가져오기
		$year = date('Y');
		$holidayList = $this->holiday($year);

		// 3. 요일 배열
		$yoilNames = array("일","월","화","수","목","금","토");

		// 4. 배송 가능 요일 파싱 (mon, tue, wed, thu, fri)
		$first_yoil = explode(',', $firstData['yoil']);
		$availableYoils = [];
		foreach($first_yoil as $value){
			if($value == 'mon') $availableYoils[] = 1;
			elseif($value == 'tue') $availableYoils[] = 2;
			elseif($value == 'wed') $availableYoils[] = 3;
			elseif($value == 'thu') $availableYoils[] = 4;
			elseif($value == 'fri') $availableYoils[] = 5;
		}

		// 5. 오늘 요일 기준 대기일 계산
		$dateW = date('w', time());
		$waitDays = 0;

		if($firstData['yoilNextDay']){
			$first_yoilNextDay = explode(',', $firstData['yoilNextDay']);
			// yoilNextDay는 월~일 순서 (0:월 ~ 6:일)
			// dateW는 일~토 순서 (0:일 ~ 6:토)
			// 변환 필요
			$yoil_NextDay = [];
			foreach($first_yoilNextDay as $key => $value){
				if($key == '6'){
					$yoil_NextDay[0] = $value; // 일요일
				}else{
					$yoil_NextDay[$key+1] = $value; // 월~토
				}
			}
			ksort($yoil_NextDay);

			$waitDays = isset($yoil_NextDay[$dateW]) ? (int)$yoil_NextDay[$dateW] : 0;
		}

		// 6. 시작일부터 최대 60일 동안 검색
		$maxSearchDays = 60;
		for($i = $waitDays; $i < $maxSearchDays; $i++){
			$checkDate = strtotime("+{$i} days");
			$checkYoil = date('w', $checkDate);

			// 배송 가능 요일인지 확인
			if(in_array($checkYoil, $availableYoils)){
				// 공휴일이 아닌지 확인
				$isHoliday = false;
				if($holidayList){
					foreach($holidayList as $holidayTimestamp => $holidayData){
						if($checkDate == $holidayTimestamp){
							$isHoliday = true;
							break;
						}
					}
				}

				// 공휴일이 아니면 이 날짜로 확정
				if(!$isHoliday){
					return [
						'date' => date('Y-m-d', $checkDate),
						'yoil' => $yoilNames[$checkYoil],
						'timestamp' => $checkDate,
						'formatted' => date('n월 j일', $checkDate) . '(' . $yoilNames[$checkYoil] . ')'
					];
				}
			}
		}

		// 60일 내에 배송 가능일을 찾지 못한 경우 null 반환
		return null;
	}
}