<?php

namespace Component\GiftOrder;

use Component\Goods\Goods;
use Component\Database\DBTableField;
use Framework\Debug\Exception\AlertBackException;
use Globals;
use Request;
use Session;
use App;

/**
* 선물하기 장바구니 관련 
*
* @package Component\GiftOrder
* @author webnmobile
*/
class CartGift extends \Component\Cart\Cart
{
	public function __construct()
	{
		parent::__construct();
		
		/* 선물하기 장바구니로 변환 */
		$this->tableName = "wm_gift";
	}
	
	/**
     * 선택 상품 찜 리스트로 저장
     *
     * @param array $getData 장바구니 sno 배열
     *
     * @return bool 결과
     */
    public function setCartToWish($getData)
    {
        if (empty($getData) === true) {
            return false;
        }

        $arrBind = [];
        foreach ($getData as $cartSno) {
            $param[] = '?';
            $this->db->bind_param_push($arrBind, 'i', $cartSno);

            // 장바구니 sno로 goodsNo 조회 - 갯수 변경 처리를 위해 추출
            $changeGoodsNoArray[] = $this->checkCartSelectGoodsNo($cartSno,'', 'sno');
        }

        if (empty($param) === true) {
            return false;
        }

        // 회원 로그인 체크
        if ($this->isLogin === true) {
            $strWhere = 'sno IN (' . implode(' , ', $param) . ') AND memNo = ?';
            $this->db->bind_param_push($arrBind, 'i', $this->members['memNo']);
        } else {
            $strWhere = 'sno IN (' . implode(' , ', $param) . ') AND  siteKey = ?';
            $this->db->bind_param_push($arrBind, 's', $this->siteKey);
        }
		$sql = "SELECT * FROM wm_subCart WHERE " . $strWhere;
		$list = $this->db->query_fetch($sql, $arrBind);
		if ($list) {
			foreach ($list as $li) {
				$arrBind = $this->db->get_binding(DBTableField::tableWish(), $li, "insert", array_keys($li));
				$this->db->set_insert_db(DB_WISH, $arrBind['param'], $arrBind['bind'], "y");
			}
		}
       
        // 장바구니 갯수 변경 처리
        $goods = \App::load(\Component\Goods\Goods::class);
        foreach ($changeGoodsNoArray as $goodsNo) {
            $goods->setCartWishCount('wish',  $goodsNo['goodsNo']);
        }

        return true;
    }
	
	 /**
     * 상품 상세 혜택 계산
     * goodsViewBenefit
     *
     * @param $getData
     *
     * @return array
     */
    public function goodsViewBenefit($getData)
    {

        gd_isset($getData['goodsMileageExcept'], 'n');
        gd_isset($getData['couponBenefitExcept'], 'n');
        gd_isset($getData['memberBenefitExcept'], 'n');
        $memberDcFl = true;

        // 회원 추가 할인과 중복 할인을 위한 카테고리 코드 정보
        $arrCateCd = $this->getMemberDcForCateCd();

        $getData['goodsNo'] = $getData['goodsNo'][0];
        $getData = $this->getMemberDcFlInfo($getData);
        if (!$getData['goodsPriceSum']) {
            $getData['goodsPriceSum'][] = $getData['set_goods_price'];
        }
	
        //회원등급별 상품할인 기능 추가 (2017-08-18)
        $goods = new Goods();
        $goodsData = $goods->getGoodsInfo($getData['goodsNo']);

        //상품 혜택 모듈
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
		
        //상품혜택 사용시 해당 변수 재설정
        $goodsData = $goodsBenefit->goodsDataFrontConvert($goodsData);

        $exceptBenefit = explode(STR_DIVISION, $goodsData['exceptBenefit']);
        $exceptBenefitGroupInfo = explode(INT_DIVISION, $goodsData['exceptBenefitGroupInfo']);

        // 제외 혜택 대상 여부
        $exceptBenefitFl = false;
        if ($goodsData['exceptBenefitGroup'] == 'all' || ($goodsData['exceptBenefitGroup'] == 'group' && in_array($this->_memInfo['groupSno'], $exceptBenefitGroupInfo) === true)) {
            $exceptBenefitFl = true;
        }

        $data['goodsDcPrice'] = 0;
        $data['memberDcPrice'] = 0;
        $data['couponDcPrice'] = 0;

        // 마이앱 사용에 따른 분기 처리
        if ($this->useMyapp) {
            $data['myappDcPrice'] = 0;
        }

        $data['goodsMileage'] = 0;
        $data['memberMileage'] = 0;
        $data['couponMileage'] = 0;

        $data['totalDcPrice'] = 0;
        $data['totalMileage'] = 0;

        // 마이앱 모듈 호출
        if ($this->useMyapp) {
            $myapp = \App::load('Component\\Myapp\\Myapp');
        }
		
		/* 정기결제 할인율 추출 */
		$subscription = App::load(\Component\SubscriptionNew\Subscription::class);
		$conf = $subscription->getCfg();
		$discount = gd_isset($conf['discount'][0], 0);
		
		if (empty($getData['goodsCnt'])) {
			$getData['goodsCnt'][] = 1;
		}


        foreach ($getData['goodsCnt'] as $k => $v) {
            $price['goodsCnt'] = $getData['goodsCnt'][$k];
            $price['goodsPriceSum'] = $getData['goodsPriceSum'][$k];
            $price['optionPriceSum'] = $getData['optionPriceSum'][$k];
            $price['optionTextPriceSum'] = $getData['optionTextPriceSum'][$k];
            $price['addGoodsPriceSum'] = $getData['addGoodsPriceSum'][$k];
            $price['couponDcPrice'] = 0;

            // 상품단가 계산 (상품, 옵션, 텍스트옵션, 추가상품)
            $unitPrice['goodsCnt'][$k] = $getData['goodsCnt'][$k];
            $unitPrice['goodsPrice'][$k] = $getData['goodsPriceSum'][$k] / $getData['goodsCnt'][$k];
            $unitPrice['optionPrice'][$k] = $getData['optionPriceSum'][$k] / $getData['goodsCnt'][$k];
            $unitPrice['optionTextPrice'][$k] = $getData['optionTextPriceSum'][$k] / $getData['goodsCnt'][$k];
            $unitPrice['addGoodsCnt'][$k] = $getData['addGoodsCnt'][$k];
			
            // 추가 상품
            if (empty($getData['addGoodsNo'][$k]) === false) {
                foreach ($getData['addGoodsNo'][$k] as $key => $v2) {
                    // 추가 상품 디비 정보
                    $arrAddGoodsNo[] = $v2;
                    $getAddGoods = $this->getAddGoodsInfo($arrAddGoodsNo);
                    $getData['addGoodsBrandCd'][$k][$key] = $getAddGoods[$v2]['brandCd'];
                }

                //회원등급 > 브랜드별 추가할인 추가 상품 브랜드 할인율 정보
                if ($this->_memInfo['fixedOrderTypeDc'] == 'brand') {
                    // 추가 상품 브랜드
                    foreach ($getData['addGoodsBrandCd'][0] as $addGoodsKey => $addGoodsBrandCd) {
                        if (in_array($addGoodsBrandCd, $this->_memInfo['dcBrandInfo']->cateCd)) {
                            $goodsBrandInfo[$arrAddGoodsNo[$addGoodsKey]][$addGoodsBrandCd] = $addGoodsBrandCd;
                        } else {
                            if ($addGoodsBrandCd) {
                                $goodsBrandInfo[$arrAddGoodsNo[$addGoodsKey]]['allBrand'] = $addGoodsBrandCd;
                            } else {
                                $goodsBrandInfo[$arrAddGoodsNo[$addGoodsKey]]['noBrand'] = $addGoodsBrandCd;
                            }
                        }

                        foreach ($goodsBrandInfo[$arrAddGoodsNo[$addGoodsKey]] as $gKey => $gVal) {
                            foreach ($this->_memInfo['dcBrandInfo']->cateCd AS $mKey => $mVal) {
                                if ($gKey == $mVal) {
                                    $unitPrice['brandDiscount'][$k][$addGoodsKey] = $this->_memInfo['dcBrandInfo']->goodsDiscount[$mKey];
                                }
                            }
                        }
                    }
                }
            }
			
            if (empty($getData['addGoodsCnt'][$k]) === false) {
                foreach ($getData['addGoodsCnt'][$k] as $key => $value) {
                    $unitPrice['addGoodsPrice'][$k][$key] = $getData['add_goods_total_price'][$k][$key] / $value;
                }
            }
            $unitPrice['priceInfo'][$k] = $price;

            $tmpGoodsPrice = $price['goodsPriceSum']+$price['optionPriceSum']+ $price['optionTextPriceSum'] +$price['addGoodsPriceSum'];
			
            //상품할인가
            if($tmpGoodsPrice > 0 ) {
				$fixedGoodsDiscountData = "option^|^text";
                $goodsDcPrice = $this->getGoodsDcData("y", $discount, "percent", $v, $price, $fixedGoodsDiscountData, $goodsData['goodsDiscountGroup'], $goodsData['goodsDiscountGroupMemberInfo']);
                $data['goodsDcPrice'] += $goodsDcPrice;
                $price['goodsDcPrice'] = $goodsDcPrice;
                if ($goodsDcPrice > 0) {
                    $unitPrice['goodsDcPrice'][$k] = $goodsDcPrice / $v;
                }
            }
		
            // 마이앱 상품 추가 할인
            if ($this->useMyapp) {
                $myappBenefitParams['goodsPrice'] = $price['goodsPriceSum'] / $getData['goodsCnt'][$k];
                $myappBenefitParams['optionPrice'] = $price['optionPriceSum'] / $getData['goodsCnt'][$k];
                $myappBenefitParams['optionTextPrice'] = $price['optionTextPriceSum'] / $getData['goodsCnt'][$k];
                $myappBenefitParams['goodsCnt'] = $getData['goodsCnt'][$k];
                $myappBenefit = $myapp->getOrderAdditionalBenefit($myappBenefitParams);
                if (empty($myappBenefit['discount']['goods']) === false && $myappBenefit['discount']['goods'] > 0) {
                    $data['myappDcPrice'] += $myappBenefit['discount']['goods'];
                    $unitPrice['myappDcPrice'][$k] = $data['myappDcPrice'];
                }
            }

            if ($getData['couponBenefitExcept'] == 'n') {
                //쿠폰 적용 할인 / 적립 금액
                if ($getData['couponApplyNo'][$k] > 0) {
                    $tmpCouponPrice = $this->getMemberCouponPriceData($price, $getData['couponApplyNo'][$k]);
                    if (is_array($tmpCouponPrice['memberCouponAlertMsg']) && (array_search('LIMIT_MIN_PRICE', $tmpCouponPrice['memberCouponAlertMsg']) === true)) {
                        // 'LIMIT_MIN_PRICE' 일때 구매금액 제한에 걸려 사용 못하는 쿠폰 처리
                        // 수량 변경 시 구매금액 제한에 걸림
                        // 적용된 쿠폰 모두 제거
                        if ($getData['displayOptionkey']) {
                            $data['couponAlertKey'][] = $getData['displayOptionkey'][$k];
                        } else {
                            $data['couponAlertKey'][] = $k;
                        }
                    } else {
                    }
                    if (is_array($tmpCouponPrice['memberCouponSalePrice'])) {
                        $goodsOptCouponSalePriceSum = array_sum($tmpCouponPrice['memberCouponSalePrice']);
                    }
                    if (is_array($tmpCouponPrice['memberCouponAddMileage'])) {
                        $goodsOptCouponAddMileageSum = array_sum($tmpCouponPrice['memberCouponAddMileage']);
                    }

                    //쿠폰 할인
                    $data['couponDcPrice'] += $goodsOptCouponSalePriceSum;
                    $unitPrice['couponDcPrice'][$k] = $price['couponDcPrice'] = $goodsOptCouponSalePriceSum;

                    //쿠폰 마일리지
                    $data['couponMileage'] += $goodsOptCouponAddMileageSum;
                    unset($tmpCouponPrice);
                    unset($goodsOptCouponSalePriceSum);
                    unset($goodsOptCouponAddMileageSum);
                }

                // 쿠폰을 사용했고 사용설정에 쿠폰만 사용설정일때 처리
                if ($data['couponDcPrice'] > 0 || $data['couponMileage'] > 0) {
                    $couponConfig = gd_policy('coupon.config');
                    if ($couponConfig['couponUseType'] == 'y' && $couponConfig['chooseCouponMemberUseType'] == 'coupon') {
                        $memberDcFl = false;
                        $data['memberDcPrice'] = 0;
                        $data['memberMileage'] = 0;
                    }
                }
            }
            //회원마일리지
            if ($getData['memberBenefitExcept'] == 'n' && $memberDcFl == true) {

                //회원 추가 상품 할인
                $tmp = $this->getMemberDcPriceData($getData['goodsNo'], $this->_memInfo, $price, $arrCateCd, $getData['addDcFl'], $getData['overlapDcFl']);

                // 회원 추가 할인혜택 적용 제외
                if (in_array('add', $exceptBenefit) === true && $exceptBenefitFl === true) {} else {
                    $data['memberDcPrice'] += $tmp['memberDcPrice'];
                    $data['tmpMemberDcPrice'] += $tmp['memberDcPrice'];
                }
                // 회원 중복 할인혜택 적용 제외
                if (in_array('overlap', $exceptBenefit) === true && $exceptBenefitFl === true) {} else {
                    $data['memberDcPrice'] += $tmp['memberOverlapDcPrice'];
                    $data['tmpMemberOverlapDcPrice'] += $tmp['memberOverlapDcPrice'];
                }
                // 회원 추가 마일리지 적립 적용 제외
                if (in_array('mileage', $exceptBenefit) === true && $exceptBenefitFl === true) {} else {
                    $data['memberMileage'] += $this->getMemberMileageData($this->_memInfo, $price);
                }
            }
            unset($tmpGoodsPrice);
        }
			
        // 추가할인 / 중복할인 /추가 마일리지 적립 재계산 (기준이 상품별일 경우)
        if ($getData['memberBenefitExcept'] == 'n' && $memberDcFl === true) {

            // 상품의 단가, 합계금액 계산
            $tmp = $this->getUnitGoodsPriceData($this->_memInfo, $unitPrice);
            $tmpPrice = $tmp['tmpPrice'];

            if (in_array($this->_memInfo['fixedOrderTypeDc'], ['goods', 'order', 'brand']) === true) {
                if (in_array('add', $exceptBenefit) === true && $exceptBenefitFl === true) {} else {
                    //회원등급 > 브랜드별 추가할인 상품 브랜드 정보
                    if ($this->_memInfo['fixedOrderTypeDc'] == 'brand') {
                        if (in_array($getData['brandCd'], $this->_memInfo['dcBrandInfo']->cateCd)) {
                            $goodsBrandInfo[$getData['goodsNo']][$getData['brandCd']] = $getData['brandCd'];
                        } else {
                            if ($getData['brandCd']) {
                                $goodsBrandInfo[$getData['goodsNo']]['allBrand'] = $getData['brandCd'];
                            } else {
                                $goodsBrandInfo[$getData['goodsNo']]['noBrand'] = $getData['brandCd'];
                            }
                        }
                    }
                    $addDcPrice = $this->getMemberGoodsAddDcPriceData($tmpPrice, [], $getData['goodsNo'], $this->_memInfo, $unitPrice, $arrCateCd, $getData['addDcFl'], [], $goodsBrandInfo);


                    if ($addDcPrice['addDcFl'] === true) {
                        $data['tmpMemberDcPrice'] = ($addDcPrice['memberDcPrice'] != 0) ? $addDcPrice['memberDcPrice'] : $data['memberDcPrice'];
                    }
                }
            }
            if (in_array($this->_memInfo['fixedOrderTypeOverlapDc'], ['goods', 'order']) === true) {
                if (in_array('overlap', $exceptBenefit) === true && $exceptBenefitFl === true) {} else {
                    $overlapDcPrice = $this->getMemberGoodsOverlapDcPriceData($tmpPrice, [], $getData['goodsNo'], $this->_memInfo, $unitPrice, $arrCateCd, $getData['overlapDcFl']);

                    if ($overlapDcPrice['overlapDcFl'] === true) {
                        $data['tmpMemberOverlapDcPrice'] = $overlapDcPrice['memberOverlapDcPrice'];
                    }
                }
            }
            $data['memberDcPrice'] = $data['tmpMemberDcPrice'] + $data['tmpMemberOverlapDcPrice'];

            if (in_array($this->_memInfo['fixedOrderTypeMileage'], ['goods', 'order']) === true) {
                if (in_array('mileage', $exceptBenefit) === true && $exceptBenefitFl === true) {} else {
                    $memberMileage = $this->getMemberGoodsMileageData($tmpPrice, [], $this->_memInfo);
                    $data['memberMileage'] = $memberMileage['memberMileage'];
                }
            }
        }

        //상품 마일리지
        if ($getData['goodsMileageExcept'] == 'n') {
            $goodsCnt = array_sum($getData['goodsCnt']);
            $price['goodsPriceSum'] = array_sum($getData['goodsPriceSum']);
            $price['optionPriceSum'] = array_sum($getData['optionPriceSum']);
            $price['optionTextPriceSum'] = array_sum($getData['optionTextPriceSum']);
            $price['addGoodsPriceSum'] = array_sum($getData['addGoodsPriceSum']);
            $price['goodsDcPrice'] = $data['goodsDcPrice'];
            $price['memberDcPrice'] = $data['memberDcPrice']; // 상품 상세는 회원 할인(추가/중복)이 같이 합산되어 있음.
            $price['couponDcPrice'] = $data['couponDcPrice'];

            // 마이앱 사용에 따른 분기 처리
            if ($this->useMyapp) {
                $price['myappDcPrice'] = $data['myappDcPrice'];
            }

            $data['goodsMileage'] += $this->getGoodsMileageData($getData['mileageFl'], $getData['mileageGoods'], $getData['mileageGoodsUnit'], $goodsCnt, $price, $goodsData['mileageGroup'], $goodsData['mileageGroupInfo'], $goodsData['mileageGroupMemberInfo']);
        }

        $data['totalDcPrice'] = $data['goodsDcPrice'] + $data['memberDcPrice'];

        // 마이앱 사용에 따른 분기 처리
        if ($this->useMyapp) {
            $data['totalDcPrice'] += $data['myappDcPrice'];
        }

        if ($data['couponDcPrice'] > 0 && $getData['set_total_price'] - $data['totalDcPrice'] < $data['couponDcPrice']) {
            $data['couponDcPrice'] = $getData['set_total_price'] - $data['totalDcPrice'];
        }

        $data['totalDcPrice'] += $data['couponDcPrice'];

        if($getData['set_total_price'] - $data['totalDcPrice'] < 0) {
            $data['totalDcPrice'] = $getData['set_total_price'];
        }
        $data['totalMileage'] = $data['goodsMileage'] + $data['memberMileage'] + $data['couponMileage'];

        $data['couponBenefitExcept'] = $getData['couponBenefitExcept'];

        return $data;
    }
	
	
	/**
     * 장바구니 상품 정보 - 상품별 상품 할인 설정
     * 상품금액의 총합이 아닌 순수 상품판매 단가의 할인율을 먼저 구한 뒤 반환할때 상품수량 곱함
     *
     * @param string $goodsDiscountFl 상품 할인 여부
     * @param int $goodsDiscount 상품 할인 금액 or Percent
     * @param string $goodsDiscountUnit Percent or Price
     * @param int $goodsCnt 상품 수량
     * @param array $goodsPrice 상품 가격 정보
     * @param string $fixedGoodsDiscount 상품할인금액기준
     * @param string $goodsDiscountGroup 상품할인대상
     * @param json $goodsDiscountGroupMemberInfo 상품할인 회원 정보
     *
     * @return int 상품할인금액
     */
    protected function getGoodsDcData($goodsDiscountFl, $goodsDiscount, $goodsDiscountUnit, $goodsCnt, $goodsPrice, $fixedGoodsDiscount = null, $goodsDiscountGroup = null, $goodsDiscountGroupMemberInfo = null)
    {
        // 상품 할인 금액
        $goodsDcPrice = $goodsPriceTmp = 0;
        $fixedGoodsDiscountData = explode(STR_DIVISION, $fixedGoodsDiscount);
        $goodsPriceTmp = $goodsPrice['goodsPriceSum'];

        if (in_array('option', $fixedGoodsDiscountData) === true) $goodsPriceTmp +=$goodsPrice['optionPriceSum'];
        if (in_array('text', $fixedGoodsDiscountData) === true) $goodsPriceTmp +=$goodsPrice['optionTextPriceSum'];
		if ($goodsPrice['addGoodsPriceSum']) $goodsPriceTmp += $goodsPrice['addGoodsPriceSum']; // 추가 상품 할인 금액 반영 
		
        // 상품금액 단가 계산
        $goodsPrice['goodsPrice'] = ($goodsPriceTmp / $goodsCnt);

        // 상품 할인 기준 금액 처리
        $tmp['discountByPrice'] = $goodsPrice['goodsPrice'];
		
        // 절사 내용
        $tmp['trunc'] = Globals::get('gTrunc.goods');
        // 상품 할인을 사용하는 경우 상품 할인 계산
        if ($goodsDiscountFl === 'y') {
            switch ($goodsDiscountGroup) {
                case 'group':
                    $goodsDiscountGroupMemberInfoData = json_decode($goodsDiscountGroupMemberInfo, true);
                    $discountKey = array_flip($goodsDiscountGroupMemberInfoData['groupSno'])[$this->_memInfo['groupSno']];

                    if ($discountKey >= 0) {
                        if ($goodsDiscountGroupMemberInfoData['goodsDiscountUnit'][$discountKey] === 'percent') {
                            $discountPercent = $goodsDiscountGroupMemberInfoData['goodsDiscount'][$discountKey] / 100;

                            // 상품할인금액
                            $goodsDcPrice = gd_number_figure($tmp['discountByPrice'] * $discountPercent, $tmp['trunc']['unitPrecision'], $tmp['trunc']['unitRound']) * $goodsCnt;
                        } else {
                            // 상품금액보다 상품할인금액이 클 경우 상품금액으로 변경
                            if ($goodsDiscountGroupMemberInfoData['goodsDiscount'][$discountKey] > $goodsPrice['goodsPrice']) $goodsDiscountGroupMemberInfoData['goodsDiscount'][$discountKey] = $goodsPrice['goodsPrice'];
                            // 상품할인금액 (정액인 경우 해당 설정된 금액으로)
                            $goodsDcPrice = $goodsDiscountGroupMemberInfoData['goodsDiscount'][$discountKey] * $goodsCnt;
                        }
                    }
					
                    break;
                case 'member':
                default:
                    if ($goodsDiscountUnit === 'percent') {
                        $discountPercent = $goodsDiscount / 100;

                        // 상품할인금액
                        $goodsDcPrice = gd_number_figure($tmp['discountByPrice'] * $discountPercent, $tmp['trunc']['unitPrecision'], $tmp['trunc']['unitRound']) * $goodsCnt;
                    } else {
                        // 상품금액보다 상품할인금액이 클 경우 상품금액으로 변경
                        if ($goodsDiscount > $goodsPrice['goodsPrice']) $goodsDiscount = $goodsPrice['goodsPrice'];
                        // 상품할인금액 (정액인 경우 해당 설정된 금액으로)
                        $goodsDcPrice = $goodsDiscount * $goodsCnt;
                    }
                    if ($goodsDiscountGroup == 'member' && empty($this->members['memNo']) === true) {
                        $goodsDcPrice = 0;
                    }
                    break;
            }
        }
		
        return $goodsDcPrice;
    }
	
	/**
	* 비회원 장바구니 상품 합치기 
	*
	* @param Integer $memNo 회원번호
	*/
	public function setMergeGuestCart($memNo = null)
    {
		if (!$memNo)
			return;
		

		$siteKey = Session::get('siteKey');
		$param = [
			'memNo = ?',
		];
		
		$bind = [
			'is',
			$memNo,
			$siteKey, 
		];
		
		$this->db->set_update_db("wm_gift", $param, "siteKey = ? AND memNo = 0", $bind);
	}
	
	/**
     * 장바구니 상품 쿠폰 적용
     *
     * @param integer $cartSno 장바구니 sno
     * @param string $memberCouponNo 회원쿠폰 고유번호(INT_DIVISION 으로 구분된 회원쿠폰고유번호)
     * @throws Exception 다른 상품에 적용된 쿠폰
     *
     * @author su
     */
    public function setMemberCouponApply($cartSno, $memberCouponNo)
    {
        // 장바구니에 적용된 쿠폰 초기화
        $this->setMemberCouponDelete($cartSno);

        // 쿠폰 모듈
        $coupon = \App::load('\\Component\\Coupon\\Coupon');

        if ($memberCouponNo) {
            // 적용가능 쿠폰인지 확인 후 쿠폰의 상태를 변경
            $memberCouponUsable = $coupon->getMemberCouponUsableCheck($memberCouponNo);
            if ($memberCouponUsable) {
                $coupon->setMemberCouponState($memberCouponNo, 'gift', false, $cartSno);
            } else {
                throw new AlertBackException(__('사용 할 수 없는 쿠폰 입니다.'));
            }
        }
        $arrBind = [
            'si',
            $memberCouponNo,
            $cartSno,
        ];
        $this->db->set_update_db($this->tableName, 'memberCouponNo = ?', 'sno = ?', $arrBind);
    }
	
	/**
     * 장바구니 담기 (단품)
     * 한개의 상품을 장바구니에 담습니다.
     *
     * @param array $arrData 상품 정보 [goodsNo, optionSno, goodsCnt, addGoodsNo, addGoodsCnt, optionText,
     *                       deliveryCollectFl, memberCouponNo, scmNo, cartMode]
     *
     * @return integer 중복상품 sno
     * @throws Exception
     */
	public function saveGoodsToCart($arrData)
    {
		$arrData['cartType'] = 'gift';
		return parent::saveGoodsToCart($arrData);
	}
	
	
	/**
	*
	* 선물하기 장바구니로 이동
	*
	*
	*
	*/
	public function saveGiftInfo($arrData)
	{

		foreach($arrData['data'] as $key => $value){	
			$sql ="SELECT * FROM es_cart WHERE sno = '{$value['cartSno']}' ";
			$cartData = $this->db->fetch($sql);
			$sql ="SELECT sno, tmpCartSno FROM wm_gift WHERE tmpCartSno='{$value['cartSno']}'";
			$esCartSno = $this->db->fetch($sql);
			
			if($esCartSno['tmpCartSno']){
				$sql ="UPDATE wm_gift SET modDt = '{$cartData['modDt']}' WHERE tmpCartSno='{$esCartSno['tmpCartSno']}'";
				$this->db->fetch($sql);
				$sno[] = $esCartSno['sno'];
			}else{
				$arrBind = $this->db->get_binding(DBTableField::tableCart(), $cartData , 'insert');
				$arrBind['bind'][3] = 'y';
				$arrBind['bind'][17] = 'gift';
				$arrBind['bind'][18] = $value['cartSno']; // 일반 장바구니 sno를 선물하기 장바구니에 넣기

				$this->db->set_insert_db($this->tableName, $arrBind['param'] , $arrBind['bind'] , 'y');
				$sno[] = $this->db->insert_id();
			}
	
		}
		
		return $sno;
	}
	
	/*
	* 선물하기 장바구니 sno 가져오기
	*
	*
	*/
	public function getCartSno($cartSno)
	{
		foreach($cartSno as $key => $value){
			$this->db->strField = "sno";
			$this->db->strWhere = "tmpCartSno ='{$value}'";
			$query = $this->db->query_complete();
			$sql ="SELECT".array_shift($query)."FROM wm_gift".implode(' ',$query);
			$list= $this->db->fetch($sql);
			$cartSno[$key] = $list['sno'];

			//장바구니 삭제
			$sql ="DELETE FROM es_cart WHERE sno='{$value}'";
			$this->db->fetch($sql);
		}
		
		return $cartSno;
	}

	/**
	 * 장바구니 비우기 처리 오버라이딩
	 * 
	 * @param string $orderNo 주문번호
	 * @return void
	 * @author Conan Kim <kmakugo@gmail.com>
	 */
	public function setCartRemove($orderNo = null)
	{
		if ($orderNo !== null) {
            $this->directCopyCartLog($orderNo);
		}

		parent::setCartRemove($orderNo);
	}

	/**
	 * 직접 쿼리 복사로 장바구니 로그 저장
	 * 
	 * @param string $orderNo 주문번호
	 * @return void
	 * @author Conan Kim <kmakugo@gmail.com>
	 */
	private function directCopyCartLog($orderNo)
	{
		$arrBind = [];

		$strSQL = "
			INSERT INTO ms_gift_log (
				orderNo, giftSno,
				addGoodsNo, addGoodsCnt, addGoodsPrices, componentGoodsNo,
				optionText, memberCouponNo, useBundleGoods, cartType, firstDelivery,
				logDt
			)
			SELECT
				tmpOrderNo as orderNo,
				sno as giftSno,
				addGoodsNo,
				addGoodsCnt,
				addGoodsPrices,
				componentGoodsNo,
				optionText, 
				memberCouponNo, 
				useBundleGoods, 
				cartType, 
				firstDelivery,
				NOW() as logDt
			FROM wm_gift
			WHERE tmpOrderNo = ?;
		";

		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$this->db->bind_query($strSQL, $arrBind);
		$this->db->query($strSQL, $arrBind);
	}

}