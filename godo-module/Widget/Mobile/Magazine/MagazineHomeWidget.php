<?php

namespace Widget\Mobile\Magazine;

/**
 * Magazine Home Widget (Mobile)
 * Fetches latest 3 WordPress magazine articles for mobile home page display
 */
class MagazineHomeWidget extends \Widget\Mobile\Widget
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
