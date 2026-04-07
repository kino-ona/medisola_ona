<?php

/**
 * 상품 class
 *
 * 상품 관련 관리자 Class
 * @author artherot
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c), Godosoft
 */
namespace Component\Goods;

use Component\Member\Group\Util as GroupUtil;
use Component\Member\Manager;
use Component\Page\Page;
use Component\Storage\Storage;
use Component\Database\DBTableField;
use Component\Validator\Validator;
use Framework\Debug\Exception\HttpException;
use Framework\Debug\Exception\AlertBackException;
use Framework\File\FileHandler;
use Framework\Utility\ImageUtils;
use Framework\Utility\StringUtils;
use Framework\Utility\ArrayUtils;
use Encryptor;
use Globals;
use LogHandler;
use UserFilePath;
use Request;
use Exception;
use Session;
use App;

class GoodsAdmin extends \Bundle\Component\Goods\GoodsAdmin
{
	
	/**
     * 관리자 상품 리스트를 위한 검색 정보
     */
    public function setSearchGoods($getValue = null, $list_type = null)
    {
		parent::setSearchGoods($getValue, $list_type);

		if ($getValue['firstDelivery']) {
			$this->search['firstDelivery'] = $getValue['firstDelivery'];
		}
		
		//웹앤모바일 2020-12-08 공동구매	
		/*
		if ($getValue['useGroupBuy']) {
			$this->goodsTable = DB_GOODS;
			$this->search['useGroupBuy'] = $getValue['useGroupBuy'];
			$this->arrWhere[] = "g.useGroupBuy = 1";
		}
		*/
		//웹앤모바일 2020-12-08 공동구매		

	}
	
    /**
     * 관리자 상품 일괄 관리 리스트 - 상품기준
     *
     * @param string $mode 리스트에 이미지 출력여부 (null or image)
     * @return array 상품 리스트 정보
     */
    public function getAdminListBatch($mode = null)
    {
        $getValue = Request::get()->toArray();

        // 2023-02-08 웹앤모바일 새벽배송 선택시
        if($getValue['useEarlyDelivery'] == '1' || $getValue['useEarlyDelivery'] == 'common' || $getValue['useEarlyDelivery'] == 'all'
            || $getValue['useGoodsViewIcon'] == 'common' || $getValue['useGoodsViewIcon'] == '1' || $getValue['useGoodsViewIcon'] == 'all'
            || $getValue['useGoodsViewBanner'] == 'common' || $getValue['useGoodsViewBanner'] == '1' || $getValue['useGoodsViewBanner'] == 'all'){
            $tmp = $this->getAdminListBatchWm($mode);
		// 선물하기 선택시
        }elseif($getValue['useGift'] == 1 || $getValue['useGift'] == 'common' || $getValue['useGift'] == 'all'){
			$tmp = $this->getAdminListBatchGift($mode);
		// 첫배송 선택시
		}elseif($getValue['firstDelivery'] == 'y' || $getValue['firstDelivery'] == 'n'){
			$tmp = $this->getAdminListBatchFirst($mode);
		} else{
            $tmp = parent::getAdminListBatch($mode);
        }

        return $tmp;
    }

    public function getAdminListBatchWm($mode = null)
    {
        // --- 검색 설정
        $getValue = Request::get()->toArray();

        gd_isset($getValue['delFl'], 'n');
        $sort = gd_isset($getValue['sort'], 'g.goodsNo desc');
        $changeGoodsTableFl = false;

        if ($mode == 'restock') {
            if ($this->goodsTable == DB_GOODS_SEARCH) {
                $changeGoodsTableFl = true;
                $this->goodsTable = DB_GOODS;
            }
            gd_isset($getValue['pageNum'], 20);
        }

        $this->setSearchGoods($getValue);

        switch ($mode) {
            case 'icon':
                $addField = ', g.goodsColor ';
                break;
            case 'delivery':
                $addField = ",g.deliverySno";
                break;
            case 'restock':
                if (gd_isset($getValue['pagelink'])) {
                    $getValue['page'] = (int)str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($getValue['pagelink'])));
                }
                $addField = ",g.restockFl";
                break;
            case 'image' :
                $addField = ", g.benefitUseType, g.newGoodsRegFl, g.newGoodsDate, g.newGoodsDateFl, g.periodDiscountStart, g.periodDiscountEnd, g.fixedGoodsDiscount, g.goodsDiscountGroupMemberInfo";
                break;
            default :
                $addField = '';
        }

        // --- 페이지 기본설정
        gd_isset($getValue['page'], 1);
        gd_isset($getValue['pageNum'], 10);

        $page = \App::load('\\Component\\Page\\Page', $getValue['page'],0,0,$getValue['pageNum']);
        $page->setCache(true); // 페이지당 리스트 수
        if ($mode != 'layer') {
            $page->setUrl(\Request::getQueryString());
        }

        //상품 헤택 모듈
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
        $goodsBenefitUse = $goodsBenefit->getConfig();

        // 현 페이지 결과
        if (!empty($this->search['cateGoods'])) {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_CATEGORY . ' as gl ON g.goodsNo = gl.goodsNo ';
        }

        if(($getValue['key'] == 'companyNm' && $getValue['keyword']) || strpos($sort, "companyNm") !== false) $join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' as s ON s.scmNo = g.scmNo ';
        if(gd_is_provider() === false && gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true )  {
            if($getValue['key'] == 'purchaseNm' && $getValue['keyword']) {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo and p.delFl = "n"';
            } else if($getValue['purchaseNoneFl'] =='y') {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo';
            }
        }

        //상품 혜택 검색
        if (!empty($this->search['goodsBenefitSno'])|| $this->search['goodsBenefitNoneFl'] == 'y') {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_BENEFIT . ' as gbl ON gbl.goodsNo = g.goodsNo ';
        }

        //상품 혜택 아이콘 검색
        if ($goodsBenefitUse == 'y' && $this->search['goodsIconCd']) {
            $join[] = 'LEFT JOIN
            (
            select t1.goodsNo,t1.benefitSno,t1.goodsIconCd
            from ' . DB_GOODS_LINK_BENEFIT . ' as t1,
            (select goodsNo, min(linkPeriodStart) as min_start from ' . DB_GOODS_LINK_BENEFIT . ' where ((benefitUseType=\'periodDiscount\' or benefitUseType=\'newGoodsDiscount\') AND linkPeriodStart < NOW() AND linkPeriodEnd > NOW()) or benefitUseType=\'nonLimit\'  group by goodsNo) as t2
            where t1.linkPeriodStart = t2.min_start and t1.goodsNo = t2.goodsNo
            ) as gbs on g.goodsNo = gbs.goodsNo ';
        }

        //상품 아이콘 테이블추가
        if ($this->search['goodsIconCdPeriod'] || $this->search['goodsIconCd']) {
            if ($this->search['goodsIconCdPeriod'] && !$this->search['goodsIconCd']) {
                $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
            } else {
                if ($goodsBenefitUse == 'y'){
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo OR (gbs.benefitSno = gi.benefitSno AND gi.iconKind = \'pr\')';
                }else{
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                }

            }
            // 검색 조건에 아이콘 검색이 있는 경우 group by 추가
            $this->db->strGroup = "g.goodsNo ";
            $goodsIconStrGroup = " GROUP BY g.goodsNo";
        }

        //추가정보 검색
        if($getValue['key'] == 'addInfo' && $getValue['keyword']) {
            $addInfoQuery = "(SELECT goodsNo,count(*) as addInfoCnt FROM ".DB_GOODS_ADD_INFO." WHERE infoValue LIKE concat('%','".$this->db->escape($getValue['keyword'])."','%') GROUP BY goodsNo)";
            $join[] = ' LEFT JOIN ' . $addInfoQuery . ' as gai ON  gai.goodsNo = g.goodsNo';
            $this->arrWhere[] = " addInfoCnt  > 0";
        }

        if(strpos($sort, "costPrice") !== false || strpos($sort, "fixedPrice") !== false) $join[] = ' INNER JOIN ' . DB_GOODS . ' as gs ON gs.goodsNo = g.goodsNo ';

        $this->arrWhere[] = "g.applyFl !='a'";

        $this->db->strField = "g.goodsNo";
        $this->db->strJoin = implode('', $join);
        $this->db->strWhere = implode(' AND ', $this->arrWhere);
        $this->db->strOrder = $sort;

        // 검색 전체를 일괄 수정을 할경우 필요한 값
        $getData['batchAll']['join'] = Encryptor::encrypt($this->db->strJoin);
        $getData['batchAll']['where'] = Encryptor::encrypt($this->db->strWhere);
        $getData['batchAll']['bind'] = Encryptor::encrypt(json_encode($this->arrBind, JSON_UNESCAPED_UNICODE));

        //상품 아이콘 기간제한 & 무제한 모두 검색시 index 태우기 위해 UNION 추가
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {
            $page->recode['union_start'] = $page->recode['start'] + 10;
            $this->db->strLimit = '0 ,' . $page->recode['union_start'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }


            $query = $query2 = $this->db->query_complete();

            $strSQL = 'SELECT goodsNo FROM ((SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query) . ') UNION';

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y'){
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')', $query2['where']);
                }else{
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )', $query2['where']);
                }


                foreach ($this->arrBind as $bind_key => $bind_val) {
                    if ($bind_key > 0) {
                        if ($this->search['goodsIconCdPeriod'] != $bind_val) {
                            $this->arrBind[count($this->arrBind)] = $bind_val;
                            $this->arrBind[0] .= 's';
                        }
                    }
                }
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                if ($goodsBenefitUse == 'y') {
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $strSQL .= '(SELECT ' . array_shift($query2) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query2) . '))a order by goodsNo desc LIMIT ' .  $page->recode['start'] . ',' . $getValue['pageNum'];
            }

        } else {
            $this->db->strLimit = $page->recode['start'] . ',' . $getValue['pageNum'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            // 2023-02-08 웹앤모바일 새벽배송 검색시
            if($getValue['useEarlyDelivery'] == '1'){
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useEarlyDelivery = 1';
            } else if ($getValue['useEarlyDelivery'] == 'common') {
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useEarlyDelivery = 0';
            }

            // 2023-02-13 웹앤모바일 아이콘설정 검색시
            if($getValue['useGoodsViewIcon'] == '1'){
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useGoodsViewIcon = 1';
            } else if ($getValue['useGoodsViewIcon'] == 'common') {
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useGoodsViewIcon = 0';
            }

            // 2023-02-16 웹앤모바일 배너설정 검색시
            if($getValue['useGoodsViewBanner'] == '1'){
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useGoodsViewBanner = 1';
            } else if ($getValue['useGoodsViewBanner'] == 'common') {
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useGoodsViewBanner = 0';
            }
            
            //무제한 아이콘
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y'){
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }else{
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $this->db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);
        }

        $data = $this->db->query_fetch($strSQL, $this->arrBind);    // 상품코드만 가져옴

        // 2023-02-08 웹앤모바일 새벽배송 선택시
        if($getValue['useEarlyDelivery'] == '1'){
            $this->search['useEarlyDelivery'] = '1';
        } else if ($getValue['useEarlyDelivery'] == 'common') { //일반상품만
            $this->search['useEarlyDelivery'] = '0';
        }   
        
        // 2023-02-13 웹앤모바일 아이콘설정 선택시
        if($getValue['useGoodsViewIcon'] == '1'){
            $this->search['useGoodsViewIcon'] = '1';
        } else if ($getValue['useGoodsViewIcon'] == 'common') { //미설정만
            $this->search['useGoodsViewIcon'] = '0';
        }

        // 2023-02-13 웹앤모바일 배너설정 선택시
        if($getValue['useGoodsViewBanner'] == '1'){
            $this->search['useGoodsViewBanner'] = '1';
        } else if ($getValue['useGoodsViewBanner'] == 'common') { //미설정만
            $this->search['useGoodsViewBanner'] = '0';
        }

        /* 검색 count 쿼리 */
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                $this->arrWhere2 = $this->arrWhere;
                foreach ($this->arrWhere2 as $k => $v) {
                    if(strpos($v, 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'') !== false) {
                        if ($goodsBenefitUse == 'y'){
                            $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\'  OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                        }else{
                            $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\'  )';
                        }

                    }
                }
            }

            //상품 아이콘 검색시 카운트
            $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM (( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') UNION';

            if ($this->search['goodsIconCd']) {
                $totalCountSQL .=  ' ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere2) . $goodsIconStrGroup. ')) tbl';
            }

        } else {
            //2023-02-08 웹앤모바일 새벽배송 선택시
            if($getValue['useEarlyDelivery'] == '1' || $getValue['useEarlyDelivery'] == 'common'){
                if($getValue['useEarlyDelivery'] == 'common'){
                    $getValue['useEarlyDelivery'] = '0';
                }
                $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useEarlyDelivery = '.$getValue['useEarlyDelivery']. ') tbl';
            //2023-02-13 웹앤모바일 아이콘설정 선택시
            }else if($getValue['useGoodsViewIcon'] == '1' || $getValue['useGoodsViewIcon'] == 'common'){
                if($getValue['useGoodsViewIcon'] == 'common'){
                    $getValue['useGoodsViewIcon'] = '0';
                }
                $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useGoodsViewIcon = '.$getValue['useGoodsViewIcon']. ' ) tbl';
            }else if($getValue['useGoodsViewBanner'] == '1' || $getValue['useGoodsViewBanner'] == 'common'){
                if($getValue['useGoodsViewBanner'] == 'common'){
                    $getValue['useGoodsViewBanner'] = '0';
                }
                $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useGoodsViewBanner = '.$getValue['useGoodsViewBanner']. ' ) tbl';
            }else{
                $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') tbl';
            }

        }
        
        $dataCount = $this->db->query_fetch($totalCountSQL, $this->arrBind,false);

        unset($this->arrBind);

        $page->recode['total'] = $dataCount['totalCnt']; //검색 레코드 수

        if($page->hasRecodeCache('amount') === false) {
            if (Session::get('manager.isProvider') || $mode === 'delivery') { // 전체 레코드 수
                $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE applyFl !="a" AND delFl=\'' . $getValue['delFl'] . '\'  AND scmNo = \'' . Session::get('manager.scmNo') . '\'');
            } else {
                $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE applyFl !="a" AND  delFl=\'' . $getValue['delFl'] . '\'');
            }
        }

        $page->setPage();

        // 아이콘  설정
        if (empty($data) === false) {
            $this->setAdminListGoods($data,",g.brandCd, g.fixedPrice,g.mileageFl, g.mileageGroup, g.mileageGoods,g.mileageGoodsUnit, g.costPrice,g.goodsDiscountFl,g.goodsDiscount,g.goodsDiscountUnit, g.goodsDiscountGroup,naverFl,g.goodsBenefitSetFl,paycoFl,daumFl".$addField,$mode);
        }

        // 각 데이터 배열화
        $getData['data'] = gd_htmlspecialchars_stripslashes(gd_isset($data));
        $getData['search'] = gd_htmlspecialchars($this->search);
        $getData['checked'] = $this->checked;
        $getData['selected'] = $this->selected;

        unset($this->arrBind);

        if ($mode == 'icon') {
            // 상품 아이콘 설정
            $this->db->strField = implode(', ', DBTableField::setTableField('tableManageGoodsIcon', null, 'iconUseFl'));
            $this->db->strWhere = 'iconUseFl = \'y\'';
            $this->db->strOrder = 'sno DESC';
            $getData['icon'] = $this->getManageGoodsIconInfo();
        }

        if ($mode == 'restock' && $changeGoodsTableFl) {
            $this->goodsTable = DB_GOODS_SEARCH;
        }


        return $getData;
    }
	
	
	public function getAdminListBatchGift($mode = null)
	{
		// --- 검색 설정
        $getValue = Request::get()->toArray();

        gd_isset($getValue['delFl'], 'n');
        $sort = gd_isset($getValue['sort'], 'g.goodsNo desc');
        $changeGoodsTableFl = false;

        if ($mode == 'restock') {
            if ($this->goodsTable == DB_GOODS_SEARCH) {
                $changeGoodsTableFl = true;
                $this->goodsTable = DB_GOODS;
            }
            gd_isset($getValue['pageNum'], 20);
        }

        $this->setSearchGoods($getValue);

        switch ($mode) {
            case 'icon':
                $addField = ', g.goodsColor ';
                break;
            case 'delivery':
                $addField = ",g.deliverySno";
                break;
            case 'restock':
                if (gd_isset($getValue['pagelink'])) {
                    $getValue['page'] = (int)str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($getValue['pagelink'])));
                }
                $addField = ",g.restockFl";
                break;
            case 'image' :
                $addField = ", g.benefitUseType, g.newGoodsRegFl, g.newGoodsDate, g.newGoodsDateFl, g.periodDiscountStart, g.periodDiscountEnd, g.fixedGoodsDiscount, g.goodsDiscountGroupMemberInfo";
                break;
            default :
                $addField = '';
        }

        // --- 페이지 기본설정
        gd_isset($getValue['page'], 1);
        gd_isset($getValue['pageNum'], 10);

        $page = \App::load('\\Component\\Page\\Page', $getValue['page'],0,0,$getValue['pageNum']);
        $page->setCache(true); // 페이지당 리스트 수
        if ($mode != 'layer') {
            $page->setUrl(\Request::getQueryString());
        }

        //상품 헤택 모듈
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
        $goodsBenefitUse = $goodsBenefit->getConfig();

        // 현 페이지 결과
        if (!empty($this->search['cateGoods'])) {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_CATEGORY . ' as gl ON g.goodsNo = gl.goodsNo ';
        }

        if(($getValue['key'] == 'companyNm' && $getValue['keyword']) || strpos($sort, "companyNm") !== false) $join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' as s ON s.scmNo = g.scmNo ';
        if(gd_is_provider() === false && gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true )  {
            if($getValue['key'] == 'purchaseNm' && $getValue['keyword']) {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo and p.delFl = "n"';
            } else if($getValue['purchaseNoneFl'] =='y') {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo';
            }
        }

        //상품 혜택 검색
        if (!empty($this->search['goodsBenefitSno'])|| $this->search['goodsBenefitNoneFl'] == 'y') {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_BENEFIT . ' as gbl ON gbl.goodsNo = g.goodsNo ';
        }

        //상품 혜택 아이콘 검색
        if ($goodsBenefitUse == 'y' && $this->search['goodsIconCd']) {
            $join[] = 'LEFT JOIN
            (
            select t1.goodsNo,t1.benefitSno,t1.goodsIconCd
            from ' . DB_GOODS_LINK_BENEFIT . ' as t1,
            (select goodsNo, min(linkPeriodStart) as min_start from ' . DB_GOODS_LINK_BENEFIT . ' where ((benefitUseType=\'periodDiscount\' or benefitUseType=\'newGoodsDiscount\') AND linkPeriodStart < NOW() AND linkPeriodEnd > NOW()) or benefitUseType=\'nonLimit\'  group by goodsNo) as t2
            where t1.linkPeriodStart = t2.min_start and t1.goodsNo = t2.goodsNo
            ) as gbs on g.goodsNo = gbs.goodsNo ';
        }

        //상품 아이콘 테이블추가
        if ($this->search['goodsIconCdPeriod'] || $this->search['goodsIconCd']) {
            if ($this->search['goodsIconCdPeriod'] && !$this->search['goodsIconCd']) {
                $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
            } else {
                if ($goodsBenefitUse == 'y'){
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo OR (gbs.benefitSno = gi.benefitSno AND gi.iconKind = \'pr\')';
                }else{
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                }

            }
            // 검색 조건에 아이콘 검색이 있는 경우 group by 추가
            $this->db->strGroup = "g.goodsNo ";
            $goodsIconStrGroup = " GROUP BY g.goodsNo";
        }

        //추가정보 검색
        if($getValue['key'] == 'addInfo' && $getValue['keyword']) {
            $addInfoQuery = "(SELECT goodsNo,count(*) as addInfoCnt FROM ".DB_GOODS_ADD_INFO." WHERE infoValue LIKE concat('%','".$this->db->escape($getValue['keyword'])."','%') GROUP BY goodsNo)";
            $join[] = ' LEFT JOIN ' . $addInfoQuery . ' as gai ON  gai.goodsNo = g.goodsNo';
            $this->arrWhere[] = " addInfoCnt  > 0";
        }

        if(strpos($sort, "costPrice") !== false || strpos($sort, "fixedPrice") !== false) $join[] = ' INNER JOIN ' . DB_GOODS . ' as gs ON gs.goodsNo = g.goodsNo ';

        $this->arrWhere[] = "g.applyFl !='a'";

        $this->db->strField = "g.goodsNo";
        $this->db->strJoin = implode('', $join);
        $this->db->strWhere = implode(' AND ', $this->arrWhere);
        $this->db->strOrder = $sort;

        // 검색 전체를 일괄 수정을 할경우 필요한 값
        $getData['batchAll']['join'] = Encryptor::encrypt($this->db->strJoin);
        $getData['batchAll']['where'] = Encryptor::encrypt($this->db->strWhere);
        $getData['batchAll']['bind'] = Encryptor::encrypt(json_encode($this->arrBind, JSON_UNESCAPED_UNICODE));

        //상품 아이콘 기간제한 & 무제한 모두 검색시 index 태우기 위해 UNION 추가
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {
            $page->recode['union_start'] = $page->recode['start'] + 10;
            $this->db->strLimit = '0 ,' . $page->recode['union_start'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $query2 = $this->db->query_complete();

            $strSQL = 'SELECT goodsNo FROM ((SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query) . ') UNION';

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y'){
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')', $query2['where']);
                }else{
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )', $query2['where']);
                }


                foreach ($this->arrBind as $bind_key => $bind_val) {
                    if ($bind_key > 0) {
                        if ($this->search['goodsIconCdPeriod'] != $bind_val) {
                            $this->arrBind[count($this->arrBind)] = $bind_val;
                            $this->arrBind[0] .= 's';
                        }
                    }
                }
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                if ($goodsBenefitUse == 'y') {
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $strSQL .= '(SELECT ' . array_shift($query2) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query2) . '))a order by goodsNo desc LIMIT ' .  $page->recode['start'] . ',' . $getValue['pageNum'];
            }
			
        } else {
            $this->db->strLimit = $page->recode['start'] . ',' . $getValue['pageNum'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

			//선물하기 선택시
			if($getValue['useGift'] == 1){
				$this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
				$this->db->strWhere .= ' AND gs.useGift = 1';
			} else if ($getValue['useGift'] == 'common') {
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useGift = 0';
            }

            //무제한 아이콘
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y'){
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }else{
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $this->db->query_complete();
			
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);	
        }

        $data = $this->db->query_fetch($strSQL, $this->arrBind);    // 상품코드만 가져옴

		// 선물하기 선택시
		if($getValue['useGift'] == 1){
			$this->search['useGift'] = 1;
		} else if ($getValue['useGift'] == 'common') { //일반상품만
            $this->search['useGift'] = 0;
        }

        /* 검색 count 쿼리 */
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                $this->arrWhere2 = $this->arrWhere;
                foreach ($this->arrWhere2 as $k => $v) {
                    if(strpos($v, 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'') !== false) {
                        if ($goodsBenefitUse == 'y'){
                            $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\'  OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                        }else{
                            $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\'  )';
                        }

                    }
                }
            }

            //상품 아이콘 검색시 카운트
            $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM (( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') UNION';

            if ($this->search['goodsIconCd']) {
                $totalCountSQL .=  ' ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere2) . $goodsIconStrGroup. ')) tbl';
            }

        } else {
			//선물하기 선택시
			if($getValue['useGift'] == 1 || $getValue['useGift'] == 'common'){
				if($getValue['useGift'] == 1){
					$totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useGift = 1) tbl';
				}else{
					$totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useGift = 0) tbl';
				}
			}else{
				$totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') tbl';
			}
			
        }
	
        $dataCount = $this->db->query_fetch($totalCountSQL, $this->arrBind,false);

        unset($this->arrBind);

        $page->recode['total'] = $dataCount['totalCnt']; //검색 레코드 수

        if($page->hasRecodeCache('amount') === false) {
            if (Session::get('manager.isProvider') || $mode === 'delivery') { // 전체 레코드 수
                $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE applyFl !="a" AND delFl=\'' . $getValue['delFl'] . '\'  AND scmNo = \'' . Session::get('manager.scmNo') . '\'');
            } else {
                $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE applyFl !="a" AND  delFl=\'' . $getValue['delFl'] . '\'');
            }
        }

        $page->setPage();

        // 아이콘  설정
        if (empty($data) === false) {
            $this->setAdminListGoods($data,",g.brandCd, g.fixedPrice,g.mileageFl, g.mileageGroup, g.mileageGoods,g.mileageGoodsUnit, g.costPrice,g.goodsDiscountFl,g.goodsDiscount,g.goodsDiscountUnit, g.goodsDiscountGroup,naverFl,g.goodsBenefitSetFl,paycoFl,daumFl".$addField,$mode);
        }

        // 각 데이터 배열화
        $getData['data'] = gd_htmlspecialchars_stripslashes(gd_isset($data));
        $getData['search'] = gd_htmlspecialchars($this->search);
        $getData['checked'] = $this->checked;
        $getData['selected'] = $this->selected;

        unset($this->arrBind);

        if ($mode == 'icon') {
            // 상품 아이콘 설정
            $this->db->strField = implode(', ', DBTableField::setTableField('tableManageGoodsIcon', null, 'iconUseFl'));
            $this->db->strWhere = 'iconUseFl = \'y\'';
            $this->db->strOrder = 'sno DESC';
            $getData['icon'] = $this->getManageGoodsIconInfo();
        }

        if ($mode == 'restock' && $changeGoodsTableFl) {
            $this->goodsTable = DB_GOODS_SEARCH;
        }


        return $getData;
	}
	
	
	
	public function getAdminListBatchFirst($mode = null)
	{
		// --- 검색 설정
        $getValue = Request::get()->toArray();

        gd_isset($getValue['delFl'], 'n');
        $sort = gd_isset($getValue['sort'], 'g.goodsNo desc');
        $changeGoodsTableFl = false;

        if ($mode == 'restock') {
            if ($this->goodsTable == DB_GOODS_SEARCH) {
                $changeGoodsTableFl = true;
                $this->goodsTable = DB_GOODS;
            }
            gd_isset($getValue['pageNum'], 20);
        }

        $this->setSearchGoods($getValue);

        switch ($mode) {
            case 'icon':
                $addField = ', g.goodsColor ';
                break;
            case 'delivery':
                $addField = ",g.deliverySno";
                break;
            case 'restock':
                if (gd_isset($getValue['pagelink'])) {
                    $getValue['page'] = (int)str_replace('page=', '', preg_replace('/^{page=[0-9]+}/', '', gd_isset($getValue['pagelink'])));
                }
                $addField = ",g.restockFl";
                break;
            case 'image' :
                $addField = ", g.benefitUseType, g.newGoodsRegFl, g.newGoodsDate, g.newGoodsDateFl, g.periodDiscountStart, g.periodDiscountEnd, g.fixedGoodsDiscount, g.goodsDiscountGroupMemberInfo";
                break;
            default :
                $addField = '';
        }

        // --- 페이지 기본설정
        gd_isset($getValue['page'], 1);
        gd_isset($getValue['pageNum'], 10);

        $page = \App::load('\\Component\\Page\\Page', $getValue['page'],0,0,$getValue['pageNum']);
        $page->setCache(true); // 페이지당 리스트 수
        if ($mode != 'layer') {
            $page->setUrl(\Request::getQueryString());
        }

        //상품 헤택 모듈
        $goodsBenefit = \App::load('\\Component\\Goods\\GoodsBenefit');
        $goodsBenefitUse = $goodsBenefit->getConfig();

        // 현 페이지 결과
        if (!empty($this->search['cateGoods'])) {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_CATEGORY . ' as gl ON g.goodsNo = gl.goodsNo ';
        }

        if(($getValue['key'] == 'companyNm' && $getValue['keyword']) || strpos($sort, "companyNm") !== false) $join[] = ' LEFT JOIN ' . DB_SCM_MANAGE . ' as s ON s.scmNo = g.scmNo ';
        if(gd_is_provider() === false && gd_is_plus_shop(PLUSSHOP_CODE_PURCHASE) === true )  {
            if($getValue['key'] == 'purchaseNm' && $getValue['keyword']) {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo and p.delFl = "n"';
            } else if($getValue['purchaseNoneFl'] =='y') {
                $join[] = ' LEFT JOIN ' . DB_PURCHASE . ' as p ON p.purchaseNo = g.purchaseNo';
            }
        }

        //상품 혜택 검색
        if (!empty($this->search['goodsBenefitSno'])|| $this->search['goodsBenefitNoneFl'] == 'y') {
            $join[] = ' LEFT JOIN ' . DB_GOODS_LINK_BENEFIT . ' as gbl ON gbl.goodsNo = g.goodsNo ';
        }

        //상품 혜택 아이콘 검색
        if ($goodsBenefitUse == 'y' && $this->search['goodsIconCd']) {
            $join[] = 'LEFT JOIN
            (
            select t1.goodsNo,t1.benefitSno,t1.goodsIconCd
            from ' . DB_GOODS_LINK_BENEFIT . ' as t1,
            (select goodsNo, min(linkPeriodStart) as min_start from ' . DB_GOODS_LINK_BENEFIT . ' where ((benefitUseType=\'periodDiscount\' or benefitUseType=\'newGoodsDiscount\') AND linkPeriodStart < NOW() AND linkPeriodEnd > NOW()) or benefitUseType=\'nonLimit\'  group by goodsNo) as t2
            where t1.linkPeriodStart = t2.min_start and t1.goodsNo = t2.goodsNo
            ) as gbs on g.goodsNo = gbs.goodsNo ';
        }

        //상품 아이콘 테이블추가
        if ($this->search['goodsIconCdPeriod'] || $this->search['goodsIconCd']) {
            if ($this->search['goodsIconCdPeriod'] && !$this->search['goodsIconCd']) {
                $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
            } else {
                if ($goodsBenefitUse == 'y'){
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo OR (gbs.benefitSno = gi.benefitSno AND gi.iconKind = \'pr\')';
                }else{
                    $join[] = ' LEFT JOIN ' . DB_GOODS_ICON . ' as gi ON g.goodsNo = gi.goodsNo ';
                }

            }
            // 검색 조건에 아이콘 검색이 있는 경우 group by 추가
            $this->db->strGroup = "g.goodsNo ";
            $goodsIconStrGroup = " GROUP BY g.goodsNo";
        }

        //추가정보 검색
        if($getValue['key'] == 'addInfo' && $getValue['keyword']) {
            $addInfoQuery = "(SELECT goodsNo,count(*) as addInfoCnt FROM ".DB_GOODS_ADD_INFO." WHERE infoValue LIKE concat('%','".$this->db->escape($getValue['keyword'])."','%') GROUP BY goodsNo)";
            $join[] = ' LEFT JOIN ' . $addInfoQuery . ' as gai ON  gai.goodsNo = g.goodsNo';
            $this->arrWhere[] = " addInfoCnt  > 0";
        }

        if(strpos($sort, "costPrice") !== false || strpos($sort, "fixedPrice") !== false) $join[] = ' INNER JOIN ' . DB_GOODS . ' as gs ON gs.goodsNo = g.goodsNo ';

        $this->arrWhere[] = "g.applyFl !='a'";

        $this->db->strField = "g.goodsNo";
        $this->db->strJoin = implode('', $join);
        $this->db->strWhere = implode(' AND ', $this->arrWhere);
        $this->db->strOrder = $sort;

        // 검색 전체를 일괄 수정을 할경우 필요한 값
        $getData['batchAll']['join'] = Encryptor::encrypt($this->db->strJoin);
        $getData['batchAll']['where'] = Encryptor::encrypt($this->db->strWhere);
        $getData['batchAll']['bind'] = Encryptor::encrypt(json_encode($this->arrBind, JSON_UNESCAPED_UNICODE));

        //상품 아이콘 기간제한 & 무제한 모두 검색시 index 태우기 위해 UNION 추가
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {
            $page->recode['union_start'] = $page->recode['start'] + 10;
            $this->db->strLimit = '0 ,' . $page->recode['union_start'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $query2 = $this->db->query_complete();

            $strSQL = 'SELECT goodsNo FROM ((SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query) . ') UNION';

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y'){
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')', $query2['where']);
                }else{
                    $query2['where'] = str_replace('gi.goodsIconCd = ? AND gi.iconKind = \'pe\'', '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )', $query2['where']);
                }


                foreach ($this->arrBind as $bind_key => $bind_val) {
                    if ($bind_key > 0) {
                        if ($this->search['goodsIconCdPeriod'] != $bind_val) {
                            $this->arrBind[count($this->arrBind)] = $bind_val;
                            $this->arrBind[0] .= 's';
                        }
                    }
                }
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                if ($goodsBenefitUse == 'y') {
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $strSQL .= '(SELECT ' . array_shift($query2) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query2) . '))a order by goodsNo desc LIMIT ' .  $page->recode['start'] . ',' . $getValue['pageNum'];
            }
			
        } else {
            $this->db->strLimit = $page->recode['start'] . ',' . $getValue['pageNum'];

            //기간제한 아이콘
            if ($this->search['goodsIconCdPeriod']) {
                $this->arrWhere[] = 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'';
                $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCdPeriod']);
                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

			//첫배송 선택시
			if($getValue['firstDelivery'] == 'y'){
				$this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
				$this->db->strWhere .= ' AND gs.useFirst = 1';
			} else if ($getValue['firstDelivery'] == 'n') {
                $this->db->strJoin = 'JOIN es_goods as gs ON g.goodsNo = gs.goodsNo';
                $this->db->strWhere .= ' AND gs.useFirst = 0';
            }

            //무제한 아이콘
            if ($this->search['goodsIconCd']) {
                if ($goodsBenefitUse == 'y'){
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }else{
                    $this->arrWhere[] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\' )';
                    $this->db->bind_param_push($this->arrBind, 's', $this->search['goodsIconCd']);
                }

                $this->db->strWhere = implode(' AND ', $this->arrWhere);
            }

            $query = $this->db->query_complete();
			
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . $this->goodsTable . ' g ' . implode(' ', $query);	
        }

        $data = $this->db->query_fetch($strSQL, $this->arrBind);    // 상품코드만 가져옴

        /* 검색 count 쿼리 */
        if ($this->search['goodsIconCdPeriod'] && $this->search['goodsIconCd']) {

            //무제한 아이콘 검색
            if ($this->search['goodsIconCd']) {
                $this->arrWhere2 = $this->arrWhere;
                foreach ($this->arrWhere2 as $k => $v) {
                    if(strpos($v, 'gi.goodsIconCd = ? AND gi.iconKind = \'pe\'') !== false) {
                        if ($goodsBenefitUse == 'y'){
                            $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\'  OR gi.goodsIconCd = ? AND gi.iconKind = \'pr\')';
                        }else{
                            $this->arrWhere2[$k] = '(gi.goodsIconCd = ? AND gi.iconKind = \'un\'  )';
                        }

                    }
                }
            }

            //상품 아이콘 검색시 카운트
            $totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM (( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') UNION';

            if ($this->search['goodsIconCd']) {
                $totalCountSQL .=  ' ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere2) . $goodsIconStrGroup. ')) tbl';
            }

        } else {
			//선물하기 선택시
			if($getValue['firstDelivery'] == 'y' || $getValue['firstDelivery'] == 'n'){
				if($getValue['firstDelivery'] == 'y'){
					$totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useFirst = 1) tbl';
				}else{
					$totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).' JOIN es_goods as gs ON gs.goodsNo = g.goodsNo WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ' AND gs.useFirst = 0) tbl';
				}
			}else{
				$totalCountSQL =  ' SELECT COUNT(1) as totalCnt FROM ( SELECT g.goodsNo FROM ' . $this->goodsTable . ' as g  '.implode('', $join).'  WHERE '.implode(' AND ', $this->arrWhere) . $goodsIconStrGroup. ') tbl';
			}
			
        }
	
        $dataCount = $this->db->query_fetch($totalCountSQL, $this->arrBind,false);

        unset($this->arrBind);

        $page->recode['total'] = $dataCount['totalCnt']; //검색 레코드 수

        if($page->hasRecodeCache('amount') === false) {
            if (Session::get('manager.isProvider') || $mode === 'delivery') { // 전체 레코드 수
                $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE applyFl !="a" AND delFl=\'' . $getValue['delFl'] . '\'  AND scmNo = \'' . Session::get('manager.scmNo') . '\'');
            } else {
                $page->recode['amount'] = $this->db->getCount($this->goodsTable, 'goodsNo', 'WHERE applyFl !="a" AND  delFl=\'' . $getValue['delFl'] . '\'');
            }
        }

        $page->setPage();

        // 아이콘  설정
        if (empty($data) === false) {
            $this->setAdminListGoods($data,",g.brandCd, g.fixedPrice,g.mileageFl, g.mileageGroup, g.mileageGoods,g.mileageGoodsUnit, g.costPrice,g.goodsDiscountFl,g.goodsDiscount,g.goodsDiscountUnit, g.goodsDiscountGroup,naverFl,g.goodsBenefitSetFl,paycoFl,daumFl".$addField,$mode);
        }

        // 각 데이터 배열화
        $getData['data'] = gd_htmlspecialchars_stripslashes(gd_isset($data));
        $getData['search'] = gd_htmlspecialchars($this->search);
        $getData['checked'] = $this->checked;
        $getData['selected'] = $this->selected;

        unset($this->arrBind);

        if ($mode == 'icon') {
            // 상품 아이콘 설정
            $this->db->strField = implode(', ', DBTableField::setTableField('tableManageGoodsIcon', null, 'iconUseFl'));
            $this->db->strWhere = 'iconUseFl = \'y\'';
            $this->db->strOrder = 'sno DESC';
            $getData['icon'] = $this->getManageGoodsIconInfo();
        }

        if ($mode == 'restock' && $changeGoodsTableFl) {
            $this->goodsTable = DB_GOODS_SEARCH;
        }


        return $getData;
	}
}