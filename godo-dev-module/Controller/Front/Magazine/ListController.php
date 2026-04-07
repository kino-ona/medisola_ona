<?php

namespace Controller\Front\Magazine;

use Component\WordPress\WordPressApi;
use Request;
use Logger;

/**
 * Magazine List Controller
 * Displays WordPress magazine articles in a paginated list
 */
class ListController extends \Controller\Front\Controller
{
    public function index()
    {
        // Get request parameters
        $request = \Request::get()->toArray();
        $page = isset($request['page']) ? max(0, (int)$request['page'] - 1) : 0; // Convert to 0-indexed
        $perPage = 14; // Items per page

        try {
            // Instantiate WordPress API component
            $wpApi = new WordPressApi();

            // Fetch articles
            $result = $wpApi->getArticles($page, $perPage);

            // Prepare pagination data
            $pagination = [
                'currentPage' => $page + 1, // Convert back to 1-indexed for display
                'totalPages' => $result['totalPages'],
                'perPage' => $perPage,
                'hasNext' => ($page + 1) < $result['totalPages'],
                'hasPrev' => $page > 0,
                'nextPage' => $page + 2,
                'prevPage' => $page,
            ];

            // Group articles for progressive grid layout
            $articles = $result['articles'];
            $this->setData('heroArticle', isset($articles[0]) ? $articles[0] : null);
            $this->setData('twoColArticles', array_slice($articles, 1, 4)); // indices 1-4
            $this->setData('threeColArticles', array_slice($articles, 5)); // indices 5+
            $this->setData('hasArticles', !empty($articles));
            $this->setData('pagination', $pagination);
            $this->setData('pageTitle', '푸드케어 레터');

        } catch (\Exception $e) {
            \Logger::channel('wordpress')->error('Magazine list controller error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Set empty data on error
            $this->setData('heroArticle', null);
            $this->setData('twoColArticles', []);
            $this->setData('threeColArticles', []);
            $this->setData('hasArticles', false);
            $this->setData('pagination', [
                'currentPage' => 1,
                'totalPages' => 0,
                'perPage' => $perPage,
                'hasNext' => false,
                'hasPrev' => false,
            ]);
            $this->setData('pageTitle', '푸드케어 레터');
            $this->setData('errorMessage', '레터 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }
}
