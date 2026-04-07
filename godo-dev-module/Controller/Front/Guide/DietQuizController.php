<?php

namespace Controller\Front\Guide;

/**
 * 나만의 식단 플랜 - 온보딩 퀴즈 페이지 (Desktop)
 * GET: /guide/diet_quiz.php?goodsNo=XXX → 퀴즈 페이지
 * POST 저장은 DietQuizPsController (/guide/diet_quiz_ps.php) 에서 처리
 */
class DietQuizController extends \Controller\Front\Controller
{
    public function index()
    {
        $getValue = \Request::get()->all();
        $goodsNo = isset($getValue['goodsNo']) ? intval($getValue['goodsNo']) : 0;
        $this->setData('goodsNo', $goodsNo);
    }
}
