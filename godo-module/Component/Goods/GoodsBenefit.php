<?php

namespace Component\Goods;

/**
 * Class 상품 혜택 관리
 * @package Bundle\Component\Goods
 * @author  cjb3333@godo.co.kr
 */
class GoodsBenefit extends \Bundle\Component\Goods\GoodsBenefit
{

    /**
     * 상품 할인 데이터 재설정 ( 프론트시용)
     *
     * @param array $goodsData 상품 데이터,array $benefitData
     *
     * @return array 상품 데이터
     *
     */
    public function goodsDataFrontConvert2($goodsData,$benefitData=null){

        if($goodsData['goodsBenefitSetFl'] == 'y') { //할인헤택 사용

            if(!empty($benefitData)){
                if(!empty($goodsData['benefitSno'])){
                    //네이버 EP쪽에서 넘어온 데이터는 날짜.Sno 형식
                    $tmpSno = explode('.',$goodsData['benefitSno']);
                    $goodsData['benefitSno'] =  $tmpSno[1];
                    $goodsBenfitData = $benefitData[$goodsData['benefitSno']];
                }else{
                    return $goodsData;
                }

            }else{
                $goodsBenfit = $this->getGoodsLink($goodsData['goodsNo'], true);
                $goodsBenfitData = $goodsBenfit['data'];
            }

            if (empty($goodsBenfitData)) {
                return $goodsData;
            }

            $convert = false;
            if ($goodsBenfitData['benefitUseType'] == 'nonLimit') { //제한없음
                $convert = true;

            } else if ($goodsBenfitData['benefitUseType'] == 'newGoodsDiscount') { //신상품

                if ($goodsBenfitData['newGoodsDateFl'] == 'day') { //신상품 할인 기간이 일인 경우
                    $endTime = strtotime(date("Y-m-d", strtotime("+" . $goodsBenfitData['newGoodsDate'] . " day", strtotime($goodsData[$goodsBenfitData['newGoodsRegFl']]))));
                    $todayTime = strtotime(date("Y-m-d"));
                    if ($todayTime <= $endTime) {
                        $convert = true;
                        $goodsData['periodDiscountDuration'] = strtotime(date("Y-m-d",$endTime)." 23:59:59")- time();
                    }
                } else { //신상품 할인 기간이 시간인 경우
                    $endTime = strtotime("+" . $goodsBenfitData['newGoodsDate'] . " hour", strtotime($goodsData[$goodsBenfitData['newGoodsRegFl']]));
                    $todayTime = strtotime("now");
                    if ($todayTime <= $endTime) {
                        $convert = true;
                        $goodsData['periodDiscountDuration'] = strtotime(date("Y-m-d H:i:s",$endTime))- time();
                    }
                }

            } else if ($goodsBenfitData['benefitUseType'] == 'periodDiscount') { //기간할인
                $convert = true;
                $goodsData['periodDiscountDuration'] = strtotime($goodsBenfitData['periodDiscountEnd'])- time();
            }

            $exceptKey = array('sno', 'benefitScheduleNextSno', 'modDt', 'regDt');
            foreach ($goodsBenfitData as $key => $value) {
                if (in_array($key, $exceptKey)) {
                    continue;
                }

                if ($key == 'goodsIconCd' && $convert) { //아이콘

                    unset($goodsData['goodsBenefitIconCd']);
                    $goodsIconTemp = explode(INT_DIVISION, $goodsData['goodsIconCd']);
                    $goodsBenefitIconTemp = explode(INT_DIVISION, $value);
                    //중복된 아이콘을 제거하고 혜택아이콘을 맨처음 출력하기 위해
                    foreach($goodsIconTemp as $k => $v){
                        if (in_array($v, $goodsBenefitIconTemp)) {
                            unset($goodsIconTemp[$k]);
                        }
                    }
                    $goodsData['goodsIconCd'] = implode(INT_DIVISION,$goodsIconTemp);
                    $goodsData['goodsBenefitIconCd'] = $value;

                } else {
                    if ($convert) {
                        $goodsData[$key] = $value; //그외 할인 정보
                    }
                }

            }

            if($goodsData['goodsIcon'] && $convert ){
                unset($goodsData['goodsBenefitIconCd']);
                $goodsIconTemp = explode(INT_DIVISION, $goodsData['goodsIcon']);
                $goodsBenefitIconTemp = explode(INT_DIVISION, $goodsBenfitData['goodsIconCd']);
                //중복된 아이콘을 제거하고 혜택아이콘을 맨처음 출력하기 위해
                foreach($goodsIconTemp as $k => $v){
                    if (in_array($v, $goodsBenefitIconTemp)) {
                        unset($goodsIconTemp[$k]);
                    }
                }
                $goodsData['goodsIcon'] = implode(INT_DIVISION,$goodsIconTemp);
                $goodsData['goodsBenefitIconCd'] = $goodsBenfitData['goodsIconCd'];
            }

        } else { //개별할인 사용

            if ($goodsData['goodsDiscountFl'] == 'y') {

                if ($goodsData['benefitUseType'] == 'newGoodsDiscount') { //신상품
                    if ($goodsData['newGoodsDateFl'] == 'day') { //신상품 할인 기간이 일인 경우
                        $endTime = strtotime(date("Y-m-d", strtotime("+" . $goodsData['newGoodsDate'] . " day", strtotime($goodsData[$goodsData['newGoodsRegFl']]))));
                        $todayTime = strtotime(date("Y-m-d"));
                        if ($todayTime <= $endTime) {
                            $goodsData['goodsDiscountFl'] = 'y';
                            $goodsData['periodDiscountDuration'] = strtotime(date("Y-m-d",$endTime)." 23:59:59")- time();
                        } else {
                            $goodsData['goodsDiscountFl'] = 'n';
                        }
                    } else { //신상품 할인 기간이 시간인 경우
                        $endTime = strtotime("+" . $goodsData['newGoodsDate'] . " hour", strtotime($goodsData[$goodsData['newGoodsRegFl']]));
                        $todayTime = strtotime("now");
                        if ($todayTime <= $endTime) {
                            $goodsData['goodsDiscountFl'] = 'y';
                            $goodsData['periodDiscountDuration'] = strtotime(date("Y-m-d H:i:s",$endTime))- time();
                        } else {
                            $goodsData['goodsDiscountFl'] = 'n';
                        }
                    }

                } else if ($goodsData['benefitUseType'] == 'periodDiscount') { //기간할인
                    if (strtotime($goodsData['periodDiscountStart']) < strtotime("now") && strtotime($goodsData['periodDiscountEnd']) > strtotime("now")) {
                        $goodsData['goodsDiscountFl'] = 'y';
                        $goodsData['periodDiscountDuration'] = strtotime($goodsData['periodDiscountEnd'])- time();
                    } else {
                        $goodsData['goodsDiscountFl'] = 'n';
                    }
                }
            }
        }

        return $goodsData;
    }
}
