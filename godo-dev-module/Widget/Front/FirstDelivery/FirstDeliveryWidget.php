<?php

namespace Widget\Front\FirstDelivery;

class FirstDeliveryWidget extends \Widget\Front\Widget
{
	public function index()
	{
		$first = \App::load('Component\Wm\FirstDelivery');
		$goodsNo = $this->getData('goodsNo');

		$firstData = $first -> getFirstDelivery($goodsNo);

		// 공휴일
		$year = date('Y' , time());
		$holidayList = $first -> holiday($year);
		
		$yoil = array("일","월","화","수","목","금","토");

		// 시작 날짜의 키값
		$start = 0;
		// 기준일. 당일/ 
		/*
		* 일~토까지 확인하여 $day값을
		* 일 : 0
		* 월 : -1
		* 화 : -2
		* 수 : -3
		* 목 : -4
		* 금 : -5
		* 토 : -6
		* 으로 설정
		*/
		$dateW = date('w' , time());

		if($dateW == '1'){
			$day = '-1';
		}elseif($dateW == '2'){
			$day = '-2';
		}elseif($dateW == '3'){
			$day = '-3';
		}elseif($dateW == '4'){
			$day = '-4';
		}elseif($dateW == '5'){
			$day = '-5';
		}elseif($dateW == '6'){
			$day = '-6';
		}else{
			$day = '0';
		}
		
		// 달력은 총 4주로 나와야 하기 때문에 요일마다 값을 차감하여 일요일을 기준으로 나오도록 설정
		$d_period = '28';
		$baseDay = date("m월 d일 ", strtotime($day."days"));
			

		for($i=0;$i<$d_period;$i++){
			$tmpDate = strtotime(date("Ymd", strtotime(($day+$i)."days")));

			$period['day'][$i] = date("d", $tmpDate);
			
			$period['value'][$i] = $tmpDate;
			//$period['day'][$i] .= "(".$yoil[date('w',$tmpDate)].")";
			$period['yoil'][$i] = date('w' , $tmpDate);

		}
		
	
		// 상품에 설정한 첫배송 데이터
		if($firstData['yoil']){
			$first_yoil = $firstData['yoil'];
			$first_yoil = explode(',' , $first_yoil);
			
			if($first_yoil){
				// 토,일 제외
				foreach($first_yoil as $key => $value){
					if($value == 'mon'){
						$first_yoil[$key] = 1;
					}elseif($value == 'tue'){
						$first_yoil[$key] = 2;
					}elseif($value == 'wed'){
						$first_yoil[$key] = 3;
					}elseif($value == 'thu'){
						$first_yoil[$key] = 4;
					}elseif($value == 'fri'){
						$first_yoil[$key] = 5;
					}
				}
			}
		}
		
		
		if($firstData['yoilNextDay']){
			// 데이터는 월부터 시작하기 때문에 key 값을 수정해 줘야 함.
			$first_yoilNextDay = $firstData['yoilNextDay'];
			
			$first_yoilNextDay = explode(',' , $first_yoilNextDay);
			$yoil_NextDay = [];
			foreach($first_yoilNextDay as $key => $value){
				// 맨 마지막 키 값을 0번 배열로 변경
				if($key == '6'){
					$yoil_NextDay[0] = $value;
				// 나머지는 키값 +1	
				}else{
					$yoil_NextDay[$key+1] = $value;
				}
			}
			// 키 정렬
			ksort($yoil_NextDay);
			
			// 0~6 , 일~토
			// 해당 요일에 맞는 선택 가능날짜 확인 ( 해당 요일의 카운트 수 )
			$cnt = $yoil_NextDay[$dateW];
			if(!$cnt)
				$cnt = 0;
		
				
			// 오늘 일 확인
			$day = date('d');
			

			foreach($period['day'] as $key => $value){
				// 먼저 모두 n으로 처리
				$period['selectYoil'][$key] = 'n'; 
				
				if($value == (int)$day){
					$start = $key;
				}
			}
			
			if($firstData['firstCnt']){
				$first_cnt = $firstData['firstCnt'];
			}
		
			// 해당 요일을 기준으로 시작. 예) 오늘 수요일(16)이면 수요일에 해당하는 숫자만큼 카운트 후 체크한 요일만 활성화 
			// 해당 요일에 맞는 숫자 카운트 후 시작.
			foreach($period['selectYoil'] as $key => $value){
				if($key >= $start+$cnt){
					$holiFl = false;
					// 선택 가능 요일에 해당한다면 활성화
					if(in_array($period['yoil'][$key] , $first_yoil)){
						// 공휴일 체크
						// 활성화되는요일이 공휴일인 경우 비활성화 처리
						if($holidayList){
							foreach($holidayList as $key_1 => $value_1){
								if($period['value'][$key] == $key_1){
									$holiFl = true;
									break;
								}
							}
						}
						if(!$holiFl){
							if($first_cnt > 0){
								$period['selectYoil'][$key] = 'y';
								$first_cnt--;
							}
						}
					}
				}
			}

		}
		
	
		$period['day'] = array_chunk($period['day'] , 7);
		$period['value'] = array_chunk($period['value'] , 7);
		$period['yoil'] = array_chunk($period['yoil'] , 7);
		$period['selectYoil'] = array_chunk($period['selectYoil'] , 7);
if(\Request::getRemoteAddress()=='58.29.28.124'){
	//gd_debug(date('d' , time()));
	$d = date('d' , time());
	$arrData['firstDelivery'] = '31';
	if($d > $arrData['firstDelivery']){
		$timestamp = strtotime('+1 month');
		$Ym = date('Ym' , $timestamp);
	}else{
		$Ym = date('Ym' , time());
	}
	//gd_debug($Ym);
	//gd_debug(date('Ym' , strtotime('+1 month')));
				
}
		$this->setData('period' , $period);
		
		
	}
}