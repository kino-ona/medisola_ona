<?php

namespace Controller\Admin\Goods;

use App;
use Request;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Component\Database\DBTableField;


class FirstDeliveryPsController extends \Controller\Admin\Controller
{
	public function index()
	{
		$in = Request::request()->toArray();

		$db = App::load(\DB::class);
		
		try{
			switch($in['mode'])
			{
				case "update_goods_config" :
					if (empty($in['goodsNo']))
						throw new AlertOnlyException("수정할 상품을 선택하세요.");

					foreach ($in['goodsNo'] as $goodsNo) {
						$param = [
							'useFirst = ?', 
						];
						if($in['firstDelivery'][$goodsNo] == 'y'){
							$useFirst = 1;
						}else{
							$useFirst = 0;
						}
						
					
						$yoil = $in['yoil'][$goodsNo];
						$yoil = implode(',' , $yoil);
						$yoilNextDay = [];
						
						$yoilNextDay[] = $in['yoilNextDay_mon'][$goodsNo]?$in['yoilNextDay_mon'][$goodsNo]:0;
						$yoilNextDay[] = $in['yoilNextDay_tue'][$goodsNo]?$in['yoilNextDay_tue'][$goodsNo]:0;
						$yoilNextDay[] = $in['yoilNextDay_wed'][$goodsNo]?$in['yoilNextDay_wed'][$goodsNo]:0;
						$yoilNextDay[] = $in['yoilNextDay_thu'][$goodsNo]?$in['yoilNextDay_thu'][$goodsNo]:0;
						$yoilNextDay[] = $in['yoilNextDay_fri'][$goodsNo]?$in['yoilNextDay_fri'][$goodsNo]:0;
						$yoilNextDay[] = $in['yoilNextDay_sat'][$goodsNo]?$in['yoilNextDay_sat'][$goodsNo]:0;
						$yoilNextDay[] = $in['yoilNextDay_sun'][$goodsNo]?$in['yoilNextDay_sun'][$goodsNo]:0;
						
						$yoilNextDay = implode(',' , $yoilNextDay);
						
						$firstCnt = $in['firstCnt'][$goodsNo]?$in['firstCnt'][$goodsNo]:0;
						
						
						$bind = ["ii", $useFirst, $goodsNo];
						
						$db->set_update_db(DB_GOODS, $param, "goodsNo = ?", $bind);
						
						// 상품번호 데이터 확인
						$sql = "SELECT sno FROM wm_firstDelivery WHERE goodsNo = '{$goodsNo}'";
						$sno = $db->fetch($sql);
						
						$arrBind = [];
						
						// update
						if($sno['sno']){
							$sql = "UPDATE wm_firstDelivery SET yoil = ? , yoilNextDay = ? , firstCnt = ? , modDt = sysdate() WHERE goodsNo = ?";
							$db->bind_param_push($arrBind , 's' , $yoil);
							$db->bind_param_push($arrBind , 's' , $yoilNextDay);
							$db->bind_param_push($arrBind , 'i' , $firstCnt);
							$db->bind_param_push($arrBind , 'i' , $goodsNo);
						// insert
						}else{
							$sql = "INSERT INTO wm_firstDelivery SET goodsNo = ? , yoil = ? , yoilNextDay = ? , firstCnt = ?";
							$db->bind_param_push($arrBind , 'i' , $goodsNo);
							$db->bind_param_push($arrBind , 's' , $yoil);
							$db->bind_param_push($arrBind , 's' , $yoilNextDay);
							$db->bind_param_push($arrBind , 'i' , $firstCnt);	
						}
						$db->bind_query($sql , $arrBind);
						
					}
					
					return $this->layer("수정되었습니다.");
					break;
					
				/* 공휴일 등록 */	
				case "register_holiday":
					if(empty($in['date']))
						throw new AlertOnlyException("공휴일을 입력해주십시오.");
					
					$db->set_delete_db("wm_deliveryHoliday2", "datestamp = ?", ["i", strtotime($in['date'])]);
					$inData = [
						'datestamp' => strtotime($in['date']),
						'memo' => $in['memo'],
					];
											
					$arrBind = $db->get_binding(DBTableField::tableWmDeliveryHoliday2(), $inData, "insert");
					
					$strSQL = "INSERT INTO wm_deliveryHoliday2 set datestamp = ?, memo = ?";
					$db->bind_query($strSQL,$arrBind['bind']);
					return $this->layer("저장하였습니다.");	
					
					break;	
				/* 공휴일 삭제 */
				case "delete_holiday":
					if(empty($in['idx']))
				        throw new AlertOnlyException("선택된 것이 없습니다.");
				    
				    foreach($in['idx'] as $val){
				        $db->set_delete_db("wm_deliveryHoliday2", "datestamp = ?", ["i", $val]);
				    }
				    return $this->layer("삭제되었습니다.");
					
					break;	
			}
			
			exit;
			
			
		}catch (AlertOnlyException $e) {
			throw $e;
		} catch (Exception $e) {
			throw $e;
		}
	
	}
}