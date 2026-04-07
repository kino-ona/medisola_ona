<?php

namespace Widget\Front\Proc;

use App;
use Logger;

/**
 * Magazine Home Widget
 * Fetches latest 3 WordPress magazine articles for home page display
 */
class MagazineHomeWidget extends \Widget\Front\Widget
{
    public function index()
    {
        try {
            // Load WordPress API component
            $wpApi = \App::load('\\Component\\WordPress\\WordPressApi');

            // Fetch latest 3 articles
            $magazineCount = 3;
            $magazineResult = $wpApi->getArticles(0, $magazineCount);
            $magazineArticles = $magazineResult['articles'];

            // Split into featured (first article) and secondary (2nd and 3rd)
            $featuredMagazine = isset($magazineArticles[0]) ? $magazineArticles[0] : null;
            $secondaryMagazines = array_slice($magazineArticles, 1, 2);

            // Set data for template
            $this->setData('featuredMagazine', $featuredMagazine);
            $this->setData('secondaryMagazines', $secondaryMagazines);
            $this->setData('hasMagazines', !empty($magazineArticles));
        } catch (\Exception $e) {
            // Log error and provide fallback empty state
            \Logger::channel('wordpress')->error('Home magazine widget error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->setData('featuredMagazine', null);
            $this->setData('secondaryMagazines', []);
            $this->setData('hasMagazines', false);
        }
    }
}
