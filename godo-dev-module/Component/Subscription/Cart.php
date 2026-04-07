<?php
namespace Component\Subscription;

use App;
use Session;
use Request;
use Globals;
use Component\Database\DBTableField;
use Component\Goods\Goods;
use Framework\Utility\SkinUtils;


use Component\Naver\NaverPay;
use Component\Payment\Payco\Payco;
use Component\GoodsStatistics\GoodsStatistics;
use Component\Policy\Policy;
use Component\Delivery\EmsRate;
use Component\Delivery\OverseasDelivery;
use Component\Mall\Mall;
use Component\Mall\MallDAO;
use Component\Member\Util\MemberUtil;
use Component\Member\Group\Util;
use Component\Validator\Validator;
use Cookie;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\AlertRedirectCloseException;
use Framework\Debug\Exception\Except;
use Framework\Utility\ArrayUtils;
use Framework\Utility\NumberUtils;
use Framework\Utility\ProducerUtils;
use Framework\Utility\StringUtils;
use Component\Godo\GodoCenterServerApi;
use Bundle\Component\Payment\Tosspay\TosspayConfig;

class Cart  extends \Bundle\Component\Cart\Cart
{
    /* 정기결제 장바구니 저장 */
   public function saveInfoCart($data = [])
   {
       if (!gd_is_login())
           return false;
          
       $memNo = Session::get("member.memNo");
       $stamp = time();
       $goods = [];
       if ($data['goodsNo']) {
           $data['memberCouponNo'] = $data['couponApplyNo'];
           unset($data['couponApplyNo']);
           
          // 쿠폰이 적용 되면 쿠폰의 상태를 변경
          if ($data['memberCouponNo']) {
              // 쿠폰 모듈
              $coupon = \App::load('\\Component\\Coupon\\Coupon');
               $memberCouponUsable = $coupon->getMemberCouponUsableCheck($data['memberCouponNo']);
               if ($memberCouponUsable) {
                   $coupon->setMemberCouponState($data['memberCouponNo'], 'cart');
               } else {
                   return false;
               }
           }
           
           $deliveryCollectFl = gd_isset($data['deliveryCollectFl'], 'pre');
           $deliveryMethodFl = gd_isset($data['deliveryMethodFl'], 'delivery');
           
           $period = gd_isset($data['period'], "1_week");
           
            foreach ($data['goodsNo'] as $k => $goodsNo) {
                $optionSno = gd_isset($data['optionSno'][$k], 0);
                $addGoodsNo = $addGoodsCnt = $memberCouponNo = "";
                if ($data['addGoodsNo'][$k])
                    $addGoodsNo = json_encode($data['addGoodsNo'][$k]);
                
                if ($data['addGoodsCnt'][$k])
                    $addGoodsCnt = json_encode($data['addGoodsCnt'][$k]);
                
                $goodsCnt = gd_isset($data['goodsCnt'][$k], 1);
                
                $optionText = json_encode($data['optionText'][$k], JSON_UNESCAPED_UNICODE);
                
                if ($data['memberCouponNo'][$k])
                    $memberCouponNo = $data['memberCouponNo'][$k];
                
                $sql = "DELETE FROM wm_subscription_cart3 WHERE goodsNo='{$goodsNo}' AND optionSno='{$optionSno}' AND period='{$period}'";
                $this->db->query($sql);
                $sql = "INSERT INTO wm_subscription_cart3 
                                SET 
                                    memNo='{$memNo}',
                                    goodsNo='{$goodsNo}',
                                    period='{$period}',
                                    optionSno='{$optionSno}',
                                    goodsCnt='{$goodsCnt}',
                                    addGoodsNo='{$addGoodsNo}',
                                    addGoodsCnt='{$addGoodsCnt}',
                                    optionText='{$optionText}',
                                    deliveryCollectFl='{$deliveryCollectFl}',
                                    deliveryMethodFl='{$deliveryMethodFl}',
                                    memberCouponNo='{$memberCouponNo}',
                                    regStamp='{$stamp}'";
                if ($this->db->query($sql))
                   $goods[] = ['goodsNo' => $goodsNo, 'optionSno' => $optionSno];
             } // endforeach 
        } // endif 
        
        if ($goods) {
            return true;
        }
   }
   
   public function updateInfoCart($data = [])
   {
       if (!gd_is_login())
           return false;
       
       $server = \Request::server()->toArray();
       if ($data['cartSno']) {
           $deliveryCollectFl = $this->db->escape(gd_isset($data['deliveryCollectFl'], 'pre'));
           $deliveryMethodFl = $this->db->escape(gd_isset($data['deliveryMethodFl'], 'delivery'));
            $stamp = time();
            $memNo = Session::get("member.memNo");
            foreach ($data['goodsNo'] as $k => $goodsNo) {
                $optionSno = $this->db->escape(gd_isset($data['optionSno'][$k], 0));
                $addGoodsNo = $addGoodsCnt = $memberCouponNo = "";
                if ($data['addGoodsNo'][$k])
                    $addGoodsNo = $this->db->escape(json_encode($data['addGoodsNo'][$k]));
                
                if ($data['addGoodsCnt'][$k])
                    $addGoodsCnt = $this->db->escape(json_encode($data['addGoodsCnt'][$k]));
                
                $goodsCnt = $this->db->escape(gd_isset($data['goodsCnt'][$k], 1));
                
                $optionText = json_encode($data['optionText'][$k], JSON_UNESCAPED_UNICODE);
                $common = "       
                                        memNo='{$memNo}',
                                        goodsNo='{$goodsNo}',
                                        optionSno='{$optionSno}',
                                        goodsCnt='{$goodsCnt}',
                                        addGoodsNo='{$addGoodsNo}',
                                        addGoodsCnt='{$addGoodsCnt}',
                                        optionText='{$optionText}',
                                        deliveryCollectFl='{$deliveryCollectFl}',
                                        deliveryMethodFl='{$deliveryMethodFl}'";
                if ($k == 0) {                  
                    $sql = "UPDATE wm_subscription_cart3 
                                    SET 
                                        {$common},
                                        modStamp='{$stamp}'
                                 WHERE idx='{$data['cartSno']}'";
                } else {
                     $sql = "INSERT INTO wm_subscription_cart3 
                                SET 
                                    memNo='{$memNo}',
                                    {$common},
                                    regStamp='{$stamp}'";
                }
                
                return $this->db->query($sql);
            }
       }
   }


    public function getCartList($idx = null, $address = null, $postValue = [], $isTemp = false, $tmpMemNo = 0, $tmpDiscount = 0, $isAdmin = false)
    {
        $getCart = [];

        if (gd_is_login() || $tmpMemNo || $postValue['isGuest']) {
            $mallBySession = SESSION::get(SESSION_GLOBAL_MALL);

            // 절사 정책 가져오기
            $truncGoods = Globals::get('gTrunc.goods');

            $arrExclude['option'] = [
                'goodsNo',
                'optionNo',
            ];
            $arrExclude['addOptionName'] = [
                'goodsNo',
                'optionCd',
                'mustFl',
            ];
            $arrExclude['addOptionValue'] = [
                'goodsNo',
                'optionCd',
            ];
            $arrInclude['goods'] = [
                'goodsNm',
                'commission',
                'scmNo',
                'purchaseNo',
                'goodsCd',
                'cateCd',
                'goodsOpenDt',
                'goodsState',
                'imageStorage',
                'imagePath',
                'brandCd',
                'makerNm',
                'originNm',
                'goodsModelNo',
                'goodsPermission',
                'goodsPermissionGroup',
                'goodsPermissionPriceStringFl',
                'goodsPermissionPriceString',
                'onlyAdultFl',
                'onlyAdultImageFl',
                'goodsAccess',
                'goodsAccessGroup',
                'taxFreeFl',
                'taxPercent',
                'goodsWeight',
                'totalStock',
                'stockFl',
                'soldOutFl',
                'salesUnit',
                'minOrderCnt',
                'maxOrderCnt',
                'salesStartYmd',
                'salesEndYmd',
                'mileageFl',
                'mileageGoods',
                'mileageGoodsUnit',
                'goodsDiscountFl',
                'goodsDiscount',
                'goodsDiscountUnit',
                'payLimitFl',
                'payLimit',
                'goodsPriceString',
                'goodsPrice',
                'fixedPrice',
                'costPrice',
                'optionFl',
                'optionName',
                'optionTextFl',
                'addGoodsFl',
                'addGoods',
                'deliverySno',
                'delFl',
                'hscode',
                'goodsSellFl',
                'goodsSellMobileFl',
                'goodsDisplayFl',
                'goodsDisplayMobileFl',
                'mileageGroup',
                'mileageGroupInfo',
                'mileageGroupMemberInfo',
                'fixedGoodsDiscount',
                'goodsDiscountGroup',
                'goodsDiscountGroupMemberInfo',
                'exceptBenefit',
                'exceptBenefitGroup',
                'exceptBenefitGroupInfo',
                'fixedSales',
                'fixedOrderCnt',
                'goodsBenefitSetFl',
                'benefitUseType',
                'newGoodsRegFl',
                'newGoodsDate',
                'newGoodsDateFl',
                'periodDiscountStart',
                'periodDiscountEnd',
                'regDt',
                'modDt'
            ];
            $arrInclude['image'] = [
                'imageSize',
                'imageName',
            ];


            $arrFieldGoods = DBTableField::setTableField('tableGoods', $arrInclude['goods'], null, 'g');
            $arrFieldOption = DBTableField::setTableField('tableGoodsOption', null, $arrExclude['option'], 'go');
            $arrFieldImage = DBTableField::setTableField('tableGoodsImage', $arrInclude['image'], null, 'gi');
            unset($arrExclude);

            if ($tmpMemNo) {
                $member = \App::load("\\Component\\Member\\Member");
                $memNo = $tmpMemNo;
                $memberInfo = $member->getMemberInfo($memNo);
                $memberGroupNo = $memberInfo['groupSno'];
                $this->_memInfo = $memberInfo;
            } else {
                $memberGroupNo = \Session::get('member.groupSno');
                $memNo = Session::get("member.memNo");
            }

            $sql = "SELECT c.*,  
                        " . implode(', ', $arrFieldGoods) . ",
                        " . implode(', ', $arrFieldOption) . ",
                        " . implode(', ', $arrFieldImage) . "
                        FROM wm_subscription_cart3 AS c 
                            INNER JOIN " . DB_GOODS . " g ON c.goodsNo = g.goodsNo
                            LEFT JOIN " . DB_GOODS_OPTION . " go ON c.optionSno = go.sno AND c.goodsNo = go.goodsNo
                            LEFT JOIN " . DB_GOODS_IMAGE . " as gi ON g.goodsNo = gi.goodsNo AND gi.imageKind = 'list'
                    WHERE c.memNo='{$memNo}'";

            if ($isTemp) {
                if ($isAdmin) {
                    $sql .= " AND c.isTemp='2'";
                } else {
                    $sql .= " AND c.isTemp='1'";
                }
            } else {
                $sql .= " AND c.isTemp='0'";
            }

            if ($idx) {
                if (is_array($idx))
                    $sql .= " AND c.idx IN (" . implode(",", $idx) . ")";
                else
                    $sql .= " AND c.idx='{$idx}'";
            }

            $result = $this->db->query($sql);

            //매입처 관련 정보
            if (gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true) {
                $strPurchaseSQL = 'SELECT purchaseNo,purchaseNm FROM ' . DB_PURCHASE . ' g  WHERE delFl = "n"';
                $tmpPurchaseData = $this->db->query_fetch($strPurchaseSQL);
                $purchaseData = array_combine(array_column($tmpPurchaseData, 'purchaseNo'), array_column($tmpPurchaseData, 'purchaseNm'));
            }

            //상품 가격 노출 관련
            $goodsPriceDisplayFl = gd_policy('goods.display')['priceFl'];

            //품절상품 설정
            if (Request::isMobile()) {
                $soldoutDisplay = gd_policy('soldout.mobile');
            } else {
                $soldoutDisplay = gd_policy('soldout.pc');
            }

            // 제외 혜택 쿠폰 번호
            $exceptCouponNo = [];
            $goodsKey = [];
            $goods = new Goods();
            //상품 혜택 모듈
            $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');

            /* 정기결제 모듈 */
            $obj = App::load(\Component\Subscription\Subscription::class);

            while ($data = $this->db->fetch($result)) {

                if(!$postValue['isScheduleList']) {
                    $cfg = $obj->setGoods($data['goodsNo'])->setMode("getGoodsCfg")->get();
                } else {
                    $cfg = $obj->getCfg();
                }

                if ($tmpDiscount) $cfg['discount'][0] = $tmpDiscount;

                if ($postValue['isScheduleList']) {


                    if ($data['goodsDiscountFl'] == 'n') {
                        $data['goodsDiscountGroupMemberInfo'] = '';
                    }

                    $data['goodsDiscountFl'] = 'y';
                    $data['goodsDiscountUnit'] = 'percent';
                    $data['goodsDiscount'] = $postValue['dc'];
                    $data['goodsDiscountGroup'] = 'all';
                    $data['fixedGoodsDiscount'] = "option";
                    $data['goodsBenefitSetFl'] = 'y';
                } else {

                    if ($cfg['discount'][0] > 0) {
                        $data['goodsDiscountFl'] = 'y';
                        $data['goodsDiscountUnit'] = 'percent';
                        $data['goodsDiscount'] = $cfg['discount'][0];
                        $data['goodsDiscountGroup'] = 'all';
                        $data['fixedGoodsDiscount'] = "option";
                        $data['goodsBenefitSetFl'] = 'y';
                    }
                }

                /* 웹앤모바일 튜닝 상품할인 + 정기배송 할인설정 처리 [시작] */
                $goodsView = $goods->getGoodsView($data['goodsNo']);

                // 상품할인이 그룹인 경우
                if ($goodsView['goodsDiscountFl'] == 'y' && $goodsView['goodsDiscountGroup'] == 'group') {

                    // 그룹 별로 설정한 데이터 json decode
                    $memberGroupData = json_decode($goodsView['goodsDiscountGroupMemberInfo'], true);

                    // 그룹 별 할인 데이터 배열 변경처리
                    foreach ($memberGroupData['groupSno'] as $_index => $_value) {
                        $clearMemberGroupData[$_value]['goodsDiscount'] = $memberGroupData['goodsDiscount'][$_index];
                        $clearMemberGroupData[$_value]['goodsDiscountUnit'] = $memberGroupData['goodsDiscountUnit'][$_index];
                    }

                    // 변경된 배열의 키값에 자신의 그룹번호와 일치하는게 있을 때
                    if(!empty($memberGroupNo)) {
                        if (array_key_exists($memberGroupNo, $clearMemberGroupData)) {
                            if ($clearMemberGroupData[$memberGroupNo]['goodsDiscountUnit'] == 'percent') {
                                $data['goodsDiscount'] += $clearMemberGroupData[$memberGroupNo]['goodsDiscount'];
                            }
                        }
                    }

                } else if ($goodsView['goodsDiscountFl'] == 'y' && $goodsView['goodsDiscountGroup'] == 'member') { // 회원전용 할인인 경우
                    if ($goodsView['goodsDiscountUnit'] == 'percent')
                        $data['goodsDiscount'] += $goodsView['goodsDiscount'];
                } else if ($goodsView['goodsDiscountFl'] == 'y' && $goodsView['goodsDiscountGroup'] == 'all') {
                    if ($goodsView['goodsDiscountUnit'] == 'percent')
                        $data['goodsDiscount'] += $goodsView['goodsDiscount'];
                }
                /* 웹앤모바일 튜닝 상품할인 + 정기배송 할인설정 처리 [종료] */

                //상품혜택 사용시 해당 변수 재설정
                $data = $goodsBenefit->goodsDataFrontConvert2($data);

                // stripcslashes 처리
                // json형태의 경우 json값안에 "이있는경우 stripslashes처리가 되어 json_decode에러가 나므로 json값중 "이 들어갈수있는경우 $aCheckKey에 해당 필드명을 추가해서 처리해주세요
                $aCheckKey = array('optionText');
                foreach ($data as $k => $v) {
                    if (!in_array($k, $aCheckKey)) {
                        $data[$k] = gd_htmlspecialchars_stripslashes($v);
                    }
                }

                // 전체상품 수량
                $this->cartGoodsCnt += $data['goodsCnt'];
                // 쿠폰사용이면
                if (!empty($data['memberCouponNo']) && $data['memberCouponNo'] != '') {
                    // 쿠폰 기본설정값을 가져와서 회원등급만 사용설정이면 쿠폰정보를 제거 처리 & changePrice false처리
                    $couponConfig = gd_policy('coupon.config');
                    if ($couponConfig['chooseCouponMemberUseType'] == 'member') {
                        $this->setMemberCouponDelete($data['sno']);
                        $data['memberCouponNo'] = '';
                        $this->changePrice = false;
                    }

                    // 쿠폰 사용정보를 가져와서 쿠폰사용정보가 있으면 쿠폰설정에 따른 결제 방식 제한을 처리해준다
                    $aTempMemberCouponNo = explode(INT_DIVISION, $data['memberCouponNo']);
                    $coupon = \App::load('\\Component\\Coupon\\Coupon');
                    foreach ($aTempMemberCouponNo as $val) {
                        $aTempCouponInfo = $coupon->getMemberCouponInfo($val);
                        if ($aTempCouponInfo['couponUseAblePaymentType'] == 'bank') {
                            $data['payLimitFl'] = 'y';
                            if ($data['payLimit'] == '') {
                                $data['payLimit'] = 'gb';
                            } else {
                                $aTempPayLimit = explode(STR_DIVISION, $data['payLimit']);
                                $bankCheck = 'n';
                                foreach ($aTempPayLimit as $limitVal) {
                                    if ($limitVal == 'gb') {
                                        $bankCheck = 'y';
                                    }
                                }
                                if ($bankCheck == 'n') {
                                    //$data['payLimit'] = STR_DIVISION . 'gb';
                                    $data['payLimit'] = array(false);
                                }
                            }
                        }
                    }
                }

                // 기준몰 상품명 저장 (무조건 기준몰 상품명이 저장되도록)
                $data['goodsNmStandard'] = $data['goodsNm'];
                if ($mallBySession && $globalData[$data['goodsNo']]) {
                    $data = array_replace_recursive($data, array_filter(array_map('trim', $globalData[$data['goodsNo']])));
                }

                // 상품 카테고리 정보
                $goods = \App::load(\Component\Goods\Goods::class);
                $data['cateAllCd'] = $goods->getGoodsLinkCategory($data['goodsNo']);

                //매입처 관련 정보
                if (gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === false || (gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true && !in_array($data['purchaseNo'], array_keys($purchaseData)))) {
                    unset($data['purchaseNo']);
                }

                // 상품 삭제 여부에 따른 처리
                if ($data['delFl'] === 'y') {
                    $_delCartSno[] = $data['sno'];
                    unset($data);
                    continue;
                } else {
                    unset($data['delFl']);
                }

                // 텍스트옵션 상품 정보
                $goodsOptionText = $goods->getGoodsOptionText($data['goodsNo']);
                if (empty($data['optionText']) === false && gd_isset($goodsOptionText)) {
                    $optionTextKey = array_keys(json_decode($data['optionText'], true));
                    foreach ($goodsOptionText as $goodsOptionTextInfo) {
                        if (in_array($goodsOptionTextInfo['sno'], $optionTextKey) === true) {
                            $data['optionTextInfo'][$goodsOptionTextInfo['sno']] = [
                                'optionSno' => $goodsOptionTextInfo['sno'],
                                'optionName' => $goodsOptionTextInfo['optionName'],
                                'baseOptionTextPrice' => $goodsOptionTextInfo['addPrice'],
                            ];
                        }
                    }

                }

                // 추가 상품 정보
                $data['addGoodsMustFl'] = $mustFl = json_decode(gd_htmlspecialchars_stripslashes($data['addGoods']), true);
                if ($data['addGoodsFl'] === 'y' && empty($data['addGoodsNo']) === false) {
                    $data['addGoodsNo'] = json_decode($data['addGoodsNo']);
                    $data['addGoodsCnt'] = json_decode($data['addGoodsCnt']);
                } else {
                    $data['addGoodsNo'] = '';
                    $data['addGoodsCnt'] = '';
                }

                // 추가 상품 필수 여부
                if ($data['addGoodsFl'] === 'y' && empty($data['addGoods']) === false) {
                    foreach ($mustFl as $k => $v) {
                        if ($v['mustFl'] == 'y') {
                            if (is_array($data['addGoodsNo']) === false) {
                                $data['addGoodsSelectedFl'] = 'n';
                                break;
                            } else {
                                $addGoodsResult = array_intersect($v['addGoods'], $data['addGoodsNo']);
                                if (empty($addGoodsResult) === true) {
                                    $data['addGoodsSelectedFl'] = 'n';
                                    break;
                                }
                            }
                        }
                    }
                    unset($mustFl);
                }

                // 텍스트 옵션 정보 (sno, value)
                $data['optionTextSno'] = [];
                $data['optionTextStr'] = [];
                if ($data['optionTextFl'] === 'y' && empty($data['optionText']) === false) {
                    $arrText = json_decode($data['optionText']);
                    foreach ($arrText as $key => $val) {
                        $data['optionTextSno'][] = $key;
                        $data['optionTextStr'][$key] = $val;
                        unset($tmp);
                    }
                }
                unset($data['optionText']);

                // 텍스트옵션 필수 사용 여부
                if ($data['optionTextFl'] === 'y') {
                    if (gd_isset($goodsOptionText)) {
                        foreach ($goodsOptionText as $k => $v) {
                            if ($v['mustFl'] == 'y' && !in_array($v['sno'], $data['optionTextSno'])) {
                                $data['optionTextEnteredFl'] = 'n';
                            }
                        }
                    }
                }
                unset($optionText);

                // 상품 구매 가능 여부
                $data = $this->checkOrderPossible($data);

                //구매불가 대체 문구 관련
                if ($data['goodsPermissionPriceStringFl'] == 'y' && $data['goodsPermission'] != 'all' && (($data['goodsPermission'] == 'member' && $this->isLogin === false) || ($data['goodsPermission'] == 'group' && !in_array($this->members['groupSno'], explode(INT_DIVISION, $data['goodsPermissionGroup']))))) {
                    $data['goodsPriceString'] = $data['goodsPermissionPriceString'];
                }

                //품절일경우 가격대체 문구 설정
                if (($data['soldOutFl'] === 'y' || ($data['soldOutFl'] === 'n' && $data['stockFl'] === 'y' && ($data['totalStock'] <= 0 || $data['totalStock'] < $data['goodsCnt']))) && $soldoutDisplay['soldout_price'] != 'price') {
                    if ($soldoutDisplay['soldout_price'] == 'text') {
                        $data['goodsPriceString'] = $soldoutDisplay['soldout_price_text'];
                    } else if ($soldoutDisplay['soldout_price'] == 'custom') {
                        $data['goodsPriceString'] = "<img src='" . $soldoutDisplay['soldout_price_img'] . "'>";
                    }
                }

                $data['goodsPriceDisplayFl'] = 'y';
                if (empty($data['goodsPriceString']) === false && $goodsPriceDisplayFl == 'n') {
                    $data['goodsPriceDisplayFl'] = 'n';
                }

                // 정책설정에서 품절상품 보관설정의 보관상품 품절시 자동삭제로 설정한 경우
                if ($this->cartPolicy['soldOutFl'] == 'n' && $data['orderPossibleCode'] == self::POSSIBLE_SOLD_OUT) {
                    $_delCartSno[] = $data['sno'];
                    unset($data);
                    continue;
                }

                // 상품결제 수단에 따른 주문페이지 결제수단 표기용 데이터
                if ($data['payLimitFl'] == 'y' && gd_isset($data['payLimit'])) {
                    $payLimit = explode(STR_DIVISION, $data['payLimit']);
                    $data['payLimit'] = $payLimit;

                    if (is_array($payLimit) && $this->payLimit) {
                        $this->payLimit = array_intersect($this->payLimit, $payLimit);
                        if (empty($this->payLimit) === true) {
                            $this->payLimit = ['false'];
                        }
                    } else {
                        $this->payLimit = $payLimit;
                    }
                }

                // 비회원시 담은 상품과 회원로그인후 담은 상품이 중복으로 있는경우 재고 체크
                $data['duplicationGoods'] = 'n';
                if (isset($tmpStock[$data['goodsNo']][$data['optionSno']]) === false) {
                    $tmpStock[$data['goodsNo']][$data['optionSno']] = $data['goodsCnt'];
                } else {
                    $data['duplicationGoods'] = 'y';
                    $chkStock = $tmpStock[$data['goodsNo']][$data['optionSno']] + $data['goodsCnt'];
                    if ($data['stockFl'] == 'y' && $data['stockCnt'] < $chkStock) {
                        $this->orderPossible = false;
                        $data['stockOver'] = 'y';
                    }
                }

                // 상품구분 초기화 (상품인지 추가상품인지?)
                $data['goodsType'] = 'goods';

                // 상품 이미지 처리 @todo 상품 사이즈 설정 값을 가지고 와서 이미지 사이즈 변경을 할것

                // 세로사이즈고정 체크
                $imageSize = SkinUtils::getGoodsImageSize('list');
                $imageConf = gd_policy('goods.image');

                if (Request::isMobile() || $imageConf['imageType'] != 'fixed') {
                    $imageSize['size1'] = '40'; // 기존 사이즈
                    $imageSize['hsize1'] = '';
                }

                // 상품 이미지 처리
                if ($data['onlyAdultFl'] == 'y' && gd_check_adult() === false && $data['onlyAdultImageFl'] == 'n') {
                    if (Request::isMobile()) {
                        $data['goodsImageSrc'] = "/data/icon/goods_icon/only_adult_mobile.png";
                    } else {
                        $data['goodsImageSrc'] = "/data/icon/goods_icon/only_adult_pc.png";
                    }

                    $data['goodsImage'] = SkinUtils::makeImageTag($data['goodsImageSrc'], $imageSize['size1']);
                } else {
                    $data['goodsImage'] = gd_html_preview_image($data['imageName'], $data['imagePath'], $data['imageStorage'], $imageSize['size1'], 'goods', $data['goodsNm'], 'class="imgsize-s"', false, false, $imageSize['hsize1']);
                }


                unset($data['imageStorage'], $data['imagePath'], $data['imageName'], $data['imagePath']);

                $data['goodsMileageExcept'] = 'n';
                $data['couponBenefitExcept'] = 'n';
                $data['memberBenefitExcept'] = 'n';

                //타임세일 할인 여부
                $data['timeSaleFl'] = false;
                if (gd_is_plus_shop(PLUSSHOP_CODE_TIMESALE) === true && Request::post()->get('mode') !== 'cartEstimate') {

                    $timeSale = \App::load('\\Component\\Promotion\\TimeSale');
                    $timeSaleInfo = $timeSale->getGoodsTimeSale($data['goodsNo']);
                    if ($timeSaleInfo) {
                        $data['timeSaleFl'] = true;
                        if ($timeSaleInfo['mileageFl'] == 'n') {
                            $data['goodsMileageExcept'] = "y";
                        }
                        if ($timeSaleInfo['couponFl'] == 'n') {
                            $data['couponBenefitExcept'] = "y";

                            // 타임세일 상품적용 쿠폰 사용불가 체크
                            if (empty($data['memberCouponNo']) === false) {
                                $exceptCouponNo[$data['sno']] = $data['memberCouponNo'];
                            }
                        }
                        if ($timeSaleInfo['memberDcFl'] == 'n') {
                            $data['memberBenefitExcept'] = "y";
                        }
                        if ($data['goodsPrice'] > 0) {
                            // 타임세일 할인금액
                            $data['timeSalePrice'] = gd_number_figure((($timeSaleInfo['benefit'] / 100) * $data['goodsPrice']), $truncGoods['unitPrecision'], $truncGoods['unitRound']);

                            $data['goodsPrice'] = gd_number_figure($data['goodsPrice'] - (($timeSaleInfo['benefit'] / 100) * $data['goodsPrice']), $truncGoods['unitPrecision'], $truncGoods['unitRound']);
                        }
                        //상품 옵션가(일체형,분리형) 타임세일 할인율 적용 ( 텍스트 옵션가 / 추가상품가격 제외 )
                        if ($data['optionFl'] === 'y') {
                            // 타임세일 할인금액
                            $data['timeSalePrice'] = gd_number_figure($data['timeSalePrice'] + (($timeSaleInfo['benefit'] / 100) * $data['optionPrice']), $truncGoods['unitPrecision'], $truncGoods['unitRound']);

                            $data['optionPrice'] = gd_number_figure($data['optionPrice'] - (($timeSaleInfo['benefit'] / 100) * $data['optionPrice']), $truncGoods['unitPrecision'], $truncGoods['unitRound']);
                        }
                    }
                }

                // 혜택제외 체크 (쿠폰)
                $exceptBenefit = explode(STR_DIVISION, $data['exceptBenefit']);
                $exceptBenefitGroupInfo = explode(INT_DIVISION, $data['exceptBenefitGroupInfo']);
                if (in_array('coupon', $exceptBenefit) === true && ($data['exceptBenefitGroup'] == 'all' || ($data['exceptBenefitGroup'] == 'group' && in_array($this->_memInfo['groupSno'], $exceptBenefitGroupInfo) === true))) {
                    if (empty($data['memberCouponNo']) === false) {
                        $exceptCouponNo[$data['sno']] = $data['memberCouponNo'];
                    }
                    $data['couponBenefitExcept'] = "y";
                }

                //배송방식에 관한 데이터
                $data['goodsDeliveryMethodFl'] = $data['deliveryMethodFl'];
                $data['goodsDeliveryMethodFlText'] = gd_get_delivery_method_display($data['deliveryMethodFl']);

                $tmpOptionName = [];
                for ($optionKey = 1; $optionKey <= 5; $optionKey++) {
                    if (empty($data['optionValue' . $optionKey]) === false) {
                        $tmpOptionName[] = $data['optionValue' . $optionKey];
                    }
                }
                $data['optionNm'] = @implode('/', $tmpOptionName);
                unset($tmpOptionName);

                if (in_array($data['goodsNo'], $goodsKey) === false) {
                    $goodsKey[] = $data['goodsNo'];
                }
                $data['goodsKey'] = array_search($data['goodsNo'], $goodsKey);

                // 현재 주문 중인 장바구니 SNO
                $this->cartSno[] = $data['sno'];

                // 쇼핑 계속하기 주소 처리
                if ($data['cateCd'] && empty($this->shoppingUrl) === true) {
                    $this->shoppingUrl = $data['cateCd'];
                }

                $getData[] = $data;
                unset($data);

            } // endwhile

            // 장바구니 상품에 대한 계산된 정보
            foreach ($getData as $k => $v) {
                $getData[$k]['sno'] = $v['idx'];
            }

            $getCart = $this->getCartDataInfo2($getData, $postValue);

            // 글로벌 해외배송 조건에 따라서 처리
            if (Globals::get('gGlobal.isFront') || $this->isAdminGlobal === true) {
                if ($address !== null) {
                    $getCart = $this->getOverseasDeliveryDataInfo($getCart, array_column($getData, 'deliverySno'), $address);
                }
            } else {
                // 복수배송지 사용시 배송정보 재설정
                if ((\Component\Order\OrderMultiShipping::isUseMultiShipping() === true && $postValue['multiShippingFl'] == 'y') || $postValue['isAdminMultiShippingFl'] === 'y') {
                    foreach ($postValue['orderInfoCdData'] as $key => $val) {
                        $tmpGetCart = [];
                        $tmpAllGetKey = [];
                        $tmpDeliverySnos = [];
                        foreach ($val as $tVal) {
                            $tmpScmNo = $this->multiShippingOrderInfo[$tVal]['scmNo'];
                            $tmpDeliverySno = $this->multiShippingOrderInfo[$tVal]['deliverySno'];
                            $tmpGetKey = $this->multiShippingOrderInfo[$tVal]['getKey'];
                            $tmpAllGetKey[] = $tmpGetKey;
                            $tmpDeliverySnos[] = $tmpDeliverySno;

                            $tmpGetCart[$tmpScmNo][$tmpDeliverySno][$tmpGetKey] = $getCart[$tmpScmNo][$tmpDeliverySno][$tmpGetKey];
                        }
                        if ($key > 0) {
                            $multiAddress = str_replace(' ', '', $postValue['receiverAddressAdd'][$key] . $postValue['receiverAddressSubAdd'][$key]);
                        } else {
                            $multiAddress = $address;
                        }

                        $tmpGetCart = $this->getDeliveryDataInfo($tmpGetCart, $tmpDeliverySnos, $multiAddress, $postValue['multiShippingFl'], $key);
                        foreach ($tmpGetCart as $sKey => $sVal) {
                            foreach ($sVal as $dKey => $dVal) {
                                foreach ($dVal as $getKey => $getVal) {
                                    if (empty($tmpGetCart[$sKey][$dKey][$getKey]) === false) {
                                        $getCart[$sKey][$dKey][$getKey] = $tmpGetCart[$sKey][$dKey][$getKey];
                                    }
                                }

                            }
                        }
                        unset($tmpGetCart);
                    }
                } else {
                    $getCart = $this->getDeliveryDataInfo($getCart, array_column($getData, 'deliverySno'), $address);
                }
            }

            // 장바구니 SCM 정보
            if (is_array($getCart)) {
                $scmClass = \App::load(\Component\Scm\Scm::class);
                $this->cartScmCnt = count($getCart);
                $this->cartScmInfo = $scmClass->getCartScmInfo(array_keys($getCart));
            }

            // 회원 할인 총 금액
            $this->totalSumMemberDcPrice = $this->totalMemberDcPrice + $this->totalMemberOverlapDcPrice;

            // 총 부가세율
            $this->totalVatRate = gd_tax_rate($this->totalGoodsPrice, $this->totalPriceSupply);

            // 비과세 설정에 따른 세금계산서 출력 여부
            if ($this->taxInvoice === true && $this->taxGoodsChk === false) {
                $this->taxInvoice = false;
            }

            // 총 결제 금액 (상품별 금액 + 배송비 - 상품할인 - 회원할인 - 사용마일리지(X) - 상품쿠폰할인 - 주문쿠폰할인(X) : 여기서는 장바구니에 있는 상품에 대해서만 계산하므로 결제 예정금액임)
            // 주문관련 할인금액 및 마일리지/예치금 사용은 setOrderSettleCalculation에서 별도로 계산됨
            $this->totalSettlePrice = $this->totalGoodsPrice + $this->totalDeliveryCharge - $this->totalGoodsDcPrice - $this->totalSumMemberDcPrice - $this->totalCouponGoodsDcPrice;
            if ($this->totalSettlePrice < 0) $this->totalSettlePrice = 0;

            // 총 적립 마일리지 (상품별 총 상품 마일리지 + 회원 그룹 총 마일리지 + 총 상품 쿠폰 마일리지 + 총 주문 쿠폰 적립 금액 : 여기서는 장바구니에 있는 상품에 대해서만 계산하므로 적립 예정금액임)
            $this->totalMileage = $this->totalGoodsMileage + $this->totalMemberMileage + $this->totalCouponGoodsMileage + $this->totalCouponOrderMileage;

            unset($getData);
        } // endif

        return $getCart;
    }
      
      public function getCartInfo($idx = null)
      {
          $info = [];
          if ($idx) {
             if ($tmp = $this->db->fetch("SELECT * FROM wm_subscription_cart3 WHERE idx='{$idx}'")) {
                 $tmp['optionText'] = json_decode($tmp['optionText'], true);
                 $info = $tmp;
             }
          } // endif 
          
          return $info;
      }

      public function set($idx = null, $goodsCnt = 0)
      {
          $this->idx = $idx;
          if ($goodsCnt)
              $this->goodsCnt = $goodsCnt;
          
          return $this;
      }
      
      public function setMode($mode = null)
      {
          $this->mode = $mode;
          return $this;
      }
      
      public function del()
      {
          if ($this->idx) {
               if ($this->mode == 'coupon_delete') {
                    return $this->db->query("UPDATE wm_subscription_cart3 SET memberCouponNo='' WHERE idx='".$this->idx."'");
                } else {
                    return $this->db->query("DELETE FROM wm_subscription_cart3 WHERE idx='".$this->idx."'");
                }
          }
      }
      
      public function changeEa()
      {
          if ($this->idx && $this->goodsCnt) {
              $sql = "UPDATE wm_subscription_cart3 SET goodsCnt='".$this->goodsCnt."' WHERE idx='".$this->idx."'";
              return $this->db->query($sql);
          }
      }
      
      public function cartDelete($idx = null)
      {
            
          if (is_array($idx)) {
              foreach ($idx as $_idx) {
                $this->set($_idx)->del();
              }
          } else {
            $this->set($idx)->del();  
          }
      }
      
     public function setOrderSettlePayCalculation($requestData)
     {
        $this->totalMemberDcPrice = $requestData['totalMemberDcPrice'];
        $this->totalMemberOverlapDcPrice = $requestData['totalMemberOverlapDcPrice'];
        $this->totalSumMemberDcPrice = $this->totalMemberDcPrice + $this->totalMemberOverlapDcPrice;

        // 전체 할인금액 초기화 = 총 상품금액 - 총 상품할인 적용된 결제금액
        $this->totalDcPrice = $this->totalGoodsPrice + $this->totalDeliveryCharge - $this->totalSettlePrice;
        $orderPrice['totalOrderDcPrice'] = $this->totalDcPrice;
       
        // 실 상품금액 = 상품금액 + 쿠폰사용금액 (순수 상품 합계금액)
        $orderPrice['totalGoodsPrice'] = $this->totalGoodsPrice;

        // 쿠폰 계산을 위한 실제 할인이 되기전에 적용된 상품판매가
        $orderPrice['totalSumGoodsPrice'] = $this->totalPrice;

        // 배송비 (전체 = 정책배송비 + 지역별배송비)
        $orderPrice['totalDeliveryCharge'] = $this->totalDeliveryCharge;
        $orderPrice['totalGoodsDeliveryPolicyCharge'] = $this->totalGoodsDeliveryPolicyCharge;
        $orderPrice['totalScmGoodsDeliveryCharge'] = $this->totalScmGoodsDeliveryCharge;
        $orderPrice['totalGoodsDeliveryAreaCharge'] = $this->totalGoodsDeliveryAreaPrice;


        // 배송비 착불 금액 넘겨 받기 (collectPrice|wholefreeprice)
        foreach ($this->setDeliveryInfo as $dKey => $dVal) {
            $orderPrice['totalDeliveryCollectPrice'][$dKey] = $dVal['goodsDeliveryCollectPrice'];
            $orderPrice['totalDeliveryWholeFreePrice'][$dKey] = $dVal['goodsDeliveryWholeFreePrice'];
        }

        // 총 상품 할인 금액
        $orderPrice['totalGoodsDcPrice'] = $this->totalGoodsDcPrice;

        // 총 회원 할인 금액
        $orderPrice['totalSumMemberDcPrice'] = $this->totalSumMemberDcPrice;
        $orderPrice['totalMemberDcPrice'] = $this->totalMemberDcPrice;//총 회원할인 금액
        $orderPrice['totalMemberOverlapDcPrice'] = $this->totalMemberOverlapDcPrice;//총 그룹별 회원 중복할인 금액

        // 쿠폰할인액 = 상품쿠폰 + 주문쿠폰 + 배송비쿠폰
        $orderPrice['totalCouponDcPrice'] = ($this->totalCouponGoodsDcPrice + $this->totalCouponOrderDcPrice + $this->totalCouponDeliveryDcPrice);
        $orderPrice['totalCouponGoodsDcPrice'] = $this->totalCouponGoodsDcPrice;
        $orderPrice['totalCouponOrderDcPrice'] = $this->totalCouponOrderDcPrice;
        $orderPrice['totalCouponDeliveryDcPrice'] = $this->totalCouponDeliveryDcPrice;

        // 주문할인금액 안분을 위한 순수상품금액 = 상품금액(옵션/텍스트옵션가 포함) + 추가상품금액 - 상품할인 - 회원할인 - 상품쿠폰할인
        $orderPrice['settleTotalGoodsPrice'] = $this->totalGoodsPrice - $this->totalGoodsDcPrice - $this->totalSumMemberDcPrice - $this->totalCouponGoodsDcPrice;

        // 배송비할인금액 안분을 위한 순수배송비금액 = 정책배송비 + 지역배송비 - 배송비 할인쿠폰 - 회원 배송비 무료
        $orderPrice['settleTotalDeliveryCharge'] = $this->totalDeliveryCharge - $this->totalCouponDeliveryDcPrice - $this->totalDeliveryFreeCharge;

        // 주문할인금액 안분을 위한 순수상품금액 + 실 배송비 - 배송비 할인쿠폰
        $orderPrice['settleTotalGoodsPriceWithDelivery'] = $orderPrice['settleTotalGoodsPrice'] + $orderPrice['settleTotalDeliveryCharge'];// 배송비 포함


        $orderPrice['totalGoodsMileage'] = $this->totalGoodsMileage;// 총 상품 적립 마일리지
        $orderPrice['totalMemberMileage'] = $this->totalMemberMileage;// 총 회원 적립 마일리지
        $orderPrice['totalCouponGoodsMileage'] = $this->totalCouponGoodsMileage;// 총 상품쿠폰 적립 마일리지
        $orderPrice['totalCouponOrderMileage'] = $this->totalCouponOrderMileage;// 총 주문쿠폰 적립 마일리지
        $orderPrice['totalMileage'] = $this->totalMileage;// 총 적립 마일리지 = 총 상품 적립 마일리지 + 총 회원 적립 마일리지 + 총 쿠폰 적립 마일리지
 

        // 총 주문할인 + 상품 할인 금액
        $orderPrice['totalDcPrice'] = $this->totalDcPrice;

        // 총 주문할인 금액 (복합과세용 금액 산출을 위해 배송비는 제외시킴)
        $orderPrice['totalOrderDcPrice'] = $this->totalCouponOrderDcPrice + $this->totalUseMileage + $this->totalUseDeposit;

        // 마일리지 지급예외 정책 저장
        $orderPrice['mileageGiveExclude'] = $this->mileageGiveExclude;
    
        $this->totalSettlePrice = $this->totalGoodsPrice + $this->totalDeliveryCharge - $this->totalGoodsDcPrice - $this->totalSumMemberDcPrice - $this->totalCouponGoodsDcPrice;
    
        // 마일리지/예치금/쿠폰 사용에 따른 실결제 금액 반영
        $orderPrice['settlePrice'] = $this->totalSettlePrice;
        
        // 주문하기에서 요청된 실 결제금액
        $requestSettlePrice = str_replace(',', '', $requestData['settlePrice']);

        return $orderPrice;
    }




    public function getCartDataInfo2($getData, $postValue = [])
    {
        // getData -> 장바구니 정보가 한개만 넘어온것인지 여러개(전체 또는 다수 선택)정보가 넘어온것인지 구분값
        $isAllFl = (count($getData) > 1) ? 'T' : 'F';

        // 상품데이터를 이용해 상품번호, 배송번호, 추가상품, 텍스트옵션, 회원쿠폰번호 별도 추출
        foreach (ArrayUtils::removeEmpty(array_column($getData, 'addGoodsNo')) as $val) {
            foreach ($val as $cKey => $cVal) {
                $arrTmp['addGoodsNo'][] = $cVal;
            }
        }
        foreach (ArrayUtils::removeEmpty(array_column($getData, 'optionTextSno')) as $key => $val) {
            foreach ($val as $cKey => $cVal) {
                $arrTmp['optionText'][] = $cVal;
            }
        }
        $couponPolicy = gd_policy('coupon.config');

        // 추가 상품 디비 정보
        $getAddGoods = $this->getAddGoodsInfo($arrTmp['addGoodsNo']);

        // 텍스트 옵션 디비 정보
        $getOptionText = $this->getOptionTextInfo($arrTmp['optionText']);

        // 반환할 장바구니 데이터 초기화
        $getCart = [];

        // 과세/비과세 상품 존재 여부 체크 초기화
        $this->taxGoodsChk = true;

        // 장바구니 갯수
        $cartCnt = 0;

        $goodsPriceInfo = [];

        $goodsCouponData = [];


        // 상품혜택관리 치환코드 생성
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');

        // 상품쿠폰 주문서페이지 변경 제한안함일 때 && 수기주문이 아닐 경우
        if($couponPolicy['productCouponChangeLimitType'] == 'n' && $this->isWrite === false) {
            $goodsCouponForTotalPrice = [];
            if((Request::getFileUri() == 'cart_ps.php') && in_array($postValue['mode'], $this->cartPsPassModeArray) == true) {
                if (empty($postValue['cartAllSno']) === false) { // cart 전체 sno 넘어왔을 경우
                    $getProductReturn = $this->getCartProductCouponDataInfo($postValue); // 전체 카트 sno를 통해 카트+상품+옵션 조합 배열(가격계산위해)
                    $goodsCouponForTotalPrice = $this->getProductCouponGoodsAllPrice($getProductReturn); // 상품쿠폰이 주문기준일 때 주문상품 전체 가격
                } else {
                    $goodsCouponForTotalPrice = $this->getProductCouponGoodsAllPrice($getData); // 상품쿠폰이 주문기준일 때 주문상품 전체 가격
                }
            } else {
                $goodsCouponForTotalPrice = $this->getProductCouponGoodsAllPrice($getData); // 상품쿠폰이 주문기준일 때 주문상품 전체 가격
            }
        }

        //상품 옵션 상태 코드 불러오기
        $request = \App::getInstance('request');
        $mallSno = $request->get()->get('mallSno', 1);
        $code = \App::load('\\Component\\Code\\Code',$mallSno);
        $deliverySell = $code->getGroupItems('05002');
        $deliverySellNew['y'] = $deliverySell['05002001']; //정상은 코드 변경
        $deliverySellNew['n'] = $deliverySell['05002002']; //품절은 코드 변경
        unset($deliverySell['05002001']);
        unset($deliverySell['05002002']);
        $optionSellCode = array_merge($deliverySellNew, $deliverySell);

        $deliveryReason = $code->getGroupItems('05003');
        $deliveryReasonNew['normal'] = $deliveryReason['05003001']; //정상은 코드 변경
        unset($deliveryReason['05003001']);
        $optionDeliveryReasonCode = array_merge($deliveryReasonNew, $deliveryReason);

        // 장바구니 상품을 다시 설정을 함 (1차 배열 SCM별, 2차 배열 배송방법)
        // 마일리지 적립 정책 : 절사처리{(판매가 * 수량) + (옵션가 * 수량) + (텍스트옵션가 * 수량) + (추가상품가 * 수량) + (추가상품가 * 수량) + (추가상품가 * 수량) ...}
        // 회원 할인 정책 : 절사처리{(판매가 * 수량) + (옵션가 * 수량) + (텍스트옵션가 * 수량) + (추가상품가 * 수량) + (추가상품가 * 수량) + (추가상품가 * 수량) ...}
        // 쿠폰 정책 : 절사처리{(판매가 * 수량) + (옵션가 * 수량) + (텍스트옵션가 * 수량) + (추가상품가 * 수량) + (추가상품가 * 수량) + (추가상품가 * 수량) ...}
        $tmpMemberDcInfo = $tmpMileageInfo = [];

        foreach ($getData as $dataKey => $dataVal) {
            // 각 상품별 가격 설정 (과세/비과세 설정에 따른 금액 계산, (판매가 * 수량), (옵션가 * 수량))
            $getData[$dataKey]['price']['fixedPrice'] = $getData[$dataKey]['fixedPrice'];
            $getData[$dataKey]['price']['costPrice'] = $getData[$dataKey]['costPrice'];
            $getData[$dataKey]['price']['baseGoodsPrice'] = $getData[$dataKey]['goodsPrice'];
            $getData[$dataKey]['price']['baseOptionPrice'] = $getData[$dataKey]['optionPrice'];
            $getData[$dataKey]['price']['baseOptionTextPrice'] = 0;
            $getData[$dataKey]['price']['goodsPrice'] = $getData[$dataKey]['goodsPrice'];
            $getData[$dataKey]['price']['optionPrice'] = $getData[$dataKey]['optionPrice'];
            $getData[$dataKey]['price']['optionCostPrice'] = $getData[$dataKey]['optionCostPrice'];
            $getData[$dataKey]['price']['optionTextPrice'] = 0;
            $getData[$dataKey]['price']['goodsPriceSum'] = $getData[$dataKey]['goodsPrice'] * $dataVal['goodsCnt'];
            $getData[$dataKey]['price']['optionPriceSum'] = $getData[$dataKey]['optionPrice'] * $dataVal['goodsCnt'];
            $getData[$dataKey]['price']['optionTextPriceSum'] = 0;
            $getData[$dataKey]['price']['addGoodsPriceSum'] = 0;
            $getData[$dataKey]['price']['addGoodsVat']['supply'] = 0;
            $getData[$dataKey]['price']['addGoodsVat']['tax'] = 0;
            $getData[$dataKey]['price']['goodsDcPrice'] = 0;
            $getData[$dataKey]['price']['memberDcPrice'] = 0;
            $getData[$dataKey]['price']['memberOverlapDcPrice'] = 0;
            $getData[$dataKey]['price']['couponGoodsDcPrice'] = 0;

            // 마이앱 사용에 따른 분기 처리
            if ($this->useMyapp) {
                $getData[$dataKey]['price']['myappDcPrice'] = 0;
            }

            $getData[$dataKey]['price']['goodsDeliveryPrice'] = 0;
            $getData[$dataKey]['price']['timeSalePrice'] = $getData[$dataKey]['timeSalePrice'];
        }

        foreach ($getData as $dataKey => $dataVal) {
            // 기본 설정
            $scmNo = (int)$dataVal['scmNo']; // SCM ID
            $arrScmNo[] = $scmNo; // 장바구니 SCM 정보
            $goodsNo = $dataVal['goodsNo']; // 상품 번호
            $optionSno = $dataVal['optionSno'];
            $deliverySno = $dataVal['deliverySno']; // 배송 정책
            $taxFreeFl = $dataVal['taxFreeFl'];
            $taxPercent = $taxFreeFl == 'f' ? 0 : $dataVal['taxPercent'];
            $memberDcFl = true;

            // 상품할인(개별,혜택) DB 저장 데이터 가공
            if($dataVal['goodsDiscountFl'] == 'y' || $dataVal['goodsBenefitSetFl'] == 'y') {
                $getData[$dataKey]['goodsDiscountInfo'] = $goodsBenefit->setBenefitOrderGoodsData($dataVal, 'discount');
            }
            // 상품적립(통합, 개별) DB 저장 데이터 가공
            $getData[$dataKey]['goodsMileageAddInfo'] = $goodsBenefit->setBenefitOrderGoodsData($dataVal, 'mileage');

            if ((\Component\Order\OrderMultiShipping::isUseMultiShipping() == true && $postValue['multiShippingFl'] == 'y') || $postValue['isAdminMultiShippingFl'] === 'y') {
                if (empty($tmpMemberDcInfo[$goodsNo][$optionSno]) === true) {
                    $tmpMemberDcInfo[$goodsNo][$optionSno] = json_decode($postValue['memberDcInfo'][$goodsNo][$optionSno], true);
                }
                if (empty($tmpMileageInfo[$goodsNo][$optionSno]) === true) {
                    $tmpMileageInfo[$goodsNo][$optionSno] = json_decode($postValue['mileageInfo'][$goodsNo][$optionSno], true);
                }
            }

            $exceptBenefit = explode(STR_DIVISION, $dataVal['exceptBenefit']);
            $exceptBenefitGroupInfo = explode(INT_DIVISION, $dataVal['exceptBenefitGroupInfo']);

            if (empty($this->couponApplyOrderNo) === false && $couponPolicy['couponUseType'] == 'y' && $couponPolicy['chooseCouponMemberUseType'] == 'coupon') {
                $memberDcFl = false;
            }

            // 제외 혜택 대상 여부
            $exceptBenefitFl = false;
            if ($dataVal['exceptBenefitGroup'] == 'all' || ($dataVal['exceptBenefitGroup'] == 'group' && in_array($this->_memInfo['groupSno'], $exceptBenefitGroupInfo) === true)) {
                $exceptBenefitFl = true;
                //회원 추가할인 제외
                if (in_array('add', $exceptBenefit) === true) $getData[$dataKey]['addDcFl'] = false;
                //회원 중복할인 제외
                if (in_array('overlap', $exceptBenefit) === true) $getData[$dataKey]['addDcFl'] = false;
            }

            // 과세상품이 있는지를 체크 과세율이 면세 또는 10%(고정) 일경우에 세금계산서 신청 가능
            if ((int)$taxPercent != '10' && (int)$taxPercent != '0') {
                $this->taxGoodsChk = false;
            }

            unset($getData[$dataKey]['fixedPrice'], $getData[$dataKey]['costPrice'], $getData[$dataKey]['goodsPrice'], $getData[$dataKey]['optionPrice']);

            // 상품 옵션 처리
            $getData[$dataKey]['option'] = [];
            if ($dataVal['optionFl'] === 'y') {
                $tmp = explode(STR_DIVISION, $dataVal['optionName']);
                for ($i = 0; $i < 5; $i++) {
                    $optKey = 'optionValue' . ($i + 1);
                    if (empty($dataVal[$optKey]) === false) {
                        $getData[$dataKey]['option'][$i]['optionName'] = (empty($tmp[$i]) === false ? $tmp[$i] : '');
                        $getData[$dataKey]['option'][$i]['optionValue'] = $dataVal[$optKey];

                        // 마지막 옵션리스트에 옵션가를 추가한다.
                        if (count($tmp) == $i + 1) {
                            $getData[$dataKey]['option'][$i]['optionPrice'] = $dataVal['optionPrice'];
                            $getData[$dataKey]['option'][$i]['optionCode'] = $dataVal['optionCode'];
                        }

                        //상품 품절 정보, 상품 배송 정보를 추가한다.
                        if($dataVal['optionSellFl'] == 't'){
                            $getData[$dataKey]['option'][$i]['optionSellStr'] = $optionSellCode[$dataVal['optionSellCode']];
                        }else if($dataVal['optionSellFl'] == 'n'){
                            $getData[$dataKey]['option'][$i]['optionSellStr'] = $optionSellCode[$dataVal['optionSellFl']];
                        }
                        if($dataVal['optionDeliveryFl'] != 'normal'){
                            $getData[$dataKey]['option'][$i]['optionDeliveryStr'] = $optionDeliveryReasonCode[$dataVal['optionDeliveryCode']];
                        }
                    }
                }
                for ($i = 1; $i <= DEFAULT_LIMIT_OPTION; $i++) {
                    $optKey = 'optionValue' . $i;
                    unset($getData[$dataKey][$optKey]);
                }
                unset($tmp);
            }
            unset($getData[$dataKey]['optionName']);

            // 추가 상품 정보
            $getData[$dataKey]['addGoods'] = [];
            if ($dataVal['addGoodsFl'] === 'y' && empty($dataVal['addGoodsNo']) === false) {
                foreach ($dataVal['addGoodsNo'] as $key => $val) {
                    $tmp = $getAddGoods[$val];

                    //
                    $this->cartAddGoodsCnt += $dataVal['addGoodsCnt'][$key];

                    // 추가상품 기본 정보
                    $getData[$dataKey]['addGoods'][$key]['scmNo'] = $tmp['scmNo'];
                    $getData[$dataKey]['addGoods'][$key]['purchaseNo'] = $tmp['purchaseNo'];
                    $getData[$dataKey]['addGoods'][$key]['commission'] = $tmp['commission'];
                    $getData[$dataKey]['addGoods'][$key]['goodsCd'] = $tmp['goodsCd'];
                    $getData[$dataKey]['addGoods'][$key]['goodsModelNo'] = $tmp['goodsModelNo'];
                    $getData[$dataKey]['addGoods'][$key]['optionNm'] = $tmp['optionNm'];
                    $getData[$dataKey]['addGoods'][$key]['brandCd'] = $tmp['brandCd'];
                    $getData[$dataKey]['addGoods'][$key]['makerNm'] = $tmp['makerNm'];
                    $getData[$dataKey]['addGoods'][$key]['stockUseFl'] = $tmp['stockUseFl'] == '1' ? 'y' : 'n';
                    $getData[$dataKey]['addGoods'][$key]['stockCnt'] = $tmp['stockCnt'];
                    $getData[$dataKey]['addGoods'][$key]['viewFl'] = $tmp['viewFl'];
                    $getData[$dataKey]['addGoods'][$key]['soldOutFl'] = $tmp['soldOutFl'];

                    // 과세/비과세 설정에 따른 금액 계산
                    $getData[$dataKey]['addGoods'][$key]['addGoodsNo'] = $val;
                    $getData[$dataKey]['addGoods'][$key]['addGoodsNm'] = $tmp['goodsNm'];
                    $getData[$dataKey]['addGoods'][$key]['addGoodsNmStandard'] = $tmp['goodsNmStandard'];
                    $getData[$dataKey]['addGoods'][$key]['addGoodsPrice'] = $tmp['goodsPrice'];
                    $getData[$dataKey]['addGoods'][$key]['addCostGoodsPrice'] = $tmp['costPrice'];
                    $getData[$dataKey]['addGoods'][$key]['addGoodsCnt'] = $dataVal['addGoodsCnt'][$key];
                    $getData[$dataKey]['addGoods'][$key]['taxFreeFl'] = $tmp['taxFreeFl'];
                    $getData[$dataKey]['addGoods'][$key]['taxPercent'] = $tmp['taxPercent'];
                    $getData[$dataKey]['addGoods'][$key]['addGoodsImage'] = $tmp['addGoodsImage'];

                    //회원등급 > 브랜드별 추가할인 상품 브랜드 정보
                    if ($this->_memInfo['fixedOrderTypeDc'] == 'brand') {
                        if (in_array($tmp['brandCd'], $this->_memInfo['dcBrandInfo']->cateCd)) {
                            $this->goodsBrandInfo[$val][$tmp['brandCd']] = $tmp['brandCd'];
                        } else {
                            if ($tmp['brandCd']) {
                                $this->goodsBrandInfo[$val]['allBrand'] = $tmp['brandCd'];
                            } else {
                                $this->goodsBrandInfo[$val]['noBrand'] = $tmp['brandCd'];
                            }
                        }

                        foreach ($this->goodsBrandInfo[$val] as $gKey => $gVal) {
                            foreach ($this->_memInfo['dcBrandInfo']->cateCd AS $mKey => $mVal) {
                                if ($gKey == $mVal) {
                                    $tmp['dcPercent'] = $this->_memInfo['dcBrandInfo']->goodsDiscount[$mKey];
                                }
                            }
                        }

                        $goodsPriceInfo[$goodsNo]['brandDiscount'][$dataKey][$key] = $tmp['dcPercent'];
                    }

                    foreach ($getData[$dataKey]['addGoodsMustFl'] as $aVal) {
                        if (in_array($val, $aVal['addGoods']) === true) {
                            $getData[$dataKey]['addGoods'][$key]['addGoodsMustFl'] = $aVal['mustFl'];
                        }
                    }

                    // 단가계산용 추가 상품 금액
                    $goodsPriceInfo[$goodsNo]['addGoodsCnt'][$dataKey][$key] = $dataVal['addGoodsCnt'][$key];
                    $goodsPriceInfo[$goodsNo]['addGoodsPrice'][$dataKey][$key] = $tmp['goodsPrice'];

                    if(empty($tmp['goodsNm'])) {
                        $getData[$dataKey]['orderPossible'] = 'n';
                        $getData[$dataKey]['orderPossibleCode'] = self::POSSIBLE_SELL_NO;
                        $getData[$dataKey]['orderPossibleMessage'] = $getData[$dataKey]['orderPossibleMessageList'][] = __('판매중지 추가상품');
                        $this->orderPossible = false;
                    }

                    // 추가상품 재고체크 처리 후 재고 없으면 구매불가 처리
                    if ($tmp['soldOutFl'] === 'y' || ($tmp['soldOutFl'] === 'n' && $tmp['stockUseFl'] === '1' && ($tmp['stockCnt'] == 0 || $tmp['stockCnt'] - $dataVal['addGoodsCnt'][$key] < 0))) {
                        $getData[$dataKey]['orderPossible'] = 'n';
                        $getData[$dataKey]['orderPossibleCode'] = self::POSSIBLE_SOLD_OUT;
                        $getData[$dataKey]['orderPossibleMessage'] = $getData[$dataKey]['orderPossibleMessageList'][] = __('추가상품 재고부족');
                        $this->orderPossible = false;
                    }

                    $getData[$dataKey]['orderPossibleMessageList'] = array_unique($getData[$dataKey]['orderPossibleMessageList']);
                    //추가상품과세율에 따른 세금계산서 출력여부 선택
                    if ((int)$tmp['taxPercent'] != '10' && (int)$tmp['taxPercent'] != '0') {
                        $this->taxGoodsChk = false;
                    }

                    // 추가 상품 순수 개별 부가세 계산 (할인 적용 안됨)
                    $getData[$dataKey]['addGoods'][$key]['addGoodsVat'] = NumberUtils::taxAll($tmp['goodsPrice'] * $dataVal['addGoodsCnt'][$key], $tmp['taxPercent'], $tmp['taxFreeFl']);

                    // 추가 상품 총 금액
                    $getData[$dataKey]['price']['addGoodsPriceSum'] += ($tmp['goodsPrice'] * $dataVal['addGoodsCnt'][$key]);

                    // 추가 상품 개별 부가세 계산
                    $getData[$dataKey]['price']['addGoodsVat']['supply'] += $getData[$dataKey]['addGoods'][$key]['addGoodsVat']['supply'];
                    $getData[$dataKey]['price']['addGoodsVat']['tax'] += $getData[$dataKey]['addGoods'][$key]['addGoodsVat']['tax'];

                    unset($tmp);
                }
            }
            unset($getData[$dataKey]['addGoodsNo']);
            unset($getData[$dataKey]['addGoodsCnt']);

            // 텍스트 옵션
            $getData[$dataKey]['optionText'] = [];
            foreach ($dataVal['optionTextSno'] as $key => $val) {
                $tmp = $getOptionText[$val];

                // 텍스트 옵션 기본 금액 합계
                $getData[$dataKey]['price']['baseOptionTextPrice'] += $tmp['baseOptionTextPrice'];

                // 과세/비과세 설정에 따른 금액 계산
                $tmp['optionTextPrice'] = $tmp['baseOptionTextPrice'];
                $getData[$dataKey]['price']['optionTextPrice'] += $tmp['optionTextPrice'];
                $tmp['optionValue'] = $dataVal['optionTextStr'][$val];
                $getData[$dataKey]['optionText'][$key] = $tmp;

                // 텍스트 옵션 총 금액
                $getData[$dataKey]['price']['optionTextPriceSum'] += ($tmp['optionTextPrice'] * $dataVal['goodsCnt']);
                unset($tmp);
            }
            unset($getData[$dataKey]['optionTextSno']);
            unset($getData[$dataKey]['optionTextStr']);

            // 단가계산용 상품 금액
            $goodsPriceInfo[$goodsNo]['goodsCnt'][$dataKey] = $dataVal['goodsCnt'];
            $goodsPriceInfo[$goodsNo]['goodsPrice'][$dataKey] = $getData[$dataKey]['price']['goodsPrice'];
            $goodsPriceInfo[$goodsNo]['optionPrice'][$dataKey] = $getData[$dataKey]['price']['optionPrice'];
            $goodsPriceInfo[$goodsNo]['optionTextPrice'][$dataKey] = $getData[$dataKey]['price']['optionTextPrice'];
            $goodsPriceInfo[$goodsNo]['memberDcFl'][$dataKey] = true;
            $goodsPriceInfo[$goodsNo]['exceptBenefit'] = $exceptBenefit;
            $goodsPriceInfo[$goodsNo]['exceptBenefitFl'] = $exceptBenefitFl;

            // 상품별 상품 할인 설정
            $policy = new Policy();
            $naverpayConfig = $policy->getNaverPaySetting();
            if ($this->getChannel() != 'naverpay' || ($naverpayConfig['useYn'] == 'y' && $naverpayConfig['saleFl'] == 'y')) {   //네이버가 아니거나 또는 네이버 사용중인데 상품할인을 사용중인 경우
                $getData[$dataKey]['price']['goodsDcPrice'] = $goodsPriceInfo[$goodsNo]['goodsDcPrice'][$dataKey] = $this->getGoodsDcData($dataVal['goodsDiscountFl'], $dataVal['goodsDiscount'], $dataVal['goodsDiscountUnit'], $dataVal['goodsCnt'], $getData[$dataKey]['price'], $getData[$dataKey]['fixedGoodsDiscount'], $getData[$dataKey]['goodsDiscountGroup'], $getData[$dataKey]['goodsDiscountGroupMemberInfo']);
                unset($getData[$dataKey]['goodsDiscountFl'], $getData[$dataKey]['goodsDiscount'], $getData[$dataKey]['goodsDiscountUnit']);
            }

            // 마이앱 추가 할인 설정
            // 상품상세와 장바구니에서는 추가구매 할인금액을 분리해서 보여주지 않기때문에 한번에 계산
            if ($this->useMyapp) {
                $myappConfig = gd_policy('myapp.config');
                if ($myappConfig['benefit']['orderAdditionalBenefit']['isUsing'] == true) {
                    $myapp = \App::load('Component\\Myapp\\Myapp');
                    $myappBenefitParams['goodsCnt'] = $dataVal['goodsCnt'];
                    $myappBenefitParams['goodsPrice'] = $getData[$dataKey]['price']['goodsPrice'];
                    $myappBenefitParams['optionPrice'] = $getData[$dataKey]['price']['optionPrice'];
                    $myappBenefitParams['optionTextPrice'] = $getData[$dataKey]['price']['optionTextPrice'];
                    $myappBenefitParams['addGoodsPrice'] = $getData[$dataKey]['price']['addGoodsPriceSum'];
                    $myappBenefit = $myapp->getOrderAdditionalBenefit($myappBenefitParams);
                    $getData[$dataKey]['price']['myappDcPrice'] = $goodsPriceInfo[$goodsNo]['myappDcPrice'][$dataKey] = $myappBenefit['discount']['goods'];
                }
            }

            // 각 상품별 마일리지 초기화
            $getData[$dataKey]['mileage']['goodsMileage'] = 0;
            $getData[$dataKey]['mileage']['memberMileage'] = 0;
            $getData[$dataKey]['mileage']['couponGoodsMileage'] = 0;

            if ($dataVal['couponBenefitExcept'] == 'n') {
                // 상품 쿠폰 금액 및 추가 마일리지
                $getData[$dataKey]['price']['couponGoodsDcPrice'] = 0;
                $getData[$dataKey]['price']['goodsCnt'] = $dataVal['goodsCnt'];
                $getData[$dataKey]['coupon'] = [];

                if ($dataVal['memberCouponNo']) {
                    if (empty($this->goodsCouponInfo[$dataVal['memberCouponNo']]) === false) {
                        $goodsCouponInfo = &$this->goodsCouponInfo[$dataVal['memberCouponNo']];
                        $memberCouponNo = explode(INT_DIVISION, $dataVal['memberCouponNo']);

                        $tempGoodsCnt = $goodsCouponInfo['saleGoodsCnt'];
                        foreach ($memberCouponNo as $val) {
                            //상품 할인 쿠폰
                            if (empty($goodsCouponInfo['info']['memberCouponSalePrice'][$val]) === false) {
                                // 상품쿠폰 주문서페이지 변경 제한안함일 때 && 수기주문이 아닐 경우
                                if($couponPolicy['productCouponChangeLimitType'] == 'n' && $postValue['productCouponChangeLimitType'] == 'n' && $this->isWrite === false) {
                                    if($tempGoodsCnt - $dataVal['goodsCnt'] > 0) {
                                        $memberCouponSalePrice = round(($goodsCouponInfo['info']['memberCouponSalePrice'][$val] * $dataVal['goodsCnt']) / $tempGoodsCnt);
                                        $goodsCouponInfo['saleGoodsCnt'] -= $dataVal['goodsCnt'];
                                        $goodsCouponInfo['info']['memberCouponSalePrice'][$val] -= $memberCouponSalePrice;
                                    } else {
                                        $memberCouponSalePrice = $goodsCouponInfo['info']['memberCouponSalePrice'][$val];
                                    }
                                } else {
                                    if ($goodsCouponInfo['saleGoodsCnt'] - $dataVal['goodsCnt'] > 0) {
                                        $memberCouponSalePrice = round(($goodsCouponInfo['info']['memberCouponSalePrice'][$val] * $dataVal['goodsCnt']) / $goodsCouponInfo['saleGoodsCnt']);

                                        $goodsCouponInfo['saleGoodsCnt'] -= $dataVal['goodsCnt'];
                                        $goodsCouponInfo['info']['memberCouponSalePrice'][$val] -= $memberCouponSalePrice;
                                    } else {
                                        $memberCouponSalePrice = $goodsCouponInfo['info']['memberCouponSalePrice'][$val];
                                    }
                                }
                                $tmp['memberCouponAlertMsg'][$val] = $goodsCouponInfo['info']['memberCouponAlertMsg'][$val];
                                $tmp['memberCouponSalePrice'][$val] = $memberCouponSalePrice;
                            }

                            //마일리지 적립 쿠폰
                            if (empty($goodsCouponInfo['info']['memberCouponAddMileage'][$val]) === false) {
                                // 상품쿠폰 주문서페이지 변경 제한안함일 때 && 수기주문이 아닐 경우
                                if($couponPolicy['productCouponChangeLimitType'] == 'n' && $postValue['productCouponChangeLimitType'] == 'n' && $this->isWrite === false) {
                                    $memberCouponAddMileage = round(($goodsCouponInfo['info']['memberCouponAddMileage'][$val] * $dataVal['goodsCnt']) / $goodsCouponInfo['mileageGoodsCnt']);
                                } else {
                                    if ($goodsCouponInfo['mileageGoodsCnt'] - $dataVal['goodsCnt'] > 0) {
                                        $memberCouponAddMileage = round(($goodsCouponInfo['info']['memberCouponAddMileage'][$val] * $dataVal['goodsCnt']) / $goodsCouponInfo['mileageGoodsCnt']);

                                        $goodsCouponInfo['mileageGoodsCnt'] -= $dataVal['goodsCnt'];
                                        $goodsCouponInfo['info']['memberCouponAddMileage'][$val] -= $memberCouponAddMileage;
                                    } else {
                                        $memberCouponAddMileage = $goodsCouponInfo['info']['memberCouponAddMileage'][$val];
                                    }
                                }
                                $tmp['memberCouponAlertMsg'][$val] = $goodsCouponInfo['info']['memberCouponAlertMsg'][$val];
                                $tmp['memberCouponAddMileage'][$val] = $memberCouponAddMileage;
                            }
                        }
                    } else {
                        // 상품쿠폰 주문서페이지 변경 제한안함일 때
                        $coupon = \App::load('\\Component\\Coupon\\Coupon');
                        $memberCouponNo = explode(INT_DIVISION, $dataVal['memberCouponNo']);

                        foreach ($memberCouponNo as $dataCouponVal) { // 배열로 넘어오는 경우도 있어 foreach 처리
                            if ($dataCouponVal != null) {
                                $couponVal = $coupon->getMemberCouponInfo($dataCouponVal);

                                $goodsCouponForTotalPriceTemp = array();
                                foreach ($getData as $pVal) {
                                    $goodsCouponForTotalPriceTemp['goodsPriceSum'] += $pVal['price']['goodsPriceSum'];
                                    $goodsCouponForTotalPriceTemp['optionPriceSum'] += $pVal['price']['optionPriceSum'];
                                    $goodsCouponForTotalPriceTemp['optionTextPriceSum'] += $pVal['price']['optionTextPriceSum'];
                                    $goodsCouponForTotalPriceTemp['addGoodsPriceSum'] += $pVal['price']['addGoodsPriceSum'];
                                }

                                // 상품쿠폰 주문서페이지 변경 제한안함일 때
                                if (!$goodsCouponForTotalPrice || $couponVal['couponProductMinOrderType'] != 'order') {
                                    $tmp = $this->getMemberCouponPriceData($getData[$dataKey]['price'], $dataVal['memberCouponNo'], $goodsCouponForTotalPriceTemp, $isAllFl);
                                } else {
                                    // 기준 금액 주문 쿠폰적용가
                                    $tmp = $this->getMemberCouponPriceData($goodsCouponForTotalPrice, $dataVal['memberCouponNo'], $goodsCouponForTotalPriceTemp, $isAllFl);
                                    // 기준 금액 변경 전 쿠폰적용가
                                    $tmpOriginProductPrice = $this->getMemberCouponPriceData($getData[$dataKey]['price'], $dataVal['memberCouponNo']);
                                    // 쿠폰적용 가격 기존으로 대체
                                    $tmp['memberCouponSalePrice'] = $tmpOriginProductPrice['memberCouponSalePrice']; // 할인액
                                    $tmp['memberCouponAddMileage'] = $tmpOriginProductPrice['memberCouponAddMileage']; // 적립액
                                }
                            }
                        }
                    }

                    if (array_search('LIMIT_MIN_PRICE', $tmp['memberCouponAlertMsg']) === false) {
                        if (is_array($tmp['memberCouponSalePrice'])) {
                            $goodsOptCouponSalePriceSum = array_sum($tmp['memberCouponSalePrice']);
                        }
                        if (is_array($tmp['memberCouponAddMileage'])) {
                            $goodsOptCouponAddMileageSum = array_sum($tmp['memberCouponAddMileage']);
                        }
                    } else {
                        // 'LIMIT_MIN_PRICE' 일때 구매금액 제한에 걸려 사용 못하는 쿠폰 처리
                        // 수량 변경 시 구매금액 제한에 걸림
                        // 적용된 쿠폰 모두 제거
                        $goodsOptCouponSalePriceSum = 0;
                        $goodsOptCouponAddMileageSum = 0;
                        $this->setMemberCouponDelete($dataVal['sno']);
                        $getData[$dataKey]['memberCouponNo'] = 0;
                        $dataVal['memberCouponNo'] = 0;
                    }

                    $goodsPriceInfo[$goodsNo]['couponDcPrice'][$dataKey] = $getData[$dataKey]['price']['couponDcPrice'] = $goodsOptCouponSalePriceSum;
                    // 상품쿠폰 데이터 추가
                    if ($dataVal['memberCouponNo']) {
                        $coupon = \App::load('\\Component\\Coupon\\Coupon');
                        $key = $tmpCouponGoodsDcPrice = $tmpCouponGoodsMileage = 0;
                        foreach (explode(INT_DIVISION, $dataVal['memberCouponNo']) as $val) {
                            if ($val != null) {
                                $getData[$dataKey]['coupon'][$val] = $coupon->getMemberCouponInfo($val, 'c.couponNm, c.couponUseType, c.couponDescribed, c.couponSaveType, c.couponUsePeriodType, c.couponUsePeriodStartDate, c.couponUsePeriodEndDate, c.couponUsePeriodDay, c.couponUseDateLimit, c.couponBenefit, c.couponBenefitType, c.couponBenefitFixApply, c.couponKindType, c.couponApplyDuplicateType, c.couponMaxBenefit, c.couponMinOrderPrice, mc.memberCouponStartDate, mc.memberCouponEndDate, c.couponProductMinOrderType');
                                $getData[$dataKey]['coupon'][$val]['convertData'] = $coupon->convertCouponData($getData[$dataKey]['coupon'][$val]);
                                $getData[$dataKey]['coupon'][$val]['couponGoodsDcPrice'] = gd_isset($tmp['memberCouponSalePrice'][$val], 0);
                                $getData[$dataKey]['coupon'][$val]['couponGoodsMileage'] = gd_isset($tmp['memberCouponAddMileage'][$val], 0);
                                $tmpCouponGoodsDcPrice += gd_isset($tmp['memberCouponSalePrice'][$val], 0);
                                $tmpCouponGoodsMileage += gd_isset($tmp['memberCouponAddMileage'][$val], 0);
                                $key++;
                            }
                        }
                    }

                    // 쿠폰을 사용했고 사용설정에 쿠폰만 사용설정일때 처리
                    if ($tmpCouponGoodsDcPrice > 0 || $tmpCouponGoodsMileage > 0) {
                        $couponConfig = gd_policy('coupon.config');
                        if ($couponConfig['couponUseType'] == 'y' && $couponConfig['chooseCouponMemberUseType'] == 'coupon') {
                            $memberDcFl = $goodsPriceInfo[$goodsNo]['memberDcFl'][$dataKey] = false;
                            $getData[$dataKey]['price']['memberDcPrice'] = 0;
                            $getData[$dataKey]['price']['memberOverlapDcPrice'] = 0;
                            $getData[$dataKey]['mileage']['memberMileage'] = 0;
                        }
                    }

                    if ($this->channel == 'naverpay') {  //네이버페이는 쿠폰상품할인 쿠폰마일리지 적립 적용안함.
                        $getData[$dataKey]['memberCouponNo'] = 0;
                        $getData[$dataKey]['coupon'] = null;
                        $getData[$dataKey]['price']['couponGoodsDcPrice'] = 0;
                        $getData[$dataKey]['price']['couponGoodsMileage'] = 0;
                    }
                    unset($tmp);
                }
            }

            // 회원 그룹별 추가 마일리지
            if ($dataVal['memberBenefitExcept'] == 'n' && $memberDcFl == true) {
                // 회원 추가 마일리지 적립 적용 제외
                if (in_array('mileage', $exceptBenefit) === true && $exceptBenefitFl === true) {
                } else {
                    if (empty($tmpMileageInfo[$goodsNo][$optionSno]) === false) {
                        $mileageInfo = &$tmpMileageInfo[$goodsNo][$optionSno];
                        if ($mileageInfo['goodsCnt'] - $dataVal['goodsCnt'] > 0) {
                            $getData[$dataKey]['mileage']['memberMileage'] = round(($mileageInfo['memberMileage'] * $dataVal['goodsCnt']) / $mileageInfo['goodsCnt']);
                            $mileageInfo['goodsCnt'] -= $dataVal['goodsCnt'];
                            $mileageInfo['memberMileage'] -= $getData[$dataKey]['mileage']['memberMileage'];
                        } else {
                            $getData[$dataKey]['mileage']['memberMileage'] = $mileageInfo['memberMileage'];
                        }
                    } else {
                        $getData[$dataKey]['mileage']['memberMileage'] = $this->getMemberMileageData($this->_memInfo, $getData[$dataKey]['price']);
                    }
                }

                // 회원 그룹별 추가 할인 및 중복 할인
                if ($this->getChannel() != 'naverpay') {
                    if (empty($tmpMemberDcInfo[$goodsNo][$optionSno]) === false) {
                        $memberDcInfo = &$tmpMemberDcInfo[$goodsNo][$optionSno];
                        $tmp = [
                            'addDcFl' => $memberDcInfo['addDcFl'],
                            'overlapDcFl' => $memberDcInfo['overlapDcFl']
                        ];
                        if ($memberDcInfo['goodsCnt'] - $dataVal['goodsCnt'] > 0) {
                            $tmp['memberDcPrice'] = round(($memberDcInfo['memberDcPrice'] * $dataVal['goodsCnt']) / $memberDcInfo['goodsCnt']);
                            $tmp['memberOverlapDcPrice'] = round(($memberDcInfo['memberOverlapDcPrice'] * $dataVal['goodsCnt']) / $memberDcInfo['goodsCnt']);
                            $memberDcInfo['memberDcPrice'] -= $tmp['memberDcPrice'];
                            $memberDcInfo['memberOverlapDcPrice'] -= $tmp['memberOverlapDcPrice'];
                            $memberDcInfo['goodsCnt'] -= $dataVal['goodsCnt'];
                        } else {
                            $tmp['memberDcPrice'] = $memberDcInfo['memberDcPrice'];
                            $tmp['memberOverlapDcPrice'] = $memberDcInfo['memberOverlapDcPrice'];
                        }
                    } else {
                        // 브랜드 할인율
                        if ($this->_memInfo['fixedOrderTypeDc'] == 'brand') {
                            if (in_array($getData[$dataKey]['brandCd'], $this->_memInfo['dcBrandInfo']->cateCd)) {
                                $goodsBrandInfo[$getData[$dataKey]['goodsNo']][$getData[$dataKey]['brandCd']] = $getData[$dataKey]['brandCd'];
                            } else {
                                if ($getData[$dataKey]['brandCd']) {
                                    $goodsBrandInfo[$getData[$dataKey]['goodsNo']]['allBrand'] = $getData[$dataKey]['brandCd'];
                                } else {
                                    $goodsBrandInfo[$getData[$dataKey]['goodsNo']]['noBrand'] = $getData[$dataKey]['brandCd'];
                                }
                            }
                        }
                        $tmp = $this->getMemberDcPriceData($dataVal['goodsNo'], $this->_memInfo, $getData[$dataKey]['price'], $this->getMemberDcForCateCd(), $dataVal['addDcFl'], $dataVal['overlapDcFl'], $goodsBrandInfo);
                        $getData[$dataKey]['memberDcInfo'] = json_encode(array_merge($tmp, ['goodsCnt' => $dataVal['goodsCnt']]));
                    }
                    // 회원 추가 할인혜택 적용 제외
                    if (in_array('add', $exceptBenefit) === true && $exceptBenefitFl === true) {
                    } else {
                        $getData[$dataKey]['addDcFl'] = $tmp['addDcFl'];
                        $getData[$dataKey]['price']['memberDcPrice'] = $tmp['memberDcPrice'];
                    }
                    // 회원 중복 할인혜택 적용 제외
                    if (in_array('overlap', $exceptBenefit) === true && $exceptBenefitFl === true) {
                    } else {
                        $getData[$dataKey]['overlapDcFl'] = $tmp['overlapDcFl'];
                        $getData[$dataKey]['price']['memberOverlapDcPrice'] = $tmp['memberOverlapDcPrice'];
                    }
                    unset($tmp);
                }
            }

            $goodsPriceInfo[$goodsNo]['couponBenefitExcept'] = $dataVal['couponBenefitExcept'];
            $goodsPriceInfo[$goodsNo]['addDcFl'] = $getData[$dataKey]['addDcFl'] ? $getData[$dataKey]['addDcFl'] : $dataVal['addDcFl'];
            $goodsPriceInfo[$goodsNo]['overlapDcFl'] = $getData[$dataKey]['overlapDcFl'] ? $getData[$dataKey]['overlapDcFl'] : $dataVal['overlapDcFl'];
            $goodsPriceInfo[$goodsNo]['scmNo'] = $scmNo;
            $goodsPriceInfo[$goodsNo]['deliverySno'] = $deliverySno;

            //회원등급 > 브랜드별 추가할인 상품 브랜드 정보
            if ($this->_memInfo['fixedOrderTypeDc'] == 'brand') {
                if (in_array($dataVal['brandCd'], $this->_memInfo['dcBrandInfo']->cateCd)) {
                    $this->goodsBrandInfo[$goodsNo][$dataVal['brandCd']] = $dataVal['brandCd'];
                } else {
                    if ($dataVal['brandCd']) {
                        $this->goodsBrandInfo[$goodsNo]['allBrand'] = $dataVal['brandCd'];
                    } else {
                        $this->goodsBrandInfo[$goodsNo]['noBrand'] = $dataVal['brandCd'];
                    }
                }
            }

            // 상품과 추가상품의 가격비율에 따른 각각의 할인금액/적립마일리지 안분 작업
            $totalAddGoodsMemberDcPrice = 0;
            $totalAddGoodsMemberOverlapDcPrice = 0;
            $totalAddGoodsCouponGoodsDcPrice = 0;
            $totalAddGoodsGoodsMileage = 0;
            $totalAddGoodsMemberMileage = 0;
            $totalAddGoodsCouponGoodsMileage = 0;
            $tmpOriginGoodsPrice = $getData[$dataKey]['price']['goodsPriceSum'] + $getData[$dataKey]['price']['optionPriceSum'] + $getData[$dataKey]['price']['optionTextPriceSum'] + $getData[$dataKey]['price']['addGoodsPriceSum'];

            // 쿠폰할인금액이 상품결제금액 보다 큰 경우 쿠폰가격 재조정 (상품결제금액이 마이너스로 나오는 오류 수정)
            //$exceptCouponPrice = $getData[$dataKey]['price']['goodsPriceSum'] + $getData[$dataKey]['price']['optionPriceSum'] + $getData[$dataKey]['price']['optionTextPriceSum'] + $getData[$dataKey]['price']['addGoodsPriceSum'] - $getData[$dataKey]['price']['goodsDcPrice'] - $getData[$dataKey]['price']['memberDcPrice'] - $getData[$dataKey]['price']['memberOverlapDcPrice'];

            $exceptCouponPrice = $getData[$dataKey]['price']['goodsPriceSum'] - $getData[$dataKey]['price']['goodsDcPrice'];

            // 마이앱 사용에 따른 분기 처리
            if ($this->useMyapp) {
                $exceptCouponPrice -= $getData[$dataKey]['price']['myappDcPrice'];
            }

            if ($couponPolicy['couponOptPriceType'] == 'y') $exceptCouponPrice += $getData[$dataKey]['price']['optionPriceSum'];
            if ($couponPolicy['couponAddPriceType'] == 'y') $exceptCouponPrice += $getData[$dataKey]['price']['addGoodsPriceSum'];
            if ($couponPolicy['couponTextPriceType'] == 'y') $exceptCouponPrice += $getData[$dataKey]['price']['optionTextPriceSum'];
            if (empty($couponPolicy['chooseCouponMemberUseType']) === true || $couponPolicy['chooseCouponMemberUseType'] == 'all') {
                if ($exceptCouponPrice <= $goodsOptCouponSalePriceSum && $this->_memInfo['fixedRatePrice'] == 'settle') {
                    $goodsOptCouponSalePriceSum = $exceptCouponPrice;
                    unset($getData[$dataKey]['price']['memberDcPrice'], $getData[$dataKey]['price']['memberOverlapDcPrice']);
                } else {
                    $exceptCouponPrice -= $getData[$dataKey]['price']['memberDcPrice'] + $getData[$dataKey]['price']['memberOverlapDcPrice'];
                }
            }
            if ($exceptCouponPrice < $goodsOptCouponSalePriceSum && $exceptCouponPrice > 0) {
                $goodsOptCouponSalePriceSum = $exceptCouponPrice;
            }
            if ($this->channel != 'naverpay') {
                $getData[$dataKey]['price']['couponGoodsDcPrice'] = gd_isset($goodsOptCouponSalePriceSum, 0);
                $getData[$dataKey]['mileage']['couponGoodsMileage'] = gd_isset($goodsOptCouponAddMileageSum, 0);
            }

            $goodsCouponData[$dataKey] = [
                'goodsNo' => $goodsNo,
                'goodsCnt' => $dataVal['goodsCnt'],
                'goodsPrice' => $getData[$dataKey]['price'],
                'couponPrice' => $goodsOptCouponSalePriceSum,
            ];

            if ($getData[$dataKey]['addGoods'] !== null) {
                // 절사 정책
                $memberTruncPolicy = Globals::get('gTrunc.member_group');
                $couponTruncPolicy = Globals::get('gTrunc.coupon');
                $mileageTruncPolicy = Globals::get('gTrunc.mileage');

                // 쿠폰 정책
                $couponPolicy = gd_policy('coupon.config');

                foreach ($getData[$dataKey]['addGoods'] as $key => $val) {
                    // 추가상품별 할인금액 초기화
                    $getData[$dataKey]['addGoods'][$key]['addGoodsMemberDcPrice'] = 0;
                    $getData[$dataKey]['addGoods'][$key]['addGoodsMemberOverlapDcPrice'] = 0;
                    $getData[$dataKey]['addGoods'][$key]['addGoodsCouponGoodsDcPrice'] = 0;

                    // 추가상품 비율
                    $addGoodsRate = (($val['addGoodsPrice'] * $val['addGoodsCnt']) / $tmpOriginGoodsPrice);

                    // 추가상품 비율에 따른 회원 할인금액 설정
                    if ($this->_memInfo['fixedRateOption'][1] == 'goods') {
                        if ($getData[$dataKey]['addDcFl']) {
                            $getData[$dataKey]['addGoods'][$key]['addGoodsMemberDcPrice'] = 0;
                            $totalAddGoodsMemberDcPrice += $getData[$dataKey]['addGoods'][$key]['addGoodsMemberDcPrice'];
                        }
                        if ($getData[$dataKey]['overlapDcFl']) {
                            $getData[$dataKey]['addGoods'][$key]['addGoodsMemberOverlapDcPrice'] = 0;
                            $totalAddGoodsMemberOverlapDcPrice += $getData[$dataKey]['addGoods'][$key]['addGoodsMemberOverlapDcPrice'];
                        }
                    }

                    // 추가상품 비율에 따른 상품쿠폰 할인금액 설정
                    if ($couponPolicy['couponAddPriceType'] == 'y') {
                        $getData[$dataKey]['addGoods'][$key]['addGoodsCouponGoodsDcPrice'] = gd_number_figure($getData[$dataKey]['price']['couponGoodsDcPrice'] * $addGoodsRate, $couponTruncPolicy['unitPrecision'], $couponTruncPolicy['unitRound']);
                        $totalAddGoodsCouponGoodsDcPrice += $getData[$dataKey]['addGoods'][$key]['addGoodsCouponGoodsDcPrice'];
                    }

                    // 추가상품 비율에 따른 상품 적립 마일리지 설정 (금액/단위 기준설정에 따른 절사)
                    if ($this->mileageGiveInfo['basic']['addGoodsPrice'] == 1) {
                        $getData[$dataKey]['addGoods'][$key]['addGoodsGoodsMileage'] = gd_number_figure($getData[$dataKey]['mileage']['goodsMileage'] * $addGoodsRate, $mileageTruncPolicy['unitPrecision'], $mileageTruncPolicy['unitRound']);
                        $totalAddGoodsGoodsMileage += $getData[$dataKey]['addGoods'][$key]['addGoodsGoodsMileage'];
                    }

                    // 추가상품 비율에 따른 회원 적립 마일리지 설정 (금액/단위 기준설정에 따른 절사)
                    if ($this->_memInfo['fixedRateOption'][1] == 'goods') {
                        $getData[$dataKey]['addGoods'][$key]['addGoodsMemberMileage'] = gd_number_figure($getData[$dataKey]['mileage']['memberMileage'] * $addGoodsRate, $mileageTruncPolicy['unitPrecision'], $mileageTruncPolicy['unitRound']);
                        $totalAddGoodsMemberMileage += $getData[$dataKey]['addGoods'][$key]['addGoodsMemberMileage'];
                    }

                    // 추가상품 비율에 따른 쿠폰 적립 마일리지 설정 (금액/단위 기준설정에 따른 절사)
                    if ($couponPolicy['couponAddPriceType'] == 'y') {
                        $getData[$dataKey]['addGoods'][$key]['addGoodsCouponGoodsMileage'] = gd_number_figure($getData[$dataKey]['mileage']['couponGoodsMileage'] * $addGoodsRate, $mileageTruncPolicy['unitPrecision'], $mileageTruncPolicy['unitRound']);
                        $totalAddGoodsCouponGoodsMileage += $getData[$dataKey]['addGoods'][$key]['addGoodsCouponGoodsMileage'];
                    }
                }

                // 상품의 할인 비율 금액 계산 = 상품의 회원/쿠폰 할인 총 금액 - 추가상품 할인 금액
                if ($this->getChannel() != 'naverpay') {
                    $getData[$dataKey]['price']['goodsMemberDcPrice'] = ($getData[$dataKey]['price']['memberDcPrice'] - $totalAddGoodsMemberDcPrice);
                    $getData[$dataKey]['price']['goodsMemberOverlapDcPrice'] = ($getData[$dataKey]['price']['memberOverlapDcPrice'] - $totalAddGoodsMemberOverlapDcPrice);
                    $getData[$dataKey]['price']['goodsCouponGoodsDcPrice'] = ($getData[$dataKey]['price']['couponGoodsDcPrice'] - $totalAddGoodsCouponGoodsDcPrice);
                    $getData[$dataKey]['price']['addGoodsMemberDcPrice'] = $totalAddGoodsMemberDcPrice;
                    $getData[$dataKey]['price']['addGoodsMemberOverlapDcPrice'] = $totalAddGoodsMemberOverlapDcPrice;
                    $getData[$dataKey]['price']['addGoodsCouponGoodsDcPrice'] = $totalAddGoodsCouponGoodsDcPrice;

                    $getData[$dataKey]['mileage']['goodsGoodsMileage'] = ($getData[$dataKey]['mileage']['goodsMileage'] - $totalAddGoodsGoodsMileage);
                    $getData[$dataKey]['mileage']['goodsMemberMileage'] = ($getData[$dataKey]['mileage']['memberMileage'] - $totalAddGoodsMemberMileage);
                    $getData[$dataKey]['mileage']['goodsCouponGoodsMileage'] = ($getData[$dataKey]['mileage']['couponGoodsMileage'] - $totalAddGoodsCouponGoodsMileage);
                    $getData[$dataKey]['mileage']['addGoodsGoodsMileage'] = $totalAddGoodsGoodsMileage;
                    $getData[$dataKey]['mileage']['addGoodsMemberMileage'] = $totalAddGoodsMemberMileage;
                    $getData[$dataKey]['mileage']['addGoodsCouponGoodsMileage'] = $totalAddGoodsCouponGoodsMileage;
                    $getData[$dataKey]['mileage']['goodsCnt'] = $dataVal['goodsCnt'];
                }
            }
            unset($totalAddGoodsMemberDcPrice);
            unset($totalAddGoodsMemberOverlapDcPrice);
            unset($totalAddGoodsCouponGoodsDcPrice);
            unset($totalAddGoodsGoodsMileage);
            unset($totalAddGoodsMemberMileage);
            unset($totalAddGoodsCouponGoodsMileage);
            unset($goodsOptCouponSalePriceSum);
            unset($goodsOptCouponAddMileageSum);
        }

        $tmpOrderPrice = [];
        $divisionOrderCoupon = $this->getDivisionOrderCoupon($goodsPriceInfo)['goods'];

        foreach ($goodsPriceInfo as $key => $val) {
            // 상품의 단가, 합계금액 계산
            if (empty($divisionOrderCoupon[$key]) === false) $val['orderCoupon'] = $divisionOrderCoupon[$key];
            $tmp = $this->getUnitGoodsPriceData($this->_memInfo, $val);
            $tmpPrice[$key] = $tmp['tmpPrice'];
            // 상품 전체 주문금액
            $tmpOrderPrice['memberDcByPrice'] += array_sum($tmpPrice[$key]['all']['memberDcByPrice']);
            $tmpOrderPrice['couponDcPrice'][$key] += array_sum($val['couponDcPrice']);

            // 추가할인 가능시 상품전체금액 계산
            if ($val['addDcFl'] === true) {
                $tmpOrderPrice['addDcTotal']['memberDcByPrice'] += array_sum($tmpPrice[$key]['all']['memberDcByPrice']);
            }
            // 중복할인 가능시 상품전체금액 계산
            if ($val['overlapDcFl'] === true) {
                $tmpOrderPrice['overlapDcTotal']['memberDcByPrice'] += array_sum($tmpPrice[$key]['all']['memberDcByPrice']);
            }
            if (empty($tmpPrice[$key]['all']['memberDcByAddPrice']) === false) {
                foreach ($tmpPrice[$key]['all']['memberDcByAddPrice'] as $k => $v) {
                    // 추가상품 전체 주문금액
                    $tmpOrderPrice['memberDcByAddPrice'] += array_sum($v);
                    // 추가할인 가능시 추가상품전체금액 계산
                    if ($val['addDcFl'] === true) {
                        $tmpOrderPrice['addDcTotal']['memberDcByAddPrice'] += array_sum($v);
                    }
                    // 중복할인 가능시 추가상품전체금액 계산
                    if ($val['overlapDcFl'] === true) {
                        $tmpOrderPrice['overlapDcTotal']['memberDcByAddPrice'] += array_sum($v);
                    }
                }
            }
        }

        if ($this->getChannel() != 'naverpay') {
            // 회원 추가/중복할인, 마일리지 지급 재계산 (금액 기준이 상품, 주문별, 브랜드별일 경우)
            foreach ($goodsPriceInfo as $key => $val) {
                if ($val['couponBenefitExcept'] == 'n') {
                    if (in_array($this->_memInfo['fixedOrderTypeDc'], ['goods', 'order', 'brand']) === true) {
                        // 회원 추가 할인혜택 적용 제외
                        if (in_array('add', $val['exceptBenefit']) === true && $val['exceptBenefitFl'] === true) {
                        } else {
                            $addDcPrice = $this->getMemberGoodsAddDcPriceData($tmpPrice[$key], $tmpOrderPrice['addDcTotal'], $key, $this->_memInfo, $val, $this->getMemberDcForCateCd(), $val['addDcFl'], $tmpOrderPrice['couponDcPrice'], $this->goodsBrandInfo);

                            foreach ($val['goodsCnt'] as $k => $v) {
                                if ($goodsPriceInfo[$key]['memberDcFl'][$k] === false) continue;

                                $addDcPrice['info']['goods'][$k] = ($addDcPrice['info']['goods'][$k] != 0) ? $addDcPrice['info']['goods'][$k] : $getData[$k]['price']['goodsMemberDcPrice'];
                                $getData[$k]['price']['goodsMemberDcPrice'] = gd_isset($addDcPrice['info']['goods'][$k], 0);

                                if (in_array($this->_memInfo['fixedOrderTypeDc'], ['brand']) === true) {
                                    // 추가 상품 브랜드 할인율
                                    $getData[$k]['price']['goodsMemberBrandDcPrice'] = gd_isset($addDcPrice['memberBrandDcPrice']['goods'][$k], 0);
                                    unset($getData[$k]['price']['addGoodsMemberDcPrice']);
                                    foreach ($getData[$k]['addGoods'] as $tKey => $tVal) {
                                        $getData[$k]['price']['addGoodsMemberDcPrice'] += $addDcPrice['info']['addGoods'][$k][$tKey];
                                        $getData[$k]['addGoods'][$tKey]['addGoodsMemberDcPrice'] = 0;
                                    }
                                } else {
                                    $getData[$k]['price']['addGoodsMemberDcPrice'] = gd_isset(array_sum($addDcPrice['info']['addGoods'][$k]), 0);

                                    foreach ($getData[$k]['addGoods'] as $tKey => $tVal) {
                                        $getData[$k]['addGoods'][$tKey]['addGoodsMemberDcPrice'] = 0;
                                    }
                                }

                            }
                            unset($addDcPrice);
                        }
                    }
                    if (in_array($this->_memInfo['fixedOrderTypeOverlapDc'], ['goods', 'order']) === true) {
                        // 회원 중복 할인혜택 적용 제외
                        if (in_array('overlap', $val['exceptBenefit']) === true && $val['exceptBenefitFl'] === true) {
                        } else {
                            $overlapDcPrice = $this->getMemberGoodsOverlapDcPriceData($tmpPrice[$key], $tmpOrderPrice['overlapDcTotal'], $key, $this->_memInfo, $val, $this->getMemberDcForCateCd(), $val['overlapDcFl'], $tmpOrderPrice['couponDcPrice']);

                            foreach ($val['goodsCnt'] as $k => $v) {
                                if ($goodsPriceInfo[$key]['memberDcFl'][$k] === false) continue;

                                $getData[$k]['price']['goodsMemberOverlapDcPrice'] = gd_isset($overlapDcPrice['info']['goods'][$k], 0);
                                $getData[$k]['price']['addGoodsMemberOverlapDcPrice'] = gd_isset(array_sum($overlapDcPrice['info']['addGoods'][$k]), 0);

                                foreach ($getData[$k]['addGoods'] as $tKey => $tVal) {
                                    $getData[$k]['addGoods'][$tKey]['addGoodsMemberOverlapDcPrice'] = gd_isset($overlapDcPrice['info']['addGoods'][$k][$tKey], 0);
                                }
                            }
                            unset($overlapDcPrice);
                        }
                    }

                    foreach ($val['goodsCnt'] as $k => $v) {
                        $getData[$k]['price']['memberDcPrice'] = $getData[$k]['price']['goodsMemberDcPrice'] + $getData[$k]['price']['addGoodsMemberDcPrice'];
                        $getData[$k]['price']['memberOverlapDcPrice'] = $getData[$k]['price']['goodsMemberOverlapDcPrice'] + $getData[$k]['price']['addGoodsMemberOverlapDcPrice'];
                    }

                    if (in_array($this->_memInfo['fixedOrderTypeMileage'], ['goods', 'order']) === true) {
                        // 회원 추가 마일리지 적립 적용 제외
                        if (in_array('mileage', $val['exceptBenefit']) === true && $val['exceptBenefitFl'] === true) {
                        } else {
                            $memberMileage = $this->getMemberGoodsMileageData($tmpPrice[$key], $tmpOrderPrice, $this->_memInfo);

                            foreach ($val['goodsCnt'] as $k => $v) {
                                if ($goodsPriceInfo[$key]['memberDcFl'][$k] === false) continue;

                                $getData[$k]['mileage']['memberMileage'] = $getData[$k]['mileage']['goodsMemberMileage'] = gd_isset($memberMileage['goods'][$k], 0);
                                $getData[$k]['mileage']['addGoodsMemberMileage'] = gd_isset(array_sum($memberMileage['addGoods'][$k]), 0);

                                foreach ($getData[$k]['addGoods'] as $tKey => $tVal) {
                                    $getData[$k]['addGoods'][$tKey]['addGoodsMemberMileage'] = gd_isset($memberMileage['addGoods'][$k][$tKey], 0);
                                    $getData[$k]['mileage']['memberMileage'] += gd_isset($memberMileage['addGoods'][$k][$tKey], 0);
                                }
                            }
                            unset($memberMileage);
                        }
                    }
                }
            }
            unset($goodsPriceInfo, $tmpPrice, $tmpOrderPrice);
        }

        // 주문 쿠폰 안분을 getData 안으로 처리
        foreach ($divisionOrderCoupon as $goodsNo => $couponVal) {
            foreach ($couponVal['divisionOrderCouponByAddGoods'] as $addKey => $addVal) {
                if (!$getData[$addKey]['price']['couponOrderDcPrice']) {
                    $getData[$addKey]['price']['couponOrderDcPrice'] = 0;
                }
                $getData[$addKey]['price']['couponOrderDcPrice'] += $addVal;
            }
            foreach ($couponVal['divisionOrderCoupon'] as $addKey => $addVal) {
                if (!$getData[$addKey]['price']['couponOrderDcPrice']) {
                    $getData[$addKey]['price']['couponOrderDcPrice'] = 0;
                }
                $getData[$addKey]['price']['couponOrderDcPrice'] += $addVal;
            }
        }
        // 상품별 마일리지 - 상품할인 / 회원할인 / 쿠폰할인 / 모바일앱할인 이 최종 처리된 가격으로 마일리지 지급 계산
        foreach ($getData as $dataKey => $dataVal) {
            if ($dataVal['goodsMileageExcept'] == 'n') {
                $getData[$dataKey]['mileage']['goodsMileage'] = $this->getGoodsMileageData($dataVal['mileageFl'], $dataVal['mileageGoods'], $dataVal['mileageGoodsUnit'], $dataVal['goodsCnt'], $getData[$dataKey]['price'], $getData[$dataKey]['mileageGroup'], $getData[$dataKey]['mileageGroupInfo'], $getData[$dataKey]['mileageGroupMemberInfo']);
//                unset($getData[$dataKey]['mileageFl'], $getData[$dataKey]['mileageGoods'], $getData[$dataKey]['mileageGoodsUnit']);
            }
        }

        // 마일리지 재계산 - 지급률재계산 / 지급금액차감 / 지급률차감 일 경우 - 사용 마일리지가 있을 경우
        if ($this->totalUseMileage > 0) {
            if ($this->mileageGiveInfo['give']['excludeFl'] == 'r' || $this->mileageGiveInfo['give']['excludeFl'] == 'm'  || $this->mileageGiveInfo['give']['excludeFl'] == 'p') {
                // 상품종류에 따른 기준 금액
                foreach ($getData as $dataKey => $dataVal) {
                    $standardMileageGoodsPrice[$dataKey] = $dataVal['price']['goodsPriceSum'];
                    if ($this->mileageGiveInfo['basic']['optionPrice'] === '1') {
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] + $dataVal['price']['optionPriceSum'];
                    }
                    if ($this->mileageGiveInfo['basic']['addGoodsPrice'] === '1') {
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] + $dataVal['price']['addGoodsPriceSum'];
                    }
                    if ($this->mileageGiveInfo['basic']['textOptionPrice'] === '1') {
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] + $dataVal['price']['optionTextPriceSum'];
                    }

                    if ($this->mileageGiveInfo['basic']['goodsDcPrice'] === '1') {
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] - $dataVal['price']['goodsDcPrice'];

                        // 마이앱 사용에 따른 분기 처리
                        if ($this->useMyapp) {
                            $standardMileageGoodsPrice[$dataKey] -= $dataVal['price']['myappDcPrice'];
                        }
                    }

                    if ($this->mileageGiveInfo['basic']['memberDcPrice'] === '1') {
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] - $dataVal['price']['memberDcPrice'];
                    }
                    if ($this->mileageGiveInfo['basic']['couponDcPrice'] === '1') {
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] - $dataVal['price']['couponGoodsDcPrice'];
                        $standardMileageGoodsPrice[$dataKey] = $standardMileageGoodsPrice[$dataKey] - $dataVal['price']['couponOrderDcPrice'];
                    }
                }
                $totalGoodsPrice = array_sum($standardMileageGoodsPrice);
                $totalGoodsCount = count($standardMileageGoodsPrice);

                if ($this->mileageGiveInfo['give']['excludeFl'] == 'r' || $this->mileageGiveInfo['give']['excludeFl'] == 'm') {
                    // 기준 금액으로 사용 마일리지 안분
                    $totalMileage = 0;
                    $goodsCount = 1;
                    $goodsUseMileage = [];
                    foreach ($standardMileageGoodsPrice as $standardKey => $standardVal) {
                        if ($totalGoodsCount == $goodsCount) {
                            $goodsUseMileage[$standardKey] = $this->totalUseMileage - $totalMileage;
                            $totalMileage += $goodsUseMileage[$standardKey];
                        } else {
                            $percentUseMileage = $standardVal / $totalGoodsPrice;
                            $goodsUseMileage[$standardKey] = gd_number_figure($this->totalUseMileage * $percentUseMileage, $this->mileageGiveInfo['trunc']['unitPrecision'], $this->mileageGiveInfo['trunc']['unitRound']);
                            $totalMileage += $goodsUseMileage[$standardKey];
                        }
                        $goodsCount++;
                    }

                    if ($this->mileageGiveInfo['give']['excludeFl'] == 'm') { // 지급금액 차감
                        foreach ($getData as $dataKey => $dataVal) {
                            $getData[$dataKey]['mileage']['goodsUseMileage'] = $goodsUseMileage[$dataKey];
                            $getData[$dataKey]['mileage']['goodsMileage'] = $getData[$dataKey]['mileage']['goodsMileage'] - $goodsUseMileage[$dataKey];
                            if ($getData[$dataKey]['mileage']['goodsMileage'] < 0) {
                                $getData[$dataKey]['mileage']['goodsMileage'] = 0;
                            }
                        }
                    } else if ($this->mileageGiveInfo['give']['excludeFl'] == 'r') { // 지급률 재계산
                        foreach ($getData as $dataKey => $dataVal) {
                            $price['goodsPriceSum'] = $dataVal['price']['goodsPriceSum'] - $goodsUseMileage[$dataKey];
                            $price['addGoodsPriceSum'] = $dataVal['price']['addGoodsPriceSum'];
                            $price['optionPriceSum'] = $dataVal['price']['optionPriceSum'];
                            $price['optionTextPriceSum'] = $dataVal['price']['optionTextPriceSum'];
                            $price['goodsDcPrice'] = $dataVal['price']['goodsDcPrice'];
                            $price['memberDcPrice'] = $dataVal['price']['memberDcPrice'];
                            $price['memberOverlapDcPrice'] = $dataVal['price']['memberOverlapDcPrice'];
                            $price['couponDcPrice'] = $dataVal['price']['couponGoodsDcPrice'] + $dataVal['price']['couponOrderDcPrice'];

                            // 마이앱 사용에 따른 분기 처리
                            if ($this->useMyapp) {
                                $price['myappDcPrice'] = $dataVal['price']['myappDcPrice'];
                            }

                            if($dataVal['goodsMileageExcept'] == 'n'){
                                $getData[$dataKey]['mileage']['goodsMileage'] = $this->getGoodsMileageData($dataVal['mileageFl'], $dataVal['mileageGoods'], $dataVal['mileageGoodsUnit'], $dataVal['price']['goodsCnt'], $price, $dataVal['mileageGroup'], $dataVal['mileageGroupInfo'], $dataVal['mileageGroupMemberInfo']);
                            }
                            $getData[$dataKey]['mileage']['goodsUseMileage'] = $goodsUseMileage[$dataKey];
                            if ($getData[$dataKey]['mileage']['goodsMileage'] < 0) {
                                $getData[$dataKey]['mileage']['goodsMileage'] = 0;
                            }
                        }
                    }
                }
                if ($this->mileageGiveInfo['give']['excludeFl'] == 'p') { // 지급률 차감
                    $percentUseMileage = $this->totalUseMileage / $totalGoodsPrice;
                    foreach ($getData as $dataKey => $dataVal) {
                        $goodsUseMileage = gd_number_figure($getData[$dataKey]['mileage']['goodsMileage'] * $percentUseMileage, $this->mileageGiveInfo['trunc']['unitPrecision'], $this->mileageGiveInfo['trunc']['unitRound']);
                        $getData[$dataKey]['mileage']['goodsMileage'] = $getData[$dataKey]['mileage']['goodsMileage'] - $goodsUseMileage;
                        $getData[$dataKey]['mileage']['goodsUseMileage'] = $goodsUseMileage;
                        if ($getData[$dataKey]['mileage']['goodsMileage'] < 0) {
                            $getData[$dataKey]['mileage']['goodsMileage'] = 0;
                        }
                    }
                }
            }
        }


        if ($this->getChannel() != 'naverpay') {
            //장바구니의 회원 혜택 계산
            $getData = $this->getMemberGroupBenefit2($getData, $divisionOrderCoupon);
        }

        $setDeliveryData = [];
        $sameDelivery = [];
        foreach ($getData as $dataKey => $dataVal) {
            $scmNo = (int)$dataVal['scmNo']; // SCM ID
            $arrScmNo[] = $scmNo; // 장바구니 SCM 정보
            $goodsNo = $dataVal['goodsNo']; // 상품 번호
            $deliverySno = $dataVal['deliverySno']; // 배송 정책
            $taxFreeFl = $dataVal['taxFreeFl'];
            $taxPercent = $taxFreeFl == 'f' ? 0 : $dataVal['taxPercent'];

            // 주문상품 교환시 교환상품금액이 0원으로 처리되지 않게 goodsPriceString제거
            if ($this->orderGoodsChange === true) unset($dataVal['goodsPriceString']);

            //가격 대체 문구가 있는경우 합에서 제외 해야 함
            if (empty($dataVal['goodsPriceString']) === false) {
                $getData[$dataKey]['price']['goodsPriceSum'] = 0 ;
                $getData[$dataKey]['price']['optionPriceSum'] = 0 ;
                $getData[$dataKey]['price']['optionTextPriceSum'] = 0 ;
                $getData[$dataKey]['price']['addGoodsPriceSum'] = 0 ;
            }

            // 상품별 가격 (상품 가격 + 옵션 가격 + 텍스트 옵션 가격 + 추가 상품 가격)
            $getData[$dataKey]['price']['goodsPriceSubtotal'] = $getData[$dataKey]['price']['goodsPriceSum'] + $getData[$dataKey]['price']['optionPriceSum'] + $getData[$dataKey]['price']['optionTextPriceSum'] + $getData[$dataKey]['price']['addGoodsPriceSum'];



            // 상품 총 판매가격
            $this->totalPrice['goodsPrice'] += $getData[$dataKey]['price']['goodsPriceSum'];

            // 상품 총 옵션가격
            $this->totalPrice['optionPrice'] += $getData[$dataKey]['price']['optionPriceSum'];

            // 상품 총 텍스트 옵션 가격
            $this->totalPrice['optionTextPrice'] += $getData[$dataKey]['price']['optionTextPriceSum'];

            // 상품 총 추가 상품 가격
            $this->totalPrice['addGoodsPrice'] += $getData[$dataKey]['price']['addGoodsPriceSum'];

            //가격 대체 문구가 없을때만 계산해야 함
            if (empty($dataVal['goodsPriceString']) === true) {
                // 상품 총 가격 & scm 별 상품 총 가격
                $this->totalGoodsPrice += $getData[$dataKey]['price']['goodsPriceSubtotal'];
                gd_isset($this->totalScmGoodsPrice[$scmNo], 0);
                $this->totalScmGoodsPrice[$scmNo] += $getData[$dataKey]['price']['goodsPriceSubtotal'];

                // 상품 할인 총 가격 & scm 별 상품 할인 총 가격
                $this->totalGoodsDcPrice += $getData[$dataKey]['price']['goodsDcPrice'];
                gd_isset($this->totalScmGoodsDcPrice[$scmNo], 0);
                $this->totalScmGoodsDcPrice[$scmNo] += $getData[$dataKey]['price']['goodsDcPrice'];

                // 상품별 총 상품 마일리지 & scm 별 총 상품 마일리지
                $this->totalGoodsMileage += $getData[$dataKey]['mileage']['goodsMileage'];
                gd_isset($this->totalScmGoodsMileage[$scmNo], 0);
                $this->totalScmGoodsMileage[$scmNo] += $getData[$dataKey]['mileage']['goodsMileage'];

                // 회원 그룹 추가 할인 총 가격 & scm 별 회원 그룹 추가 할인 총 가격
                if ($this->getChannel() != 'naverpay') {
                    $this->totalMemberDcPrice += $getData[$dataKey]['price']['memberDcPrice'];
                    gd_isset($this->totalScmMemberDcPrice[$scmNo], 0);
                    $this->totalScmMemberDcPrice[$scmNo] += $getData[$dataKey]['price']['memberDcPrice'];

                    // 회원 그룹 중복 할인 총 가격 & scm 별 회원 그룹 중복 할인 총 가격
                    $this->totalMemberOverlapDcPrice += $getData[$dataKey]['price']['memberOverlapDcPrice'];
                    gd_isset($this->totalScmMemberOverlapDcPrice[$scmNo], 0);
                    $this->totalScmMemberOverlapDcPrice[$scmNo] += $getData[$dataKey]['price']['memberOverlapDcPrice'];

                    // 회원 그룹 브랜드 할인 총 가격
                    $this->totalMemberBrandDcPrice += $getData[$dataKey]['price']['goodsMemberBrandDcPrice'];
                }

                // 회원 그룹 총 마일리지 & scm 별 회원 그룹 총 마일리지
                $this->totalMemberMileage += $getData[$dataKey]['mileage']['memberMileage'];
                gd_isset($this->totalScmMemberMileage[$scmNo], 0);
                $this->totalScmMemberMileage[$scmNo] += $getData[$dataKey]['price']['memberMileage'];

                // 상품 총 쿠폰 금액 & scm 별 상품 총 쿠폰 금액
                $this->totalCouponGoodsDcPrice += $getData[$dataKey]['price']['couponGoodsDcPrice'];
                gd_isset($this->totalScmCouponGoodsDcPrice[$scmNo], 0);
                $this->totalScmCouponGoodsDcPrice[$scmNo] += $getData[$dataKey]['price']['couponGoodsDcPrice'];

                // 마이앱 사용에 따른 분기 처리
                if ($this->useMyapp) {
                    // 마이앱 할인 총 가격 & scm 별 마이앱 할인 총 가격
                    $this->totalMyappDcPrice += $getData[$dataKey]['price']['myappDcPrice'];
                    gd_isset($this->totalScmMyappDcPrice[$scmNo], 0);
                    $this->totalScmMyappDcPrice[$scmNo] += $getData[$dataKey]['price']['myappDcPrice'];
                }

                // 상품 총 쿠폰 마일리지 & scm 별 상품 총 쿠폰 마일리지
                $this->totalCouponGoodsMileage += $getData[$dataKey]['mileage']['couponGoodsMileage'];
                if ($this->channel == 'naverpay') {   //네이버페이는 쿠폰적립파일리지 제외
                    $this->totalCouponGoodsMileage = 0;
                }
                gd_isset($this->totalScmCouponGoodsMileage[$scmNo], 0);
                $this->totalScmCouponGoodsMileage[$scmNo] += $getData[$dataKey]['mileage']['couponGoodsMileage'];
            }

            // 할인금액을 적용한 상품별 합계금액을 위해 DC요소를 마이너스 처리 함
            if ($this->getChannel() != 'naverpay' || ($naverpayConfig['useYn'] == 'y' && $naverpayConfig['saleFl'] == 'y')) {
                $getData[$dataKey]['price']['goodsPriceSubtotal'] = $getData[$dataKey]['price']['goodsPriceSubtotal'] - $getData[$dataKey]['price']['goodsDcPrice'] - $getData[$dataKey]['price']['memberDcPrice'] - $getData[$dataKey]['price']['memberOverlapDcPrice'] - $getData[$dataKey]['price']['couponGoodsDcPrice'];

                // 마이앱 사용에 따른 분기 처리
                if ($this->useMyapp) {
                    $getData[$dataKey]['price']['goodsPriceSubtotal'] -= $getData[$dataKey]['price']['myappDcPrice'];
                }

                if($getData[$dataKey]['price']['goodsPriceSubtotal'] < 0 ) $getData[$dataKey]['price']['goodsPriceSubtotal'] = 0;
            }

            // 각 상품별 부가세 계산 (상품 가격, 옵션 가격, 텍스트 옵션을 더한 가격의 부가세 계산후, 추가 상품에 대한 부가세를 더함)
            $getData[$dataKey]['price']['goodsVat'] = gd_tax_all(($getData[$dataKey]['price']['goodsPriceSum'] + $getData[$dataKey]['price']['optionPriceSum'] + $getData[$dataKey]['price']['optionTextPriceSum']), $taxPercent, $taxFreeFl);

            // 상품별 총 공급가액 및 세액, 비과세(면세)금액 (상품 가격, 옵션 가격, 텍스트 옵션 부가세에, 추가상품에 대한 부가세를 더함) 단, 할인을 제외한 순수 상품에 대한 공급가액과 부가세를 산출
            $this->totalPriceSupply += ($getData[$dataKey]['price']['goodsVat']['supply'] + $getData[$dataKey]['price']['addGoodsVat']['supply']);
            $this->totalTaxPrice += ($getData[$dataKey]['price']['goodsVat']['tax'] + $getData[$dataKey]['price']['addGoodsVat']['tax']);
            if ($taxFreeFl == 'f') {
                $this->totalFreePrice += $getData[$dataKey]['price']['goodsPriceSubtotal'];
            }

            // 사은품 설정을 위한 데이타
            $giftConf = gd_policy('goods.gift');
            if ($giftConf['giftFl'] === 'y') {
                $this->giftForData[$goodsNo]['scmNo'] = $getData[$dataKey]['scmNo'];
                $this->giftForData[$goodsNo]['cateCd'] = $getData[$dataKey]['cateCd'];
                $this->giftForData[$goodsNo]['brandCd'] = $getData[$dataKey]['brandCd'];
                $this->giftForData[$goodsNo]['price'] = gd_isset($this->giftForData[$goodsNo]['price'], 0) + $getData[$dataKey]['price']['goodsPriceSubtotal'];
                $this->giftForData[$goodsNo]['cnt'] = gd_isset($this->giftForData[$goodsNo]['cnt'], 0) + $dataVal['goodsCnt'];
                $addGoodsCnt = gd_isset($this->giftForData[$goodsNo]['addGoodsCnt'], 0);
                if(empty($dataVal['addGoods']) == false) {
                    foreach ($dataVal['addGoods'] as $addGoodsVal) {
                        $addGoodsCnt += $addGoodsVal['addGoodsCnt'];
                    }
                }
                $this->giftForData[$goodsNo]['addGoodsCnt'] = $addGoodsCnt;
            }

            $this->multiShippingOrderInfo[$getData[$dataKey]['sno']] = [
                'scmNo' => $scmNo,
                'deliverySno' => $deliverySno,
                'getKey' => count($getCart[$scmNo][$deliverySno]),
            ];
            $getData[$dataKey]['priceInfo'] = json_encode($getData[$dataKey]['price']);
            $getData[$dataKey]['mileageInfo'] = json_encode($getData[$dataKey]['mileage']);
            if ($getData[$dataKey]['goodsDeliveryFl'] == 'y') {
                if (empty($setDeliveryData[$scmNo][$deliverySno]) === true) {
                    $setDeliveryData[$scmNo][$deliverySno] = $getData[$dataKey]['sno'];
                }
            } else if ($getData[$dataKey]['goodsDeliveryFl'] == 'n' && $getData[$dataKey]['sameGoodsDeliveryFl'] == 'y') {
                if (empty($setDeliveryData[$scmNo][$deliverySno][$dataVal['goodsNo']]) === true) {
                    $setDeliveryData[$scmNo][$deliverySno][$dataVal['goodsNo']] = $getData[$dataKey]['sno'];
                }
            }

            if ($getData[$dataKey]['goodsDeliveryFl'] == 'y') {
                $getData[$dataKey]['parentCartSno'] = $setDeliveryData[$scmNo][$deliverySno];
            } else if ($getData[$dataKey]['goodsDeliveryFl'] == 'n' && $getData[$dataKey]['sameGoodsDeliveryFl'] == 'y') {
                $getData[$dataKey]['parentCartSno'] = $setDeliveryData[$scmNo][$deliverySno][$dataVal['goodsNo']];
            } else {
                $getData[$dataKey]['parentCartSno'] = $getData[$dataKey]['sno'];
            }
            unset($getData[$dataKey]['mileage']['goodsCnt']);

            // 상품혜택관리 치환코드 생성
            $getData[$dataKey] = $goodsBenefit->goodsDataFrontReplaceCode($getData[$dataKey], 'cartOrder');

            // 장바구니 상품 정보
            $getCart[$scmNo][$deliverySno][] = $getData[$dataKey];
            if ($getData[$dataKey]['goodsDeliveryFl'] != 'y' && $getData[$dataKey]['sameGoodsDeliveryFl'] == 'y') {
                $sameDelivery['goodsNo'][$scmNo][$deliverySno][gd_isset($sameDelivery['key'][$scmNo][$deliverySno], 0)] = $getData[$dataKey]['goodsNo'];
                $sameDelivery['setKey'][$scmNo][$deliverySno][$getData[$dataKey]['goodsNo']][] = $sameDelivery['key'][$scmNo][$deliverySno];
                $sameDelivery['key'][$scmNo][$deliverySno]++;

            }

            // 장바구니 상품 개수
            $cartCnt++;

            // 장바구니 SCM 업체의 상품 갯수
            $this->cartScmGoodsCnt[$scmNo] = $this->cartScmGoodsCnt[$scmNo] + 1;
        }
        unset($getAddGoods, $getOptionText, $getData, $setDeliveryData);

        if (empty($sameDelivery['setKey']) === false) {
            $setSameCart = [];
            foreach ($sameDelivery['setKey'] as $scmNo => $sVal) {
                foreach ($sVal as $deliverySno => $dVal) {
                    foreach ($dVal as $goodsNo => $kVal) {
                        foreach ($kVal as $key => $val) {
                            $setSameCart[$scmNo][$deliverySno][] = $getCart[$scmNo][$deliverySno][$val];
                        }
                    }
                }
            }
            if (count($getCart[$scmNo][$deliverySno]) == count($setSameCart[$scmNo][$deliverySno])) {
                $getCart[$scmNo][$deliverySno] = $setSameCart[$scmNo][$deliverySno];
            }
            unset($setSameCart);
        }

        // 장바구니 상품 개수
        $this->cartCnt = $cartCnt;

        return $getCart;
    }



    private function getMemberGroupBenefit2($orderData, $orderCoupon) {
        //회원 로그인 안했으면 넘겨받은 값 그대로 리턴

        $orderData = $this->resetMemberGroupBenefit($orderData); //회원 등급 혜택 리셋

        //쿠폰 설정 중 쿠폰/회원혜택 중복적용 여부 설정 가져오기
        $couponConfig = gd_policy('coupon.config');
        if ($couponConfig['chooseCouponMemberUseType'] == 'coupon') {
            //쿠폰만 사용인 경우 주문 쿠폰을 사용 하였는지 확인
            if (!empty($orderCoupon)) {
                //주문 쿠폰을 사용 할 경우, 회원 그룹별 혜택 사용하지 않음
                return $orderData;
            }
            //상품 쿠폰을 사용 한 경우, 상품 쿠폰을 사용 한 상품의 장바구니 일련번호 저장
            foreach ($orderData as $orderDataKey => $orderDataValue) {
                if (!empty($orderDataValue['coupon'])) {
                    $goodsCouponGoods[] = $orderDataValue['sno']; //쿠폰 사용한 상품 목록
                }
            }
        }

        //타임세일 설정 가져오기
        $timeSale = \App::load('\\Component\\Promotion\\TimeSale');

        //주문하는 상품을 기준으로 반복
        foreach ($orderData as $orderDataKey => $orderDataValue) {
            //쿠폰 설정 중 쿠폰/회원혜택 중복적용이 쿠폰만 사용이며, 상품 쿠폰을 사용 한경우 할인 하지 않음
            if (in_array($orderDataValue['sno'], $goodsCouponGoods)) continue;

            //타임세일 설정 중 회원등급 혜택 적용 여부 설정
            $timeSaleInfo = $timeSale->getGoodsTimeSale($orderDataValue['goodsNo']);
            if ($timeSaleInfo['memberDcFl'] == 'n') continue;

            //구매금액 기준으로 잡을 항목(옵션가, 추가상품가, 텍스트옵션가)
            $basePrice = $orderDataValue['price']['goodsPriceSum']; //상품가
            if (in_array('option',  $this->_memInfo['fixedRateOption'])) $basePrice += $orderDataValue['price']['optionPriceSum']; //상품가
            if (in_array('goods',   $this->_memInfo['fixedRateOption'])) $basePrice += $orderDataValue['price']['addGoodsPriceSum']; //추가상품가
            if (in_array('text',    $this->_memInfo['fixedRateOption'])) $basePrice += $orderDataValue['price']['optionTextPriceSum']; //텍스트옵션가

            //할인시 절사기준 가져오기
            $memberTruncPolicy = Globals::get('gTrunc.member_group');

            //할인/적립 시 적용 금액 기준 (판매금액, 결제금액)
            if ($this->_memInfo['fixedRatePrice'] == 'settle') {
                //결제금액일 경우, 기준 금액에서 상품쿠폰 할인금액, 주문쿠폰 할인 금액, 상품 할인 금액을 빼준다.
                foreach ($orderDataValue['coupon'] as $orderDataValueCouponValue) {
                    $basePrice -= $orderDataValueCouponValue['couponGoodsDcPrice']; //상품쿠폰 할인금액
                }

                $basePrice -= $orderCoupon[$orderDataValue['goodsNo']]['divisionOrderCoupon'][$orderDataKey]; //상품쿠폰 할인금액
                //추가상품이 있는지 확인
                if (!empty($orderDataValue['addGoods'])) {
                    $basePrice -= $orderCoupon[$orderDataValue['goodsNo']]['divisionOrderCouponByAddGoods'][$orderDataKey]; //상품쿠폰(추가상품) 할인금액
                }

                $basePrice -= $orderDataValue['price']['goodsDcPrice']; //상품 할인금액
            }

            //기준 금액 정리
            $basePriceArr['option'][$orderDataValue['sno']] = $basePrice; //옵션별 임시 저장
            $basePriceArr['goods'][$orderDataValue['goodsNo']] += $basePrice; //상품별 임시 저장
            $basePriceArr['order'] += $basePrice; //주문별 임시 저장
            $basePriceArr['brand'][$orderDataValue['brandCd']] += $basePrice - $orderDataValue['price']['addGoodsPriceSum']; //브랜드별 임시 저장
            if ($orderDataValue['price']['addGoodsPriceSum'] > 0) {
                $basePriceArr['brand'][''] += $orderDataValue['price']['addGoodsPriceSum']; //브랜드(추가상품) 임시 저장
            }

            //추가 할인 적용 제외할 상품 목록 만들기
            {
                //특정 공급사
                if (in_array('scm', $this->_memInfo['dcExOption'])) {
                    if (in_array($orderDataValue['scmNo'], $this->_memInfo['dcExScm'])) {
                        $exceptGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                        $exceptGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                        $exceptGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                        $exceptGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                    }
                }
                //특정 카테고리
                if (in_array('category', $this->_memInfo['dcExOption'])) {
                    foreach ($orderDataValue['cateAllCd'] as $orderDataValueCateAllCdValue) {
                        if (in_array($orderDataValueCateAllCdValue['cateCd'], $this->_memInfo['dcExCategory'])) {
                            $exceptGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                            $exceptGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                            $exceptGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                            $exceptGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                        }
                    }
                }
                //특정 브랜드
                if (in_array('brand', $this->_memInfo['dcExOption'])) {
                    if (in_array($orderDataValue['brandCd'], $this->_memInfo['dcExBrand'])) {
                        $exceptGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                        $exceptGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                        $exceptGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                        $exceptGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                    }
                }
                //특정 상품
                if (in_array('goods', $this->_memInfo['dcExOption'])) {
                    if (in_array($orderDataValue['goodsNo'], $this->_memInfo['dcExGoods'])) {
                        $exceptGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                        $exceptGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                        $exceptGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                        $exceptGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                    }
                }
                //상품 개별 설정에서 제외 설정 시
                //상품별 회원 할인 혜택 제외 설정 가져오기
                $tmpExceptBenefit[$orderDataKey] = explode(STR_DIVISION, $orderDataValue['exceptBenefit']);
                //상품별 그룹 혜택 적용 제외 여부
                if (in_array('add', $tmpExceptBenefit[$orderDataKey]) && empty($exceptGoodsDc[$orderDataValue['sno']])) {
                    if ($orderDataValue['exceptBenefitGroup'] == 'all') {
                        $exceptGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                        $exceptGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                        $exceptGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                        $exceptGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                    } else if ($orderDataValue['exceptBenefitGroup'] == 'group') {
                        $tmpExceptBenefitGroupInfo = explode(INT_DIVISION, $orderDataValue['exceptBenefitGroupInfo']);
                        if (in_array($this->_memInfo['groupSno'], $tmpExceptBenefitGroupInfo)) {
                            $exceptGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                            $exceptGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                            $exceptGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                            $exceptGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                        }
                        unset($tmpExceptBenefitGroupInfo);
                    }
                }
            }

            //중복 할인 적용 할 상품
            {
                //상품 개별 설정에서 제외 설정 시
                //상품별 회원 할인 혜택 제외 설정 가져오기
                $tmpExceptBenefit[$orderDataKey] = explode(STR_DIVISION, $orderDataValue['exceptBenefit']);
                //상품별 그룹 혜택 적용 제외 여부
                $tmpExcept = false;
                if (in_array('overlap', $tmpExceptBenefit[$orderDataKey])) {
                    if ($orderDataValue['exceptBenefitGroup'] == 'all') {
                        $tmpExcept = true;
                    } else if ($orderDataValue['exceptBenefitGroup'] == 'group') {
                        $tmpExceptBenefitGroupInfo = explode(INT_DIVISION, $orderDataValue['exceptBenefitGroupInfo']);
                        if (in_array($this->_memInfo['groupSno'], $tmpExceptBenefitGroupInfo)) {
                            $tmpExcept = true;
                        }
                        unset($tmpExceptBenefitGroupInfo);
                    }
                }

                if ($tmpExcept === false) {
                    //특정 공급사
                    if (in_array('scm', $this->_memInfo['overlapDcOption'])) {
                        if (in_array($orderDataValue['scmNo'], $this->_memInfo['overlapDcScm'])) {
                            $overlapDcGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                            $overlapDcGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                            $overlapDcGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                            $overlapDcGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                        }
                    }
                    //특정 카테고리
                    if (in_array('category', $this->_memInfo['overlapDcOption'])) {
                        foreach ($orderDataValue['cateAllCd'] as $orderDataValueCateAllCdValue) {
                            if (in_array($orderDataValueCateAllCdValue['cateCd'], $this->_memInfo['overlapDcCategory'])) {
                                $overlapDcGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                                $overlapDcGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                                $overlapDcGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                                $overlapDcGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                            }
                        }
                    }
                    //특정 브랜드
                    if (in_array('brand', $this->_memInfo['overlapDcOption'])) {
                        if (in_array($orderDataValue['brandCd'], $this->_memInfo['overlapDcBrand'])) {
                            $overlapDcGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                            $overlapDcGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                            $overlapDcGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                            $overlapDcGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                        }
                    }

                    //특정 상품
                    if (in_array('goods', $this->_memInfo['overlapDcOption'])) {
                        if (in_array($orderDataValue['goodsNo'], $this->_memInfo['overlapDcGoods'])) {
                            $overlapDcGoodsDc[$orderDataValue['sno']]['price'] = $basePrice;
                            $overlapDcGoodsDc[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                            $overlapDcGoodsDc[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                            $overlapDcGoodsDc[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                        }
                    }
                }
            }

            //마일리지 적용 제외 상품
            {
                //상품 개별 설정에서 제외 설정 시
                //상품별 회원 할인 혜택 제외 설정 가져오기
                $tmpExceptBenefit[$orderDataKey] = explode(STR_DIVISION, $orderDataValue['exceptBenefit']);
                //상품별 그룹 혜택 적용 제외 여부
                if (in_array('mileage', $tmpExceptBenefit[$orderDataKey])) {
                    if ($orderDataValue['exceptBenefitGroup'] == 'all') {
                        $exceptGoodsMileage[$orderDataValue['sno']]['price'] = $basePrice;
                        $exceptGoodsMileage[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                        $exceptGoodsMileage[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                        $exceptGoodsMileage[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                    } else if ($orderDataValue['exceptBenefitGroup'] == 'group') {
                        $tmpExceptBenefitGroupInfo = explode(INT_DIVISION, $orderDataValue['exceptBenefitGroupInfo']);
                        if (in_array($this->_memInfo['groupSno'], $tmpExceptBenefitGroupInfo)) {
                            $exceptGoodsMileage[$orderDataValue['sno']]['price'] = $basePrice;
                            $exceptGoodsMileage[$orderDataValue['sno']]['goodsNo'] = $orderDataValue['goodsNo'];
                            $exceptGoodsMileage[$orderDataValue['sno']]['cateAllCd'] = $orderDataValue['cateAllCd'];
                            $exceptGoodsMileage[$orderDataValue['sno']]['brandCd'] = $orderDataValue['brandCd'];
                        }
                        unset($tmpExceptBenefitGroupInfo);
                    }
                }
            }

            //변수 재설정
            unset($basePrice);
        }

        //추가할인 방법 (옵션별, 상품별, 주문별, 브랜드별)
        //기준 금액 추가할인으로 재 계산
        $addDcBasePriceArr = $basePriceArr;
        foreach ($exceptGoodsDc as $exceptGoodsDcKey => $exceptGoodsDcValue) {
            //옵션별 금액 보정
            $addDcBasePriceArr['option'][$exceptGoodsDcKey] -= $exceptGoodsDcValue['price'];
            //상품별
            $addDcBasePriceArr['goods'][$exceptGoodsDcValue['goodsNo']] -= $exceptGoodsDcValue['price'];
            //주문별
            $addDcBasePriceArr['order'] -= $exceptGoodsDcValue['price'];
            //브랜드별
            $addDcBasePriceArr['brand'][$exceptGoodsDcValue['brandCd']] -= $exceptGoodsDcValue['price'];
        }

        $resultAddDcPriceArr = []; ///건별 추가 할인액
        foreach ($orderData as $orderDataKey => $orderDataValue) {
            $percent = $this->_memInfo['dcPercent'] / 100;
            switch ($this->_memInfo['fixedOrderTypeDc']) {
                case 'option':
                    //옵션별일 경우
                    if ($this->_memInfo['dcLine'] <= $addDcBasePriceArr['option'][$orderDataValue['sno']]) {
                        $tmpPrice = $addDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultAddDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultAddDcPriceArr[$orderDataValue['sno']] = $resultAddDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'goods':
                    //상품별일 경우
                    if ($this->_memInfo['dcLine'] <= $addDcBasePriceArr['goods'][$orderDataValue['goodsNo']]) {
                        $tmpPrice = $addDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultAddDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultAddDcPriceArr[$orderDataValue['sno']] = $resultAddDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'order':
                    //주문별일 경우
                    if ($this->_memInfo['dcLine'] <= $addDcBasePriceArr['order']) {
                        $tmpPrice = $addDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultAddDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultAddDcPriceArr[$orderDataValue['sno']] = $resultAddDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'brand':
                    //브랜드별일 경우
                    //브랜드별 할인율 설정
                    $percent = 0; // 브랜드별 할인율을 다시 설정 하기 위해 할인율 리셋
                    foreach ($this->_memInfo['dcBrandInfo']->cateCd as $dcBrandInfoKey => $dcBrandInfoVal) {
                        if ($orderDataValue['brandCd'] == $dcBrandInfoVal) {
                            $percent = ($this->_memInfo['dcBrandInfo']->goodsDiscount[$dcBrandInfoKey]);
                        } else if ($dcBrandInfoVal == 'noBrand' && $orderDataValue['brandCd'] == '') {
                            $percent = ($this->_memInfo['dcBrandInfo']->goodsDiscount[$dcBrandInfoKey]);
                        } else if ($dcBrandInfoVal == 'allBrand' && !in_array($orderDataValue['brandCd'], $this->_memInfo['dcBrandInfo']->cateCd) && $orderDataValue['brandCd'] != '') {
                            $percent = ($this->_memInfo['dcBrandInfo']->goodsDiscount[$dcBrandInfoKey]);
                        }
                    }
                    foreach ($this->_memInfo['dcBrandInfo']->cateCd as $dcBrandInfoKey => $dcBrandInfoVal) {
                        if ($dcBrandInfoVal == 'noBrand') {
                            $addGoodsPercent = ($this->_memInfo['dcBrandInfo']->goodsDiscount[$dcBrandInfoKey]);
                        }
                    }
                    $percent = $percent / 100;
                    $addGoodsPercent = $addGoodsPercent / 100;
                    if ($this->_memInfo['dcLine'] <= $addDcBasePriceArr['brand'][$orderDataValue['brandCd']]) {
                        $tmpPrice = $addDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultAddDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultAddDcPriceArr[$orderDataValue['sno']] = $resultAddDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }

                    //추가상품이 있다면
                    if ($orderDataValue['price']['addGoodsPriceSum'] > 0) {
                        if ($this->_memInfo['dcLine'] <= $addDcBasePriceArr['brand']['']) {
                            $resultAddDcPriceArr[$orderDataValue['sno']] += gd_number_figure(($addGoodsPercent * $orderDataValue['price']['addGoodsPriceSum']), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        }
                    }
                    break;
            }
        }

        //중복할인 기준 (옵션별, 상품별, 주문별)
        //기준 금액 중복할인으로 재 계산
        $overlapDcBasePriceArr = [];
        foreach ($overlapDcGoodsDc as $overlapDcGoodsDcKey => $overlapDcGoodsDcValue) {
            //옵션별 금액 보정
            $overlapDcBasePriceArr['option'][$overlapDcGoodsDcKey] += $overlapDcGoodsDcValue['price'];
            //상품별
            $overlapDcBasePriceArr['goods'][$overlapDcGoodsDcValue['goodsNo']] += $overlapDcGoodsDcValue['price'];
            //주문별
            $overlapDcBasePriceArr['order'] += $overlapDcGoodsDcValue['price'];
        }

        $resultOverlapDcPriceArr = []; ///건별 중복 할인액
        foreach ($orderData as $orderDataKey => $orderDataValue) {
            $percent = $this->_memInfo['overlapDcPercent'] / 100;
            switch ($this->_memInfo['fixedOrderTypeOverlapDc']) {
                case 'option':
                    //옵션별일 경우
                    if ($this->_memInfo['overlapDcLine'] <= $overlapDcBasePriceArr['option'][$orderDataValue['sno']]) {
                        $tmpPrice = $overlapDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultOverlapDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultOverlapDcPriceArr[$orderDataValue['sno']] = $resultOverlapDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'goods':
                    //상품별일 경우
                    if ($this->_memInfo['overlapDcLine'] <= $overlapDcBasePriceArr['goods'][$orderDataValue['goodsNo']]) {
                        $tmpPrice = $overlapDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultOverlapDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultOverlapDcPriceArr[$orderDataValue['sno']] = $resultOverlapDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'order':
                    //주문별일 경우
                    if ($this->_memInfo['overlapDcLine'] <= $overlapDcBasePriceArr['order']) {
                        $tmpPrice = $overlapDcBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultOverlapDcPriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $memberTruncPolicy['unitPrecision'], $memberTruncPolicy['unitRound']);;
                        $resultOverlapDcPriceArr[$orderDataValue['sno']] = $resultOverlapDcPriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
            }
        }

        //추가 마일리지 적립 절사 기준 가져오기
        $mileageTruncPolicy = Globals::get('gTrunc.mileage');

        //추가 마일리지 적립 방법
        //기준 금액 추가할인으로 재 계산
        $mileageBasePriceArr = $basePriceArr;
        foreach ($exceptGoodsMileage as $exceptGoodsMileageKey => $exceptGoodsMileageValue) {
            //옵션별 금액 보정
            $mileageBasePriceArr['option'][$exceptGoodsMileageKey] -= $exceptGoodsMileageValue['price'];
            //상품별
            $mileageBasePriceArr['goods'][$exceptGoodsMileageValue['goodsNo']] -= $exceptGoodsMileageValue['price'];
            //주문별
            $mileageBasePriceArr['order'] -= $exceptGoodsMileageValue['price'];
            //브랜드별
            $mileageBasePriceArr['brand'][$exceptGoodsMileageValue['brandCd']] -= $exceptGoodsMileageValue['price'];
        }

        $resultMileagePriceArr = []; ///건별 마일리지 지급액
        foreach ($orderData as $orderDataKey => $orderDataValue) {
            $percent = $this->_memInfo['mileagePercent'] / 100;
            switch ($this->_memInfo['fixedOrderTypeMileage']) {
                case 'option':
                    //옵션별일 경우
                    if ($this->_memInfo['mileageLine'] <= $mileageBasePriceArr['option'][$orderDataValue['sno']]) {
                        $tmpPrice = $mileageBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultMileagePriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $mileageTruncPolicy['unitPrecision'], $mileageTruncPolicy['unitRound']);;
                        $resultMileagePriceArr[$orderDataValue['sno']] = $resultMileagePriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'goods':
                    //상품별일 경우
                    if ($this->_memInfo['mileageLine'] <= $mileageBasePriceArr['goods'][$orderDataValue['goodsNo']]) {
                        $tmpPrice = $mileageBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultMileagePriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $mileageTruncPolicy['unitPrecision'], $mileageTruncPolicy['unitRound']);;
                        $resultMileagePriceArr[$orderDataValue['sno']] = $resultMileagePriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
                case 'order':
                    //주문별일 경우
                    if ($this->_memInfo['mileageLine'] <= $mileageBasePriceArr['order']) {
                        $tmpPrice = $mileageBasePriceArr['option'][$orderDataValue['sno']] / $orderDataValue['goodsCnt'];
                        $resultMileagePriceArr[$orderDataValue['sno']] = gd_number_figure(($percent * $tmpPrice), $mileageTruncPolicy['unitPrecision'], $mileageTruncPolicy['unitRound']);;
                        $resultMileagePriceArr[$orderDataValue['sno']] = $resultMileagePriceArr[$orderDataValue['sno']] * $orderDataValue['goodsCnt'];
                    }
                    break;
            }
        }

        foreach ($orderData as $orderDataKey => $orderDataValue) {
            $orderData[$orderDataKey]['price']['memberDcPrice'] = $resultAddDcPriceArr[$orderDataValue['sno']];
            $orderData[$orderDataKey]['price']['memberOverlapDcPrice'] = $resultOverlapDcPriceArr[$orderDataValue['sno']];
            $orderData[$orderDataKey]['price']['goodsMemberDcPrice'] = $resultAddDcPriceArr[$orderDataValue['sno']];
            $orderData[$orderDataKey]['price']['goodsMemberOverlapDcPrice'] = $resultOverlapDcPriceArr[$orderDataValue['sno']];
            $orderData[$orderDataKey]['price']['addGoodsMemberDcPrice'] = 0;
            $orderData[$orderDataKey]['price']['addGoodsMemberOverlapDcPrice'] = 0;

            $orderData[$orderDataKey]['mileage']['memberMileage'] = $resultMileagePriceArr[$orderDataValue['sno']];
            $orderData[$orderDataKey]['mileage']['goodsMemberMileage'] = $resultMileagePriceArr[$orderDataValue['sno']];
            $orderData[$orderDataKey]['mileage']['addGoodsMemberMileage'] = 0;

            $orderData[$orderDataKey]['memberDcInfo'] = '';
        }

        return $orderData;
    }

    private function resetMemberGroupBenefit($orderData){
        foreach ($orderData as $orderDataKey => $orderDataValue) {
            $orderData[$orderDataKey]['price']['memberDcPrice'] = 0;
            $orderData[$orderDataKey]['price']['memberOverlapDcPrice'] = 0;
            $orderData[$orderDataKey]['price']['goodsMemberDcPrice'] = 0;
            $orderData[$orderDataKey]['price']['goodsMemberOverlapDcPrice'] = 0;
            $orderData[$orderDataKey]['price']['addGoodsMemberDcPrice'] = 0;
            $orderData[$orderDataKey]['price']['addGoodsMemberOverlapDcPrice'] = 0;

            $orderData[$orderDataKey]['mileage']['memberMileage'] = 0;
            $orderData[$orderDataKey]['mileage']['goodsMemberMileage'] = 0;
            $orderData[$orderDataKey]['mileage']['addGoodsMemberMileage'] = 0;

            $orderData[$orderDataKey]['memberDcInfo'] = '';
        }
        return $orderData;
    }



    public function setTempCart($arrData) {

        $memNo = \Session::get('member.memNo');

        $cartSno = [];

        foreach ($arrData['optionSno'] as $key => $val) {
            $sql = "INSERT INTO wm_subscription_cart3 SET memNo='" . $memNo . "', goodsNo='" . $arrData['goodsNo'] . "', optionSno = '" . $val . "', goodsCnt = '" . $arrData['goodsCnt'][$val] . "', deliveryCollectFl = 'pre', deliveryMethodFl = 'delivery', regStamp = NOW()";
            $this->db->fetch($sql);
            $cartSno[] = $this->db->insert_id();
        }

        return $cartSno;
    }

    public function removeTempCart($arrIdx) {
       foreach ($arrIdx['cartSno'] as $key => $val) {
           $sql = "DELETE FROM wm_subscription_cart3 WHERE idx = '" . $val . "'";
           $this->db->query($sql);
       }
    }
}