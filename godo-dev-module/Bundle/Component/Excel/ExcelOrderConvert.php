<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2022, NHN COMMERCE Corp.
 */

namespace Bundle\Component\Excel;

use App;
use Component\Database\DBTableField;
use Component\Delivery\Delivery;
use Component\Godo\NaverPayAPI;
use Component\Mail\MailMimeAuto;
use Component\Member\MemberDAO;
use Component\Member\Manager;
use Component\Sms\Code;
use Component\Sms\SmsAutoCode;
use Framework\Utility\StringUtils;
use Session;

/**
 * Class ExcelOrderConvert
 *
 * 주문 Excel 업로드 Class
 *
 * @package Bundle\Component\Excel
 * @author sueun-choi <sueun-choi@nhn-commerce.com>
 */
class ExcelOrderConvert extends \Component\Excel\ExcelDataConvert
{

    protected $skipSendOrderInfo = false;

    public $_arrIncludeOg = [
        'orderNo',
        'invoiceCompanySno',
        'invoiceNo',
        'deliveryDt',
        'deliveryCompleteDt',
        'orderStatus',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 받아온 엑셀 파일과 기존 주문테이블의 송장번호를 비교해 일치하는 데이터가 있으면 반환
     * 송장 일괄등록 전 체크용도
     *
     * @param $files
     *
     * @return array
     * @throws Exception
     * @author sueun-choi <sueun-choi@nhn-commerce.com>
     */
    public function checkOrderInvoiceExcel($files)
    {
        if ($files['excel']['error'] > 0) {
            throw new Exception(__('엑셀 화일이 존재하지 않습니다. 다운 로드 받으신 엑셀파일을 이용해서 "Excel 97-2003 통합문서" 로 저장이 된 엑셀 파일만 가능합니다.'));
        }
        if ($this->hasError()) {
            $this->createBodyByError();
            $this->printExcel();

            return false;
        }
        if (!$this->read()) {
            $this->createBodyByReadError();
            $this->printExcel();

            return false;
        }
        $this->excelReader->setReadDataOnly(true);
        $this->sheet = $this->excelReader->setReadEmptyCells(false)->load($files['excel']['tmp_name'])->getActiveSheet();
        $arrSheet = $this->sheet->toArray();
        unset($arrSheet[0]);
        // 1번째 줄은 설명, 2번째 줄부터 데이타 입니다.
        if ($arrSheet[1] == null) {
            throw new Exception(__('엑셀 화일을 확인해 주세요. 엑셀 데이타가 존재하지 않습니다. 데이타는 2번째 줄부터 작성을 하셔야 합니다.'));
        }
        // 데이터 송장번호 유무 체크
        $setData = $arrField = $arrBind = $arrWhere = [];
        foreach($arrSheet as $sheetKey => $sheetVal){
            $arrData = array_filter($sheetVal);
            if (!empty($arrData)) {
                $limitData = $sheetKey;
                $arrExcelData[] = $arrData;
            }
        }
        // 1,000건 넘길 시 예외 처리
        if ($limitData > 1001) {
            throw new Exception(__('송장 입력데이터는 1,000건 이상 처리할 수 없습니다.'));
        }

        foreach($arrExcelData as $excelSheetKey => $excelSheetVal) {
            $sno = $excelSheetVal[1];
            $orderNo = sprintf('%.0f', trim($excelSheetVal[2]));
            $tmpField[] = DBTableField::setTableField('tableOrderGoods', $this->_arrIncludeOg, null, 'og');
            // 쿼리용 필드 합침
            $tmpKey = array_keys($tmpField);
            foreach ($tmpKey as $key) {
                $arrField = array_merge($arrField, $tmpField[$key]);
            }
            unset($tmpField, $tmpKey);
            // 상품주문번호
            if (!empty($sno) || $sno != 0) {
                $arrWhere[] = 'og.sno=?';
                $this->db->bind_param_push($arrBind, 'i', $sno);
            }
            // 주문번호
            if (!empty($orderNo) || $orderNo != 0) {
                $arrWhere[] = 'og.orderNo=?';
                $this->db->bind_param_push($arrBind, 's', $orderNo);
            }
            // 쿼리 구성
            $this->db->strField = implode(',', $arrField) . ',og.sno,og.regDt';
            $this->db->strWhere = implode(' AND ', $arrWhere);
            $this->db->strLimit = '1';
            // 쿼리 생성
            $query = $this->db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GOODS . ' AS og ' . implode(' ', $query);
            $getData = $this->db->query_fetch($strSQL, $arrBind, false);
            if ($getData['deliveryDt'] == '0000-00-00 00:00:00' || in_array(substr($getData['orderStatus'], 0, 1), ['o', 'p', 'g'])) {
                $getData['deliveryDt'] = '-';
            }
            if ($getData['deliveryCompleteDt'] == '0000-00-00 00:00:00' || in_array(substr($getData['orderStatus'], 0, 1), ['o', 'p', 'g'])) {
                $getData['deliveryCompleteDt'] = '-';
            }
            if (!empty($getData['invoiceNo'])) {
                $setData[] = $getData;
            }
            unset($getData, $arrWhere, $arrField, $arrBind);
        }

        return $setData;
    }

    /**
     * 송장번호입력양식 업로드
     *
     *
     * @param array $files $_FILES
     *
     * @return array
     * @throws Exception
     */
    public function updateOrderInvoiceExcel(&$files)
    {
        set_time_limit(RUN_TIME_LIMIT);

        if ($files['excel']['error'] > 0) {
            throw new Exception(__('엑셀 화일이 존재하지 않습니다. 다운 로드 받으신 엑셀파일을 이용해서 "Excel 97-2003 통합문서" 로 저장이 된 엑셀 파일만 가능합니다.'));
        }
        if (!$this->read()) {
            $this->createBodyByReadError();
            $this->printExcel();

            return false;
        }
        $this->excelReader->setReadDataOnly(true);
        $this->sheet = $this->excelReader->setReadEmptyCells(false)->load($files['excel']['tmp_name'])->getActiveSheet();
        $arrSheet = $this->sheet->toArray();
        unset($arrSheet[0]);
        foreach($arrSheet as $sheetKey => $sheetVal){
            $arrData = array_filter($sheetVal);
            if (!empty($arrData)) {
                $arrExcelData[] = $arrData;
            }
        }
        if (file_exists(USERPATH . 'config/orderNew.php')) {
            $sFiledata = \FileHandler::read(App::getUserBasePath() . '/config/orderNew.php');
            $orderNew = json_decode($sFiledata, true);

            if ($orderNew['flag'] == 'T') {
                $order = App::load('\\Component\\Order\\OrderNew');
                $orderAdmin = App::load('\\Component\\Order\\OrderAdminNew');
            } else {
                $order = App::load('\\Component\\Order\\Order');
                $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');
            }
        } else {
            $upgradeFlag = gd_policy('order.upgrade');
            \FileHandler::write(App::getUserBasePath() . '/config/orderNew.php', json_encode($upgradeFlag));
            if ($upgradeFlag['flag'] == 'T') {
                $order = App::load('\\Component\\Order\\OrderNew');
                $orderAdmin = App::load('\\Component\\Order\\OrderAdminNew');
            } else {
                $order = App::load('\\Component\\Order\\Order');
                $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');
            }
        }
        // 송장일괄등록 그룹코드 설정
        $groupCd = $orderAdmin->getMaxInvoiceGroupCd();
        // 엑셀 저장(저장 실패할경우 무시)
        try {
            if(is_uploaded_file($files['excel']['tmp_name'])) {
                $ext = pathinfo($files['excel']['name'], PATHINFO_EXTENSION);
                $saveFileNm = sprintf('invoice_%s',$groupCd).$ext;
                $savePath =  \Framework\StaticProxy\Proxy\UserFilePath::data('excel','invoice')->getRealPath();
                $saveFullPath = $savePath.DS.$saveFileNm;
                if(!is_dir($savePath)){
                    mkdir($savePath,0707);
                }
                if(file_exists($saveFullPath)){
                    $saveFileNm = sprintf('invoice_%s',$groupCd).'_'.date('Hms').$ext;
                    $saveFullPath = $savePath.DS.$saveFileNm;
                }
                \FileHandler::copy($files['excel']['tmp_name'], $saveFullPath, 0707);
            }
        } catch(\Throwable $e){
        }

        // 사용 중인 배송업체
        $delivery = App::load(\Component\Delivery\Delivery::class);
        $invoiceCompanyDmethod = [];
        $invoiceCompanyNaverPay = [];
        $invoiceCompanys = $delivery->getDeliveryCompany(null, true);
        foreach ($invoiceCompanys as $companySno) {
            $invoiceCompanySnos[] = $companySno['sno'];
            $invoiceCompanyDmethod[$companySno['sno']] = [
                'companyKey' => $companySno['companyKey'],
                'deliveryFl' => $companySno['deliveryFl'],
            ];
            $invoiceCompanyNaverPay[$companySno['sno']] = $companySno['naverPayCode'];
        }

        // 데이타 실행 건수 초기화
        $successCnt = $failCnt = $functionAuthCnt = 0; // 운영자 주문상태 기능 권한에 따른 처리상태 추가

        // 엑셀 업로드 시 상태변경에 따른 메일/sms 발송을 막기 위한 상태 변경
        $this->skipSendOrderInfo = true;
        $sendDeliveryOrderInfo = $sendDeliveryCompleteOrderInfo = [];

        $logger = App::getInstance('logger');
        $loggerTitle = '엑셀송장업로드('.$groupCd.') ';
        $logger->info($loggerTitle.'Start', ['전체:'.(count($arrExcelData))]);
        // 엑셀 데이터를 추출해서 데이터 설정 (상품주문번호가 없는 경우 제외)
        try{
            $naverPayApi = new NaverPayAPI();
            foreach($arrExcelData as $excelSheetKey => $excelSheetVal){
                //xlsx 확장자의 날짜 형식 값 변환
                $deliveryDt = $excelSheetVal[5] ? date('Y-m-d',round((($excelSheetVal[5] - 25569) * 86400-60*60*9)*10)/10) : '';
                $deliveryCompleteDt = $excelSheetVal[6] ? date('Y-m-d',round((($excelSheetVal[6] - 25569) * 86400-60*60*9)*10)/10) : '';
                $getData = [
                    'sno' => trim($excelSheetVal[1]),
                    'orderNo' => trim($excelSheetVal[2]),
                    'deliveryDt' => $deliveryDt ? $deliveryDt : '', // 배송중일자
                    'deliveryCompleteDt' => $deliveryCompleteDt ? $deliveryCompleteDt : '', // 배송완료일자
                    'invoiceCompanySno' => trim($excelSheetVal[3]),
                    'invoiceNo' => StringUtils::xssClean(trim($excelSheetVal[4])),
                ];
                // 쿼리를 실행할지 말지
                $isRun = true;

                // 성공인지 실패인지
                $result = false;

                if (!$getData['orderNo']) {
                    $failMessage[] = $failReason = __('주문번호가 존재하지 않습니다.');
                    $isRun = false;
                }

                // 배송업체 체크 후 없으면 실패 처리
                if (!in_array($getData['invoiceCompanySno'], $invoiceCompanySnos)) {
                    $failMessage[] = $failReason = __('배송업체가 존재하지 않습니다.');
                    $isRun = false;
                }

                // 송장번호 없는 경우
                if (empty($getData['invoiceNo']) || $getData['invoiceNo'] === 0) {
                    $failMessage[] = $failReason = __('송장번호가 없습니다.');
                    $isRun = false;
                }

                // 주문번호 확인이 안되는 경우
                if ((empty($getData['sno']) || $getData['sno'] == 0) && (empty($getData['orderNo']) || $getData['orderNo'] == 0)) {
                    $failMessage[] = $failReason = __('주문이 존재하지 않습니다.');
                    $isRun = false;
                }

                // 공급사로 업로드 하는 경우
                if (Manager::isProvider()) {
                    if (empty($getData['sno']) || $getData['sno'] == 0) {
                        $failMessage[] = $failReason = __('송장일괄등록은 상품주문번호가 반드시 필요합니다.');
                        $isRun = false;
                    }
                }

                // 배송일이 배송완료일보다 큰 경우
                if (!empty($getData['deliveryDt']) && !empty($getData['deliveryCompleteDt'])) {
                    if (strtotime($getData['deliveryDt']) > strtotime($getData['deliveryCompleteDt'])) {
                        $failMessage[] = $failReason = __('배송일이 배송완료일보다 큽니다.');
                        $isRun = false;
                    }
                }

                // 쿼리를 실행할 수 없는 경우에 대한 처리
                if ($isRun === true) {
                    // 날짜에 현재 시간대 추가
                    if (!empty($getData['deliveryDt'])) {
                        $getData['deliveryDt'] .= ' ' . date('H:i:s');
                    }
                    if (!empty($getData['deliveryCompleteDt'])) {
                        $getData['deliveryCompleteDt'] .= ' ' . date('H:i:s');
                    }

                    //배송방식 업데이트
                    $upData['deliveryMethodFl'] = 'delivery';
                    if(trim($getData['invoiceCompanySno'])){
                        //배송방식이 택배가 아닐 경우
                        if($invoiceCompanyDmethod[$getData['invoiceCompanySno']]['deliveryFl'] === 'n'){
                            $upData['deliveryMethodFl'] = $invoiceCompanyDmethod[$getData['invoiceCompanySno']]['companyKey'];
                        }
                    }

                    // 주문번호와 주문상품번호 체크
                    $upData['invoiceCompanySno'] = $getData['invoiceCompanySno'];
                    $upData['invoiceNo'] = $getData['invoiceNo'];
                    $upData['invoiceDt'] = date('Y-m-d H:i:s');
                    $upData['deliveryDt'] = $getData['deliveryDt'];
                    $upData['deliveryCompleteDt'] = $getData['deliveryCompleteDt'];
                    $arrBind = $this->db->get_binding(DBTableField::tableOrderGoods(), $upData, 'update', array_keys($upData));

                    // 상품주문번호 조건절
                    if (empty($getData['sno']) || $getData['sno'] == 0) {
                        $getData['sno'] = null;
                    } else {
                        $tmpWhere[] = 'sno=?';
                        $this->db->bind_param_push($arrBind['bind'], 'i', $getData['sno']);
                    }

                    // 주문번호 조건절
                    if (empty($getData['orderNo']) || $getData['orderNo'] == 0) {
                        $getData['orderNo'] = null;
                    } else {
                        $tmpWhere[] = 'orderNo=?';
                        $this->db->bind_param_push($arrBind['bind'], 's', $getData['orderNo']);
                    }

                    // 공급사로 업로드 하는 경우
                    if (Manager::isProvider()) {
                        $tmpWhere[] = 'scmNo=?';
                        $this->db->bind_param_push($arrBind['bind'], 's', Session::get('manager.scmNo'));
                    }

                    // 송장일괄등록시 해당 주문상태의 주문만 변경(입금, 상품, 배송, 추가입금)
                    $tmpWhere[] = 'SUBSTRING(orderStatus, 1, 1) IN (\'g\', \'d\', \'p\', \'z\')';

                    // 주문상품 데이터 업데이트 성공 여부 반환
                    if (!empty($tmpWhere)) {
                        // 현 주문정보
                        $orderGoods = $order->getOrderGoods($getData['orderNo'], $getData['sno'], null, null, null, ['orderChannelFl']);
                        $resultFail = true;
                        if(!$orderGoods) {
                            $failMessage[] = $failReason = __('존재하지 않는 주문번호 또는 상품주문번호 입니다.');
                            $resultFail = false;
                            $result = false;
                        }
                        if (Manager::isProvider()) {
                            if (Session::get('manager.scmNo') != $orderGoods[0]['scmNo']) {
                                $failMessage[] = $failReason = __('공급사 주문 상품이 아닙니다. 상품주문번호를 확인해 주세요.');
                                $resultFail = false;
                                $result = false;
                            }
                        }

                        if ($resultFail) {
                            // 현재 주문상태모드
                            $statusMode = substr($orderGoods[0]['orderStatus'], 0, 1);

                            // 입금대기 상태인 경우 실패 처리
                            if ($statusMode != 'o' && in_array($statusMode, explode(',', 'g,d,p,z'))) {
                                // 주문상품 데이터 업데이트
                                $changeCode = false;
                                $result = $this->db->set_update_db(DB_ORDER_GOODS, $arrBind['param'], implode(' AND ', $tmpWhere), $arrBind['bind']);
                                if($result>0) {
                                    $result = true;
                                }

                                //교환추가 된 경우
                                if($statusMode === 'z'){
                                    // 배송일자 기록에 따른 주문상태 변경
                                    if (!empty($getData['deliveryDt']) && !empty($getData['invoiceNo'])) {   //배송일자가 있으면 추가배송중
                                        $changeCode = 3;
                                    }

                                    if (!empty($getData['deliveryCompleteDt'])) {   //완료일자가 있으면 추가배송완료
                                        $changeCode = 4;
                                    }
                                }
                                else {
                                    // 배송일자 기록에 따른 주문상태 변경
                                    if (!empty($getData['deliveryDt']) && !empty($getData['invoiceNo'])) {   //배송일자가 있으면 배송중
                                        $changeCode = 1;
                                    }

                                    if (!empty($getData['deliveryCompleteDt'])) {   //완료일자가 있으면 배송완료
                                        $changeCode = 2;
                                    }
                                }

                                if (!in_array($statusMode, explode(',', 'g,d,p,z'))) {
                                    $changeCode = 9;    // 입금, 배송, 상품 그룹 상태가 아닌 경우 송장번호 입력 불가, 배송상태 변경 불가
                                }

                                // 주문상태 변경 처리
                                if ($changeCode !== false) {
                                    // 운영자 기능권한 처리 (주문 상태 권한) - 관리자페이지에서만
                                    $thisCallController = App::getInstance('ControllerNameResolver')->getControllerRootDirectory();
                                    if ($thisCallController == 'admin' && Session::get('manager.functionAuthState') == 'check' && Session::get('manager.functionAuth.orderState') != 'y') {
                                        $failMessage[] = $failReason = __('주문상태 변경 권한이 없는 운영자');
                                        $result = 'functionAuth';
                                    } else {
                                        //네이버페이 주문이면
                                        if ($orderGoods[0]['orderChannelFl'] == 'naverpay') {
                                            if(in_array($statusMode,['p','d','g']) === false){
                                                $failMessage[] = $failReason = __('변경 불가한 주문상태입니다.('.$statusMode.')');
                                                $result = false;
                                            }
                                            else {
                                                $delivery = new Delivery();
                                                $deliveryCompany = $delivery->getDeliveryCompany($upData['invoiceCompanySno'], 'naverpay');
                                                $_dispatchDate = date_create($getData['deliveryDt']);
                                                $dispatchDate = date_format($_dispatchDate, date('Ymd H:i:s'));

                                                if ($changeCode == 1) { //배송중
                                                    try {
                                                        foreach($orderGoods as $orderGoodsData){
                                                            $apiOrderGoodsNo = $orderGoodsData['apiOrderGoodsNo'];
                                                            $naverPayCompanyCode = $invoiceCompanyNaverPay[$upData['invoiceCompanySno']];

                                                            if($orderGoodsData['orderStatus'] == 'd1' && $changeCode == 1){
                                                                $failMessage[] = $failReason = __('이미 배송중상태 입니다.('.$orderGoodsData['orderStatus'].'->d'.$changeCode.')');
                                                                $result = false;
                                                                continue;
                                                            }

                                                            $params = [
                                                                'ProductOrderID' => $apiOrderGoodsNo,
                                                                'DeliveryMethodCode' => 'DELIVERY',
                                                                'DeliveryCompanyCode' => $naverPayCompanyCode,
                                                                'TrackingNumber' => $upData['invoiceNo'],
                                                                'DispatchDate' => $dispatchDate,
                                                            ];

                                                            $apiResult = $naverPayApi->request('ShipProductOrder', $params);
                                                            if ($apiResult !== false) {
                                                                $result = true;
                                                            } else {
                                                                $failMessage[] = $failReason = $naverPayApi->getError();
                                                                $result = false;
                                                            }
                                                        }
                                                    } catch (Exception $e) {
                                                        $failMessage[] = $failReason = $naverPayApi->getError();
                                                        $result = false;
                                                    }
                                                } else if ($changeCode == 2) {  //배송완료
                                                    $failMessage[] = $failReason = __('네이버페이 주문은 배송완료로 처리 하실 수 없습니다.');
                                                    $result = false;
                                                } else {
                                                    $failMessage[] = $failReason = sprintf('%s(%s : d' . $changeCode . ')', __('변경 할 수없는 주문상태'), __('배송코드'));
                                                    $result = false;
                                                }
                                            }
                                        } else {
                                            if ($changeCode == 9) {
                                                $failMessage[] = $failReason = __('송장등록 불가 상태');
                                                $result = false;
                                            } else {
                                                //교환추가 된 경우
                                                if($statusMode === 'z'){
                                                    $completeCode = 'z' . $changeCode;
                                                }
                                                else {
                                                    $completeCode = 'd' . $changeCode;
                                                }
                                                foreach ($orderGoods as $key => $orderGoodsData) {
                                                    if (in_array(substr($orderGoodsData['orderStatus'], 0, 1), ['g', 'd', 'p', 'z']) === false) {
                                                        unset($orderGoods[$key]);
                                                    }
                                                }
                                                $orderAdmin->updateStatusUnconditionalPreprocess($getData['orderNo'], $orderGoods, $statusMode, $completeCode, __('송장일괄등록에서'), true);
                                            }
                                        }
                                    }

                                    // 주문번호만 있는 경우 상품주문번호를 추출해 담아준다.
                                    $getData['sno'] = $orderGoods[0]['sno'];
                                }
                            } else {
                                if (!in_array($statusMode, explode(',', 'g,d,p'))) {
                                    $failMessage[] = $failReason = __('송장등록 불가 상태');
                                } else {
                                    $failMessage[] = $failReason = __('입금대기시 송장등록 불가');
                                }
                                $result = false;
                            }
                        }
                    }
                    unset($arrBind, $tmpWhere);
                }

                // 주문상품 변경 성공시 주문송장일괄처리 데이터 업데이트
                $invoiceData['completeFl'] = ($result === 'functionAuth' ? 'f' : ($result !== false ? 'y' : 'n')); // 운영자 주문상태 기능 권한에 따른 처리상태 추가
                $invoiceData['orderNo'] = $getData['orderNo'];
                $invoiceData['orderGoodsNo'] = $getData['sno'];
                $invoiceData['scmNo'] = Session::get('manager.scmNo');
                $invoiceData['managerNo'] = Session::get('manager.sno');
                $invoiceData['groupCd'] = $groupCd;
                $invoiceData['invoiceCompanySno'] = $getData['invoiceCompanySno'];
                $invoiceData['invoiceNo'] = $getData['invoiceNo'];
                $invoiceData['deliveryDt'] = $getData['deliveryDt'];
                $invoiceData['deliveryCompleteDt'] = $getData['deliveryCompleteDt'];
                $invoiceData['failReason'] = $failReason;
                $logger->info($loggerTitle.'data', [$invoiceData]);
                $arrBind = $this->db->get_binding(DBTableField::tableOrderInvoice(), $invoiceData, 'insert', array_keys($invoiceData));
                \Logger::info(__METHOD__ . ' RESULRT: ', [$invoiceData]);
                $this->db->set_insert_db(DB_ORDER_INVOICE, $arrBind['param'], $arrBind['bind'], 'y');
                unset($arrBind, $failReason);

                // 성공/실패/운영자권한 건수 계산
                if ($result === 'functionAuth') {
                    $functionAuthCnt++;
                } else if ($result === true) {
                    if (!empty($getData['deliveryDt']) && !empty($getData['invoiceNo'])) {   //배송중으로 변경되는 상태 조건인 경우
                        if($orderGoods[0]['orderChannelFl'] !== 'etc'){
                            $sendDeliveryOrderInfo[$getData['orderNo']] = $getData['orderNo'];
                        }
                    } elseif (!empty($getData['deliveryCompleteDt'])) {     //완료일자가 있으면 배송완료
                        if($orderGoods[0]['orderChannelFl'] !== 'etc'){
                            $sendDeliveryCompleteOrderInfo[$getData['orderNo']] = $getData['orderNo'];
                        }
                    }

                    $successCnt++;
                } else {
                    $failCnt++;
                }
            }

            // 초기 값으로 재설정
            $this->skipSendOrderInfo = false;
            foreach ($sendDeliveryOrderInfo as $orderNo) {
                $order->sendOrderInfo(MailMimeAuto::GOODS_DELIVERY, 'all', $orderNo);
                $order->sendOrderInfo(Code::INVOICE_CODE, 'sms', $orderNo);
            }
            foreach ($sendDeliveryCompleteOrderInfo as $orderNo) {
                $order->sendOrderInfo(Code::DELIVERY_COMPLETED, 'sms', $orderNo);
            }
            $logger->info($loggerTitle.'End', ['전체:'.count($arrExcelData).'/성공수:'.$successCnt.'/실패수:'.$failCnt]);

        } catch(\Exception $e){
            $logger->info($loggerTitle.'Exception', ['상품주문번호 : '.$orderGoods[0]['sno'],$e->getMessage()]);
        }

        return [
            'total' => $successCnt + $failCnt + $functionAuthCnt,
            'success' => $successCnt,
            'fail' => $failCnt,
            'functionAuth' => $functionAuthCnt,
            'message' => $failMessage,
        ];
    }

}
