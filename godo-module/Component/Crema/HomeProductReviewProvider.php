<?php

namespace Component\Crema;

use App;
use Framework\Utility\SkinUtils;
use Logger;

class HomeProductReviewProvider
{
    public function buildTabsData($tabConfigSno, $tabConfigJson, $perTabLimit = 3, $initialVisibleCount = 3, $activeTabOnly = false)
    {
        $reviewLimit = (int)$perTabLimit;
        if ($reviewLimit <= 0) $reviewLimit = 3;
        if ($reviewLimit > 15) $reviewLimit = 15;

        $visibleCount = (int)$initialVisibleCount;
        if ($visibleCount <= 0) $visibleCount = 3;

        $tabConfig = $this->resolveTabConfig($tabConfigSno, $tabConfigJson);
        if (empty($tabConfig)) {
            return ['tabs' => [], 'hasTabs' => false, 'activeTab' => '', 'initialVisibleCount' => $visibleCount];
        }

        $tabConfig = $this->resolveDisplaySnoTabs($tabConfig);

        $goodsNos = $this->collectGoodsNos($tabConfig, 50);
        $goodsMap = $this->getGoodsMap($goodsNos);

        $reviewApi = App::load('\\Component\\Crema\\CremaReviewApi');

        $tabs = [];
        foreach ($tabConfig as $tabIdx => $tab) {
            $tabGoodsNos = array_slice((array)($tab['goodsNos'] ?? []), 0, 50);
            $isFirstTab = ($tabIdx === 0);
            $skipReviews = $activeTabOnly && !$isFirstTab;

            if ($skipReviews) {
                $products = $this->buildProductsWithoutReviews($tabGoodsNos, $goodsMap);
                $tabs[] = [
                    'tabKey' => (string)$tab['tabKey'],
                    'tabName' => (string)$tab['tabName'],
                    'products' => $products,
                    'hasProducts' => !empty($products),
                    'hasAnyReviews' => false,
                    'loaded' => false,
                ];
                continue;
            }

            $products = $this->buildProductsForGoodsNos($tabGoodsNos, $goodsMap, $reviewApi, $reviewLimit, $visibleCount);

            if (empty($products) && !$activeTabOnly) {
                continue;
            }

            $tabReviewCount = 0;
            foreach ($products as $p) {
                $tabReviewCount += $p['totalReviewCount'];
            }

            $tabs[] = [
                'tabKey' => (string)$tab['tabKey'],
                'tabName' => (string)$tab['tabName'],
                'products' => $products,
                'hasProducts' => !empty($products),
                'hasAnyReviews' => $tabReviewCount > 0,
                'loaded' => true,
            ];
        }

        if (empty($tabs)) {
            return ['tabs' => [], 'hasTabs' => false, 'activeTab' => '', 'initialVisibleCount' => $visibleCount];
        }

        $activeTab = $tabs[0]['tabKey'];
        foreach ($tabs as $idx => $tab) {
            $tabs[$idx]['isActive'] = ($tab['tabKey'] === $activeTab);
        }

        return [
            'tabs' => $tabs,
            'hasTabs' => true,
            'activeTab' => $activeTab,
            'initialVisibleCount' => $visibleCount,
            'tabConfigSno' => (string)$tabConfigSno,
            'reviewLimit' => $reviewLimit,
        ];
    }

    /**
     * AJAX: 특정 탭의 상품+리뷰 데이터만 반환
     */
    public function buildSingleTabData($tabKey, $tabConfigSno, $reviewLimit = 3, $initialVisibleCount = 3)
    {
        $reviewLimit = (int)$reviewLimit;
        if ($reviewLimit <= 0) $reviewLimit = 3;
        if ($reviewLimit > 15) $reviewLimit = 15;

        $visibleCount = (int)$initialVisibleCount;
        if ($visibleCount <= 0) $visibleCount = 3;

        $tabConfig = $this->resolveTabConfig($tabConfigSno, '');
        if (empty($tabConfig)) {
            return null;
        }

        $tabConfig = $this->resolveDisplaySnoTabs($tabConfig);

        $targetTab = null;
        foreach ($tabConfig as $tab) {
            if ((string)$tab['tabKey'] === (string)$tabKey) {
                $targetTab = $tab;
                break;
            }
        }

        if ($targetTab === null) {
            return null;
        }

        $tabGoodsNos = array_slice((array)($targetTab['goodsNos'] ?? []), 0, 50);
        $goodsMap = $this->getGoodsMap($tabGoodsNos);
        $reviewApi = App::load('\\Component\\Crema\\CremaReviewApi');

        $products = $this->buildProductsForGoodsNos($tabGoodsNos, $goodsMap, $reviewApi, $reviewLimit, $visibleCount);

        $tabReviewCount = 0;
        foreach ($products as $p) {
            $tabReviewCount += $p['totalReviewCount'];
        }

        return [
            'tabKey' => (string)$targetTab['tabKey'],
            'tabName' => (string)$targetTab['tabName'],
            'products' => $products,
            'hasProducts' => !empty($products),
            'hasAnyReviews' => $tabReviewCount > 0,
        ];
    }

    /**
     * AJAX: 특정 탭의 리뷰만 반환 (상품은 이미 SSR로 렌더링됨)
     * 반환: goodsNo => reviews[] 매핑
     */
    public function getTabReviews($tabKey, $tabConfigSno, $reviewLimit = 3)
    {
        $reviewLimit = (int)$reviewLimit;
        if ($reviewLimit <= 0) $reviewLimit = 3;
        if ($reviewLimit > 15) $reviewLimit = 15;

        $tabConfig = $this->resolveTabConfig($tabConfigSno, '');
        if (empty($tabConfig)) {
            return null;
        }

        $tabConfig = $this->resolveDisplaySnoTabs($tabConfig);

        $targetTab = null;
        foreach ($tabConfig as $tab) {
            if ((string)$tab['tabKey'] === (string)$tabKey) {
                $targetTab = $tab;
                break;
            }
        }

        if ($targetTab === null) {
            return null;
        }

        $tabGoodsNos = array_slice((array)($targetTab['goodsNos'] ?? []), 0, 50);
        $goodsMap = $this->getGoodsMap($tabGoodsNos);
        $reviewApi = App::load('\\Component\\Crema\\CremaReviewApi');

        $reviewsByGoods = [];
        foreach ($tabGoodsNos as $goodsNo) {
            $goodsNo = (int)$goodsNo;
            if ($goodsNo <= 0 || empty($goodsMap[$goodsNo])) {
                continue;
            }
            $goods = $goodsMap[$goodsNo];
            $goodsReviews = $reviewApi->getReviewsByGoodsNo($goods['goodsNo'], $reviewLimit);
            $items = [];
            foreach ((array)$goodsReviews as $review) {
                $reviewThumb = !empty($review['thumbnail']) ? $review['thumbnail'] : $goods['thumb'];
                $items[] = [
                    'goodsNo' => $goods['goodsNo'],
                    'goodsNm' => $goods['goodsNm'],
                    'reviewThumb' => $reviewThumb,
                    'reviewScore' => (int)($review['score'] ?? 0),
                    'reviewMessage' => (string)($review['message'] ?? ''),
                    'reviewUserName' => (string)($review['userName'] ?? ''),
                    'reviewCreatedAt' => (string)($review['createdAt'] ?? ''),
                ];
            }
            $reviewsByGoods[(string)$goodsNo] = $items;
        }

        return $reviewsByGoods;
    }

    /**
     * 상품 번호 배열로 상품+리뷰 데이터를 빌드 (기획전 그룹 등 외부 위젯용)
     */
    public function buildGroupProducts($goodsNos, $reviewLimit = 3, $visibleCount = 3)
    {
        $reviewLimit = (int)$reviewLimit;
        if ($reviewLimit <= 0) $reviewLimit = 3;
        if ($reviewLimit > 15) $reviewLimit = 15;

        $visibleCount = (int)$visibleCount;
        if ($visibleCount <= 0) $visibleCount = 3;

        $goodsMap = $this->getGoodsMap($goodsNos);
        $reviewApi = App::load('\\Component\\Crema\\CremaReviewApi');
        return $this->buildProductsForGoodsNos($goodsNos, $goodsMap, $reviewApi, $reviewLimit, $visibleCount);
    }

    /**
     * AJAX: 여러 상품의 리뷰를 일괄 반환 (기획전 그룹 위젯용)
     * 반환: goodsNo => reviews[] 매핑
     */
    public function getGroupReviews($goodsNos, $reviewLimit = 3)
    {
        $reviewLimit = (int)$reviewLimit;
        if ($reviewLimit <= 0) $reviewLimit = 3;
        if ($reviewLimit > 15) $reviewLimit = 15;

        $goodsNos = array_slice((array)$goodsNos, 0, 50);
        $goodsMap = $this->getGoodsMap($goodsNos);
        $reviewApi = App::load('\\Component\\Crema\\CremaReviewApi');

        $reviewsByGoods = [];
        foreach ($goodsNos as $goodsNo) {
            $goodsNo = (int)$goodsNo;
            if ($goodsNo <= 0 || empty($goodsMap[$goodsNo])) {
                continue;
            }
            $goods = $goodsMap[$goodsNo];
            $goodsReviews = $reviewApi->getReviewsByGoodsNo($goods['goodsNo'], $reviewLimit);
            $items = [];
            foreach ((array)$goodsReviews as $review) {
                $reviewThumb = !empty($review['thumbnail']) ? $review['thumbnail'] : $goods['thumb'];
                $items[] = [
                    'goodsNo' => $goods['goodsNo'],
                    'goodsNm' => $goods['goodsNm'],
                    'reviewThumb' => $reviewThumb,
                    'reviewScore' => (int)($review['score'] ?? 0),
                    'reviewMessage' => (string)($review['message'] ?? ''),
                    'reviewUserName' => (string)($review['userName'] ?? ''),
                    'reviewCreatedAt' => (string)($review['createdAt'] ?? ''),
                ];
            }
            $reviewsByGoods[(string)$goodsNo] = $items;
        }

        return $reviewsByGoods;
    }

    /**
     * AJAX: 특정 상품의 추가 리뷰 반환 (펼치기용)
     */
    public function getMoreReviews($goodsNo, $limit = 15)
    {
        $goodsNo = (int)$goodsNo;
        if ($goodsNo <= 0) {
            return [];
        }

        $limit = (int)$limit;
        if ($limit <= 0) $limit = 15;
        if ($limit > 15) $limit = 15;

        $goodsMap = $this->getGoodsMap([$goodsNo]);
        if (empty($goodsMap[$goodsNo])) {
            return [];
        }

        $goods = $goodsMap[$goodsNo];
        $reviewApi = App::load('\\Component\\Crema\\CremaReviewApi');
        $goodsReviews = $reviewApi->getReviewsByGoodsNo($goods['goodsNo'], $limit);

        $reviewItems = [];
        foreach ($goodsReviews as $review) {
            $reviewThumb = !empty($review['thumbnail']) ? $review['thumbnail'] : $goods['thumb'];
            $reviewItems[] = [
                'goodsNo' => $goods['goodsNo'],
                'goodsNm' => $goods['goodsNm'],
                'reviewThumb' => $reviewThumb,
                'reviewScore' => (int)($review['score'] ?? 0),
                'reviewMessage' => (string)($review['message'] ?? ''),
                'reviewUserName' => (string)($review['userName'] ?? ''),
                'reviewCreatedAt' => (string)($review['createdAt'] ?? ''),
            ];
        }

        return $reviewItems;
    }

    private function buildProductsForGoodsNos($tabGoodsNos, $goodsMap, $reviewApi, $reviewLimit, $visibleCount)
    {
        $products = [];
        foreach ($tabGoodsNos as $goodsNo) {
            $goodsNo = (int)$goodsNo;
            if ($goodsNo <= 0 || empty($goodsMap[$goodsNo])) {
                continue;
            }

            $goods = $goodsMap[$goodsNo];
            $goodsReviews = $reviewApi->getReviewsByGoodsNo($goods['goodsNo'], $reviewLimit);
            $reviewItems = [];
            if (!empty($goodsReviews)) {
                foreach ($goodsReviews as $review) {
                    $reviewThumb = !empty($review['thumbnail']) ? $review['thumbnail'] : $goods['thumb'];
                    $reviewItems[] = [
                        'goodsNo' => $goods['goodsNo'],
                        'goodsNm' => $goods['goodsNm'],
                        'reviewThumb' => $reviewThumb,
                        'reviewScore' => (int)($review['score'] ?? 0),
                        'reviewMessage' => (string)($review['message'] ?? ''),
                        'reviewUserName' => (string)($review['userName'] ?? ''),
                        'reviewCreatedAt' => (string)($review['createdAt'] ?? ''),
                    ];
                }
            }

            $products[] = [
                'goodsNo' => $goods['goodsNo'],
                'goodsNm' => $goods['goodsNm'],
                'goodsPrice' => $goods['goodsPrice'],
                'goodsFixedPrice' => $goods['goodsFixedPrice'],
                'showStrikePrice' => !empty($goods['showStrikePrice']),
                'goodsSalePrice' => $goods['goodsSalePrice'],
                'showGoodsSale' => !empty($goods['showGoodsSale']),
                'thumb' => $goods['thumb'],
                'reviews' => $reviewItems,
                'hasReviews' => !empty($reviewItems),
                'totalReviewCount' => count($reviewItems),
                'hasMoreReviews' => count($reviewItems) >= $reviewLimit,
            ];
        }
        return $products;
    }

    private function buildProductsWithoutReviews($tabGoodsNos, $goodsMap)
    {
        $products = [];
        foreach ($tabGoodsNos as $goodsNo) {
            $goodsNo = (int)$goodsNo;
            if ($goodsNo <= 0 || empty($goodsMap[$goodsNo])) {
                continue;
            }
            $goods = $goodsMap[$goodsNo];
            $products[] = [
                'goodsNo' => $goods['goodsNo'],
                'goodsNm' => $goods['goodsNm'],
                'goodsPrice' => $goods['goodsPrice'],
                'goodsFixedPrice' => $goods['goodsFixedPrice'],
                'showStrikePrice' => !empty($goods['showStrikePrice']),
                'goodsSalePrice' => $goods['goodsSalePrice'],
                'showGoodsSale' => !empty($goods['showGoodsSale']),
                'thumb' => $goods['thumb'],
                'reviews' => [],
                'hasReviews' => false,
                'totalReviewCount' => 0,
                'hasMoreReviews' => false,
            ];
        }
        return $products;
    }

    private function resolveTabConfig($tabConfigSno, $tabConfigJson)
    {
        $json = trim((string)$tabConfigJson);
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            Logger::channel('crema')->error('Invalid tabConfigJson format');
        }

        return $this->getPresetTabConfig((string)$tabConfigSno);
    }

    /**
     * displaySno 엔트리를 고도몰 상품진열 설정에서 탭 구조와 상품 목록으로 확장한다.
     * 탭진열형(displayType 07)이면 진열 내부 탭 구조를 그대로 사용한다.
     */
    private function resolveDisplaySnoTabs($tabConfig)
    {
        $resolved = [];

        foreach ($tabConfig as $tab) {
            $displaySno = (int)($tab['displaySno'] ?? 0);
            if ($displaySno <= 0) {
                $resolved[] = $tab;
                continue;
            }

            $expandedTabs = $this->expandDisplaySno($displaySno);
            if (empty($expandedTabs)) {
                continue;
            }

            foreach ($expandedTabs as $t) {
                $resolved[] = $t;
            }
        }

        return $resolved;
    }

    private function expandDisplaySno($displaySno)
    {
        try {
            $goods = App::load('\\Component\\Goods\\Goods');
            $displayData = $goods->getDisplayThemeInfo($displaySno);
        } catch (\Throwable $e) {
            Logger::channel('crema')->error('Failed to load display theme', [
                'displaySno' => $displaySno,
                'message' => $e->getMessage(),
            ]);
            return [];
        }

        if (empty($displayData) || ($displayData['displayFl'] ?? '') !== 'y') {
            return [];
        }

        $rawGoodsNo = (string)($displayData['goodsNo'] ?? '');
        if ($rawGoodsNo === '') {
            return [];
        }

        $mobileCd = isset($displayData['mobileThemeCd']) ? trim((string)$displayData['mobileThemeCd']) : '';
        $pcCd = isset($displayData['themeCd']) ? trim((string)$displayData['themeCd']) : '';
        $themeCd = ($mobileCd !== '') ? $mobileCd : $pcCd;
        $themeInfo = $this->loadThemeInfo($themeCd);

        $displayType = ($themeInfo !== null) ? (string)($themeInfo['displayType'] ?? '') : '';

        Logger::channel('crema')->info('expandDisplaySno theme resolve', [
            'displaySno' => $displaySno,
            'mobileCd' => $mobileCd,
            'pcCd' => $pcCd,
            'usedCd' => $themeCd,
            'displayType' => $displayType,
            'hasThemeInfo' => ($themeInfo !== null),
        ]);

        if ($displayType === '07') {
            return $this->expandTabTypeDisplay($displayData, $themeInfo, $rawGoodsNo);
        }

        $flattened = implode(INT_DIVISION, array_filter(explode(STR_DIVISION, $rawGoodsNo)));
        $goodsNos = $this->parseGoodsNos($flattened);
        if (empty($goodsNos)) {
            return [];
        }

        return [[
            'tabKey' => 'tab_0',
            'tabName' => (string)($displayData['themeNm'] ?? '전체'),
            'goodsNos' => $goodsNos,
        ]];
    }

    private function expandTabTypeDisplay($displayData, $themeInfo, $rawGoodsNo)
    {
        $detailSet = $themeInfo['detailSet'] ?? null;
        if (is_string($detailSet)) {
            $detailSet = @unserialize($detailSet);
        }

        $tabTitles = [];
        if (is_array($detailSet)) {
            $titleData = $detailSet;
            unset($titleData[0], $titleData[1]);
            $tabTitles = array_values($titleData);
        }

        $goodsGroups = explode(STR_DIVISION, $rawGoodsNo);
        $tabs = [];

        foreach ($goodsGroups as $groupIdx => $groupStr) {
            if (trim($groupStr) === '') {
                continue;
            }

            $goodsNos = $this->parseGoodsNos($groupStr);
            if (empty($goodsNos)) {
                continue;
            }

            $tabName = isset($tabTitles[$groupIdx]) ? (string)$tabTitles[$groupIdx] : '탭 ' . ($groupIdx + 1);

            $tabs[] = [
                'tabKey' => 'tab_' . $groupIdx,
                'tabName' => $tabName,
                'goodsNos' => $goodsNos,
            ];
        }

        return $tabs;
    }

    private function loadThemeInfo($themeCd)
    {
        try {
            $displayConfig = App::load('\\Component\\Display\\DisplayConfig');
            if ($themeCd === '') {
                $fallback = $displayConfig->getInfoThemeConfigCate('B', 'y');
                return !empty($fallback[0]) ? $fallback[0] : null;
            }
            return $displayConfig->getInfoThemeConfig($themeCd);
        } catch (\Throwable $e) {
            Logger::channel('crema')->error('Failed to load theme config', [
                'themeCd' => $themeCd,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function parseGoodsNos($goodsNoStr)
    {
        $parts = explode(INT_DIVISION, $goodsNoStr);
        $result = [];
        foreach ($parts as $v) {
            $v = trim($v);
            if ((int)$v > 0) {
                $result[] = $v;
            }
        }
        return $result;
    }

    private function getPresetTabConfig($tabConfigSno)
    {
        $presets = [
            'HOME_REVIEW_TAB_01' => [
                ['displaySno' => 17],
            ],
        ];

        return $presets[$tabConfigSno] ?? [];
    }

    private function collectGoodsNos($tabConfig, $perTabLimit)
    {
        $goodsNos = [];
        foreach ($tabConfig as $tab) {
            $tabGoodsNos = array_slice((array)($tab['goodsNos'] ?? []), 0, $perTabLimit);
            foreach ($tabGoodsNos as $goodsNo) {
                $goodsNo = (int)$goodsNo;
                if ($goodsNo > 0) {
                    $goodsNos[$goodsNo] = $goodsNo;
                }
            }
        }
        return array_values($goodsNos);
    }

    private function getGoodsMap($goodsNos)
    {
        if (empty($goodsNos)) {
            return [];
        }

        $db = App::load('DB');
        $safeNos = array_map('intval', $goodsNos);
        $safeNos = array_filter($safeNos, function ($no) {
            return $no > 0;
        });
        if (empty($safeNos)) {
            return [];
        }

        $inClause = implode(',', $safeNos);
        $displayFl = \Request::isMobile() ? 'goodsDisplayMobileFl' : 'goodsDisplayFl';
        $rows = $db->query_fetch(
            'SELECT g.goodsNo, g.goodsNm, g.goodsPrice, g.fixedPrice, g.goodsDiscountFl, g.goodsDiscount, g.goodsDiscountUnit, g.goodsDiscountGroup, g.goodsDiscountGroupMemberInfo, g.imagePath, g.imageStorage, gi.imageName
             FROM ' . \DB_GOODS . ' g
             LEFT JOIN ' . \DB_GOODS_IMAGE . " gi ON gi.goodsNo = g.goodsNo AND gi.imageKind = 'add1' AND gi.imageNo = 0
             WHERE g.goodsNo IN ({$inClause})
               AND g.delFl = 'n'
               AND g.applyFl = 'y'
               AND g.{$displayFl} = 'y'
               AND (g.goodsOpenDt IS NULL OR g.goodsOpenDt < NOW())"
        );

        $goodsDcPrice = 0;
        try {
            $goodsComponent = App::load('\\Component\\Goods\\Goods');
        } catch (\Throwable $e) {
            $goodsComponent = null;
        }

        $goodsMap = [];
        foreach ((array)$rows as $row) {
            $goodsNo = (int)($row['goodsNo'] ?? 0);
            if ($goodsNo <= 0) {
                continue;
            }

            $price = (int)($row['goodsPrice'] ?? 0);
            $fixedPrice = (int)($row['fixedPrice'] ?? 0);
            $goodsDcPrice = 0;
            if ($goodsComponent && ($row['goodsDiscountFl'] ?? '') === 'y') {
                $aGoodsInfo = [
                    'goodsNo' => $goodsNo,
                    'goodsPrice' => $price,
                    'goodsDiscountFl' => $row['goodsDiscountFl'],
                    'goodsDiscount' => (int)($row['goodsDiscount'] ?? 0),
                    'goodsDiscountUnit' => (string)($row['goodsDiscountUnit'] ?? 'price'),
                    'goodsDiscountGroup' => (string)($row['goodsDiscountGroup'] ?? ''),
                    'goodsDiscountGroupMemberInfo' => (string)($row['goodsDiscountGroupMemberInfo'] ?? ''),
                ];
                try {
                    $goodsDcPrice = (int)$goodsComponent->getGoodsDcPrice($aGoodsInfo);
                } catch (\Throwable $e) {
                    $goodsDcPrice = 0;
                }
            }
            $salePrice = max(0, $price - $goodsDcPrice);

            $thumb = '';
            if (!empty($row['imageName']) && !empty($row['imagePath']) && !empty($row['imageStorage'])) {
                try {
                    $thumbData = SkinUtils::imageViewStorageConfig(
                        $row['imageName'],
                        $row['imagePath'],
                        $row['imageStorage'],
                        300,
                        'goods',
                        false
                    );
                    $thumb = (string)($thumbData[0] ?? '');
                } catch (\Throwable $e) {
                    Logger::channel('crema')->error('Home review goods thumbnail resolve failed', [
                        'goodsNo' => $goodsNo,
                        'message' => $e->getMessage(),
                    ]);
                    $thumb = '';
                }
            }

            $goodsMap[$goodsNo] = [
                'goodsNo' => $goodsNo,
                'goodsNm' => (string)($row['goodsNm'] ?? ''),
                'goodsPrice' => number_format($price),
                'goodsFixedPrice' => number_format($fixedPrice),
                'showStrikePrice' => $fixedPrice > 0 && $fixedPrice !== $price,
                'goodsSalePrice' => number_format($salePrice),
                'showGoodsSale' => $goodsDcPrice > 0,
                'thumb' => $thumb,
            ];
        }

        return $goodsMap;
    }
}
