<?php
namespace Controller\Front\Subscription;

use App;
use Request;

class CartPsController extends \Controller\Front\Controller 
{
    public function index()
    {
        $get = Request::get()->toArray();
        $post = Request::post()->toArray();
        
        $obj = App::load(\Component\Subscription\Cart::class);
        $in = array_merge($get, $post);
        $db = App::load('DB');
        if ($in['cartMode'])
            $in['mode'] = $in['cartMode'];
        
         switch ($in['mode']) {
             case "cartIn" : 
                echo $obj->saveInfoCart($in);
                break;
             case "cartUpdate" : 
                if ($obj->updateInfoCart($in))
                     return $this->js("parent.location.reload();");
                
                return $this->js("alert('적용/변경에 실패하였습니다.');");
                break;
             case "couponApply" : 
                if ($in['cart']['cartSno']) {
                    $couponApplyNo = $db->escape($in['cart']['couponApplyNo']);
                    $idx = $db->escape($in['cart']['cartSno']);
                    $sql = "UPDATE wm_subscription_cart SET memberCouponNo='{$couponApplyNo}' WHERE idx='{$idx}'";
                    if ($db->query($sql))
                        return $this->js("parent.location.reload();");
                    
                    return $this->js("alert('적용/변경에 실패하였습니다.');");
                }
                break;
             case "cartSelectCalculation" : 
                try { 
                    if ($in['cartSno']) {
                        $obj->getCartList($in['cartSno']);
                         $setData = [
                            'cartCnt' => $obj->cartCnt,
                            'totalGoodsPrice' => $obj->totalGoodsPrice,
                            'totalGoodsDcPrice' => $obj->totalGoodsDcPrice,
                            'totalGoodsMileage' => $obj->totalGoodsMileage,
                            'totalMemberDcPrice' => $obj->totalSumMemberDcPrice,
                            'totalMemberOverlapDcPrice' => $obj->totalMemberOverlapDcPrice,
                            'totalMemberMileage' => $obj->totalMemberMileage,
                            'totalCouponGoodsDcPrice' => $obj->totalCouponGoodsDcPrice,
                            'totalCouponGoodsMileage' => $obj->totalCouponGoodsMileage,
                            'totalDeliveryCharge' => $obj->totalDeliveryCharge,
                            'totalSettlePrice' => $obj->totalSettlePrice,
                            'totalMileage' => $obj->totalMileage,
                            'totalMemberBankDcPrice' => $obj->totalMemberBankDcPrice,
                        ];
                    } else {
                        $setData = [
                            'cartCnt' => 0,
                            'totalGoodsPrice' => 0,
                            'totalGoodsDcPrice' => 0,
                            'totalGoodsMileage' => 0,
                            'totalMemberDcPrice' => 0,
                            'totalMemberOverlapDcPrice' => 0,
                            'totalMemberMileage' => 0,
                            'totalCouponGoodsDcPrice' => 0,
                            'totalCouponGoodsMileage' => 0,
                            'totalDeliveryCharge' => 0,
                            'totalSettlePrice' => 0,
                            'totalMileage' => 0,
                            'totalMemberBankDcPrice' => 0,
                        ];
                    }
                    
                    $this->json($setData);
                    exit;
                } catch (Exception $e) {
                    $this->json($e->getMessage());
                    exit;
                }
                break;
              case 'set_mileage' :
                $memInfo = $this->getData('gMemberInfo');

                $obj->getCartList($postValue['cartSno'], null, $postValue);

                // 마일리지 정책
                // '마일리지 정책에 따른 주문시 사용가능한 범위 제한' 에 사용되는 기준금액 셋팅
                $setMileagePriceArr = [
                    'totalDeliveryCharge' => $postValue['totalDeliveryCharge'] + $postValue['deliveryAreaCharge'],
                    'totalGoodsDeliveryAreaPrice' => $postValue['deliveryAreaCharge'],
                    'totalCouponOrderDcPrice' => gd_isset($postValue['totalCouponOrderDcPrice'], 0),
                ];
                $mileagePrice = $obj->setMileageUseLimitPrice($setMileagePriceArr);
                // 마일리지 정책에 따른 주문시 사용가능한 범위 제한
                $mileageUse = $obj->getMileageUseLimit(gd_isset($memInfo['mileage'], 0), $mileagePrice);

                $this->json([
                    'mileageUse' => $mileageUse,
                ]);
                exit;
                break;
             // 지역별 배송비 계산하기 (바로구매시 바로구매 쿠키가 사라지는 증상으로 인해 order_ps에서 이동 처리)
            case 'check_area_delivery':
                try {
                    // 장바구니내 지역별 배송비 처리를 위한 주소 값
                    $address = str_replace(' ', '', Request::post()->get('receiverAddress') . Request::post()->get('receiverAddressSub'));

                    // 장바구니 정보 (해당 프로퍼티를 가장 먼저 실행해야 계산된 금액 사용 가능)
                    $obj->getCartList($postValue['cartSno'], $address);

                    // 주문서 작성시 발생되는 금액을 장바구니 프로퍼티로 설정하고 최종 settlePrice를 산출 (사용 마일리지/예치금/주문쿠폰)
                    $orderPrice = $obj->setOrderSettlePayCalculation($postValue);

                    $mileageUse = [];
                    $memInfo = $this->getData('gMemberInfo');
                    if(count($memInfo) > 0){
                        // 마일리지 정책
                        // '마일리지 정책에 따른 주문시 사용가능한 범위 제한' 에 사용되는 기준금액 셋팅
                        $setMileagePriceArr = [
                            'totalCouponOrderDcPrice' => gd_isset($postValue['totalCouponOrderDcPrice'], 0),
                        ];
                        $mileagePrice = $obj->setMileageUseLimitPrice($setMileagePriceArr);
                        // 마일리지 정책에 따른 주문시 사용가능한 범위 제한
                        $mileageUse = $obj->getMileageUseLimit(gd_isset($memInfo['mileage'], 0), $mileagePrice);
                    }

                    $this->json([
                        'areaDelivery' => array_sum($orderPrice['totalGoodsDeliveryAreaCharge']),
                        'mileageUse' => $mileageUse,
                    ]);

                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }
                break;

            // 지역별 배송비 계산하기 (바로구매시 바로구매 쿠키가 사라지는 증상으로 인해 order_ps에서 이동 처리)
            case 'check_country_delivery':
                try {
                    // 국가코드가 잘못된 경우 배송비 0원 처리
                    if (v::countryCode()->validate($postValue['countryCode']) === false) {
                        $this->json([
                            'overseasDelivery' => 0,
                            'overseasInsuranceFee' => 0,
                        ]);
                    }

                    // 장바구니 정보 (해당 프로퍼티를 가장 먼저 실행해야 계산된 금액 사용 가능)
                    $obj->getCartList($postValue['cartSno'], $postValue['countryCode']);

                    // 주문서 작성시 발생되는 금액을 장바구니 프로퍼티로 설정하고 최종 settlePrice를 산출 (사용 마일리지/예치금/주문쿠폰)
                    $orderPrice = $obj->setOrderSettlePayCalculation($postValue);

                    $this->json([
                        'overseasDelivery' => $orderPrice['totalDeliveryCharge'],
                        'overseasInsuranceFee' => $orderPrice['totalDeliveryInsuranceFee'],
                    ]);

                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }
                exit();
                break;

            case 'multi_shipping_delivery':
                try {
                    $selectGoods = json_decode($postValue['selectGoods'], true);

                    $cartIdx = $setGoodsCnt = $setAddGoodsCnt = [];
                    foreach ($selectGoods as $key => $val) {
                        if ($val['goodsCnt'] > 0) {
                            $cartIdx[] = $val['sno'];
                            $setGoodsCnt[$val['sno']]['goodsCnt'] = $val['goodsCnt'];
                        }
                        if (empty($val['addGoodsNo']) === false) {
                            foreach ($val['addGoodsNo'] as $aKey => $aVal) {
                                $setAddGoodsCnt[$val['sno']][$aVal] = $val['addGoodsCnt'][$aKey];
                            }
                        }
                    }

                    $obj->getCartList($cartIdx, $postValue['address'], $postValue);

                    $this->json([
                        'deliveryCharge' => array_sum($obj->totalGoodsDeliveryPolicyCharge),
                        'deliveryAreaPrice' => array_sum($obj->totalGoodsDeliveryAreaPrice),
                    ]);
                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }
                exit();
                break;

             case 'check_multi_area_delivery':
                // 회원 정보
                $memInfo = $this->getData('gMemberInfo');
                $tmpMileagePrice = [];

                parse_str($postValue['data'], $data);
                $addressHead = 'receiver';
                if (empty($data['tmpDeliverTab']) === false) {
                    $addressHead = $data['tmpDeliverTab'];
                }
                $areaDelivery = $policyCharge = 0;
                foreach ($data['selectGoods'] as $key => $val) {
                    if (empty($val) === false) {
                        $setData = [];
                        $cartIdx = $setGoodsCnt = $setAddGoodsCnt = [];
                        $selectGoods = json_decode($val, true);


                        foreach ($selectGoods as $tKey => $tVal) {
                            if ($tVal['goodsCnt'] > 0) {
                                $cartIdx[] = $tVal['sno'];
                                $setGoodsCnt[$tVal['sno']] = $tVal['goodsCnt'];
                                $setData[$tVal['scmNo']][$tVal['deliverySno']][$tKey] = [
                                    'goodsNo' => $tVal['goodsNo'],
                                    'goodsCnt' => $tVal['goodsCnt'],

                                ];
                            }
                            if (empty($tVal['addGoodsNo']) === false) {
                                foreach ($tVal['addGoodsNo'] as $aKey => $aVal) {
                                    $setAddGoodsCnt[$tVal['sno']][$aVal] = $tVal['addGoodsCnt'][$aKey];
                                }
                            }
                        }

                        if ($key == 0) {
                            $address = str_replace(' ', '', $data[$addressHead . 'Address'] . $data[$addressHead . 'AddressSub']);
                        } else {
                            $address = str_replace(' ', '', gd_isset($data['receiverAddressAdd'][$key], $data['shippingAddressAdd'][$key]) . gd_isset($data['receiverAddressSubAdd'][$key], $data['shippingAddressSubAdd'][$key]));
                        }

                        $obj->getCartList($cartIdx, $address, $postValue);

                        $policyCharge += array_sum($obj->totalGoodsDeliveryPolicyCharge);
                        $areaDelivery += array_sum($obj->totalGoodsDeliveryAreaPrice);
                        unset($obj->totalGoodsDeliveryAreaPrice);
                        unset($obj->totalPrice, $obj->totalGoodsDcPrice, $obj->totalMemberDcPrice, $obj->totalMemberOverlapDcPrice, $obj->totalCouponGoodsDcPrice, $obj->totalDeliveryCharge, $obj->totalGoodsDeliveryPolicyCharge);
                    }
                }

                $mileageUse = [];
                if(count($memInfo) > 0) {
                    $obj->getCartList($data['cartSno'], null, $postValue);

                    // 마일리지 정책
                    // '마일리지 정책에 따른 주문시 사용가능한 범위 제한' 에 사용되는 기준금액 셋팅
                    $setMileagePriceArr = [
                        'totalDeliveryCharge' => $policyCharge + $areaDelivery,
                        'totalGoodsDeliveryAreaPrice' => $areaDelivery,
                    ];
                    $mileagePrice = $obj->setMileageUseLimitPrice($setMileagePriceArr);
                    // 마일리지 정책에 따른 주문시 사용가능한 범위 제한
                    $mileageUse = $obj->getMileageUseLimit(gd_isset($memInfo['mileage'], 0), $mileagePrice);
                }

                $this->json([
                    'areaDelivery' => $areaDelivery,
                    'maximumLimit' => gd_isset($mileageUse['maximumLimit'], 0), //레거시보존
                    'mileageUse' => $mileageUse,
                ]);
                exit();
                break;
             // 상품 쿠폰 주문 적용 / 변경 / 삭제
            case 'goodsCouponOrderApply':
                try {
                    if($postValue['cartIdx']) {
                        // 상품적용 쿠폰 제거
                        sort($postValue['cartIdx']);
                        foreach ($postValue['cartIdx'] as $delKey => $delVal) {
                            if (array_key_exists($delVal, $postValue['cart']) == false) {
                                $obj->setMemberCouponDelete($delVal);
                            }
                        }
                    }
                    if($postValue['cart']) {
                        // 상품적용 쿠폰 적용 / 변경
                        foreach ($postValue['cart'] as $cartKey => $cartApplyData) {
                            if ($cartApplyData) {
                                $obj->setMemberCouponApply($cartKey, $cartApplyData);
                            }
                        }
                    }

                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }
                break;

         }
        exit;
    }
}