<?php
namespace Controller\Mobile\Subscription;

use App;
use Component\GoodsStatistics\GoodsStatistics;
use Component\Bankda\BankdaOrder;
use Component\Subscription\Cart;
use Component\Member\Member;
use Component\Order\Order;
use Component\Order\OrderMultiShipping;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertBackException;
use Globals;
use Request;

class OrderPsController extends \Controller\Mobile\Controller 
{
    /**
     * {@inheritdoc}
     *
     * @throws AlertOnlyException
     * @throws AlertRedirectException
     * @author Jong-tae Ahn <qnibus@godo.co.kr>
     */
    public function index()
    {
        // POST 데이터 수신
        $postValue = Request::post()->toArray();

        // 모듈 설정
        $cart = App::load(\Component\Subscription\Cart::class);
        $subObj = App::load(\Component\Subscription\Subscription::class);
        $order = App::load(\Component\Order\Order::class);
        $db = App::load('DB');
        switch ($postValue['mode']) {
            // 배송지 관리 리스트
            case 'shipping_list':
                try {
                    $deliveryAddress = $order->getShippingAddressList(1, 999);
                    $this->json($deliveryAddress);
                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }
                break;

            // 지역별 배송비 계산하기 (기존 모바일 food_story 스킨패치 하지 않는 이상 레거시때문에 남겨야 함)
            case 'check_area_delivery':
                try {
                    // 장바구니내 지역별 배송비 처리를 위한 주소 값
                    $address = str_replace(' ', '', $postValue['receiverAddress'] . $postValue['receiverAddressSub']);

                    // 장바구니 정보 (해당 프로퍼티를 가장 먼저 실행해야 계산된 금액 사용 가능)
                    $cart->getCartList($postValue['cartSno'], $address);

                    // 주문서 작성시 발생되는 금액을 장바구니 프로퍼티로 설정하고 최종 settlePrice를 산출 (사용 마일리지/예치금/주문쿠폰)
                    $orderPrice = $cart->setOrderSettleCalculation($postValue);

                    $mileageUse = [];
                    $memInfo = $this->getData('gMemberInfo');
                    if(count($memInfo) > 0){
                        // 마일리지 정책
                        // '마일리지 정책에 따른 주문시 사용가능한 범위 제한' 에 사용되는 기준금액 셋팅
                        $mileagePrice = $cart->setMileageUseLimitPrice();
                        // 마일리지 정책에 따른 주문시 사용가능한 범위 제한
                        $mileageUse = $cart->getMileageUseLimit(gd_isset($memInfo['mileage'], 0), $mileagePrice);
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

            // 주문쿠폰 사용시 회원추가/중복 할인 금액 / 마일리지 지급 재조정
            case 'set_recalculation':
                try {
                    $memInfo = $this->getData('gMemberInfo');

                    if (empty($postValue['cartIdx']) === false) {
                        $cartIdx = $postValue['cartIdx'];
                    }
                    $cart->totalCouponOrderDcPrice = $postValue['totalCouponOrderDcPrice'];
                    $cart->totalUseMileage = $postValue['useMileage'];
                    $cart->deliveryFree = $postValue['deliveryFree'];

                    $cartAddData = $cart->getCartList($cartIdx);

                    // 마일리지 정책
                    // '마일리지 정책에 따른 주문시 사용가능한 범위 제한' 에 사용되는 기준금액 셋팅
                    $setMileagePriceArr = [
                        'totalDeliveryCharge' => $postValue['totalDeliveryCharge'] + $postValue['deliveryAreaCharge'],
                        'totalGoodsDeliveryAreaPrice' => $postValue['deliveryAreaCharge'],
                    ];
                    $mileagePrice = $cart->setMileageUseLimitPrice($setMileagePriceArr);
                    // 마일리지 정책에 따른 주문시 사용가능한 범위 제한
                    $mileageUse = $cart->getMileageUseLimit(gd_isset($memInfo['mileage'], 0), $mileagePrice);

                    // 예치금 전액 결제시 회원등급 추가 무통장 할인 제거에 따른 상품적립 마일리지 재계산
                    if ($postValue['resetMemberBankDcPrice'] == 'y') {
                        foreach ($cartAddData as $sKey => $sVal) {
                            foreach ($sVal as $dKey => $dVal) {
                                foreach ($dVal as $gKey => $gVal) {
                                    $gVal['price']['memberDcPrice'] -= $gVal['price']['goodsMemberBankDcPrice'];
                                    $gVal['price']['goodsPriceSubtotal'] += $gVal['price']['goodsMemberBankDcPrice'];
                                    unset($gVal['price']['goodsMemberBankDcPrice']);
                                    $goodsMileage = $cart->getGoodsMileageDataCont($gVal['mileageFl'], $gVal['mileageGoods'], $gVal['mileageGoodsUnit'], $gVal['goodsCnt'], $gVal['price'], $gVal['mileageGroup'], $gVal['mileageGroupInfo'], $gVal['mileageGroupMemberInfo']);
                                    $totalMileage = $goodsMileage + $cart->totalMemberMileage + $cart->totalCouponGoodsMileage + $cart->totalCouponOrderMileage;
                                }
                            }
                        }
                    }

                    $setData = [
                        'cartCnt' => $cart->cartCnt,
                        'totalGoodsPrice' => $cart->totalGoodsPrice,
                        'totalGoodsDcPrice' => $cart->totalGoodsDcPrice,
                        'totalGoodsMileage' => ($postValue['resetMemberBankDcPrice'] == 'y') ? $goodsMileage : $cart->totalGoodsMileage,
                        'totalMemberDcPrice' => $cart->totalMemberDcPrice,
                        'totalMemberBankDcPrice' => $cart->totalMemberBankDcPrice,
                        'totalMemberOverlapDcPrice' => $cart->totalMemberOverlapDcPrice,
                        'totalMemberMileage' => $cart->totalMemberMileage,
                        'totalCouponGoodsDcPrice' => $cart->totalCouponGoodsDcPrice,
                        'totalCouponGoodsMileage' => $cart->totalCouponGoodsMileage,
                        'totalDeliveryCharge' => $cart->totalDeliveryCharge,
                        'totalSettlePrice' => $cart->totalSettlePrice,
                        'totalMileage' => ($postValue['resetMemberBankDcPrice'] == 'y') ? $totalMileage : $cart->totalMileage,
                        'mileageUse' => $mileageUse,
                    ];

                    $this->json($setData);
                    exit;
                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }

                break;

            // 배송지 정보
            case 'get_shipping_data':
                try {
                    $shippingData = $order->getShippingAddressData($postValue['sno']);
                    $this->json($shippingData);
                } catch (Exception $e) {
                    $this->json([
                        'error' => 1,
                        'message' => $e->getMessage(),
                    ]);
                }
                break;

            // 주문서 저장하기
            default:
                try {
                    $orderMultiShipping = new OrderMultiShipping();
                    $orderBasic = gd_policy('order.basic');

                    // 모바일 배송지 주소 정리 (모바일 주문페이지에서 넘어올 때 사용)
                    if (Request::post()->has('tmpDeliverTab')) {
                        switch (Request::post()->get('tmpDeliverTab')) {
                            // 배송지목록
                            case 'shipping':
                                $postValue['receiverName'] = $postValue['shippingName'];
                                $postValue['receiverZipcode'] = $postValue['shippingZipcode'];
                                $postValue['receiverZonecode'] = $postValue['shippingZonecode'];
                                $postValue['receiverCountryCode'] = $postValue['shippingCountryCode'];
                                $postValue['receiverCountry'] = $postValue['shippingCountry'];
                                $postValue['receiverState'] = $postValue['shippingState'];
                                $postValue['receiverCity'] = $postValue['shippingCity'];
                                $postValue['receiverAddress'] = $postValue['shippingAddress'];
                                $postValue['receiverAddressSub'] = $postValue['shippingAddressSub'];
                                $postValue['receiverCellPhonePrefix'] = $postValue['shippingCellPhonePrefix'];
                                $postValue['receiverCellPhonePrefixCode'] = $postValue['shippingCellPhonePrefixCode'];
                                $postValue['receiverPhone'] = $postValue['shippingPhone'];
                                $postValue['receiverCellPhonePrefix'] = $postValue['shippingCellPhonePrefix'];
                                $postValue['receiverCellPhonePrefixCode'] = $postValue['shippingCellPhonePrefixCode'];
                                $postValue['receiverCellPhone'] = $postValue['shippingCellPhone'];
                                if ($orderBasic['useSafeNumberFl'] == 'y') {
                                    $postValue['receiverUseSafeNumberFl'] = $postValue['shippingUseSafeNumberFl'];
                                }
                                break;

                            // 직접입력
                            case 'direct':
                                $postValue['receiverName'] = $postValue['directName'];
                                $postValue['receiverZipcode'] = $postValue['directZipcode'];
                                $postValue['receiverZonecode'] = $postValue['directZonecode'];
                                $postValue['receiverCountryCode'] = $postValue['directCountryCode'];
                                $postValue['receiverCountry'] = $postValue['directCountry'];
                                $postValue['receiverState'] = $postValue['directState'];
                                $postValue['receiverCity'] = $postValue['directCity'];
                                $postValue['receiverAddress'] = $postValue['directAddress'];
                                $postValue['receiverAddressSub'] = $postValue['directAddressSub'];
                                $postValue['receiverPhonePrefix'] = $postValue['directPhonePrefix'];
                                $postValue['receiverPhonePrefixCode'] = $postValue['directPhonePrefixCode'];
                                $postValue['receiverPhone'] = $postValue['directPhone'];
                                $postValue['receiverCellPhonePrefix'] = $postValue['directCellPhonePrefix'];
                                $postValue['receiverCellPhonePrefixCode'] = $postValue['directCellPhonePrefixCode'];
                                $postValue['receiverCellPhone'] = $postValue['directCellPhone'];
                                if ($orderBasic['useSafeNumberFl'] == 'y') {
                                    $postValue['receiverUseSafeNumberFl'] = $postValue['directUseSafeNumberFl'];
                                }
                                break;
                        }
                    }

                    // 주문서 정보 체크
                    $postValue = $order->setOrderDataValidation($postValue, true);

                    // 결제수단이 없는 경우 PG창이 열리기 때문에 강제로 무통장으로 처리
                    if (empty($postValue['settleKind']) === true) {
                        $postValue['settleKind'] = 'pc';
                    }

                    if (\Session::has('trackingKey') === true) {
                        $postValue['trackingKey'] = \Session::get('trackingKey');
                    }

                    // 배송비 산출을 위한 주소 및 국가 선택
                    if (Globals::get('gGlobal.isFront')) {
                        // 주문서 작성페이지에서 선택된 국가코드
                        $address = $postValue['receiverCountryCode'];
                    } else {
                        // 장바구니내 해외/지역별 배송비 처리를 위한 주소 값
                        $address = str_replace(' ', '', $postValue['receiverAddress'] . $postValue['receiverAddressSub']);
                    }

                    $cart->totalCouponOrderDcPrice = $postValue['totalCouponOrderDcPrice'];
                    $cart->totalUseMileage = $postValue['useMileage'];
                    $cart->deliveryFree = $postValue['deliveryFree'];
                    $cart->couponApplyOrderNo = $postValue['couponApplyOrderNo'];

                    try {
                        $db->begin_tran();
                        if ($orderMultiShipping->isUseMultiShipping() === true && $postValue['multiShippingFl'] == 'y') {
                            $resetCart = $orderMultiShipping->resetCart($postValue);
                            $postValue['cartSno'] = $resetCart['setCartSno'];
                            $postValue['orderInfoCdData'] = $resetCart['orderInfoCd'];
                            $postValue['orderInfoCdBySno'] = $resetCart['orderInfoCdBySno'];
                            $cart->goodsCouponInfo = $resetCart['goodscouponInfo'];

                            $multiShippingField = ['shippingCellPhonePrefixAdd', 'shippingPhonePrefixAdd', 'shippingSnoAdd', 'shippingNameAdd', 'shippingCountryCodeAdd', 'shippingCityAdd', 'shippingStateAdd', 'shippingAddressAdd', 'shippingAddressSubAdd', 'shippingZonecodeAdd', 'shippingZipcodeAdd', 'shippingAddressAdd', 'shippingAddressSubAdd', 'shippingPhonePrefixCodeAdd', 'shippingPhoneAdd', 'shippingCellPhonePrefixCodeAdd', 'shippingCellPhoneAdd', ];

                            if ($orderBasic['useSafeNumberFl'] == 'y') {
                                $multiShippingField[] = 'shippingUseSafeNumberFlAdd';
                            }

                            foreach ($multiShippingField as $field) {
                                $replaceField = str_replace('shipping', 'receiver', $field);

                                $postValue[$replaceField] = $postValue[$field];
                            }
                        }

                        // 장바구니 정보 (해당 프로퍼티를 가장 먼저 실행해야 계산된 금액 사용 가능)
                        $cartInfo = $cart->getCartList($postValue['cartSno'], $address, $postValue);
                        $postValue['multiShippingOrderInfo'] = $cart->multiShippingOrderInfo;

                        // ===== 정기배송 상품의 firstDelivery 자동 계산 =====
                        $subObj->calculateFirstDeliveryForCart($cartInfo);

                        // 예치금 전액 결제시 회원등급 추가 무통장 할인 제거에 따른 상품적립 마일리지 재계산
                        if ($postValue['resetMemberBankDcPrice'] == 'y') {
                            foreach ($cartInfo as $sKey => $sVal) {
                                foreach ($sVal as $dKey => $dVal) {
                                    foreach ($dVal as $gKey => $gVal) {
                                        $cartInfo[$sKey][$dKey][$gKey]['price']['memberDcPrice'] -= $gVal['price']['goodsMemberBankDcPrice'];
                                        $cartInfo[$sKey][$dKey][$gKey]['price']['goodsPriceSubtotal'] += $gVal['price']['goodsMemberBankDcPrice'];
                                        unset($cartInfo[$sKey][$dKey][$gKey]['price']['goodsMemberBankDcPrice']);
                                        $cartInfo[$sKey][$dKey][$gKey]['mileage']['goodsMileage'] = $cart->getGoodsMileageDataCont($cartInfo[$sKey][$dKey][$gKey]['mileageFl'], $cartInfo[$sKey][$dKey][$gKey]['mileageGoods'], $cartInfo[$sKey][$dKey][$gKey]['mileageGoodsUnit'], $cartInfo[$sKey][$dKey][$gKey]['goodsCnt'], $cartInfo[$sKey][$dKey][$gKey]['price'], $cartInfo[$sKey][$dKey][$gKey]['mileageGroup'], $cartInfo[$sKey][$dKey][$gKey]['mileageGroupInfo'], $cartInfo[$sKey][$dKey][$gKey]['mileageGroupMemberInfo']);
                                    }
                                }
                            }
                        }

                        // 주문불가한 경우 진행 중지
                        if (!$cart->orderPossible) {
                            if(trim($cart->orderPossibleMessage) !== ''){
                                throw new AlertRedirectException(__($cart->orderPossibleMessage), null, null, '../subscription/cart.php', 'top');
                            }
                            else {
                                throw new AlertRedirectException(__('구매 불가 상품이 포함되어 있으니 장바구니에서 확인 후 다시 주문해주세요.'), null, null, '../subscription/cart.php', 'top');
                            }
                        }

                        // EMS 배송불가
                        if (!$cart->emsDeliveryPossible) {
                            throw new AlertRedirectException(__('무게가 %sg 이상의 상품은 구매할 수 없습니다. (배송범위 제한)', '30k'), null, null, '../subscription/cart.php', 'top');
                        }

                        // 개별결제수단이 설정되어 있는데 모두 다른경우 결제 불가
                        if (empty($cart->payLimit) === false && in_array('false', $cart->payLimit)) {
                            throw new AlertRedirectException(__('주문하시는 상품의 결제 수단이 상이 하여 결제가 불가능합니다.'), null, null, '../subscription/cart.php', 'top');
                        }

                        // 설정 변경등으로 쿠폰 할인가등이 변경된경우
                        if (!$cart->changePrice) {
                            throw new AlertRedirectException(__('할인/적립 금액이 변경되었습니다. 상품 결제 금액을 확인해 주세요!'), null, null, '../subscription/cart.php', 'top');
                        }

                        // 주문서 작성시 발생되는 금액을 장바구니 프로퍼티로 설정하고 최종 settlePrice를 산출 (사용 마일리지/예치금/주문쿠폰)
                        $orderPrice = $cart->setOrderSettleCalculation($postValue);

                        // 설정 변경등으로 쿠폰 할인가등이 변경된경우 - 주문쿠폰체크
                        if (!$cart->changePrice) {
                            throw new AlertRedirectException(__('할인/적립 금액이 변경되었습니다. 상품 결제 금액을 확인해 주세요!'), null, null, '../subscription/cart.php', 'top');
                        }

                        // 마일리지/예치금 전용 구매상품인 경우 찾아내기
                        if (empty($cart->payLimit) === false) {
                            $isOnlyMileage = true;
                            foreach ($cart->payLimit as $val) {
                                if (!in_array($val, [Order::SETTLE_KIND_MILEAGE, Order::SETTLE_KIND_DEPOSIT])) {
                                    $isOnlyMileage = false;
                                }
                            }

                            // 마일리지/예치금 결제 전용인 경우
                            if ($isOnlyMileage) {
                                // 예치금/마일리지 복합결제 구매상품인 경우 결제금액이 0원이 아닌 경우
                                if (in_array(Order::SETTLE_KIND_DEPOSIT, $cart->payLimit) && in_array(Order::SETTLE_KIND_MILEAGE, $cart->payLimit) && $orderPrice['settlePrice'] != 0) {
                                    throw new Exception(__('결제금액보다 예치금/마일리지 사용 금액이 부족합니다.'));
                                }

                                // 예치금 전용 구매상품이면서 결제금액이 0원이 아닌 경우
                                if (in_array(Order::SETTLE_KIND_DEPOSIT, $cart->payLimit) && $orderPrice['settlePrice'] != 0) {
                                    throw new Exception(__('결제금액보다 예치금이 부족합니다.'));
                                }

                                // 마일리지 전용 구매상품이면서 결제금액이 0원이 아닌 경우
                                if (in_array(Order::SETTLE_KIND_MILEAGE, $cart->payLimit) && $orderPrice['settlePrice'] != 0) {
                                    throw new Exception(__('결제금액보다 마일리지가 부족합니다.'));
                                }
                            }
                        }
                        $db->commit();

                    } catch (Exception $e) {
                        $db->rollback();
                        throw new Exception($e->getMessage());
                    }

                    // 결제금액이 0원인 경우 전액할인 수단으로 강제 변경 및 주문 채널을 shop 으로 고정
                    if ($orderPrice['settlePrice'] == 0) {
                        $postValue['settleKind'] = Order::SETTLE_KIND_ZERO;
                        $postValue['orderChannelFl'] = 'shop';
                    }

                    /*
                     * 주문정보 발송 시점을 트랜잭션 종료 후 진행하기 위한 로직 추가
                     */
                    $smsAuto = \App::load('Component\\Sms\\SmsAuto');
                    $smsAuto->setUseObserver(true);
                    $mailAuto = \App::load('Component\\Mail\\MailMimeAuto');
                    $mailAuto->setUseObserver(true);
                    // 주문 저장하기 (트랜젝션)
                    $result = \DB::transaction(function () use ($order, $cartInfo, $postValue, $orderPrice, $cart) {
                        // 장바구니에서 계산된 전체 과세 비율 필요하면 추후 사용 -> $cart->totalVatRate
                        return $order->saveOrderInfo($cartInfo, $postValue, $orderPrice);
                    });
                    

                    // 주문 저장 후 처리
                    if ($result) {
                        /* 정기결제 처리 START */
                       if (!$uid = $subObj->registerSubscription($postValue))
                            throw new AlertRedirectException(__('정기결제 신청에 실패하였습니다.'), null, null, '../subscription/cart.php', 'parent');

                      $stamp = strtotime(date("Ymd"));
                      if (!$idx = $subObj->updateScheduleOrder($uid, $stamp, $order->orderNo)) {
                          $subObj->rollbackSubscription($uid);
                          throw new AlertRedirectException(__('정기결제 신청에 실패하였습니다.'), null, null, '../subscription/cart.php', 'parent');
                      }
                      
                      if ($postValue['settleKind'] == 'pc') {
                          if (!$subObj->pay($idx)) { // 1회결제가 실패한 경우 정기결제 정보 롤백 
                              $subObj->rollbackSubscription($uid);
                              throw new AlertRedirectException(__('정기결제 신청에 실패하였습니다.'), null, null, '../subscription/cart.php', 'parent');
                          }
                      }
                      
                      /* 정기결제 장바구니 상품 삭제 */
                      $cart->cartDelete($postValue['cartSno']);
                      
					  $smsAuto->notify();
                      $mailAuto->notify();
					  
                        // 결제 완료 페이지 이동
                        throw new AlertRedirectException(null, null, null, '../order/order_end.php?orderNo=' . $order->orderNo, 'parent');
                        
                      /* 정기결제 처리 END */
                    }
                } catch (Exception $e) {
                    if (get_class($e) == Exception::class) {
                        throw new AlertOnlyException($e->getMessage(), null, null, "window.parent.changePaymentButton(1);");
                    } else {
                        throw $e;
                    }
                }
                break;
        }
    }
}