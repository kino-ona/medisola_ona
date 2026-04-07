<?php

namespace Component\Crema;

use Logger;

class CremaReviewApi
{
    const OAUTH_URL = 'https://api.cre.ma/oauth/token';
    const REVIEW_URL = 'https://api.cre.ma/v1/reviews';
    const CONNECT_TIMEOUT = 5;
    const REQUEST_TIMEOUT = 10;
    const MAX_WINDOW_LOOPS = 24; // 45-day windows, about 3 years

    private static $tokenCache = [];

    public function getReviewsByGoodsNo($goodsNo, $limit = 3)
    {
        $goodsNo = trim((string)$goodsNo);
        $limit = (int)$limit;
        if ($goodsNo === '' || $goodsNo === '0') {
            return [];
        }
        if ($limit <= 0) {
            $limit = 3;
        }

        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            Logger::channel('crema')->error('Crema: skipping review fetch, no access token', [
                'goodsNo' => $goodsNo,
            ]);
            return [];
        }

        try {
            $reviews = [];
            $reviewIds = [];
            $loop = 0;
            $windowEnd = new \DateTime('now', new \DateTimeZone('Asia/Seoul'));

            while (count($reviews) < $limit && $loop < self::MAX_WINDOW_LOOPS) {
                $windowStart = clone $windowEnd;
                $windowStart->modify('-44 days');

                $url = self::REVIEW_URL . '?' . http_build_query([
                    'access_token' => $accessToken,
                    'product_code' => $goodsNo,
                    'limit' => min(100, $limit),
                    'page' => 1,
                    'date_order_desc' => 1,
                    'start_date' => $windowStart->format('Y-m-d'),
                    'end_date' => $windowEnd->format('Y-m-d'),
                    'score' => 5,
                    'photo' => 1,
                ]);

                $response = $this->request('GET', $url);

                if ((int)$response['status'] !== 200) {
                    Logger::channel('crema')->error('Crema review API error', [
                        'status' => $response['status'],
                        'goodsNo' => $goodsNo,
                        'startDate' => $windowStart->format('Y-m-d'),
                        'endDate' => $windowEnd->format('Y-m-d'),
                    ]);
                    break;
                }

                $decoded = json_decode($response['body'], true);
                if (!is_array($decoded) || isset($decoded['error'])) {
                    Logger::channel('crema')->error('Crema review unexpected response', [
                        'goodsNo' => $goodsNo,
                        'bodySnippet' => mb_substr((string)$response['body'], 0, 200),
                    ]);
                    break;
                }

                foreach ($decoded as $review) {
                    if (!is_array($review) || empty($review['id'])) {
                        continue;
                    }
                    $reviewId = (int)$review['id'];
                    if ($reviewId <= 0 || isset($reviewIds[$reviewId])) {
                        continue;
                    }
                    $reviewIds[$reviewId] = true;

                    $message = trim(strip_tags((string)($review['message'] ?? '')));
                    $reviewImage = $this->extractReviewThumbnail($review);
                    $reviews[] = [
                        'id' => $reviewId,
                        'score' => (int)($review['score'] ?? 0),
                        'message' => $this->truncate($message, 80),
                        'userName' => (string)($review['user_name'] ?? ''),
                        'createdAt' => $this->formatDate((string)($review['created_at'] ?? '')),
                        'thumbnail' => $reviewImage,
                    ];

                    if (count($reviews) >= $limit) {
                        break 2;
                    }
                }

                $windowEnd = clone $windowStart;
                $windowEnd->modify('-1 day');
                $loop++;
            }

            Logger::channel('crema')->info('Crema review fetch result', [
                'goodsNo' => $goodsNo,
                'reviewCount' => count($reviews),
                'loops' => $loop,
            ]);

            return array_slice($reviews, 0, $limit);
        } catch (\Exception $e) {
            Logger::channel('crema')->error('Crema review exception', [
                'goodsNo' => $goodsNo,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function getAccessToken()
    {
        $cremaConfig = gd_policy('service.crema');
        $clientId = trim((string)($cremaConfig['clientId'] ?? ''));
        $clientSecret = trim((string)($cremaConfig['clientSecret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            Logger::channel('crema')->error('Crema config missing client credentials');
            return null;
        }

        $cacheKey = md5($clientId);
        $cached = self::$tokenCache[$cacheKey] ?? null;
        if (!empty($cached['accessToken']) && (int)$cached['expiresAt'] > time()) {
            return $cached['accessToken'];
        }

        $postData = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $response = $this->request('POST', self::OAUTH_URL, $postData);

        if ((int)$response['status'] !== 200) {
            Logger::channel('crema')->error('Crema oauth error', [
                'status' => $response['status'],
            ]);
            return null;
        }

        $decoded = json_decode($response['body'], true);
        $accessToken = (string)($decoded['access_token'] ?? '');
        if ($accessToken === '') {
            Logger::channel('crema')->error('Crema oauth token parse failed');
            return null;
        }

        $expiresIn = (int)($decoded['expires_in'] ?? 3600);
        self::$tokenCache[$cacheKey] = [
            'accessToken' => $accessToken,
            'expiresAt' => time() + max(60, $expiresIn - 60),
        ];

        return $accessToken;
    }

    private function request($method, $url, $body = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$body);
        }

        $result = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Crema request failed: ' . $error);
        }
        curl_close($ch);

        return [
            'status' => $status,
            'body' => (string)$result,
        ];
    }

    private function truncate($text, $maxLen)
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }
        return mb_substr($text, 0, $maxLen) . '...';
    }

    private function formatDate($raw)
    {
        if ($raw === '') {
            return '';
        }
        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return '';
        }
        return date('Y.m.d', $timestamp);
    }

    private function extractReviewThumbnail($review)
    {
        $images = $review['images'] ?? [];
        if (!empty($images[0])) {
            $image = $images[0];
            if (!empty($image['thumbnail_url'])) {
                return (string)$image['thumbnail_url'];
            }
            if (!empty($image['gallery_url'])) {
                return (string)$image['gallery_url'];
            }
            if (!empty($image['url'])) {
                return (string)$image['url'];
            }
        }

        return '';
    }
}
