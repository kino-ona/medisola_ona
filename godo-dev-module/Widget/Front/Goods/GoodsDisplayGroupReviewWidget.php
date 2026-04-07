<?php

namespace Widget\Front\Goods;

use App;
use Logger;
use Request;

class GoodsDisplayGroupReviewWidget extends \Widget\Front\Widget
{
    public function index()
    {
        try {
            $sno = (int)$this->getData('sno');
            $reviewLimit = (int)$this->getData('reviewLimit') ?: 3;
            $initialVisibleCount = (int)$this->getData('initialVisibleCount') ?: 3;

            if ($sno <= 0) {
                $this->setEmptyData();
                return;
            }

            $goods = App::load('\\Component\\Goods\\Goods');
            $eventData = $goods->getDisplayThemeInfo($sno);

            if (empty($eventData) || ($eventData['displayFl'] ?? '') !== 'y') {
                $this->setEmptyData();
                return;
            }

            Request::get()->set('mainLinkData', [
                'mainThemeSno' => $eventData['sno'],
                'mainThemeNm' => $eventData['themeNm'],
                'mainThemeDevice' => $eventData['mobileFl'],
            ]);

            $otherEventData = $goods->getDisplayOtherEventList();

            $eventGroup = App::load('\\Component\\Promotion\\EventGroupTheme');
            $groupDataList = $eventGroup->getSimpleData($sno);

            if (empty($groupDataList)) {
                $this->setEmptyData();
                return;
            }

            $provider = App::load('\\Component\\Crema\\HomeProductReviewProvider');
            $groups = [];

            foreach ($groupDataList as $idx => $group) {
                $goodsNos = $this->parseGroupGoodsNos($group['groupGoodsNo'] ?? '');
                if (empty($goodsNos)) continue;

                $groupTitleHtml = $this->buildGroupTitle($group, 'pc');
                $products = $provider->buildGroupProducts($goodsNos, $reviewLimit, $initialVisibleCount);

                $groups[] = [
                    'groupSno' => $group['sno'],
                    'groupName' => $group['groupName'] ?? '',
                    'groupTitleHtml' => $groupTitleHtml,
                    'products' => $products,
                    'hasProducts' => !empty($products),
                    'isFirst' => ($idx === 0),
                ];
            }

            $this->setData('groups', $groups);
            $this->setData('hasGroups', !empty($groups));
            $this->setData('eventThemeName', $eventData['themeNm'] ?? '');
            $this->setData('eventThemePcContents', $eventData['pcContents'] ?? '');
            $this->setData('otherEventData', $otherEventData ?: []);
            $this->setData('reviewLimit', $reviewLimit);
            $this->setData('initialVisibleCount', $initialVisibleCount);

        } catch (\Exception $e) {
            Logger::channel('crema')->error('Front GoodsDisplayGroupReviewWidget error', [
                'message' => $e->getMessage(),
            ]);
            $this->setEmptyData();
        }
    }

    private function setEmptyData()
    {
        $this->setData('groups', []);
        $this->setData('hasGroups', false);
    }

    private function parseGroupGoodsNos($rawGoodsNo)
    {
        $rawGoodsNo = trim($rawGoodsNo);
        if ($rawGoodsNo === '') return [];

        $flattened = implode(INT_DIVISION, array_filter(explode(STR_DIVISION, $rawGoodsNo)));
        return array_values(array_filter(
            array_map('trim', explode(INT_DIVISION, $flattened)),
            function ($v) { return (int)$v > 0; }
        ));
    }

    private function buildGroupTitle($group, $device)
    {
        $imageField = ($device === 'mobile') ? 'groupNameImageMobile' : 'groupNameImagePc';
        $imageName = trim($group[$imageField] ?? '');

        if ($imageName !== '') {
            return "<img src='/data/event_group/" . $imageName . "' border='0' />";
        }

        return $group['groupName'] ?? '';
    }
}
