<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2025, Medisola.
 * @link https://weare.medisola.co.kr
 */

namespace Controller\Admin\Goods;

use App;
use Request;
use Exception;

/**
 * 영양 정보 관리 목록 컨트롤러
 *
 * @package Controller\Admin\Goods
 */
class NutritionInfoListController extends \Controller\Admin\Controller
{
    public function index()
    {
        // --- 메뉴 설정
        $this->callMenu('goods', 'addGoods', 'nutritionInfo');

        try {
            $getValue = Request::get()->toArray();

            $db = App::load('DB');

            // --- 검색 설정
            $arrWhere = [];
            $arrBind = [];

            // 상품 메뉴 필터 (es_goods의 addGoods JSON에서 "메뉴" title의 addGoodsNo 목록)
            gd_isset($getValue['goodsNo'], '1000000445');
            if (!empty($getValue['goodsNo'])) {
                $goodsBind = [];
                $db->bind_param_push($goodsBind, 's', $getValue['goodsNo']);
                $goodsData = $db->query_fetch(
                    "SELECT addGoods FROM " . DB_GOODS . " WHERE goodsNo = ?",
                    $goodsBind, false
                );

                $menuAddGoodsNos = [];
                if (!empty($goodsData['addGoods'])) {
                    $addGoodsJson = json_decode(stripslashes($goodsData['addGoods']), true);
                    if (is_array($addGoodsJson)) {
                        foreach ($addGoodsJson as $group) {
                            if (isset($group['title']) && $group['title'] === '메뉴') {
                                $menuAddGoodsNos = array_merge($menuAddGoodsNos, $group['addGoods'] ?? []);
                            }
                        }
                    }
                }

                if (!empty($menuAddGoodsNos)) {
                    $placeholders = implode(',', array_fill(0, count($menuAddGoodsNos), '?'));
                    $arrWhere[] = "ag.addGoodsNo IN ({$placeholders})";
                    foreach ($menuAddGoodsNos as $no) {
                        $db->bind_param_push($arrBind, 's', $no);
                    }
                } else {
                    // 해당 상품에 메뉴 항목이 없음
                    $arrWhere[] = "1 = 0";
                }
            }

            // 키워드 검색 (상품명 또는 상품코드)
            if (!empty($getValue['keyword'])) {
                $searchKey = $getValue['key'] ?? 'all';
                if ($searchKey === 'goodsNm') {
                    $arrWhere[] = "ag.goodsNm LIKE concat('%',?,'%')";
                    $db->bind_param_push($arrBind, 's', $getValue['keyword']);
                } elseif ($searchKey === 'goodsCd') {
                    $arrWhere[] = "ag.goodsCd LIKE concat('%',?,'%')";
                    $db->bind_param_push($arrBind, 's', $getValue['keyword']);
                } else {
                    $arrWhere[] = "(ag.goodsNm LIKE concat('%',?,'%') OR ag.goodsCd LIKE concat('%',?,'%'))";
                    $db->bind_param_push($arrBind, 's', $getValue['keyword']);
                    $db->bind_param_push($arrBind, 's', $getValue['keyword']);
                }
            }

            // 카테고리 필터
            if (!empty($getValue['category'])) {
                $arrWhere[] = "ag.category = ?";
                $db->bind_param_push($arrBind, 's', $getValue['category']);
            }

            // 질환케어 필터
            if (!empty($getValue['diseaseType'])) {
                $arrWhere[] = "ag.disease_type LIKE concat('%',?,'%')";
                $db->bind_param_push($arrBind, 's', $getValue['diseaseType']);
            }

            // 음식스타일 필터
            if (!empty($getValue['foodStyle'])) {
                $arrWhere[] = "ag.food_style = ?";
                $db->bind_param_push($arrBind, 's', $getValue['foodStyle']);
            }

            // --- 정렬 설정
            $sort = gd_isset($getValue['sort'], 'ag.regDt desc');

            // --- 페이지 기본 설정
            gd_isset($getValue['page'], 1);
            gd_isset($getValue['pageNum'], 50);

            $page = App::load('\\Component\\Page\\Page', $getValue['page'], 0, 0, $getValue['pageNum']);
            $page->setCache(true);

            // 전체 레코드 수
            $strSQLAmount = 'SELECT COUNT(*) AS cnt FROM ' . DB_ADD_GOODS;
            if ($page->hasRecodeCache('amount') === false) {
                $res = $db->query_fetch($strSQLAmount, null, false);
                $page->recode['amount'] = $res['cnt'];
            }

            // 검색 레코드 수
            $strSQLCount = 'SELECT COUNT(*) AS cnt FROM ' . DB_ADD_GOODS . ' AS ag';
            if (!empty($arrWhere)) {
                $strSQLCount .= ' WHERE ' . implode(' AND ', $arrWhere);
            }
            if ($page->hasRecodeCache('total') === false) {
                $res = $db->query_fetch($strSQLCount, $arrBind, false);
                $page->recode['total'] = $res['cnt'];
            }
            $page->setUrl(Request::getQueryString());
            $page->setPage();

            // --- 데이터 조회
            $db->strField = "ag.addGoodsNo, ag.goodsNm, ag.goodsCd,
                ag.nutrition_calories, ag.nutrition_protein, ag.nutrition_carbs,
                ag.nutrition_sugar, ag.nutrition_fat, ag.nutrition_saturated_fat,
                ag.nutrition_trans_fat, ag.nutrition_sodium, ag.nutrition_omega3,
                ag.nutrition_cholesterol, ag.nutrition_fiber,
                ag.product_weight, ag.disease_type, ag.nutrition_tags,
                ag.category, ag.food_style, ag.meal_type,
                ag.main_ingredients, ag.allergens,
                ag.name_en, ag.description";

            if (!empty($arrWhere)) {
                $db->strWhere = implode(' AND ', $arrWhere);
            }
            $db->strOrder = $sort;
            $db->strLimit = $page->recode['start'] . ',' . $getValue['pageNum'];

            $query = $db->query_complete();
            $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_ADD_GOODS . ' AS ag ' . implode(' ', $query);
            $data = $db->query_fetch($strSQL, $arrBind);

            // JSON 필드 디코딩 (main_ingredients, allergens)
            if ($data) {
                foreach ($data as &$row) {
                    foreach (['main_ingredients', 'allergens'] as $jsonField) {
                        $val = $row[$jsonField] ?? '';
                        if (!empty($val)) {
                            $decoded = json_decode($val, true);
                            if (is_array($decoded)) {
                                $row[$jsonField . '_display'] = implode(', ', $decoded);
                            } else {
                                $row[$jsonField . '_display'] = $val;
                            }
                        } else {
                            $row[$jsonField . '_display'] = '';
                        }
                    }
                }
                unset($row);
            }

            // --- 카테고리 목록 (필터용)
            $categoryOptions = [
                '' => '전체',
                '해산물' => '해산물',
                '육류' => '육류',
                '식단백' => '식단백',
                '샐러드' => '샐러드',
                '국/찌개' => '국/찌개',
                '반찬' => '반찬',
                '간식' => '간식',
                '음료' => '음료',
            ];

            // --- 음식스타일 목록 (필터용)
            $foodStyleOptions = [
                '' => '전체',
                '한식' => '한식',
                '양식' => '양식',
                '일식' => '일식',
                '중식' => '중식',
                '에스닉' => '에스닉',
            ];

            // --- 검색어 옵션
            $searchKeyOptions = [
                'all' => '전체',
                'goodsNm' => '상품명',
                'goodsCd' => '자체상품코드',
            ];

            $this->setData('data', gd_htmlspecialchars_stripslashes($data));
            $this->setData('search', $getValue);
            $this->setData('page', $page);
            $this->setData('categoryOptions', $categoryOptions);
            $this->setData('foodStyleOptions', $foodStyleOptions);
            $this->setData('searchKeyOptions', $searchKeyOptions);

            $this->getView()->setPageName('goods/nutrition_info_list.php');

        } catch (Exception $e) {
            throw $e;
        }
    }
}
