<?php

namespace Component\WordPress;

use UserFilePath;
use Logger;
use Exception;


/**
 * WordPress REST API Integration Component
 * Fetches magazine articles from blog.medisola.co.kr via WordPress.com public API
 */
class WordPressApi
{
    const API_BASE_URL = 'https://public-api.wordpress.com/wp/v2/sites/blog.medisola.co.kr';
    const CACHE_TTL = 1800; // 30 minutes
    const CONNECT_TIMEOUT = 10; // 10 seconds
    // Exclude specific WordPress category IDs from list API (comma-separated)
    const EXCLUDED_CATEGORY_IDS = '782413856';

    private $cacheDir;

    public function __construct()
    {
        $this->cacheDir = UserFilePath::data('wp_magazine_cache');

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get list of articles
     *
     * @param int $page Page number (0-indexed, will be converted to 1-indexed for API)
     * @param int $perPage Items per page
     * @param string $order Sort order (asc/desc)
     * @param string $orderBy Sort field (date/modified/title)
     * @return array ['articles' => array, 'totalPages' => int, 'currentPage' => int]
     */
    public function getArticles($page = 0, $perPage = 12, $order = 'desc', $orderBy = 'date')
    {
        $cacheKey = "wp_posts_list_{$page}_{$perPage}_{$order}_{$orderBy}_exclude_" . self::EXCLUDED_CATEGORY_IDS;

        // Try to get from cache
        $cached = $this->getCachedData($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Build API URL
        $url = self::API_BASE_URL . '/posts';
        $params = [
            '_fields' => 'id,date,modified,slug,status,type,link,title,excerpt,jetpack_featured_media_url',
            'page' => $page + 1, // Convert to 1-indexed
            'per_page' => $perPage,
            'order' => $order,
            'orderby' => $orderBy,
            // WordPress.com REST API: exclude category IDs
            // e.g. ?categories_exclude=782413856
            'categories_exclude' => self::EXCLUDED_CATEGORY_IDS,
        ];

        $url .= '?' . http_build_query($params);

        try {
            $response = $this->makeRequest($url);

            if ($response['status'] !== 200) {
                \Logger::channel('wordpress')->error('WordPress API error', [
                    'status' => $response['status'],
                    'url' => $url,
                    'body' => $response['body']
                ]);

                // Try to return stale cache on error
                return $this->getCachedData($cacheKey, true) ?: ['articles' => [], 'totalPages' => 0, 'currentPage' => $page];
            }

            $articles = json_decode($response['body'], true);
            $totalPages = isset($response['headers']['x-wp-totalpages']) ? (int)$response['headers']['x-wp-totalpages'] : 1;

            if (!is_array($articles)) {
                \Logger::channel('wordpress')->error('Invalid WordPress API response', ['response' => $response['body']]);
                return ['articles' => [], 'totalPages' => 0, 'currentPage' => $page];
            }

            // Process articles
            $processedArticles = array_map(function($article) {
                return $this->processArticle($article, false);
            }, $articles);

            $result = [
                'articles' => $processedArticles,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];

            // Cache the result
            $this->setCachedData($cacheKey, $result);

            return $result;

        } catch (\Exception $e) {
            \Logger::channel('wordpress')->error('WordPress API exception', [
                'message' => $e->getMessage(),
                'url' => $url
            ]);

            // Try to return stale cache on error
            return $this->getCachedData($cacheKey, true) ?: ['articles' => [], 'totalPages' => 0, 'currentPage' => $page];
        }
    }

    /**
     * Get single article by ID
     *
     * @param int $id Article ID
     * @return array|null Article data or null on error
     */
    public function getArticle($id)
    {
        $cacheKey = "wp_post_{$id}";

        // Try to get from cache
        $cached = $this->getCachedData($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Build API URL
        $url = self::API_BASE_URL . '/posts/' . $id;
        $params = [
            '_fields' => 'id,date,modified,slug,status,type,link,title,content,jetpack_featured_media_url',
        ];

        $url .= '?' . http_build_query($params);

        try {
            $response = $this->makeRequest($url);

            if ($response['status'] !== 200) {
                \Logger::channel('wordpress')->error('WordPress API error for article', [
                    'status' => $response['status'],
                    'id' => $id,
                    'url' => $url,
                    'body' => $response['body']
                ]);

                // Try to return stale cache on error
                return $this->getCachedData($cacheKey, true);
            }

            $article = json_decode($response['body'], true);

            if (!is_array($article)) {
                \Logger::channel('wordpress')->error('Invalid WordPress API article response', ['response' => $response['body']]);
                return null;
            }

            // Process article
            $processedArticle = $this->processArticle($article, true);

            // Cache the result
            $this->setCachedData($cacheKey, $processedArticle);

            return $processedArticle;

        } catch (\Exception $e) {
            \Logger::channel('wordpress')->error('WordPress API exception for article', [
                'message' => $e->getMessage(),
                'id' => $id,
                'url' => $url
            ]);

            // Try to return stale cache on error
            return $this->getCachedData($cacheKey, true);
        }
    }

    /**
     * Make HTTP request using cURL
     *
     * @param string $url URL to request
     * @return array ['status' => int, 'headers' => array, 'body' => string]
     */
    private function makeRequest($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error);
        }

        curl_close($ch);

        // Split headers and body
        $headerString = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Parse headers
        $headers = $this->parseHeaders($headerString);

        return [
            'status' => $httpCode,
            'headers' => $headers,
            'body' => $body
        ];
    }

    /**
     * Parse HTTP headers
     *
     * @param string $headerString Raw header string
     * @return array Parsed headers
     */
    private function parseHeaders($headerString)
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Process article data to Godo format
     *
     * @param array $article Raw WordPress article
     * @param bool $includeContent Include full content (for detail page)
     * @return array Processed article
     */
    private function processArticle($article, $includeContent = false)
    {
        $processed = [
            'articleId' => $article['id'] ?? 0,
            'subject' => $article['title']['rendered'] ?? '',
            'slug' => $article['slug'] ?? '',
            'regDate' => $this->formatDate($article['date'] ?? ''),
            'originalUrl' => $article['link'] ?? '',
            'imageUrl' => $article['jetpack_featured_media_url'] ?? '',
        ];

        if ($includeContent) {
            // For detail page, include full content
            $processed['content'] = $article['content']['rendered'] ?? '';
        } else {
            // For list page, include excerpt
            $processed['summary'] = strip_tags($article['excerpt']['rendered'] ?? '');
        }

        return $processed;
    }

    /**
     * Format WordPress date to Korean format
     *
     * @param string $date WordPress date string
     * @return string Formatted date
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }

        try {
            $timestamp = strtotime($date);
            return date('Y.m.d', $timestamp);
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @param bool $ignoreExpiry Ignore expiry time (for serving stale cache on error)
     * @return mixed|null Cached data or null if not found/expired
     */
    private function getCachedData($key, $ignoreExpiry = false)
    {
        $cacheFile = $this->cacheDir . '/' . md5($key) . '.cache';

        if (!file_exists($cacheFile)) {
            return null;
        }

        if (!$ignoreExpiry) {
            $fileTime = filemtime($cacheFile);
            if (time() - $fileTime > self::CACHE_TTL) {
                return null;
            }
        }

        $data = file_get_contents($cacheFile);
        return unserialize($data);
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @return bool Success
     */
    private function setCachedData($key, $data)
    {
        $cacheFile = $this->cacheDir . '/' . md5($key) . '.cache';
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }

    /**
     * Clear all cache
     *
     * @return bool Success
     */
    public function clearCache()
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
}
