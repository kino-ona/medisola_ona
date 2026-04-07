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
namespace Controller\Front\Mypage;

/**
 * Class MypageQnaController
 *
 * @package Bundle\Controller\Front\Mypage
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class IndexController extends \Bundle\Controller\Front\Mypage\IndexController
{
	public function index()
	{
		parent::index();

		/* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */
        $useGift = \App::load('\\Component\\Wm\\UseGift');
        $orderData = $this->getData('orderData');
		
		$firstDelivery = \App::load(\Component\Wm\FirstDelivery::class);

        foreach ($orderData as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $isGiftOrder = $useGift->getGiftUse($val['orderNo']);
                $orderData[$key]['goods'][0]['isGiftOrder'] = $isGiftOrder['isGiftOrder'];
            }
        }
        /* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 끝 */
		

		foreach($orderData as $key => $value){
			$filteredGoodsCnt = 0;
			foreach($value['goods'] as $key_1 => $goods){
				$firstDate = $firstDelivery -> getFirstDeliveryOrder($goods['sno']);
				if($firstDate){
					$firstDate = substr($firstDate, 0, 10);
					$firstDate = substr($firstDate, 5 , 5);
					$firstDate = str_replace('-' , '월 ' , $firstDate);
					$firstDate .= '일';
				
					$orderData[$key]['goods'][$key_1]['firstDate'] = $firstDate;
				}

				if ($goods["isComponentGoods"] != true) {
					$filteredGoodsCnt++;
				}
			}
			$orderData[$key]['orderGoodsCnt'] = $filteredGoodsCnt;
		}
		$this->setData('orderData', gd_isset($orderData));
	}
}