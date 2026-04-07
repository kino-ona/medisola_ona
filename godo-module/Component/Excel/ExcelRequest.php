<?php
/**
 * 상품노출형태 관리
 * @author    atomyang
 * @version   1.0
 * @since     1.0
 * @copyright ⓒ 2016, NHN godo: Corp.
 */

namespace Component\Excel;

use Component\Database\DBTableField;
use Component\Member\MemberDAO;
use Encryptor;
use Framework\StaticProxy\Proxy\FileHandler;
use Framework\Utility\ComponentUtils;
use Framework\Utility\ArrayUtils;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\NumberUtils;
use Framework\Security\Token;
use LogHandler;
use Request;
use Session;
use UserFilePath;
use Exception;
use Globals;
use Component\Member\Group\Util as GroupUtil;
use Component\Member\Manager;
use Component\Validator\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App;

class ExcelRequest	extends \Bundle\Component\Excel\ExcelRequest
{
    

    const EXCEL_PAGE_NUM = '100';

    /**
     * @var array arrBind
     */
    private $arrBind = [];

    /**
     * @var array 조건
     */
    private $arrWhere = [];

    /**
     * @var array 체크
     */
    private $checked = [];

    /**
     * @var array 검색
     */
    private $search = [];

    private $excelHeader;

    private $excelFooter;

    private $excelPageNum;



    private $_logger;

    /** @var array 카카오페이 결제수단 */
    const KAKAOPAY_PAY_METHOD = [
        'CARD'  =>  '(신용카드)',
        'MONEY' =>  '(카카오머니)'
    ];

    public function __construct()
    {
        parent::__construct();
        
        $this->excelHeader = '<html xmlns="http://www.w3.org/1999/xhtml" lang="ko" xml:lang="ko">' . chr(10);
        $this->excelHeader .= '<head>' . chr(10);
        $this->excelHeader .= '<title>Excel Down</title>' . chr(10);
        $this->excelHeader .= '<meta http-equiv="Content-Type" content="text/html; charset=' . SET_CHARSET . '" />' . chr(10);
        $this->excelHeader .= '<style>' . chr(10);
        $this->excelHeader .= 'br{mso-data-placement:same-cell;}' . chr(10);
        //        $this->excelHeader .= 'td{mso-number-format:"\@";} ' . chr(10);
        $this->excelHeader .= '.xl31{mso-number-format:"0_\)\;\\\(0\\\)";}' . chr(10);
        $this->excelHeader .= '.xl24{mso-number-format:"\@";} ' . chr(10);
        $this->excelHeader .= '.title{font-weight:bold; background-color:#F6F6F6; text-align:center;} ' . chr(10);
        $this->excelHeader .= '</style>' . chr(10);
        $this->excelHeader .= '</head>' . chr(10);
        $this->excelHeader .= '<body>' . chr(10);

        $this->excelFooter = '</body>' . chr(10);
        $this->excelFooter .= '</html>' . chr(10);

        $this->_logger = App::getInstance('logger');
    }

    public function makeExcelFile(&$arrData)
    {
        $excleForm = \App::load('\\Component\\Excel\\ExcelForm');
        $formData = $excleForm->getInfoExcelForm($arrData['formSno']);
        $excelFieldName = $excleForm->setExcelForm($formData['menu'], $formData['location']);

        if ($arrData['menu'] === 'orderDraft') {
            $funcName = "getOrderList";
        } else {
            $funcName = "get" . ucfirst($formData['menu']) . str_replace("_", "", ucwords($formData['location'], "_"));
        }

        $this->fileConfig['menu'] = $formData['menu'];
        $this->fileConfig['location'] = $formData['location'];

        if (!is_dir(UserFilePath::data('excel', $formData['menu'])->getPathName())) {
            mkdir(UserFilePath::data('excel', $formData['menu'])->getPathName(), 0707);
        }

        if ($arrData['passwordFl'] == 'y') {
            // 특정문자 치환
            $replace = ['$', '"', '`'];
            foreach ($replace as $key => $val) {
                $arrData['password'] = str_replace($val, '\\' . $val, $arrData['password']);
            }
            $this->fileConfig['password'] = gd_isset($arrData['password']);
        } else {
            $this->fileConfig['password'] = "";
        }
        if ($formData['menu'] == 'order' || $arrData['menu'] === 'orderDraft') {
            if ($formData['location'] == 'tax_invoice_request' || $formData['location'] == 'tax_invoice_list') {
                $whereCondition = unserialize($arrData['whereCondition']);
                if ($arrData['excelPageNum'] > 0) $whereCondition['pageNum'] = $arrData['excelPageNum'];
                $getData = $this->$funcName($whereCondition, $formData['excelField'], $excelFieldName);
                unset($whereCondition);
            } else {
                $defaultField = $excleForm->setExcelForm($formData['menu'], $formData['location']);

                // 5년 경과 주문 엑셀다운로드 시
                if ($formData['location'] == 'order_delete') {
                    $getData = $this->getDeleteLapseOrderList($arrData['sno'], $formData['excelField'], $defaultField, $excelFieldName);
                    $this->_logger->channel('orderDelete')->info(__METHOD__ . ' EXCEL DOWNLOAD GET DATA ', [$getData]);
                } else if ($formData['location'] == 'scheduled_delivery') {
                    $getData = $this->generateScheduledDeliveryList(unserialize($arrData['whereCondition']), $formData['excelField'], $defaultField, $excelFieldName);
                } else {
                    $getData = $this->getOrderList(unserialize($arrData['whereCondition']), $formData['excelField'], $defaultField, $excelFieldName);
                }
            }
        } else if ($formData['menu'] == 'member') {
            if ($formData['location'] == 'service_privacy_down') {
                // 회원관리 > 회원리스트 > 개인정보수집 동의상태 변경내역 다운로드 (법적 이슈)
                $tmpGetData = $this->getServicePrivacyHistory($arrData, $formData['excelField'], $excelFieldName);
            } else {
                $tmpGetData = $this->getMemberMemberList(unserialize($arrData['whereCondition']), $formData['excelField'], $excelFieldName);
            }
            $getData = $tmpGetData['getData'];
            $arrData['totalCount'] = $tmpGetData['totalCount'];
        } else if ($formData['menu'] == 'promotion' && $formData['location'] == 'coupon_manage') {
            $getData = $this->getPromotionCouponManage($arrData, $formData['excelField'], $excelFieldName);
        } else if ($formData['menu'] == 'promotion' && $formData['location'] == 'coupon_offline_manage') {
            $getData = $this->getPromotionCouponOfflineManage($arrData, $formData['excelField'], $excelFieldName);
        } else if ($formData['menu'] == 'adminLog'){
            $getData = $this->getPolicyAdminLogList(unserialize($arrData['whereCondition']), $formData['excelField'], $excelFieldName);
        } else {
            $getData = $this->$funcName(unserialize($arrData['whereCondition']), $formData['excelField'], $excelFieldName);
        }

        if ($getData) {
            if (\is_array($getData)) {
                foreach ($getData as $k => $v) {
                    $this->genarateFile($v);
                }
            } else {
                if (gd_isset($this->fileConfig['password'])) {
                    $fileList = $this->fileConfig['fileName'];
                    unset($this->fileConfig['fileName']);
                    foreach ($fileList as $k => $v) {
                        $tmpFilePath = UserFilePath::data('excel', $this->fileConfig['menu'], $v)->getRealPath();
                        $fileName = pathinfo($tmpFilePath)['filename'];

                        $zipFilePath = UserFilePath::data('excel', $this->fileConfig['menu'], $fileName . ".zip")->getRealPath();
                        exec('cd ' . pathinfo($tmpFilePath)['dirname'] . ' && zip -P ' . $this->fileConfig['password'] . ' -r "' . $zipFilePath . '" "' . $fileName . '.xls"');
                        $this->fileConfig['fileName'][] = $fileName . ".zip";
                        FileHandler::delete($tmpFilePath);
                    }
                }

            }

            $arrData['filePath'] = "excel" . DS . $formData['menu'];
            $arrData['fileName'] = implode(STR_DIVISION, $this->fileConfig['fileName']);

            $arrData['state'] = "y";
            $arrData['expiryDate'] = date("Y-m-d", strtotime("+7 day"));

            return $arrData;
        } else {
            return false;
        }
    }

    /*
    * 주문목록 엑셀 파일 만들기 함수 (함수이름, getOrderList => makeOrderListExcel 이 더 정확함)
    */
    public function getOrderList($whereCondition, $excelField, $defaultField, $excelFieldName)
    {
        $orderAdmin = \App::load('\\Component\\Order\\OrderAdmin');

        $excelField = explode(STR_DIVISION, $excelField);

        $orderType = "order";
        $isGlobal = false;

        foreach ($excelField as $k => $v) {
            if ($defaultField[$v]['orderFl'] != 'y') {
                $orderType = "goods";
            }
            if (strpos($v, 'global_') !== false) {
                $isGlobal = true;
                $globalFiled[] = str_replace("global_", "", $v);
            }

            //임의의 추가항목이 있을 경우.
            $defaultAddField = '{addFieldNm}_';
            if (strstr($v, $defaultAddField)) {
                $tmpAddFieldName = str_replace($defaultAddField, '', $v);
                $excelFieldName[$v]['name'] = $tmpAddFieldName;
            }
        }

        //if(gd_isset($whereCondition['periodFl']) === null) $whereCondition['periodFl'] = "-1";
        gd_isset($whereCondition['periodFl'], 7);

        $userHandleMode = ['order_list_user_exchange', 'order_list_user_return', 'order_list_user_refund'];
        if (in_array($this->fileConfig['location'], $userHandleMode)) {
            $whereCondition['userHandleMode'] = $whereCondition['statusMode'];
            unset($whereCondition['statusMode']);
            $isUserHandle = true;
        } else {
            $isUserHandle = false;
        }

        // --- 검색 설정
        $orderData = $orderAdmin->fetchOrderListForAdminExcel($whereCondition, $whereCondition['periodFl'], $isUserHandle, $orderType, $excelField, 0, $this->excelPageNum);

        //튜닝한 업체를 위해 데이터 맞춤
        if (empty($orderData['totalCount']) === true && empty($orderData['orderList']) === true) {
            $isGenerator = false;
            $totalNum = count($orderData);
            if ($this->excelPageNum) $orderList = array_chunk($orderData, $this->excelPageNum, true);
            else $orderList = array_chunk($orderData, $totalNum, true);
        } else {
            $isGenerator = true;
            $totalNum = count($orderData['totalCount']);
            $orderList = $orderData['orderList'];
            if (gd_is_provider() && $orderType == 'goods') {
                $totalScmInfo = $orderData['totalScmInfo'];
            }
        }
        unset($goodsData);

        //값이 입력되지 않은 것으로 간주하고 전체 다운로드.
        if ($this->excelPageNum == 0) {
            $pageNum = 0;
        } else if ($this->excelPageNum >= $totalNum) {
            $pageNum = 0;
        } else {
            $pageNum = ceil($totalNum / $this->excelPageNum) - 1;
        }

        $setHedData[] = "<tr>";


        $arrTag = [
            'orderGoodsNm',
            'goodsNm',
            'orderGoodsNmStandard',
            'goodsNmStandard',
        ];

        for ($i = 0; $i <= $pageNum; $i++) {

            $fileName = $this->fileConfig['location'] . array_sum(explode(' ', microtime()));
            $tmpFilePath = UserFilePath::data('excel', $this->fileConfig['menu'], $fileName . ".xls")->getRealPath();

            $fh = fopen($tmpFilePath, 'a+');
            fwrite($fh, $this->excelHeader . "<table border='1'>");

            if ($isGenerator) {
                if ($i == '0') {
                    $data = $orderList;
                } else {
                    $data = $orderAdmin->fetchOrderListForAdminExcel($whereCondition, $whereCondition['periodFl'], $isUserHandle, $orderType, $excelField, $i, $this->excelPageNum)['orderList'];
                }
            } else {
                $data = $orderList[$i];
            }

            foreach ($data as $key => $val) {
                $progress = round((100 / ($totalNum - 1)) * ($key + ($i * $this->excelPageNum)));
                if ($progress % 20 == 0 || $progress == '100') {
                    echo "<script> parent.progressExcel('" . gd_isset($progress, 0) . "'); </script>";
                }

                if ($val['orderGoodsNmStandard']) {
                    list($val['orderGoodsNm'], $val['orderGoodsNmStandard']) = [$val['orderGoodsNmStandard'], $val['orderGoodsNm']];
                }

                if ($val['goodsNmStandard']) {
                    list($val['goodsNm'], $val['goodsNmStandard']) = [$val['goodsNmStandard'], $val['goodsNm']];
                }

                if ($whereCondition['statusMode'] === 'o') {
                    // 입금대기리스트에서 '주문상품명' 을 입금대기 상태의 주문상품명만 구성
                    $noPay = (int)$val['noPay'] - 1;
                    if (trim($val['goodsNm']) !== '') {
                        if ($noPay > 0) {
                            $val['orderGoodsNm'] = $val['goodsNm'] . ' 외 ' . $noPay . ' 건';
                        } else {
                            $val['orderGoodsNm'] = $val['goodsNm'];
                        }
                    }
                    if (trim($val['goodsNmStandard']) !== '') {
                        if ($noPay > 0) {
                            $val['orderGoodsNmStandard'] = $val['goodsNmStandard'] . ' ' . __('외') . ' ' . $noPay . ' ' . __('건');
                        } else {
                            $val['orderGoodsNmStandard'] = $val['goodsNmStandard'];
                        }
                    }
                }

                if ($isGlobal && $val['mallSno'] != $this->gGlobal['defaultMallSno']) {
                    $val['currencyPolicy'] = json_decode($val['currencyPolicy'], true);
                    $val['exchangeRatePolicy'] = json_decode($val['exchangeRatePolicy'], true);
                    $val['currencyIsoCode'] = $val['currencyPolicy']['isoCode'];
                    $val['exchangeRate'] = $val['exchangeRatePolicy']['exchangeRate' . $val['currencyPolicy']['isoCode']];

                    foreach ($globalFiled as $globalKey => $globalValue) {
                        $val["global_" . $globalValue] = NumberUtils::globalOrderCurrencyDisplay($val[$globalValue], $val['exchangeRate'], $val['currencyPolicy']);
                    }
                }

                if ($val['refundBankName']) {
                    $_tmpRefundAccount[] = $val['refundBankName'];
                    $_tmpRefundAccountNumber[] = $val['refundBankName'];
                }
                if ($val['refundAccountNumber'] && gd_str_length($val['refundAccountNumber']) > 50) {
                    $val['refundAccountNumber'] = \Encryptor::decrypt($val['refundAccountNumber']);
                }
                if ($val['userRefundAccountNumber'] && gd_str_length($val['userRefundAccountNumber']) > 50) {
                    $val['userRefundAccountNumber'] = \Encryptor::decrypt($val['userRefundAccountNumber']);
                }
                if ($val['refundAccountNumber']) {
                    $_tmpRefundAccount[] = $val['refundAccountNumber'];
                }
                if ($val['userRefundAccountNumber']) {
                    $_tmpRefundAccountNumber[] = $val['userRefundAccountNumber'];
                }

                if ($val['refundDepositor']) {
                    $_tmpRefundAccount[] = $val['refundDepositor'];
                    $_tmpRefundAccountNumber[] = $val['refundDepositor'];
                }

                if (empty($_tmpRefundAccount) == false) {
                    $val['refundAccountNumber'] = implode(STR_DIVISION, $_tmpRefundAccount);
                    unset($_tmpRefundAccount);
                }
                if (empty($_tmpRefundAccountNumber) == false) {
                    $val['userRefundAccountNumber'] = implode(STR_DIVISION, $_tmpRefundAccountNumber);
                    unset($_tmpRefundAccountNumber);
                }

                // 상품 무게 및 용량
                if ($val['goodsWeight'] || $val['goodsVolume']) {
                    $val['g_goodsWeight'] = ($val['goodsWeight'] === '0.00') ? '0' : number_format((float)$val['goodsWeight'] / $val['goodsCnt'], 2, '.', '');
                    $val['g_goodsVolume'] = ($val['goodsVolume'] === '0.00') ? '0' : number_format((float)$val['goodsVolume'] / $val['goodsCnt'], 2, '.', '');
                    $val['goodsWeightVolume'] = $val['g_goodsWeight'] . gd_isset(Globals::get('gWeight.unit'), 'kg') . '|' . $val['g_goodsVolume'] . gd_isset(Globals::get('gVolume.unit'), '㎖');

                    // 총 무게 및 용량
                    $val['goodsWeight'] = ($val['goodsWeight'] === '0.00') ? '0' : $val['goodsWeight'];
                    $val['goodsVolume'] = ($val['goodsVolume'] === '0.00') ? '0' : $val['goodsVolume'];
                    $val['goodsTotalWeightVolume'] = $val['goodsWeight'] . gd_isset(Globals::get('gWeight.unit'), 'kg') . '|' . $val['goodsVolume'] . gd_isset(Globals::get('gVolume.unit'), '㎖');
                }

                if (gd_is_provider()) {
                    if ($orderType == 'goods') {
                        $val['scmOrderCnt'] = $totalScmInfo[$val['orderNo']]['scmOrderCnt'];
                        $val['scmGoodsCnt'] = $totalScmInfo[$val['orderNo']]['scmGoodsCnt'];
                        $val['scmGoodsNm'] = $totalScmInfo[$val['orderNo']]['scmGoodsNm'];
                        $val['totalGoodsPrice'] = $totalScmInfo[$val['orderNo']]['totalGoodsPrice'];
                        $tmpScmDeliveryCharge = explode(STR_DIVISION, $totalScmInfo[$val['orderNo']]['scmDeliveryCharge']);
                        $tmpScmDeliverySno = explode(STR_DIVISION, $totalScmInfo[$val['orderNo']]['scmDeliverySno']);
                        $val['scmDeliveryCharge'] = array_sum(array_combine($tmpScmDeliverySno, $tmpScmDeliveryCharge));
                    } else {
                        $tmpScmDeliveryCharge = explode(STR_DIVISION, $val['scmDeliveryCharge']);
                        $tmpScmDeliverySno = explode(STR_DIVISION, $val['scmDeliverySno']);
                        $val['scmDeliveryCharge'] = array_sum(array_combine($tmpScmDeliverySno, $tmpScmDeliveryCharge));
                    }
                    $val['scmGoodsNm'] = gd_htmlspecialchars(gd_htmlspecialchars_stripslashes(StringUtils::stripOnlyTags($val['scmGoodsNm'])));
                    unset($tmpScmDeliveryCharge);
                    unset($tmpScmDeliverySno);
                }

                $tmpData[] = "<tr>";
                foreach ($excelField as $excelKey => $excelValue) {

                    if (!$excelFieldName[$excelValue]) continue;

                    if (in_array($excelValue, $arrTag)) {
                        $val[$excelValue] = gd_htmlspecialchars(gd_htmlspecialchars_stripslashes(StringUtils::stripOnlyTags($val[$excelValue])));
                    }

                    if ($key == '0') {
                        $setHedData[] = "<td class='title'>" . $excelFieldName[$excelValue]['name'] . "</td>";
                    }

                    $tmpOptionInfo = [];
                    $tmpOptionCode = [];
                    if ($val['optionInfo']) {
                        $tmpOption = json_decode(gd_htmlspecialchars_stripslashes($val['optionInfo']), true);
                        foreach ($tmpOption as $optionKey => $optionValue) {
                            if ($optionValue[2]) $tmpOptionCode[] = $optionValue[2];
                            $tmpOptionInfo[] = $optionValue[0] . " : " . $optionValue[1];
                        }
                        unset($tmpOption);
                    }

                    switch ($excelValue) {
                        case 'orderGoodsCnt':
                            if (gd_is_provider()) {
                                $tmpData[] = "<td>" . $val['scmOrderCnt'] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }
                            break;
                        case 'orderGoodsNm':
                            if (gd_is_provider()) {
                                if ($val['scmOrderCnt'] == 1) {
                                    $tmpData[] = "<td>" . $val['scmGoodsNm'] . "</td>";
                                } else {
                                    $tmpData[] = "<td>" . $val['scmGoodsNm'] . " 외 " . ($val['scmOrderCnt'] - 1) . " 건</td>";
                                }
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }

                            break;
                        case 'totalSettlePrice':
                            if (gd_is_provider()) {
                                $tmpData[] = "<td>" . ($val['totalOrderGoodsPrice'] + $val['scmDeliveryCharge']) . "</td>";
                            } else {
                                if ($val['orderChannelFl'] === 'naverpay') {
                                    $checkoutData = json_decode($val['checkoutData'], true);
                                    $tmpData[] = "<td>" . $checkoutData['orderData']['GeneralPaymentAmount'] . " </td>";
                                } else {
                                    $tmpData[] = "<td>" . $val['totalRealSettlePrice'] . " </td>";
                                }
                            }
                            break;
                        case 'totalDeliveryCharge':
                            if (gd_is_provider()) {
                                $tmpData[] = "<td>" . $val['scmDeliveryCharge'] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }
                            break;
                        case 'goodsDeliveryCollectFl':
                            if ($val['goodsDeliveryCollectFl'] == 'pre') {
                                $tmpData[] = "<td>선불</td>";
                            } else {
                                $tmpData[] = "<td>착불</td>";
                            }
                            break;
                        case 'mallSno':
                            $tmpData[] = "<td>" . $this->gGlobal['mallList'][$val['mallSno']]['mallName'] . "</td>";
                            break;
                        case 'hscode':

                            if ($val['hscode']) {

                                $hscode = json_decode(gd_htmlspecialchars_stripslashes($val['hscode']), true);

                                if ($hscode) {
                                    foreach ($hscode as $k1 => $v1) {
                                        $_tmpHsCode[] = $k1 . " : " . $v1;
                                    }

                                    $tmpData[] = "<td>" . implode("<br>", $_tmpHsCode) . "</td>";
                                    unset($_tmpHsCode);

                                } else {
                                    $tmpData[] = "<td></td>";
                                }

                                unset($hscode);

                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;
                        case 'addField':
                            unset($excelAddField);
                            $addFieldData = json_decode($val[$excelValue], true);
                            $excelAddField[] = "<td><table border='1'><tr>";
                            foreach ($addFieldData as $addFieldKey => $addFieldVal) {
                                if ($addFieldVal['process'] == 'goods') {
                                    foreach ($addFieldVal['data'] as $addDataKey => $addDataVal) {
                                        $goodsVal = $addDataVal;
                                        if ($addFieldVal['type'] == 'text' && $addFieldVal['encryptor'] == 'y') {
                                            $goodsVal = Encryptor::decrypt($goodsVal);
                                        }
                                        $excelAddField[] = "<td>" . $addFieldVal['name'] . " (" . $addFieldVal['goodsNm'][$addDataKey] . ") : " . $goodsVal . "</td>";
                                    }
                                } else {
                                    $excelAddField[] = "<td>" . $addFieldVal['name'] . " : ";
                                    $goodsVal = $addFieldVal['data'];
                                    if ($addFieldVal['type'] == 'text' && $addFieldVal['encryptor'] == 'y') {
                                        $goodsVal = Encryptor::decrypt($goodsVal);
                                    }
                                    $excelAddField[] = $goodsVal;
                                    $excelAddField[] = "</td>";
                                }
                            }
                            if ($val['orderChannelFl'] == 'naverpay') {
                                $checkoutData = json_decode($val['checkoutData'], true);
                                if (empty($checkoutData['orderGoodsData']['IndividualCustomUniqueCode']) === false) {
                                    $excelAddField[] = "<td> 개인통관 고유번호(네이버) : " . $checkoutData['orderGoodsData']['IndividualCustomUniqueCode'] . "</td>";
                                }
                            }
                            if ($val['orderChannelFl'] == 'payco') {
                                $paycoDataField = empty($val['fintechData']) === false ? 'fintechData' : 'checkoutData';
                                if (empty($val[$paycoDataField]) === false) {
                                    $paycoData = json_decode($val[$paycoDataField], true);
                                    if ($paycoData['individualCustomUniqNo']) {
                                        $excelAddField[] = "<td> 개인통관 고유번호(페이코) : " . $paycoData['individualCustomUniqNo'] . "</td>";
                                    }
                                }
                            }
                            $excelAddField[] = "</tr></table></td>";
                            $tmpData[] = implode('', $excelAddField);
                            break;
                        case 'goodsType':
                            if ($val['goodsType'] == 'addGoods') {
                                $tmpData[] = "<td>추가</td>";
                            } else {
                                $tmpData[] = "<td>일반</td>";
                            }

                            break;
                        case 'goodsNm':
                            if ($val['goodsType'] == 'addGoods') {
                                $tmpData[] = "<td><span style='color:red'>[" . __('추가') . "]</span>" . $val[$excelValue] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }

                            break;
                        case 'optionInfo':
                            if ($tmpOptionInfo) {
                                $tmpData[] = "<td>" . implode("<br/>", $tmpOptionInfo) . "</td>";
                            } else {
                                $tmpData[] = "<td></td>";
                            }
                            break;
                        case 'optionCode':
                            if ($tmpOptionCode) {
                                $tmpData[] = "<td class='xl24'>" . implode("<br/>", $tmpOptionCode) . "</td>";
                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;
                        case 'optionTextInfo':
                            $tmpOptionTextInfo = [];
                            if ($val[$excelValue]) {
                                $tmpOption = json_decode($val[$excelValue], true);
                                foreach ($tmpOption as $optionKey => $optionValue) {
                                    //$tmpOptionTextInfo[] = $optionValue[0] . " : " . $optionValue[1] . " / " . __('옵션가') . " : " . $optionValue[2];
                                    $tmpOptionTextInfo[] = gd_htmlspecialchars_stripslashes($optionValue[0]) . " : " . gd_htmlspecialchars_stripslashes($optionValue[1]);
                                }
                            }
                            if ($tmpOptionTextInfo) {
                                $tmpData[] = "<td>" . implode("<br/>", $tmpOptionTextInfo) . "</td>";
                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            unset($tmpOptionTextInfo);
                            unset($tmpOption);
                            break;
                        case 'orderStatus':
                            $tmpData[] = "<td>" . $orderAdmin->getOrderStatusAdmin($val[$excelValue]) . "</td>";
                            break;
                        case 'settleKind':
                            $settleKind = $orderAdmin->printSettleKind($val[$excelValue]);
                            switch ($val['settleKind']) {
                                case 'pk' : // 카카오페이 결제수단 구분 추가
                                    $settleKind .= '<br />' . self::KAKAOPAY_PAY_METHOD[$val['pgSettleNm']];
                                    break;
                            }
                            $tmpData[] = "<td>" . $settleKind . "</td>";
                            break;
                        // 숫자 처리 - 주문 번호 및 우편번호(5자리) 숫자에 대한 처리
                        case 'orderNo':
                        case 'apiOrderNo':
                        case 'apiOrderGoodsNo':
                        case 'receiverZonecode':
                        case 'invoiceNo':
                        case 'pgTid':
                        case 'pgAppNo':
                        case 'pgResultCode':
                        case 'orderPhone':
                        case 'orderCellPhone':
                        case 'receiverPhone':
                            $tmpData[] = "<td class=\"xl24\">" . $val[$excelValue] . "</td>";
                            break;

                        case 'totalGift':
                        case 'ogi.presentSno':
                        case 'ogi.giftNo':
                            $gift = $orderAdmin->getOrderGift($val['orderNo'], $val['scmNo'], 40);
                            $presentTitle = [];
                            $giftInfo = [];
                            $totalGift = [];
                            if ($gift) {
                                foreach ($gift as $gk => $gv) {
                                    $presentTitle[] = $gv['presentTitle'];
                                    $giftInfo[] = $gv['giftNm'] . INT_DIVISION . $gv['giveCnt'] . "개";
                                    $totalGift[] = $gv['presentTitle'] . INT_DIVISION . $gv['giftNm'] . INT_DIVISION . $gv['giveCnt'] . "개";
                                }

                                if ($excelValue == 'ogi.presentSno') {
                                    $tmpData[] = "<td>" . implode("<br>", $presentTitle) . "</td>";
                                } else if ($excelValue == 'ogi.giftNo') {
                                    $tmpData[] = "<td>" . implode("<br>", $giftInfo) . "</td>";
                                } else if ($excelValue == 'totalGift') {
                                    $tmpData[] = "<td>" . implode("<br>", $totalGift) . "</td>";
                                }
                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;

                        case 'memNo':
                            $tmpData[] = "<td class=\"xl24\">" . $val['memId'] . "</td>";
                            break;
                        case 'receiverAddressTotal':
                            $receiverAddress = $val['receiverAddress'] . " " . $val['receiverAddressSub'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitAddress'])) === false) $receiverAddress = $val['visitAddress'];
                            if ($val['mallSno'] != $this->gGlobal['defaultMallSno']) {
                                $countriesCode = $orderAdmin->getCountriesList();
                                $countriesCode = array_combine(array_column($countriesCode, 'code'), array_column($countriesCode, 'countryNameKor'));
                                $tmpData[] = "<td>" . $countriesCode[$val['receiverCountryCode']] . " " . $val['receiverCity'] . " " . $val['receiverState'] . " " . $receiverAddress . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $receiverAddress . "</td>";
                            }
                            break;
                        case 'receiverAddress':
                            $receiverAddress = $val['receiverAddress'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitAddress'])) === false) $receiverAddress = $val['visitAddress'];
                            $tmpData[] = "<td class='xl24'>" . $receiverAddress . "</td>";
                            break;
                        case 'receiverAddressSub':
                            $receiverAddressSub = $val['receiverAddressSub'];
                            if ($val['deliveryMethodFl'] == 'visit') $receiverAddressSub = '';
                            $tmpData[] = "<td class='xl24'>" . $receiverAddressSub . "</td>";
                            break;
                        case 'receiverName':
                            $receiverName = $val['receiverName'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitName'])) === false) $receiverName = $val['visitName'];
                            $tmpData[] = "<td class='xl24'>" . $receiverName . "</td>";
                            break;
                        case 'receiverCellPhone':
                            $receiverCellPhone = $val['receiverCellPhone'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitPhone'])) === false) $receiverCellPhone = $val['visitPhone'];
                            $tmpData[] = "<td class='xl24'>" . $receiverCellPhone . "</td>";
                            break;
                        case 'addGoodsNo':

                            $addGoods = $orderAdmin->getOrderAddGoods(
                                $val['orderNo'],
                                $val['orderCd'],
                                [
                                    'sno',
                                    'addGoodsNo',
                                    'goodsNm',
                                    'goodsCnt',
                                    'goodsPrice',
                                    'optionNm',
                                    'goodsImage',
                                    'addMemberDcPrice',
                                    'addMemberOverlapDcPrice',
                                    'addCouponGoodsDcPrice',
                                    'addGoodsMileage',
                                    'addMemberMileage',
                                    'addCouponGoodsMileage',
                                    'divisionAddUseDeposit',
                                    'divisionAddUseMileage',
                                    'divisionAddCouponOrderDcPrice',
                                ]
                            );

                            $addGoodsInfo = [];
                            if ($addGoods) {
                                foreach ($addGoods as $av => $ag) {
                                    $addGoodsInfo[] = $ag['goodsNm'];
                                }
                                $tmpData[] = "<td>" . implode("<br>", $addGoodsInfo) . "</td>";

                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;
                        case 'orderMemo' :
                            if ($val['orderChannelFl'] == 'naverpay') {
                                $checkoutData = json_decode($val['checkoutData'], true);
                                $tmpData[] = "<td>" . $checkoutData['orderGoodsData']['ShippingMemo'] . "</td>";
                            } else {
                                $orderMemo = $val['orderMemo'];
                                if ($val['deliveryMethodFl'] == 'y') $orderMemo = $val['visitMemo'];
                                $tmpData[] = "<td>" . $orderMemo . "</td>";
                            }
                            break;

                        case 'packetCodeFl' :
                            $tmpData[] = (trim($val['packetCode'])) ? "<td>y</td>" : "<td>n</td>";
                            break;

                        //복수배송지 배송지
                        case 'multiShippingOrder' :
                            $multiShippingName = ((int)$val['orderInfoCd'] === 1) ? '메인 배송지' : '추가' . ((int)$val['orderInfoCd'] - 1) . ' 배송지';
                            $tmpData[] = "<td>" . $multiShippingName . "</td>";
                            break;

                        //복수배송지 배송지별배송비
                        case 'multiShippingPrice' :
                            $tmpData[] = "<td>" . $val['deliveryCharge'] . "</td>";
                            break;

                        // 안심번호 (사용하지 않을경우 휴대폰번호 노출)
                        case 'receiverSafeNumber':
                            if ($val['receiverUseSafeNumberFl'] == 'y' && empty($val['receiverSafeNumber']) == false && empty($val['receiverSafeNumberDt']) == false && DateTimeUtils::intervalDay($val['receiverSafeNumberDt'], date('Y-m-d H:i:s')) <= 30) {
                                $tmpData[] = "<td class='xl24'>" . $val['receiverSafeNumber'] . "</td>";
                            } else {
                                $tmpData[] = "<td class='xl24'>" . $val['receiverCellPhone'] . "</td>";
                            }

                            break;
                        case 'userHandleInfo':
                            $userHandleInfo = '';
                            if ($whereCondition['userHandleViewFl'] != 'y') {
                                $userHandleInfo = $orderAdmin->getUserHandleInfo($val['orderNo'], $val['orderGoodsSno'])[0];
                            }
                            $tmpData[] = "<td>" . $userHandleInfo . "</td>";

                            break;
                        case 'goodsCnt' :
                            $tmpOrderCnt = $val[$excelValue];
                            if ($whereCondition['optionCountFl'] === 'per') {
                                $tmpOrderCnt = 1;
                            }
                            $tmpData[] = "<td>" . $tmpOrderCnt . "</td>";
                            break;
                        case 'orderChannelFl':
                            $channel = $val[$excelValue];
                            if ($val['trackingKey']) $channel .= '<br />페이코쇼핑';
                            $tmpData[] = "<td>" . $channel . "</td>";
                            break;
                        case 'orderTypeFl':
                            // 주문유형
                            if ($val['orderTypeFl'] == 'pc') {
                                $tmpData[] = "<td>PC쇼핑몰</td>";
                            } else if ($val['orderTypeFl'] == 'mobile') {
                                if (empty($val['appOs']) === true && empty($val['pushCode']) === true) {
                                    $tmpData[] = "<td>모바일쇼핑몰 - WEB</td>";
                                } else {
                                    $tmpData[] = "<td>모바일쇼핑몰 - APP</td>";
                                }
                            } else {
                                $tmpData[] = "<td>수기주문</td>";
                            }

                            break;
                        case 'totalGoodsPriceByGoods':
                            $tmpData[] = "<td>" . (($val['goodsPrice'] + $val['optionPrice'] + $val['optionTextPrice']) * $val['goodsCnt']) . "</td>";
                            break;
                        case 'goodsPriceWithOption':
                            $tmpData[] = "<td>" . ($val['goodsPrice'] + $val['optionPrice'] + $val['optionTextPrice']) . "</td>";
                            break;
                        case 'useCouponNm': // 사용된 쿠폰명
                            if ($val['couponGoodsDcPrice'] <= 0 && $val['totalCouponDeliveryDcPrice'] <= 0) {
                                $tmpData[] = '<td></td>';
                                break;
                            }
                            if ($val['goodsType'] == 'addGoods') {
                                $tmpData[] = '<td>' . $tmpCouponNm . '</td>';
                                break;
                            }
                            $tmpGoodsCouponNmText = $tmpOrderCouponNmText = $tmpDeliveryCouponNmText = [];
                            $orderCouponData = $orderAdmin->getOrderCoupon($val['orderNo']);
                            foreach ($orderCouponData as $couponVal) {
                                $couponBenefitType = ($couponVal['couponBenefitType'] == 'fix') ? gd_currency_default() : '%';
                                // 상품 쿠폰
                                if ($val['couponGoodsDcPrice'] > 0) {
                                    if ($couponVal['couponKindType'] == 'sale' && $couponVal['couponUseType'] == 'product') {
                                        $orderGoodsStatusMode = substr($val['orderStatus'], 0, 1); // 주문상품상태값
                                        $orderCancelFl = false; // 주문 교환/환불/취소/반품 구분
                                        if ($orderGoodsStatusMode == 'c' || $orderGoodsStatusMode == 'e' || $orderGoodsStatusMode == 'r' || $orderGoodsStatusMode == 'b') {
                                            $orderCancelFl = true;
                                        }
                                        if ($val['goodsNo'] == $couponVal['goodsNo'] && ($val['orderCd'] == $couponVal['orderCd']) || $orderCancelFl) {
                                            $tmpGoodsCouponNmText[] = $couponVal['couponNm'] . " : " . gd_money_format($couponVal['couponBenefit']) . $couponBenefitType . " 할인";
                                        }
                                    }
                                }
                                // 주문적용 쿠폰
                                if ($val['totalCouponOrderDcPrice'] > 0 || $val['divisionCouponOrderDcPrice'] > 0) {
                                    if ($couponVal['couponKindType'] == 'sale' && $couponVal['couponUseType'] == 'order') {
                                        if ($val['divisionCouponOrderDcPrice'] > 0) { // 주문적용 쿠폰인 경우
                                            $tmpOrderCouponNmText[] = $couponVal['couponNm'] . " : " . gd_money_format($couponVal['couponBenefit']) . $couponBenefitType . " 할인";
                                        }
                                    }
                                }
                                // 배송비 쿠폰
                                if ($val['totalCouponDeliveryDcPrice'] > 0) { // 배송비적용 쿠폰
                                    if ($couponVal['couponKindType'] == 'delivery' || $couponVal['couponUseType'] == 'delivery') {
                                        $tmpDeliveryCouponNmText[] = $couponVal['couponNm'] . " : " . gd_money_format($couponVal['couponBenefit']) . $couponBenefitType . " 할인";
                                    }
                                }
                            }
                            $tmpCouponNmText = array_merge($tmpGoodsCouponNmText, array_merge($tmpOrderCouponNmText, $tmpDeliveryCouponNmText));
                            $tmpCouponNm = implode('<br/>', $tmpCouponNmText);
                            $tmpData[] = '<td>' . $tmpCouponNm . '</td>';
                            break;
                        case 'goodsDiscountInfo':
                            if ($val['goodsDcPrice'] > 0) {
                                $goodsDiscountInfo = json_decode($val[$excelValue], true);
                                $arrDiscountGroup = array('member' => '| 회원전용 |', 'group' => '| 특정회원등급 |');
                                $arrNewGoodsReg = array('regDt' => '등록일', 'modDt' => '수정일');
                                $arrNewGoodsDate = array('day' => '일', 'hour' => '시간');
                                $benefitNm = '개별설정 ';
                                if (empty($goodsDiscountInfo['benefitNm']) == false && $goodsDiscountInfo != null) {
                                    $benefitNm = $goodsDiscountInfo['benefitNm'];
                                }
                                $divisionText = ''; // 기간할인 구분자 초기화
                                if (!$arrDiscountGroup[$goodsDiscountInfo['goodsDiscountGroup']]) $divisionText = " | ";  // 배열에 값이 없을 경우 기간할인 구분자 삽입
                                if ($goodsDiscountInfo['goodsDiscountUnit'] == 'price') $goodsDiscountPricePrint = ' | ' . gd_currency_symbol() . gd_money_format($goodsDiscountInfo['goodsDiscount']) . gd_currency_string();
                                else $goodsDiscountPricePrint = ' | ' . $goodsDiscountInfo['goodsDiscount'] . '%';
                                if ($goodsDiscountInfo['benefitUseType'] == 'nonLimit') { // 제한 없음
                                    $benefitPeriod = '';
                                } else if ($goodsDiscountInfo['benefitUseType'] == 'newGoodsDiscount') { // 등록일 기준
                                    $benefitPeriod = $divisionText . ' 상품' . $arrNewGoodsReg[$goodsDiscountInfo['newGoodsRegFl']] . '부터 ' . $goodsDiscountInfo['newGoodsDate'] . $arrNewGoodsDate[$goodsDiscountInfo['newGoodsDateFl']] . '까지';
                                } else { // 기간 제한
                                    $benefitPeriod = $divisionText . " " . gd_date_format("Y-m-d H:i", $goodsDiscountInfo['periodDiscountStart']) . ' ~ ' . gd_date_format("Y-m-d H:i", $goodsDiscountInfo['periodDiscountEnd']);
                                }
                                if (empty($goodsDiscountInfo) === false || $goodsDiscountInfo != null) {
                                    $tmpGoodsDiscountInfoText[] = $benefitNm . $benefitPeriod . $goodsDiscountPricePrint . "할인";
                                } else {
                                    $tmpGoodsDiscountInfoText[] = '상품 할인 | ' . gd_money_format($val['goodsDcPrice']) . gd_currency_default() . "할인";
                                }
                            }
                            $tmpGoodsDiscountInfo = implode('<br/>', $tmpGoodsDiscountInfoText);
                            $tmpData[] = '<td>' . $tmpGoodsDiscountInfo . '</td>';
                            unset($tmpGoodsDiscountInfoText);
                            break;
                        case 'memberPolicy':
                            $orderMemberPolicy = json_decode($val[$excelValue], true);
                            $arrMemberPolicyFixedOrderType = array('option' => '옵션별', 'goods' => '상품별', 'order' => '주문별', 'brand' => '브랜드별');
                            if ($orderMemberPolicy['fixedOrderTypeDc'] == 'brand') {
                                //회원등급 > 브랜드별 추가할인 상품 브랜드 정보
                                if (in_array($val['brandCd'], $orderMemberPolicy['dcBrandInfo']['cateCd'])) {
                                    $goodsBrandInfo[$val['goodsNo']][$val['brandCd']] = $val['brandCd'];
                                } else {
                                    if ($val['brandCd']) {
                                        $goodsBrandInfo[$val['goodsNo']]['allBrand'] = $val['brandCd'];
                                    } else {
                                        $goodsBrandInfo[$val['goodsNo']]['noBrand'] = $val['brandCd'];
                                    }
                                }
                                // 무통장결제 중복 할인 설정 체크에 따른 할인율
                                foreach ($goodsBrandInfo[$val['goodsNo']] as $gKey => $gVal) {
                                    foreach ($orderMemberPolicy['dcBrandInfo']['cateCd'] as $mKey => $mVal) {
                                        if ($gKey == $mVal) {
                                            $orderMemberPolicy['dcPercent'] = ($orderMemberPolicy['dcBrandInfo']['goodsDiscount'][$mKey]);
                                        }
                                    }
                                }
                            }
                            if ($val['orgMemberDcPrice'] > 0) {
                                if (empty($orderMemberPolicy) == false) {
                                    $tmpMemberPolicyText[] = $orderMemberPolicy['groupNm'] . " : " . $arrMemberPolicyFixedOrderType[$orderMemberPolicy['fixedOrderTypeDc']] . " 구매금액 " . gd_currency_display($orderMemberPolicy['dcLine']) . " 이상 " .
                                        $orderMemberPolicy['dcPercent'] . "% 추가 할인";
                                } else {
                                    $tmpMemberPolicyText[] = "회원 추가 할인 : " . gd_money_format($val['orgMemberDcPrice']) . gd_currency_default() . "할인";
                                }
                            }
                            if ($val['orgMemberOverlapDcPrice'] > 0) {
                                if (empty($orderMemberPolicy) == false) {
                                    $tmpMemberPolicyText[] = $orderMemberPolicy['groupNm'] . " : " . $arrMemberPolicyFixedOrderType[$orderMemberPolicy['fixedOrderTypeOverlapDc']] . " 구매금액 " . gd_currency_display($orderMemberPolicy['overlapDcLine']) . " 이상 " .
                                        $orderMemberPolicy['overlapDcPercent'] . "% 중복 할인";
                                } else {
                                    $tmpMemberPolicyText[] = "회원 중복 할인 : " . gd_money_format($val['orgMemberOverlapDcPrice']) . gd_currency_default() . "할인";
                                }
                            }
                            $tmpMemberPolicy = implode('<br/>', $tmpMemberPolicyText);
                            $tmpData[] = '<td>' . $tmpMemberPolicy . '</td>';
                            unset($tmpMemberPolicyText);
                            break;
						// 웹앤모바일 2023-08-23 첫 배송일 추가	
						case "firstDelivery":
							$this->db->strField = "firstDelivery";
							$this->db->strWhere = "orderNo = {$val['orderNo']} AND sno = {$val['orderGoodsSno']}";
							$query = $this->db->query_complete();
							$sql = "SELECT".array_shift($query)."FROM ".DB_ORDER_GOODS.implode(' ' , $query);
							$firstDelivery = $this->db->fetch($sql);

							if($firstDelivery['firstDelivery']){
								$firstTime = strtotime($firstDelivery['firstDelivery']);
								
								$md = date('Y-m-d' , $firstTime);
								//$md = str_replace('-' , '월 ', $md);
								//$md .= '일';
								/*
								$daily = array('일','월','화','수','목','금','토');
								
								$w = $daily[date('w' , strtotime($firstDelivery['firstDelivery']))];
								$tmpData[] = '<td>' . $w.'요일' . '</td>';
								*/
								$tmpData[] = '<td>' . $md . '</td>';
							}else{
								$tmpData[] = '<td style="text-align:center;">-</td>';
							}
							
							break;	
                        default  :
                            if ($excelFieldName[$excelValue]['type'] == 'price' && $val[$excelValue] != '') {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            } else if ($excelFieldName[$excelValue]['type'] == 'mileage' && $val[$excelValue] != '') {
                                $tmpData[] = "<td>" . number_format($val[$excelValue]) . $this->mileageGiveInfo['basic']['unit'] . "</td>";
                            } else if ($excelFieldName[$excelValue]['type'] == 'deposit' && $val[$excelValue] != '') {
                                $tmpData[] = "<td>" . number_format($val[$excelValue]) . $this->depositInfo['unit'] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }
                            break;
                    }

                    unset($tmpOptionInfo);
                    unset($tmpOptionCode);

                }
                $tmpData[] = "</tr>";

                if ($key == '0') {
                    fwrite($fh, implode(chr(10), $setHedData));
                    unset($setHedData);
                }

                if ($whereCondition['optionCountFl'] === 'per') {
                    $tmpGoodsCnt = ($val['goodsCnt'] == '0' || empty($val['goodsCnt']) === true) ? 1 : $val['goodsCnt'];
                    for ($tmpDataCnt = 1; $tmpDataCnt <= $tmpGoodsCnt; $tmpDataCnt++) {
                        fwrite($fh, implode(chr(10), $tmpData));
                    }
                } else {
                    fwrite($fh, implode(chr(10), $tmpData));
                }
                unset($tmpData);
            }

            fwrite($fh, "</table>");
            fwrite($fh, $this->excelFooter);
            fclose($fh);

            $this->fileConfig['fileName'][] = $fileName . ".xls";
        }


        return true;
    }

    /*
    * 회차 배송 엑셀 파일 만들기 함수
    */
    public function generateScheduledDeliveryList($whereCondition, $excelField, $defaultField, $excelFieldName)
    {
        $orderAdmin = \App::load('\\Component\\Order\\OrderAdmin');

        $excelField = explode(STR_DIVISION, $excelField);

        $orderType = "order";
        $isGlobal = false;

        foreach ($excelField as $k => $v) {
            if ($defaultField[$v]['orderFl'] != 'y') {
                $orderType = "goods";
            }
            if (strpos($v, 'global_') !== false) {
                $isGlobal = true;
                $globalFiled[] = str_replace("global_", "", $v);
            }

            //임의의 추가항목이 있을 경우.
            $defaultAddField = '{addFieldNm}_';
            if (strstr($v, $defaultAddField)) {
                $tmpAddFieldName = str_replace($defaultAddField, '', $v);
                $excelFieldName[$v]['name'] = $tmpAddFieldName;
            }
        }

        //if(gd_isset($whereCondition['periodFl']) === null) $whereCondition['periodFl'] = "-1";
        gd_isset($whereCondition['periodFl'], 7);

        $userHandleMode = ['order_list_user_exchange', 'order_list_user_return', 'order_list_user_refund'];
        if (in_array($this->fileConfig['location'], $userHandleMode)) {
            $whereCondition['userHandleMode'] = $whereCondition['statusMode'];
            unset($whereCondition['statusMode']);
            $isUserHandle = true;
        } else {
            $isUserHandle = false;
        }

        // --- 검색 설정
        $orderData = $orderAdmin->fetchScheduledDeliveryListForAdminExcel($whereCondition, $whereCondition['periodFl'], $isUserHandle, $orderType, $excelField, 0, $this->excelPageNum);

        //튜닝한 업체를 위해 데이터 맞춤
        if (empty($orderData['totalCount']) === true && empty($orderData['orderList']) === true) {
            $isGenerator = false;
            $totalNum = count($orderData);
            if ($this->excelPageNum) $orderList = array_chunk($orderData, $this->excelPageNum, true);
            else $orderList = array_chunk($orderData, $totalNum, true);
        } else {
            $isGenerator = true;
            $totalNum = count($orderData['totalCount']);
            $orderList = $orderData['orderList'];
            if (gd_is_provider() && $orderType == 'goods') {
                $totalScmInfo = $orderData['totalScmInfo'];
            }
        }

        unset($goodsData);

        //값이 입력되지 않은 것으로 간주하고 전체 다운로드.
        if ($this->excelPageNum == 0) {
            $pageNum = 0;
        } else if ($this->excelPageNum >= $totalNum) {
            $pageNum = 0;
        } else {
            $pageNum = ceil($totalNum / $this->excelPageNum) - 1;
        }

        $setHedData[] = "<tr>";


        $arrTag = [
            'orderGoodsNm',
            'goodsNm',
            'orderGoodsNmStandard',
            'goodsNmStandard',
        ];


        for ($i = 0; $i <= $pageNum; $i++) {

            $fileName = $this->fileConfig['location'] . array_sum(explode(' ', microtime()));
            $tmpFilePath = UserFilePath::data('excel', $this->fileConfig['menu'], $fileName . ".xls")->getRealPath();

            $fh = fopen($tmpFilePath, 'a+');
            fwrite($fh, $this->excelHeader . "<table border='1'>");

            if ($isGenerator) {
                if ($i == '0') {
                    $data = $orderList;
                } else {
                    $data = $orderAdmin->fetchScheduledDeliveryListForAdminExcel($whereCondition, $whereCondition['periodFl'], $isUserHandle, $orderType, $excelField, $i, $this->excelPageNum)['orderList'];
                }
            } else {
                $data = $orderList[$i];
            }

            foreach ($data as $key => $val) {
                $progress = round((100 / ($totalNum - 1)) * ($key + ($i * $this->excelPageNum)));
                if ($progress % 20 == 0 || $progress == '100') {
                    echo "<script> parent.progressExcel('" . gd_isset($progress, 0) . "'); </script>";
                }

                if ($val['orderGoodsNmStandard']) {
                    list($val['orderGoodsNm'], $val['orderGoodsNmStandard']) = [$val['orderGoodsNmStandard'], $val['orderGoodsNm']];
                }

                if ($val['goodsNmStandard']) {
                    list($val['goodsNm'], $val['goodsNmStandard']) = [$val['goodsNmStandard'], $val['goodsNm']];
                }

                if ($whereCondition['statusMode'] === 'o') {
                    // 입금대기리스트에서 '주문상품명' 을 입금대기 상태의 주문상품명만 구성
                    $noPay = (int)$val['noPay'] - 1;
                    if (trim($val['goodsNm']) !== '') {
                        if ($noPay > 0) {
                            $val['orderGoodsNm'] = $val['goodsNm'] . ' 외 ' . $noPay . ' 건';
                        } else {
                            $val['orderGoodsNm'] = $val['goodsNm'];
                        }
                    }
                    if (trim($val['goodsNmStandard']) !== '') {
                        if ($noPay > 0) {
                            $val['orderGoodsNmStandard'] = $val['goodsNmStandard'] . ' ' . __('외') . ' ' . $noPay . ' ' . __('건');
                        } else {
                            $val['orderGoodsNmStandard'] = $val['goodsNmStandard'];
                        }
                    }
                }

                if ($isGlobal && $val['mallSno'] != $this->gGlobal['defaultMallSno']) {
                    $val['currencyPolicy'] = json_decode($val['currencyPolicy'], true);
                    $val['exchangeRatePolicy'] = json_decode($val['exchangeRatePolicy'], true);
                    $val['currencyIsoCode'] = $val['currencyPolicy']['isoCode'];
                    $val['exchangeRate'] = $val['exchangeRatePolicy']['exchangeRate' . $val['currencyPolicy']['isoCode']];

                    foreach ($globalFiled as $globalKey => $globalValue) {
                        $val["global_" . $globalValue] = NumberUtils::globalOrderCurrencyDisplay($val[$globalValue], $val['exchangeRate'], $val['currencyPolicy']);
                    }
                }

                if ($val['refundBankName']) {
                    $_tmpRefundAccount[] = $val['refundBankName'];
                    $_tmpRefundAccountNumber[] = $val['refundBankName'];
                }
                if ($val['refundAccountNumber'] && gd_str_length($val['refundAccountNumber']) > 50) {
                    $val['refundAccountNumber'] = \Encryptor::decrypt($val['refundAccountNumber']);
                }
                if ($val['userRefundAccountNumber'] && gd_str_length($val['userRefundAccountNumber']) > 50) {
                    $val['userRefundAccountNumber'] = \Encryptor::decrypt($val['userRefundAccountNumber']);
                }
                if ($val['refundAccountNumber']) {
                    $_tmpRefundAccount[] = $val['refundAccountNumber'];
                }
                if ($val['userRefundAccountNumber']) {
                    $_tmpRefundAccountNumber[] = $val['userRefundAccountNumber'];
                }

                if ($val['refundDepositor']) {
                    $_tmpRefundAccount[] = $val['refundDepositor'];
                    $_tmpRefundAccountNumber[] = $val['refundDepositor'];
                }

                if (empty($_tmpRefundAccount) == false) {
                    $val['refundAccountNumber'] = implode(STR_DIVISION, $_tmpRefundAccount);
                    unset($_tmpRefundAccount);
                }
                if (empty($_tmpRefundAccountNumber) == false) {
                    $val['userRefundAccountNumber'] = implode(STR_DIVISION, $_tmpRefundAccountNumber);
                    unset($_tmpRefundAccountNumber);
                }

                // 상품 무게 및 용량
                if ($val['goodsWeight'] || $val['goodsVolume']) {
                    $val['g_goodsWeight'] = ($val['goodsWeight'] === '0.00') ? '0' : number_format((float)$val['goodsWeight'] / $val['goodsCnt'], 2, '.', '');
                    $val['g_goodsVolume'] = ($val['goodsVolume'] === '0.00') ? '0' : number_format((float)$val['goodsVolume'] / $val['goodsCnt'], 2, '.', '');
                    $val['goodsWeightVolume'] = $val['g_goodsWeight'] . gd_isset(Globals::get('gWeight.unit'), 'kg') . '|' . $val['g_goodsVolume'] . gd_isset(Globals::get('gVolume.unit'), '㎖');

                    // 총 무게 및 용량
                    $val['goodsWeight'] = ($val['goodsWeight'] === '0.00') ? '0' : $val['goodsWeight'];
                    $val['goodsVolume'] = ($val['goodsVolume'] === '0.00') ? '0' : $val['goodsVolume'];
                    $val['goodsTotalWeightVolume'] = $val['goodsWeight'] . gd_isset(Globals::get('gWeight.unit'), 'kg') . '|' . $val['goodsVolume'] . gd_isset(Globals::get('gVolume.unit'), '㎖');
                }

                if (gd_is_provider()) {
                    if ($orderType == 'goods') {
                        $val['scmOrderCnt'] = $totalScmInfo[$val['orderNo']]['scmOrderCnt'];
                        $val['scmGoodsCnt'] = $totalScmInfo[$val['orderNo']]['scmGoodsCnt'];
                        $val['scmGoodsNm'] = $totalScmInfo[$val['orderNo']]['scmGoodsNm'];
                        $val['totalGoodsPrice'] = $totalScmInfo[$val['orderNo']]['totalGoodsPrice'];
                        $tmpScmDeliveryCharge = explode(STR_DIVISION, $totalScmInfo[$val['orderNo']]['scmDeliveryCharge']);
                        $tmpScmDeliverySno = explode(STR_DIVISION, $totalScmInfo[$val['orderNo']]['scmDeliverySno']);
                        $val['scmDeliveryCharge'] = array_sum(array_combine($tmpScmDeliverySno, $tmpScmDeliveryCharge));
                    } else {
                        $tmpScmDeliveryCharge = explode(STR_DIVISION, $val['scmDeliveryCharge']);
                        $tmpScmDeliverySno = explode(STR_DIVISION, $val['scmDeliverySno']);
                        $val['scmDeliveryCharge'] = array_sum(array_combine($tmpScmDeliverySno, $tmpScmDeliveryCharge));
                    }
                    $val['scmGoodsNm'] = gd_htmlspecialchars(gd_htmlspecialchars_stripslashes(StringUtils::stripOnlyTags($val['scmGoodsNm'])));
                    unset($tmpScmDeliveryCharge);
                    unset($tmpScmDeliverySno);
                }

                $tmpData[] = "<tr>";
                foreach ($excelField as $excelKey => $excelValue) {

                    if (!$excelFieldName[$excelValue]) continue;

                    if (in_array($excelValue, $arrTag)) {
                        $val[$excelValue] = gd_htmlspecialchars(gd_htmlspecialchars_stripslashes(StringUtils::stripOnlyTags($val[$excelValue])));
                    }

                    if ($key == '0') {
                        $setHedData[] = "<td class='title'>" . $excelFieldName[$excelValue]['name'] . "</td>";
                    }

                    $tmpOptionInfo = [];
                    $tmpOptionCode = [];
                    if ($val['optionInfo']) {
                        $tmpOption = json_decode(gd_htmlspecialchars_stripslashes($val['optionInfo']), true);
                        foreach ($tmpOption as $optionKey => $optionValue) {
                            if ($optionValue[2]) $tmpOptionCode[] = $optionValue[2];
                            $tmpOptionInfo[] = $optionValue[0] . " : " . $optionValue[1];
                        }
                        unset($tmpOption);
                    }

                    switch ($excelValue) {
                        case 'orderGoodsCnt':
                            if (gd_is_provider()) {
                                $tmpData[] = "<td>" . $val['scmOrderCnt'] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }
                            break;
                        case 'orderGoodsNm':
                            if (gd_is_provider()) {
                                if ($val['scmOrderCnt'] == 1) {
                                    $tmpData[] = "<td>" . $val['scmGoodsNm'] . "</td>";
                                } else {
                                    $tmpData[] = "<td>" . $val['scmGoodsNm'] . " 외 " . ($val['scmOrderCnt'] - 1) . " 건</td>";
                                }
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }

                            break;
                        case 'totalSettlePrice':
                            if (gd_is_provider()) {
                                $tmpData[] = "<td>" . ($val['totalOrderGoodsPrice'] + $val['scmDeliveryCharge']) . "</td>";
                            } else {
                                if ($val['orderChannelFl'] === 'naverpay') {
                                    $checkoutData = json_decode($val['checkoutData'], true);
                                    $tmpData[] = "<td>" . $checkoutData['orderData']['GeneralPaymentAmount'] . " </td>";
                                } else {
                                    $tmpData[] = "<td>" . $val['totalRealSettlePrice'] . " </td>";
                                }
                            }
                            break;
                        case 'totalDeliveryCharge':
                            if (gd_is_provider()) {
                                $tmpData[] = "<td>" . $val['scmDeliveryCharge'] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }
                            break;
                        case 'goodsDeliveryCollectFl':
                            if ($val['goodsDeliveryCollectFl'] == 'pre') {
                                $tmpData[] = "<td>선불</td>";
                            } else {
                                $tmpData[] = "<td>착불</td>";
                            }
                            break;
                        case 'mallSno':
                            $tmpData[] = "<td>" . $this->gGlobal['mallList'][$val['mallSno']]['mallName'] . "</td>";
                            break;
                        case 'hscode':

                            if ($val['hscode']) {

                                $hscode = json_decode(gd_htmlspecialchars_stripslashes($val['hscode']), true);

                                if ($hscode) {
                                    foreach ($hscode as $k1 => $v1) {
                                        $_tmpHsCode[] = $k1 . " : " . $v1;
                                    }

                                    $tmpData[] = "<td>" . implode("<br>", $_tmpHsCode) . "</td>";
                                    unset($_tmpHsCode);

                                } else {
                                    $tmpData[] = "<td></td>";
                                }

                                unset($hscode);

                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;
                        case 'addField':
                            unset($excelAddField);
                            $addFieldData = json_decode($val[$excelValue], true);
                            $excelAddField[] = "<td><table border='1'><tr>";
                            foreach ($addFieldData as $addFieldKey => $addFieldVal) {
                                if ($addFieldVal['process'] == 'goods') {
                                    foreach ($addFieldVal['data'] as $addDataKey => $addDataVal) {
                                        $goodsVal = $addDataVal;
                                        if ($addFieldVal['type'] == 'text' && $addFieldVal['encryptor'] == 'y') {
                                            $goodsVal = Encryptor::decrypt($goodsVal);
                                        }
                                        $excelAddField[] = "<td>" . $addFieldVal['name'] . " (" . $addFieldVal['goodsNm'][$addDataKey] . ") : " . $goodsVal . "</td>";
                                    }
                                } else {
                                    $excelAddField[] = "<td>" . $addFieldVal['name'] . " : ";
                                    $goodsVal = $addFieldVal['data'];
                                    if ($addFieldVal['type'] == 'text' && $addFieldVal['encryptor'] == 'y') {
                                        $goodsVal = Encryptor::decrypt($goodsVal);
                                    }
                                    $excelAddField[] = $goodsVal;
                                    $excelAddField[] = "</td>";
                                }
                            }
                            if ($val['orderChannelFl'] == 'naverpay') {
                                $checkoutData = json_decode($val['checkoutData'], true);
                                if (empty($checkoutData['orderGoodsData']['IndividualCustomUniqueCode']) === false) {
                                    $excelAddField[] = "<td> 개인통관 고유번호(네이버) : " . $checkoutData['orderGoodsData']['IndividualCustomUniqueCode'] . "</td>";
                                }
                            }
                            if ($val['orderChannelFl'] == 'payco') {
                                $paycoDataField = empty($val['fintechData']) === false ? 'fintechData' : 'checkoutData';
                                if (empty($val[$paycoDataField]) === false) {
                                    $paycoData = json_decode($val[$paycoDataField], true);
                                    if ($paycoData['individualCustomUniqNo']) {
                                        $excelAddField[] = "<td> 개인통관 고유번호(페이코) : " . $paycoData['individualCustomUniqNo'] . "</td>";
                                    }
                                }
                            }
                            $excelAddField[] = "</tr></table></td>";
                            $tmpData[] = implode('', $excelAddField);
                            break;
                        case 'goodsType':
                            if ($val['goodsType'] == 'addGoods') {
                                $tmpData[] = "<td>추가</td>";
                            } else {
                                $tmpData[] = "<td>일반</td>";
                            }

                            break;
                        case 'goodsNm':
                            if ($val['goodsType'] == 'addGoods') {
                                $tmpData[] = "<td><span style='color:red'>[" . __('추가') . "]</span>" . $val[$excelValue] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }

                            break;
                        case 'optionInfo':
                            if ($tmpOptionInfo) {
                                $tmpData[] = "<td>" . implode("<br/>", $tmpOptionInfo) . "</td>";
                            } else {
                                $tmpData[] = "<td></td>";
                            }
                            break;
                        case 'optionCode':
                            if ($tmpOptionCode) {
                                $tmpData[] = "<td class='xl24'>" . implode("<br/>", $tmpOptionCode) . "</td>";
                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;
                        case 'optionTextInfo':
                            $tmpOptionTextInfo = [];
                            if ($val[$excelValue]) {
                                $tmpOption = json_decode($val[$excelValue], true);
                                foreach ($tmpOption as $optionKey => $optionValue) {
                                    //$tmpOptionTextInfo[] = $optionValue[0] . " : " . $optionValue[1] . " / " . __('옵션가') . " : " . $optionValue[2];
                                    $tmpOptionTextInfo[] = gd_htmlspecialchars_stripslashes($optionValue[0]) . " : " . gd_htmlspecialchars_stripslashes($optionValue[1]);
                                }
                            }
                            if ($tmpOptionTextInfo) {
                                $tmpData[] = "<td>" . implode("<br/>", $tmpOptionTextInfo) . "</td>";
                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            unset($tmpOptionTextInfo);
                            unset($tmpOption);
                            break;
                        case 'orderStatus':
                            $tmpData[] = "<td>" . $orderAdmin->getOrderStatusAdmin($val[$excelValue]) . "</td>";
                            break;
                        case 'settleKind':
                            $settleKind = $orderAdmin->printSettleKind($val[$excelValue]);
                            switch ($val['settleKind']) {
                                case 'pk' : // 카카오페이 결제수단 구분 추가
                                    $settleKind .= '<br />' . self::KAKAOPAY_PAY_METHOD[$val['pgSettleNm']];
                                    break;
                            }
                            $tmpData[] = "<td>" . $settleKind . "</td>";
                            break;
                        // 숫자 처리 - 주문 번호 및 우편번호(5자리) 숫자에 대한 처리
                        case 'orderNo':
                        case 'apiOrderNo':
                        case 'apiOrderGoodsNo':
                        case 'receiverZonecode':
                        case 'invoiceNo':
                        case 'pgTid':
                        case 'pgAppNo':
                        case 'pgResultCode':
                        case 'orderPhone':
                        case 'orderCellPhone':
                        case 'receiverPhone':
                            $tmpData[] = "<td class=\"xl24\">" . $val[$excelValue] . "</td>";
                            break;

                        case 'totalGift':
                        case 'ogi.presentSno':
                        case 'ogi.giftNo':
                            $gift = $orderAdmin->getOrderGift($val['orderNo'], $val['scmNo'], 40);
                            $presentTitle = [];
                            $giftInfo = [];
                            $totalGift = [];
                            if ($gift) {
                                foreach ($gift as $gk => $gv) {
                                    $presentTitle[] = $gv['presentTitle'];
                                    $giftInfo[] = $gv['giftNm'] . INT_DIVISION . $gv['giveCnt'] . "개";
                                    $totalGift[] = $gv['presentTitle'] . INT_DIVISION . $gv['giftNm'] . INT_DIVISION . $gv['giveCnt'] . "개";
                                }

                                if ($excelValue == 'ogi.presentSno') {
                                    $tmpData[] = "<td>" . implode("<br>", $presentTitle) . "</td>";
                                } else if ($excelValue == 'ogi.giftNo') {
                                    $tmpData[] = "<td>" . implode("<br>", $giftInfo) . "</td>";
                                } else if ($excelValue == 'totalGift') {
                                    $tmpData[] = "<td>" . implode("<br>", $totalGift) . "</td>";
                                }
                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;

                        case 'memNo':
                            $tmpData[] = "<td class=\"xl24\">" . $val['memId'] . "</td>";
                            break;
                        case 'receiverAddressTotal':
                            $receiverAddress = $val['receiverAddress'] . " " . $val['receiverAddressSub'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitAddress'])) === false) $receiverAddress = $val['visitAddress'];
                            if ($val['mallSno'] != $this->gGlobal['defaultMallSno']) {
                                $countriesCode = $orderAdmin->getCountriesList();
                                $countriesCode = array_combine(array_column($countriesCode, 'code'), array_column($countriesCode, 'countryNameKor'));
                                $tmpData[] = "<td>" . $countriesCode[$val['receiverCountryCode']] . " " . $val['receiverCity'] . " " . $val['receiverState'] . " " . $receiverAddress . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $receiverAddress . "</td>";
                            }
                            break;
                        case 'receiverAddress':
                            $receiverAddress = $val['receiverAddress'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitAddress'])) === false) $receiverAddress = $val['visitAddress'];
                            $tmpData[] = "<td class='xl24'>" . $receiverAddress . "</td>";
                            break;
                        case 'receiverAddressSub':
                            $receiverAddressSub = $val['receiverAddressSub'];
                            if ($val['deliveryMethodFl'] == 'visit') $receiverAddressSub = '';
                            $tmpData[] = "<td class='xl24'>" . $receiverAddressSub . "</td>";
                            break;
                        case 'receiverName':
                            $receiverName = $val['receiverName'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitName'])) === false) $receiverName = $val['visitName'];
                            $tmpData[] = "<td class='xl24'>" . $receiverName . "</td>";
                            break;
                        case 'receiverCellPhone':
                            $receiverCellPhone = $val['receiverCellPhone'];
                            if ($val['deliveryMethodFl'] == 'visit' && empty(trim($val['visitPhone'])) === false) $receiverCellPhone = $val['visitPhone'];
                            $tmpData[] = "<td class='xl24'>" . $receiverCellPhone . "</td>";
                            break;
                        case 'addGoodsNo':

                            $addGoods = $orderAdmin->getOrderAddGoods(
                                $val['orderNo'],
                                $val['orderCd'],
                                [
                                    'sno',
                                    'addGoodsNo',
                                    'goodsNm',
                                    'goodsCnt',
                                    'goodsPrice',
                                    'optionNm',
                                    'goodsImage',
                                    'addMemberDcPrice',
                                    'addMemberOverlapDcPrice',
                                    'addCouponGoodsDcPrice',
                                    'addGoodsMileage',
                                    'addMemberMileage',
                                    'addCouponGoodsMileage',
                                    'divisionAddUseDeposit',
                                    'divisionAddUseMileage',
                                    'divisionAddCouponOrderDcPrice',
                                ]
                            );

                            $addGoodsInfo = [];
                            if ($addGoods) {
                                foreach ($addGoods as $av => $ag) {
                                    $addGoodsInfo[] = $ag['goodsNm'];
                                }
                                $tmpData[] = "<td>" . implode("<br>", $addGoodsInfo) . "</td>";

                            } else {
                                $tmpData[] = "<td></td>";
                            }

                            break;
                        case 'orderMemo' :
                            if ($val['orderChannelFl'] == 'naverpay') {
                                $checkoutData = json_decode($val['checkoutData'], true);
                                $tmpData[] = "<td>" . $checkoutData['orderGoodsData']['ShippingMemo'] . "</td>";
                            } else {
                                $orderMemo = $val['orderMemo'];
                                if ($val['deliveryMethodFl'] == 'y') $orderMemo = $val['visitMemo'];
                                $tmpData[] = "<td>" . $orderMemo . "</td>";
                            }
                            break;

                        case 'packetCodeFl' :
                            $tmpData[] = (trim($val['packetCode'])) ? "<td>y</td>" : "<td>n</td>";
                            break;

                        //복수배송지 배송지
                        case 'multiShippingOrder' :
                            $multiShippingName = ((int)$val['orderInfoCd'] === 1) ? '메인 배송지' : '추가' . ((int)$val['orderInfoCd'] - 1) . ' 배송지';
                            $tmpData[] = "<td>" . $multiShippingName . "</td>";
                            break;

                        //복수배송지 배송지별배송비
                        case 'multiShippingPrice' :
                            $tmpData[] = "<td>" . $val['deliveryCharge'] . "</td>";
                            break;

                        // 안심번호 (사용하지 않을경우 휴대폰번호 노출)
                        case 'receiverSafeNumber':
                            if ($val['receiverUseSafeNumberFl'] == 'y' && empty($val['receiverSafeNumber']) == false && empty($val['receiverSafeNumberDt']) == false && DateTimeUtils::intervalDay($val['receiverSafeNumberDt'], date('Y-m-d H:i:s')) <= 30) {
                                $tmpData[] = "<td class='xl24'>" . $val['receiverSafeNumber'] . "</td>";
                            } else {
                                $tmpData[] = "<td class='xl24'>" . $val['receiverCellPhone'] . "</td>";
                            }

                            break;
                        case 'userHandleInfo':
                            $userHandleInfo = '';
                            if ($whereCondition['userHandleViewFl'] != 'y') {
                                $userHandleInfo = $orderAdmin->getUserHandleInfo($val['orderNo'], $val['orderGoodsSno'])[0];
                            }
                            $tmpData[] = "<td>" . $userHandleInfo . "</td>";

                            break;
                        case 'goodsCnt' :
                            $tmpOrderCnt = $val[$excelValue];
                            if ($whereCondition['optionCountFl'] === 'per') {
                                $tmpOrderCnt = 1;
                            }
                            $tmpData[] = "<td>" . $tmpOrderCnt . "</td>";
                            break;
                        case 'orderChannelFl':
                            $channel = $val[$excelValue];
                            if ($val['trackingKey']) $channel .= '<br />페이코쇼핑';
                            $tmpData[] = "<td>" . $channel . "</td>";
                            break;
                        case 'orderTypeFl':
                            // 주문유형
                            if ($val['orderTypeFl'] == 'pc') {
                                $tmpData[] = "<td>PC쇼핑몰</td>";
                            } else if ($val['orderTypeFl'] == 'mobile') {
                                if (empty($val['appOs']) === true && empty($val['pushCode']) === true) {
                                    $tmpData[] = "<td>모바일쇼핑몰 - WEB</td>";
                                } else {
                                    $tmpData[] = "<td>모바일쇼핑몰 - APP</td>";
                                }
                            } else {
                                $tmpData[] = "<td>수기주문</td>";
                            }

                            break;
                        case 'totalGoodsPriceByGoods':
                            $tmpData[] = "<td>" . (($val['goodsPrice'] + $val['optionPrice'] + $val['optionTextPrice']) * $val['goodsCnt']) . "</td>";
                            break;
                        case 'goodsPriceWithOption':
                            $tmpData[] = "<td>" . ($val['goodsPrice'] + $val['optionPrice'] + $val['optionTextPrice']) . "</td>";
                            break;
                        case 'useCouponNm': // 사용된 쿠폰명
                            if ($val['couponGoodsDcPrice'] <= 0 && $val['totalCouponDeliveryDcPrice'] <= 0) {
                                $tmpData[] = '<td></td>';
                                break;
                            }
                            if ($val['goodsType'] == 'addGoods') {
                                $tmpData[] = '<td>' . $tmpCouponNm . '</td>';
                                break;
                            }
                            $tmpGoodsCouponNmText = $tmpOrderCouponNmText = $tmpDeliveryCouponNmText = [];
                            $orderCouponData = $orderAdmin->getOrderCoupon($val['orderNo']);
                            foreach ($orderCouponData as $couponVal) {
                                $couponBenefitType = ($couponVal['couponBenefitType'] == 'fix') ? gd_currency_default() : '%';
                                // 상품 쿠폰
                                if ($val['couponGoodsDcPrice'] > 0) {
                                    if ($couponVal['couponKindType'] == 'sale' && $couponVal['couponUseType'] == 'product') {
                                        $orderGoodsStatusMode = substr($val['orderStatus'], 0, 1); // 주문상품상태값
                                        $orderCancelFl = false; // 주문 교환/환불/취소/반품 구분
                                        if ($orderGoodsStatusMode == 'c' || $orderGoodsStatusMode == 'e' || $orderGoodsStatusMode == 'r' || $orderGoodsStatusMode == 'b') {
                                            $orderCancelFl = true;
                                        }
                                        if ($val['goodsNo'] == $couponVal['goodsNo'] && ($val['orderCd'] == $couponVal['orderCd']) || $orderCancelFl) {
                                            $tmpGoodsCouponNmText[] = $couponVal['couponNm'] . " : " . gd_money_format($couponVal['couponBenefit']) . $couponBenefitType . " 할인";
                                        }
                                    }
                                }
                                // 주문적용 쿠폰
                                if ($val['totalCouponOrderDcPrice'] > 0 || $val['divisionCouponOrderDcPrice'] > 0) {
                                    if ($couponVal['couponKindType'] == 'sale' && $couponVal['couponUseType'] == 'order') {
                                        if ($val['divisionCouponOrderDcPrice'] > 0) { // 주문적용 쿠폰인 경우
                                            $tmpOrderCouponNmText[] = $couponVal['couponNm'] . " : " . gd_money_format($couponVal['couponBenefit']) . $couponBenefitType . " 할인";
                                        }
                                    }
                                }
                                // 배송비 쿠폰
                                if ($val['totalCouponDeliveryDcPrice'] > 0) { // 배송비적용 쿠폰
                                    if ($couponVal['couponKindType'] == 'delivery' || $couponVal['couponUseType'] == 'delivery') {
                                        $tmpDeliveryCouponNmText[] = $couponVal['couponNm'] . " : " . gd_money_format($couponVal['couponBenefit']) . $couponBenefitType . " 할인";
                                    }
                                }
                            }
                            $tmpCouponNmText = array_merge($tmpGoodsCouponNmText, array_merge($tmpOrderCouponNmText, $tmpDeliveryCouponNmText));
                            $tmpCouponNm = implode('<br/>', $tmpCouponNmText);
                            $tmpData[] = '<td>' . $tmpCouponNm . '</td>';
                            break;
                        case 'goodsDiscountInfo':
                            if ($val['goodsDcPrice'] > 0) {
                                $goodsDiscountInfo = json_decode($val[$excelValue], true);
                                $arrDiscountGroup = array('member' => '| 회원전용 |', 'group' => '| 특정회원등급 |');
                                $arrNewGoodsReg = array('regDt' => '등록일', 'modDt' => '수정일');
                                $arrNewGoodsDate = array('day' => '일', 'hour' => '시간');
                                $benefitNm = '개별설정 ';
                                if (empty($goodsDiscountInfo['benefitNm']) == false && $goodsDiscountInfo != null) {
                                    $benefitNm = $goodsDiscountInfo['benefitNm'];
                                }
                                $divisionText = ''; // 기간할인 구분자 초기화
                                if (!$arrDiscountGroup[$goodsDiscountInfo['goodsDiscountGroup']]) $divisionText = " | ";  // 배열에 값이 없을 경우 기간할인 구분자 삽입
                                if ($goodsDiscountInfo['goodsDiscountUnit'] == 'price') $goodsDiscountPricePrint = ' | ' . gd_currency_symbol() . gd_money_format($goodsDiscountInfo['goodsDiscount']) . gd_currency_string();
                                else $goodsDiscountPricePrint = ' | ' . $goodsDiscountInfo['goodsDiscount'] . '%';
                                if ($goodsDiscountInfo['benefitUseType'] == 'nonLimit') { // 제한 없음
                                    $benefitPeriod = '';
                                } else if ($goodsDiscountInfo['benefitUseType'] == 'newGoodsDiscount') { // 등록일 기준
                                    $benefitPeriod = $divisionText . ' 상품' . $arrNewGoodsReg[$goodsDiscountInfo['newGoodsRegFl']] . '부터 ' . $goodsDiscountInfo['newGoodsDate'] . $arrNewGoodsDate[$goodsDiscountInfo['newGoodsDateFl']] . '까지';
                                } else { // 기간 제한
                                    $benefitPeriod = $divisionText . " " . gd_date_format("Y-m-d H:i", $goodsDiscountInfo['periodDiscountStart']) . ' ~ ' . gd_date_format("Y-m-d H:i", $goodsDiscountInfo['periodDiscountEnd']);
                                }
                                if (empty($goodsDiscountInfo) === false || $goodsDiscountInfo != null) {
                                    $tmpGoodsDiscountInfoText[] = $benefitNm . $benefitPeriod . $goodsDiscountPricePrint . "할인";
                                } else {
                                    $tmpGoodsDiscountInfoText[] = '상품 할인 | ' . gd_money_format($val['goodsDcPrice']) . gd_currency_default() . "할인";
                                }
                            }
                            $tmpGoodsDiscountInfo = implode('<br/>', $tmpGoodsDiscountInfoText);
                            $tmpData[] = '<td>' . $tmpGoodsDiscountInfo . '</td>';
                            unset($tmpGoodsDiscountInfoText);
                            break;
                        case 'memberPolicy':
                            $orderMemberPolicy = json_decode($val[$excelValue], true);
                            $arrMemberPolicyFixedOrderType = array('option' => '옵션별', 'goods' => '상품별', 'order' => '주문별', 'brand' => '브랜드별');
                            if ($orderMemberPolicy['fixedOrderTypeDc'] == 'brand') {
                                //회원등급 > 브랜드별 추가할인 상품 브랜드 정보
                                if (in_array($val['brandCd'], $orderMemberPolicy['dcBrandInfo']['cateCd'])) {
                                    $goodsBrandInfo[$val['goodsNo']][$val['brandCd']] = $val['brandCd'];
                                } else {
                                    if ($val['brandCd']) {
                                        $goodsBrandInfo[$val['goodsNo']]['allBrand'] = $val['brandCd'];
                                    } else {
                                        $goodsBrandInfo[$val['goodsNo']]['noBrand'] = $val['brandCd'];
                                    }
                                }
                                // 무통장결제 중복 할인 설정 체크에 따른 할인율
                                foreach ($goodsBrandInfo[$val['goodsNo']] as $gKey => $gVal) {
                                    foreach ($orderMemberPolicy['dcBrandInfo']['cateCd'] as $mKey => $mVal) {
                                        if ($gKey == $mVal) {
                                            $orderMemberPolicy['dcPercent'] = ($orderMemberPolicy['dcBrandInfo']['goodsDiscount'][$mKey]);
                                        }
                                    }
                                }
                            }
                            if ($val['orgMemberDcPrice'] > 0) {
                                if (empty($orderMemberPolicy) == false) {
                                    $tmpMemberPolicyText[] = $orderMemberPolicy['groupNm'] . " : " . $arrMemberPolicyFixedOrderType[$orderMemberPolicy['fixedOrderTypeDc']] . " 구매금액 " . gd_currency_display($orderMemberPolicy['dcLine']) . " 이상 " .
                                        $orderMemberPolicy['dcPercent'] . "% 추가 할인";
                                } else {
                                    $tmpMemberPolicyText[] = "회원 추가 할인 : " . gd_money_format($val['orgMemberDcPrice']) . gd_currency_default() . "할인";
                                }
                            }
                            if ($val['orgMemberOverlapDcPrice'] > 0) {
                                if (empty($orderMemberPolicy) == false) {
                                    $tmpMemberPolicyText[] = $orderMemberPolicy['groupNm'] . " : " . $arrMemberPolicyFixedOrderType[$orderMemberPolicy['fixedOrderTypeOverlapDc']] . " 구매금액 " . gd_currency_display($orderMemberPolicy['overlapDcLine']) . " 이상 " .
                                        $orderMemberPolicy['overlapDcPercent'] . "% 중복 할인";
                                } else {
                                    $tmpMemberPolicyText[] = "회원 중복 할인 : " . gd_money_format($val['orgMemberOverlapDcPrice']) . gd_currency_default() . "할인";
                                }
                            }
                            $tmpMemberPolicy = implode('<br/>', $tmpMemberPolicyText);
                            $tmpData[] = '<td>' . $tmpMemberPolicy . '</td>';
                            unset($tmpMemberPolicyText);
                            break;
						// 웹앤모바일 2023-08-23 첫 배송일 추가	
						case "firstDelivery":
							$this->db->strField = "firstDelivery";
							$this->db->strWhere = "orderNo = {$val['orderNo']} AND sno = {$val['orderGoodsSno']}";
							$query = $this->db->query_complete();
							$sql = "SELECT".array_shift($query)."FROM ".DB_ORDER_GOODS.implode(' ' , $query);
							$firstDelivery = $this->db->fetch($sql);

							if($firstDelivery['firstDelivery']){
								$firstTime = strtotime($firstDelivery['firstDelivery']);
								
								$md = date('Y-m-d' , $firstTime);
								//$md = str_replace('-' , '월 ', $md);
								//$md .= '일';
								/*
								$daily = array('일','월','화','수','목','금','토');
								
								$w = $daily[date('w' , strtotime($firstDelivery['firstDelivery']))];
								$tmpData[] = '<td>' . $w.'요일' . '</td>';
								*/
								$tmpData[] = '<td>' . $md . '</td>';
							}else{
								$tmpData[] = '<td style="text-align:center;">-</td>';
							}
							
							break;	
                        default  :
                            if ($excelFieldName[$excelValue]['type'] == 'price' && $val[$excelValue] != '') {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            } else if ($excelFieldName[$excelValue]['type'] == 'mileage' && $val[$excelValue] != '') {
                                $tmpData[] = "<td>" . number_format($val[$excelValue]) . $this->mileageGiveInfo['basic']['unit'] . "</td>";
                            } else if ($excelFieldName[$excelValue]['type'] == 'deposit' && $val[$excelValue] != '') {
                                $tmpData[] = "<td>" . number_format($val[$excelValue]) . $this->depositInfo['unit'] . "</td>";
                            } else {
                                $tmpData[] = "<td>" . $val[$excelValue] . "</td>";
                            }
                            break;
                    }

                    unset($tmpOptionInfo);
                    unset($tmpOptionCode);

                }
                $tmpData[] = "</tr>";

                if ($key == '0') {
                    fwrite($fh, implode(chr(10), $setHedData));
                    unset($setHedData);
                }

                if ($whereCondition['optionCountFl'] === 'per') {
                    $tmpGoodsCnt = ($val['goodsCnt'] == '0' || empty($val['goodsCnt']) === true) ? 1 : $val['goodsCnt'];
                    for ($tmpDataCnt = 1; $tmpDataCnt <= $tmpGoodsCnt; $tmpDataCnt++) {
                        fwrite($fh, implode(chr(10), $tmpData));
                    }
                } else {
                    fwrite($fh, implode(chr(10), $tmpData));
                }
                unset($tmpData);
            }
            fwrite($fh, "</table>");
            fwrite($fh, $this->excelFooter);
            fclose($fh);

            $this->fileConfig['fileName'][] = $fileName . ".xls";
        }

        return true;
    }
}
