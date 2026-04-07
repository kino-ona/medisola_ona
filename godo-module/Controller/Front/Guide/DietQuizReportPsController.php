<?php

namespace Controller\Front\Guide;

/**
 * 나만의 식단 플랜 - 영양 분석 결과 데이터 AJAX 핸들러
 * POST: 퀴즈 데이터 → 리포트 JSON 반환
 * 추후 AI API로 대체 시 이 컨트롤러 내부만 수정하면 됨
 */
class DietQuizReportPsController extends \Controller\Front\Controller
{
    public function index()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $post = \Request::post()->toArray();

            if (empty($post['gender']) || empty($post['birthYear']) || empty($post['height']) || empty($post['weight'])) {
                echo json_encode(['success' => false, 'error' => '필수 항목을 입력해주세요.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (isset($post['conditions']) && is_string($post['conditions'])) {
                $post['conditions'] = json_decode($post['conditions'], true);
            }
            if (!is_array($post['conditions'] ?? null)) {
                $post['conditions'] = [];
            }

            $dietFinder = \App::load('\\Component\\DietFinder\\DietFinder');
            $reportData = $dietFinder->generateNutritionReport($post);

            echo json_encode([
                'success' => true,
                'report' => $reportData,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => '서버 오류: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
