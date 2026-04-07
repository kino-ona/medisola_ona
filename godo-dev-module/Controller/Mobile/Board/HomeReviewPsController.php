<?php

namespace Controller\Mobile\Board;

use App;
use Request;
use Logger;

class HomeReviewPsController extends \Controller\Mobile\Controller
{
    public function index()
    {
        $mode = Request::get()->get('mode', Request::post()->get('mode'));

        switch ($mode) {
            case 'get_tab':
                $this->handleGetTab();
                break;
            case 'get_tab_reviews':
                $this->handleGetTabReviews();
                break;
            case 'get_more_reviews':
                $this->handleGetMoreReviews();
                break;
            case 'get_group_reviews':
                $this->handleGetGroupReviews();
                break;
            default:
                $this->json(['error' => 1, 'message' => 'Invalid mode']);
                break;
        }
        exit;
    }

    private function handleGetTab()
    {
        try {
            $tabKey = trim((string)Request::get()->get('tabKey', Request::post()->get('tabKey')));
            $tabConfigSno = trim((string)Request::get()->get('tabConfigSno', Request::post()->get('tabConfigSno')));
            $reviewLimit = (int)Request::get()->get('reviewLimit', Request::post()->get('reviewLimit', 3));
            $initialVisibleCount = (int)Request::get()->get('initialVisibleCount', Request::post()->get('initialVisibleCount', 3));

            if ($tabKey === '' || $tabConfigSno === '') {
                $this->json(['error' => 1, 'message' => 'Missing tabKey or tabConfigSno']);
                return;
            }

            $provider = App::load('\\Component\\Crema\\HomeProductReviewProvider');
            $data = $provider->buildSingleTabData($tabKey, $tabConfigSno, $reviewLimit, $initialVisibleCount);

            if ($data === null) {
                $this->json(['error' => 1, 'message' => 'Tab not found']);
                return;
            }

            $this->json(['error' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            Logger::channel('crema')->error('HomeReviewPs get_tab error (mobile)', [
                'message' => $e->getMessage(),
            ]);
            $this->json(['error' => 1, 'message' => 'Server error']);
        }
    }

    private function handleGetTabReviews()
    {
        try {
            $tabKey = trim((string)Request::get()->get('tabKey', Request::post()->get('tabKey')));
            $tabConfigSno = trim((string)Request::get()->get('tabConfigSno', Request::post()->get('tabConfigSno')));
            $reviewLimit = (int)Request::get()->get('reviewLimit', Request::post()->get('reviewLimit', 3));

            if ($tabKey === '' || $tabConfigSno === '') {
                $this->json(['error' => 1, 'message' => 'Missing tabKey or tabConfigSno']);
                return;
            }

            $provider = App::load('\\Component\\Crema\\HomeProductReviewProvider');
            $data = $provider->getTabReviews($tabKey, $tabConfigSno, $reviewLimit);

            if ($data === null) {
                $this->json(['error' => 1, 'message' => 'Tab not found']);
                return;
            }

            $this->json(['error' => 0, 'reviewsByGoods' => $data]);
        } catch (\Throwable $e) {
            Logger::channel('crema')->error('HomeReviewPs get_tab_reviews error (mobile)', [
                'message' => $e->getMessage(),
            ]);
            $this->json(['error' => 1, 'message' => 'Server error']);
        }
    }

    private function handleGetGroupReviews()
    {
        try {
            $goodsNosRaw = trim((string)Request::get()->get('goodsNos', Request::post()->get('goodsNos')));
            $limit = (int)Request::get()->get('limit', Request::post()->get('limit', 3));

            if ($goodsNosRaw === '') {
                $this->json(['error' => 1, 'message' => 'Missing goodsNos']);
                return;
            }

            $goodsNos = array_filter(array_map('intval', explode(',', $goodsNosRaw)));
            if (empty($goodsNos)) {
                $this->json(['error' => 1, 'message' => 'Invalid goodsNos']);
                return;
            }

            $provider = App::load('\\Component\\Crema\\HomeProductReviewProvider');
            $data = $provider->getGroupReviews($goodsNos, $limit);

            $this->json(['error' => 0, 'reviewsByGoods' => $data]);
        } catch (\Throwable $e) {
            Logger::channel('crema')->error('HomeReviewPs get_group_reviews error (mobile)', [
                'message' => $e->getMessage(),
            ]);
            $this->json(['error' => 1, 'message' => 'Server error']);
        }
    }

    private function handleGetMoreReviews()
    {
        try {
            $goodsNo = (int)Request::get()->get('goodsNo', Request::post()->get('goodsNo'));
            $limit = (int)Request::get()->get('limit', Request::post()->get('limit', 15));

            if ($goodsNo <= 0) {
                $this->json(['error' => 1, 'message' => 'Invalid goodsNo']);
                return;
            }

            $provider = App::load('\\Component\\Crema\\HomeProductReviewProvider');
            $reviews = $provider->getMoreReviews($goodsNo, $limit);

            $this->json(['error' => 0, 'reviews' => $reviews]);
        } catch (\Throwable $e) {
            Logger::channel('crema')->error('HomeReviewPs get_more_reviews error (mobile)', [
                'message' => $e->getMessage(),
            ]);
            $this->json(['error' => 1, 'message' => 'Server error']);
        }
    }
}
