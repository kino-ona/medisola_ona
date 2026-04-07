<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

namespace Controller\Admin\Goods;

use Request;
use Framework\Debug\Exception\AlertOnlyException;

class GoodsPsController extends \Bundle\Controller\Admin\Goods\GoodsPsController
{
    public function index()
    {
        parent::index();
        // --- 각 배열을 trim 처리
        $postValue = Request::post()->toArray();
        switch ($postValue['mode']) {
            case 'early_delivery_goods' :
                if (empty($postValue['arrGoodsNo']))
                    throw new AlertOnlyException("저장할 상품을 선택하세요.");

                $earlyDelivery = \App::load('\\Component\\Wm\\EarlyDelivery');
                $earlyDelivery->updateEarlyDelivery($postValue);

                return $this->layer("저장하였습니다.");
                break;
            case 'self_register':
                $goodsIcon = \App::load('\\Component\\Wm\\GoodsIcon');
                $goodsIcon->registerGoodsIcon1($postValue);

                return $this->layer("저장하였습니다.");

                break;
            case 'check_register':
                $goodsIcon = \App::load('\\Component\\Wm\\GoodsIcon');
                $goodsIcon->registerGoodsIcon2($postValue);

                return $this->layer("저장하였습니다.");

                break;
            case 'delete_icon':
                $goodsIcon = \App::load('\\Component\\Wm\\GoodsIcon');
                $goodsIcon->deleteGoodsIcon($postValue['sno']);

                return $this->layer("삭제되었습니다.");

                break;

            case 'change_icon_sort':
                $data = explode(',', $postValue['data']);
                foreach ($data as $key => $val) {
                    $data2 = explode('|', $val);
                    $data3[$key]['sno'] = $data2[0];
                    $data3[$key]['sort'] = $data2[1];
                }
                $goodsBanner = \App::load('\\Component\\Wm\\GoodsIcon');
                $goodsBanner->changeIconSort($postValue['goodsNo'], $data3);
                break;
            case 'banner_register':
                $goodsBanner = \App::load('\\Component\\Wm\\GoodsBanner');
                $goodsBanner->registerGoodsBanner($postValue['goodsNo']);

                return $this->layer("저장하였습니다.");
                break;

            case 'delete_banner':
                $goodsBanner = \App::load('\\Component\\Wm\\GoodsBanner');
                $goodsBanner->deleteGoodsBanner($postValue['sno']);

                return $this->layer("삭제되었습니다.");
                break;

            case 'change_banner_sort':
                $data = explode(',', $postValue['data']);
                foreach ($data as $key => $val) {
                    $data2 = explode('|', $val);
                    $data3[$key]['sno'] = $data2[0];
                    $data3[$key]['sort'] = $data2[1];
                }
                $goodsBanner = \App::load('\\Component\\Wm\\GoodsBanner');
                $goodsBanner->changeBannerSort($postValue['goodsNo'], $data3);
                break;
        }
    }
}