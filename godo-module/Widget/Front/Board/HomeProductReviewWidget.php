<?php

namespace Widget\Front\Board;

use App;
use Logger;

class HomeProductReviewWidget extends \Widget\Front\Widget
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
        } catch (\Exception $e) {
            Logger::channel('crema')->error('Front HomeProductReviewWidget error', [
                'message' => $e->getMessage(),
            ]);

            $this->setData('tabs', []);
            $this->setData('hasTabs', false);
            $this->setData('activeTab', '');
        }
    }
}
