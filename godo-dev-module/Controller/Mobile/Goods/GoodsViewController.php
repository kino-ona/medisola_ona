<?php

namespace Controller\Mobile\Goods;

use Component\Wm\UseGift;

class GoodsViewController extends \Bundle\Controller\Mobile\Goods\GoodsViewController
{
    public function index()
    {
        parent::index();

        $getValue = \Request::get()->all();

        $goodsIcon = \App::load('\\Component\\Wm\\GoodsIcon');
        $iconData = $goodsIcon->getGoodsIcon($getValue['goodsNo']);

        $goodsBanner = \App::load('\\Component\\Wm\\GoodsBanner');
        $bannerData = $goodsBanner->getGoodsBanner($getValue['goodsNo']);


        $this->setData('iconData', $iconData);
        $this->setData('bannerData', $bannerData);

        // 웹앤모바일 2023-06-09 선물하기
        $getBanner = \App::load(UseGift::class);
        $goodsView = $this->getData("goodsView");
        $goodsView = $getBanner->GiftUseSet($goodsView);
        $this->setData('goodsView', $goodsView);
        // 웹앤모바일 2023-06-09 선물하기

        // 웹앤모바일 정기결제 기능 추가 ================================================== START
        $obj = new \Component\Subscription\Subscription();
        if ($obj->applyFl) {
            if ($goodsView['isSubscription'] > 0) {
                $this->getView()->setPageName("goods/goods_view_subscription");
            }
        }
        // 웹앤모바일 정기결제 기능 추가 ================================================== END

        // 일반 상품 <-> 정기 결제 상품 연결 로직 =========================================== START
        if ($goodsView['isSubscription'] == 0 && $goodsView['linkedSubscriptionGoodsNo'] > 0) {
            // 일반 상품 → 정기 결제 상품 링크
            $this->setData('linkedSubscriptionGoodsNo', $goodsView['linkedSubscriptionGoodsNo']);
        } elseif ($goodsView['isSubscription'] == 1) {
            // 정기 결제 상품 → 일반 상품 찾기 (역방향 조회) - Subscription 컴포넌트 재사용
            $linkedRegularGoodsNo = $obj->getLinkedRegularGoodsNo($goodsView['goodsNo']);
            if ($linkedRegularGoodsNo) {
                $this->setData('linkedRegularGoodsNo', $linkedRegularGoodsNo);

                // 연결된 일반 상품의 전체 정보 조회
                $goods = \App::load('\\Component\\Goods\\Goods');
                $linkedGoodsData = $goods->getGoodsView($linkedRegularGoodsNo);

                // 일반 상품의 optionName이 '*기간'이고 addGoods가 있으면 전달
                if (!empty($linkedGoodsData['optionName']) &&
                    $linkedGoodsData['optionName'] === '*기간' &&
                    !empty($linkedGoodsData['addGoods'])) {

                    $this->setData('linkedRegularAddGoods', $linkedGoodsData['addGoods']);
                }
            }
        }
        // 일반 상품 <-> 정기 결제 상품 연결 로직 =========================================== END

        // 정기결제 상품의 첫 배송일 자동 계산 ============================================== START
        if ($goodsView['isSubscription'] == 1 && $goodsView['useFirst'] == 1) {
            $firstDeliveryObj = \App::load('Component\\Wm\\FirstDelivery');
            $autoFirstDeliveryDate = $firstDeliveryObj->calculateNextAvailableDeliveryDate($goodsView['goodsNo']);

            if ($autoFirstDeliveryDate) {
                $this->setData('autoFirstDeliveryDate', $autoFirstDeliveryDate);
            }
        }
        // 정기결제 상품의 첫 배송일 자동 계산 ============================================== END

        // 나만의 식단 플랜 상품 감지 ================================================ START
        // 🔧 임시 CSV Export 기능 (영양 정보 입력용)

        if (isset($getValue['exportCSV']) && $getValue['exportCSV'] == '1') {
            $this->exportAddGoodsCsv($getValue['goodsNo']);
            exit; // CSV 다운로드 후 종료
        }

        if ($getValue['goodsNo'] == 1000000445) { // 임시 테스트 코드
        // if (isset($goodsView['isCustomDiet']) && $goodsView['isCustomDiet'] == 1) {
            $dietFinder = \App::load('\\Component\\DietFinder\\DietFinder');
            // goodsView['addGoods']를 직접 전달하여 "메뉴" 그룹의 addGoodsList 사용
            $menuItems = $dietFinder->getMenuItemsForCustomDiet($getValue['goodsNo'], $goodsView['addGoods'] ?? null);
            $goodsOptions = $dietFinder->getGoodsOptions($getValue['goodsNo']);

            // V0 UI용 JSON 데이터 전달
            $this->setData('addGoodsNutritionJson', json_encode($menuItems, JSON_UNESCAPED_UNICODE));
            $this->setData('optionsJson', json_encode($goodsOptions, JSON_UNESCAPED_UNICODE));
            $this->setData('menuItemsCount', count($menuItems));

            // *추가 가격 그룹에서 프리미엄 addGoods 아이템 추출
            $premiumAddGoodsInfo = null;
            if (is_array($goodsView['addGoods'] ?? null)) {
                foreach ($goodsView['addGoods'] as $group) {
                    if (isset($group['title']) && $group['title'] === '*추가 가격' && !empty($group['addGoodsList'])) {
                        $premiumItem = $group['addGoodsList'][0];
                        $premiumAddGoodsInfo = [
                            'addGoodsNo' => $premiumItem['addGoodsNo'],
                            'goodsPrice' => (int)($premiumItem['goodsPrice'] ?? 0),
                            'goodsNm' => $premiumItem['goodsNm'] ?? '',
                        ];
                        break;
                    }
                }
            }
            $this->setData('premiumAddGoodsJson', json_encode($premiumAddGoodsInfo, JSON_UNESCAPED_UNICODE));

            // 디버깅용 데이터 (그룹별 아이템 이름·가격 포함)
            $debugInfo = [
                'addGoodsFl' => $goodsView['addGoodsFl'] ?? null,
                'addGoodsCount' => is_array($goodsView['addGoods'] ?? null) ? count($goodsView['addGoods']) : 0,
                'premiumAddGoods' => $premiumAddGoodsInfo,
                'groups' => []
            ];
            if (is_array($goodsView['addGoods'] ?? null)) {
                foreach ($goodsView['addGoods'] as $group) {
                    $groupInfo = [
                        'title' => $group['title'] ?? 'no title',
                        'addGoodsListCount' => isset($group['addGoodsList']) ? count($group['addGoodsList']) : 0,
                        'items' => []
                    ];
                    if (!empty($group['addGoodsList'])) {
                        foreach (array_slice($group['addGoodsList'], 0, 5) as $item) {
                            $groupInfo['items'][] = [
                                'no' => $item['addGoodsNo'] ?? '',
                                'nm' => $item['goodsNm'] ?? '',
                                'price' => $item['goodsPrice'] ?? 0,
                            ];
                        }
                    }
                    $debugInfo['groups'][] = $groupInfo;
                }
            }
            $this->setData('debugInfo', json_encode($debugInfo, JSON_UNESCAPED_UNICODE));

            // responseSno가 있으면 추천 데이터도 전달
            if (isset($getValue['responseSno']) && !empty($getValue['responseSno'])) {
                $conditionsResult = $dietFinder->getConditionsFromResponse(intval($getValue['responseSno']));
                $this->setData('conditionsJson', json_encode($conditionsResult, JSON_UNESCAPED_UNICODE));
                $this->setData('userDataJson', json_encode($conditionsResult['userData'] ?? null, JSON_UNESCAPED_UNICODE));
                $this->setData('recommendedLinesJson', json_encode($conditionsResult['recommendedLines'] ?? [], JSON_UNESCAPED_UNICODE));
            }

            // 헤더/푸터 숨김 설정 (고도몰 템플릿 엔진 방식)
            // tpls.header_inc = false → outline_header: 'noprint'와 동일 효과
            $this->setData('tpls', [
                'header_inc' => false,  // 상단 감춤
                'footer_inc' => false   // 하단 감춤
            ]);


            // 커스텀 다이어트 전용 템플릿 사용
            if (isset($getValue['view']) && $getValue['view'] === 'summary') {
                $this->getView()->setPageName("goods/goods_view_custom_diet_summary");
            } else {
                $this->getView()->setPageName("goods/goods_view_custom_diet");
            }
        }
        // 나만의 식단 플랜 상품 감지 ================================================ END
    }

    /**
     * 🔧 임시 CSV Export: es_addGoods 테이블 영양 정보 입력용
     * 사용법: /m/goods/goods_view.php?goodsNo=12345&exportCsv=1
     *
     * @param int $goodsNo 상품번호
     */
    private function exportAddGoodsCsv($goodsNo)
    {
        $db = \App::load('DB');

        // 1. goodsNo의 addGoods JSON에서 "메뉴" 그룹 추출
        $arrBind = [];
        $db->bind_param_push($arrBind, 'i', $goodsNo);

        $strSQL = "SELECT addGoods FROM " . DB_GOODS . " WHERE goodsNo = ?";
        $goodsData = $db->query_fetch($strSQL, $arrBind, false);

        if (empty($goodsData['addGoods'])) {
            die('❌ ERROR: 해당 상품에 addGoods 데이터가 없습니다.');
        }

        // 2. JSON 파싱 및 "메뉴" 그룹 찾기
        $addGoodsJson = json_decode($goodsData['addGoods'], true);
        if (!is_array($addGoodsJson)) {
            die('❌ ERROR: addGoods JSON 파싱 실패');
        }

        $menuAddGoodsNos = [];
        foreach ($addGoodsJson as $group) {
            if (isset($group['title']) && $group['title'] === '메뉴' && !empty($group['addGoods'])) {
                $menuAddGoodsNos = $group['addGoods'];
                break;
            }
        }

        if (empty($menuAddGoodsNos)) {
            die('❌ ERROR: "메뉴" 그룹에 addGoods가 없습니다.');
        }

        // 3. es_addGoods 테이블에서 해당 메뉴들 조회
        $placeholders = implode(',', array_fill(0, count($menuAddGoodsNos), '?'));
        $arrBind = [];
        foreach ($menuAddGoodsNos as $addGoodsNo) {
            $db->bind_param_push($arrBind, 'i', $addGoodsNo);
        }

        $strSQL = "SELECT
                    addGoodsNo,
                    goodsNm,
                    goodsDescription,
                    name_en,
                    category,
                    product_weight,
                    nutrition_calories,
                    nutrition_protein,
                    nutrition_carbs,
                    nutrition_sugar,
                    nutrition_fat,
                    nutrition_saturated_fat,
                    nutrition_trans_fat,
                    nutrition_sodium,
                    nutrition_omega3,
                    nutrition_cholesterol,
                    nutrition_fiber,
                    nutrition_tags,
                    main_ingredients,
                    allergens,
                    disease_type,
                    is_new,
                    recommend_reasons
                   FROM " . DB_ADD_GOODS . "
                   WHERE addGoodsNo IN ({$placeholders})
                   ORDER BY FIELD(addGoodsNo, " . implode(',', $menuAddGoodsNos) . ")";

        $result = $db->query_fetch($strSQL, $arrBind);

        if (empty($result)) {
            die('❌ ERROR: 조회된 메뉴가 없습니다.');
        }

        // 4. CSV 생성 및 다운로드
        $filename = 'addGoods_nutrition_' . $goodsNo . '_' . date('YmdHis') . '.csv';

        // HTTP 헤더 설정
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // UTF-8 BOM 추가 (엑셀에서 한글 깨짐 방지)
        echo "\xEF\xBB\xBF";

        // CSV 파일 핸들 생성 (stdout)
        $output = fopen('php://output', 'w');

        // CSV 헤더 (한글 + 영문)
        $headers = [
            'addGoodsNo (ID)',
            'goodsNm (상품명)',
            'goodsDescription (상품설명)',
            'name_en (영문명)',
            'category (카테고리-콤마구분)',
            'product_weight (중량g)',
            'nutrition_calories (칼로리kcal)',
            'nutrition_protein (단백질g)',
            'nutrition_carbs (탄수화물g)',
            'nutrition_sugar (당g)',
            'nutrition_fat (지방g)',
            'nutrition_saturated_fat (포화지방g)',
            'nutrition_trans_fat (트랜스지방g)',
            'nutrition_sodium (나트륨mg)',
            'nutrition_omega3 (오메가3mg)',
            'nutrition_cholesterol (콜레스테롤mg)',
            'nutrition_fiber (식이섬유g)',
            'nutrition_tags (영양태그)',
            'main_ingredients (주재료JSON)',
            'allergens (알레르기JSON)',
            'disease_type (질환케어)',
            'is_new (신상품)',
            'recommend_reasons (추천사유JSON)'
        ];
        fputcsv($output, $headers);

        // CSV 데이터 행
        foreach ($result as $row) {
            fputcsv($output, [
                $row['addGoodsNo'],
                $row['goodsNm'],
                $row['goodsDescription'] ?? '',
                $row['name_en'] ?? '',
                $row['category'] ?? '',
                $row['product_weight'] ?? '',
                $row['nutrition_calories'] ?? '',
                $row['nutrition_protein'] ?? '',
                $row['nutrition_carbs'] ?? '',
                $row['nutrition_sugar'] ?? '',
                $row['nutrition_fat'] ?? '',
                $row['nutrition_saturated_fat'] ?? '',
                $row['nutrition_trans_fat'] ?? '',
                $row['nutrition_sodium'] ?? '',
                $row['nutrition_omega3'] ?? '',
                $row['nutrition_cholesterol'] ?? '',
                $row['nutrition_fiber'] ?? '',
                $row['nutrition_tags'] ?? '',
                $row['main_ingredients'] ?? '',
                $row['allergens'] ?? '',
                $row['disease_type'] ?? '',
                $row['is_new'] ?? '0',
                $row['recommend_reasons'] ?? ''
            ]);
        }

        fclose($output);
    }
}