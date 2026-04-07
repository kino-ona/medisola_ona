<?php

namespace Widget\Front\Magazine;

use App;
use Logger;

/**
 * Magazine Home Widget
 * Fetches latest 4 WordPress magazine articles for home page display
 */
class MagazineHomeWidget extends \Widget\Front\Widget
{
    public function index()
    {
        try {
            // Load WordPress API component
            $wpApi = \App::load('\\Component\\WordPress\\WordPressApi');

            // Fetch latest 4 articles
            $magazineCount = 4;
            $magazineResult = $wpApi->getArticles(0, $magazineCount);
            $magazineArticles = $magazineResult['articles'];

            // Set data for template
            $this->setData('magazines', $magazineArticles);
            $this->setData('hasMagazines', !empty($magazineArticles));

        } catch (\Exception $e) {
            // Log error and provide fallback empty state
            \Logger::channel('wordpress')->error('Home magazine widget error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->setData('magazines', []);
            $this->setData('hasMagazines', false);
        }
    }
}
