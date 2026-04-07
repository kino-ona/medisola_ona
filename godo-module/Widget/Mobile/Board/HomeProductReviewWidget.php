<?php

namespace Widget\Mobile\Board;

use App;
use Logger;

class HomeProductReviewWidget extends \Widget\Mobile\Widget
{
    public function index()
    {
        try {
            $provider = App::load('\\Component\\Crema\\HomeProductReviewProvider');

            $tabConfigSno = (string)$this->getData('tabConfigSno');
            $tabConfigJson = (string)$this->getData('tabConfigJson');
            $perTabLimit = (int)$this->getData('perTabLimit');
            $initialVisibleCount = (int)$this->getData('initialVisibleCount');

            if ($tabConfigSno === '') {
                $tabConfigSno = 'HOME_REVIEW_TAB_01';
            }

            $result = $provider->buildTabsData($tabConfigSno, $tabConfigJson, $perTabLimit, $initialVisibleCount, true);

            $this->setData($result);

            $sectionTitle = (string)$this->getData('sectionTitle');
            $sectionSubtitle = (string)$this->getData('sectionSubtitle');
            $this->setData('sectionTitle', $sectionTitle !== '' ? $sectionTitle : '맞춤형 식단 선택형 메뉴');
            $this->setData('sectionSubtitle', $sectionSubtitle);
        } catch (\Exception $e) {
            Logger::channel('crema')->error('Mobile HomeProductReviewWidget error', [
                'message' => $e->getMessage(),
            ]);

            $this->setData('tabs', []);
            $this->setData('hasTabs', false);
            $this->setData('activeTab', '');
        }
    }
}
