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
 * @link http://www.godo.co.kr
 */
namespace Component\Order;

use App;
use Component\Bankda\BankdaOrder;
use Component\Database\DBTableField;
use Component\Delivery\Delivery;
use Component\Deposit\Deposit;
use Component\Godo\MyGodoSmsServerApi;
use Component\Godo\NaverPayAPI;
use Component\Mail\MailMimeAuto;
use Component\Mall\Mall;
use Component\Member\Manager;
use Component\Mileage\Mileage;
use Component\Naver\NaverPay;
use Component\Sms\Code;
use Component\Validator\Validator;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\Except;
use Framework\Debug\Exception\LayerNotReloadException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\StaticProxy\Proxy\UserFilePath;
use Framework\Utility\ArrayUtils;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\NumberUtils;
use Framework\Utility\StringUtils;
use Globals;
use LogHandler;
use Request;
use Session;
use Vendor\Spreadsheet\Excel\Reader as SpreadsheetExcelReader;
use Component\Page\Page;


/**
 * 주문 class
 * 주문 관련 관리자 Class
 *
 * @package Bundle\Component\Order
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class OrderAdmin extends \Bundle\Component\Order\OrderAdmin
{
	 /* 검색 배송 구분 조건 추가 */
	protected function _setSearch($searchData, $searchPeriod = 7, $isUserHandle = false)
	{
        $result = parent::_setSearch($searchData, $searchPeriod, $isUserHandle);
		
		if($searchData['firstDelivery']){
			$this->search['firstDelivery'][0] = $searchData['firstDelivery'][0];
			$this->search['firstDelivery'][1] = $searchData['firstDelivery'][1];
		}
		// 웹앤모바일 2023-08-22 첫배송일 주문건 리스트 확인
		if ($searchData['firstDelivery'][0] && $searchData['firstDelivery'][1]) {
			$searchData['firstDelivery'][0] = str_replace('-' , '' , $searchData['firstDelivery'][0]);
			$searchData['firstDelivery'][0] = (int)$searchData['firstDelivery'][0];
			$searchData['firstDelivery'][1] = str_replace('-' , '' , $searchData['firstDelivery'][1]);
			$searchData['firstDelivery'][1] = (int)$searchData['firstDelivery'][1];
			
			 $this->arrWhere[] = "og.firstDelivery >= ? AND og.firstDelivery <= ?";
			 $this->db->bind_param_push($this->arrBind, 'i', $searchData['firstDelivery'][0]);
			 $this->db->bind_param_push($this->arrBind, 'i', $searchData['firstDelivery'][1]);
		}
		
    return $result;
	}
	
	/* 주문서에 배송구분 표기 */
	public function getOrderListForAdmin($searchData, $searchPeriod, $isUserHandle = false)
	{	
		$tmp = parent::getOrderListForAdmin($searchData, $searchPeriod, $isUserHandle);
		// 웹앤모바일 2023-08-08 첫배송일 주문건 확인

		foreach($tmp['data'] as $key => $value){
			foreach($value['goods'] as $key_1 => $value_1){
				foreach($value_1 as $key_2 => $value_2){
					foreach($value_2 as $key_3 => $value_3){
						$this->db->strField = "firstDelivery";
						$this->db->strWhere = "orderNo = '{$value_3['orderNo']}' AND sno = '{$value_3['sno']}'";
						$query = $this->db->query_complete();
						$sql = "SELECT".array_shift($query)."FROM ".DB_ORDER_GOODS.implode(' ' , $query);
						$firstDelivery = $this->db->fetch($sql);
						
						if($firstDelivery['firstDelivery'] > 0){
							$firstTime = strtotime($firstDelivery['firstDelivery']);
	
							$md = date('m-d' , $firstTime);
											
							$md = str_replace('-' , '월 ', $md);
							$md .= '일';
							/*
							$daily = array('일','월','화','수','목','금','토');
							
							$w = $daily[date('w' , strtotime($firstDelivery['firstDelivery']))];
							$tmp['data'][$key]['goods'][$key_1][$key_2][$key_3]['firstDelivery'] = $w.'요일';
							*/
							$tmp['data'][$key]['goods'][$key_1][$key_2][$key_3]['firstDelivery'] = $md;
						}
					}
				}
			}
		}
		
		return $tmp;
	}
	
	
	/**
	 * 관리자 주문 상세정보
	 *
	 * @param string $orderNo 주문 번호
	 * @param null $orderGoodsNo
	 * @param integer $handleSno 반품/교환/환불 테이블 번호
	 *
	 * @param string $statusMode
	 * @param array $excludeStatus 제외할 주문상태 값
	 * @param string $orderStatusMode 주문상세페이지 로드시 내역 종류
	 *
	 * @return array|bool 주문 상세정보
	 * @throws Exception
	 */
	public function getOrderView($orderNo, $orderGoodsNo = null, $handleSno = null, $statusMode = null, $excludeStatus = null, $orderStatusMode = null)
	{
		$tmp = parent::getOrderView($orderNo, $orderGoodsNo , $handleSno , $statusMode , $excludeStatus , $orderStatusMode);
		foreach($tmp['goods'] as $key => $value){
			foreach($value as $key_1 => $value_1){
				foreach($value_1 as $key_2 => $value_2){

					//gd_debug($value_2);
					$this->db->strField = "firstDelivery";
					$this->db->strWhere = "orderNo = '{$tmp['orderNo']}' AND sno = '{$value_2['sno']}'";
					$query = $this->db->query_complete();
					$sql = "SELECT".array_shift($query)."FROM ".DB_ORDER_GOODS.implode(' ' , $query);
					$firstDelivery = $this->db->fetch($sql);
					
					if($firstDelivery['firstDelivery'] > 0){
						$firstTime = strtotime($firstDelivery['firstDelivery']);
				
						$md = date('m-d' , $firstTime);
						$md = str_replace('-' , '월 ', $md);
						$md .= '일';
						/*
						$daily = array('일','월','화','수','목','금','토');
						
						$w = $daily[date('w' , strtotime($firstDelivery['firstDelivery']))];
						$tmp['data'][$key]['goods'][$key_1][$key_2][$key_3]['firstDelivery'] = $w.'요일';
						*/
						$tmp['goods'][$key][$key_1][$key_2]['firstDelivery'] = $md;
					}
				}
			}	
		}
		
		return $tmp;
	}

	public function fetchScheduledDeliveryList($searchData, $searchPeriod, $isUserHandle = false)
	{
		if (trim($searchData['orderAdminGridMode']) !== '') {
			//주문리스트 그리드 설정
			$orderAdminGrid = \App::load('\\Component\\Order\\OrderAdminGrid');

			$this->orderGridConfigList = $orderAdminGrid->getSelectOrderGridConfigList($searchData['orderAdminGridMode']);	
		}

		$this->_setDeliverySearch($searchData, $searchPeriod, $isUserHandle);

		
		// 주문번호별로 보기
		$isDisplayOrderGoods = ($this->search['view'] !== 'order'); // view모드가 orderGoods & orderGoodsSimple이 아닌 경우 true

		// --- 페이지 기본설정
		gd_isset($searchData['page'], 1);
		gd_isset($searchData['pageNum'], 20);
		$page = \App::load('\\Component\\Page\\Page', $searchData['page'], 0, 0, $searchData['pageNum']);
		$page->setCache(true)->setUrl(\Request::getQueryString()); // 페이지당 리스트 수

		// 주문상태 정렬 예외 케이스 처리
		if ($searchData['sort'] == 'o.orderStatus asc') {
			$searchData['sort'] = 'case LEFT(o.orderStatus, 1) when \'o\' then \'01\' when \'p\' then \'02\' when \'g\' then \'03\' when \'d\' then \'04\' when \'s\' then \'05\' when \'e\' then \'06\' when \'b\' then \'07\' when \'r\' then \'08\' when \'c\' then \'09\' when \'f\' then \'10\' else \'11\' end';
		} elseif ($searchData['sort'] == 'o.orderStatus desc') {
			$searchData['sort'] = 'case LEFT(o.orderStatus, 1) when \'f\' then \'01\' when \'c\' then \'02\' when \'r\' then \'03\' when \'b\' then \'04\' when \'e\' then \'05\' when \'s\' then \'06\' when \'d\' then \'07\' when \'g\' then \'08\' when \'p\' then \'09\' when \'o\' then \'10\' else \'11\' end';
		}

		$orderSort = "sd.estimatedDeliveryDt asc, sd.orderNo asc, sdg.sno asc";

		$arrIncludeOi = [
			'orderName',
			'receiverName',
			'orderMemo',
			'orderCellPhone',
			'packetCode',
			'smsFl',
		];

		$tmpField[] = DBTableField::setTableField('tableOrderInfo', $arrIncludeOi, null, 'oi');
		$tmpField[] = ['oi.sno AS orderInfoSno'];

		$join[] = ' JOIN  ms_scheduledDeliveryGoods sdg ON sdg.scheduledDeliverySno = sd.sno ';
		$join[] = ' JOIN ' . DB_ORDER . ' o ON o.orderNo = sd.orderNo ';
		$join[] = ' LEFT JOIN ' . DB_ORDER_DELIVERY . ' od ON sd.orderDeliverySno = od.sno ';
		$join[] = ' LEFT JOIN ' . DB_MEMBER_HACKOUT_ORDER . ' mho ON sd.orderNo = mho.orderNo ';
		$join[] = ' LEFT JOIN ' . DB_ORDER_INFO . ' oi ON (sd.orderNo = oi.orderNo)   
							AND (CASE WHEN od.orderInfoSno > 0 THEN od.orderInfoSno = oi.sno ELSE oi.orderInfoCd = 1 END)';

		if (($this->search['key'] == 'all' && empty($this->search['keyword']) === false)  || $this->search['key'] == 'sm.companyNm' || strpos($orderSort, "sm.companyNm ") !== false || $this->multiSearchScmJoinFl) {
			$join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' sm ON sd.scmNo = sm.scmNo ';
		}

		if (($this->search['key'] == 'all' && empty($this->search['keyword']) === false) || $this->search['key'] == 'm.nickNm' || $this->search['key'] == 'm.memId' || ($this->search['memFl'] == 'y' && empty($this->search['memberGroupNo']) === false) || $this->multiSearchMemberJoinFl) {
			$join[] = ' LEFT JOIN ' . DB_MEMBER . ' m ON o.memNo = m.memNo AND m.memNo > 0 ';
		}

		//상품 브랜드 코드 검색
		if (empty($this->search['brandCd']) === false || empty($this->search['brandNoneFl']) === false) {
			$join[] = ' LEFT JOIN ' . DB_GOODS . ' as g ON sdg.goodsNo = g.goodsNo ';
		}

		//택배 예약 상태에 따른 검색
		if ($this->search['invoiceReserveFl']) {
			$join[] = ' LEFT JOIN ' . DB_ORDER_GODO_POST . ' ogp ON ogp.invoiceNo = sd.invoiceNo ';
		}

		// 쿠폰검색시만 join
		if ($this->search['couponNo'] > 0) {
			$join[] = ' LEFT JOIN ' . DB_ORDER_COUPON . ' oc ON o.orderNo = oc.orderNo ';
			$join[] = ' LEFT JOIN ' . DB_MEMBER_COUPON . ' mc ON mc.memberCouponNo = oc.memberCouponNo ';
		}

		// 상품º주문번호별 메모 검색시
		if ($this->search['withAdminMemoFl'] == 'y') {
			$join[] = ' LEFT JOIN ' . DB_ADMIN_ORDER_GOODS_MEMO . ' aogm ON o.orderNo = aogm.orderNo ';
		}

		// 쿼리용 필드 합침
		$tmpKey = array_keys($tmpField);
		$arrField = [];
		foreach ($tmpKey as $key) {
			$arrField = array_merge($arrField, $tmpField[$key]);
		}
		unset($tmpField, $tmpKey);

		// 현 페이지 결과
		$this->db->strField = '
			sdg.sno,
			sdg.goodsNo,
			sdg.goodsCd,
			sdg.orderGoodsSno,
			sdg.goodsCnt, 
		 	sd.sno AS scheduledDeliverySno,
		 	sd.orderNo,
			sd.orderDeliverySno,
			sd.round,
			sd.totalRound,
			sd.deliveryDt,
			sd.estimatedDeliveryDt,
			sd.invoiceNo,
			sd.invoiceCompanySno,
			sd.deliveryStatus,
		 	CONCAT(sd.orderNo, \'-\', sd.orderGoodsSno, \'-\', sd.round) deliveryOrderNo,
			LEFT(sd.deliveryStatus, 1) as deliveryStatusMode,
			o.mallSno,
			o.memNo,
			o.trackingKey,
			o.orderTypeFl,
			o.appOs,
			o.pushCode,'
			. implode(', ', $arrField);

		
		$this->db->strJoin = implode('', $join);
		$this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
		$this->db->strOrder = $orderSort;

		if (!$isDisplayOrderGoods) {
			if ($searchData['statusMode'] === 'o') {
				// 입금대기리스트 > 주문번호별 에서 '주문상품명' 을 입금대기 상태의 주문상품명만으로 노출시키기 위해 개수를 구함
				$this->db->strField .= ', SUM(IF(LEFT(o.orderStatus, 1)=\'o\', 1, 0)) AS noPay';
			}
			$this->db->strField .= ', o.regDt, SUM(IF(LEFT(o.orderStatus, 1)=\'o\' OR LEFT(o.orderStatus, 1)=\'p\' OR LEFT(o.orderStatus,1)=\'g\', 1, 0)) AS noDelivery';
			$this->db->strField .= ', SUM(IF(LEFT(o.orderStatus, 1)=\'d\' AND o.orderStatus != \'d2\', 1, 0)) AS deliverying';
			$this->db->strField .= ', SUM(IF(o.orderStatus=\'d2\' OR LEFT(o.orderStatus, 1)=\'s\', 1, 0)) AS deliveryed';
			$this->db->strField .= ', SUM(IF(LEFT(o.orderStatus, 1)=\'c\', 1, 0)) AS cancel';
			$this->db->strField .= ', SUM(IF(LEFT(o.orderStatus, 1)=\'e\', 1, 0)) AS exchange';
			$this->db->strField .= ', SUM(IF(LEFT(o.orderStatus, 1)=\'b\', 1, 0)) AS back';
			$this->db->strField .= ', SUM(IF(LEFT(o.orderStatus, 1)=\'r\', 1, 0)) AS refund';

			$this->db->strGroup = 'sd.orderNo';
		} else if ($this->search['withAdminMemoFl'] == 'y') {
			// 상품º주문번호별 메모 검색시
			$this->db->strGroup = 'sd.sno';
		}

		gd_isset($searchData['useStrLimit'], true);
		if ($searchData['useStrLimit']) {
			$this->db->strLimit = $page->recode['start'] . ',' . $searchData['pageNum'];
		}

		$query = $this->db->query_complete();
		$strSQL = 'SELECT ' . array_shift($query) . ' FROM ms_scheduledDelivery sd ' . implode(' ', $query);

		$getData = $this->db->query_fetch($strSQL, $this->arrBind);

		// 검색 레코드 수
		$query['group'] = 'GROUP BY sd.orderNo';
		unset($query['order']);

		if ($page->hasRecodeCache('total') === false) {
			
			$sqlCount = 'SELECT (o.realTaxSupplyPrice + o.realTaxFreePrice + o.realTaxVatPrice) AS price, COUNT(distinct(sdg.sno)) AS cnt FROM ms_scheduledDelivery sd ' . implode(' ', str_ireplace('limit ' . $page->recode['start'] . ',' . $searchData['pageNum'], '', $query));
			$total = $this->db->query_fetch($sqlCount, $this->arrBind, true);

			$page->recode['totalPrice'] = array_sum(array_column($total, 'price'));
		}

		if ($isDisplayOrderGoods) {
			$sdgSno = 'sdg.sno';
			$groupBy = '';
			$page->recode['total'] = array_sum(array_column($total, 'cnt'));
			$this->search['deliveryFl'] = true;
		} else {
			$sdgSno = 'sd.sno';
			$groupBy = ' GROUP BY sd.sno';
			$page->recode['total'] = count($total);
		}

		// 주문상태에 따른 전체 갯수
		if ($page->hasRecodeCache('amount') === false) {
			if (Manager::isProvider()) {
				if ($this->search['statusMode'] !== null) {
					$query = 'SELECT COUNT(' . $sdgSno . ') as total FROM ms_scheduledDelivery sd JOIN ms_scheduledDeliveryGoods sdg on sd.sno = sdg.scheduledDeliverySno WHERE sd.scmNo=' . Session::get('manager.scmNo') . ' AND (sd.deliveryStatus LIKE concat(\'' . $this->search['statusMode'] . '\',\'%\'))' . $groupBy;
				} else {
					$query = 'SELECT COUNT(' . $sdgSno . ') as total FROM ms_scheduledDelivery sd JOIN ms_scheduledDeliveryGoods sdg on sd.sno = sdg.scheduledDeliverySno WHERE sd.scmNo=' . Session::get('manager.scmNo') . ' AND LEFT(sd.deliveryStatus, 1) NOT IN (\'o\', \'c\') AND sd.deliveryStatus != \'' . $this->arrBind[1] . '\'' . $groupBy;
				}
			} else {
				if ($this->search['statusMode'] !== null && $this->search['statusMode'] !== '') {
					$statusModes = explode(',', $this->search['statusMode']);
					$statusConditions = array_map(function($status) {
						return "sd.deliveryStatus LIKE '{$status}%'";
					}, $statusModes);
					$query = 'SELECT COUNT(' . $sdgSno . ') as total FROM ms_scheduledDelivery sd JOIN ms_scheduledDeliveryGoods sdg on sd.sno = sdg.scheduledDeliverySno WHERE (' . implode(' OR ', $statusConditions) . ')' . $groupBy;
				}  else {
					$query = 'SELECT COUNT(' . $sdgSno . ') as total FROM ms_scheduledDelivery sd JOIN ms_scheduledDeliveryGoods sdg on sd.sno = sdg.scheduledDeliverySno WHERE (sd.deliveryStatus != \'' . $this->arrBind[1] . '\') ' . $groupBy;
				}
			}

			if (!$isDisplayOrderGoods) {
				$query = "SELECT COUNT(*) as total FROM ({$query}) as t";
			}

			$total = $this->db->query_fetch($query, null, false)['total'];
			$page->recode['amount'] = $total;
		}

		$page->setPage(null, ['totalPrice']);

		return $this->setScheduledDeliveryListForAdmin($getData, $isUserHandle, $isDisplayOrderGoods, true, $searchData['statusMode']);		
	}

	public function setScheduledDeliveryListForAdmin($getData, $isUserHandle = false, $isDisplayOrderGoods = false, $setInfoFl = false, $searchStatusMode = '')
	{

		$delivery = new Delivery();
		$delivery->setDeliveryMethodCompanySno();
		$orderBasic = gd_policy('order.basic');
		if (($orderBasic['userHandleAdmFl'] == 'y' && $orderBasic['userHandleScmFl'] == 'y') === false) {
			unset($orderBasic['userHandleScmFl']);
		}

		//정보가 없을경우 다시 가져올수 있도록 수정
		if (empty($getData[0]['orderGoodsNm']) === true) $setInfoFl = true;

		if ($setInfoFl) {
			// 사용 필드
			$arrIncludeOg = [
				'sno',
				'apiOrderGoodsNo',
				'commission',
				'goodsType',
				'orderCd',
				'userHandleSno',
				'handleSno',
				'orderStatus',
				'goodsNm',
				'goodsNmStandard',
				// 'goodsCnt', 
				// 'goodsPrice',
				'optionPrice',
				'optionTextPrice',
				'addGoodsPrice',
				'divisionUseDeposit',
				'divisionUseMileage',
				'divisionCouponOrderDcPrice',
				'goodsDcPrice',
				'memberDcPrice',
				'memberOverlapDcPrice',
				'couponGoodsDcPrice',
				'goodsDeliveryCollectPrice',
				'goodsDeliveryCollectFl',
				'optionInfo',
				'optionTextInfo',
				// 'invoiceCompanySno',
				// 'invoiceNo',
				'addGoodsCnt',
				'paymentDt',
				'cancelDt',
				'timeSaleFl',
				'checkoutData',
				'og.regDt',
				'LEFT(og.orderStatus, 1) as statusMode',
				'deliveryMethodFl',
				'deliveryScheduleFl',
				// 'goodsCd',
				'taxVatGoodsPrice',
				'hscode',
				'brandCd',
				'goodsModelNo',
				'costPrice',
				'cancelDt',
				'goodsTaxInfo',
				'makerNm',
				'deliveryDt',
				'deliveryCompleteDt',
				'finishDt',
			];

			$arrIncludeO = [
				'orderNo',
				'apiOrderNo',
				'mallSno',
				'orderGoodsNm',
				'orderGoodsNmStandard',
				'orderGoodsCnt',
				'settlePrice',
				'totalGoodsPrice',
				'settleKind',
				'receiptFl',
				'bankSender',
				'bankAccount',
				'escrowDeliveryFl',
				'orderTypeFl',
				'appOs',
				'pushCode',
				'orderChannelFl',
				'firstSaleFl',
				//'adminMemo',
				'o.memNo AS memNoCheck',
				'LEFT(o.orderStatus, 1) as totalStatus',
				'totalDeliveryCharge',
				'useMileage',
				'useDeposit',
				'totalGoodsDcPrice',
				'totalMemberDcPrice',
				'totalMemberOverlapDcPrice',
				'totalCouponGoodsDcPrice',
				'totalCouponOrderDcPrice',
				'totalMemberDeliveryDcPrice',
				'totalCouponDeliveryDcPrice',
				'totalEnuriDcPrice',
				'currencyPolicy',
				'exchangeRatePolicy',
				'useMileage',
				'useDeposit',
				'multiShippingFl',
				'realTaxSupplyPrice',
				'realTaxVatPrice',
				'realTaxFreePrice',
				'checkoutData',
			];

			// 마이앱 사용에 따른 분기 처리
			if ($this->useMyapp) {
				array_push($arrIncludeO, 'totalMyappDcPrice');
			}

			//주문상품정보
			$strField = implode(",", $arrIncludeOg);
			$strSQL = 'SELECT ' . $strField . ' FROM ' . DB_ORDER_GOODS . ' og  WHERE sno IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'orderGoodsSno'))) : '""') . ')' . gd_isset($strGroup, "");
			$tmpOrderGoodsData = $this->db->query_fetch($strSQL, null);
			$orderGoodsData = array_combine(array_column($tmpOrderGoodsData, 'sno'), $tmpOrderGoodsData);
			
			//주문정보
			$strSQL = 'SELECT ' . implode(",", $arrIncludeO) . ' FROM ' . DB_ORDER . ' o  WHERE o.orderNo IN ("' . implode('","', array_unique(array_column($getData, 'orderNo'))) . '")';
			$tmpOrderData = $this->db->query_fetch($strSQL, null);
			$orderData = array_combine(array_column($tmpOrderData, 'orderNo'), $tmpOrderData);

			//상품정보
			$strField = "g.goodsNo,g.imagePath,g.imageStorage,g.stockFl, gi.imageName";
			$strSQL = 'SELECT ' . $strField . ' FROM ' . DB_GOODS . ' g LEFT JOIN ' . DB_GOODS_IMAGE . ' gi ON gi.goodsNo = g.goodsNo AND gi.imageKind = \'list\' WHERE g.goodsNo IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'goodsNo'))) : '""') . ')';
			$tmpGoodsData = $this->db->query_fetch($strSQL, null);
			$goodsData = array_combine(array_column($tmpGoodsData, 'goodsNo'), $tmpGoodsData);

			//추가상품 정보
			$strField = "addGoodsNo,ag.imagePath AS addImagePath, ag.imageStorage AS addImageStorage, ag.imageNm AS addImageName";
			$strSQL = 'SELECT ' . $strField . ' FROM ' . DB_ADD_GOODS . ' ag  WHERE addGoodsNo IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'goodsNo'))) : '""') . ')';
			$tmpAddGoodsData = $this->db->query_fetch($strSQL, null);
			$addGoodsData = array_combine(array_column($tmpAddGoodsData, 'addGoodsNo'), $tmpAddGoodsData);

			//공급사 정보
			$strScmSQL = 'SELECT scmNo,companyNm FROM ' . DB_SCM_MANAGE . ' g  WHERE scmNo IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'scmNo'))) : '""') . ')';
			$tmpScmData = $this->db->query_fetch($strScmSQL);
			$scmData = array_combine(array_column($tmpScmData, 'scmNo'), array_column($tmpScmData, 'companyNm'));

			//몰정보
			$strMallSQL = 'SELECT domainFl,mallName,sno FROM ' . DB_MALL . ' mm  WHERE sno IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'mallSno'))) : '""') . ')';
			$tmpMallData = $this->db->query_fetch($strMallSQL);
			$mallData = array_combine(array_column($tmpMallData, 'sno'), $tmpMallData);

			//매입처 정보
			if (gd_is_provider() === false && gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true) {
				$strPurchaseSQL = 'SELECT purchaseNo,purchaseNm FROM ' . DB_PURCHASE . ' g  WHERE delFl = "n" AND purchaseNo IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'purchaseNo'))) : '""') . ')';
				$tmpPurchaseData = $this->db->query_fetch($strPurchaseSQL);
				$purchaseData = array_combine(array_column($tmpPurchaseData, 'purchaseNo'), array_column($tmpPurchaseData, 'purchaseNm'));
			}

			//회원정보
			$strField = "memId,nickNm,groupSno,cellPhone,memNo as memNoUnique,cellPhone,groupNm";
			$strSQL = 'SELECT ' . $strField . ' FROM ' . DB_MEMBER . ' m LEFT JOIN ' . DB_MEMBER_GROUP . ' mg ON  m.groupSno = mg.sno  WHERE m.memNo > 0 AND m.memNo IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'memNo'))) : '""') . ')';
			$tmpMemberData = $this->db->query_fetch($strSQL, null);
			$memberData = array_combine(array_column($tmpMemberData, 'memNoUnique'), $tmpMemberData);

			//배송정보
			$strField = "sno,deliverySno,deliveryCharge,deliveryPolicyCharge,deliveryAreaCharge,deliveryMethod,divisionDeliveryUseMileage,divisionDeliveryUseDeposit,scmNo,orderInfoSno,realTaxSupplyDeliveryCharge,realTaxVatDeliveryCharge,realTaxFreeDeliveryCharge";
			$strSQL = 'SELECT ' . $strField . ' FROM ' . DB_ORDER_DELIVERY . ' od WHERE od.sno IN (' . (empty($getData) === false ? implode(',', array_unique(array_column($getData, 'orderDeliverySno'))) : '""') . ')';
			$tmpDeliaveryData = $this->db->query_fetch($strSQL, null);
			$deliveryData = array_combine(array_column($tmpDeliaveryData, 'sno'), $tmpDeliaveryData);

			//주문정보 - 배송정보 - 수령자정보
			$strField = "sno, receiverName, receiverZonecode, receiverZipcode, receiverAddress, receiverAddressSub, orderInfoCd, orderNo, orderMemo";
			$infoWhere = '';
			if (Manager::isProvider()) {
				if ($isDisplayOrderGoods) {
					//상품주문번호별
					$infoWhere = ' AND sno IN ("' . implode('","', array_unique(array_column($getData, 'orderInfoSno'))) . '") ';
				} else {
					//주문번호별
					$strSQL = 'SELECT orderInfoSno, orderNo FROM ' . DB_ORDER_DELIVERY . ' WHERE scmNo = ' . Session::get('manager.scmNo') . ' AND orderNo IN ("' . implode('","', array_unique(array_column($getData, 'orderNo'))) . '")';
					$tmpAllOrderDeliveryData = $this->db->query_fetch($strSQL, null);
					$infoWhere = ' AND sno IN ("' . implode('","', array_unique(array_column($tmpAllOrderDeliveryData, 'orderInfoSno'))) . '") ';
				}
			}
			$strSQL = 'SELECT ' . $strField . ' FROM ' . DB_ORDER_INFO . ' WHERE orderNo IN ("' . implode('","', array_unique(array_column($getData, 'orderNo'))) . '") ' . $infoWhere . ' ORDER BY orderInfoCd ASC';
			$tmpOrderInfoData = $this->db->query_fetch($strSQL, null);
			$orderInfoData = array_combine(array_column($tmpOrderInfoData, 'sno'), $tmpOrderInfoData);
			$orderInfoCountData = array_count_values(array_column($orderInfoData, 'orderNo'));

			$orderMemoData = $orderReceiverNameData = [];
			if (count($orderInfoData) > 0) {
				//주문번호별 리스트에서 배송지, 수령자의 메인배송지 정보를 알려줌과 동시에 카운트를 알려주기 위해 처리
				$reverseOrderInfoData = $orderInfoData;
				rsort($reverseOrderInfoData);
				foreach ($reverseOrderInfoData as $key => $value) {
					if ($value['orderMemo']) {
						$orderMemoData[$value['orderNo']]['orderMemo'] = $value['orderMemo'];
						$orderMemoData[$value['orderNo']]['orderMemoCount'] += 1;
					}
					if ($value['receiverName']) {
						$orderReceiverNameData[$value['orderNo']]['receiverName'] = $value['receiverName'];
						$orderReceiverNameData[$value['orderNo']]['receiverNameCount'] += 1;
					}
				}
				unset($reverseOrderInfoData, $tmpOrderInfoData);
			}

			//리스트 그리드 항목에 브랜드가 있을경우 브랜드 정보 포함
			if (array_key_exists('brandNm', $this->orderGridConfigList)) {
				$brandData = [];
				$brand = \App::load('\\Component\\Category\\Brand');
				$brandOriginalData = $brand->getCategoryData(null, null, 'cateNm');
				if (count($brandOriginalData) > 0) {
					$brandData = array_combine(array_column($brandOriginalData, 'cateCd'), array_column($brandOriginalData, 'cateNm'));
				}
			}
		}

		if (gd_isset($getData)) {
			$giftList = [];
			// 주문번호에 따라 배열 처리
			if ($setInfoFl) {
				foreach ($getData as $key => &$val) {
					//주문정보
					if ($orderData[$val['orderNo']]) $val = $val + $orderData[$val['orderNo']];
					if ($orderGoodsData[$val['orderGoodsSno']]) $val = $val + $orderGoodsData[$val['orderGoodsSno']];
					if ($goodsData[$val['goodsNo']]) $val = $val + $goodsData[$val['goodsNo']];
					if ($addGoodsData[$val['goodsNo']]) $val = $val + $addGoodsData[$val['goodsNo']];
					if ($deliveryData[$val['orderDeliverySno']]) $val = $val + $deliveryData[$val['orderDeliverySno']];
					if ($orderInfoData[$val['orderInfoSno']]) $val = $val + $orderInfoData[$val['orderInfoSno']];

					if ($mallData[$val['mallSno']]) $val = $val + $mallData[$val['mallSno']];
					if ($memberData[$val['memNo']]) $val = $val + $memberData[$val['memNo']];
					$val['smsCellPhone'] = $val['memNo'] > 0 ? $val['cellPhone'] : $val['receiverCellPhonec'];
					$val['memNo'] = is_null($val['memNo']) ?  0 : $val['memNoUnique'];

					$val['companyNm'] = $scmData[$val['scmNo']];
					$val['purchaseNm'] = $purchaseData[$val['purchaseNo']];
					$val['brandNm'] = $brandData[$val['brandCd']];

					// 주문유형
					if ($val['orderTypeFl'] == 'pc') {
						$val['orderTypeFlNm'] = 'PC쇼핑몰';
					} else if ($val['orderTypeFl'] == 'mobile') {
						if (empty($val['appOs']) === true && empty($val['pushCode']) === true) {
							$val['orderTypeFlNm'] = '모바일쇼핑몰<br>(WEB)';
						} else {
							$val['orderTypeFlNm'] = '모바일쇼핑몰<br>(APP)';
						}
					} else {
						$val['orderTypeFlNm'] = '수기주문';
					}

					if (empty($val['deliveryOrderNo']) === false) {
						// json형태의 경우 json값안에 "이있는경우 stripslashes처리가 되어 json_decode에러가 나므로 json값중 "이 들어갈수있는경우 $aCheckKey에 해당 필드명을 추가해서 처리해주세요
						$aCheckKey = ['optionTextInfo'];
						foreach ($val as $k => $v) {
							if (!in_array($k, $aCheckKey)) {
								$val[$k] = gd_htmlspecialchars_stripslashes($v);
							}
						}
						if ($orderBasic['userHandleFl'] == 'y' && (!Manager::isProvider() && $orderBasic['userHandleAdmFl'] == 'y') || (Manager::isProvider() && $orderBasic['userHandleScmFl'] == 'y')) {
							if ($this->search['userHandleViewFl'] != 'y') {
								if ($isDisplayOrderGoods) {
									$val['userHandleInfo'] = $this->getUserHandleInfo($val['orderNo'], $val['sno'], [$this->search['userHandleMode']]);
								} else {
									$val['userHandleInfo'] = $this->getUserHandleInfo($val['orderNo'], null, [$this->search['userHandleMode']]);
								}
							}
						}
						// 상품º주문번호별 메모 등록여부 초기화
						$data[$val['deliveryOrderNo']]['goods'][] = $val;
						$data[$val['deliveryOrderNo']]['adminOrdGoodsMemo'] = false;

						// 탈퇴회원의 개인정보 데이터
						$withdrawnMembersOrderData = $this->getWithdrawnMembersOrderViewByOrderNo($val['orderNo']);
						$withdrawnMembersPersonalData = $withdrawnMembersOrderData['personalInfo'][0];
						$data[$val['deliveryOrderNo']]['withdrawnMembersPersonalData'] = $withdrawnMembersPersonalData;
					}
				}
			} else {
				foreach ($getData as $key => $val) {
					if (empty($val['deliveryOrderNo']) === false) {
						// json형태의 경우 json값안에 "이있는경우 stripslashes처리가 되어 json_decode에러가 나므로 json값중 "이 들어갈수있는경우 $aCheckKey에 해당 필드명을 추가해서 처리해주세요
						$aCheckKey = ['optionTextInfo'];
						foreach ($val as $k => $v) {
							if (!in_array($k, $aCheckKey)) {
								$val[$k] = gd_htmlspecialchars_stripslashes($v);
							}
						}
						if ($orderBasic['userHandleFl'] == 'y' && (!Manager::isProvider() && $orderBasic['userHandleAdmFl'] == 'y') || (Manager::isProvider() && $orderBasic['userHandleScmFl'] == 'y')) {
							if ($isDisplayOrderGoods) {
								$val['userHandleInfo'] = $this->getUserHandleInfo($val['orderNo'], $val['sno'], [$this->search['userHandleMode']]);
							} else {
								$val['userHandleInfo'] = $this->getUserHandleInfo($val['orderNo'], null, [$this->search['userHandleMode']]);
							}
						}
						$data[$val['deliveryOrderNo']]['goods'][] = $val;
					}
				}
			}


			//복수배송지 사용 여부에 따라 페이지 노출시 scmNo 의 키를 order info sno 로 교체한다.
			$orderMultiShipping = App::load('\\Component\\Order\\OrderMultiShipping');
			$useMultiShippingKey = $orderMultiShipping->checkChangeOrderListKey();

			//택배사 sno에 매핑된 택배사 회사명 배열 가져오기
			$invoiceCompanyNameData = $this->getInvoiceCompanyNames();

			// 결제방법과 처리 상태 설정
			foreach ($data as $key => &$val) {
				$orderGoods = $val['goods'];
				unset($val['goods']);
				foreach ($orderGoods as $oKey => &$oVal) {
					if ($oVal['deliveryMethodFl']) {
						$oVal['deliveryMethodFlText'] = $delivery->deliveryMethodList['name'][$oVal['deliveryMethodFl']];
						$oVal['deliveryMethodFlSno'] = $delivery->deliveryMethodList['sno'][$oVal['deliveryMethodFl']];
					}
					// 상품명 태그 제거
					$oVal['orderGoodsNm'] = StringUtils::stripOnlyTags(html_entity_decode($oVal['orderGoodsNm']));
					$oVal['goodsNm'] = StringUtils::stripOnlyTags(html_entity_decode($oVal['goodsNm']));

					// 리스트에서 무조건 해외상점 몰 이름이 한글로 나오도록 강제 변환
					if ($oVal['mallSno'] > DEFAULT_MALL_NUMBER) {
						//리스트에 해외몰 주문건에대한 주문상품명르 노출시키기 위해 해외몰 주문상품명유지
						$oVal['orderGoodsNmGlobal'] = $oVal['orderGoodsNm'];
						$oVal['goodsNmGlobal'] = $oVal['goodsNm'];

						if (empty($oVal['orderGoodsNmStandard']) === false) {
							$oVal['orderGoodsNm'] = StringUtils::stripOnlyTags(html_entity_decode($oVal['orderGoodsNmStandard']));
						}
						if (empty($oVal['goodsNmStandard']) === false) {
							$oVal['goodsNm'] = StringUtils::stripOnlyTags(html_entity_decode($oVal['goodsNmStandard']));
						}
					}

					if (!$isDisplayOrderGoods && $searchStatusMode === 'o') {
						// 입금대기리스트 > 주문번호별 에서 '주문상품명' 을 입금대기 상태의 주문상품명만 구성
						$noPay = (int)$oVal['noPay'] - 1;
						if ($noPay > 0) {
							$oVal['orderGoodsNmStandard'] = $oVal['orderGoodsNm'] = $oVal['goodsNm'] . ' 외 ' . $noPay . ' 건';
						} else {
							$oVal['orderGoodsNmStandard'] = $oVal['orderGoodsNm'] = $oVal['goodsNm'];
						}

						if ($oVal['mallSno'] > DEFAULT_MALL_NUMBER) {
							if ($noPay > 0) {
								$oVal['orderGoodsNmGlobal'] = $oVal['goodsNmGlobal'] . ' ' . __('외') . ' ' . $noPay . ' ' . __('건');
							} else {
								$oVal['orderGoodsNmGlobal'] = $oVal['goodsNmGlobal'];
							}
						}
					}

					//상품진열시에만 실행

					if ($isDisplayOrderGoods) {

						// 옵션처리
						// 현재 foreach문의 $data를 할당하면서 이미 gd_htmlspecialchars_stripslashes처리를 하기때문에 여기서는 처리할필요가없음
						$options = json_decode($oVal['optionInfo'], true);

						$oVal['optionInfo'] = $options;
						if ($oVal['orderChannelFl'] == 'naverpay') {
							$naverPay = new NaverPay();
							$oVal['checkoutData'] = json_decode($oVal['checkoutData'], true);
							if ($oVal['checkoutData']['returnData']['ReturnReason']) {
								$oVal['handleReason'] = $naverPay->getClaimReasonCode($oVal['checkoutData']['returnData']['ReturnReason'], 'back');
							} else if ($oVal['checkoutData']['exchangeData']['ExchangeReason']) {
								$oVal['handleReason'] = $naverPay->getClaimReasonCode($oVal['checkoutData']['exchangeData']['ExchangeReason'], 'back');
							} else if ($oVal['checkoutData']['cancelData']['CancelReason']) {
								$oVal['handleReason'] = $naverPay->getClaimReasonCode($oVal['checkoutData']['cancelData']['CancelReason'], 'back');
							}
						}

						// 텍스트옵션
						$textOptions = json_decode($oVal['optionTextInfo'], true);
						$oVal['optionTextInfo'] = $textOptions;

						// 배송 택배사 설정
						$oVal['invoiceCompanyNm'] = $invoiceCompanyNameData[$oVal['invoiceCompanySno']];
					}

					//복수배송지를 사용 중이며 리스트에서 노출시킬 목적으로만 사용중이면 사은품은 단 한번만 저장시킨다.
					if ($useMultiShippingKey === true) {
						if (!$giftList[$key][$oVal['scmNo']]) {
							$oVal['gift'] = $this->getOrderGift($key, $oVal['scmNo'], 40);
							$giftList[$key][$oVal['scmNo']] = $oVal['gift'];
						}
					} else {
						// 사은품
						if ($giftList[$key]) {
							$oVal['gift'] = $giftList[$key];
						} else {
							$oVal['gift'] = $this->getOrderGift($key, $oVal['scmNo'], 40);
							$giftList[$key] = $oVal['gift'];
						}
					}

					// 추가상품
					$oVal['addGoods'] = $this->getOrderAddGoods(
						$key,
						$oVal['orderCd'],
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

					// 추가상품 할인/적립 안분 금액을 포함한 총 금액 (상품별 적립/할인 금액 + 추가상품별 적립/할인 금액)
					$oVal['totalMemberDcPrice'] = $oVal['memberDcPrice'];
					$oVal['totalMemberOverlapDcPrice'] = $oVal['memberOverlapDcPrice'];
					$oVal['totalCouponGoodsDcPrice'] = $oVal['couponGoodsDcPrice'];
					$oVal['totalGoodsMileage'] = $oVal['goodsMileage'];
					$oVal['totalMemberMileage'] = $oVal['memberMileage'];
					$oVal['totalCouponGoodsMileage'] = $oVal['couponGoodsMileage'];
					$oVal['totalDivisionUseDeposit'] = $oVal['divisionUseDeposit'];
					$oVal['totalDivisionUseMileage'] = $oVal['divisionUseMileage'];
					$oVal['totalDivisionCouponOrderDcPrice'] = $oVal['divisionCouponOrderDcPrice'];
					if (!empty($oVal['addGoods'])) {
						foreach ($oVal['addGoods'] as $aVal) {
							$oVal['totalMemberDcPrice'] += $aVal['addMemberDcPrice'];
							$oVal['totalMemberOverlapDcPrice'] += $aVal['addMemberOverlapDcPrice'];
							$oVal['totalCouponGoodsDcPrice'] += $aVal['addCouponGoodsDcPrice'];
							$oVal['totalGoodsMileage'] += $aVal['addGoodsMileage'];
							$oVal['totalMemberMileage'] += $aVal['addMemberMileage'];
							$oVal['totalCouponGoodsMileage'] += $aVal['addCouponGoodsMileage'];
							$oVal['totalDivisionUseDeposit'] += $aVal['divisionAddUseDeposit'];
							$oVal['totalDivisionUseMileage'] += $aVal['divisionAddUseMileage'];
							$oVal['totalDivisionCouponOrderDcPrice'] += $aVal['divisionAddCouponOrderDcPrice'];
						}
					}

					// 추가상품 수량 (테이블 UI 처리에 필요)
					$oVal['addGoodsCnt'] = empty($oVal['addGoods']) ? 0 : count($oVal['addGoods']);

					// 주문 상태명 설정
					$oValOrderStatus = $oVal['orderStatus'];
					if (gd_isset($oValOrderStatus)) {
						$oVal['beforeStatusStr'] = $this->getOrderStatusAdmin($oVal['beforeStatus']);
						$oVal['totalStatusStr'] = $this->getOrderStatusAdmin($oVal['totalStatus']);
						$oVal['settleKindStr'] = $this->printSettleKind($oVal['settleKind']);
						$oVal['escrowFl'] = substr($oVal['settleKind'], 0, 1);

						// 반품/교환/환불신청인 경우 해당 상태를 출력
						if ($isUserHandle) {
							$oVal['orderStatusStr'] = $this->getUserHandleMode($oVal['userHandleMode'], $oVal['userHandleFl']);
						} else {
							$oVal['orderStatusStr'] = $this->getOrderStatusAdmin($oVal['orderStatus']);
						}
					}

					//총 할인금액
					$totalDcPriceArray = [
						$orderData[$oVal['orderNo']]['totalGoodsDcPrice'],
						$orderData[$oVal['orderNo']]['totalMemberDcPrice'],
						$orderData[$oVal['orderNo']]['totalMemberOverlapDcPrice'],
						$orderData[$oVal['orderNo']]['totalCouponGoodsDcPrice'],
						$orderData[$oVal['orderNo']]['totalCouponOrderDcPrice'],
						$orderData[$oVal['orderNo']]['totalMemberDeliveryDcPrice'],
						$orderData[$oVal['orderNo']]['totalCouponDeliveryDcPrice'],
						$orderData[$oVal['orderNo']]['totalEnuriDcPrice'],
					];

					// 마이앱 사용에 따른 분기 처리
					if ($this->useMyapp) {
						array_push($totalDcPriceArray, $orderData[$oVal['orderNo']]['totalMyappDcPrice']);
					}

					$oVal['totalDcPrice'] = array_sum($totalDcPriceArray);

					//총 부가결제 금액
					$oVal['totalUseAddedPrice'] = $orderData[$oVal['orderNo']]['useMileage'] + $orderData[$oVal['orderNo']]['useDeposit'];

					//총 주문 금액 : 총 상품금액 + 총 배송비 - 총 할인금액
					$oVal['totalOrderPrice'] = $orderData[$oVal['orderNo']]['totalGoodsPrice'] + $orderData[$oVal['orderNo']]['totalDeliveryCharge'] - $oVal['totalDcPrice'];

					//총 실 결제금액
					if ($oVal['orderChannelFl'] === 'naverpay') {
						$checkoutData = json_decode($orderData[$oVal['orderNo']]['checkoutData'], true);
						// 네이버페이 포인트를 사용한 경우 realtax 에 값이 담기지 않아 실금액을 구할 수 없으므로 checkoutData 를 이용한다.
						if ($isDisplayOrderGoods) {
							// $isDisplayOrderGoods 인 경우 상단에서 이미 decode 처리가 되어 있음
							$oVal['totalRealSettlePrice'] = $checkoutData['orderData']['GeneralPaymentAmount'];
						} else {
							$checkoutData = json_decode($oVal['checkoutData'], true);
							$oVal['totalRealSettlePrice'] = $checkoutData['orderData']['GeneralPaymentAmount'];
						}
					} else {
						$oVal['totalRealSettlePrice'] = $orderData[$oVal['orderNo']]['realTaxSupplyPrice'] + $orderData[$oVal['orderNo']]['realTaxVatPrice'] + $orderData[$oVal['orderNo']]['realTaxFreePrice'];
					}

					// 멀티상점 환율 기본 정보
					$oVal['currencyPolicy'] = json_decode($oVal['currencyPolicy'], true);
					$oVal['exchangeRatePolicy'] = json_decode($oVal['exchangeRatePolicy'], true);
					$oVal['currencyIsoCode'] = $oVal['currencyPolicy']['isoCode'];
					$oVal['exchangeRate'] = $oVal['exchangeRatePolicy']['exchangeRate' . $oVal['currencyPolicy']['isoCode']];

					//총 배송지 수
					$oVal['totalOrderInfoCount'] = $orderInfoCountData[$oVal['orderNo']];

					//총 배송 메모 수 및 첫번째 메모
					$oVal['multiShippingOrderMemo'] = $orderMemoData[$oVal['orderNo']]['orderMemo'];
					$oVal['multiShippingOrderMemoCount'] = $orderMemoData[$oVal['orderNo']]['orderMemoCount'];

					//총 수령자 수 및 첫번째 수령자
					$oVal['multiShippingReceiverName'] = $orderReceiverNameData[$oVal['orderNo']]['receiverName'];
					$oVal['multiShippingReceiverNameCount'] = $orderReceiverNameData[$oVal['orderNo']]['receiverNameCount'];

					// 주문별 주문상태 카운팅
					if (!isset($data[$key]['status'])) {
						$data[$key]['status'] = [];
					}
					if (in_array(substr($oVal['orderStatus'], 0, 1), ['o', 'p', 'g', 'f'])) {
						$data[$key]['status']['noDelivery'] += 1;
					}
					if ($oVal['orderStatus'] == 'd1') {
						$data[$key]['status']['deliverying'] += 1;
					}
					if ($oVal['orderStatus'] == 'd2' || substr($oVal['orderStatus'], 0, 1) == 's') {
						$data[$key]['status']['deliveryed'] += 1;
					}
					if (substr($oVal['orderStatus'], 0, 1) == 'c') {
						$data[$key]['status']['cancel'] += 1;
					}
					if (substr($oVal['orderStatus'], 0, 1) == 'e') {
						$data[$key]['status']['exchange'] += 1;
					}
					if (substr($oVal['orderStatus'], 0, 1) == 'b') {
						$data[$key]['status']['back'] += 1;
					}
					if (substr($oVal['orderStatus'], 0, 1) == 'r') {
						$data[$key]['status']['refund'] += 1;
					}

					// 데이터 SCM/Delivery 3차 배열로 재구성
					if ($useMultiShippingKey === true) {
						//복수배송지를 사용 중이며 리스트에서 노출시킬 목적으로만 사용중이면 주문데이터 배열의 scmNo 를 orderInfoSno로 대체한다.
						$data[$key]['goods'][$oVal['orderInfoSno']][$oVal['orderDeliverySno']][$oKey] = $oVal;
					} else {
						$data[$key]['goods'][$oVal['scmNo']][$oVal['deliverySno']][$oKey] = $oVal;
					}

					// 테이블 UI 표현을 위한 변수
					if (!isset($data[$key]['cnt'])) {
						$data[$key]['cnt'] = [];
					}
					//복수배송지를 사용 중이며 리스트에서 노출시킬 목적으로만 사용중이면 주문데이터 배열의 scmNo 를 orderInfoSno로 대체한다.
					if ($useMultiShippingKey === true) {
						$data[$key]['cnt']['multiShipping'][$oVal['orderInfoSno']] += 1 + $oVal['addGoodsCnt'];
					} else {
						$data[$key]['cnt']['scm'][$oVal['scmNo']] += 1 + $oVal['addGoodsCnt'];
					}
					$deliveryUniqueKey = $oVal['deliverySno'] . '-' . $oVal['orderDeliverySno'];
					$data[$key]['cnt']['delivery'][$deliveryUniqueKey] += 1 + $oVal['addGoodsCnt'];
					$data[$key]['cnt']['goods']['all'] += 1 + $oVal['addGoodsCnt'];
					$data[$key]['cnt']['goods']['goods'] += 1;
					$data[$key]['cnt']['goods']['addGoods'] += $oVal['addGoodsCnt'];
				}

				// 별도의 데이터 추가 실제 총 결제금액 = 주문결제금액 + 배송비
				foreach ($orderGoods as $tKey => $tVal) {
					$firstKey = $tVal['scmNo'];
					$secontKey = $tVal['deliverySno'];

					//복수배송지를 사용 중이며 리스트에서 노출시킬 목적으로만 사용중이면 주문데이터 배열의 scmNo 를 orderInfoSno로 대체한다.
					if ($useMultiShippingKey === true) {
						$firstKey = $tVal['orderInfoSno'];
						$secontKey = $tVal['orderDeliverySno'];
					}
					$data[$key]['goods'][$firstKey][$secontKey][$tKey]['totalSettlePrice'] = $oVal['settlePrice'];
				}

				if (Manager::isProvider()) {
					$data[$key] = $this->getProviderTotalPriceList($data[$key], $key);
				}
			}

			// 각 데이터 배열화
			$getData['data'] = gd_isset($data);

			unset($giftList);
		}

		// 사용자 교환/반품/환불 신청 여부
		$getData['isUserHandle'] = $isUserHandle;

		// 검색값 설정
		if (empty($this->search) === false) {
			$getData['search'] = gd_htmlspecialchars($this->search);
		}

		// 체크값 설정
		if (empty($this->checked) === false) {
			$getData['checked'] = $this->checked;
		}

		// 리스트 그리드 항목 설정
		if (empty($this->orderGridConfigList) === false) {
			$getData['orderGridConfigList'] = $this->orderGridConfigList;
		}

		//복수배송지를 사용 중이며 리스트에서 노출시킬 목적으로만 사용중이면 주문데이터 배열의 scmNo 를 orderInfoSno로 대체한다. : true 일시
		$getData['useMultiShippingKey'] = $useMultiShippingKey;

		// 페이지 전체값
		$page = \App::load('\\Component\\Page\\Page');
		$getData['amount'] = $page->recode['total'];

		return $getData;
	}

	/**
	 * 관리자 회차배송 리스트 엑셀
	 */
	public function fetchScheduledDeliveryListForAdminExcel($searchData, $searchPeriod, $isUserHandle = false, $orderType = 'goods', $excelField, $page, $pageLimit)
	{
		unset($this->arrWhere);
		unset($this->arrBind);
		//$excelField  / $page / $pageLimit 해당 정보가 없을경우 튜닝한 업체이므로 기존형태로 반환해줘야함

		// // --- 검색 설정
		$this->_setDeliverySearch($searchData, $searchPeriod, $isUserHandle);

		// 체크박스로 선택된 항목 출력시
		if ($searchData['statusCheck'] && is_array($searchData['statusCheck'])) {
			foreach ($searchData['statusCheck'] as $key => $val) {
				foreach ($val as $k => $v) {
					$_tmp = explode(INT_DIVISION, $v);
					if ($orderType == 'goods' && $searchData['view'] == 'order') unset($_tmp[1]);
					if ($_tmp[1]) {
						$tmpWhere[] = "(sd.orderNo = ? AND sd.sno = ?)";
						$this->db->bind_param_push($this->arrBind, 's', $_tmp[0]);
						$this->db->bind_param_push($this->arrBind, 's', $_tmp[1]);
					} else {
						$tmpWhere[] = "(sd.orderNo = ?)";
						$this->db->bind_param_push($this->arrBind, 's', $_tmp[0]);
					}
				}
			}

			$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			unset($tmpWhere);
		}

		$orderSort = "sd.estimatedDeliveryDt asc, sd.orderNo asc, sdg.sno asc";

		// 사용 필드
		$arrInclude = [
			'o.orderNo',
			'o.orderChannelFl',
			'o.apiOrderNo',
			'o.memNo',
			'o.orderChannelFl',
			'o.orderGoodsNm',
			'o.orderGoodsCnt',
			'o.settlePrice as totalSettlePrice',
			'o.totalDeliveryCharge',
			'o.useDeposit as totalUseDeposit',
			'o.useMileage as totalUseMileage',
			'(o.totalMemberDcPrice + o.totalMemberDeliveryDcPrice) AS totalMemberDcPrice',
			'o.totalGoodsDcPrice',
			'(o.totalCouponGoodsDcPrice + o.totalCouponOrderDcPrice + o.totalCouponDeliveryDcPrice)as totalCouponDcPrice',
			'totalCouponOrderDcPrice',
			'totalCouponDeliveryDcPrice',
			'o.totalMileage',
			'o.totalGoodsMileage',
			'o.totalMemberMileage',
			'(o.totalCouponGoodsMileage+o.totalCouponOrderMileage) as totalCouponMileage',
			'o.settleKind',
			'o.bankAccount',
			'o.bankSender',
			'o.receiptFl',
			'o.pgResultCode',
			'o.pgTid',
			'o.pgAppNo',
			'o.paymentDt',
			'o.addField',
			'o.mallSno',
			'o.orderGoodsNmStandard',
			'o.overseasSettlePrice',
			'o.currencyPolicy',
			'o.exchangeRatePolicy',
			'o.totalEnuriDcPrice',
			'(o.realTaxSupplyPrice + o.realTaxVatPrice + o.realTaxFreePrice) AS totalRealSettlePrice',
			'o.checkoutData',
			'o.trackingKey',
			'o.fintechData',
			'o.checkoutData',
			'o.orderTypeFl',
			'o.appOs',
			'o.pushCode',
			'o.memberPolicy',
			'o.totalMyappDcPrice',
			'o.pgSettleNm',
			'oi.regDt as orderDt',
			'oi.orderName',
			'oi.orderEmail',
			'oi.orderPhone',
			'oi.orderCellPhone',
			'oi.receiverName',
			'oi.receiverPhone',
			'oi.receiverCellPhone',
			'oi.receiverUseSafeNumberFl',
			'oi.receiverSafeNumber',
			'oi.receiverSafeNumberDt',
			'oi.receiverZonecode',
			'oi.receiverZipcode',
			'oi.receiverAddress',
			'oi.receiverAddressSub',
			'oi.receiverCity',
			'oi.receiverState',
			'oi.receiverCountryCode',
			'oi.orderMemo',
			'oi.packetCode',
			'oi.orderInfoCd',
			'oi.visitName',
			'oi.visitPhone',
			'oi.visitMemo',
			'CONCAT(sd.orderNo, \'-\', sd.orderGoodsSno, \'-\', sd.round) AS deliveryOrderNo',
			'sd.orderDeliverySno',
			'sd.round',
			'sd.totalRound',
			'sd.scmNo',
			'sdg.orderGoodsSno ',
			'sd.deliveryStatus',
			'sdg.goodsNo',
			'sdg.goodsNm',
			'sdg.goodsCd',
			'sdg.goodsCnt',
			'sdg.goodsPrice',
			'sd.invoiceNo',
			'sd.deliveryCompleteDt',
			'sd.deliveryDt',
			'sd.estimatedDeliveryDt',
			'od.deliveryCharge',
			'od.orderInfoSno',
			'od.deliveryPolicyCharge',
			'od.deliveryAreaCharge',
			'od.realTaxSupplyDeliveryCharge',
			'od.realTaxVatDeliveryCharge',
			'od.realTaxFreeDeliveryCharge',
			'od.divisionDeliveryUseMileage',
			'od.divisionDeliveryUseDeposit',
		];
		
		$join[] = ' JOIN  ms_scheduledDeliveryGoods sdg ON sdg.scheduledDeliverySno = sd.sno ';
		$join[] = ' JOIN ' . DB_ORDER . ' o ON o.orderNo = sd.orderNo ';
		$join[] = ' LEFT JOIN ' . DB_ORDER_DELIVERY . ' od ON sd.orderDeliverySno = od.sno ';
		$join[] = ' LEFT JOIN ' . DB_MEMBER_HACKOUT_ORDER . ' mho ON sd.orderNo = mho.orderNo';
		$join[] = ' LEFT JOIN ' . DB_ORDER_INFO . ' oi ON (sd.orderNo = oi.orderNo) 
                    AND (CASE WHEN od.orderInfoSno > 0 THEN od.orderInfoSno = oi.sno ELSE oi.orderInfoCd = 1 END)';

		//공급사
		if (in_array("scmNm", array_values($excelField)) || in_array("scmNo", array_values($excelField)) || empty($excelField) === true || empty($searchData['scmFl']) === false || ($searchData['key'] == 'all' && $searchData['keyword'])) {
			$arrIncludeScm = [
				'sm.companyNm as scmNm'
			];

			$arrInclude = array_merge($arrInclude, $arrIncludeScm);
			$join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' sm ON sd.scmNo = sm.scmNo ';
			unset($arrIncludeScm);
		}

		//회원
		if (in_array("memNo", array_values($excelField)) || in_array("memNm", array_values($excelField)) ||  in_array("groupNm", array_values($excelField)) || empty($excelField) === true || $searchData['memFl'] || ($searchData['key'] == 'all' && $searchData['keyword'])) {
			$arrIncludeMember = [
				'IF(m.memNo > 0, m.memNm, oi.orderName) AS memNm',
				'm.memId',
				'mg.groupNm',
			];

			$arrInclude = array_merge($arrInclude, $arrIncludeMember);
			$join[] = ' LEFT JOIN ' . DB_MEMBER . ' m ON o.memNo = m.memNo ';
			$join[] = ' LEFT OUTER JOIN ' . DB_MEMBER_GROUP . ' mg ON m.groupSno = mg.sno ';
			unset($arrIncludeMember);
		}

		//사은품
		if (in_array("oi.presentSno", array_values($excelField)) || empty($excelField) === true || in_array("ogi.giftNo", array_values($excelField))) {
			$arrIncludeGift = [
					'GROUP_CONCAT(ogi.presentSno SEPARATOR "/") AS presentSno ',
					'GROUP_CONCAT(ogi.giftNo SEPARATOR "/") AS giftNo '
				];

			$arrInclude = array_merge($arrInclude, $arrIncludeGift);

			$join[] = ' LEFT JOIN ' . DB_ORDER_GIFT . ' ogi ON ogi.orderNo = o.orderNo ';
			unset($arrIncludeGift);
		}

		//상품 브랜드 코드 검색
		if (empty($this->search['brandCd']) === false || empty($excelField) === true || empty($this->search['brandNoneFl']) === false) {
			$join[] = ' LEFT JOIN ' . DB_GOODS . ' as g ON sdg.goodsNo = g.goodsNo ';
		}

		//택배 예약 상태에 따른 검색
		if ($this->search['invoiceReserveFl']) {
			$join[] = ' LEFT JOIN ' . DB_ORDER_GODO_POST . ' ogp ON ogp.invoiceNo = sd.invoiceNo ';
		}

		// 쿠폰검색시만 join
		if ($this->search['couponNo'] > 0) {
			$join[] = ' LEFT JOIN ' . DB_ORDER_COUPON . ' oc ON o.orderNo = oc.orderNo ';
			$join[] = ' LEFT JOIN ' . DB_MEMBER_COUPON . ' mc ON mc.memberCouponNo = oc.memberCouponNo ';
		}
		
		// 현 페이지 결과
		if ($page == '0') {
			$this->db->strField = 'CONCAT(sd.orderNo, \'-\', sd.orderGoodsSno, \'-\', sd.round) deliveryOrderNo';
			$this->db->strJoin = implode('', $join);
			$this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));


			//총갯수관련
			$query = $this->db->query_complete();
			$strSQL = 'SELECT ' . array_shift($query) . ' FROM ms_scheduledDelivery sd ' . implode(' ', $query);

			$result['totalCount'] = $this->db->query_fetch($strSQL, $this->arrBind);
		}

		$this->db->strField = implode(', ', $arrInclude) . ",totalGoodsPrice";
		$this->db->strJoin = implode('', $join);
		$this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
		$this->db->strOrder = $orderSort;
		if ($pageLimit) $this->db->strLimit = (($page * $pageLimit)) . "," . $pageLimit;
		$query = $this->db->query_complete();
		$strSQL = 'SELECT ' . array_shift($query) . ' FROM ms_scheduledDelivery sd ' . implode(' ', $query);

		if (empty($excelField) === false) {
			if (Manager::isProvider()) {
				$result['orderList'] = $this->db->query_fetch($strSQL, $this->arrBind);
			} else {
				$result['orderList'] = $this->db->query_fetch_generator($strSQL, $this->arrBind);
			}
		} else {
			$result = $this->db->query_fetch($strSQL, $this->arrBind);
		}

		if (Manager::isProvider()) {
			$result = $this->getProviderTotalPriceExcelList($result, $orderType);
		}

		return $result;
	}

	public function getInvoiceCompanyNames(): array
	{
		// 배송회사 정보 설정
		$delivery = \App::load('\\Component\\Delivery\\Delivery');
		$deliveryCompanyList = $delivery->getDeliveryCompany();
		$arrSno = ArrayUtils::getSubArrayByKey($deliveryCompanyList, 'sno');
		$arrCompanyName = ArrayUtils::getSubArrayByKey($deliveryCompanyList, 'companyName');
		return array_combine($arrSno, $arrCompanyName);
	}

	protected function _setDeliverySearch($searchData, $searchPeriod = -1, $isUserHandle = false)
	{
		if (isset($searchData['isMultiSearch'])) {
			$isMultiSearch = $searchData['isMultiSearch'];
		} else {
			$isMultiSearch = gd_isset(\Session::get('manager.isOrderSearchMultiGrid'), 'n');
		}

		//탈퇴회원거래내역조회 제한 여부
		$session = \App::getInstance('session');
		$manager = \App::load('\\Component\\Member\\Manager');
		$this->withdrawnMembersOrderLimitViewFl = $manager->getManagerFunctionAuth($session->get('manager.sno'))['functionAuth']['withdrawnMembersOrderLimitViewFl'];

		// 통합 검색
		$this->search['combineSearch'] = [
			'o.orderNo' => __('주문번호'),
			'sd.invoiceNo' => __('송장번호'),
			'sdg.goodsNm' => __('상품명'),
			'sdg.goodsNo' => __('상품코드'),
			'__disable1' => '==========',
			'oi.orderName' => __('주문자명'),
			'oi.orderPhone' => __('주문자 전화번호'),
			'oi.orderCellPhone' => __('주문자 휴대폰번호'),
			'oi.orderEmail' => __('주문자 이메일'),
			'oi.receiverName' => __('수령자명'),
			'oi.receiverPhone' => __('수령자 전화번호'),
			'oi.receiverCellPhone' => __('수령자 휴대폰번호'),
			'o.bankSender' => __('입금자명'),
			'__disable2' => '==========',
			'm.memId' => __('아이디'),
			'm.nickNm' => __('닉네임'),
			'oi.orderName' => __('주문자명'),
		];
		if ($isMultiSearch == 'y') {
			$this->search['combineSearch'] = [
				'o.orderNo' => __('주문번호'),
				'sd.invoiceNo' => __('송장번호'),
				'o.bankSender' => __('입금자명'),
				'm.memId' => __('아이디'),
				'm.nickNm' => __('닉네임'),
				'__disable1' => '==========',
				'oi.orderName' => __('주문자명'),
				'oi.orderPhone' => __('주문자 전화번호'),
				'oi.orderCellPhone' => __('주문자 휴대폰번호'),
				'oi.orderEmail' => __('주문자 이메일'),
				'oi.receiverName' => __('수령자명'),
				'oi.receiverPhone' => __('수령자 전화번호'),
				'oi.receiverCellPhone' => __('수령자 휴대폰번호'),
			];
		}

		// Like Search & Equal Search
		$this->search['searchKindArray'] = [
			'equalSearch' => __('검색어 전체일치'),
			'fullLikeSearch' => __('검색어 부분포함'),
		];

		if (gd_is_provider() === false) {
			$this->search['combineSearch']['__disable3'] = "==========";
			$this->search['combineSearch']['sm.companyNm'] = __('공급사명');
			if (gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true) {
				$this->search['combineSearch']['pu.purchaseNm'] = __('매입처명');
			}
		}

		// !중요! 순서 변경시 하단의 노출항목 조절 필요
		$this->search['combineTreatDate'] = [
			'o.regDt' => __('주문일'),
			'o.paymentDt' => __('결제확인일'),
			'sd.invoiceDt' => __('송장입력일'),
			'sd.estimatedDeliveryDt' => __('배송희망일'),
			'sd.deliveryDt' => __('배송일'),
			'sd.deliveryCompleteDt' => __('배송완료일'),
		];

		// --- $searchData trim 처리
		if (isset($searchData)) {
			gd_trim($searchData);
		}

		// --- 정렬
		$this->search['sortList'] = [
			'o.orderNo desc' => sprintf('%s↓', __('주문일')),
			'o.orderNo asc' => sprintf('%s↑', __('주문일')),
			'o.orderNo desc' => sprintf('%s↓', __('주문번호')),
			'o.orderNo asc' => sprintf('%s↑', __('주문번호')),
			'o.orderGoodsNm desc' => sprintf('%s↓',  __('상품명')),
			'o.orderGoodsNm asc' => sprintf('%s↑', __('상품명')),
			'oi.orderName desc' => sprintf('%s↓', __('주문자')),
			'oi.orderName asc' => sprintf('%s↑', __('주문자')),
			'o.settlePrice desc' => sprintf('%s↓', __('총 결제금액')),
			'o.settlePrice asc' => sprintf('%s↑', __('총 결제금액')),
			'oi.receiverName desc' => sprintf('%s↓', __('수령자')),
			'oi.receiverName asc' => sprintf('%s↑', __('수령자')),
			'sm.companyNm desc' => sprintf('%s↓', __('공급사')),
			'sm.companyNm asc' => sprintf('%s↑', __('공급사')),
			'sd.deliveryStatus desc' => sprintf('%s↓', __('처리상태')),
			'sd.deliveryStatus asc' => sprintf('%s↑', __('처리상태')),
		];

		// 상품주문번호별 탭을 제외하고는 처리상태 정렬 제거
		if ($isUserHandle === false) {
			unset($this->search['sortList']['sd.deliveryStatus desc'], $this->search['sortList']['sd.deliveryStatus asc']);
		}

		// 상품주문번호별 탭을 제외하고는 처리상태 정렬 제거
		if ($isUserHandle === false) {
			unset($this->search['sortList']['sd.deliveryStatus desc'], $this->search['sortList']['sd.deliveryStatus asc']);
		}

		$this->search['treatDateFl'] = gd_isset($searchData['treatDateFl'], 'sd.estimatedDeliveryDt');

		self::setAddSearchSortList(array('estimatedDeliveryDt'));

		// 검색을 위한 bind 정보
		$fieldTypeGoods = DBTableField::getFieldTypes('tableGoods');

		// 검색기간 설정
		$data = gd_policy('order.defaultSearch');
		$data['searchPeriod'] = $searchPeriod;


		// CRM관리에서 주문요약 내역 90일 처리
		$thisCallController = \App::getInstance('ControllerNameResolver')->getControllerName();
		if ($thisCallController == 'Controller\Admin\Share\MemberCrmController') {
			$searchPeriod = 90;
		} else {
			$searchPeriod = gd_isset($data['searchPeriod'], -1);
		}

		$isInitialSearch = !$searchData['treatDate'];
		// --- 검색 설정
		$this->search['mallFl'] = gd_isset($searchData['mallFl'], 'all');
		$this->search['exceptOrderStatus'] = gd_isset($searchData['exceptOrderStatus']);    //예외처리할 주문상태
		$this->search['detailSearch'] = gd_isset($searchData['detailSearch']);
		$this->search['statusMode'] = gd_isset($searchData['statusMode']);
		$this->search['key'] = gd_isset($searchData['key']);
		$this->search['keyword'] = gd_isset($searchData['keyword']);
		$this->search['sort'] = gd_isset($searchData['sort']);
		$this->search['orderStatus'] = gd_isset($searchData['orderStatus']);
		$this->search['pgChargeBack'] = gd_isset($searchData['pgChargeBack']);
		$this->search['processStatus'] = gd_isset($searchData['processStatus']);
		$this->search['userHandleMode'] = gd_isset($searchData['userHandleMode']);
		$this->search['userHandleFl'] = gd_isset($searchData['userHandleFl']);
		$this->search['treatDateFl'] = gd_isset($searchData['treatDateFl'], 'sd.estimatedDeliveryDt');
		$this->search['treatDate'][] = gd_isset($searchData['treatDate'][0], date('Y-m-d', strtotime('-' . $searchPeriod . ' day')));
		if ($searchPeriod == '1') $this->search['treatDate'][] = gd_isset($searchData['treatDate'][1], date('Y-m-d', strtotime('-' . $searchPeriod . ' day')));
		else $this->search['treatDate'][] = gd_isset($searchData['treatDate'][1], date('Y-m-d'));
		if (strtotime($this->search['treatDate'][1]) < strtotime($this->search['treatDate'][0])) {
			$tmp = $this->search['treatDate'][0];
			$this->search['treatDate'][0] = $this->search['treatDate'][1];
			$this->search['treatDate'][1] = $tmp;
		}
		if ($isInitialSearch) {
			$this->search['treatDate'][0] = date('Y-m-d', strtotime($this->search['treatDate'][0] . ' -1 year'));
		}
		if ($searchData['treatTimeFl'] != 'y') unset($searchData['treatTime']); // 시간설정 사용 시
		$this->search['treatTime'][] = gd_isset($searchData['treatTime'][0], '00:00:00');
		$this->search['treatTime'][] = gd_isset($searchData['treatTime'][1], '23:59:59');
		$this->search['treatTimeFl'] = gd_isset($searchData['treatTimeFl'], 'n');
		$this->search['settleKind'] = gd_isset($searchData['settleKind']);
		$this->search['settlePrice'][] = gd_isset($searchData['settlePrice'][0]);
		$this->search['settlePrice'][] = gd_isset($searchData['settlePrice'][1]);
		$this->search['memFl'] = gd_isset($searchData['memFl']);
		$this->search['memberGroupNo'] = gd_isset($searchData['memberGroupNo']);
		$this->search['memberGroupNoNm'] = gd_isset($searchData['memberGroupNoNm']);
		$this->search['receiptFl'] = gd_isset($searchData['receiptFl']);
		$this->search['userHandleViewFl'] = gd_isset($searchData['userHandleViewFl']);
		$this->search['orderTypeFl'] = gd_isset($searchData['orderTypeFl']);
		$this->search['orderChannelFl'] = gd_isset($searchData['orderChannelFl']);
		$this->search['scmFl'] = gd_isset($searchData['scmFl'], 'all');
		$this->search['fdi'] = gd_isset($searchData['fdi'], '1');
		// 공급사 선택 후 공급사가 없는 경우
		if ($searchData['scmNo'] == 0 && $searchData['scmFl'] == 1) {
			$this->search['scmFl'] = 'all';
		}
		$this->search['scmNo'] = gd_isset($searchData['scmNo']);
		$this->search['scmNoNm'] = gd_isset($searchData['scmNoNm']);
		$this->search['scmAdjustNo'] = gd_isset($searchData['scmAdjustNo']);
		$this->search['scmAdjustType'] = gd_isset($searchData['scmAdjustType']);
		$this->search['manualPayment'] = gd_isset($searchData['manualPayment'], '');
		$this->search['invoiceFl'] = gd_isset($searchData['invoiceFl'], '');
		$this->search['firstSaleFl'] = gd_isset($searchData['firstSaleFl'], 'n');
		$this->search['withdrawnMembersOrderFl'] = gd_isset($searchData['withdrawnMembersOrderChk'], $this->withdrawnMembersOrderLimitViewFl == 'y' ? 'n' : 'y');
		$this->search['withGiftFl'] = gd_isset($searchData['withGiftFl'], 'n');
		$this->search['withMemoFl'] = gd_isset($searchData['withMemoFl'], 'n');
		$this->search['withAdminMemoFl'] = gd_isset($searchData['withAdminMemoFl'], 'n');
		$this->search['withPacket'] = gd_isset($searchData['withPacket'], 'n');
		$this->search['overDepositDay'] = gd_isset($searchData['overDepositDay']);
		$this->search['invoiceCompanySno'] = gd_isset($searchData['invoiceCompanySno']);
		$this->search['invoiceNoFl'] = gd_isset($searchData['invoiceNoFl']);
		$this->search['underDeliveryDay'] = gd_isset($searchData['underDeliveryDay']);
		$this->search['underDeliveryOrder'] = gd_isset($searchData['underDeliveryOrder'], 'n');
		$this->search['couponNo'] = gd_isset($searchData['couponNo']);
		$this->search['couponNoNm'] = gd_isset($searchData['couponNoNm']);
		$this->search['couponAllFl'] = gd_isset($searchData['couponAllFl']);
		$this->search['eventNo'] = gd_isset($searchData['eventNo']);
		$this->search['eventNoNm'] = gd_isset($searchData['eventNoNm']);
		$this->search['dateSearchFl'] = gd_isset($searchData['dateSearchFl'], 'y');

		$this->search['purchaseNo'] = gd_isset($searchData['purchaseNo']);
		$this->search['purchaseNoNm'] = gd_isset($searchData['purchaseNoNm']);
		$this->search['purchaseNoneFl'] = gd_isset($searchData['purchaseNoneFl']);

		$this->search['brandNoneFl'] = gd_isset($searchData['brandNoneFl']);
		$this->search['brand'] = ArrayUtils::last(gd_isset($searchData['brand']));
		$this->search['brandCd'] = gd_isset($searchData['brandCd']);
		$this->search['brandCdNm'] = gd_isset($searchData['brandCdNm']);
		$this->search['orderNo'] = gd_isset($searchData['orderNo']);
		$this->search['orderMemoCd'] = gd_isset($searchData['orderMemoCd']);

		$this->search['goodsNo'] = gd_isset($searchData['goodsNo']);
		$this->search['goodsText'] = gd_isset($searchData['goodsText']);
		$this->search['goodsKey'] = gd_isset($searchData['goodsKey']);

		// --- 검색 종류 설정 (Like Or Equal)
		$this->search['searchKind'] = gd_isset($searchData['searchKind']);
		$this->search['searchPeriod'] = gd_isset($searchData['searchPeriod'], $searchPeriod);

		$orderBasic = gd_policy('order.basic');
		if (($orderBasic['userHandleAdmFl'] == 'y' && $orderBasic['userHandleScmFl'] == 'y') === false) {
			unset($orderBasic['userHandleScmFl']);
		}

		if ($isMultiSearch == 'y' && empty($searchData['memNo']) === true) {
			if (DateTimeUtils::intervalDay($this->search['treatDate'][0], $this->search['treatDate'][1]) > 180) {
				throw new AlertBackException(__('6개월이상 기간으로 검색하실 수 없습니다.'));
			}
		} else {
			if (DateTimeUtils::intervalDay($this->search['treatDate'][0], $this->search['treatDate'][1]) > 700) {
				throw new AlertBackException(__('700일 이상 기간으로 검색하실 수 없습니다.'));
			}
		}

		$this->search['view'] = gd_isset($searchData['view'], 'orderGoodsSimple');

		// CRM
		$this->search['memNo'] = gd_isset($searchData['memNo'], null);

		// --- 검색 설정
		$this->checked['treatTimeFl'][$this->search['treatTimeFl']]  =
			$this->checked['purchaseNoneFl'][$this->search['purchaseNoneFl']]  =
			$this->checked['mallFl'][$this->search['mallFl']] =
			$this->checked['scmFl'][$this->search['scmFl']] =
			$this->checked['fdi'][$this->search['fdi']] =
			$this->checked['memFl'][$this->search['memFl']] =
			$this->checked['manualPayment'][$this->search['manualPayment']] =
			$this->checked['firstSaleFl'][$this->search['firstSaleFl']] =
			$this->checked['withdrawnMembersOrderFl'][$this->search['withdrawnMembersOrderFl']] =
			$this->checked['withGiftFl'][$this->search['withGiftFl']] =
			$this->checked['withMemoFl'][$this->search['withMemoFl']] =
			$this->checked['withAdminMemoFl'][$this->search['withAdminMemoFl']] =
			$this->checked['withPacket'][$this->search['withPacket']] =
			$this->checked['underDeliveryOrder'][$this->search['underDeliveryOrder']] =
			$this->checked['invoiceNoFl'][$this->search['invoiceNoFl']] =
			$this->checked['brandNoneFl'][$this->search['brandNoneFl']] =
			$this->checked['couponAllFl'][$this->search['couponAllFl']] =
			$this->checked['receiptFl'][$this->search['receiptFl']] =
			$this->checked['memoType'][$this->search['memoType']] =
			$this->checked['userHandleViewFl'][$this->search['userHandleViewFl']] = 'checked="checked"';
		$this->checked['periodFl'][$searchPeriod] = 'active';

		// --- 검색 종류 설정 (Like Or Equal)
		if ($this->search['searchKind'] && in_array($this->search['key'], $this->changeSearchKind)) {
			$this->setKeySearchType($this->search['key'], $this->search['searchKind']);
		}

		if ($this->search['orderNo'] !== null) {
			$this->arrWhere[] = 'o.orderNo = ?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['orderNo']);
		}

		// 회원 주문인 경우 (CRM 주문조회)
		if ($this->search['memNo'] !== null) {
			$this->arrWhere[] = 'o.memNo = ?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['memNo']);
		}

		// 주문 상태 모드가 있는 경우
		if ($this->search['statusMode'] !== null) {
			$tmp = explode(',', $this->search['statusMode']);
			foreach ($tmp as $val) {
				$sameOrderStatus = $this->getOrderStatusList($val, null, null, 'orderList');

				$sameOrderStatus = array_keys($sameOrderStatus);
				$sameOrderStatusCount = count($sameOrderStatus);
				if ($sameOrderStatusCount > 1) {
					$tmpbind = array_fill(0, $sameOrderStatusCount, '?');
					$tmpWhere[] = 'sd.deliveryStatus IN (' . implode(',', $tmpbind) . ')';
					foreach ($sameOrderStatus as $valStatus) {
						$this->db->bind_param_push($this->arrBind, 's', $valStatus);
					}
				} else if ($sameOrderStatus) {
					$tmpWhere[] = 'sd.deliveryStatus = ?';
					$this->db->bind_param_push($this->arrBind, 's', $sameOrderStatus[0]);
				}
			}
			if ($tmpWhere) {
				$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			}
			unset($tmpWhere);
		}

		if ($this->search['exceptOrderStatus']) { //예외처리할 주문상태 쿼리
			$exceptStatusQuery = implode("','", $this->search['exceptOrderStatus']);
			$this->arrWhere[] = "sd.deliveryStatus NOT IN ('" . $exceptStatusQuery . "')";
		}

		// 공급사 선택
		if (Manager::isProvider()) {
			// 공급사로 로그인한 경우 기존 scm에 값 설정
			$this->arrWhere[] = 'sd.scmNo = ' . Session::get('manager.scmNo');
			// 공급사에서는 입금대기 상태와 취소상태가 보여지면 안된다.
			$excludeStatusCode = ['o', 'c', 'f'];
			$arrWhereOrderStatusArray = $this->getExcludeOrderStatus($this->orderStatus, $excludeStatusCode);
			$this->arrWhere[] = 'sd.deliveryStatus IN (\'' . implode('\',\'', array_keys($arrWhereOrderStatusArray)) . '\')';
			unset($arrWhereOrderStatusArray);
		} else {
			if ($this->search['scmFl'] == '1') {
				if (is_array($this->search['scmNo'])) {
					foreach ($this->search['scmNo'] as $val) {
						$tmpWhere[] = 'sd.scmNo = ?';
						$this->db->bind_param_push($this->arrBind, 's', $val);
					}
					$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
					unset($tmpWhere);
				} else if ($this->search['scmNo'] > 1) {
					$this->arrWhere[] = 'sd.scmNo = ?';
					$this->db->bind_param_push($this->arrBind, 'i', $this->search['scmNo']);
				}
			} elseif ($this->search['scmFl'] == '0') {
				$this->arrWhere[] = 'sd.scmNo = 1';
			}
		}

		// 1회차 배송 제외 여부 (First Delivery Included)
		// 왜 이 옵션이 있는가? -> 1회차 회차 배송은 주문 자체 배송과 함께 처리하기 위함 (내부적으로 주문과 1회차 배송 상태는 싱크함)
		if ($this->search['fdi'] == '0') { // 1회차 배송 제외
			$this->arrWhere[] = 'sd.round != ?';
			$this->db->bind_param_push($this->arrBind, 'i', 1);
		}

		// 상품 검색
		if ($this->search['goodsNo']) {
			$this->arrWhere[] = 'sdg.goodsNo = ?';
			$this->db->bind_param_push($this->arrBind, 'i', $this->search['goodsNo']);
		} else if ($this->search['goodsText']) {
			$goodsKey = $this->search['goodsKey'];
			if ($goodsKey == 'sdg.goodsNm') {
				$this->arrWhere[] = 'sdg.goodsNm LIKE concat(\'%\',?,\'%\')';
			} else {
				$this->arrWhere[] = $goodsKey . ' = ?';
			}
			$this->db->bind_param_push($this->arrBind, 's', $this->search['goodsText']);
		}

		// 키워드 검색
		if ($this->search['key'] && $this->search['keyword']) {
			$keyword = $this->search['keyword'];
			if ($isMultiSearch == 'y') {
				$useNaverPay = $this->getNaverPayConfig('useYn') == 'y';
				foreach ($keyword as $keywordKey => $keywordVal) {
					if ($keywordVal) {
						if (in_array($this->search['key'][$keywordKey], ['m.memId', 'm.nickNm'])) $this->multiSearchMemberJoinFl = true;
						if (in_array($this->search['key'][$keywordKey], ['pu.purchaseNm'])) $this->multiSearchPurchaseJoinFl = true;
						if (in_array($this->search['key'][$keywordKey], ['sm.companyNm'])) $this->multiSearchScmJoinFl = true;
						$keywordVal = explode(',', preg_replace('{(?:\r\n|\r|\n)}', ",", $keywordVal));
						$_keyword = $_naverPayKeyword = $_naverPayVal = [];
						foreach ($keywordVal as $keywordVal2) {
							$keywordVal2 = trim($keywordVal2);
							if (count($_keyword) >= 10 || empty($keywordVal2)) continue;
							if (strpos($this->search['key'][$keywordKey], 'Phone') !== false) {
								$keywordVal2 = StringUtils::numberToPhone(str_replace('-', '', $keywordVal2), true);
							}
							$_keyword[] = '?';
							$this->db->bind_param_push($this->arrBind, 's', $keywordVal2);
							if ($this->search['key'][$keywordKey] == 'o.orderNo' && $useNaverPay) { //네이버페이 사용할경우 네이버페이 주문번호도 추가 검색
								$_naverPayKeyword[] = '?';
								$_naverPayVal[] = $keywordVal2;
							}
						}
						if ($_keyword) $keywordWhere[] = $this->search['key'][$keywordKey] . " in (" . implode(",", $_keyword) . ")";
						if ($useNaverPay && count($_naverPayVal) > 0) {
							$keywordWhere[] = "o.apiOrderNo in (" . implode(",", $_naverPayKeyword) . ")";
							foreach ($_naverPayVal as $_naverPayVal2) {
								$this->db->bind_param_push($this->arrBind, 's', $_naverPayVal2);
							}
						}
						unset($_keyword, $_naverPayKeyword, $_naverPayVal);
					}
				}
				if ($keywordWhere) $this->arrWhere[] = '(' . implode(' OR ', $keywordWhere) . ')';
			} else {
				if (is_array($this->search['key'])) $this->search['key'] = $this->search['key'][0];
				if (is_array($this->search['keyword'])) $this->search['keyword'] = $this->search['keyword'][0];
				if ($this->search['key'] == 'all') {
					$tmpWhere = array_keys($this->search['combineSearch']);
					if ($this->getNaverPayConfig('useYn') == 'y') {    //네이버페이 사용할경우 네이버페이 주문번호도 추가 검색
						$tmpWhere[] = 'o.apiOrderNo';
					}
					array_shift($tmpWhere);
					$arrWhereAll = [];
					foreach ($tmpWhere as $keyNm) {
						// 전화번호인 경우 -(하이픈)이 없어도 검색되도록 처리
						if (strpos($keyNm, 'Phone') !== false) {
							$keyword = str_replace('-', '', $keyword);
						} else {
							$keyword = $this->search['keyword'];
						}
						$searchType = $this->search['searchKind'];
						if ($searchType == 'fullLikeSearch') {
							if (strpos($keyNm, 'Phone') !== false) {
								$arrWhereAll[] = '(REPLACE(' . $keyNm . ', "-", "") LIKE concat(\'%\',?,\'%\'))';
							} else {
								$arrWhereAll[] = '(' . $keyNm . ' LIKE concat(\'%\',?,\'%\'))';
							}
						} else if ($searchType == 'equalSearch') {
							if (strpos($keyNm, 'Phone') !== false) {
								$arrWhereAll[] = '(' . $keyNm . ' = ? )';
							} else {
								$arrWhereAll[] = '(REPLACE' . $keyNm . ', "-", "") = ? )';
							}
						} else if ($searchType == 'endLikeSearch') {
							$arrWhereAll[] = '(' . $keyNm . ' LIKE concat(?,\'%\'))';
						} else {
							$arrWhereAll[] = '(' . $keyNm . ' LIKE concat(\'%\',?,\'%\'))';
						}
						$this->db->bind_param_push($this->arrBind, 's', $keyword);
					}
					$this->arrWhere[] = '(' . implode(' OR ', $arrWhereAll) . ')';
					unset($tmpWhere);
				} else {
					if ($this->search['key'] == 'o.orderNo') {    //네이버페이 사용중이고 주문번호 단일 검색일 경우 주문번호는 equal 검색
						if ($this->getNaverPayConfig('useYn') == 'y') {
							$this->arrWhere[] = '(' . $this->search['key'] . ' = ? OR apiOrderNo = ? )';
							$this->db->bind_param_push($this->arrBind, 's', $keyword);
						} else {
							$this->arrWhere[] = $this->search['key'] . ' = ?';
						}
					} else {
						$searchType = $this->search['searchKind'];
						if ($searchType == 'fullLikeSearch') {
							if (strpos($this->search['key'], 'Phone') !== false) {
								$this->arrWhere[] = '(REPLACE(' . $this->search['key'] . ', "-", "") LIKE concat(\'%\',?,\'%\'))';
							} else {
								$this->arrWhere[] = '(' . $this->search['key'] . ' LIKE concat(\'%\',?,\'%\'))';
							}
						} else if ($searchType == 'equalSearch') {
							if (strpos($this->search['key'], 'Phone') !== false) {
								$this->arrWhere[] = '(REPLACE(' . $this->search['key'] . ', "-", "") = ?)';
							} else {
								$this->arrWhere[] = '(' . $this->search['key'] . ' = ?)';
							}
						} else if ($searchType == 'endLikeSearch') {
							$this->arrWhere[] = '(' . $this->search['key'] . ' LIKE concat(?,\'%\'))';
						} else {
							$this->arrWhere[] = '(' . $this->search['key'] . ' LIKE concat(\'%\',?,\'%\'))';
						}
					}

					// 전화번호인 경우 -(하이픈)이 없어도 검색되도록 처리
					if (strpos($this->search['key'], 'Phone') !== false) {
						$keyword = str_replace('-', '', $keyword);
					} else {
						$keyword = $this->search['keyword'];
					}
					$this->db->bind_param_push($this->arrBind, 's', $keyword);
				}
			}
		}

		// 주문유형
		if ($this->search['orderTypeFl'][0]) {
			$orderTypeMobileAll = false; // 모바일 전체 검색 여부(WEB / APP)
			foreach ($this->search['orderTypeFl'] as $val) {
				$this->checked['orderTypeFl'][$val] = 'checked="checked"';

				// 모바일 (WEB / APP) 주문유형 검색 추가
				if (in_array('mobile', $this->search['orderTypeFl'])) {
					$orderTypeMobileAll = true;
				}

				if ($orderTypeMobileAll === false) {
					switch ($val) {
						case 'mobile-web':
							$val = 'mobile';
							$this->arrWhere[] = 'o.appOs  = ""';
							break;
						case 'mobile-app':
							$val = 'mobile';
							$this->arrWhere[] = '(o.appOs  != "" OR o.pushCode != "")';
							break;
					}
				}
				$tmpWhere[] = 'o.orderTypeFl = ?';
				$this->db->bind_param_push($this->arrBind, 's', $val);
			}
			$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			unset($tmpWhere);
		} else {
			$this->checked['orderTypeFl'][''] = 'checked="checked"';
		}

		// 주문채널
		if ($this->search['orderChannelFl'][0]) {
			foreach ($this->search['orderChannelFl'] as $val) {
				if ($val == 'paycoShopping') {
					$tmpWhere[] = "o.trackingKey <> ''";
				} else {
					$tmpWhere[] = 'o.orderChannelFl = ?';
					$this->db->bind_param_push($this->arrBind, 's', $val);
				}
				$this->checked['orderChannelFl'][$val] = 'checked="checked"';
			}
			$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			unset($tmpWhere);
		} else {
			$this->checked['orderChannelFl'][''] = 'checked="checked"';
		}

		// 주문상태
		if ($this->search['orderStatus'][0]) {
			foreach ($this->search['orderStatus'] as $val) {
				// 주문번호별/상품주문번호별 검색조건중 주문상태의 여부에 따라 검색설정 저장이 오작동하는 이슈가 있어 프론트에는 노출되지 않지만 hidden필드로 처리해서 임의로 작동되게 처리 함
				if ($this->search['view'] === 'orderGoods' || $this->search['statusMode'] !== null) {
					$tmpWhere[] = 'sd.deliveryStatus = ?';
					$this->db->bind_param_push($this->arrBind, 's', $val);
				}
				$this->checked['orderStatus'][$val] = 'checked="checked"';
			}
			if ($this->search['view'] === 'orderGoods' || $this->search['statusMode'] !== null) {
				$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			}
			unset($tmpWhere);
		} else {
			$this->checked['orderStatus'][''] = 'checked="checked"';
		}

		// 차지백 서비스건만 검색
		if ($this->search['pgChargeBack']) {
			$this->arrWhere[] = ' o.pgChargeBack=? ';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['pgChargeBack']);
			$this->checked['pgChargeBack'][$this->search['pgChargeBack']] = 'checked="checked"';
		}

		// 처리일자 검색
		if ($this->search['dateSearchFl'] == 'y' && $this->search['treatDateFl'] && isset($searchPeriod) && $this->search['treatDate'][0] && $this->search['treatDate'][1]) {
			switch (substr($this->search['treatDateFl'], -2)) {
				case '.b':
				case '.e':
				case '.r':
					$this->arrWhere[] = ' oh.handleMode=? ';
					$this->db->bind_param_push($this->arrBind, 's', substr($this->search['treatDateFl'], -1));
					break;
			}
			$dateField = str_replace(['Dt.r', 'Dt.b', 'Dt.e'], 'Dt', $this->search['treatDateFl']);

			$this->arrWhere[] = $dateField . ' BETWEEN ? AND ?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['treatDate'][0] . ' ' . $this->search['treatTime'][0]);
			$this->db->bind_param_push($this->arrBind, 's', $this->search['treatDate'][1] . ' ' . $this->search['treatTime'][1]);
		}


		// 결제 방법
		if ($this->search['settleKind'][0]) {
			foreach ($this->search['settleKind'] as $val) {
				if ($val == self::SETTLE_KIND_DEPOSIT) {
					$tmpWhere[] = 'o.useDeposit > 0';
				} elseif ($val == self::SETTLE_KIND_MILEAGE) {
					$tmpWhere[] = 'o.useMileage > 0';
				} else {
					$tmpWhere[] = 'o.settleKind = ?';
					$this->db->bind_param_push($this->arrBind, 's', $val);
				}
				$this->checked['settleKind'][$val] = 'checked="checked"';
			}
			if ($val == 'gr') { // 기타결제 검색 시 나중에결제 추가
				$tmpWhere[] = 'o.settleKind = "pl"';
			}
			$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			unset($tmpWhere);
		} else {
			$this->checked['settleKind'][''] = 'checked="checked"';
		}

		// 결제금액 검색
		if ($this->search['settlePrice'][1]) {
			//            $this->arrWhere[] = '(((og.goodsPrice + og.optionPrice + og.optionTextPrice ) * og.goodsCnt) + og.addGoodsPrice - og.memberDcPrice - og.memberOverlapDcPrice - og.couponGoodsDcPrice - og.divisionUseDeposit - og.divisionUseMileage - og.divisionGoodsDeliveryUseDeposit - og.divisionGoodsDeliveryUseMileage + od.deliveryCharge) BETWEEN ? AND ?';
			$this->arrWhere[] = '(o.settlePrice BETWEEN ? AND ?)';
			$this->db->bind_param_push($this->arrBind, 'i', $this->search['settlePrice'][0]);
			$this->db->bind_param_push($this->arrBind, 'i', $this->search['settlePrice'][1]);
		}

		// 회원여부 및 그룹별 검색
		if ($this->search['memFl']) {
			if ($this->search['memFl'] == 'y') {
				// 회원그룹선택
				if (is_array($this->search['memberGroupNo'])) {
					foreach ($this->search['memberGroupNo'] as $val) {
						$tmpWhere[] = 'm.groupSno = ?';
						$this->db->bind_param_push($this->arrBind, 's', $val);
					}
					$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
					unset($tmpWhere);
				} else if ($this->search['memberGroupNo'] > 1) {
					$this->arrWhere[] = 'm.groupSno = ?';
					$this->db->bind_param_push($this->arrBind, 'i', $this->search['memberGroupNo']);
				}

				// 회원만
				$this->arrWhere[] = 'o.memNo > 0';
			} elseif ($this->search['memFl'] == 'n') {
				$this->arrWhere[] = 'o.memNo = 0';
			}
		}

		// 첫주문 검색
		if ($this->search['firstSaleFl'] == 'y') {
			$this->arrWhere[] = 'o.firstSaleFl = ?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['firstSaleFl']);
		}

		// 탈퇴회원 주문 제외하고 검색
		if ($this->search['withdrawnMembersOrderFl'] === 'n') {
			$this->arrWhere[] = 'mho.orderNo is null';
		}

		// 영수증 검색
		if ($this->search['receiptFl']) {
			$this->arrWhere[] = 'o.receiptFl = ?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['receiptFl']);
		}

		// 배송정보 검색 (사은품 포함)
		if ($this->search['withGiftFl'] == 'y') {
			$this->arrWhere[] = '(SELECT COUNT(sno) FROM ' . DB_ORDER_GIFT . ' WHERE orderNo = o.orderNo) > 0';
		}

		// 배송정보 검색 (배송메시지 입력)
		if ($this->search['withMemoFl'] == 'y') {
			$this->arrWhere[] = 'oi.orderMemo != \'\'';
		}

		// 상품º주문번호별 메모 (관리자 메모 입력)
		if ($this->search['withAdminMemoFl'] == 'y') {
			if ($this->search['orderMemoCd']) {
				$this->arrWhere[] = 'aogm.memoCd=? AND aogm.delFl = \'n\'';
				$this->db->bind_param_push($this->arrBind, 's', $this->search['orderMemoCd']);
			} else {
				$this->arrWhere[] = 'aogm.orderNo != \'\' AND aogm.delFl = \'n\'';
			}
			//$this->arrWhere[] = 'o.adminMemo != \'\'';
		}

		// 배송정보 검색 (묶음배송)
		if ($this->search['withPacket'] == 'y') {
			$this->arrWhere[] = 'oi.packetCode != \'\'';
		}

		// 배송지연일
		if ($this->search['underDeliveryDay'] > 0) {
			$includeStatusCode = ['p', 'g'];
			$arrWhereOrderStatusArray = $this->getIncludeOrderStatus($this->orderStatus, $includeStatusCode);
			$this->arrWhere[] = 'sd.deliveryStatus IN (\'' . implode('\',\'', array_keys($arrWhereOrderStatusArray)) . '\') AND sd.estimatedDeliveryDt < ?';
			$this->db->bind_param_push($this->arrBind, 's', date('Y-m-d', strtotime('-' . $this->search['underDeliveryDay'] . ' day')) . ' 00:00:00');
			unset($arrWhereOrderStatusArray);

			// 주문상태 체크하기
			unset($this->checked['orderStatus']);
			$this->checked['orderStatus']['p1'] =
				$this->checked['orderStatus']['g1'] =
				$this->checked['orderStatus']['g2'] =
				$this->checked['orderStatus']['g3'] =
				$this->checked['orderStatus']['g4'] =
				'checked="checked"';

			//TODO 추후 주문단위 리스트 생기면 작업?
			if ($this->search['underDeliveryOrder'] == 'y') {
			}
		}

		// 송장번호 검색
		if ($this->search['invoiceCompanySno'] > 0) {
			$this->arrWhere[] = 'sd.invoiceCompanySno=?';
			$this->db->bind_param_push($this->arrBind, 's', $this->search['invoiceCompanySno']);
		}

		// 송장번호 유무 체크
		if ($this->search['invoiceNoFl'] === 'y') {
			$this->arrWhere[] = 'sd.invoiceNo<>\'\'';
		} elseif ($this->search['invoiceNoFl'] === 'n') {
			$this->arrWhere[] = 'sd.invoiceNo=\'\'';
		}

		if ($this->search['couponAllFl'] === 'y') {
			//쿠폰사용 주문 전체 검색
			$this->arrWhere[] = '(o.totalCouponGoodsDcPrice > 0 OR o.totalCouponOrderDcPrice > 0 OR o.totalCouponDeliveryDcPrice > 0)';
		} else {
			// 쿠폰 검색
			if ($this->search['couponNo'] > 0) {
				$this->arrWhere[] = 'mc.couponNo=?';
				$this->db->bind_param_push($this->arrBind, 's', $this->search['couponNo']);
			}
		}


		// 배송 검색
		if ($this->search['invoiceFl']) {
			if ($this->search['invoiceFl'] == 'y') $this->arrWhere[] = 'sd.invoiceNo !=""';
			else if ($this->search['invoiceFl'] == 'n') $this->arrWhere[] = 'sd.invoiceNo =""';
			else $this->arrWhere[] = 'TRIM(oi.receiverCellPhone) NOT REGEXP \'^([0-9]{3,4})-?([0-9]{3,4})-?([0-9]{4})$\'';

			$this->checked['invoiceFl'][$this->search['invoiceFl']] = 'checked="checked"';
		} else {
			$this->checked['invoiceFl'][''] = 'checked="checked"';
		}

		// 브랜드 검색
		if (($this->search['brandCd'] && $this->search['brandCdNm']) || $this->search['brand']) {
			if (!$this->search['brandCd'] && $this->search['brand'])
			$this->search['brandCd'] = $this->search['brand'];
			$this->arrWhere[] = 'g.brandCd = ?';
			$this->db->bind_param_push($this->arrBind, $fieldTypeGoods['brandCd'], $this->search['brandCd']);
		} else {
			$this->search['brandCd'] = '';
		}

		//브랜드 미지정
		if ($this->search['brandNoneFl']) {
			$this->arrWhere[] = 'g.brandCd  = ""';
		}

		if (empty($this->arrBind)) {
			$this->arrBind = null;
		}
	}

	/**
	 * 회차배송 리스트에서 배송 상태 일괄 변경 처리 - 회차배송별
	 *
	 * @param string $arrData 변경 정보
	 */
	public function changeScheduledDeliveryStatus($arrData)
	{
		$tmpData = [];
		$tmpData['changeStatus'] = $arrData['changeStatus'];
		foreach ($arrData['statusCheck'] as $statusMode => $val) {
			$tmpData['statusCheck'][$statusMode] = $val;
		}

		$this->requestDeliveryStatusChange($tmpData);
	}

	/**
     * 회차배송의 배송 상태 일괄 변경 처리
     *
     * @param $arrData
     *
     * @throws Exception
     */
	public function requestDeliveryStatusChange($arrData)
	{
		// 운영자 기능권한 처리 (주문 상태 변경 권한) - 관리자페이지에서만
		$thisCallController = \App::getInstance('ControllerNameResolver')->getControllerRootDirectory();
		if ($thisCallController == 'admin' && Session::get('manager.functionAuthState') == 'check' && Session::get('manager.functionAuth.orderState') != 'y') {
			throw new Exception(__('권한이 없습니다. 권한은 대표운영자에게 문의하시기 바랍니다.'));
		}

		// 주문 정보 확인
		if (empty($arrData['statusCheck']) === true) {
			throw new Exception(__('[리스트] 회차배송 정보가 존재하지 않습니다.'));
		}

		// 체크된 상품의 값을 회차배송번호와 회차배송상품번호로 분리
		foreach ($arrData['statusCheck'] as $statusMode => $sVal) {
			foreach ($sVal as $val) {
				$tmpArr = explode(INT_DIVISION, $val);
				if (isset($tmpArr[1]) === true) {
					$statusCode[$statusMode][$tmpArr[0]][] = $tmpArr[1];
				} else {
					$statusCode[$statusMode][$tmpArr[0]][] = null;
				}
			}
		}

		// 유효성 검사 후 처리
		if (empty($statusCode) === false) {
			$allCount = 0;
			$checkModifiedCount = 0;
			foreach ($statusCode as $statusMode => $scheduledDeliverySnos) {
				foreach ($scheduledDeliverySnos as $scheduledDeliverySno => $scheduledDeliveryGoodsSnos) {
					// 회차배송상품별이 아닌 회차배송별로 일괄 처리가 필요한 경우 
					$scheduledDeliveryGoodsSnos = ArrayUtils::removeEmpty($scheduledDeliveryGoodsSnos);
					if (empty($scheduledDeliveryGoodsSnos) === true) {
						$scheduledDeliveryGoodsSnos = null;
					}

					// 주문 상태, 실패일경우 현재 상태 수정 처리, 및 취소에서 주문 상태 처리시에 현재 상태 수정 처리 가능하게
					if ($statusMode == 'p') {
						$bundleFl = true;
					} else {
						$bundleFl = false;
					}

					// naverpay 일 경우 왜 나뉘어 있는 지 모르겠음 호출하는 함수는 같은 함수임
					// 기존 orderStatus	변경 코드와 구조를 유지하기 위해 코드 최적화는 하지 않고 구조를 유지함
					if ($this->getChannel() == 'naverpay') {
						$this->updateDeliveryStatusPreprocess($scheduledDeliverySno, $statusMode, $arrData['changeStatus'], '리스트에서', $bundleFl);
					} else {
						$changeStatusMode = substr($arrData['changeStatus'], 0, 1);

						if (!in_array($changeStatusMode, ['e', 'z', 'b', 'c'])) {
							$this->updateDeliveryStatusPreprocess($scheduledDeliverySno, $statusMode, $arrData['changeStatus'], '리스트에서', $bundleFl, null, $arrData['useVisit']);
						} else {
							$checkModifiedCount++;
						}
					}
					$allCount++;
				}
			}

			if ($checkModifiedCount > 0) {
				//@todo 변경할 수 없는 상태에 대한 처리
				throw new Exception(sprintf(__('총 %d개 중 %d를 처리 완료하였습니다.'), $allCount, $checkModifiedCount));
			}
		}
	}

	public function updateDeliveryStatusPreprocess($scheduledDeliverySno,  $statusMode, $changeStatus, $reason = false, $bundleFl = false, $mode = null, $useVisit = null, $autoProcess = false, $userHandleFl = null)
	{
		if (($result = $this->updateDeliveryStatusUnconditionalPreprocess($scheduledDeliverySno, $statusMode, $changeStatus, $reason, $bundleFl, $mode, $useVisit, $autoProcess, $userHandleFl)) !== true) {
			if ($result['errorMsg']) {
				$message = sprintf(__('"배송상태변경 실패하였습니다.(%s")'), $result['errorMsg']);
			} else {
				$message = sprintf(__('"%s"로 변경이 불가능한 배송상태 입니다.'), $this->getOrderStatusAdmin($changeStatus));
			}
			$message .= __('<br>자세한 내용은 매뉴얼을 참고하시기 바랍니다.');
			\Logger::channel('naverPay')->info('updateDeliveryStatusUnconditionalPreprocess 실패', [__METHOD__, $statusMode, $changeStatus]);

			throw new LayerNotReloadException($message);
		}
	}

	public function updateDeliveryStatusUnconditionalPreprocess($scheduledDeliverySno, $statusMode, $changeStatus, $reason = false, $bundleFl = false, $mode = null, $useVisit = null, $autoProcess = false, $userHandleFl = false)
	{
		$scheduledDelivery = $this->fetchScheduledDelivery($scheduledDeliverySno);
		$orderNo = $scheduledDelivery['orderNo'];
		$orderData = $this->getOrderData($orderNo);
		$channel = $orderData['orderChannelFl'];
		$changeStatusMode = substr($changeStatus, 0, 1);

		// 기존 orderStatus 변경 코드와 구조를 유지하기 위해 코드 최적화는 하지 않고 구조를 유지함
		// 각 주문에 대한 상태 변경 기본 조건 체크 및 같은 주문상태는 변경처리 안함
		$changeCheck = false;
		$changeStatusCheck = true;

		if (in_array($changeStatusMode, $this->deliveryStatusStandardCode[$statusMode])) {
			if ($scheduledDelivery['deliveryStatus'] == $changeStatus) {
				// 주문상태가 같은 경우 변경 안됨
				$changeStatusCheck = false;
			}

			// 변경 가능한 경우 bundleData로 담는다
			if ($changeStatusCheck == true) {
				$bundleData['sno'][] = $scheduledDeliverySno;
				$bundleData['deliveryStatus'][] = $scheduledDelivery['deliveryStatus'];
				$changeCheck = true;
			}
		}

		// $this->statusStandardCode 정의한 변경 가능한 주문상태인 경우만 처리
		if ($changeCheck === true || $channel == 'naverpay') {
			if (empty($reason) === true) {
				$reason = __('회차배송 리스트에서');
			}
			$bundleData['changeStatus'] = $changeStatus;
			$bundleData['reason'] = $reason . ' ' . $this->getOrderStatusAdmin($changeStatus) . __(' 처리');
			
			$functionName = 'deliveryStatusChangeCode' . strtoupper($changeStatusMode);

			// 주문 상태에 따른 함수 실행
			$this->$functionName($scheduledDeliverySno, $bundleData);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 회차배송 하나를 가져옴
	 * @param $scheduledDeliverySno 회차배송번호
	 */
	public function fetchScheduledDelivery($scheduledDeliverySno)
	{
		if (empty($scheduledDeliverySno) === true) {
			return false;
		}

		$tmpField[0] = DBTableField::setTableField('tableScheduledDelivery', null, null, 'sd');
		$tmpKey = array_keys($tmpField);
		$arrField = [];
		foreach ($tmpKey as $key) {
			$arrField = array_merge($arrField, $tmpField[$key]);
		}
		unset($tmpField, $tmpKey);

		$arrWhere[] = 'sd.sno = ?';

		$this->db->bind_param_push($arrBind, 's', $scheduledDeliverySno);

		// 쿼리문 생성 및 데이타 호출
		$this->db->strField = implode(', ', $arrField);
		$this->db->strWhere = implode(' AND ', gd_isset($arrWhere));
		$query = $this->db->query_complete();
		$strSQL = 'SELECT ' . array_shift($query) . ' FROM ms_scheduledDelivery AS sd ' . implode(' ', $query);

		$getData = $this->db->query_fetch($strSQL, $arrBind, false);

		unset($arrWhere, $arrBind);
		
		return $getData;
	}

	/**
	 * 회차배송 하나를 주문번호와 주문상품번호로 가져옴
	 * @param $scheduledDeliverySno 회차배송번호
	 */
	public function fetchScheduledDeliveryByParentOrderGoods($orderNo, $orderGoodsSno)
	{
		if (empty($orderNo) === true || empty($orderGoodsSno) === true) {
			return false;
		}

		$bindParam = [];
		$fields = DBTableField::setTableField('tableScheduledDelivery');
		$this->db->strField = implode(', ', $fields);
		$this->db->strWhere = 'orderNo = ? AND orderGoodsSno = ?';

		$this->db->bind_param_push($bindParam, 's', $orderNo);
		$this->db->bind_param_push($bindParam, 'i', $orderGoodsSno);

		$query = $this->db->query_complete();
		$strSQL = sprintf('SELECT %s FROM ms_scheduledDelivery %s', array_shift($query), implode(' ', $query));

		$result = $this->db->query_fetch($strSQL, $bindParam, true);

		return $result;
	}

	public function saveScheduledDeliveryOrderInvoice($arrData)
	{
		$scmNo = \Session::get('manager.scmNo');
		$updateData = [
			'searchView' => $arrData['searchView'],
			'fromPageMode' => $arrData['fromPageMode'],
		];

		$tmpScheduledDeliverySnoArr = $scheduledDeliverySnos = [];
		if (count($arrData['statusCheck']) > 0) {
			foreach ($arrData['statusCheck'] as $statusKey => $valueArr) {
				if (count($valueArr) > 0) {
					foreach ($valueArr as $key => $value) {
						if ($arrData['invoiceIndividualUnsetFl'][$value] && $arrData['invoiceIndividualUnsetFl'][$value] === $value) {
							$tmpScheduledDeliverySnoArr[] = $value;
						}
					}
				}
			}
		}

		$scheduledDeliverySnos = array_values(array_unique(array_filter($tmpScheduledDeliverySnoArr)));
		if (count($scheduledDeliverySnos) > 0) {
			foreach ($scheduledDeliverySnos as $oKey => $scheduledDeliverySno) {
				$scheduledDelivery = $this->fetchScheduledDelivery($scheduledDeliverySno);

				if (Manager::isProvider() && $scmNo != Session::get('manager.scmNo')) {
					continue;
				}

				$statusMode = substr($scheduledDelivery['deliveryStatus'], 0, 1);

				if ($statusMode !== 'g') {
					continue;
				}
				if ((int)$scmNo !== (int)DEFAULT_CODE_SCMNO && (int)$scmNo !== (int)$scheduledDelivery['scmNo']) {
					continue;
				}
				$updateData['statusCheck'][$statusMode][] = $scheduledDeliverySno;
				$updateData['invoiceCompanySno'][$statusMode][$scheduledDeliverySno] = $arrData['invoiceCompanySno'][$scheduledDeliverySno];
				$updateData['invoiceNo'][$statusMode][$scheduledDeliverySno] = $arrData['invoiceNo'][$scheduledDeliverySno];
			}
			if (count($updateData['statusCheck'][$statusMode]) > 0) {
				$this->saveScheduledDeliveryInvoice($updateData);
			}
		}
	}

	public function saveScheduledDeliveryInvoice($arrData)
	{
		// 체크된 상품별 송장 처리 데이터 처리
		$scheduledDeliveryUpdateData = [];
		foreach ($arrData['statusCheck'] as $statusMode => $data) {
			foreach ($data as $aData) {
				$explodedData = explode(INT_DIVISION, $aData);
				$scheduledDeliverySno = $explodedData[0];
				$scheduledDeliveryUpdateData['sno'][] = $scheduledDeliverySno;
				$scheduledDeliveryUpdateData['invoiceCompanySno'][] = $arrData['invoiceCompanySno'][$statusMode][$scheduledDeliverySno];
				$scheduledDeliveryUpdateData['invoiceNo'][] = StringUtils::xssClean($arrData['invoiceNo'][$statusMode][$scheduledDeliverySno]);
				$scheduledDeliveryUpdateData['invoiceDt'][] = date('Y-m-d H:i:s');

				$arrDataKey = ['sno' => $scheduledDeliverySno];

				if (empty($scheduledDeliveryUpdateData) === false) {
					$compareField = array_keys($scheduledDeliveryUpdateData);
					$scheduledDelivery = $this->fetchScheduledDelivery($scheduledDeliverySno);

					//get_compare_array_data 함수의 첫번째 인자가 바깥에 한 번 더 배열로 감싸져 있어야 해서 배열로 감싸줌
					$wrappedScheduledDelivery = [$scheduledDelivery];

					$compareScheduledDelivery = $this->db->get_compare_array_data($wrappedScheduledDelivery, gd_isset($scheduledDeliveryUpdateData), false, $compareField);
					$this->db->set_compare_process('ms_scheduledDelivery', gd_isset($scheduledDeliveryUpdateData), $arrDataKey, $compareScheduledDelivery, $compareField);
				}
			}
		}
	}

	/**
	 * 관리자 주문 리스트 엑셀
	 * 반품/교환/환불 정보까지 한번에 가져올 수 있게 되어있다.
	 * !!!: Bundle의 getOrderListForAdminExcel 함수를 오버라이딩하여 사용 by Conan Kim (kmakugo@gmail.com)
	 * ms_scheduledDelivery, ms_scheduledDeliveryGoods 테이블을 조인하여 데이터를 가져온다.
	 *
	 * @param string $searchData 검색 데이타
	 * @param string $searchPeriod 기본 조회 기간
	 *
	 * @return array 주문 리스트 정보
	 */
	public function fetchOrderListForAdminExcel($searchData, $searchPeriod, $isUserHandle = false, $orderType = 'goods', $excelField, $page, $pageLimit)
	{
		unset($this->arrWhere);
		unset($this->arrBind);
		//$excelField  / $page / $pageLimit 해당 정보가 없을경우 튜닝한 업체이므로 기존형태로 반환해줘야함
		// --- 검색 설정
		$this->_setSearch($searchData, $searchPeriod, $isUserHandle);

		if ($searchData['statusCheck'] && is_array($searchData['statusCheck'])) {
			foreach ($searchData['statusCheck'] as $key => $val) {
				foreach ($val as $k => $v) {
					$_tmp = explode(INT_DIVISION, $v);
					if ($orderType == 'goods' && $searchData['view'] == 'order') unset($_tmp[1]);
					if ($_tmp[1]) {
						$tmpWhere[] = "(og.orderNo = ? AND og.sno = ?)";
						$this->db->bind_param_push($this->arrBind, 's', $_tmp[0]);
						$this->db->bind_param_push($this->arrBind, 's', $_tmp[1]);
					} else {
						$tmpWhere[] = "(og.orderNo = ?)";
						$this->db->bind_param_push($this->arrBind, 's', $_tmp[0]);
					}
				}
			}

			$this->arrWhere[] = '(' . implode(' OR ', $tmpWhere) . ')';
			unset($tmpWhere);
		}

		// 주문상태 정렬 예외 케이스 처리
		if ($searchData['sort'] == 'og.orderStatus asc') {
			$searchData['sort'] = 'case LEFT(og.orderStatus, 1) when \'o\' then \'1\' when \'p\' then \'2\' when \'g\' then \'3\' when \'d\' then \'4\' when \'s\' then \'5\' when \'e\' then \'6\' when \'b\' then \'7\' when \'r\' then \'8\' when \'c\' then \'9\' when \'f\' then \'10\' else \'11\' end';
		} elseif ($searchData['sort'] == 'og.orderStatus desc') {
			$searchData['sort'] = 'case LEFT(og.orderStatus, 1) when \'f\' then \'1\' when \'c\' then \'2\' when \'r\' then \'3\' when \'b\' then \'4\' when \'e\' then \'5\' when \'s\' then \'6\' when \'d\' then \'7\' when \'g\' then \'8\' when \'p\' then \'9\' when \'o\' then \'10\' else \'11\' end';
		}

		// 정렬 설정
		if ($orderType === 'goods') {
			$orderSort = gd_isset($searchData['sort'], $this->orderGoodsMultiShippingOrderBy);
		} else {
			$orderSort = gd_isset($searchData['sort'], $this->orderGoodsOrderBy);
		}

		if ($orderType === 'goods') {
			if (!preg_match("/orderCd/", $orderSort)) {
				$orderSort = $orderSort . ", og.orderCd asc";
			}
		}

		// 사용 필드
		$arrInclude = [
			'o.orderNo',
			'o.orderChannelFl',
			'o.apiOrderNo',
			'o.memNo',
			'o.orderChannelFl',
			'o.orderGoodsNm',
			'o.orderGoodsCnt',
			'o.settlePrice as totalSettlePrice',
			'o.totalDeliveryCharge',
			'o.useDeposit as totalUseDeposit',
			'o.useMileage as totalUseMileage',
			'(o.totalMemberDcPrice + o.totalMemberDeliveryDcPrice) AS totalMemberDcPrice',
			'o.totalGoodsDcPrice',
			'(o.totalCouponGoodsDcPrice + o.totalCouponOrderDcPrice + o.totalCouponDeliveryDcPrice)as totalCouponDcPrice',
			'totalCouponOrderDcPrice',
			'totalCouponDeliveryDcPrice',
			'o.totalMileage',
			'o.totalGoodsMileage',
			'o.totalMemberMileage',
			'(o.totalCouponGoodsMileage+o.totalCouponOrderMileage) as totalCouponMileage',
			'o.settleKind',
			'o.bankAccount',
			'o.bankSender',
			'o.receiptFl',
			'o.pgResultCode',
			'o.pgTid',
			'o.pgAppNo',
			'o.paymentDt',
			'o.addField',
			'o.mallSno',
			'o.orderGoodsNmStandard',
			'o.overseasSettlePrice',
			'o.currencyPolicy',
			'o.exchangeRatePolicy',
			'o.totalEnuriDcPrice',
			'(o.realTaxSupplyPrice + o.realTaxVatPrice + o.realTaxFreePrice) AS totalRealSettlePrice',
			'o.checkoutData',
			'o.trackingKey',
			'o.fintechData',
			'o.checkoutData',
			'o.orderTypeFl',
			'o.appOs',
			'o.pushCode',
			'o.memberPolicy',
			'o.totalMyappDcPrice',
			'o.pgSettleNm',
			'oi.regDt as orderDt',
			'oi.orderName',
			'oi.orderEmail',
			'oi.orderPhone',
			'oi.orderCellPhone',
			'oi.receiverName',
			'oi.receiverPhone',
			'oi.receiverCellPhone',
			'oi.receiverUseSafeNumberFl',
			'oi.receiverSafeNumber',
			'oi.receiverSafeNumberDt',
			'oi.receiverZonecode',
			'oi.receiverZipcode',
			'oi.receiverAddress',
			'oi.receiverAddressSub',
			'oi.receiverCity',
			'oi.receiverState',
			'oi.receiverCountryCode',
			'oi.orderMemo',
			'oi.packetCode',
			'oi.orderInfoCd',
			'oi.visitName',
			'oi.visitPhone',
			'oi.visitMemo',
			'og.orderDeliverySno AS orderDeliverySno ',
			'og.scmNo AS scmNo ',
			'og.apiOrderGoodsNo AS apiOrderGoodsNo ',
			'og.sno AS orderGoodsSno ',
			'og.orderCd AS orderCd ',
			'og.orderStatus AS orderStatus ',
			'og.goodsNo AS goodsNo ',
			'og.goodsCd AS goodsCd ',
			'og.goodsModelNo AS goodsModelNo ',
			'og.goodsNm AS goodsNm ',
			'og.optionInfo AS optionInfo ',
			'og.goodsCnt AS goodsCnt ',
			'og.goodsWeight AS goodsWeight ',
			'og.goodsVolume AS goodsVolume ',
			'og.cateCd AS cateCd ',
			'og.brandCd AS brandCd ',
			'og.makerNm AS makerNm ',
			'og.originNm AS originNm ',
			'og.addGoodsCnt AS addGoodsCnt ',
			'og.optionTextInfo AS optionTextInfo ',
			'og.goodsTaxInfo AS goodsTaxInfo ',
			'og.goodsPrice AS goodsPrice ',
			'og.fixedPrice AS fixedPrice ',
			'og.costPrice AS costPrice ',
			'og.commission AS commission ',
			'og.optionPrice AS optionPrice ',
			'og.optionCostPrice AS optionCostPrice ',
			'og.optionTextPrice AS optionTextPrice ',
			'og.invoiceCompanySno AS invoiceCompanySno ',
			'og.invoiceNo AS invoiceNo ',
			'og.deliveryCompleteDt AS deliveryCompleteDt ',
			'og.visitAddress AS visitAddress ',
			'og.goodsDeliveryCollectFl',
			'og.deliveryMethodFl',
			'og.goodsNmStandard',
			'og.goodsMileage',
			'og.memberMileage',
			'og.couponGoodsMileage',
			'og.divisionUseDeposit',
			'og.divisionUseMileage',
			'og.divisionGoodsDeliveryUseDeposit',
			'og.divisionGoodsDeliveryUseMileage',
			'og.divisionCouponOrderDcPrice',
			'og.goodsDcPrice',
			'(og.memberDcPrice+og.memberOverlapDcPrice+od.divisionMemberDeliveryDcPrice) as memberDcPrice',
			'og.memberDcPrice as orgMemberDcPrice',
			'og.memberOverlapDcPrice as orgMemberOverlapDcPrice',
			'og.goodsDiscountInfo',
			'og.myappDcPrice',
			'(og.couponGoodsDcPrice + og.divisionCouponOrderDcPrice) as couponGoodsDcPrice',
			'og.goodsTaxInfo AS addGoodsTaxInfo ',
			'og.commission AS addGoodsCommission ',
			'og.goodsPrice AS addGoodsPrice ',
			'og.timeSalePrice',
			'og.finishDt',
			'og.deliveryDt',
			'og.deliveryCompleteDt',
			'og.goodsType',
			'og.hscode',
			'og.checkoutData AS og_checkoutData',
			'og.enuri',
			'oh.handleReason',
			'oh.handleDetailReason',
			'oh.refundMethod',
			'oh.refundBankName',
			'oh.refundAccountNumber',
			'oh.refundDepositor',
			'oh.refundPrice',
			'oh.refundDeliveryCharge',
			'oh.refundDeliveryInsuranceFee',
			'oh.refundUseDeposit',
			'oh.refundUseMileage',
			'oh.refundDeliveryUseDeposit',
			'oh.refundDeliveryUseMileage',
			'oh.refundUseDepositCommission',
			'oh.refundUseMileageCommission',
			'oh.completeCashPrice',
			'oh.completePgPrice',
			'oh.completeCashPrice',
			'oh.completeDepositPrice',
			'oh.completeMileagePrice',
			'oh.refundCharge',
			'oh.refundUseDeposit',
			'oh.refundUseMileage',
			'oh.regDt as handleRegDt',
			'oh.handleDt',
			'od.deliveryCharge',
			'od.orderInfoSno',
			'od.deliveryPolicyCharge',
			'od.deliveryAreaCharge',
			'od.realTaxSupplyDeliveryCharge',
			'od.realTaxVatDeliveryCharge',
			'od.realTaxFreeDeliveryCharge',
			'od.divisionDeliveryUseMileage',
			'od.divisionDeliveryUseDeposit',
		];
		if ($searchData['statusMode'] === 'o') {
			// 입금대기리스트에서 '주문상품명' 을 입금대기 상태의 주문상품명만으로 노출시키기 위해 개수를 구함
			$arrInclude[] = 'SUM(IF(LEFT(og.orderStatus, 1)=\'o\', 1, 0)) AS noPay';
		}

		// join 문
		$join[] = ' LEFT JOIN ' . DB_ORDER . ' o ON o.orderNo = og.orderNo ';
		$join[] = ' LEFT JOIN ' . DB_ORDER_HANDLE . ' oh ON og.handleSno = oh.sno AND og.orderNo = oh.orderNo ';
		$join[] = ' LEFT JOIN ' . DB_ORDER_DELIVERY . ' od ON og.orderDeliverySno = od.sno ';
		$join[] = ' LEFT JOIN ' . DB_ORDER_INFO . ' oi ON (og.orderNo = oi.orderNo) 
                    AND (CASE WHEN od.orderInfoSno > 0 THEN od.orderInfoSno = oi.sno ELSE oi.orderInfoCd = 1 END)';
		$join[] = ' LEFT JOIN ' . DB_MEMBER_HACKOUT_ORDER . ' mho ON og.orderNo = mho.orderNo';


		// 회차배송 1회차 배송 수량을 알기 위해 회차 배송 테이블 조인
		if (in_array("firstRoundGoodsCnt", array_values($excelField))) {
			$arrIncludeScheduledDelivery = [
				'IF(sd.sno IS NULL, null, sd.estimatedDeliveryDt) as firstRoundEstimatedDeliveryDt',
				'IF(sd.sno IS NULL, null, IFNULL(sd.goodsCnt, 0)) as firstRoundGoodsCnt',
			];
			$arrInclude = array_merge($arrInclude, $arrIncludeScheduledDelivery);

			$join[] = ' LEFT JOIN (
									select a.sno, a.orderNo, b.orderGoodsSno orderGoodsSno, goodsCnt, estimatedDeliveryDt 
									FROM ms_scheduledDelivery a 
									JOIN ms_scheduledDeliveryGoods b on (a.sno = b.scheduledDeliverySno)
									WHERE round = 1
								) sd ON og.orderNo = sd.orderNo AND og.sno = sd.orderGoodsSno';

			unset($arrIncludeScheduledDelivery);
		}

		//매입처
		if ((($this->search['key'] == 'all' && empty($this->search['keyword']) === false)  || $this->search['key'] == 'pu.purchaseNm' || empty($excelField) === true || in_array("purchaseNm", array_values($excelField))) && gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true && gd_is_provider() === false) {
			$arrIncludePurchase = [
				'pu.purchaseNm'
			];

			$arrInclude = array_merge($arrInclude, $arrIncludePurchase);
			$join[] = ' LEFT JOIN ' . DB_PURCHASE . ' pu ON og.purchaseNo = pu.purchaseNo ';
			unset($arrIncludePurchase);
		}

		//공급사
		if (in_array("scmNm", array_values($excelField)) || in_array("scmNo", array_values($excelField)) || empty($excelField) === true || empty($searchData['scmFl']) === false || ($searchData['key'] == 'all' && $searchData['keyword'])) {
			$arrIncludeScm = [
				'sm.companyNm as scmNm'
			];

			$arrInclude = array_merge($arrInclude, $arrIncludeScm);
			$join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' sm ON og.scmNo = sm.scmNo ';
			unset($arrIncludeScm);
		}

		//회원
		if (in_array("memNo", array_values($excelField)) || in_array("memNm", array_values($excelField)) ||  in_array("groupNm", array_values($excelField)) || empty($excelField) === true || $searchData['memFl'] || ($searchData['key'] == 'all' && $searchData['keyword'])) {
			$arrIncludeMember = [
				'IF(m.memNo > 0, m.memNm, oi.orderName) AS memNm',
				'm.memId',
				'mg.groupNm',
			];

			$arrInclude = array_merge($arrInclude, $arrIncludeMember);
			$join[] = ' LEFT JOIN ' . DB_MEMBER . ' m ON o.memNo = m.memNo ';
			$join[] = ' LEFT OUTER JOIN ' . DB_MEMBER_GROUP . ' mg ON m.groupSno = mg.sno ';
			unset($arrIncludeMember);
		}

		//사은품
		if (in_array("oi.presentSno", array_values($excelField)) || empty($excelField) === true || in_array("ogi.giftNo", array_values($excelField))) {
			$arrIncludeGift = [
				'GROUP_CONCAT(ogi.presentSno SEPARATOR "/") AS presentSno ',
				'GROUP_CONCAT(ogi.giftNo SEPARATOR "/") AS giftNo '
			];

			$arrInclude = array_merge($arrInclude, $arrIncludeGift);

			$join[] = ' LEFT JOIN ' . DB_ORDER_GIFT . ' ogi ON ogi.orderNo = o.orderNo ';
			unset($arrIncludeGift);
		}

		//상품 브랜드 코드 검색
		if (empty($this->search['brandCd']) === false || empty($excelField) === true || empty($this->search['brandNoneFl']) === false) {
			$join[] = ' LEFT JOIN ' . DB_GOODS . ' as g ON og.goodsNo = g.goodsNo ';
		}

		//택배 예약 상태에 따른 검색
		if ($this->search['invoiceReserveFl']) {
			$join[] = ' LEFT JOIN ' . DB_ORDER_GODO_POST . ' ogp ON ogp.invoiceNo = og.invoiceNo ';
		}

		// 쿠폰검색시만 join
		if ($this->search['couponNo'] > 0) {
			$join[] = ' LEFT JOIN ' . DB_ORDER_COUPON . ' oc ON o.orderNo = oc.orderNo ';
			$join[] = ' LEFT JOIN ' . DB_MEMBER_COUPON . ' mc ON mc.memberCouponNo = oc.memberCouponNo ';
		}

		// 반품/교환/환불신청 사용에 따른 리스트 별도 처리 (조건은 검색 메서드 참고)
		if ($isUserHandle) {
			$arrIncludeOuh = [
				'count(ouh.sno) as totalClaimCnt',
				'userHandleReason',
				'userHandleDetailReason',
				'userRefundAccountNumber',
				'adminHandleReason',
				'ouh.regDt AS userHandleRegDt'
			];
			$join[] = ' LEFT JOIN ' . DB_ORDER_USER_HANDLE . ' ouh ON og.userHandleSno = ouh.sno ';

			$arrInclude = array_merge($arrInclude, $arrIncludeOuh);
			unset($arrIncludeOuh);
		}
		// @kookoo135 고객 클레임 신청 주문 제외
		if ($this->search['userHandleViewFl'] == 'y') {
			$this->arrWhere[] = ' NOT EXISTS (SELECT 1 FROM ' . DB_ORDER_USER_HANDLE . ' WHERE (og.userHandleSno = sno OR og.sno = userHandleGoodsNo) AND userHandleFl = \'r\')';
		}

		// 골라담기 추가 가격 상품 가격을 위한 orderGoods 레코드는 엑셀 출력에서 제외
		$this->arrWhere[] = 'NOT (og.goodsType = \'addGoods\' AND og.goodsNo = 1000000132)';

		// 현 페이지 결과
		if ($page == '0') {
			$this->db->strField = 'og.orderNo';
			$this->db->strJoin = implode('', $join);
			$this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
			if ($orderType == 'goods') $this->db->strGroup = "CONCAT(og.orderNo,og.orderCd,og.goodsNo)";
			else  $this->db->strGroup = "CONCAT(og.orderNo)";

			//총갯수관련
			$query = $this->db->query_complete();
			$strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GOODS . ' og ' . implode(' ', $query);
			$result['totalCount'] = $this->db->query_fetch($strSQL, $this->arrBind);
		}

		$this->db->strField = implode(', ', $arrInclude) . ",totalGoodsPrice";
		$this->db->strJoin = implode('', $join);
		$this->db->strWhere = implode(' AND ', gd_isset($this->arrWhere));
		if ($orderType == 'goods') $this->db->strGroup = "CONCAT(og.orderNo,og.orderCd,og.goodsNo)";
		else  $this->db->strGroup = "CONCAT(og.orderNo)";
		$this->db->strOrder = $orderSort;
		if ($pageLimit) $this->db->strLimit = (($page * $pageLimit)) . "," . $pageLimit;
		$query = $this->db->query_complete();
		$strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ORDER_GOODS . ' og ' . implode(' ', $query);

		if (empty($excelField) === false) {
			if (Manager::isProvider()) {
				$result['orderList'] = $this->db->query_fetch($strSQL, $this->arrBind);
			} else {
				$result['orderList'] = $this->db->query_fetch_generator($strSQL, $this->arrBind);
			}
		} else {
			$result = $this->db->query_fetch($strSQL, $this->arrBind);
		}

		if (Manager::isProvider()) {
			$result = $this->getProviderTotalPriceExcelList($result, $orderType);
		}

		return $result;
	}

	/**
	 * 주문 상품의 총 회차를 추출한다.
	 * 회차 배송의 경우 옵션 텍스트의 *기간, N주 포맷으로 되어 있다고 가정한다.
	 * 
	 * @param string $optionInfoText 주문 상품의 옵션 정보
	 */
	private function extractTotalRound($optionInfoText, $isFreshDelivery = false) {
		$optionInfo = json_decode(gd_htmlspecialchars_stripslashes($optionInfoText, true));
		foreach ($optionInfo as $option) {
			$optionName = $option[0];
			$optionValue = $option[1]; // ex) 2주 (20식) | 12주 (120식) | 4주(40식) ...
			if (strpos($optionName, '기간') !== false) {
				break;
			}
		}

		if ($optionName == '*기간' || ($isFreshDelivery && $optionName == '기간')) {
			$totalRound = (int)explode('주', $optionValue)[0];
			if (is_numeric($totalRound)) {
				return $totalRound * ($isFreshDelivery ? 2 : 1); // 냉장배송(freshDelivery)는 주 2회 (월/목) 회차 배송을 함, 냉동은 주 1회
			}
		}
		return 0;
	}

	/**
	 * 주문 상품 리스트에 부모 주문 상품 sno를 할당한다.
	 * 부모 상품 코드 (parentGoodsNo)는 있지만 sno가 없기 때문에 골라담기 한 상품을 정확히 찾을 수 없다.
	 * orderGoods는 부모 상품과 자식 상품이  orderCd로 정렬, 순서로 배치되기 때문에 이를 활용하여 부모 주문 상품 sno를 할당한다.
	 * 
	 * @param array $orderGoodsList 주문 상품 리스트
	 */
	private function assignParentOrderGoodsSnos(&$orderGoodsList)
	{
		$parentOrderGoodsSno = null;
		foreach ($orderGoodsList as $key => $orderGoods) {
			if ($orderGoods['goodsType'] === 'goods') {
				$parentOrderGoodsSno = $orderGoods['sno'];

				// FIXME: $orderGoods['goodsPrice'] == 0 대신 && $orderGoods['isComponentGoods'] 가 더 정확함
				// 하지만 현재 isComponentGoods가 설정되지 않는 경우가 발생하고 있어 우선 가격으로 체크 -> 원인 파악되면 다시 돌릴 것
			} else if ($orderGoods['goodsType'] == 'addGoods' && $orderGoods['goodsPrice'] == 0) {
				$orderGoodsList[$key]['parentOrderGoodsSno'] = $parentOrderGoodsSno;
			}
		}
	}

	/**
	 * 회차배송에 해당 상품을 나눈다. 
	 * 골라 담은 상품을 수량 하나 씩 순차적으로 회차배송에 배분하고 수량을 감소시키는 방식
	 *
	 * @param array $scheduledDeliveries 분배될 회차 배송
	 * @param array $goodsList 분배할 상품 목록
	 * @param int $totalRound 총 분배 회차
	 */
	private function assignGoodsListToScheduledDeliveries(&$scheduledDeliveries, $goodsList, $totalRound)
	{
		$dsIndex = 0;
		foreach ($goodsList as $goods) {
			$goodsCnt = $goods['goodsCnt'];
			for ($i = 0; $i < $goodsCnt; $i++) {
				if (empty($scheduledDeliveries[$dsIndex]['goodsList'][$goods['sno']])) {
					$goods['goodsCnt'] = 1;
					$scheduledDeliveries[$dsIndex]['goodsList'][$goods['sno']] = $goods;
				} else {
					$scheduledDeliveries[$dsIndex]['goodsList'][$goods['sno']]['goodsCnt'] += 1;
				}

				// 골고루 배송하기 분배하기 위해 홀짝수 회차로 배송을 나눈다.
				if (($dsIndex + 2) >= $totalRound) {
					if ($dsIndex % 2 === 0) {
						$dsIndex = 1;
					} else {
						$dsIndex = 0;
					}
				} else {
					$dsIndex = ($dsIndex + 2) % $totalRound;
				}
			}
		}
	}

	/**
	 * 다회차 배송 건일 경우 회차배송 레코드를 생성하고 상품을 분배한다.
	 * 다만, 기존에 회차배송 레코드가 존재하지 않을 경우만 생성한다.
	 * 
	 * @param array $orderNos 다회차 배송 레코드를 생성할 후보 주문 번호
	 * 
	 */
	public function trySplitScheduledDeliveries($orderNos, $changeStatus) {
		$needToCheckDeliveryStatusSync = false;
		 
		foreach ($orderNos as $orderNo) {
			$fields = [
				'sno',
				'goodsNo',
				'goodsNm',
				'goodsType',
				'optionInfo',
				'parentGoodsNo',
				'goodsCnt',
				'goodsCd',
				'goodsPrice',
				'orderStatus',
				'orderDeliverySno',
				'scmNo',
				'paymentDt',
				'invoiceCompanySno',
				'invoiceNo',
				'invoiceDt',
				'deliveryDt',
				'deliveryCompleteDt',
				'finishDt',
				'deliveryLog',
				'orderCd',
				'isComponentGoods',
				'firstDelivery'
			];
			$orderGoodsList = $this->getOrderGoods($orderNo, null, null, $fields );

			$this->assignParentOrderGoodsSnos($orderGoodsList);

			$parentOrderGoodsList = array_filter($orderGoodsList, function($orderGoods) {
				return $orderGoods['goodsType'] === 'goods';
			});

			foreach($parentOrderGoodsList as $parentOrderGoods) {
				$isFreshDelivery = (strlen($parentOrderGoods['firstDelivery']) > 4);
				$totalRound = $this->extractTotalRound($parentOrderGoods['optionInfo'], $isFreshDelivery);

				// 1회 배송은 회차배송이 필요없으므로 그 이상의 경우만 처리	
				if ($totalRound > 1) {
					$needToCheckDeliveryStatusSync = true;
					$parentOrderGoodsSno = $parentOrderGoods['sno'];
					
					$scheduledDeliveries = $this->fetchScheduledDeliveryByParentOrderGoods($orderNo, $parentOrderGoodsSno);

					// Once we have scheduled delivery record, we don't need to create it again
					if (empty($scheduledDeliveries)) {
						if($isFreshDelivery) {
							$this->generateScheduledFreshDeliveries($parentOrderGoods, $orderNo, $totalRound, $parentOrderGoods['firstDelivery']);
						} else {
							$paymentDt = $parentOrderGoods['paymentDt'];
							if($changeStatus == 'p1') {
								$paymentDt = date("Y-m-d H:i:s");
							}
							$this->generateScheduledDeliveries($parentOrderGoods, $orderNo, $totalRound, $paymentDt);
						}

						$generatedScheduledDeliveries = $this->fetchScheduledDeliveryByParentOrderGoods($orderNo, $parentOrderGoodsSno);

						$childOrderGoodsList = array_filter($orderGoodsList, function($orderGoods) use ($parentOrderGoodsSno) {
							return $orderGoods['parentOrderGoodsSno'] === $parentOrderGoodsSno;
						});

						if (!$isFreshDelivery) {
							$this->assignGoodsListToScheduledDeliveries($generatedScheduledDeliveries, $childOrderGoodsList, $totalRound);
						}
					
						foreach ($generatedScheduledDeliveries as $generatedDeliverySchedule) {
							$this->generateScheduledDeliveryGoodsList($parentOrderGoods, $generatedDeliverySchedule);
						}
						$needToCheckDeliveryStatusSync = false;
					} else {
						echo "Scheduled delivery record already exists for orderNo: $orderNo, parentOrderGoodsSno: $parentOrderGoodsSno";
					}
				}
			}
		}

		return $needToCheckDeliveryStatusSync;
	}

	/**
	 * 가장 가까운 미래의 + 2 working day를 찾는다.
	 * 메디쏠라는 화요일부터 토요일까지 워킹데이이다.
	 * 공휴일은 고려하지 않는다.
	 * 
	 * @param string $baseDay 기준일
	 * @return string baseDay로 부터 가장 가까운 미래의 + 2 working day
	 */
	private function findNearest2WorkingDay($baseDay = 'now')
	{
		$theNextDay = strtotime($baseDay . ' +1 day');
		while (true) {
			$theNextDay = strtotime('+1 day', $theNextDay);
			$dayOfWeek = date('N', $theNextDay);
			// when tuesday to saturday
			if (2 <= $dayOfWeek && $dayOfWeek <= 6) {
				return date('Y-m-d', $theNextDay);
			}
		}
	}
	
	/**
	 * 배송 기준 공휴일을 가져온다.
	 * 공휴일 다음날도 배송 기준 공휴일로 포함한다.
	 * 
	 * @param string $startDate 시작일, 시작일로 부터 1년 후까지 공휴일 정보를 가져온다.
	 * 
	 * @return array 공휴일 정보
	 */
	public function fetchComingHolidays($startDate = 'now')
	{
		$startDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
		$endDate = strtotime($startDate . ' +1 year');
		$query = "SELECT * FROM wm_deliveryHoliday2 WHERE datestamp BETWEEN ".strtotime($startDate)." AND ".$endDate." ORDER BY datestamp ASC";
		$holidays = $this->db->query_fetch($query);

		$uniqueHolidays = array_reduce($holidays, function($acc, $holiday) {
			// 휴무일 당일은 기본적으로 배송을 받을 수 있는 날로 지정하기로 함
			// $acc[$holiday['datestamp']] = $holiday; // 당일
			$nextDay = strtotime('+1 day', $holiday['datestamp']);
			$acc[$nextDay] = array_merge($holiday, ['memo' => $holiday['memo'] . ' (다음날)']); // 다음날
			return $acc;
		}, []);
		return $uniqueHolidays;
	}

	/**
	 * 7일 기준 회차에 따른 배송 가능일 중 공휴일을 제외한 배송 가능일을 가져온다.
	 * 
	 * @param string $baseDay 기준일
	 * @param int $round 회차
	 * @param array $holidays 공휴일 정보
	 * 
	 * @return string 배송 가능일
	 */
	public function getDeliverableDayAtDeliveryRound($baseDay, $round, $holidays)
	{
		$baseTimestamp = strtotime(date('Y-m-d 00:00:00', strtotime($baseDay)));
		$candidateTimestamp = strtotime('+' . ($round - 1) * 7 . ' days', $baseTimestamp);

		while (true) {
			$holiday = $holidays[$candidateTimestamp];
			if (empty($holiday)) {	
				$dayOfWeek = date('N', $candidateTimestamp);
				if (2 <= $dayOfWeek && $dayOfWeek <= 6) { // when tuesday to saturday
					return date('Y-m-d', $candidateTimestamp);
				}
			}
			$candidateTimestamp = strtotime('+1 day', $candidateTimestamp);
		}
	}

	/* 
	 * freshDeliveryDay should be calculated according to below rules
	 * round 1 : baseDay (selected by user - should be one of the tuesday, friday)
	 * round n : the closest tuesday or friday after the n-1 round delivery date
	 * means, we ship the products only on tuesday and friday
	 * 
	 * @return string 배송 가능일
	 */
	public function getFreshDeliveryDayBy($baseDay, $round) {
		// $MONDAY = '1';
		$TUESDAY = '2';
		$baseTimestamp = strtotime($baseDay);
		$baseDayOfWeek = date('N', $baseTimestamp); // 1:monday, 4:thursday -> 2:tuesday, 5:friday (월,목 -> 화,금 정책 변경)

		$incrementor = $round - 1;
		$incrementDays = (int)(floor($incrementor / 2) * 7);
		if($incrementor % 2 == 1) {
			$incrementDays += ($baseDayOfWeek == $TUESDAY) ? 3 : 4;
		}
		$freshDeliveryDate = strtotime($baseDay . ' +' . $incrementDays . ' days');
		return date('Y-m-d', $freshDeliveryDate);
	}

	/**
	 * 주문 상품의 회차배송 레코드를 생성한다.
	 * estimatedDeliveryDt should be calculated according to below rules
	 * round n : payment complete date + 2 + ((n-1) * 7) working days
	 * working days are tuesday to saturday (5 days)
	 * means, we ship the products only on monday to friday
	 * and exclude holidays
	 * if the calculated date is holiday, then it should be the next working day
	 * 
	 * @param array $parentOrderGoods 부모 주문 상품 정보
	 * @param string $orderNo 주문 번호
	 * @param int $totalRound 총 회차 수
	 */
	private function generateScheduledDeliveries($parentOrderGoods, $orderNo, $totalRound, $paymentDt)
	{
		$nearest2WorkingDay = $this->findNearest2WorkingDay($paymentDt);
		$holidays = $this->fetchComingHolidays($nearest2WorkingDay);
		$estimatedDeliveryDt = $this->getDeliverableDayAtDeliveryRound($nearest2WorkingDay, 1, $holidays);

		for ($i = 1; $i <= $totalRound; $i++) {
			$arrData = [
				'orderNo' => $orderNo,
				'orderGoodsSno' => $parentOrderGoods['sno'],
				'orderDeliverySno' => $parentOrderGoods['orderDeliverySno'],
				'scmNo' => $parentOrderGoods['scmNo'],
				'round' => $i,
				'totalRound' => $totalRound,
				'estimatedDeliveryDt' => date('Y-m-d', strtotime($estimatedDeliveryDt . ' +' . (($i - 1) * 7) . ' days')),
				'deliveryStatus' => 'p1', // 결제완료 상태
			];

			if ($i === 1) {
				// Only 1st round delivery should be the same as the parent order goods
				$arrData = array_merge($arrData, [
					'deliveryStatus' => $parentOrderGoods['orderStatus'],
					'invoiceCompanySno'	=> $parentOrderGoods['invoiceCompanySno'],
					'invoiceNo' => $parentOrderGoods['invoiceNo'],
					'invoiceDt' => $parentOrderGoods['invoiceDt'] === '0000-00-00 00:00:00' ? NULL : $parentOrderGoods['invoiceDt'],
					'deliveryDt' => $parentOrderGoods['deliveryDt'] === '0000-00-00 00:00:00' ? NULL : $parentOrderGoods['deliveryDt'],
					'deliveryCompleteDt' => $parentOrderGoods['deliveryCompleteDt'] === '0000-00-00 00:00:00' ? NULL : $parentOrderGoods['deliveryCompleteDt'],
					'finishDt' => $parentOrderGoods['finishDt'] === '0000-00-00 00:00:00' ? NULL : $parentOrderGoods['finishDt'],
					'deliveryLog' => $parentOrderGoods['deliveryLog'],
				]);
			}

			$arrData = array_filter($arrData, function($value) {
				return !is_null($value);
			});
			$insertFields = array_keys($arrData);
			$arrBind = $this->db->get_binding(DBTableField::tableScheduledDelivery(), $arrData, 'insert', $insertFields);
			$this->db->set_insert_db('ms_scheduledDelivery', $arrBind['param'], $arrBind['bind'], 'y');
		}
	}

	/**
	 * 주문 상품의 냉장 회차배송 레코드를 생성한다.
	 * estimatedDeliveryDt should be calculated according to below rules
	 * round 1 : the first delivery date
	 * round n : the closest next mon or thu after the n-1 round delivery date
	 * 
	 * @param array $parentOrderGoods 부모 주문 상품 정보
	 * @param string $orderNo 주문 번호
	 * @param int $totalRound 총 회차 수
	 */
	private function generateScheduledFreshDeliveries($parentOrderGoods, $orderNo, $totalRound, $firstDeliveryDt)
	{
		for ($i = 1; $i <= $totalRound; $i++) {
			$arrData = [
				'orderNo' => $orderNo,
				'orderGoodsSno' => $parentOrderGoods['sno'],
				'orderDeliverySno' => $parentOrderGoods['orderDeliverySno'],
				'scmNo' => $parentOrderGoods['scmNo'],
				'round' => $i,
				'totalRound' => $totalRound,
				'estimatedDeliveryDt' => $this->getFreshDeliveryDayBy($firstDeliveryDt, $i),
				'deliveryStatus' => 'p1', // 결제완료 상태
			];

			if ($i === 1) {
				// Only 1st round delivery should be the same as the parent order goods
				$arrData = array_merge($arrData, [
					'deliveryStatus' => $parentOrderGoods['orderStatus'],
				]);
			} 

			$arrData = array_filter($arrData, function($value) {
				return !is_null($value);
			});
			$insertFields = array_keys($arrData);
			$arrBind = $this->db->get_binding(DBTableField::tableScheduledDelivery(), $arrData, 'insert', $insertFields);
			$this->db->set_insert_db('ms_scheduledDelivery', $arrBind['param'], $arrBind['bind'], 'y');
		}
	}

	

	/**
	 * 회차 배송 상품 레코드를 생성한다.
	 * 호출 전에 deliverySchedule 파라미터 안에 생성할 골담 상품을 'goodsList' 에 할당해야 한다.
	 * 
	 * @param array $scheduledDeliveries 회차 배송 정보
	 */
	private function generateScheduledDeliveryGoodsList($parentOrderGoods, $scheduledDeliveries) 
	{
		$childOrderGoodsList = $scheduledDeliveries['goodsList'];

		$childOrderGoodsList = array_merge([$parentOrderGoods], $childOrderGoodsList ?? []);

		foreach($childOrderGoodsList as $childOrderGoods) {
			// insert scheduledDeliveryGoods
			$insertData = [
				'orderNo' => $scheduledDeliveries['orderNo'],
				'scheduledDeliverySno' => $scheduledDeliveries['sno'],
				'orderGoodsSno' => $childOrderGoods['sno'],
				'goodsNo' => $childOrderGoods['goodsNo'],
				'goodsCd' => $childOrderGoods['goodsCd'],
				'goodsNm' => $childOrderGoods['goodsNm'],
				'goodsCnt' => $childOrderGoods['goodsCnt'],
				'goodsPrice' => $childOrderGoods['goodsPrice'], // TODO: if possible
			];

			$insertFields = array_keys($insertData);
			$arrBind = $this->db->get_binding(DBTableField::tableScheduledDeliveryGoods(), $insertData, 'insert', $insertFields);
			$this->db->set_insert_db('ms_scheduledDeliveryGoods', $arrBind['param'], $arrBind['bind'], 'y');
		}
	}

	public function deliverCompleteOrderAutomatically() 
	{
		// 회차배송 자동 배송 완료 처리 대상 찾아 자동 완료 처리 진행
		$orderBasic = gd_policy('order.basic');
		$autoDeliveryCompleteFl = $orderBasic['autoDeliveryCompleteFl'];
		$autoDeliveryCompleteDay = $orderBasic['autoDeliveryCompleteDay'];

		if ($autoDeliveryCompleteFl === 'y' && $autoDeliveryCompleteDay > 0) {
			$scheduledDeliveries = $this->fetchUncompletedScheduledDeliveriesInDays($autoDeliveryCompleteDay);
			foreach ($scheduledDeliveries as $scheduledDelivery) {
				$sno = $scheduledDelivery['sno'];
				$orderNo = $scheduledDelivery['orderNo'];
				$orderGoodsSno = $scheduledDelivery['orderGoodsSno'];
				$round = $scheduledDelivery['round'];
				$deliveryStatus = $scheduledDelivery['deliveryStatus'];

				$this->setDeliveryComplete($sno);
				$this->scheduledDeliveryLog($orderNo, $orderGoodsSno, $round,  $deliveryStatus, '배송완료(D2)', '자동배송완료로 인한 배송완료 처리');
			}
		}

		// 기존 자동 주문 배송확인 처리 진행
		return parent::deliverCompleteOrderAutomatically();
	}

	public function settleOrderAutomatically()
	{
		// 회차배송 자동 배송 확인 처리 대상 찾아 자동 확인 처리 진행
		$orderBasic = gd_policy('order.basic');
		$autoOrderConfirmFl = $orderBasic['autoOrderConfirmFl'];
		$autoOrderConfirmDay = $orderBasic['autoOrderConfirmDay'];

		if ($autoOrderConfirmFl === 'y' && $autoOrderConfirmDay > 0) {
			$scheduledDeliveries = $this->fetchUnsettledScheduledDeliveriesInDays($autoOrderConfirmDay);

			foreach ($scheduledDeliveries as $scheduledDelivery) {
				$sno = $scheduledDelivery['sno'];
				$orderNo = $scheduledDelivery['orderNo'];
				$orderGoodsSno = $scheduledDelivery['orderGoodsSno'];
				$round = $scheduledDelivery['round'];
				$deliveryStatus = $scheduledDelivery['deliveryStatus'];

				$this->setDeliverySettled($sno);
				$this->scheduledDeliveryLog($orderNo, $orderGoodsSno, $round,  $deliveryStatus, '배송확인(S1)', '자동확인완료로 인한 배송확인 처리');
			}
		}

		// 기존 자동 주문 수취확인 처리 진행
		return parent::settleOrderAutomatically();
	}

	private function fetchUncompletedScheduledDeliveriesInDays($days) {
		$query = 'SELECT sno, deliveryDt, orderNo, orderGoodsSno, round, deliveryStatus, estimatedDeliveryDt FROM ms_scheduledDelivery
            WHERE deliveryDt < \'' . date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' day')) . '\'
            AND deliveryDt != \'0000-00-00 00:00:00\'
            AND deliveryStatus = \'d1\'';
		$scheduledDeliveries = $this->db->query_fetch($query);
		return $scheduledDeliveries;
	}

	private function fetchUnsettledScheduledDeliveriesInDays($days) {
		$query = 'SELECT sno, deliveryCompleteDt, orderNo, orderGoodsSno, round, deliveryStatus, estimatedDeliveryDt FROM ms_scheduledDelivery
            WHERE deliveryCompleteDt < \'' . date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' day')) . '\'
            AND deliveryCompleteDt != \'0000-00-00 00:00:00\'
            AND deliveryStatus = \'d2\'';
		$scheduledDeliveries = $this->db->query_fetch($query);
		return $scheduledDeliveries;
	}

	private function setDeliveryComplete($scheduledDeliverySno) {
		$updateData = [
			'deliveryStatus' => 'd2', // 배송완료
			'deliveryCompleteDt' => date('Y-m-d H:i:s'),
		];
		$updateFields = array_keys($updateData);
		$arrBind = $this->db->get_binding(DBTableField::tableScheduledDelivery(), $updateData, 'update', $updateFields);
		$arrWhere = 'sno = ?';
		$this->db->bind_param_push($arrBind['bind'], 'i', $scheduledDeliverySno);
		$this->db->set_update_db('ms_scheduledDelivery', $arrBind['param'], $arrWhere, $arrBind['bind'], false);
	}

	private function setDeliverySettled($scheduledDeliverySno) {
		$updateData = [
			'deliveryStatus' => 's1', // 배송확인
			'finishDt' => date('Y-m-d H:i:s'),
		];
		$updateFields = array_keys($updateData);
		$arrBind = $this->db->get_binding(DBTableField::tableScheduledDelivery(), $updateData, 'update', $updateFields);
		$arrWhere = 'sno = ?';
		$this->db->bind_param_push($arrBind['bind'], 'i', $scheduledDeliverySno);
		$this->db->set_update_db('ms_scheduledDelivery', $arrBind['param'], $arrWhere, $arrBind['bind'], false);
	}

	/**
	 * API 및 관리자 화면에서 주문 상태 변경 시 1회차 배송 상태 동기화
	 */
	public function updateStatusUnconditionalPreprocess($orderNo, $goodsData, $statusMode, $changeStatus, $reason = false, $bundleFl = false, $mode = null, $useVisit = null, $autoProcess = false, $userHandleFl = false)
	{
		// 부모 클래스의 기본 로직 실행
		$result = parent::updateStatusUnconditionalPreprocess($orderNo, $goodsData, $statusMode, $changeStatus, $reason, $bundleFl, $mode, $useVisit, $autoProcess, $userHandleFl);
		
		// 배송중(d1) 상태로 변경 시 1회차 배송 상태 동기화
		if ($result === true && $changeStatus == 'd1' && !empty($goodsData)) {
			try {
				$orderGoodsSnos = array_column($goodsData, 'sno');
				if (!empty($orderGoodsSnos)) {
					$order = App::load('\\Component\\Order\\Order');
					$order->syncDeliveryStatusOfFirstRoundDelivery($orderGoodsSnos);
				}
			} catch (Exception $e) {
				// 에러가 발생해도 주문 상태 변경은 계속 진행
				// \Logger::channel('scheduledDelivery')->error('1회차 배송 상태 동기화 실패', [
				// 	'orderNo' => $orderNo,
				// 	'changeStatus' => $changeStatus,
				// 	'error' => $e->getMessage()
				// ]);
			}
		}
		
		return $result;
	}
}

