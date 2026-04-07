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
namespace Component\Order;

use App;
use Component\Naver\NaverPay;
use Component\Database\DBTableField;
use Component\Member\Util\MemberUtil;
use Exception;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Utility\StringUtils;
use Globals;
use Request;
use Session;

/**
 * 주문 class
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class Order extends \Bundle\Component\Order\Order
{
    /**
     * @var array 현재 상태에 대한 변경 가능 상태 기준표
     */
    public $deliveryStatusStandardCode = [];

    /**
     * 해당 주문번호 상품상세내역 출력
     * 추가상품 및 옵션정보등 모든 정보를 배열형태로 담는다.
     * 간단하게 상품정보만 가져와서 사용할 것이라면 self::getOrderGoods() 를 사용하면 된다.
     *
     * @param integer $orderNo       주문 번호
     * @param mixed   $orderGoodsNo  특정 주문상품만 출력
     * @param string  $handleSno     특정 취소코드만 출력
     * @param string  $userHandleSno 특정 반품/교환/환불코드만 출력
     * @param string  $status        상태값 (admin, user, null)
     * @param boolean $scmFl         scm별 출력 여부 (기본 true)
     * @param boolean $stockFl       재고 출력 여부 (기본 false)
     * @param string  $statusMode    반품/교환/환불 상태 모드 값 (r, b, e)
     * @param array   $excludeStatus 제외할 주문상태 값
     * @param boolean $mailFl 메일발송데이터 여부
     * @param boolean $useMultiShippingKey 데이터 배열의 key를 복수배송지의 키 (order info sno)로 사용할 것인지에 대한 flat값
     *
     * @return array 해당 주문 상품 정보
     */

    public function __construct()
    {   
        parent::__construct();
        // 결제완료
        $this->deliveryStatusStandardCode['p'] = [
            'p',
            'g',
            'd',
            's',
            'r',
        ];

        // 상품준비중
        $this->deliveryStatusStandardCode['g'] = [
            'p',
            'g',
            'd',
            's',
            'r',
        ];

        // 배송 (중/완료)
        $this->deliveryStatusStandardCode['d'] = [
            'p',
            'g',
            'd',
            's',
        ];

        // 구매확정
        $this->deliveryStatusStandardCode['s'] = [
            'p',
            'g',
            'd',
            's',
        ];

        // 환불완료
        $this->deliveryStatusStandardCode['r'] = [
            'p',
            'g',
        ];
    }

    public function getOrderGoodsData($orderNo, $orderGoodsNo = null, $handleSno = null, $userHandleSno = null, $status = null, $scmFl = true, $stockFl = false, $statusMode = null, $excludeStatus = null, $mailFl = false, $useMultiShippingKey = false)
    {
        $orderData = $this->getOrderData($orderNo);
        $arrExclude = [];
        $arrIncludeOg = [
            'apiOrderGoodsNo',
            'mallSno',
            'orderStatus',
            'invoiceCompanySno',
            'invoiceNo',
            'orderCd',
            'orderGroupCd',
            'userHandleSno',
            'handleSno',
            'orderDeliverySno',
            'goodsType',
            'parentMustFl',
            'parentGoodsNo',
            'goodsNo',
            'goodsCd',
            'goodsNm',
            'goodsNmStandard',
            'goodsCnt',
            'goodsPrice',
            'costPrice',
            'taxSupplyGoodsPrice',
            'taxVatGoodsPrice',
            'taxFreeGoodsPrice',
            'realTaxSupplyGoodsPrice',
            'realTaxVatGoodsPrice',
            'realTaxFreeGoodsPrice',
            'divisionUseDeposit',
            'divisionUseMileage',
            'divisionGoodsDeliveryUseDeposit',
            'divisionGoodsDeliveryUseMileage',
            'divisionCouponOrderDcPrice',
            'divisionCouponOrderMileage',
            'addGoodsPrice',
            'optionPrice',
            'optionCostPrice',
            'optionTextPrice',
            'goodsDcPrice',
            'memberDcPrice',
            'memberOverlapDcPrice',
            'couponGoodsDcPrice',
            'goodsDeliveryCollectPrice',
            'goodsMileage',
            'memberMileage',
            'couponGoodsMileage',
            'goodsDeliveryCollectFl',
            'minusDepositFl',
            'minusRestoreDepositFl',
            'minusMileageFl',
            'minusRestoreMileageFl',
            'plusMileageFl',
            'plusRestoreMileageFl',
            'couponMileageFl',
            'optionSno',
            'optionInfo',
            'optionTextInfo',
            'goodsTaxInfo',
            'checkoutData',
            'timeSaleFl',
            'statisticsOrderFl',
            'statisticsGoodsFl',
            'deliveryMethodFl',
            'deliveryScheduleFl',
            'deliveryDt',
            'paymentDt',
            'deliveryCompleteDt',
            'finishDt',
            'taxVatGoodsPrice',
            'hscode',
            'brandCd',
            'goodsModelNo',
            'cancelDt',
            'goodsTaxInfo',
            'makerNm',
            'deliveryCompleteDt',
            'commission',
            'enuri',
            'goodsDiscountInfo',
            'goodsMileageAddInfo',
            'visitAddress',
            'isComponentGoods',
            'addedGoodsPrice',
        ];
        $arrIncludeG = [
            'cateCd',
            'imagePath',
            'imageStorage',
            'stockFl',
            'goodsSellFl',
            'goodsSellMobileFl',
        ];
        $arrIncludeGi = [
            'imageSize',
            'imageName',
            'imageRealSize',
        ];
        $arrIncludeSm = [
            'scmNo',
            'companyNm',
        ];

        $arrIncludeOh = [
            'beforeStatus',
            'handleMode',
            'handleCompleteFl',
            'handleReason',
            'handleDetailReason',
            'handleDetailReasonShowFl',
            'handleDt',
            'refundGroupCd',
            'refundMethod',
            'refundBankName',
            'refundAccountNumber',
            'refundDepositor',
            'refundPrice',
            'refundUseDeposit',
            'refundUseMileage',
            'refundDeliveryUseDeposit',
            'refundDeliveryUseMileage',
            'refundDeliveryCharge',
            'refundGiveMileage',
            'refundCharge',
            'refundUseDepositCommission',
            'refundUseMileageCommission',
            'refundDeliveryInsuranceFee',
            'completeCashPrice',
            'completePgPrice',
            'completeDepositPrice',
            'completeMileagePrice',
            'refundDeliveryCoupon',
            'handleGroupCd',
        ];
        $arrIncludeOuh = [
            'sno',
            'userHandleMode',
            'userHandleFl',
            'userHandleGoodsNo',
            'userHandleGoodsCnt',
            'userRefundMethod',
            'userRefundBankName',
            'userRefundAccountNumber',
            'userRefundDepositor',
            'userHandleReason',
            'userHandleDetailReason',
            'adminHandleReason',
        ];
        $arrIncludeOd = [
            'deliverySno',
            'deliveryCharge',
            'taxSupplyDeliveryCharge',
            'taxVatDeliveryCharge',
            'taxFreeDeliveryCharge',
            'realTaxSupplyDeliveryCharge',
            'realTaxVatDeliveryCharge',
            'realTaxFreeDeliveryCharge',
            'deliveryPolicyCharge',
            'deliveryAreaCharge',
            'divisionDeliveryUseDeposit',
            'divisionDeliveryUseMileage',
            'divisionDeliveryCharge',
            'divisionMemberDeliveryDcPrice',
            'deliveryInsuranceFee',
            'deliveryMethod',
            'deliveryWeightInfo',
            'deliveryTaxInfo',
            'goodsDeliveryFl',
            'orderInfoSno',
            'deliveryPolicy',
        ];
        $arrIncludeM = [
            'managerId',
            'managerNm',
        ];
        $arrIncludeO = [
            'memNo',
            'orderChannelFl',
            'apiOrderNo',
            'mileageGiveExclude',
            'totalMemberDeliveryDcPrice',
            'multiShippingFl',
        ];

        // 마이앱 사용에 따른 분기 처리
        if ($this->useMyapp) {
            array_push($arrIncludeOg, 'myappDcPrice');
        }

        $tmpField[] = DBTableField::setTableField('tableOrderGoods', $arrIncludeOg, $arrExclude, 'og');
        $tmpField[] = DBTableField::setTableField('tableOrderDelivery', $arrIncludeOd, ['scmNo'], 'od');
        $tmpField[] = DBTableField::setTableField('tableOrderHandle', $arrIncludeOh, null, 'oh');
        $tmpField[] = DBTableField::setTableField('tableOrderUserHandle', $arrIncludeOuh, null, 'ouh');
        $tmpField[] = DBTableField::setTableField('tableGoods', $arrIncludeG, null, 'g');
        $tmpField[] = DBTableField::setTableField('tableGoodsImage', $arrIncludeGi, null, 'gi');
        $tmpField[] = DBTableField::setTableField('tableScmManage', $arrIncludeSm, null, 'sm');

        $tmpField[] = DBTableField::setTableField('tableManager', $arrIncludeM, null, 'm');
        $tmpField[] = DBTableField::setTableField('tableOrder', $arrIncludeO, null, 'o');

        if(gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true && gd_is_provider() === false) {
            $arrIncludePu = [
                'purchaseNo',
                'purchaseNm',
            ];
            $tmpField[] = DBTableField::setTableField('tablePurchase', $arrIncludePu, null, 'pu');
        }

        //복수배송지 사용시
        if($useMultiShippingKey === true){
            $arrIncludeOi = [
                'receiverName',
                'receiverZonecode',
                'receiverZipcode',
                'receiverAddress',
                'receiverAddressSub',
                'receiverPhone',
                'receiverCellPhone',
                'orderInfoCd',
            ];
            $tmpField[] = DBTableField::setTableField('tableOrderInfo', $arrIncludeOi, null, 'oi');
            $tmpField[] = ['oi.sno AS orderInfoSno'];
            $this->orderGoodsOrderBy = 'od.orderInfoSno asc, og.regDt desc, og.scmNo asc, og.orderCd asc';
        }

        $tmpKey = array_keys($tmpField);
        $arrField = [];
        foreach ($tmpKey as $key) {
            $arrField = array_merge($arrField, $tmpField[$key]);
        }
        unset($tmpField, $tmpKey);

        // Binding for below field subQuery
        // $this->db->strField .= ', (SELECT GROUP_CONCAT(CONCAT(goodsNm, "(", goodsCnt, "개)") SEPARATOR ", ") FROM es_orderGoods WHERE orderNo = ? AND goodsType = "addGoods" AND goodsPrice = 0) as componentGoods';
        // code.
        if ($status != 'admin') {
            $this->db->bind_param_push($arrBind, 's', $orderNo);
        }

        // where 절
        $arrWhere[] = 'og.orderNo = ?';
        $this->db->bind_param_push($arrBind, 's', $orderNo);

        // addGoods type이면서 goods price == 0 인 것은 구성 상품(골라 담기)으로 간주하여 주문 한 건으로 취급 하지 않기 위해 조건 배제한다.
        // if ($status != 'admin') {
        //     $arrWhere[] = '(og.goodsType != \'addGoods\' OR (og.goodsType = \'addGoods\' AND og.goodsPrice > 0)) ';
        // }

        if ($statusMode !== null) {
            $arrWhere[] = 'LEFT(og.orderStatus, 1) = ? ';
            $this->db->bind_param_push($arrBind, 's', $statusMode);
        }
        if ($excludeStatus !== null && is_array($excludeStatus)) {
            foreach($excludeStatus as $val){
                $bindQuery[] = '?';
                $this->db->bind_param_push($arrBind, 's', $val);
            }
            $arrWhere[] = 'og.orderStatus NOT IN (' . implode(',', $bindQuery) . ')';
        }
        if ($handleSno !== null) {
            $arrWhere[] = 'og.handleSno = ? ';
            $this->db->bind_param_push($arrBind, 'i', $handleSno);
        }
        if ($userHandleSno !== null) {
            $arrWhere[] = 'ouh.sno = ? ';
            $this->db->bind_param_push($arrBind, 'i', $userHandleSno);
        }
        if ($orderGoodsNo !== null) {
            $arrBindParam = null;
            if (is_array($orderGoodsNo)) {
                foreach ($orderGoodsNo as $sno) {
                    $this->db->bind_param_push($arrBind, 'i', $sno);
                    $arrBindParam[] = '?';
                }
                $arrWhere[] = 'og.sno IN (' . implode(',', $arrBindParam) . ')';
            } else {
                $arrWhere[] = 'og.sno = ? ';
                $this->db->bind_param_push($arrBind, 'i', $orderGoodsNo);
            }
        }

        // join 문
        $join[] = ' LEFT JOIN ' . DB_GOODS . ' g ON og.goodsNo = g.goodsNo ';
        $join[] = ' LEFT JOIN ' . DB_GOODS_IMAGE . ' gi ON og.goodsNo = gi.goodsNo AND gi.imageKind = \'list\' ';
        $join[] = ' LEFT JOIN ' . DB_ADD_GOODS . ' ag ON og.goodsNo = ag.addGoodsNo ';
        $join[] = ' LEFT JOIN ' . DB_ORDER_DELIVERY . ' od ON og.orderDeliverySno = od.sno ';
        $join[] = ' LEFT JOIN ' . DB_ORDER_HANDLE . ' oh ON og.orderNo = oh.orderNo AND og.handleSno = oh.sno ';
        if ($userHandleSno !== null) {
            $join[] = ' LEFT JOIN ' . DB_ORDER_USER_HANDLE . ' ouh ON og.orderNo = ouh.orderNo AND (ouh.sno = og.userHandleSno || ouh.userHandleGoodsNo = og.sno) ';
        } else {
            $join[] = ' LEFT JOIN ' . DB_ORDER_USER_HANDLE . ' ouh ON og.orderNo = ouh.orderNo AND og.userHandleSno = ouh.sno ';
        }
        $join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' sm ON og.scmNo = sm.scmNo ';
        if(gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true && gd_is_provider() === false) {
            $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' pu ON og.purchaseNo = pu.purchaseNo ';
        }
        //복수배송지 사용시
        if($useMultiShippingKey === true){
            $join[] = ' LEFT JOIN ' . DB_ORDER_INFO . ' oi ON (og.orderNo = oi.orderNo)  
                        AND (CASE WHEN od.orderInfoSno > 0 THEN od.orderInfoSno = oi.sno ELSE oi.orderInfoCd = 1 END) ';
        }
        else {
            $join[] = ' LEFT JOIN ' . DB_ORDER_INFO . ' oi ON og.orderNo = oi.orderNo AND oi.orderInfoCd = 1 ';
        }
        $join[] = ' LEFT JOIN ' . DB_MANAGER . ' m ON ouh.managerNo = m.sno ';
        $join[] = ' LEFT JOIN ' . DB_ORDER . ' o ON og.orderNo = o.orderNo ';

        $this->db->strJoin = implode('', $join);
        $this->db->strWhere = implode(' AND ', $arrWhere);
        if($useMultiShippingKey === true){
            $this->db->strOrder = $this->orderGoodsMultiShippingOrderBy;
        }
        else {
            $this->db->strOrder = $this->orderGoodsOrderBy;
        }
        $this->db->strField = 'o.regDt, og.sno, oh.regDt AS handleRegDt, ouh.regDt AS userHandleRegDt, og.modDt, ' . implode(', ', $arrField);

        // addGoods 필드 변경 처리 (goods와 동일해서)
        $this->db->strField .= ', ag.imagePath AS addImagePath, ag.imageStorage AS addImageStorage, ag.imageNm AS addImageName, ag.stockUseFl AS addStockFl, ag.stockCnt AS addStockCnt';

        // addGoods type이면서 goods price == 0 인 것은 구성 상품(골라 담기)으로 간주하여 주문 한 건으로 취급하여 주문서에 표시할 수 있도록 이름만 패칭해 온다.
        // Binding parameter code is located above
        // // Binding for below field subQuery
        if ($status != 'admin') {
            $this->db->strField .= ', IF(goodsType = "goods", (SELECT GROUP_CONCAT(CONCAT(goodsNm, "(", goodsCnt, "식", IF(addedGoodsPrice > 0, CONCAT(" +", FORMAT(addedGoodsPrice, 0), "원)"), ")")) SEPARATOR ", ") FROM es_orderGoods WHERE orderNo = ? AND isComponentGoods = true), null) as componentGoods';
        }

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GOODS . ' og ' . implode(' ', $query);
        $getData = $this->db->query_fetch($strSQL, $arrBind);

        // !중요! 해외상점인 경우 배열을 배송비가 높은 우선 순으로 재정렬 처리 (이로 인해 원하는 정렬로 나오지 않을 수는 있슴)
        // 본 처리를 무시하면 UI에서 배송비가 0원으로 나오는 케이스가 발생할 수 있슴
        if ($getData[0]['mallSno'] > DEFAULT_MALL_NUMBER) {
            $tmpData = [];
            foreach ($getData as $key => $val) {
                if ($val['deliveryCharge'] > 0) {
                    array_unshift($tmpData, $val);
                } else {
                    array_push($tmpData, $val);
                }
            }
            $getData = $tmpData;
            unset($tmpData);
        }

        // 데이타 출력 + 데이타 갱신
        if (count($getData) > 0) {
            // 배송비 중복 계산 방지용 변수 (동일조건을 한번만 더함)
            $orderDeliverySno = 0;
            if ($orderData['orderChannelFl'] == 'naverpay') {
                $naverPay = new NaverPay();
            }

            if(!is_object($delivery)){
                $delivery = \App::load('\\Component\\Delivery\\Delivery');
            }
            $delivery->setDeliveryMethodCompanySno();
            $orderInfoSno = $orderInfoKey = '';
            foreach ($getData as $key => $val) {
                if($useMultiShippingKey === true || $val['multiShippingFl'] == 'y') {
                    if ($val['multiShippingFl'] == 'y') {
                        if (empty($orderInfoSno) === true || $orderInfoSno != $val['orderInfoSno']) {
                            $orderInfoSno = $val['orderInfoSno'];
                            $orderInfoKey = $key;
                            $getData[$orderInfoKey]['orderInfoRow'] = 1;
                        } else {
                            $getData[$orderInfoKey]['orderInfoRow'] += 1;
                        }
                    } else {
                        if ($key == 0) {
                            $getData[0]['orderInfoRow'] = 1;
                        } else {
                            $getData[0]['orderInfoRow'] += 1;
                        }
                    }
                    if ($val['orderInfoCd'] > 1) {
                        $getData[$key]['orderInfoTit'] = '추가배송지' . ($val['orderInfoCd'] - 1);
                    } else {
                        $getData[$key]['orderInfoTit'] = '메인배송지';
                    }
                }
                if (gd_str_length($getData[$key]['refundAccountNumber']) > 50) {
                    $getData[$key]['refundAccountNumber'] = \Encryptor::decrypt($getData[$key]['refundAccountNumber']);
                }
                if (gd_str_length($getData[$key]['userRefundAccountNumber']) > 50) {
                    $getData[$key]['userRefundAccountNumber'] = \Encryptor::decrypt($getData[$key]['userRefundAccountNumber']);
                }

                // 태그제거
                $getData[$key]['goodsNm'] = StringUtils::stripOnlyTags($getData[$key]['goodsNm']);
                // 주문상태 텍스트로 변경
                $getData[$key]['orderStatusStr'] = $this->_getOrderStatus($val['orderStatus'], ($status !== null ? $status : 'user'));

                if ($val['orderChannelFl'] == 'naverpay') {
                    $checkoutData = json_decode($val['checkoutData'], true);
                    $getData[$key]['checkoutData'] = $checkoutData;
                    //TODO:네이버페이상태체크
                    //발송지연
                    $naverpayStatus = $naverPay->getStatus($checkoutData,$val['handleSno']);
                    foreach($checkoutData as $nval){
                        if($nval['RequestChannel']){
                            $naverpayStatus['requestChannel'] = $nval['RequestChannel'];
                            break;
                        }
                    }

                    $getData[$key]['naverpayStatus'] = $naverpayStatus;
                }

                if (isset($getData[$key]['beforeStatus'])) {
                    $getData[$key]['beforeStatusStr'] = $this->getOrderStatusAdmin($getData[$key]['beforeStatus']);
                }

                // 반품/교환/환불신청 상태
                if ($val['userHandleSno']) {
                    $getData[$key]['userHandleFlStr'] = $this->getUserHandleMode($val['userHandleMode'], $val['userHandleFl']);
                }

                // 옵션 처리
                $getData[$key]['optionInfo'] = [];
                if (empty($val['optionInfo']) === false) {
                    $option = json_decode(gd_htmlspecialchars_stripslashes($val['optionInfo']), true);
                    if (empty($option) === false) {
                        foreach ($option as $oKey => $oVal) {
                            $getData[$key]['optionInfo'][$oKey]['optionName'] = $oVal[0];
                            $getData[$key]['optionInfo'][$oKey]['optionValue'] = $oVal[1];
                            $getData[$key]['optionInfo'][$oKey]['optionCode'] = $oVal[2];
                            $getData[$key]['optionInfo'][$oKey]['optionRealPrice'] = $oVal[3];
                            $getData[$key]['optionInfo'][$oKey]['deliveryInfoStr'] = $oVal[4];
                        }
                        unset($option);
                    }
                }

                // 텍스트 옵션 처리
                if (empty($val['optionTextInfo']) === false) {
                    $option = json_decode($val['optionTextInfo'], true);
                    unset($getData[$key]['optionTextInfo']);
                    if (empty($option) === false) {
                        foreach ($option as $oKey => $oVal) {
                            $getData[$key]['optionTextInfo'][$oKey]['optionName'] = gd_htmlspecialchars_stripslashes($oVal[0]);
                            $getData[$key]['optionTextInfo'][$oKey]['optionValue'] = gd_htmlspecialchars_stripslashes($oVal[1]);
                            $getData[$key]['optionTextInfo'][$oKey]['optionTextPrice'] = gd_htmlspecialchars_stripslashes($oVal[2]);
                        }
                    }
                    unset($option);
                }

                // 사은품 처리
                $getData[$key]['gift'] = $this->getOrderGift($orderNo, $oVal['scmNo'], 40);

                // 추가상품 처리
                $getData[$key]['addGoods'] = $this->getOrderAddGoods(
                    $orderNo,
                    $val['orderCd'],
                    [
                        'sno',
                        'addGoodsNo',
                        'goodsNm',
                        'goodsCnt',
                        'goodsPrice',
                        'stockUseFl',
                        'stockCnt',
                        'addMemberDcPrice',
                        'addMemberOverlapDcPrice',
                        'addCouponGoodsDcPrice',
                        'addGoodsMileage',
                        'addMemberMileage',
                        'addCouponGoodsMileage',
                        'taxSupplyAddGoodsPrice',
                        'taxVatAddGoodsPrice',
                        'taxFreeAddGoodsPrice',
                        'realTaxSupplyAddGoodsPrice',
                        'realTaxVatAddGoodsPrice',
                        'realTaxFreeAddGoodsPrice',
                        'goodsTaxInfo',
                        'divisionAddUseDeposit',
                        'divisionAddUseMileage',
                        'divisionAddCouponOrderDcPrice',
                        'divisionAddCouponOrderMileage',
                        'optionNm',
                        'goodsImage',
                    ]
                );

                // 추가상품 수량 (테이블 UI 처리에 필요)
                if (!isset($getData[$key]['addGoodsCnt'])) {
                    $getData[$key]['addGoodsCnt'] = empty($getData[$key]['addGoods']) ? 0 : count($getData[$key]['addGoods']);
                }

                // 상품 이미지 처리
                if ($getData[$key]['goodsType'] === 'addGoods') {
                    $getData[$key]['goodsImage'] = gd_html_add_goods_image($val['goodsNo'], $val['addImageName'], $val['addImagePath'], $val['addImageStorage'], 50, $val['goodsNm'], '_blank');
                } else {
                    $getData[$key]['goodsImage'] = gd_html_preview_image($val['imageName'], $val['imagePath'], $val['imageStorage'], 50, 'goods', $val['goodsNm'], null, false, false);
                }

                // 세금정보 처리
                $getData[$key]['goodsTaxInfo'] = explode(STR_DIVISION, $val['goodsTaxInfo']);

                // 재고 출력
                if ($stockFl === true) {
                    if ($getData[$key]['goodsType'] === 'addGoods') {
                        if ($val['addStockFl'] == 1) {
                            $getData[$key]['stockCnt'] = number_format($val['addStockCnt']);
                        } else {
                            $getData[$key]['stockCnt'] = '∞';
                        }
                    } else {
                        // 유한 재고 인 경우
                        if ($val['stockFl'] == 'y') {
                            $getData[$key]['stockCnt'] = number_format($this->getOrderGoodsStock($val['goodsNo'], $val['optionInfo']));
                            // 무한 재고 인 경우
                        } else {
                            $getData[$key]['stockCnt'] = '∞';
                        }
                    }
                }

                // 주문상품 할인/적립 안분 금액을 포함한 총 금액 (최종 상품별 적립/할인 금액 + 추가상품별 적립/할인 금액)
                $getData[$key]['totalMemberDcPrice'] = $val['memberDcPrice'];
                $getData[$key]['totalMemberOverlapDcPrice'] = $val['memberOverlapDcPrice'];
                $getData[$key]['totalCouponGoodsDcPrice'] = $val['couponGoodsDcPrice'];
                // 마이앱 사용에 따른 분기 처리
                if ($this->useMyapp) {
                    $getData[$key]['totalMyappDcPrice'] = $val['myappDcPrice'];
                }
                $getData[$key]['totalGoodsMileage'] = $val['goodsMileage'];
                $getData[$key]['totalMemberMileage'] = $val['memberMileage'];
                $getData[$key]['totalCouponGoodsMileage'] = $val['couponGoodsMileage'];
                $getData[$key]['totalDivisionCouponOrderDcPrice'] = $val['divisionCouponOrderDcPrice'];
                $getData[$key]['totalDivisionCouponOrderMileage'] = $val['divisionCouponOrderMileage'];
                $getData[$key]['totalDivisionUseDeposit'] = $val['divisionUseDeposit'];
                $getData[$key]['totalDivisionUseMileage'] = $val['divisionUseMileage'];

                // 실제 적립된 마일리지만 산출
                if ($val['plusMileageFl'] == 'y') {
                    $getData[$key]['totalRealGoodsMileage'] = $val['goodsMileage'];
                    $getData[$key]['totalRealMemberMileage'] = $val['memberMileage'];
                    $getData[$key]['totalRealCouponGoodsMileage'] = $val['couponGoodsMileage'];
                    $getData[$key]['totalRealDivisionCouponOrderMileage'] = $val['divisionCouponOrderMileage'];
                }

                if (!empty($getData[$key]['addGoods'])) {
                    foreach ($getData[$key]['addGoods'] as $aVal) {
                        $getData[$key]['totalMemberDcPrice'] += $aVal['addMemberDcPrice'];
                        $getData[$key]['totalMemberOverlapDcPrice'] += $aVal['addMemberOverlapDcPrice'];
                        $getData[$key]['totalCouponGoodsDcPrice'] += $aVal['addCouponGoodsDcPrice'];
                        $getData[$key]['totalGoodsMileage'] += $aVal['addGoodsMileage'];
                        $getData[$key]['totalMemberMileage'] += $aVal['addMemberMileage'];
                        $getData[$key]['totalCouponGoodsMileage'] += $aVal['addCouponGoodsMileage'];
                        $getData[$key]['totalDivisionUseDeposit'] += $aVal['divisionAddUseDeposit'];
                        $getData[$key]['totalDivisionUseMileage'] += $aVal['divisionAddUseMileage'];
                        $getData[$key]['totalDivisionCouponOrderDcPrice'] += $aVal['divisionAddCouponOrderDcPrice'];
                        $getData[$key]['totalDivisionCouponOrderMileage'] += $aVal['divisionAddCouponOrderMileage'];

                        if ($val['plusMileageFl'] == 'y') {
                            $getData[$key]['totalRealGoodsMileage'] += $aVal['addGoodsMileage'];
                            $getData[$key]['totalRealMemberMileage'] += $aVal['addMemberMileage'];
                            $getData[$key]['totalRealCouponGoodsMileage'] += $aVal['addCouponGoodsMileage'];
                            $getData[$key]['totalRealDivisionCouponOrderMileage'] += $aVal['divisionAddCouponOrderMileage'];
                        }
                    }
                }

                // 검색 조건에 따른 실제 총 결제금액 합산 (배송비 안분 예치금/마일리지 제외)
                $discountPrice = $val['goodsDcPrice'] + $getData[$key]['totalMemberDcPrice'] + $getData[$key]['totalMemberOverlapDcPrice'] + $getData[$key]['totalCouponGoodsDcPrice'] + $getData[$key]['totalDivisionUseDeposit'] + $getData[$key]['totalDivisionUseMileage'] + $getData[$key]['totalDivisionCouponOrderDcPrice'] + $getData[$key]['enuri'];

                if ($this->useMyapp) { // 마이앱 사용에 따른 분기 처리
                    $discountPrice += $getData[$key]['totalMyappDcPrice'];
                }

                $getData[$key]['settlePrice'] = (($val['goodsPrice'] + $val['optionPrice'] + $val['optionTextPrice']) * $val['goodsCnt']) + $val['addGoodsPrice'];
                if ($mailFl === false) {
                    $getData[$key]['settlePrice'] -= $discountPrice;
                }

                // 취소가능 상품리스트
                if (in_array(substr($val['orderStatus'], 0, 1), $this->statusClaimCode['c']) === true) {
                    $getData[$key]['canCancel'] = true;
                }
                if ($val['orderChannelFl'] == 'naverpay') {  //네이버페이는 입금대기인경우 취소안됨
                    if (substr($orderData['orderStatus'], 0, 1) == 'd' || substr($orderData['orderStatus'], 0, 1) == 'o') {
                        $getData[$key]['canCancel'] = false;
                    }
                }

                // 환불가능 상품리스트
                if (in_array(substr($val['orderStatus'], 0, 1), $this->statusClaimCode['r']) === true) {
                    $getData[$key]['canRefund'] = true;
                }
                if ($val['orderChannelFl'] == 'naverpay') {  //네이버페이는 배송중,배송완료일 경우 환불안됨
                    if (substr($orderData['orderStatus'], 0, 1) == 'd') {
                        $getData[$key]['canRefund'] = false;
                    }
                }

                // 반품가능 상품리스트
                if (in_array(substr($val['orderStatus'], 0, 1), $this->statusClaimCode['b']) === true) {
                    $getData[$key]['canBack'] = true;
                }

                // 교환가능 상품리스트
                if (in_array(substr($val['orderStatus'], 0, 1), ['o', 'p', 's', 'g', 'd']) === true) {
                    $getData[$key]['canExchange'] = true;
                }
                if ($val['orderChannelFl'] == 'naverpay') {  //네이버페이는 배송중,배송완료일 경우 환불안됨
                    if (in_array(substr($orderData['orderStatus'], 0, 1), ['d'])) {
                        $getData[$key]['canExchange'] = false;
                    }
                }

                //네이버페이는 배송중,배송완료일 경우 환불안됨
                if ($val['orderChannelFl'] == 'naverpay') {
                    if (in_array(substr($orderData['orderStatus'], 0, 1), ['s'])) {
                        $getData[$key]['canExchange'] = false;
                        $getData[$key]['canBack'] = false;
                    }
                }

                // 주문상품까지의 예치금/마일리지 총 합 처리
                $getData[$key]['totalGoodsDivisionUseDeposit'] += $getData[$key]['totalDivisionUseDeposit'];
                $getData[$key]['totalGoodsDivisionUseMileage'] += $getData[$key]['totalDivisionUseMileage'];

                // 배송비에 안분된 예치금/마일리지를 상품별로 재 안분된 금액으로 추가 시켜 전체 할인된 예치금/마일리지 금액을 산출
                $getData[$key]['totalDivisionUseDeposit'] += $val['divisionGoodsDeliveryUseDeposit'];
                $getData[$key]['totalDivisionUseMileage'] += $val['divisionGoodsDeliveryUseMileage'];

                if($val['deliveryMethodFl']){
                    $getData[$key]['deliveryMethodFlText'] = gd_get_delivery_method_display($val['deliveryMethodFl']);
                    $getData[$key]['deliveryMethodFlSno'] = $delivery->deliveryMethodList['sno'][$val['deliveryMethodFl']];
                }

                // 관리자-주문 상세 - 쿠폰/할인/혜택 - 주문 상품 할인 데이터
                if(empty($val['goodsDiscountInfo']) === false && $val['goodsDiscountInfo'] != null) {
                    $getData[$key]['goodsDiscountInfo'] = json_decode($val['goodsDiscountInfo'], true);
                }
                // 관리자-주문 상세 - 쿠폰/할인/혜택 - 주문 상품 적립 데이터
                if(empty($val['goodsMileageAddInfo']) === false && $val['goodsMileageAddInfo'] != null) {
                    $getData[$key]['goodsMileageAddInfo'] = json_decode($val['goodsMileageAddInfo'], true);
                }
            }

            // 전체주문의 배송비 조건별 남아있는 금액 산출
            $realDeliveryCharge = $this->getRealDeliveryCharge($orderNo);

            // 실제 남은 배송비 계산 및 scm 별 데이터 저장
            foreach ($getData as $key => $val) {
                // 실제 남은 배송비 설정
                $getData[$key]['realDeliveryCharge'] = $realDeliveryCharge[$val['orderDeliverySno']];

                // settle에 배송비 안분된 마일리지/예치금을 빼줘 최종 settle을 만듬
                if ($mailFl === false) {
                    $getData[$key]['settlePrice'] -= ($val['divisionGoodsDeliveryUseDeposit'] + $val['divisionGoodsDeliveryUseMileage']);
                }

                // SCM 별로 데이터 설정
                if ($scmFl === true) {
                    //복수배송지 사용시 scm no 를 order info sno 로 대체한다.
                    if($useMultiShippingKey === true){
                        $setData[$val['orderInfoSno']][] = $getData[$key];
                    }
                    else {
                        $setData[$val['scmNo']][] = $getData[$key];
                    }
                } else {
                    $setData[$key] = $getData[$key];
                }
            }

            $setData = gd_htmlspecialchars_stripslashes($setData);

            if (($handleSno !== null || $orderGoodsNo !== null || $userHandleSno !== null) && ($scmFl !== true && is_array($orderGoodsNo) === false)) {
                return $setData[0];
            } else {
                return $setData;
            }
        } else {
            return false;
        }
    }

    protected function setStatusChange($orderNo, $arrData, $autoProcess = false)
    {
		$return = parent::setStatusChange($orderNo, $arrData, $autoProcess);
        if ($return !== false) {
            $requireSync = true;

            if (in_array($arrData['changeStatus'], ['g1', 'p1'])) {
                $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');
                $requireSync = $orderAdmin->trySplitScheduledDeliveries([$orderNo], $arrData['changeStatus']);
            }

            if ($requireSync) {
                try {
                    $orderGoodsSnos = $arrData['sno'];
                    $this->syncDeliveryStatusOfFirstRoundDelivery($orderGoodsSnos);
                } catch (Exception $e) {
                    // teams 연동
                    echo "Error has occurred: " . $e->getMessage();
                }
            }
        }
		/** 웹앤모바일 튜닝 - 2020-06-10, ERP 주문데이터 전송 */
		//$erp = App::load(\Component\Erp\Erp::class);
		//$erp->sendOrder($orderNo); // 주문 전송 
		//$erp->sendRefundOrder($orderNo); // 환불 상품 전송 
		//$erp->sendExchangeOrderGoods($orderNo); // 교환상품 처리  

		/** 튜닝 - 2020-06-07, 선물하기 주문의 경우 SMS 전송 */
        $giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
        $giftOrder->sendGiftSms($orderNo);

		return $return;
	}
	
	public function saveOrderInfo($cartInfo, $orderInfo, $orderPrice, $checkSumData = true)
    {
        /**
         * cart에서 orderGoods로 이동하기 전 componentGoodsNo를 가지고 있는 
         * addGoods의 경우 isComponentGoods를 true로 설정
         */
        foreach ($cartInfo as $sKey => $sVal) {
            foreach ($sVal as $dKey => $dVal) {
                foreach ($dVal as $gKey => $cartItem) {
                    if ($cartItem['goodsType'] == 'goods') {
                        if (gd_isset($cartItem['componentGoodsNo'])) {
                          $componentGoodsNos[$cartItem['goodsNo']] = json_decode($cartItem['componentGoodsNo'], true);
                        }
                        if (gd_isset($cartItem['addGoodsPrices'])) {
                          $addedGoodsPrices[$cartItem['goodsNo']] = json_decode($cartItem['addGoodsPrices'], true);
                          $addedGoodsPriceIndices[$cartItem['goodsNo']] = $gKey + 1;
                        }

                        if (!empty($cartItem['firstDelivery']) && $cartItem['firstDelivery'] !== '0') {
                            $cartInfo[$sKey][$dKey][$gKey]['invoiceNo'] = '새벽배송(' . $cartItem['firstDelivery'] . ')';
                        }
                    }
                }
                // FIXME: 카트에 골라담기 상품이 두 개 이상 있는 경우 isComponentGoods가 제대로 설정되지 않음
                // ex, 2502100959000001
                // FIXME: 알 수 없는 이유로 isComponentGoods가 제대로 설정되지 않음
                // ex, 2502261717555886
                // 현재까진 과거 componentGoodsNo 필드가 없던 시절 (25년 2월 경)에 카트에 담았던 상품에 대해서만 발생하는 것으로 추측
                foreach ($dVal as $gKey => $cartItem) {
                    if($cartItem['goodsType'] == 'addGoods' && gd_isset($cartItem['parentGoodsNo'])) {
                        if (in_array($cartItem['addGoodsNo'], $componentGoodsNos[$cartItem['parentGoodsNo']])) {
                            $cartInfo[$sKey][$dKey][$gKey]['isComponentGoods'] = true;
                        }
                        $cartInfo[$sKey][$dKey][$gKey]['addedGoodsPrice'] = $addedGoodsPrices[$cartItem['parentGoodsNo']][$gKey - $addedGoodsPriceIndices[$cartItem['parentGoodsNo']]];
                    }
                }
            }
        }

		//if(\Request::getRemoteAddress()=='182.216.219.157' || \Request::getRemoteAddress()=='112.146.205.124'){
			/** 웹앤모바일 튜닝 - 2020-07-15, 직접 입력 처리 */
			if ($orderInfo['_orderMemo']) {
				$orderInfo['orderMemo'] = $orderInfo['_orderMemo'];
			}
			
			/** 선물하기 주문인경우 직접 주소지 입력 처리 */
			if ($orderInfo['addGiftAddress']) {
				$orderInfo['receiverZonecode'] = $orderInfo['_receiverZonecode'];
				$orderInfo['receiverZipcode'] = $orderInfo['_receiverZipcode'];
				$orderInfo['receiverAddress'] = $orderInfo['_receiverAddress'];
				$orderInfo['receiverAddressSub'] = $orderInfo['_receiverAddressSub'];
			}
			
		//}
		return parent::saveOrderInfo($cartInfo, $orderInfo, $orderPrice, $checkSumData);
	}

    /* 2023-03-23 웹앤모바일 받는 사람이 주소 입력시 sms 전송 x 추가 */
    protected function sendOrderInfoBySms($sendType, $orderNo, $orderGoodsData = null, $claimPrice = null, $smsCnt = null)
    {
        $useGift = \App::load(\Component\Wm\UseGift::class);
        $getAddGiftAddress = $useGift->getAddGiftAddress($orderNo);
        if ($sendType != 'INVOICE_CODE' || !$getAddGiftAddress['giftUpdateStamp']) {
            parent::sendOrderInfoBySms($sendType, $orderNo, $orderGoodsData, $claimPrice, $smsCnt);
        } else {
            return false;
        }
    }
    /* 2023-03-23 웹앤모바일 받는 사람이 주소 입력시 sms 전송 x 추가 끝 */


    /* 2023-03-23 웹앤모바일 선물하기 상품일 때 상품배송 메일 전송 x 추가 */
    protected function sendOrderInfoByMail($sendType, $orderNo)
    {
        $useGift = App::load(\Component\Wm\UseGift::class);
        $isGiftOrder = $useGift->getGiftUse($orderNo);
        if ($sendType != 'DELIVERY' || !$isGiftOrder['isGiftOrder']) {
            parent::sendOrderInfoByMail($sendType, $orderNo);
        } else {
            return false;
        }
    }
    /* 2023-03-23 웹앤모바일 선물하기 상품일 때 상품배송 메일 전송 x 추가 끝 */

    /** 2023-04-03 웹앤모바일 선물하기 상품이고 받는 사람이 주소 입력했을 때 제외하기 추가
     * 최근 배송지 데이터 반환
     * 마지막 주문건의 배송정보를 DB_ORDER_SHIPPING_ADDRESS 테이블의 형식에 맞게 반환
     *
     * @return mixed
     */
    public function getRecentShippingAddress()
    {
        $tmpField = [
            'oi.receiverName AS shippingName',
            'oi.receiverPhone AS shippingPhone',
            'oi.receiverCellPhone AS shippingCellPhone',
            'oi.receiverZipcode AS shippingZipcode',
            'oi.receiverZonecode AS shippingZonecode',
            'oi.receiverAddress AS shippingAddress',
            'oi.receiverAddressSub AS shippingAddressSub',
        ];
        // 해외용 필드 추가
        $tmpField = array_merge($tmpField, [
            'oi.receiverPhonePrefixCode AS shippingPhonePrefixCode',
            'oi.receiverPhonePrefix AS shippingPhonePrefix',
            'oi.receiverCellPhonePrefixCode AS shippingCellPhonePrefixCode',
            'oi.receiverCellPhonePrefix AS shippingCellPhonePrefix',
            'oi.receiverCountryCode AS shippingCountryCode',
            'oi.receiverCountry AS shippingCountry',
            'oi.receiverCity AS shippingCity',
            'oi.receiverState AS shippingState',
        ]);

        $strSQL = 'SELECT ' . implode(',', $tmpField) . ' FROM ' . DB_ORDER_INFO . ' AS oi LEFT JOIN ' . DB_ORDER . ' AS o ON o.orderNo = oi.orderNo AND oi.orderInfoCd = 1 WHERE o.memNo=? AND (oi.isGiftOrder = 0 OR oi.giftUpdateStamp = 0) ORDER BY o.regDt DESC LIMIT 0, 1';
        $getData = $this->db->query_fetch(
            $strSQL, [
            'i',
            Session::get('member.memNo'),
        ], false
        );

        return gd_htmlspecialchars_stripslashes($getData);
    }

    public function getOrderList($pageNum = 10, $dates = null, $statusMode = null)
    {
        $getData = parent::getOrderList($pageNum, $dates, $statusMode);
        
        foreach ($getData as $anOrderIndex => $anOrder) {
            $priceAddedGoodsName = '';
            foreach ($anOrder['goods'] as $aGoodsIndex => $aGoods) {
                if ($aGoods['isComponentGoods'] == '1' && floatval($aGoods['addedGoodsPrice']) > 0) {
                    $priceAddedGoodsName .= $aGoods['goodsNm'].', ';
                }
            }

            if ($priceAddedGoodsName !== '') {
                $priceAddedGoodsName = substr($priceAddedGoodsName, 0, -2);
                $getData[$anOrderIndex]['priceAddedGoodsName'] = $priceAddedGoodsName;
            }
        }
        return $getData;
    }

    public function deliveryStatusChangeCodeP($scheduledDeliverySno, $arrData)
    {
        $this->setScheduledDeliveryStatusChange($scheduledDeliverySno, $arrData);
    }

    public function deliveryStatusChangeCodeG($scheduledDeliverySno, $arrData)
    {
        $this->setScheduledDeliveryStatusChange($scheduledDeliverySno, $arrData);
    }

    public function deliveryStatusChangeCodeD($scheduledDeliverySno, $arrData)
    {
        $this->setScheduledDeliveryStatusChange($scheduledDeliverySno, $arrData);
    }

    public function deliveryStatusChangeCodeS($scheduledDeliverySno, $arrData)
    {
        $this->setScheduledDeliveryStatusChange($scheduledDeliverySno, $arrData);
    }

    public function deliveryStatusChangeCodeR($scheduledDeliverySno, $arrData)
    {
        $this->setScheduledDeliveryStatusChange($scheduledDeliverySno, $arrData);
    }

    protected function setScheduledDeliveryStatusChange($scheduledDeliverySno, $arrData, $autoProcess = false)
    {
        if (empty($arrData['changeStatus'])) {
            return false;
        } else {
            if (strlen($arrData['changeStatus']) != 2) {
                return false;
            }
        }

        $changeStatusMode = substr($arrData['changeStatus'], 0, 1);

        // 현재 상태 수정 처리
        // deliveryStatus변경 update코드를 이전 orderStatus 변경 로직 구조를 그대로 유지하기 위해 코드 구조 변경을 하지 않음
        // 모든게 확실해 지면 코드 구조 변경 가능
        $arrWhere[] = 'sno = ?';
        
        foreach ($arrData['sno'] as $key => $val) {
            // 주문 로그 저장
            // FIXME: 주문상태 변경 로그 저장
            // $logCode01 = $this->getOrderStatusAdmin($arrData['orderStatus'][$key]) . '(' . $arrData['orderStatus'][$key] . ')';
            // $logCode02 = $this->getOrderStatusAdmin($arrData['changeStatus']) . '(' . $arrData['changeStatus'] . ')';
            // $reason = explode(STR_DIVISION, $arrData['reason']);
            // if (count($reason) > 1) {
            //     $reason = $reason[0];
            //     if (empty($reason[1]) === false) {
            //         $reason .= ' (' . $reason[1] . ')';
            //     }
            // }

            // bind 데이터
            $arrBind = [];
            $arrBind['param'][] = 'deliveryStatus = ?';
            $this->db->bind_param_push($arrBind['bind'], 's', $arrData['changeStatus']);

            // 배송일자
            if ($changeStatusMode == 'd') {
                // 배송중
                if ($arrData['changeStatus'] == 'd1') {
                    $arrBind['param'][] = 'deliveryDt = ?';
                    $this->db->bind_param_push($arrBind['bind'], 's', date('Y-m-d H:i:s'));
                }

                // 배송완료 일자
                if ($arrData['changeStatus'] == 'd2') {
                    $arrBind['param'][] = 'deliveryCompleteDt = ?';
                    $this->db->bind_param_push($arrBind['bind'], 's', date('Y-m-d H:i:s'));
                }
            }

            // 구매확정 일자
            if ($changeStatusMode == 's') {
                $arrBind['param'][] = 'finishDt = ?';
                $this->db->bind_param_push($arrBind['bind'], 's', date('Y-m-d H:i:s'));
            }

            // scheduledDeliverySno
            $this->db->bind_param_push($arrBind['bind'], 'i', $val);

            $this->db->set_update_db('ms_scheduledDelivery', $arrBind['param'], implode(' AND ', $arrWhere), $arrBind['bind']);

            unset($arrBind);

            // FIXME: 배송상태 변경 로그 저장
            // $this->orderLog($orderNo, $val, $logCode01, $logCode02, implode(' | ', $reason), false, $autoProcess);
        }
        unset($arrWhere);
    }

    public function fetchScheduledDeliveries($pageNum = 10, $dates = null, $statusMode = null)
    {
        $arrBind = $arrWhere = [];

        if (MemberUtil::checkLogin() == 'member') {
            $arrWhere[] = 'o.memNo = ?';
            $this->db->bind_param_push($arrBind, 'i', Session::get('member.memNo'));
            // $this->db->bind_param_push($arrBind, 'i', 1042); // For testing
        } else {
            throw new AlertRedirectException(__('로그인 정보가 존재하지 않습니다.'), null, null, '../member/login.php');
        }

        if (null !== $dates && is_array($dates) && $dates[0] != '' && $dates[1] != '') {
            $arrWhere[] = 'o.regDt BETWEEN ? AND ?'; 
            $this->db->bind_param_push($arrBind, 's', $dates[0] . ' 00:00:00');
            $this->db->bind_param_push($arrBind, 's', $dates[1] . ' 23:59:59');
        } else {  //빈값으로 넘어오면 1년범위 검색
            $dates[0] = date('Y-m-d', strtotime('-365 days'));
            $dates[1] = date('Y-m-d');
            $arrWhere[] = 'o.regDt BETWEEN ? AND ?'; 
            $this->db->bind_param_push($arrBind, 's', $dates[0] . ' 00:00:00');
            $this->db->bind_param_push($arrBind, 's', $dates[1] . ' 23:59:59');
        }

        // Scheduled Delivery Table 필드
        $arrInclude = [
            'sno',
            'orderNo',
            'orderGoodsSno',
            'orderDeliverySno',
            'scmNo',
            'round',
            'totalRound',
            'deliveryStatus',
            'invoiceCompanySno',
            'estimatedDeliveryDt',
            'invoiceNo',
            'invoiceDt',
            'deliveryDt',
            'deliveryCompleteDt',
            'finishDt',
        ];

        if (Globals::get('gGlobal.isFront')) {
            array_push($arrInclude, 'currencyPolicy', 'exchangeRatePolicy');
        }

        $tmpField[] = DBTableField::setTableField('tableScheduledDelivery', $arrInclude, null, 'sd');

        // 조인
        $arrJoin[] = ' JOIN ms_scheduledDeliveryGoods sdg ON sdg.scheduledDeliverySno = sd.sno ';
        $arrJoin[] = ' JOIN '. DB_ORDER .' o ON sdg.orderNo = o.orderNo ';
        $arrJoin[] = ' JOIN '. DB_ORDER_GOODS .' mog ON (sd.orderGoodsSno = mog.sno)';
        $arrJoin[] = ' LEFT JOIN '. DB_ORDER_GOODS .' og ON (sdg.orderGoodsSno = og.sno AND og.isComponentGoods = true)';
        $arrJoin[] = ' JOIN '. DB_GOODS .' g ON (mog.goodsNo = g.goodsNo)';
        $arrJoin[] = ' LEFT JOIN ' . DB_GOODS_IMAGE . ' gi ON g.goodsNo = gi.goodsNo AND imageKind = \'list\' ';


        // 필드 정리
        $tmpKey = array_keys($tmpField);
        $arrField = [];
        foreach ($tmpKey as $key) {
            $arrField = array_merge($arrField, $tmpField[$key]);
        }
        unset($tmpField, $tmpKey);

        // Order Table 필드
        $arrField[] = 'o.orderNo';
        $arrField[] = 'o.orderChannelFl';
        $arrField[] = 'o.settlePrice';
        $arrField[] = 'o.settleKind';
        $arrField[] = 'o.orderGoodsCnt';
        $arrField[] = 'o.orderTypeFl';
        $arrField[] = 'o.orderGoodsCnt';
        $arrField[] = 'o.multiShippingFl';
        $arrField[] = 'o.regDt orderDate';
        
        // Main Order Goods Table 필드
        $arrField[] = 'mog.goodsNo';
        $arrField[] = 'mog.goodsNm';
        $arrField[] = 'mog.goodsPrice';
        $arrField[] = 'mog.optionPrice';
        $arrField[] = 'mog.optionTextPrice';
        $arrField[] = 'mog.goodsCnt';
        $arrField[] = 'mog.optionInfo';
        $arrField[] = 'mog.optionTextInfo';
        $arrField[] = 'mog.firstDelivery';

        // Goods Table 필드
        $arrField[] = 'g.imagePath';
        $arrField[] = 'g.imageStorage';

        // Goods Image Table 필드
        $arrField[] = 'gi.imageName';
        
        // ScheduledDeliveryGoods Table 필드
        $arrField[] = 'GROUP_CONCAT(CONCAT(sdg.goodsNm, "(", sdg.goodsCnt, "식)") SEPARATOR ", ") goodsNames';
        $arrField[] = 'SUM(sdg.goodsCnt) deliveryGoodsCnt';

        // 페이지 기본설정
        $pageNo = Request::get()->get('page', 1);
        $page = \App::load('\\Component\\Page\\Page', $pageNo);
        $page->page['list'] = $pageNum; // 페이지당 리스트 수
        $page->block['cnt'] = 5;
        $page->setPage();
        $page->setUrl(Request::getQueryString());

        // 현 페이지 결과
        $this->db->strJoin = implode('', $arrJoin);
        $this->db->strField = implode(', ', $arrField);
        $this->db->strWhere = implode(' AND ', gd_isset($arrWhere));
        $this->db->strOrder = 'sd.orderNo, sd.sno asc';
        $this->db->strLimit = $page->recode['start'] . ',' . $pageNum;
        $this->db->strGroup = 'sd.sno';

        if (empty($arrBind)) {
            $arrBind = null;
        }
        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ms_scheduledDelivery AS sd ' . implode(' ', $query);

        $data = $this->db->slave()->query_fetch($strSQL, $arrBind);

        // 현 페이지 검색 결과
        unset($query['group'], $query['order'], $query['limit']);
        $strCntSQL = 'SELECT COUNT(DISTINCT sd.sno) AS cnt FROM ms_scheduledDelivery AS sd ' . implode(' ', $query);
        $total = $this->db->slave()->query_fetch($strCntSQL, $arrBind, false)['cnt'];

        // 검색 레코드 수
        $page->recode['total'] = $total;
        $page->setPage();

        if (gd_isset($data)) {
            $prevOrderNo = null;
            $rowSpan = 1;
            foreach ($data as $key => $val) {
                $val['orderInfo'] = $this->getOrderInfo($val['orderNo'], false);
                $val['settleName'] = $this->getSettleKind($val['settleKind']);

                // 옵션 처리
                $data[$key]['optionInfo'] = [];
                if (empty($val['optionInfo']) === false) {
                    $option = json_decode(gd_htmlspecialchars_stripslashes($val['optionInfo']), true);

                    if (empty($option) === false) {
                        foreach ($option as $oKey => $oVal) {
                            $data[$key]['optionInfo'][$oKey]['optionName'] = $oVal[0];
                            $data[$key]['optionInfo'][$oKey]['optionValue'] = $oVal[1];
                            $data[$key]['optionInfo'][$oKey]['optionCode'] = $oVal[2];
                            $data[$key]['optionInfo'][$oKey]['optionRealPrice'] = $oVal[3];
                            $data[$key]['optionInfo'][$oKey]['deliveryInfoStr'] = $oVal[4];
                        }
                        unset($option);
                    }
                }
                
                $data[$key]['optionTextInfo'] = [];
                // 텍스트 옵션 처리
                if (empty($val['optionTextInfo']) === false) {
                    $option = json_decode($val['optionTextInfo'], true);
                    unset($data[$key]['optionTextInfo']);
                    if (empty($option) === false) {
                        foreach ($option as $oKey => $oVal) {
                            $data[$key]['optionTextInfo'][$oKey]['optionName'] = gd_htmlspecialchars_stripslashes($oVal[0]);
                            $data[$key]['optionTextInfo'][$oKey]['optionValue'] = gd_htmlspecialchars_stripslashes($oVal[1]);
                            $data[$key]['optionTextInfo'][$oKey]['optionTextPrice'] = gd_htmlspecialchars_stripslashes($oVal[2]);
                        }
                    }
                    unset($option);
                }

                $data[$key]['goodsImage'] = gd_html_preview_image($val['imageName'], $val['imagePath'], $val['imageStorage'], 50, 'goods', $val['goodsNm'], null, false, false);
                $data[$key]['deliveryStatusText'] = $this->getDeliverStatusText($val['deliveryStatus']);
                $data[$key]['canChange'] = $this->canChangeEstimatedDeliveryDate($val['deliveryStatus']);

                // estimatedDeliveryDt 에 요일을 한글로 추가
                setlocale(LC_TIME, 'ko_KR.UTF-8');
                $data[$key]['estimatedDeliveryDate'] = strftime('%Y-%m-%d (%a)', strtotime($val['estimatedDeliveryDt']));

                if ($prevOrderNo == $val['orderNo']) {
                    $data[$key]['rowspan'] = '0';
                    $rowSpan++;
                    $data[$key - ($rowSpan - 1)]['rowspan'] = $rowSpan;
                } else {
                    $rowSpan = 1;
                    $data[$key]['rowspan'] = '1';
                }
                $prevOrderNo = $val['orderNo'];
            }
        }

        return gd_htmlspecialchars_stripslashes($data);
    }

    private function getDeliverStatusText($deliveryStatus)
    {
        switch(substr($deliveryStatus, 0, 1)) {
            case 'o':
            case 'p':
                return '배송예정';
            case 'g':
                return '상품준비중';
            case 'd':
                switch($deliveryStatus) {
                    case 'd1':
                        return '배송중';
                    case 'd2':
                        return '배송완료';
                }
            case 's':
                return '배송확정';
            case 'r':
                return '환불완료';
            default:
                return '상태오류';
        }
    }

    private function canChangeEstimatedDeliveryDate($deliveryStatus)
    {
        switch(substr($deliveryStatus, 0, 1)) {
            case 'o':
            case 'p':
                return true;
            default:
                return false;
        }
    }

    public function fetchScheduledDeliveriesBySno($scheduledDeliverySno, $fetchRestRounds = false)
    {
        // FIXME: Check session for security // $this->db->bind_param_push($arrBind, 'i', Session::get('member.memNo'));

        $query = "
            SELECT 
            sd.sno, sd.orderNo, sd.round, sd.totalRound, sd.deliveryStatus, sd.estimatedDeliveryDt
        ";

        if ($fetchRestRounds) {
            $query .= "
                FROM ms_scheduledDelivery sd
                JOIN (
                    SELECT orderNo, round, orderGoodsSno
                    FROM ms_scheduledDelivery
                    WHERE sno = ?
                ) sub ON sd.orderNo = sub.orderNo and sd.orderGoodsSno = sub.orderGoodsSno
                WHERE sd.round >= sub.round
                
            ";
        } else {
            $query .= "
                FROM ms_scheduledDelivery sd
                WHERE sd.sno = ?
            ";
        }
        
        $query .= "ORDER BY sd.round";

        return $this->db->slave()->query_fetch($query, ['i', $scheduledDeliverySno], true);
    }

    public function changeEstimatedDeliveryDates($scheduledDeliverySno, $estimatedDeliveryDate, $updateFollowings, $isFreshDelivery = false)
    {
        if ($isFreshDelivery) {
            throw new Exception('냉장 상품은 배송일 변경이 불가합니다.');
        }

        $scheduledDeliveries = $this->fetchScheduledDeliveriesBySno($scheduledDeliverySno, $updateFollowings);

        if ($scheduledDeliveries[0]['sno'] != $scheduledDeliverySno) {
            throw new Exception('Invalid scheduled delivery sno');
        }

        $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');
        $holidays = $orderAdmin->fetchComingHolidays($estimatedDeliveryDate);
        $baseRound = 1;
        foreach ($scheduledDeliveries as $scheduledDelivery) {
            if(!$this->canChangeEstimatedDeliveryDate($scheduledDelivery['deliveryStatus'])) {
                continue;
            }

            // FIXME: Do we need to check holiday for each round?
            // currently 냉장 check holidays for each round
            // And 냉동 does not check holidays for each round
            // Don't know why, But if possible, we make them consistent
            if($isFreshDelivery) {
                $estimatedDeliveryDatePerRound = $orderAdmin->getFreshDeliveryDayBy($estimatedDeliveryDate, $baseRound);
            } else {
                $estimatedDeliveryDatePerRound = $orderAdmin->getDeliverableDayAtDeliveryRound($estimatedDeliveryDate, $baseRound, $holidays);
            }
            $this->updateEstimatedDeliveryDateBySno($scheduledDelivery['sno'], $estimatedDeliveryDatePerRound);
            $baseRound++;
        }
    }

    public function updateEstimatedDeliveryDateBySno($scheduledDeliverySno, $estimatedDeliveryDate)
    {
        $arrBind = [];
        $arrBind['param'][] = 'estimatedDeliveryDt = ?';
        $this->db->bind_param_push($arrBind['bind'], 's', $estimatedDeliveryDate);
        $this->db->bind_param_push($arrBind['bind'], 'i', $scheduledDeliverySno);
        $this->db->set_update_db('ms_scheduledDelivery', $arrBind['param'], 'sno = ?', $arrBind['bind']);
    }

    /**
	 * 주문상태 변경시 회차 배송 정보가 있으면 1회차 배송정보 상태를 주문 상태로 동기화를 시킨다.
	 * 동기화 기준은 es_orderGoods 의 goodsType이 'goods'만 대상으로 한다.
	 * 1회차 배송은 회차 배송에서 보이지 않기 때문에 deliveryStatus 
	 * p, g, d, s, r 만을 대상으로 하지는 않는다. 주문상태 = 배송상태.
	 * @param array $arrData 주문상태 변경 대상 정보
	 * @author Conan Kim (kmakugo@gmail.com)
	 */
	public function syncDeliveryStatusOfFirstRoundDelivery($orderGoodsSnos)
	{
		if (empty($orderGoodsSnos)) {
            return;
        }

        $strQuery = "SELECT 
        sno,
        orderNo,
        orderStatus,
        orderDeliverySno,
        scmNo,
        invoiceCompanySno,
        invoiceNo,
        invoiceDt,
        deliveryDt,
        deliveryCompleteDt,
        finishDt,
        deliveryLog
        FROM es_orderGoods WHERE  sno IN (" . implode(",", $orderGoodsSnos) . ") AND goodsType = 'goods'";
        $orderGoodsList = $this->db->query_fetch($strQuery, [], true);

        foreach ($orderGoodsList as $orderGoods) {
            $orderNo = $orderGoods['orderNo'];
            $orderGoodsSno = $orderGoods['sno'];
            $strQuery = "SELECT sno FROM ms_scheduledDelivery WHERE orderNo = '". $orderNo ."' AND orderGoodsSno = '". $orderGoodsSno ."' AND round = 1";
            if ($orderGoods['orderStatus'] == 'r3') {
                $strQuery = "SELECT sno FROM ms_scheduledDelivery WHERE orderNo = '". $orderNo ."' AND orderGoodsSno = '". $orderGoodsSno ."' AND (round = 1 OR (round > 1 AND deliveryStatus = 'p1'))";
            }
            $scheduledDeliveryResult = $this->db->query_fetch($strQuery, [], true);
            $scheduledDeliverySnos = array_column($scheduledDeliveryResult, 'sno');

            if (!empty($scheduledDeliverySnos)) {
                // Execute update query
                $updateData = [
                    'deliveryStatus' => $orderGoods['orderStatus'],
                    'orderDeliverySno' => $orderGoods['orderDeliverySno'],
                    'scmNo' => $orderGoods['scmNo'],
                    'invoiceCompanySno' => $orderGoods['invoiceCompanySno'],
                    'invoiceNo' => $orderGoods['invoiceNo'],
                    'invoiceDt' => $orderGoods['invoiceDt'],
                    'deliveryDt' => $orderGoods['deliveryDt'],
                    'deliveryCompleteDt' => $orderGoods['deliveryCompleteDt'],
                    'finishDt' => $orderGoods['finishDt'],
                    'deliveryLog' => $orderGoods['deliveryLog'],
                ];

                $arrBind = $this->db->get_binding(DBTableField::tableScheduledDelivery(), $updateData, 'update', array_keys($updateData));
                $where = 'sno IN (' . @implode(',', $scheduledDeliverySnos) . ')';
                $this->db->set_update_db('ms_scheduledDelivery', $arrBind['param'], $where, $arrBind['bind'], false);
                unset($updateData);
            }
        }
	}

    /**
     * 완료 안된 회차 배송 정보가 있으면 1회차 배송정보 상태를 주문 상태로 동기화를 시킨다.
     * 배송완료 (d2), 구매확정 (s1) 상태는 deliverCompleteOrderAutomatically() 메소드 등을 통해 처리되므로 
     * 이외의 경우에 대해서만 동기화 된다.
     */
	public function syncUnsettledDeliveryStatusOfFirstRoundDelivery($orderGoodsSnos)
	{
        // 1) Fetch all unsettled scheduled deliveries (not in r3, s1)
        $strQuery = "SELECT sno, orderNo, orderGoodsSno, deliveryStatus FROM ms_scheduledDelivery WHERE deliveryStatus NOT IN ('r3','s1')  and round=1";
        $unsettledScheduledDeliveries = $this->db->query_fetch($strQuery, [], true);

        if (empty($unsettledScheduledDeliveries)) {
            return;
        }

        // 2) Fetch all order goods for these scheduled deliveries
        $orderGoodsSnos = array_values(array_unique(array_column($unsettledScheduledDeliveries, 'orderGoodsSno')));
        $orderGoodsSnos = array_map('intval', $orderGoodsSnos);
        if (empty($orderGoodsSnos)) {
            return;
        }

        $strQuery = "SELECT 
        sno,
        orderNo,
        orderStatus,
        orderDeliverySno,
        scmNo,
        invoiceCompanySno,
        invoiceNo,
        invoiceDt,
        deliveryDt,
        deliveryCompleteDt,
        finishDt,
        deliveryLog
        FROM es_orderGoods WHERE sno IN (" . implode(",", $orderGoodsSnos) . ") AND goodsType = 'goods'";
        $orderGoodsList = $this->db->query_fetch($strQuery, [], true);

        if (empty($orderGoodsList)) {
            return;
        }

        $orderGoodsMap = [];
        foreach ($orderGoodsList as $og) {
            $orderGoodsMap[$og['sno']] = $og;
        }

        // 3) Compare and update scheduled delivery only if delivery status is before order status
        foreach ($unsettledScheduledDeliveries as $sd) {
            $ogSno = $sd['orderGoodsSno'];
            if (!isset($orderGoodsMap[$ogSno])) {
                continue;
            }
            $og = $orderGoodsMap[$ogSno];
            
            // Only update if delivery status is before order status in the sequence
            if ($og['orderStatus'] !== $sd['deliveryStatus'] && $this->isStatusBefore($sd['deliveryStatus'], $og['orderStatus'])) {
                $updateData = [
                    'deliveryStatus' => $og['orderStatus'],
                    'orderDeliverySno' => $og['orderDeliverySno'],
                    'scmNo' => $og['scmNo'],
                    'invoiceCompanySno' => $og['invoiceCompanySno'],
                    'invoiceNo' => $og['invoiceNo'],
                    'invoiceDt' => $og['invoiceDt'],
                    'deliveryDt' => $og['deliveryDt'],
                    'deliveryCompleteDt' => $og['deliveryCompleteDt'],
                    'finishDt' => $og['finishDt'],
                    'deliveryLog' => $og['deliveryLog'],
                ];

                $arrBind = $this->db->get_binding(DBTableField::tableScheduledDelivery(), $updateData, 'update', array_keys($updateData));
                $where = 'sno = ' . (int)$sd['sno'];

                $this->db->set_update_db('ms_scheduledDelivery', $arrBind['param'], $where, $arrBind['bind'], false);
                unset($arrBind);
            }
        }
	}

    /**
     * Check if deliveryStatus is before orderStatus in the defined sequence
     * 
     * @param string $deliveryStatus Current delivery status
     * @param string $orderStatus Target order status
     * @return bool True if delivery status is before order status
     */
    private function isStatusBefore($deliveryStatus, $orderStatus)
    {
        $deliveryParsed = $this->parseStatus($deliveryStatus);
        $orderParsed = $this->parseStatus($orderStatus);
        
        // If either status is invalid, don't update
        if (!$deliveryParsed || !$orderParsed) {
            return false;
        }
        
        $deliveryTypeOrder = $this->getStatusTypeOrder($deliveryParsed['type']);
        $orderTypeOrder = $this->getStatusTypeOrder($orderParsed['type']);
        
        // If either status type is invalid, don't update
        if ($deliveryTypeOrder === -1 || $orderTypeOrder === -1) {
            return false;
        }
        
        // Compare status types first
        if ($deliveryTypeOrder < $orderTypeOrder) {
            // Delivery status type is before order status type (e.g., p vs g)
            return true;
        } else if ($deliveryTypeOrder > $orderTypeOrder) {
            // Delivery status type is after order status type (e.g., g vs p)
            return false;
        } else {
            // Same status type, compare levels (e.g., p1 vs p2)
            return $deliveryParsed['level'] < $orderParsed['level'];
        }
    }

     /**
     * Parse status code into type and level
     * 
     * @param string $status The status code (e.g., 'p1', 'g2', 'd3')
     * @return array Array with 'type' and 'level' keys, or null if invalid
     */
    private function parseStatus($status)
    {
        if (strlen($status) < 2) {
            return null;
        }
        
        $type = substr($status, 0, 1);
        $level = (int)substr($status, 1);
        
        // Validate status type
        if (!in_array($type, ['o', 'p', 'g', 'd', 's', 'c', 'r', 'b', 'e'])) {
            return null;
        }
        
        // Validate level (should be positive integer)
        if ($level <= 0) {
            return null;
        }
        
        return [
            'type' => $type,
            'level' => $level
        ];
    }

    /**
     * Get the order index of a status type (first character)
     * 
     * @param string $statusType The status type (o, p, g, d, s, c, r, b, e)
     * @return int The order index, or -1 if not found
     */
    private function getStatusTypeOrder($statusType)
    {
        $statusTypeOrder = [
            'o' => 0,  // 주문접수
            'p' => 1,  // 결제완료
            'g' => 2,  // 상품준비중
            'd' => 3,  // 배송중/완료
            's' => 4,  // 구매확정 (finished status)
            'c' => 4,  // 취소 (finished status)
            'r' => 4,  // 환불 (finished status)
            'b' => 4,  // 반품 (finished status)
            'e' => 4,  // 교환 (finished status)
        ];
        
        return isset($statusTypeOrder[$statusType]) ? $statusTypeOrder[$statusType] : -1;
    }

    /**
     * 회차배송 log 처리
     *
     * @author conan kim
     *
     * @param string  $orderNo   주문 번호
     * @param integer $goodsSno  주문 상품 번호
     * @param integer $round     회차
     * @param string  $logCode01 로그 코드 1
     * @param string  $logCode02 로그 코드 2
     * @param string  $logDesc   로그 내용
     * @param boolean $userOrder 사용자 모드 저장여
     */
    public function scheduledDeliveryLog($orderNo, $orderGoodsSno, $round, $logCode01 = null, $logCode02 = null, $logDesc = null, $userOrder = false, $autoProcess = false)
    {
        $tableField = DBTableField::tableLogDelivery();
        foreach ($tableField as $key => $val) {
            // assign data through variable variables 
            $arrData[$val['val']] = gd_isset(${$val['val']});
        }

        // IP 추가
        $arrData['managerIp'] = Request::getRemoteAddress();

        if($autoProcess == true){
            if(strpos($logCode02, 'r3)') > 0){
                $arrData['managerNo'] = 0;
                $arrData['managerId'] = 'System';
                $arrData['managerIp'] = '';
            }else if(strpos($logCode02, 'r1)') > 0){
                $arrData['managerNo'] = 0;
                $arrData['managerId'] = gd_isset(Session::get('member.memId'), '');
            }else{
                $arrData['managerNo'] = 0;
                $arrData['managerId'] = '';
            }
        }else if ($userOrder === false) {
            if (Session::has('manager.managerId')) {
                $arrData['managerNo'] = Session::get('manager.managerNo');
                $arrData['managerId'] = Session::get('manager.managerId');
            } else {
                $arrData['managerNo'] = '';
                $arrData['managerId'] = '';
            }

            if($this->logManagerNo){
                $manager = new Manager();
                $memberData = $manager->getManagerInfo($this->logManagerNo);
                $arrData['managerNo'] = $memberData['sno'];
                $arrData['managerId'] = $memberData['managerId'];
            }

        } else {
            $arrData['managerId'] = '';
        }

        $arrBind = $this->db->get_binding(DBTableField::tableLogDelivery(), $arrData, 'insert');
        $this->db->set_insert_db('ms_logDelivery', $arrBind['param'], $arrBind['bind'], 'y');
        unset($arrBind);
    }

    public function getOrderView($orderNo)
    {
        $data = parent::getOrderView($orderNo);
        $this->assignParentOrderGoodsSnos($data['goods']);
        return $data;
    }

    /**
	 * 주문 상품 리스트에 부모 주문 상품 sno를 할당한다.
	 * 부모 상품 코드 (parentGoodsNo)는 있지만 sno가 없기 때문에 골라담기 한 상품을 정확히 찾을 수 없다.
	 * orderGoods는 부모 상품과 자식 상품이  orderGoodsCd로 정렬, 순서로 배치되기 때문에 이를 활용하여 부모 주문 상품 sno를 할당한다.
	 * 
	 * @param array $orderGoodsList 주문 상품 리스트
	 */
	private function assignParentOrderGoodsSnos(&$orderGoodsList)
	{
		$parentOrderGoodsSno = null;
        $parentGoodsNo = null;
		foreach ($orderGoodsList as $key => $orderGoods) {
			if ($orderGoods['goodsType'] === 'goods') {
				$parentOrderGoodsSno = $orderGoods['sno'];
                $parentGoodsNo = $orderGoods['goodsNo'];
			} else if ($orderGoods['goodsType'] == 'addGoods' && $orderGoods['isComponentGoods'] && $orderGoods['parentGoodsNo'] == $parentGoodsNo) {
				$orderGoodsList[$key]['parentOrderGoodsSno'] = $parentOrderGoodsSno;
			}
		}
	}

    // 웹앤모바일 정기결제 start
    public function generateOrderNo()
    {
        $tmp = parent::generateOrderNo();

        $db=\App::load(\DB::class);
        $sql="select count(orderNo) as cnt from ".DB_ORDER." where orderNo=?";
        $row = $db->query_fetch($sql,['s',$tmp],false);

        if($row['cnt']<=0){
            return $tmp;
        }else{
            while(1){
                $result = $this->wgenerateOrderNo();

                $sql="select count(orderNo) as cnt from ".DB_ORDER." where orderNo=?";
                $row = $db->query_fetch($sql,['s',$result],false);

                if($row['cnt']<=0){
                    break;
                }
            }
            return $result;
        }
    }

    public function wgenerateOrderNo()
    {
        // 0 ~ 999 마이크로초 중 랜덤으로 sleep 처리 (동일 시간에 들어온 경우 중복을 막기 위해서.)
        usleep(mt_rand(0, 999));

        // 0 ~ 99 마이크로초 중 랜덤으로 sleep 처리 (첫번째 sleep 이 또 동일한 경우 중복을 막기 위해서.)
        usleep(mt_rand(0, 99));

        // microtime() 함수의 마이크로 초만 사용
        list($usec) = explode(' ', microtime());

        // 마이크로초을 4자리 정수로 만듬 (마이크로초 뒤 2자리는 거의 0이 나오므로 8자리가 아닌 4자리만 사용함 - 나머지 2자리도 짜름... 너무 길어서.)
        $tmpNo = sprintf('%04d', round($usec * 10000));

        // PREFIX_ORDER_NO (년월일시분초) 에 마이크로초 정수화 한 값을 붙여 주문번호로 사용함, 16자리 주문번호임
        return PREFIX_ORDER_NO . $tmpNo;
    }
    // 웹앤모바일 정기결제 end

    /**
     * 취소/교환/반품/환불 처리 중 환불일 경우 PG환불 하는 프로세스
     *
     * @param array $bundleData 신청 정보
     *
     * @return string 결과정보
     */
    public function processAutoPgCancel($bundleData, $userHandleSno)
    {
        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            if ($obj->chkSubscriptionOrder($bundleData['orderNo'])) {
                $result = $obj->refundSubscriptionOrder($bundleData, $userHandleSno);
                if (!empty($result['msg'])) {
                    return $result['msg'];
                }
            }
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END

        return parent::processAutoPgCancel($bundleData, $userHandleSno);
    }
}