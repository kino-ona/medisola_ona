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
namespace Controller\Mobile\Goods;

use Component\Wm\UseGift;


/**
 * Class LayerDeliveryAddress
 *
 * @package Bundle\Controller\Front\Order
 * @author  su
 */
class LayerOptionController extends \Bundle\Controller\Mobile\Goods\LayerOptionController
{
    public function index()
    {
        parent::index();

		// 웹앤모바일 2023-06-09 선물하기
        $getBanner = \App::load(UseGift::class);
        $goodsView = $this->getData('goodsView');
        $goodsView = $getBanner->GiftUseSet($goodsView);

        $this->setData('goodsView', $goodsView);
		//
    }
}