<?php

namespace Controller\Front\Guide;

/**
 * 나만의 식단 플랜 - 퀴즈 응답 저장 AJAX 핸들러
 * URL: /guide/diet_quiz_ps.php (POST)
 */
class DietQuizPsController extends \Controller\Front\Controller
{
    public function index()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $post = \Request::post()->toArray();

            // 필수 필드 검증
            if (empty($post['gender']) || empty($post['birthYear']) || empty($post['height']) || empty($post['weight'])) {
                echo json_encode(['success' => false, 'error' => '필수 항목을 입력해주세요.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // conditions JSON 파싱
            if (isset($post['conditions']) && is_string($post['conditions'])) {
                $post['conditions'] = json_decode($post['conditions'], true);
            }
            if (!is_array($post['conditions'] ?? null)) {
                $post['conditions'] = [];
            }

            $dietFinder = \App::load('\\Component\\DietFinder\\DietFinder');
            $responseSno = $dietFinder->saveQuizResponse($post);

            if ($responseSno) {
                echo json_encode([
                    'success' => true,
                    'responseSno' => $responseSno
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => '저장에 실패했습니다. 다시 시도해주세요.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => '서버 오류: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
