<?php

namespace Controller\Front\Magazine;

use Component\WordPress\WordPressApi;
use Request;
use Logger;

/**
 * Magazine View Controller
 * Displays single WordPress magazine article with related articles
 */
class ViewController extends \Controller\Front\Controller
{
    public function index()
    {
        // Get request parameters
        $request = \Request::get()->toArray();
        $articleId = isset($request['id']) ? (int)$request['id'] : 0;

        if ($articleId <= 0) {
            // Redirect to list if no article ID
            header('Location: /magazine/list.php');
            exit;
        }

        try {
            // Instantiate WordPress API component
            $wpApi = new WordPressApi();

            // Fetch article
            $article = $wpApi->getArticle($articleId);

            if (!$article) {
                $this->setData('errorMessage', '매거진 글을 찾을 수 없습니다.');
                $this->setData('magazineView', null);
                $this->setData('recentArticles', []);
                return;
            }

            // Fetch recent articles for "More Articles" section
            $recentResult = $wpApi->getArticles(0, 5);
            $recentArticles = array_filter($recentResult['articles'], function($item) use ($articleId) {
                return $item['articleId'] !== $articleId;
            });
            $recentArticles = array_slice($recentArticles, 0, 4); // Get 4 recent articles (excluding current)

            // SEO 메타 태그 설정
            $metaTags = [];
            $snsShareMetaTags = [];

            // 기본 정보
            $description = htmlspecialchars(strip_tags($article['summary']), ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($article['subject'], ENT_QUOTES, 'UTF-8');
            $imageUrl = htmlspecialchars($article['imageUrl'], ENT_QUOTES, 'UTF-8');
            $date = htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8');
            $modified = isset($article['modified']) ? htmlspecialchars($article['modified'], ENT_QUOTES, 'UTF-8') : $date;

            // 1. 기본 SEO 메타 태그
            $metaTags[] = '<meta name="description" content="' . $description . '">';
            $metaTags[] = '<meta name="keywords" content="메디쏠라,매거진,건강정보,' . $title . '">';

            // 2. Open Graph 메타 태그
            $snsShareMetaTags[] = '<meta property="og:title" content="' . $title . ' - 푸드케어 레터">';
            $snsShareMetaTags[] = '<meta property="og:description" content="' . $description . '">';
            $snsShareMetaTags[] = '<meta property="og:image" content="' . $imageUrl . '">';
            $snsShareMetaTags[] = '<meta property="og:type" content="article">';
            $snsShareMetaTags[] = '<meta property="article:published_time" content="' . $date . '">';

            // 3. Twitter Card 메타 태그
            $snsShareMetaTags[] = '<meta name="twitter:card" content="summary_large_image">';
            $snsShareMetaTags[] = '<meta name="twitter:title" content="' . $title . '">';
            $snsShareMetaTags[] = '<meta name="twitter:description" content="' . $description . '">';
            $snsShareMetaTags[] = '<meta name="twitter:image" content="' . $imageUrl . '">';

            // 4. JSON-LD 구조화 데이터
            $jsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $article['subject'],
                'image' => $article['imageUrl'],
                'datePublished' => $article['date'],
                'dateModified' => $modified,
                'author' => [
                    '@type' => 'Organization',
                    'name' => '메디쏠라'
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => '메디쏠라',
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => 'https://medisola.co.kr/data/skin/front/medisola_dev/images/logo.png'
                    ]
                ],
                'description' => $article['summary']
            ];
            $snsShareMetaTags[] = '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

            // Set data for template
            $this->setData('magazineView', $article);
            $this->setData('recentArticles', $recentArticles);
            $this->setData('pageTitle', $title . ' - 푸드케어 레터');
            $this->setData('headerMeta', $metaTags);
            $this->setData('snsShareMetaTag', $snsShareMetaTags);

        } catch (\Exception $e) {
            \Logger::channel('wordpress')->error('Magazine view controller error', [
                'message' => $e->getMessage(),
                'articleId' => $articleId,
                'trace' => $e->getTraceAsString()
            ]);

            // Set error data
            $this->setData('errorMessage', '매거진 글을 불러오는 중 오류가 발생했습니다.');
            $this->setData('magazineView', null);
            $this->setData('recentArticles', []);
            $this->setData('pageTitle', '푸드케어 레터');
        }
    }
}
